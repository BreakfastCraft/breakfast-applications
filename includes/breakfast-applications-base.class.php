<?php

class Breakfast_Applications_Base {
	public $app_table;
	public $answer_table;
	public $question_table;
	public $version = '0.0.2';

	function __construct() {
		global $wpdb;

		$this->app_table      = $wpdb->prefix . "breakfast_apps_applications";
		$this->answer_table   = $wpdb->prefix . "breakfast_apps_answers";
		$this->question_table = $wpdb->prefix . "breakfast_apps_questions";

	}
} 