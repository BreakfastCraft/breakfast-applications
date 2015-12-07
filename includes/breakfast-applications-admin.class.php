<?php

/**
 * Created by PhpStorm.
 * User: bryan
 * Date: 13/09/2014
 * Time: 16:46
 */
class Breakfast_Applications_Admin extends Breakfast_Applications_Base {
	var $servers_option = 'breakfast_applications_servers';
	var $app_status = array( 'Pending', 'Approved', 'Denied' );

	public function __construct() {
		parent::__construct();
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		register_activation_hook( plugin_dir_path( __DIR__ ) . 'breakfast-applications.php', array(
			$this,
			'install_db'
		) );
		add_action( 'admin_footer', array( $this, 'applications_javascript' ) );
	}

	function install_db() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$answer_sql = "CREATE TABLE " . $this->answer_table . " (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			application_id BIGINT(20) NOT NULL,
			question_id BIGINT(20) NOT NULL,
			answer TEXT NULL,
			PRIMARY KEY  (id)
			);";

		$application_sql = "CREATE TABLE " . $this->app_table . "  (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			age TINYINT NOT NULL,
			minecraft_name VARCHAR(25) NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'pending',
			applied_on DATETIME NOT NULL,
			decision_on DATETIME NULL,
			reason TEXT NULL,
			PRIMARY KEY  (id)
			);";
		$question_sql    = "CREATE TABLE " . $this->question_table . " (
			id BIGINT(20) NOT NULL AUTO_INCREMENT ,
			question TEXT NOT NULL,
			format VARCHAR(10) NOT NULL DEFAULT 'text',
			status VARCHAR(10) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id)
		);";

		dbDelta( $answer_sql );
		dbDelta( $application_sql );
		dbDelta( $question_sql );

		add_option( 'breakfast_applications_db_version', $this->version );

		$installed_ver = get_option( 'breakfast_applications_db_version' );
		if ( $installed_ver != $this->version ) {
			update_option( 'breakfast_applications_db_version', $this->version );
		}
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

		add_submenu_page( 'applications', __( 'Settings', 'breakfast-applications' ), __( 'Settings', 'breakfast-applications'),
			'promote_users', 'applications_settings', array( $this, 'settings_handler') );

	}


	function admin_page_handler() {
		global $wpdb;
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
			<h2><?php _e( 'Applications', 'breakfast-application' ) ?></h2>
			<div style="float: right;">

				<a href="?page=applications&tab=pending">Pending</a> | <a href="?page=applications&tab=approved">Approved</a> |  <a href="?page=applications&tab=denied">Denied</a>
				
			</div>
			<?php
			$tab = ($_REQUEST['tab'] == 'approved' || $_REQUEST['tab'] == 'denied' || $_REQUEST['tab'] == 'pending') ? $_REQUEST['tab'] : 'pending';
			$table = new Breakfast_Applications_List_Table( $this->app_table, $this->answer_table, $tab);
			$table->prepare_items();
			$table->display();
			?>
		</div>
	<?php
	}

	function settings_handler() {
		if ( isset( $_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'] ) ) {
			if ( isset( $_POST['settings'])) {
				update_option( $this->settings_option, $_POST['settings']);
			}
		}

		$settings = get_option($this->settings_option);
		if ( ! isset( $settings['notify_new'] ) ) {
			$settings['notify_new'] = '';
		}
		if ( ! isset( $settings['notify_approved'] ) ) {
			$settings['notify_approved'] = '';
		}
		if ( ! isset( $settings['admin_notify_new_email'] ) ) {
			$settings['admin_notify_new_email'] = '';
		}
		if ( ! isset( $settings['admin_notify_approved_email'] ) ) {
			$settings['admin_notify_approved_email'] = '';
		}
		if ( ! isset( $settings['user_notify_approval_email'] ) ) {
			$settings['user_notify_approval_email'] = '';
		}
		if ( ! isset( $settings['user_notify_denial_email'] ) ) {
			$settings['user_notify_denial_email'] = '';
		}
		if ( ! isset( $settings['admin_notify_new_subject'] ) ) {
			$settings['admin_notify_new_subject'] = '';
		}
		if ( ! isset( $settings['admin_notify_approved_subject'] ) ) {
			$settings['admin_notify_approved_subject'] = '';
		}
		if ( ! isset( $settings['user_notify_approval_subject'] ) ) {
			$settings['user_notify_approval_subject'] = '';
		}
		if ( ! isset( $settings['user_notify_denial_subject'] ) ) {
			$settings['user_notify_denial_subject'] = '';
		}



		?>
		<div class="wrap">
			<?php
			printf( '<h2>%s</h2>', esc_html__( 'Applications Settings', 'breakfast-applications') );
			?>
			<form name="settings_form" method="post">
				<?php wp_nonce_field(); ?>
				<?php include plugin_dir_path( __DIR__ ) . 'views/settings.php'; ?>
			</form>
		</div>
		<?php
	}
	function settings_meta_box( $settings ) {
		include plugin_dir_path( __DIR__ ) . 'views/settings.php';
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
		include plugin_dir_path( __DIR__ ) . 'views/servers.metabox.php';
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
							$this->question_table,
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
				$wpdb->update( $this->question_table, array( 'status' => 'inactive' ), array( 'status' => 'active' ) );

				//set questions active
				$actives = array();
				foreach ( $active_ids as $id ) {
					$actives[] = "id = $id";
				}
				$wpdb->query( "UPDATE " . $this->question_table . " SET status='active' WHERE " . join( ' OR ', $actives ) );
			}
			//add/update questions
		}

		$questions = $wpdb->get_results( "SELECT * FROM " . $this->question_table . " WHERE STATUS='active'", ARRAY_A );
		add_meta_box( 'questions_meta_box', 'Questions', array(
			$this,
			'questions_meta_box'
		), 'questions', 'normal', 'default' );

		include plugin_dir_path( __DIR__ ) . 'views/questions.php';
	}

	function questions_meta_box( $questions ) {
		include plugin_dir_path( __DIR__ ) . 'views/questions.metabox.php';
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

		if ( ! empty( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'view_application' ) ) {
			if ( $_REQUEST['op'] == 'approve' ) {
				$message = $this->approve( $_REQUEST['id'], $_REQUEST['reason'] );
			} elseif ( $_REQUEST['op'] == 'deny' ) {
				$message = $this->deny( $_REQUEST['id'], $_REQUEST['reason'] );
			} else {
				$message = 'Invalid operation.';
			}
		}

		global $wpdb;
		$message     = '';
		$notice      = '';
		$application = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $this->app_table . " WHERE id = %d", $_REQUEST['id'] ), ARRAY_A );
		if ( empty( $application ) ) {
			wp_redirect( '?page=applications&message=' . urlencode( 'Unable to find application #' . $_REQUEST['id'] . '.' ) );
			exit( 0 );

		} else {
			$application = $application[0];
		}
		$user = get_user_by( 'id', $application['user_id'] );

		$answers = $wpdb->get_results( $wpdb->prepare( "
				SELECT " . $this->answer_table . ".*, " . $this->question_table . ".question
				FROM " . $this->answer_table . ", " . $this->question_table . "
				WHERE
					application_id=%d
					AND
					" . $this->answer_table . ".question_id = " . $this->question_table . ".id
				ORDER BY id ASC", $application['id'] ), ARRAY_A );

		$ban_info = json_decode( file_get_contents( 'http://api.fishbans.com/bans/' . $application['minecraft_name'] ), true );
		$bans     = 0;
		if ( $ban_info['success'] ) {
			foreach ( $ban_info['bans']['service'] as $service ) {
				$bans += $service['bans'];
			}
		}

		//include page html
		include plugin_dir_path( __DIR__ ) . 'views/view_application.php';

	}

	function approve( $app_id, $note ) {
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$this->whitelist( $app['minecraft_name'] );
		$this->admin_approve_email($app['minecraft_name'], $note);
		$wpdb->update( $this->app_table, array(
			'status'      => 'approved',
			'reason'	  => $note,
			'decision_on' => current_time( 'mysql', 1 )
		), array( 'id' => $app['id'] ), array( '%s', '%s' ), '%d' );
		$this->user_approve_email( $app['user_id'], $note );

		return 'Application approved.';

	}

	function deny( $app_id, $reason ) {
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$wpdb->update( $this->app_table, array(
			'status'      => 'denied',
			'reason'      => $reason,
			'decision_on' => current_time( 'mysql', 1 )
		), array( 'id' => $app['id'] ), array( '%s', '%s', '%s' ), '%d' );
		$this->user_deny_email( $app['user_id'], $reason );

		return 'Application denied.';
	}

	function user_deny_email( $user_id, $reason ) {
		$settings = get_option( $this->settings_option );
		$content = stripslashes( $settings['user_notify_denial_email'] );
		if ( ! empty ( $content ) && ! empty( $settings['user_notify_denial_subject'] ) ) {
			$content = str_replace ( '{{reason}}', $reason, $content );
			$user = get_user_by ( 'id', $user_id );
			wp_mail ( $user->data->user_email, $settings['user_notify_denial_subject'], $content );
		}
	}

	function user_approve_email( $user_id, $note ) {
		$settings = get_option( $this->settings_option );
		$content = stripslashes ( $settings['user_notify_approval_email'] );
		if ( ! empty ( $content ) && ! empty ( $settings['user_notify_approval_subject'] ) ) {
			if ( ! empty ( $note ) ) {
				$note = "Note:\r\n$note";
			}
			$content = str_replace ( '{{note}}', $note, $content );

			$user = get_user_by ( 'id', $user_id );
			wp_mail ( $user->data->user_email, $settings['user_notify_approval_subject'], $content );
		}
	}

	function admin_approve_email($name) {
		$settings = get_option( $this->settings_option );
		$content = stripslashes ( $settings['admin_notify_approval_email'] );
		if ( ! empty ( $content ) && ! empty ( $settings['admin_notify_approval_subject'] ) ) {
			$content = str_replace ( '{{name}}', $name, $content );

			foreach ( explode ( ',', $settings['notify_approved'] ) as $name ) {
				$name = trim ( $name );
				$user = get_user_by ( 'login', $name );
				if ( $user ) {
					wp_mail ( $user->data->user_email, $settings['admin_notify_approval_subject'], $content );
				}
			}
		}


	}

	function whitelist( $name ) {
		$servers = get_option( $this->servers_option );
		if ( is_array ( $servers ) ) {
			foreach ( $servers as $server ) {
				if ( $server['master'] ) {
					$query = new SourceQuery();
					try {
						$query->Connect ( $server['host'], $server['port'], 1, SourceQuery::SOURCE );
						$query->SetRconPassword ( $server['pass'] );
						$query->Rcon ( "whitelist add $name" );
					} catch ( Exception $e ) {
						echo $e->getMessage ();
					}
				}
			}


			foreach ( $servers as $server ) {
				$query = new SourceQuery();
				try {
					$query->Connect ( $server['host'], $server['port'], 1, SourceQuery::SOURCE );
					$query->SetRconPassword ( $server['pass'] );
					$query->Rcon ( "whitelist reload" );

				} catch ( Exception $e ) {
					echo $e->getMessage ();
				}
			}
		}

	}


}