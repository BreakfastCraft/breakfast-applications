<?php
/**
 * Plugin Name: Minecraft Applictions
 * Version: 0.1
 * Author: Bryan Garcia

 */
require_once( __DIR__ . "/breakfast-applications-list-table.php" );

if ( !class_exists( 'SourceQuery' ) )
	require_once( __DIR__ . "/SourceQuery/SourceQuery.class.php" );

class Breakfast_Applications_Plugin
{
	var $servers_option = 'breakfast_applications_servers';
	var $app_status = array( 'Pending', 'Approved', 'Denied' );

	function __construct()
	{
		global $wpdb;

		$this->app_table = $wpdb->prefix . "breakfast_apps_applications";
		$this->answer_table = $wpdb->prefix . "breakfast_apps_answers";
		$this->question_table = $wpdb->prefix . "breakfast_apps_questions";


		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		register_activation_hook( __FILE__, array( $this, 'install_db' ) );
		add_action( 'admin_footer', array( $this, 'applications_javascript' ) );
		add_shortcode( 'breakfast-application', array( $this, 'form_shortcode' ) );
	}

	function create_admin_menu()
	{
		add_menu_page( __( 'Applications', 'breakfast-applications' ), __( 'Applications', 'breakfast-applications' ),
			'promote_users', 'applications', array( $this, 'admin_page_handler' ), 'dashicons-admin-users' );

		add_submenu_page( 'applications', __( 'Questions', 'breakfast-applications' ), __( 'Questions', 'breakfast-applications' ),
			'promote_users', 'applications_questions', array( $this, 'questions_handler' ) );

		add_submenu_page( null, __( 'View Application', 'breakfast-applications' ), __( 'View Application', 'breakfast-applications' ),
			'promote_users', 'applications_view', array( $this, 'view_application_handler' ) );

		add_submenu_page( 'applications', __( 'Servers', 'breakfast-applications' ), __( 'Servers', 'breakfast-applications' ),
			'promote_users', 'applications_servers', array( $this, 'servers_handler' ) );

	}

	function install_db()
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$answer_sql = "CREATE TABLE $this->answer_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			application_id BIGINT(20) NOT NULL,
			question_id BIGINT(20) NOT NULL,
			answer TEXT NULL,
			PRIMARY KEY  (id)
			);";

		$application_sql = "CREATE TABLE $this->app_table  (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			age TINYINT NOT NULL,
			minecraft_name VARCHAR(25) NOT NULL,
			status TINYINT NOT NULL DEFAULT 0,
			PRIMARY KEY  (id)
			);";
		$question_sql = "CREATE TABLE $this->question_table (
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

	function admin_page_handler()
	{
		global $wpdb;
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
			<h2><?php _e( 'Applications', 'breakfast-application' ) ?></h2>

			<?php
			$table = new Breakfast_Applications_List_Table( $this->app_table, $this->answer_table );
			$table->prepare_items();
			$table->display();
			?>
		</div>
	<?php
	}

	function servers_handler()
	{

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] ) ) {
			$servers = array();
			if ( isset( $_POST['servers'] ) ) {
				foreach ( $_POST['servers'] as $server ) {
					if ( !empty( $server['host'] ) && !empty( $server['port'] ) && !empty( $server['pass'] ) ) {
						$servers[] = $server;
					}
				}
			}
			update_option( $this->servers_option, $servers );
		}

		if ( !isset( $servers ) ) {
			$servers = get_option( $this->servers_option );
		}
		add_meta_box( 'servers_meta_box', 'Servers', array( $this, 'servers_meta_box' ), 'servers_meta_box', 'normal', 'default' );
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

	function servers_meta_box( $servers )
	{
		?>
		<div id="breakfast-servers">
			<?php for ( $i = 0; $i < count( $servers ); $i++ ): ?>
				<div class="breakfast-server" id="breakfast-<?php echo $i; ?>">
					<label for="host-<?php echo $i; ?>">Host:</label>
					<input type="text" name="servers[<?php echo $i; ?>][host]" id="host-<?php echo $i; ?>"
						   value="<?php echo isset( $_POST['servers'][$i] ) ? $_POST['servers'][$i]['host'] : $servers[$i]['host']; ?>"/>

					<label for="port-<?php echo $i; ?>">Port:</label>
					<input type="text" name="servers[<?php echo $i; ?>][port]" id="port-<?php echo $i; ?>"
						   value="<?php echo isset( $_POST['servers'][$i] ) ? $_POST['servers'][$i]['port'] : $servers[$i]['port']; ?>"/>

					<label type="text" for="pass-<?php echo $i; ?>">Password:</label>
					<input type="text" name="servers[<?php echo $i; ?>][pass]" id="pass-<?php echo $i; ?>"
						   value="<?php echo isset( $_POST['servers'][$i] ) ? $_POST['servers'][$i]['pass'] : $servers[$i]['pass']; ?>"/>

					<label for="host-<?php echo $i; ?>">Master:</label>
					<input type="hidden" name="servers[<?php echo $i; ?>][master]" value="0"/>
					<input type="checkbox" name="servers[<?php echo $i; ?>][master]" id="host-<?php echo $i; ?>"
						   value="1"
						<?php if ( ( isset( $_POST['servers'][$i] ) && $_POST['servers'][$i]['master'] ) || ( !isset( $_POST['servers'][$i] ) && $servers[$i]['master'] ) ): ?>
							checked
						<?php endif; ?>>

					<button class="button remove">Remove</button>
					<hr/>
				</div>
			<?php endfor; ?>
		</div>
		<button id="breakfast-add-server" class="button">Add Server</button>
		<input type="submit" name="submit" class="button" value="Save Servers"/>
	<?php
	}

	function questions_handler()
	{
		global $wpdb;
		$message = '';
		$notice = '';

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], basename( __FILE__ ) ) ) {
			$active_ids = array();
			foreach ( $_POST['questions'] as $question ) {
				$question['question'] = trim( $question['question'] );

				if ( is_numeric( $question['id'] ) ) {
					//update existing question
					$wpdb->update(
						$this->question_table,
						array( 'question' => $question['question'], 'format' => $question['format'], 'status' => 'active' ),
						array( 'id' => (int)$question['id'] ),
						'%s',
						'%d' );
					$active_ids[] = $question['id'];
				} else {
					//insert new question
					if ( !empty( $question['question'] ) ) {
						$wpdb->insert(
							$this->question_table,
							array( 'question' => $question['question'], 'format' => $question['format'], 'status' => 'active' )
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
				$wpdb->query( "UPDATE $this->question_table SET status='active' WHERE " . join( ' OR ', $actives ) );
			}
			//add/update questions
		}

		$questions = $wpdb->get_results( "SELECT * FROM $this->question_table WHERE status='active'", ARRAY_A );
		add_meta_box( 'questions_meta_box', 'Questions', array( $this, 'questions_meta_box' ), 'questions', 'normal', 'default' );

		?>
		<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
		<h2><?php _e( 'Application Questions', 'breakfast-application' ) ?></h2>
		<?php if ( !empty( $notice ) ): ?>
		<div id="notice" class="error"><p><?php echo $notice ?></p></div>
	<?php endif; ?>
		<?php if ( !empty( $message ) ): ?>
		<div id="message" class="updated"><p><?php echo $message ?></p></div>
	<?php endif; ?>
		<form method="POST">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
			<?php do_meta_boxes( 'questions', 'normal', $questions ); ?>
		</form>
	<?php
	}

	function questions_meta_box( $questions )
	{
		?>
		<div id="breakfast-questions">
			<?php foreach ( $questions as $question ): ?>
				<div class="breakfast-question" id="breakfast-<?php echo $question['id']; ?>">
					<input type="hidden" value="<?php echo $question['id']; ?>"
						   name="questions[<?php echo $question['id']; ?>][id]"/>
					<textarea cols="70" rows="5" class="input"
							  name="questions[<?php echo $question['id']; ?>][question]"><?php echo $question['question']; ?></textarea><br/>
					<select name="questions[<?php echo $question['id']; ?>][format]">
						<option value="text" <?php echo ( $question['format'] == 'text' ) ? 'selected' : ''; ?>>Text
						</option>
						<option
							value="textarea" <?php echo ( $question['format'] == 'textarea' ) ? 'selected' : ''; ?>>
							Textarea
						</option>

					</select><br/>
					<button class="button remove">Remove</button>
					<hr/>
				</div>
			<?php endforeach; ?>
		</div>
		<button id="breakfast-add-question" class="button">Add Question</button>
		<input type="submit" name="submit" class="button" value="Save Questions"/>
	<?php
	}

	function applications_javascript()
	{
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

	function view_application_handler()
	{
		global $wpdb;
		$message = '';
		$notice = '';
		$application = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->app_table WHERE id = %d", $_REQUEST['id'] ), ARRAY_A );
		if ( empty( $application ) ) {
			wp_redirect( '?page=applications&message=' . urlencode( 'Unable to find application #' . $_REQUEST['id'] . '.' ) );
			exit( 0 );

		} else {
			$application = $application[0];
		}
		$user = get_user_by( 'id', $application['user_id'] );

		$answers = $wpdb->get_results( $wpdb->prepare( "
				SELECT $this->answer_table.*, $this->question_table.question
				FROM $this->answer_table, $this->question_table
				WHERE
					application_id=%d
					AND
					$this->answer_table.question_id = $this->question_table.id
				ORDER BY id ASC", $application['id'] ), ARRAY_A );

		$ban_info = json_decode( file_get_contents( 'http://api.fishbans.com/bans/' . $application['minecraft_name'] ), true );
		$bans = 0;
		if ( $ban_info['success'] ) {
			foreach ( $ban_info['bans']['service'] as $service ) {
				$bans += $service['bans'];
			}
		}
		if ( !empty( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], basename( __FILE__ ) ) ) {
			if ( $_REQUEST['op'] == 'approve' )
				$message = $this->approve( $_REQUEST['id'] );
			elseif ( $_REQUEST['op'] == 'deny' )
				$message = $this->deny( $_REQUEST['id'] );
			else
				$message = 'Invalid operation.';
		}
		?>
		<div class="wrap">
			<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
			<h2><?php _e( 'Applications', 'breakfast-application' ) ?></h2>

			<?php if ( !empty( $notice ) ): ?>
				<div id="notice" class="error"><p><?php echo $notice ?></p></div>
			<?php endif; ?>
			<?php if ( !empty( $message ) ): ?>
				<div id="message" class="updated"><p><?php echo $message ?></p></div>
			<?php endif; ?>

			<form method="POST">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
				<input type="hidden" name="id" value="<?php echo $application['id'] ?>"/>
				<dl>
					<dt>Username:</dt>
					<dd><?php echo $user->data->user_login; ?></dd>
					<dt>Display Name:</dt>
					<dd><?php echo $user->data->display_name; ?></dd>
					<dt>Email:</dt>
					<dd><?php echo $user->data->user_email; ?></dd>
					<dt>Age:</dt>
					<dd><?php echo $application['age']; ?></dd>
					<dt>Minecraft Name:</dt>
					<dd><?php echo $application['minecraft_name']; ?></dd>

					<?php foreach ( $answers as $answer ): ?>
						<dt><?php echo $answer['question']; ?></dt>
						<dd><?php echo $answer['answer']; ?></dd>
					<?php endforeach; ?>
				</dl>

				<h2>Fishbans Info</h2>

				<div>
					<?php if ( $ban_info['success'] ): ?>
						<dl>
							<dt>Total Bans: <?php echo $bans; ?></dt>
							<?php foreach ( $ban_info['bans']['service'] as $service => $values ): ?>
								<dt><?php echo $service; ?>: <?php echo $values['bans']; ?></dt>
								<?php foreach ( $values['ban_info'] as $server => $info ): ?>
									<dd><?php echo $server; ?> - <?php echo $info; ?></dd>
								<?php endforeach; ?>
							<?php endforeach; ?>
						</dl>
					<?php else: ?>
						<?php echo $ban_info['error']; ?>
					<?php endif; ?>
				</div>
				<div style="margin-top: 15px;">
					<button name="op" value="approve" id="op_approve" class="button">Approve</button>
					<button name="op" value="deny" id="op_deny" class="button">Deny</button>
				</div>
			</form>
		</div>
	<?php

	}

	//[breakfast-application]
	function form_shortcode( $atts )
	{
		global $wpdb;
		$a = shortcode_atts( array(
			'title' => 'Apply to BreakfastCraft Servers'
		), $atts );

		$questions = $wpdb->get_results( "SELECT * FROM $this->question_table WHERE status='active'", ARRAY_A );
		$application = $wpdb->get_row( "SELECT * FROM $this->app_table WHERE user_id=" . get_current_user_id(), ARRAY_A );

		if ( $application == null ) {
			$application = array( 'age' => '', 'minecraft_name' => '' );
			$answers = array();
		} else {
			$answers = $wpdb->get_results( "SELECT * FROM $this->answer_table WHERE application_id={$application['id']}", ARRAY_A );
		}

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ) ) {
			if ( $application != null ) {
				$wpdb->update(
					$this->app_table,
					array( 'age' => (int)$_POST['age'], 'minecraft_name' => $_POST['minecraft_name'] ),
					array( 'id' => $application['id'] ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				foreach ( $questions as $question ) {

					$answer = sub_array_find( $question['id'], $answers, 'id' );

					if ( $answer ) {
						$wpdb->update(
							$this->answer_table,
							array( 'answer' => $_POST['question-' . $question['id']] ),
							array( 'id' => $answer['id'] )
						);
					} else {
						$wpdb->insert(
							$this->answer_table,
							array(
								'application_id' => $application['id'],
								'question_id' => $question['id'],
								'answer' => $_POST['question-' . $question['id']]
							)
						);
					}
				}


			} else {
				$wpdb->insert(
					$this->app_table,
					array( 'age' => (int)$_POST['age'], 'minecraft_name' => $_POST['minecraft_name'] ),
					array( '%d', '%s' )
				);

				foreach ( $questions as $question ) {

					$wpdb->insert(
						$this->answer_table,
						array(
							'application_id' => $application['id'],
							'question_id' => $question['id'],
							'answer' => $_POST['question-' . $question['id']]
						)
					);


				}

			}
			$message = "<strong>Thank you your applying.</strong> We will review your application and get back to you as quickly as we can.";
		}


		ob_start()
		?>
		<?php if ( !empty( $message ) ): ?>
		<div class="alert alert-success">
			<?php echo $message; ?>
		</div>
	<?php endif; ?>
		<div class="well">
			<form method="POST" class="form-vertical">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(); ?>"/>
				<fieldset>
					<div class="form-group">
						<label for="minecraft_name">Minecraft Username</label>

						<input type="text" class="form-control" id="minecraft_name"
							   value="<?php echo ( isset( $_POST['minecraft_name'] ) ) ? $_POST['minecraft_name'] : $application['minecraft_name']; ?>"
							   name="minecraft_name"/>

					</div>
					<div class="form-group">
						<label for="age">Age</label>


						<input type="text" class="form-control" id="age" name="age"
							   value="<?php echo ( isset( $_POST['age'] ) ) ? $_POST['age'] : $application['age']; ?>"/>

					</div>
					<?php foreach ( $questions as $question ): ?>

						<?php if ( $application == null ) {
							$answer = '';
						} else {
							$answer = $wpdb->get_var( "SELECT answer FROM $this->answer_table WHERE question_id={$question['id']} AND application_id={$application['id']}" );
						} ?>

						<div class="form-group">
							<label
								for="question-<?php echo $question['id']; ?>"><?php echo $question['question']; ?></label>
							<?php if ( $question['format'] == 'textarea' ): ?>
								<textarea class="form-control" id="question-<?php echo $question['id']; ?>"
										  name="question-<?php echo $question['id']; ?>"><?php echo ( isset( $_POST['question-' . $question['id']] ) ) ? $_POST['question-' . $question['id']] : $answer; ?></textarea>
							<?php else: ?>
								<input type="text" class="form-control" id="question-<?php echo $question['id']; ?>"
									   name="question-<?php echo $question['id']; ?>"
									   value="<?php echo ( isset( $_POST['question-' . $question['id']] ) ) ? $_POST['question-' . $question['id']] : $answer; ?>"/>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<div class="form-group">
						<input type="submit" value="Submit" name="submit" class="btn btn-primary"/>
					</div>
				</fieldset>
			</form>
		</div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

	function approve( $app_id )
	{
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM $this->app_table WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$this->whitelist( $app['minecraft_name'] );
		$wpdb->update( $this->app_table, array( 'status' => 1 ), array( 'id' => $app['id'] ), '%d', '%d' );
		return 'Application approved.';

	}

	function deny( $app_id )
	{
		global $wpdb;

		$app = $wpdb->get_row( "SELECT * FROM $this->app_table WHERE id=$app_id", ARRAY_A );
		if ( $app == null ) {
			return "Could not find application #$app_id";
		}
		$wpdb->update( $this->app_table, array( 'status' => 2 ), array( 'id' => $app['id'] ), '%d', '%d' );
		return 'Application denied.';
	}

	function whitelist( $name )
	{
		$servers = get_option( $this->servers_option );
		foreach ( $servers as $server ) {
			$query = new SourceQuery();
			try {
				$query->Connect( $server['host'], $server['port'], 1, SourceQuery::SOURCE );
				$query->SetRconPassword( $server['pass'] );
				if ( $server['master'] ) {
					$query->Rcon( "whitelist add $name" );
				}
				$query->Rcon( "whitelist reload" );

			} catch ( Exception $e ) {
				echo $e->getMessage();
			}
		}
	}

}


function sub_array_find( $needle, $haystack, $property )
{
	foreach ( $haystack as $hay ) {
		if ( $hay[$property] == $needle )
			return $hay;
	}
	return null;
}


new Breakfast_Applications_Plugin();

