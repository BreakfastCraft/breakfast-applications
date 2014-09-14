<?php
/**
 * Plugin Name: Breakfastcraft Applictions
 * Version: 0.0.3
 * Author: Bryan Garcia

 */

require_once( __DIR__ . "/includes/breakfast-applications-list-table.php" );
require_once plugin_dir_path( __FILE__ ) . 'includes/breakfast-applications-base.class.php';

if ( ! class_exists( 'SourceQuery' ) ) {
	require_once( __DIR__ . "/SourceQuery/SourceQuery.class.php" );
}

class Breakfast_Applications_Plugin extends Breakfast_Applications_Base {

	function __construct() {
		parent::__construct();


		add_shortcode( 'breakfast-application', array( $this, 'form_shortcode' ) );

		add_filter( 'if_menu_conditions', array( $this, 'if_menu_conditions' ) );

	}

	//[breakfast-application]
	function form_shortcode( $atts ) {
		global $wpdb;
		$a = shortcode_atts( array(
			'title' => 'Apply to BreakfastCraft Servers'
		), $atts );

		$questions   = $wpdb->get_results( "SELECT * FROM " . $this->question_table . " WHERE STATUS='active'", ARRAY_A );
		$application = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE user_id=" . get_current_user_id(), ARRAY_A );

		if ( $application == null ) {
			$application = array( 'age' => '', 'minecraft_name' => '' );
			$answers     = array();
		} else {
			$answers = $wpdb->get_results( "SELECT * FROM " . $this->answer_table . " WHERE application_id={$application['id']}", ARRAY_A );
		}

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'] ) ) {
			if ( isset( $application['id'] ) ) {
				$wpdb->update(
					$this->app_table,
					array(
						'age'            => (int) $_POST['age'],
						'status'         => 'pending',
						'minecraft_name' => $_POST['minecraft_name'],
						'applied_on'     => current_time( 'mysql', 1 )
					),
					array( 'id' => $application['id'] ),
					array( '%d', '%s', '%s', '%s' ),
					array( '%d' )
				);
				foreach ( $questions as $question ) {

					$answer = sub_array_find( $question['id'], $answers, 'id' );

					if ( $answer ) {
						$wpdb->update(
							$this->answer_table,
							array( 'answer' => $_POST[ 'question-' . $question['id'] ] ),
							array( 'id' => $answer['id'] )
						);
					} else {
						$wpdb->insert(
							$this->answer_table,
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
					$this->app_table,
					array(
						'user_id'        => get_current_user_id(),
						'age'            => (int) $_POST['age'],
						'minecraft_name' => $_POST['minecraft_name'],
						'applied_on'     => current_time( 'mysql', 1 )
					),
					array( '%d', '%s', '%s', '%s' )
				);
				$application = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE id=" . $wpdb->insert_id, ARRAY_A );

				foreach ( $questions as $question ) {

					$wpdb->insert(
						$this->answer_table,
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

	function if_menu_conditions() {
		$conditions[] = array(
			'name'      => 'If logged in and application not approved',
			'condition' => function ( $item ) {
				global $wpdb;
				if ( ! is_user_logged_in() ) {
					return false;
				}
				$application = $wpdb->get_row( "SELECT * FROM " . $this->app_table . " WHERE user_id=" . get_current_user_id(), ARRAY_A );
				if ( $application == null || $application['status'] != 'approved' ) {
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

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . "includes/breakfast-applications-admin.class.php";
	new Breakfast_Applications_Admin();
}
