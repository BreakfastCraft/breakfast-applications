<?php
/**
 * Plugin Name: Breakfastcraft Applictions
 * Version: 0.1
 * Author: Bryan Garcia

 */

require_once( __DIR__ . "/includes/breakfast-applications-list-table.php" );

if ( ! class_exists( 'SourceQuery' ) ) {
	require_once( __DIR__ . "/SourceQuery/SourceQuery.class.php" );
}

class Breakfast_Applications_Plugin {
	var $servers_option = 'breakfast_applications_servers';
	var $app_status = array( 'Pending', 'Approved', 'Denied' );

	public static $app_table, $answer_table, $question_table;

	function __construct() {
		global $wpdb;

		self::$app_table      = $wpdb->prefix . "breakfast_apps_applications";
		self::$answer_table   = $wpdb->prefix . "breakfast_apps_answers";
		self::$question_table = $wpdb->prefix . "breakfast_apps_questions";


		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		register_activation_hook( __FILE__, array( $this, 'install_db' ) );
		add_action( 'admin_footer', array( $this, 'applications_javascript' ) );
		add_shortcode( 'breakfast-application', array( $this, 'form_shortcode' ) );

		add_filter( 'if_menu_conditions', array( $this, 'if_menu_conditions' ) );

	}

	function create_admin_menu() {
		add_menu_page( __( 'Applications', 'breakfast-applications' ), __( 'Applications', 'breakfast-applications' ),
			'promote_users', 'applications', array( $this, 'admin_page_handler' ), 'dashicons-admin-users' );

		add_submenu_page( 'applications', __( 'Questions', 'breakfast-applications' ), __( 'Questions', 'breakfast-applications' ),
			'promote_users', 'applications_questions', array( $this, 'questions_handler' ) );

		add_submenu_page( null, __( 'View Application', 'breakfast-applications' ), __( 'View Application', 'breakfast-applications' ),
			'promote_users', 'applications_view', array( $this, 'view_application_handler' ) );

		add_submenu_page( 'applications', __( 'Servers', 'breakfast-applications' ), __( 'Servers', 'breakfast-applications' ),
			'promote_users', 'applications_servers', array( $this, 'servers_handler' ) );

	}

	function install_db() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$answer_sql = "CREATE TABLE " . self::$answer_table . " (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			application_id BIGINT(20) NOT NULL,
			question_id BIGINT(20) NOT NULL,
			answer TEXT NULL,
			PRIMARY KEY  (id)
			);";

		$application_sql = "CREATE TABLE " . self::$app_table . "  (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			age TINYINT NOT NULL,
			minecraft_name VARCHAR(25) NOT NULL,
			status TINYINT NOT NULL DEFAULT 0,
			applied_on DATETIME NOT NULL,
			decision_on DATETIME NULL,
			PRIMARY KEY  (id)
			);";
		$question_sql    = "CREATE TABLE " . self::$question_table . " (
			id BIGINT(20) NOT NULL AUTO_INCREMENT ,
			question TEXT NOT NULL,
			format VARCHAR(10) NOT NULL DEFAULT 'text',
			status VARCHAR(10) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id)
		);";

		dbDelta( $answer_sql );
		dbDelta( $application_sql );
		dbDelta( $question_sql );

		add_option( 'breakfast_applications_db_version', '0.1' );

		$installed_ver = get_option( 'breakfast_applications_db_version' );
		if ( $installed_ver != '0.1' ) {
			update_option( 'breakfast_applications_db_version', '0.1' );
		}
	}

	function admin_page_handler() {
		global $wpdb;
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
			<h2><?php _e( 'Applications', 'breakfast-application' ) ?></h2>

			<?php
			$table = new Breakfast_Applications_List_Table( self::$app_table, self::$answer_table );
			$table->prepare_items();
			$table->display();
			?>
		</div>
	<?php
	}

	function servers_handler() {

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] ) ) {
			$servers = array();
			if ( isset( $_POST['servers'] ) ) {
				foreach ( $_POST['servers'] as $server ) {
					if ( ! empty( $server['host'] ) && ! empty( $server['port'] ) && ! empty( $server['pass'] ) ) {
						$servers[] = $server;
					}
				}
			}
			update_option( $this->servers_option, $servers );
		}

		if ( ! isset( $servers ) ) {
			$servers = get_option( $this->servers_option );
		}
		add_meta_box( 'servers_meta_box', 'Servers', array(
			$this,
			'servers_meta_box'
		), 'servers_meta_box', 'normal', 'default' );
		?>
		<div class="wrap">
			<?php
			printf( '<h2>%s</h2>', esc_html__( 'Applications Servers', 'breakfast-applications' ) );
			?>
			<form name="servers_form" method="post">
				<?php wp_nonce_field(); ?>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-1">
						<div class="postbox-container-1">
							<?php do_meta_boxes( 'servers_meta_box', 'normal', $servers ); ?>
						</div>
					</div>
				</div>
			</form>
		</div>
	<?php
	}

	function servers_meta_box( $servers ) {
		include( __DIR__ . '/views/servers.metabox.php' );
	}

	function questions_handler() {
		global $wpdb;
		$message = '';
		$notice  = '';

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'questions' ) ) {
			$active_ids = array();
			foreach ( $_POST['questions'] as $question ) {
				$question['question'] = trim( $question['question'] );

				if ( is_numeric( $question['id'] ) ) {
					//update existing question
					$wpdb->update(
						$this->question_table,
						array(
							'question' => $question['question'],
							'format'   => $question['format'],
							'status'   => 'active'
						),
						array( 'id' => (int) $question['id'] ),
						'%s',
						'%d' );
					$active_ids[] = $question['id'];
				} else {
					//insert new question
					if ( ! empty( $question['question'] ) ) {
						$wpdb->insert(
							self::$question_table,
							array(
								'question' => $question['question'],
								'format'   => $question['format'],
								'status'   => 'active'
							)
						);
						$active_ids[] = $wpdb->insert_id;
					}
				}

				// set all inactive
				$wpdb->update( self::$question_table, array( 'status' => 'inactive' ), array( 'status' => 'active' ) );

				//set questions active
				$actives = array();
				foreach ( $active_ids as $id ) {
					$actives[] = "id = $id";
				}
				$wpdb->query( "UPDATE " . self::$question_table . " SET status='active' WHERE " . join( ' OR ', $actives ) );
			}
			//add/update questions
		}

		$questions = $wpdb->get_results( "SELECT * FROM " . self::$question_table . " WHERE status='active'", ARRAY_A );
		add_meta_box( 'questions_meta_box', 'Questions', array(
			$this,
			'questions_meta_box'
		), 'questions', 'normal', 'default' );

		include( __DIR__ . '/views/questions.php' );
	}

	function questions_meta_box( $questions ) {
		include( __DIR__ . '/views/questions.metabox.php' );
	}

	function applications_javascript() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				var newID = 0;
				$('.breakfast-question button.remove').click(function (event) {
					event.preventDefault();
					$(event.target).parent().remove();
				});

				$('button#breakfast-add-question').click(function (event) {
					event.preventDefault();
					$('#breakfast-questions').append('<div class="breakfast-question" id="breakfast-new-' + newID + '">' +
					'<input type="hidden" value="new" name="questions[new-' + newID + '][id]"/>' +
					'<textarea cols="70" rows="5" class="input" name="questions[new-' + newID + '][question]"></textarea> ' +
					'<br/><select name="questions[new-' + newID + '][format]"><option value="text">Text</option> ' +
					'<option value="textarea">Textarea</option></select><br /><button ' +
					'class="button remove">Remove</button><hr/></div>');
					newID += 1;
				});

				var serverNewID = 0;
				$('.breakfast-server button.remove').click(function (event) {
					event.preventDefault();
					$(event.target).parent().remove();
				});

				$('button#breakfast-add-server').click(function (event) {
					event.preventDefault();
					$('#breakfast-servers').append('<div class="breakfast-server" id="breakfast-new-' + serverNewID + '">' +
					'<label for="host-new-' + serverNewID + '">Host:</label>' +
					'<input  type="text" name="servers[new-' + serverNewID + '][host]" id="host-new-' + serverNewID + '"/>' +
					'<label for="port-new-' + serverNewID + '">Port:</label>' +
					'<input  type="text" name="servers[new-' + serverNewID + '][port]" id="port-new-' + serverNewID + '"/>' +
					'<label for="pass-new-' + serverNewID + '">Password:</label>' +
					'<input type="text" name="servers[new-' + serverNewID + '][pass]" id="pass-new-' + serverNewID + '"/>' +
					'<label for="host-new-' + serverNewID + '">Master:</label>' +
					'<input type="hidden" name="servers[new-' + serverNewID + '][master]" />' +
					'<input type="checkbox" name="servers[new-' + serverNewID + '][master]" id="host-new-' + serverNewID + '"/>' +
					'<button class="button remove">Remove</button>' +
					'<hr/>' +
					'</div>');
					serverNewID += 1;
				});


			});
		</script>
	<?php
	}

	function view_application_handler() {
		global $wpdb;
		$message     = '';
		$notice      = '';
		$application = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . self::$app_table . " WHERE id = %d", $_REQUEST['id'] ), ARRAY_A );
		if ( empty( $application ) ) {
			wp_redirect( '?page=applications&message=' . urlencode( 'Unable to find application #' . $_REQUEST['id'] . '.' ) );
			exit( 0 );

		} else {
			$application = $application[0];
		}
		$user = get_user_by( 'id', $application['user_id'] );

		$answers = $wpdb->get_results( $wpdb->prepare( "
				SELECT " . self::$answer_table . ".*, " . self::$question_table . ".question
				FROM " . self::$answer_table . ", " . self::$question_table . "
				WHERE
					application_id=%d
					AND
					" . self::$answer_table . ".question_id = " . self::$question_table . ".id
				ORDER BY id ASC", $application['id'] ), ARRAY_A );

		$ban_info = json_decode( file_get_contents( 'http://api.fishbans.com/bans/' . $application['minecraft_name'] ), true );
		$bans     = 0;
		if ( $ban_info['success'] ) {
			foreach ( $ban_info['bans']['service'] as $service ) {
				$bans += $service['bans'];
			}
		}
		if ( ! empty( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'view_application' ) ) {
			if ( $_REQUEST['op'] == 'approve' ) {
				$message = $this->approve( $_REQUEST['id'] );
			} elseif ( $_REQUEST['op'] == 'deny' ) {
				$message = $this->deny( $_REQUEST['id'] );
			} else {
				$message = 'Invalid operation.';
			}
		}
		//include page html
		include( __DIR__ . '/views/view_application.php' );

	}

	//[breakfast-application]
	function form_shortcode( $atts ) {
		global $wpdb;
		$a = shortcode_atts( array(
			'title' => 'Apply to BreakfastCraft Servers'
		), $atts );

		$questions   = $wpdb->get_results( "SELECT * FROM " . self::$question_table . " WHERE status='active'", ARRAY_A );
		$application = $wpdb->get_row( "SELECT * FROM " . self::$app_table . " WHERE user_id=" . get_current_user_id(), ARRAY_A );

		if ( $application == null ) {
			$application = array( 'age' => '', 'minecraft_name' => '' );
			$answers     = array();
		} else {
			$answers = $wpdb->get_results( "SELECT * FROM " . self::$answer_table . " WHERE application_id={$application['id']}", ARRAY_A );
		}

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ) ) {
			if ( isset( $application['id'] ) ) {
				$wpdb->update(
					self::$app_table,
					array(
						'age'            => (int) $_POST['age'],
						'minecraft_name' => $_POST['minecraft_name'],
						'applied_on'     => current_time( 'mysql', 1 )
					),
					array( 'id' => $application['id'] ),
					array( '%d', '%s', '%s' ),
					array( '%d' )
				);
				foreach ( $questions as $question ) {

					$answer = sub_array_find( $question['id'], $answers, 'id' );

					if ( $answer ) {
						$wpdb->update(
							self::$answer_table,
							array( 'answer' => $_POST[ 'question-' . $question['id'] ] ),
							array( 'id' => $answer['id'] )
						);
					} else {
						$wpdb->insert(
							self::$answer_table,
							array(
								'application_id' => $application['id'],
								'question_id'    => $question['id'],
								'answer'         => $_POST[ 'question-' . $question['id'] ]
							)
						);
					}
				}
			} else {
				$wpdb->insert(
					self::$app_table,
					array(
						'user_id'        => get_current_user_id(),
						'age'            => (int) $_POST['age'],
						'minecraft_name' => $_POST['minecraft_name'],
						'applied_on'     => current_time( 'mysql', 1 )
					),
					array( '%d', '%s', '%s', '%s' )
				);
				$application = $wpdb->get_row( "SELECT * FROM " . self::$app_table . " WHERE id=" . $wpdb->insert_id, ARRAY_A );

				foreach ( $questions as $question ) {

					$wpdb->insert(
						self::$answer_table,
						array(
							'application_id' => $application['id'],
							'question_id'    => $question['id'],
							'answer'         => $_POST[ 'question-' . $question['id'] ]
						)
					);
				}
			}
			$message = "<strong>Thank you your applying.</strong> We will review your application and get back to you as quickly as we can.";
		}
		ob_start();
		include( __DIR__ . '/views/application.php' );
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	function approve( $app_id ) {
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM " . self::$app_table . " WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$this->whitelist( $app['minecraft_name'] );
		$wpdb->update( self::$app_table, array(
			'status'      => 1,
			'decision_on' => current_time( 'mysql', 1 )
		), array( 'id' => $app['id'] ), array( '%d', '%s' ), '%d' );

		return 'Application approved.';

	}

	function deny( $app_id ) {
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM " . self::$app_table . " WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$wpdb->update( self::$app_table, array(
			'status'      => 2,
			'decision_on' => current_time( 'mysql', 1 )
		), array( 'id' => $app['id'] ), array( '%d', '%s' ), '%d' );

		return 'Application denied.';
	}

	function whitelist( $name ) {
		$servers = get_option( $this->servers_option );
		foreach ( $servers as $server ) {
			if ( $server['master'] ) {
				$query = new SourceQuery();
				try {
					$query->Connect( $server['host'], $server['port'], 1, SourceQuery::SOURCE );
					$query->SetRconPassword( $server['pass'] );
					$query->Rcon( "whitelist add $name" );
				} catch ( Exception $e ) {
					echo $e->getMessage();
				}
			}
		}

		foreach ( $servers as $server ) {
			$query = new SourceQuery();
			try {
				$query->Connect( $server['host'], $server['port'], 1, SourceQuery::SOURCE );
				$query->SetRconPassword( $server['pass'] );
				$query->Rcon( "whitelist reload" );

			} catch ( Exception $e ) {
				echo $e->getMessage();
			}
		}

	}

	function if_menu_conditions() {
		$conditions[] = array(
			'name'      => 'If logged in and application not approved',
			'condition' => function ( $item ) {
				global $wpdb;
				if ( ! is_user_logged_in() ) {
					return false;
				}
				$application = $wpdb->get_row( "SELECT * FROM " . Breakfast_Applications_Plugin::$app_table . " WHERE user_id=" . get_current_user_id(), ARRAY_A );
				if ( $application == null || $application['status'] != 1 ) {
					return true;
				}

				return false;
			}
		);

		return $conditions;
	}

}


function sub_array_find( $needle, $haystack, $property ) {
	foreach ( $haystack as $hay ) {
		if ( $hay[ $property ] == $needle ) {
			return $hay;
		}
	}

	return null;
}


new Breakfast_Applications_Plugin();

