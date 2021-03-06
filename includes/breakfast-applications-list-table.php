<?php
if ( ! class_exists( 'WP_LIST_TABLE' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Breakfast_Applications_List_Table extends WP_List_Table {
	var $app_table;
	var $answer_table;
	var $tab;

	function __construct( $app_table, $answer_table, $tab ) {
		$this->app_table    = $app_table;
		$this->answer_table = $answer_table;
		$this->tab = $tab;

		parent::__construct( array(
			'singular' => 'application',
			'plural'   => 'applications',
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}


	function column_user_name( $item ) {
		$user    = get_user_by( 'id', $item['user_id'] );
		$actions = array(
			'edit' => sprintf( '<a href="?page=applications_view&id=%s">%s</a>', $item['id'],
				__( 'View', 'custom_table_example' ) )
		);

		return sprintf( '%s %s',
			$user->data->display_name,
			$this->row_actions( $actions )
		);
	}

	function column_status( $item ) {
		return $item['status'];
	}

	function get_columns() {
		$columns = array(
			'user_name'      => __( 'Username', 'breakfast-applications' ),
			'age'            => __( 'Age', 'breakfast-applications' ),
			'minecraft_name' => __( 'Minecraft Name', 'breakfast-applications' ),
			'status'         => __( 'Application Status', 'breakfast-applications' ),
			'applied_on'     => __( 'Applied', 'breakfast-applications' )
		);

		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'applied_on' => array('applied_on', false),
			'username'  => array('username', false),
			'age'   => array('age', false),
			'minecraft_name' => array('minecraft_name', false)
		);
		return $sortable_columns;
	}

	function prepare_items() {
		global $wpdb;

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$total_items = $wpdb->get_var($wpdb->prepare("SELECT count(id) FROM $this->app_table WHERE status=%s", $this->tab ));

		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ) ? $_REQUEST['orderby'] : 'applied_on';
		$order   = ( isset( $_REQUEST['order'] ) && in_array( $_REQUEST['order'], array(
				'asc',
				'desc'
			) ) ) ? $_REQUEST['order'] : 'desc';

		$this->items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->app_table WHERE status=%s ORDER BY $orderby $order
			LIMIT %d OFFSET %d", $this->tab, $per_page, $paged*$per_page ), ARRAY_A );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}
}