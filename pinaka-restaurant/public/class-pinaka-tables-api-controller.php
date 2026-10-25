<?php
/**
 * REST API Tables API controller
 *
 * Handles requests to the Tables API endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Tables Controller class.
 */
class Pinaka_Tables_Api_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-restaurant-pos/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'tables';

	/**
	 * Register the routes for Tables.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-table',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_tables_method' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-table',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_tables_method' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-table',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_tables_method' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-tables',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_tables' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-tables-by-time',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_tables_by_time' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-tables-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tables_by_id' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-tables-by-zone-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tables_by_zone_id' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-tables-by-restaurant-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_tables_by_restaurant_id' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-merge-tables',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_tables_with_merge' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-merge-tables-with-table-status',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'create_merge_tables_with_table_status'),
				'permission_callback' => array($this, 'check_user_role_permission'), 
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-merge-tables-with-table-status',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'update_merge_tables_with_table_status'),
				'permission_callback' => array($this, 'check_user_role_permission'), 
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-merge-tables-with-table-status',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'delete_merge_tables_with_table_status'),
				'permission_callback' => array($this, 'check_user_role_permission'), 
			)
		);
		
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-merge-tables-with-reservation',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'get_all_merge_tables_with_reservation'),
				'permission_callback' => array($this, 'check_user_role_permission'), 
			)
		);
		
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/table-status-counts',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'get_table_status_counts'],
				'permission_callback' => [$this, 'check_user_role_permission'],
			]
		);
	}
	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function check_user_role_permission( $request ) {

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'pinakapos_rest_cannot_view',
				esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		// Get all registered roles
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );

		// Allow if user has any of the registered roles
		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $all_roles, true ) ) {
				return true;
			}
		}

		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
	
	public function get_table_status_counts($request)
	{
		$response = $this->get_all_tables($request);

		if (is_wp_error($response)) {
			return $response;
		}

		$data = $response->get_data();
 
		if (empty($data['table_details'])) {
			return new WP_REST_Response([
				'success' => true,
				'available_tables' => 0,
				'reserved_tables'  => 0,
				'dinein_tables'    => 0,
				'ready_to_pay_tables' => 0,
				'total_tables'     => 0,
			], 200);
		}

		$available = 0;
		$reserved  = 0;
		$dinein    = 0;
		$ready_to_pay = 0;

		foreach ($data['table_details'] as $table) {

			switch ($table['status']) {

				case 'Available':
					$available++;
					break;

				case 'Reserve':
					$reserved++;
					break;

				case 'Dine in':
			    case 'Occupied':
					$dinein++;
					break;

				case 'Ready to Pay':
					$ready_to_pay++;
					break;
			}
		}

		return new WP_REST_Response([
			'success'            => true,
			'available_tables'   => $available,
			'reserved_tables'    => $reserved,
			'dinein_tables'      => $dinein,
			'ready_to_pay_tables'=> $ready_to_pay,
			'total_tables'       => count($data['table_details']),
		], 200);
	}
	public function create_tables_method($request) 
	{
		$params = $request->get_json_params();
		$required_fields = [
			'table_name',
			'table_capacity',
			'table_shape',
			'zone_id',
			'table_pos_x',
			'table_pos_y',
			'table_rotation'
		];

		$user_id = get_current_user_id();

		// Handle restaurant ID
		if (!isset($params['restaurant_id'])) {
			$restaurant_id = get_user_meta($user_id, 'user_restaurant_id', true);
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}

		// Validate required fields
		foreach ($required_fields as $field) {
			if (!isset($params[$field])) {
				return new WP_REST_Response([
					'success' => false,
					'message' => "Missing required parameter: $field"
				], 400);
			}
			if($field!=='table_rotation')
			{
				if (empty($params[$field])) {
					return new WP_REST_Response([
						'success' => false,
						'message' => "Field '$field' cannot be empty."
					], 400);
				}
			}
		}
		// Specific numeric validation
		if (
			!is_numeric($params['table_capacity']) || $params['table_capacity'] <= 0 ||
			!is_numeric($params['table_pos_x']) || $params['table_pos_x'] <= 0 ||
			!is_numeric($params['table_pos_y']) || $params['table_pos_y'] <= 0
		) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Please enter valid numeric values greater than 0 for table_capacity, table_pos_x, and table_pos_y.'
			], 400);
		}
		if(isset($params['table_rotation']))
		{
			if (!in_array($params['table_rotation'], [0.0, 90.0], true)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Please select valid table rotation'
				], 400);
			}
		}
		if (empty($params['zone_id'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		if (empty($restaurant_id)) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant id is required.'
			], 422);
		}
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		$table_name = sanitize_text_field($params['table_name']);
		$zone_id = intval($params['zone_id']);
		$zone = get_post($zone_id);

		if (!$zone || $zone->post_type !== 'zones' || $zone->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished zone ID.',
			], 404);
		}

		$existing = new WP_Query([
			'post_type'      => 'tables',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => '_table_name',
					'value'   => $table_name,
					'compare' => '='
				],
				[
					'key'     => '_table_zone_id',
					'value'   => $zone_id,
					'compare' => '='
				],
				[
					'key'     => '_restaurant_id',
					'value'   => intval($restaurant_id),
					'compare' => '='
				]
			]
		]);

		if ($existing->have_posts()) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'This table already exists in the same zone for the restaurant.'
			], 409);
		}

		$post_id = wp_insert_post([
			'post_title'  => $table_name,
			'post_type'   => 'tables',
			'post_status' => 'publish',
			'post_author' => $user_id
		]);

		if (is_wp_error($post_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to create table.'
			], 500);
		}
		
		update_post_meta($post_id, '_restaurant_id', intval($restaurant_id));
		update_post_meta($post_id, '_table_name', $table_name);
		update_post_meta($post_id, '_table_capacity', intval($params['table_capacity']));
		update_post_meta($post_id, '_table_shape', sanitize_text_field($params['table_shape']));
		update_post_meta($post_id, '_table_zone_id', $zone_id);
		update_post_meta($post_id, '_table_pos_x', floatval($params['table_pos_x']));
		update_post_meta($post_id, '_table_pos_y', floatval($params['table_pos_y']));
		update_post_meta($post_id, '_table_rotation', isset($params['table_rotation']) ? floatval($params['table_rotation']) : 0);

		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Table successfully created.',
			'table_id'      => $post_id,
			'zone_id'       => $zone_id,
			'restaurant_id' => intval($restaurant_id)
		], 200);
	}

	public function update_tables_method($request) 
	{
		$params  = $request->get_json_params();
		$user_id = get_current_user_id();

		// Handle restaurant ID
		if (!isset($params['restaurant_id'])) {
			$restaurant_id = get_user_meta($user_id, 'user_restaurant_id', true);
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}
		if (!isset($params['table_id'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table id is required.'
			], 422);
		}
		// Ensure table_id is provided
		if (empty($params['table_id']) || !is_numeric($params['table_id']) || $params['table_id'] <= 0) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table id is mandatory and must be a valid number.'
			], 400);
		}

		$table_id = intval($params['table_id']);
		
		// Verify table post exists
		$post = get_post($table_id);
		if (!$post || $post->post_type !== 'tables' || $post->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished Table ID.',
			], 404);
		}

		// Validate fields are not empty strings
		$fields_to_validate = [
			'table_name',
			'table_capacity',
			'table_shape',
			'zone_id',
			'table_pos_x',
			'table_pos_y',
			// 'table_rotation',
		];

		foreach ($fields_to_validate as $field) 
		{
			if (isset($params[$field]) && empty($params[$field])) 
			{
				return new WP_REST_Response([
					'success' => false,
					'message' => "Parameter '$field' cannot be empty."
				], 400);
			}
		}
		if(isset($params['table_rotation']))
		{
			if (!in_array($params['table_rotation'], [0.0, 90.0], true)) 
			{
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Please select valid table rotation'
				], 400);
			}
		}
		// Numeric validation
		if (
			(isset($params['table_capacity']) && (!is_numeric($params['table_capacity']) || $params['table_capacity'] <= 0)) ||
			(isset($params['table_pos_x']) && (!is_numeric($params['table_pos_x']) || $params['table_pos_x'] <= 0)) ||
			(isset($params['table_pos_y']) && (!is_numeric($params['table_pos_y']) || $params['table_pos_y'] <= 0))
		) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'table_capacity, table_pos_x, and table_pos_y must be numeric values greater than 0.'
			], 400);
		}
		if (!isset($params['zone_id'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		if (empty($params['zone_id'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		if (empty($restaurant_id)) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant id is required.'
			], 422);
		}
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		// Validate zone
		$zone_id = intval($params['zone_id']);
		$zone = get_post($zone_id);
		if (!$zone || $zone->post_type !== 'zones' || $zone->post_status !== 'publish') 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished zone ID.',
			], 404);
		}

		// Check for duplicate table name in same zone excluding current table
		if (isset($params['table_name']) && !empty($params['table_name'])) 
		{
			$existing = new WP_Query([
				'post_type'      => 'tables',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'post__not_in'   => [$table_id],
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => '_table_name',
						'value'   => sanitize_text_field($params['table_name']),
						'compare' => '='
					],
					[
						'key'     => '_table_zone_id',
						'value'   => $zone_id,
						'compare' => '='
					],
					[
						'key'     => '_restaurant_id',
						'value'   => intval($restaurant_id),
						'compare' => '='
					]
				]
			]);

			if ($existing->have_posts()) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Table with this name already exists in this zone for this restaurant.'
				], 409);
			}
		}

		// Update post title if name provided
		if (isset($params['table_name']) && !empty($params['table_name'])) {
			$updated = wp_update_post([
				'ID'         => $table_id,
				'post_title' => sanitize_text_field($params['table_name']),
				'post_author' => $user_id
			], true);

			if (is_wp_error($updated)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Failed to update table.'
				], 500);
			}
		}

		// Update meta
		update_post_meta($table_id, '_restaurant_id', intval($restaurant_id));
		update_post_meta($table_id, '_table_zone_id', $zone_id);

		$fields_to_update = [
			'table_name',
			'table_capacity',
			'table_shape',
			'table_pos_x',
			'table_pos_y',
			'table_rotation'
		];

		$field_handlers = [
			'table_name'       => 'sanitize_text_field',
			'table_shape'      => 'sanitize_text_field',
			'table_capacity'   => 'intval',
			'table_pos_x'      => 'floatval',
			'table_pos_y'      => 'floatval',
			'table_rotation'   => 'floatval'
		];
	
		foreach ($fields_to_update as $field) 
		{
			if (isset($params[$field])) {
				$sanitize = $field_handlers[$field];
				update_post_meta($table_id, "_$field", $sanitize($params[$field]));
			}
		}

		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Table successfully updated.',
			'table_id'      => $table_id,
			'zone_id'       => $zone_id,
			'restaurant_id' => intval($restaurant_id)
		], 200);
	}

	public function delete_tables_method($request)
	{
		$params = $request->get_json_params();
		$user_id = get_current_user_id();
		if(!isset($params['table_id']))
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'table_id is required.'
			], 400);
		}
		if(!isset($params['zone_id']))
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'zone_id required.'
			], 400);
		}
		// Validate required fields
		if (
			empty($params['table_id']) || !is_numeric($params['table_id']) ||
			empty($params['zone_id']) || !is_numeric($params['zone_id']) ||
			(!isset($params['restaurant_id']) && !get_user_meta($user_id, 'user_restaurant_id', true))
		) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Valid table_id, zone_id, and restaurant_id are required.'
			], 400);
		}

		$table_id     = intval($params['table_id']);
		$zone_id      = intval($params['zone_id']);
		$restaurant_id = isset($params['restaurant_id']) ? sanitize_text_field($params['restaurant_id']) : get_user_meta($user_id, 'user_restaurant_id', true);
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		// Fetch the table post
		$post = get_post($table_id);
		if (!$post || $post->post_type !== 'tables' || $post->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished Table ID.',
			], 404);
		}

		// Fetch meta values and validate match
		$table_restaurant_id = intval(get_post_meta($post->ID, '_restaurant_id', true));
		$table_zone_id       = intval(get_post_meta($post->ID, '_table_zone_id', true));

		if ($table_restaurant_id !== intval($restaurant_id) || $table_zone_id !== $zone_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table does not belong to the specified restaurant and zone.'
			], 403);
		}

		// Delete the table
		$deleted = wp_trash_post($table_id);
		if (!$deleted) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to delete table.'
			], 500);
		}

		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Table successfully deleted.',
			'table_id'      => $table_id,
			'zone_id'       => $zone_id,
			'restaurant_id' => intval($restaurant_id)
		], 200);
	}

	public function get_all_tables($request){
    
        $params = $request->get_params();
        $user_id = get_current_user_id();

        // Get restaurant ID
        $restaurant_id = isset($params['restaurant_id']) 
            ? sanitize_text_field($params['restaurant_id']) 
            : get_user_meta($user_id, 'user_restaurant_id', true);

        if (empty($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant not assigned to login user.',
            ], 422);
        }

        $restaurants = get_option('pinaka_pos_restaurants', []);
        $found = false;

        foreach ($restaurants as $restaurant) {
            if ($restaurant['id'] == $restaurant_id) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant Not Exists.',
            ], 409);
        }

        $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
        if ($user_restaurant_id !== intval($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User Not assigned to Restaurant',
            ], 409);
        }

        // Fetch all tables of restaurant
        $existing = get_posts([
            'post_type'      => 'tables',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ]
        ]);

        date_default_timezone_set('Asia/Kolkata');
        $current_datetime   = date('Y-m-d H:i:s');
        $current_timestamp  = strtotime($current_datetime);
        
        $current_datetime = current_datetime();
        $current_timestamp = $current_datetime->getTimestamp();
        
        // Fetch today's reservations (ignore slot_type completely)
        $reservation_data1 = [];
        $reservations1 = get_posts([
            'post_type'      => 'reservation_list',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_table_status',
                    'value'   => ['Reserve','Dine in','Ready to Pay'],
                    'compare' => 'IN'
                ],
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ],
                [
                    'key'     => '_reservation_date',
                    'value'   => date('Y-m-d'),
                    'compare' => '='
                ],
            ]
        ]);
		foreach ( $reservations1 as $reservation ) {

            $reservation_id = $reservation->ID;
        
            $orders = wc_get_orders( [
                'limit'      => -1,
                'meta_key'   => '_reservation_id',
                'meta_value' => $reservation_id,
                'return'     => 'objects',
            ] );
        
            foreach ( $orders as $order ) {
        
                // Skip child orders
                if ( $order->get_parent_id() != 0 ) {
                    continue;
                }
        
                if ( $order->get_status() === 'cancelled' ) {
                    update_post_meta( $reservation_id, '_reservation_status', 'Cancelled' );
                    update_post_meta( $reservation_id, '_table_status', 'Available' );
                }
            }
        }
        foreach ($reservations1 as $reservation) {
            $reservation_id     = $reservation->ID;
            
            $table_id           = intval(get_post_meta($reservation_id, '_table_no_id', true));
            $reservation_time   = get_post_meta($reservation_id, '_reservation_time', true);
            $reservation_date   = get_post_meta($reservation_id, '_reservation_date', true);
            $zones_id           = get_post_meta($reservation_id, '_zone_id', true);
            $rest_id            = get_post_meta($reservation_id, '_restaurant_id', true);
            $reservation_status = get_post_meta($reservation_id, '_reservation_status', true);
			
            $customer_phone     = get_post_meta($reservation_id, '_customer_phone', true);
			$table_status       = get_post_meta($reservation_id, '_table_status', true);
            if ($table_id > 0) {
            $reservation_timestamp = strtotime($reservation_date . ' ' . $reservation_time);
            $two_hours_before = $reservation_timestamp - (2 * 60 * 60);
			$two_hours_after  = $reservation_timestamp + (2 * 60 * 60);
            $buffer_end       = $reservation_timestamp + (30 * 60); // 30 mins after start
            $cutoff_time      = date('h:i A', $reservation_timestamp - 600);  // 10 mins before
            $pre_buffer_time  = date('h:i A', $reservation_timestamp - 1800); // 30 mins before
            $buffer_time      = date('h:i A', $reservation_timestamp) . ' - ' . date('h:i A', $buffer_end);
            
            $dine_in_start_time = get_post_meta($reservation_id, '_dine_in_start_time', true);

            if (
                $reservation_status === 'Seated' &&
                !empty($dine_in_start_time)
            ) {
                $expire_time = strtotime($dine_in_start_time . ' +3 hours');
            
                if ($current_timestamp >= $expire_time) {
            
                    update_post_meta($reservation_id, '_table_status', 'Available');
                    update_post_meta($reservation_id, '_reservation_status', 'Completed');
                    delete_post_meta($reservation_id, '_dine_in_start_time');
            
                    $orders = wc_get_orders([
                        'limit'      => 1,
                        'meta_key'   => '_reservation_id',
                        'meta_value' => $reservation_id,
                        'return'     => 'objects',
                    ]);
            
                    if (!empty($orders)) {
                        $parent_order = $orders[0];
                        $parent_order->update_meta_data('_table_status', 'Available');
                        $parent_order->save();
                    }
            
                    $table_status = 'Available';
                    $reservation_status = 'Completed';
                }
            }
			
            // ✅ Only mark Reserve between 2 hrs before start and 30 mins after start
			
            if ($current_timestamp >= $two_hours_before && $current_timestamp <= $two_hours_after || in_array($table_status,['Dine in', 'Ready to Pay'])) {
		
                $key = $rest_id . '-' . $zones_id . '-' . $table_id;

                // Default Reserve
                $status = 'Reserve';

                // Override if cancelled/expired/dine in
                if (in_array($reservation_status, ['Cancelled', 'Expired' ,'Completed'], true)) {
                    $status = "Available";
                } elseif ($reservation_status === 'Seated') {
                    $status = "Dine in";
                }
				elseif ($reservation_status === 'Ready to Pay') {
                    $status = "Ready to Pay";
                }
				elseif ($reservation_status === ''){
					$status = "Reserve";
				}

                $reservation_data1[$key] = [
                    'reservation_id'     => $reservation_id,
                    'table_status'       => $status,
                    'dine_in_time'       => $dine_in_start_time,
                    'reservation_date'   => $reservation_date,
                    'reservation_time'   => $reservation_time,
                    'table_id'           => $table_id,
                    'reservation_status' => $reservation_status,
                    'reservation_mobile' => $customer_phone,
                    'cutoff_time'        => $cutoff_time,
                    'pre_buffer_time'    => $pre_buffer_time,
                    'buffer_time'        => $buffer_time,
                ];
                }
            }
        }
		
        // Merge with tables
        $zones = [];
        foreach ($existing as $zone) {
            $table_id = $zone->ID;
            $resta_id = get_post_meta($table_id, '_restaurant_id', true);
            $zone_id  = get_post_meta($table_id, '_table_zone_id', true);
            $key      = $resta_id . '-' . $zone_id . '-' . $table_id;

            // Default status
            $status = isset($reservation_data1[$key]['table_status']) 
                ? $reservation_data1[$key]['table_status'] 
                : 'Available';

            if (isset($reservation_data1[$key])) {
            $res_status = $reservation_data1[$key]['reservation_status'];

            if (in_array($res_status, ['Cancelled', 'Expired','Completed'], true)) {
                $status = "Available";
            } elseif ($res_status === 'Seated') {
                $status = "Dine in";
            } 
			elseif ($res_status === 'Ready to Pay') {
				$status = "Ready to Pay";
			} 
			elseif ($res_status === '') {
                $status = "Reserve";
            }
        }
        
		$active_orders = wc_get_orders([
			'status'      => ['processing', 'pending'], // active statuses
			'limit'       => 1, // latest only
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_query'  => [
				'relation' => 'AND',
				[
					'key'   => '_table_id',
					'value' => $table_id,
				],
				[
					'key'   => '_zone_id',
					'value' => $zone_id,
				],
				[
					'key'   => '_restaurant_id',
					'value' => $resta_id,
				],
				[
					'key'   => '_parent_order',
					'value' => 'yes',
				],
				[
					'key'   => '_table_status',
					'value'   => ['Occupied', 'Dine in'],
					'compare' => 'IN',
				],
			],
		]);
		$order_amount=0;
		if (!empty($active_orders) && !isset($reservation_data1[$key])) {
			$order = $active_orders[0]; // WC_Order object
			$is_all_kot_done = $order->get_meta('_all_done', true);
			$is_generated_bill = $order->get_meta( 'is_generated_bill', true );
			// Get dine in start time from parent order
            $dine_in_time = $order->get_meta('_dine_in_start_time', true);
			if ($is_all_kot_done == "yes" || $is_generated_bill == 1) {
                $status = "Ready to Pay";
            } elseif ($order->get_meta('_table_status', true) === 'Occupied') {
                $status = "Occupied";
            } else {
                $status = "Dine in";
            }
            $order_amount = $order->get_total();
		}
        if ( in_array( $status, [ 'Available','Occupied' ], true ) ) {
            $order_amount = 0;
        }
        $zone_data = [
            'table_id'      => $table_id,
            'table_name'    => $zone->post_title,
            'restaurant_id' => intval($resta_id),
            'capacity'      => get_post_meta($table_id, '_table_capacity', true),
            'shape'         => get_post_meta($table_id, '_table_shape', true),
            'zone_id'       => intval($zone_id),
            'pos_x'         => get_post_meta($table_id, '_table_pos_x', true),
            'pos_y'         => get_post_meta($table_id, '_table_pos_y', true),
            'rotation'      => get_post_meta($table_id, '_table_rotation', true),
            'dine_in_time'  => $dine_in_time,
            'status'        => $status,
            'order_amount'  => $order_amount
			//'merged_tables' => $table_name
        ];

        //Add merge information
        // Add merge information
		$is_merged       = get_post_meta($table_id, '_is_merged', true);
		$child_table_ids = get_post_meta($table_id, '_child_table_ids', true);
		$parent_table_id = get_post_meta($table_id, '_parent_table_id', true);

		if ($is_merged === 'yes') {

			if (!empty($child_table_ids)) {
				// This is a parent table
				$zone_data['is_merged']       = true;
				$zone_data['merge_role']      = 'parent';
				$child_table_ids_array         = explode(',', $child_table_ids);
				$zone_data['child_table_ids'] = $child_table_ids_array;

				// Compute total capacity (parent + children)
				$parent_capacity       = intval(get_post_meta($table_id, '_table_capacity', true));
				$total_child_capacity  = 0;
				$child_names           = [];
				foreach ($child_table_ids_array as $cid) {
					$total_child_capacity += intval(get_post_meta($cid, '_table_capacity', true));
					$child_names[] = get_the_title($cid);
				}
				$zone_data['capacity'] = $parent_capacity + $total_child_capacity;

				// Parent table status
				// $parent_status = get_post_meta($table_id, '_table_status', true);
				// if (empty($parent_status)) {
				// 	$parent_status = 'Available';
				// }
				// $zone_data['status'] = $parent_status;

				// Build merged tables string
				$parent_name = get_the_title($table_id);
				$zone_data['merged_tables'] = $parent_name . '-' . implode('-', $child_names);

			} else {
				// This is a child table
				$zone_data['is_merged']       = true;
				$zone_data['merge_role']      = 'child';
				$zone_data['parent_table_id'] = intval($parent_table_id);

				// Capacity is always 0 for child
				$zone_data['capacity'] = 0;

				// Child table status
				$child_status = get_post_meta($table_id, '_table_status', true);
				if (empty($child_status)) {
					$child_status = 'Available';
				}
				$zone_data['status'] = $child_status;

				// Build merged tables string
				$parent_name = get_the_title($parent_table_id);
				$child_name  = get_the_title($table_id);
				$zone_data['merged_tables'] = $parent_name . '-' . $child_name;
			}

		} else {
			// Not merged
			$zone_data['is_merged']     = false;
			$zone_data['merged_tables'] = $zone->post_title;
		}


        //Reservation details (if Reserved / Dine in)
        if (
            isset($reservation_data1[$key]) &&
            in_array($status, ["Reserve", "Dine in"], true)
        ) {
            $zone_data['reservation_id']     = $reservation_data1[$key]['reservation_id'];
            $zone_data['reservation_mobile'] = $reservation_data1[$key]['reservation_mobile'];
            $zone_data['reservation_date']   = $reservation_data1[$key]['reservation_date'];
            $zone_data['reservation_time']   = $reservation_data1[$key]['reservation_time'];
            $zone_data['dine_in_time']       = $reservation_data1[$key]['dine_in_time'];
            $zone_data['cutoff_time']        = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' -10 minutes'));
            $zone_data['pre_buffer_time']    = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' -30 minutes'));
            $zone_data['buffer_time']        = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' +30 minutes'));
        }

        $zones[] = $zone_data;
    }


        return new WP_REST_Response([
            'success'       => true,
            'message'       => 'All tables fetched successfully.',
            'table_details' => $zones,
        ], 200);
    }

  public function get_all_tables_by_time($request)
	{
		$params = $request->get_params();
		$reservation_time = isset($params['reservation_time']) ? sanitize_text_field($params['reservation_time']) : '';
		$reservation_date = $params['reservation_date'] ?: date('Y-m-d');
		$user_id = get_current_user_id();
		// Get restaurant ID
        $restaurant_id = isset($params['restaurant_id']) 
            ? sanitize_text_field($params['restaurant_id']) 
            : get_user_meta($user_id, 'user_restaurant_id', true);

        if (empty($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant not assigned to login user.'
            ], 422);
        }
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		$existing = get_posts([
			'post_type'      => 'tables',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_restaurant_id',
					'value'   => $restaurant_id,
					'compare' => '='
				]
			]
		]);

		date_default_timezone_set('Asia/Kolkata');
        $current_datetime   = date('Y-m-d H:i:s');
        $current_timestamp  = strtotime($current_datetime);
        
        $current_datetime = current_datetime();
        $current_timestamp = $current_datetime->getTimestamp();
        
		$reservation_data1 = [];
		$reservations1 = get_posts([
			'post_type'      => 'reservation_list',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => '_table_status',
					'value'   => ['Reserve', 'Dine in','Ready to Pay'],
					'compare' => 'IN'
				],
				[
					'key'     => '_restaurant_id',
					'value'   => $restaurant_id,
					'compare' => '='
				],
				[
					'key'     => '_reservation_date',
					'value'   => $reservation_date,
					'compare' => '='
				],
				
			]
		]);

        // $reservations1 = get_posts([
        //     'post_type'      => 'reservation_list',
        //     'post_status'    => 'publish',
        //     'posts_per_page' => -1,
        //     'meta_query'     => $meta_query
        // ]);
        foreach ( $reservations1 as $reservation ) {

            $reservation_id = $reservation->ID;
        
            $orders = wc_get_orders( [
                'limit'      => -1,
                'meta_key'   => '_reservation_id',
                'meta_value' => $reservation_id,
                'return'     => 'objects',
            ] );
        
            foreach ( $orders as $order ) {
        
                // Skip child orders
                if ( $order->get_parent_id() != 0 ) {
                    continue;
                }
        
                if ( $order->get_status() === 'cancelled' ) {
                    update_post_meta( $reservation_id, '_reservation_status', 'Cancelled' );
                    update_post_meta( $reservation_id, '_table_status', 'Available' );
                }
            }
        }
		foreach ($reservations1 as $reservation) {

            $reservation_id     = $reservation->ID;
			
            $table_id           = intval(get_post_meta($reservation_id, '_table_no_id', true));
            $reservation_time   = get_post_meta($reservation_id, '_reservation_time', true);
            $reservation_date   = get_post_meta($reservation_id, '_reservation_date', true);
            $zones_id           = get_post_meta($reservation_id, '_zone_id', true);
            $rest_id            = get_post_meta($reservation_id, '_restaurant_id', true);
            $reservation_status = get_post_meta($reservation_id, '_reservation_status', true);
            $customer_phone     = get_post_meta($reservation_id, '_customer_phone', true);
			$table_status       = get_post_meta($reservation_id, '_table_status', true);
			
			

            if ($table_id > 0) {
                $reservation_timestamp = strtotime($reservation_date . ' ' . $reservation_time);
		        $two_hours_before = $reservation_timestamp - (2 * 60 * 60);
				$two_hours_after = $reservation_timestamp + (2 * 60 * 60);
	            $buffer_end       = $reservation_timestamp + (30 * 60);

                // Check against user-supplied slot time (instead of NOW)
                if (!empty($params['reservation_time'])) {
                    $slot_timestamp = strtotime($reservation_date . ' ' . $params['reservation_time']);
                } else {
                    $slot_timestamp = $current_timestamp; // fallback
                }
				
                if ($slot_timestamp >= $two_hours_before && $slot_timestamp <= $two_hours_after || $table_status == 'Dine in') {
                    $key = $rest_id . '-' . $zones_id . '-' . $table_id;
                    $status = 'Reserve';
              
					if (in_array($reservation_status, ['Cancelled', 'Expired'], true)) {
                        $status = "Available";
                    } elseif ($reservation_status === 'Seated') {
                        $status = "Dine in";
                    }
					elseif ($reservation_status === 'Ready to Pay') {
                        $status = "Ready to Pay";
                    }
					else if ($reservation_status === '') {
						$status = "Reserve";
					}
					
                    $reservation_data1[$key] = [
                        'reservation_id'     => $reservation_id,
                        'table_status'       => $status,
                        'reservation_date'   => $reservation_date,
                        'reservation_time'   => $reservation_time,
                        'table_id'           => $table_id,
                        'reservation_status' => $reservation_status,
                        'reservation_mobile' => $customer_phone,
                        'cutoff_time'        => date('h:i A', $reservation_timestamp - 600),
                        'pre_buffer_time'    => date('h:i A', $reservation_timestamp - 1800),
                        'buffer_time'        => date('h:i A', $reservation_timestamp) . ' - ' . date('h:i A', $buffer_end),
                    ];
                }
            }
        }
		//echo json_encode($reservation_data1);die;
        // Merge with tables
        $zones = [];
        foreach ($existing as $zone) {

            $table_id = $zone->ID;
            $resta_id = get_post_meta($table_id, '_restaurant_id', true);
            $zone_id  = get_post_meta($table_id, '_table_zone_id', true);
            $key      = $resta_id . '-' . $zone_id . '-' . $table_id;

            // Default status
            $status = isset($reservation_data1[$key]['table_status']) 
                ? $reservation_data1[$key]['table_status'] 
                : 'Available';
			//echo $status;
            if (isset($reservation_data1[$key])) {
                $res_status = $reservation_data1[$key]['reservation_status'];

                if (in_array($res_status, ['Cancelled', 'Expired','Completed'], true)) {
                    $status = "Available";
                } elseif ($res_status === 'Seated') {
                    $status = "Dine in";
                }
				elseif ($res_status === 'Ready to Pay') {
                    $status = "Ready to Pay";
                }
				else if ($res_status === '') {
					$status = "Reserve";
				}
            }
			$active_orders = wc_get_orders([
				'status'      => ['processing', 'pending'],
				'limit'       => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'meta_query'  => [
					'relation' => 'AND',
					[
						'key'   => '_table_id',
						'value' => $table_id,
					],
					[
						'key'   => '_zone_id',
						'value' => $zone_id,
					],
					[
						'key'   => '_restaurant_id',
						'value' => $resta_id,
					],
					[
						'key'   => '_parent_order',
						'value' => 'yes',
					],
					[
    					'key'   => '_table_status',
    					'value'   => ['Occupied', 'Dine in'],
    					'compare' => 'IN',
    				],
				],
			]);

			if (!empty($active_orders) && !isset($reservation_data1[$key])) {

                $order = $active_orders[0];
            
                $dine_in_start_time = $order->get_meta('_dine_in_start_time', true);
            
                if (!empty($dine_in_start_time) &&
                    $current_timestamp >= strtotime($dine_in_start_time . ' +3 hours')) {
            
                    // Make the table available
                    $order->update_meta_data('_table_status', 'Available');
                    $order->save();
            
                    $status = "Available";
            
                } else {
            
                    $is_all_kot_done = $order->get_meta('_all_done', true);
            
                    if ($is_all_kot_done == "yes") {
                        $status = "Ready to Pay";
                    } elseif ($order->get_meta('_table_status', true) === 'Occupied') {
                        $status = "Occupied";
                    } else {
                        $status = "Dine in";
                    }
                }
            }
			// if(in_array($status, ["Ready to Pay", "Dine in"])) {
			// 	continue;
			// }
			if (in_array($status, ["Dine in", "Occupied"])) {
                continue;
            }
			// merged info
            $is_merged        = get_post_meta($table_id, '_is_merged', true);
            $parent_table_id  = get_post_meta($table_id, '_parent_table_id', true);

            // skip merged tables (both parent and child)
            if ($is_merged === 'yes') {
                continue;
            }
			//echo $status;
            $zone_data = [
                'table_id'      => $table_id,
                'table_name'    => $zone->post_title,
                'restaurant_id' => intval($resta_id),
                'capacity'      => get_post_meta($table_id, '_table_capacity', true),
                'shape'         => get_post_meta($table_id, '_table_shape', true),
                'zone_id'       => intval($zone_id),
                'pos_x'         => get_post_meta($table_id, '_table_pos_x', true),
                'pos_y'         => get_post_meta($table_id, '_table_pos_y', true),
                'rotation'      => get_post_meta($table_id, '_table_rotation', true),
                'status'        => $status
            ];

			//Add merge information
            $is_merged        = get_post_meta($table_id, '_is_merged', true);
            $parent_table_id  = get_post_meta($table_id, '_parent_table_id', true);
            $child_table_ids  = get_post_meta($table_id, '_child_table_ids', true);

		    if ($is_merged === 'yes') {
                if ($parent_table_id == $table_id) {
                    // Parent table
                    $zone_data['is_merged']       = true;
                    $zone_data['merge_role']      = 'parent';
                    $zone_data['child_table_ids'] = !empty($child_table_ids) ? explode(',', $child_table_ids) : [];

				    // Build merged tables string
                    $parent_name = get_the_title($parent_table_id);
                    $child_names = [];
					$total_child_capacity = 0;

                    if (!empty($zone_data['child_table_ids'])) {
                        foreach ($zone_data['child_table_ids'] as $cid) {
                            $child_names[] = get_the_title($cid);
							$total_child_capacity += intval(get_post_meta($cid, '_table_capacity', true));
                        }
                    }
         
					//Update parent capacity = parent + children
                    $parent_capacity = intval(get_post_meta($parent_table_id, '_table_capacity', true));
                    $zone_data['capacity'] = $parent_capacity + $total_child_capacity;

                    //Parent status comes from DB (or Available)
                    $parent_status = get_post_meta($parent_table_id, '_table_status', true);
                    if ($parent_status === '' || $parent_status === null) {
                        $parent_status = 'Available';
                    }
                
				    $zone_data['status'] = $parent_status;
				    $zone_data['merged_tables'] = $parent_name . '-' . implode('-', $child_names);
                } else {
                    // Child table
                    $zone_data['is_merged']       = true;
                    $zone_data['merge_role']      = 'child';
                    $zone_data['parent_table_id'] = intval($parent_table_id);
					//Capacity always 0 for child
                    $zone_data['capacity'] = 0;

		            //Status same as parent
                    $parent_status = get_post_meta($parent_table_id, '_table_status', true);
                    if ($parent_status === '' || $parent_status === null) {
                        $parent_status = 'Available';
                    }
                
				    $zone_data['status'] = $parent_status;
                    $parent_name = get_the_title($parent_table_id);
                    $child_name  = get_the_title($table_id);
                    $zone_data['merged_tables'] = $parent_name . '-' . $child_name;
                }

            } else {
                $zone_data['is_merged'] = false;
			    $zone_data['merged_tables']   = $zone->post_title;
            }

            if (
                isset($reservation_data1[$key]) &&
                in_array($status, ["Reserve", "Dine in"], true)
            ) {
                $zone_data['reservation_id']     = $reservation_data1[$key]['reservation_id'];
                $zone_data['reservation_mobile'] = $reservation_data1[$key]['reservation_mobile'];
                $zone_data['reservation_date']   = $reservation_data1[$key]['reservation_date'];
                $zone_data['reservation_time']   = $reservation_data1[$key]['reservation_time'];
                $zone_data['cutoff_time']        = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' -10 minutes'));
                $zone_data['pre_buffer_time']    = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' -30 minutes'));
				$zone_data['buffer_time']        = date('h:i A', strtotime($reservation_data1[$key]['reservation_time'] . ' +30 minutes'));
			}
            $zones[] = $zone_data;
        }

		return new WP_REST_Response([
			'success'      => true,
			'message'      => 'All tables fetched successfully.',
			'table_details' => $zones,
		], 200);
	}

	public function get_tables_by_id($request)
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		if (!isset($params['restaurant_id'])) {
			$restaurant_id = get_user_meta($user_id, 'user_restaurant_id', true);
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}
		if (empty($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant not assigned to login user.'
			], 422);
		}
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		$params = $request->get_params();
		if (!isset($params['table_id'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table id is required.'
			], 422);
		}
		$table_id = intval($params['table_id']);

		if (empty($table_id)) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table id is required.'
			], 422);
		}

		$tables_data = get_post($table_id);

		if (!$tables_data || $tables_data->post_type !== 'tables' || $tables_data->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished table ID.',
			], 404);
		}
	
		$table_restaurant_id = get_post_meta($tables_data->ID, '_restaurant_id', true);
		if ($restaurant_id != $table_restaurant_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Table does not belong to your restaurant.'
			], 403);
		}

		$tables[] = [
			'table_id' => $tables_data->ID,
			'table_name'     => $tables_data->post_title,
			"restaurant_id" => get_post_meta($tables_data->ID, '_restaurant_id', true),
			'capacity'   => get_post_meta($tables_data->ID, '_table_capacity', true),
			'shape' => get_post_meta($tables_data->ID, '_table_shape', true),
			'zone_id' => get_post_meta($tables_data->ID, '_table_zone_id', true),
			'pos_x' => get_post_meta($tables_data->ID, '_table_pos_x', true),
			'pos_y' => get_post_meta($tables_data->ID, '_table_pos_y', true),
			'rotation' => get_post_meta($tables_data->ID, '_table_rotation', true),
		];
		return new WP_REST_Response([
			'success'      => true,
			'message'      => 'Table details fetched successfully.',
			'table_details' => $tables,
		], 200);
	}
	public function get_tables_by_zone_id($request)
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		if (!isset($params['restaurant_id'])) {
			$restaurant_id = get_user_meta($user_id, 'user_restaurant_id', true);
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}
		if (empty($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant id is required.'
			], 422);
		}
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		$params = $request->get_params();
		if (!isset($params['zone_id'])) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone ID is required.'
			], 422);
		}
		$zone_id = $params['zone_id'];
		if (empty($zone_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone ID is required.'
			], 422);
		}

		$zone_post = get_post($zone_id);
		if (!$zone_post || $zone_post->post_type !== 'zones' || $zone_post->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid or unpublished zone ID.',
			], 404);
		}
	
		$zone_restaurant_id = get_post_meta($zone_post->ID, '_restaurant_id', true);
		if ($restaurant_id != $zone_restaurant_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone does not belong to your restaurant.'
			], 403);
		}
	
		$query = new WP_Query([
			'post_type'      => 'tables',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_table_zone_id',
					'value'   => $zone_id,
					'compare' => '='
				]
			]
		]);

		$tables = [];

if ($query->have_posts()) {

    foreach ($query->posts as $post) {

        $table_id = $post->ID;
        $zone_id  = get_post_meta($table_id, '_table_zone_id', true);
        $rest_id  = get_post_meta($table_id, '_restaurant_id', true);

        // Check if table has an active parent order
        $active_orders = wc_get_orders([
            'status'     => ['processing', 'pending'],
            'limit'      => 1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'   => '_restaurant_id',
                    'value' => $rest_id
                ],
                [
                    'key'   => '_zone_id',
                    'value' => $zone_id
                ],
                [
                    'key'   => '_table_id',
                    'value' => $table_id
                ],
                [
                    'key'   => '_table_status',
                    'value' => 'Dine in'
                ],
                [
                    'key'   => '_parent_order',
                    'value' => 'yes'
                ]
            ]
        ]);

        // Determine table status
        $is_occupied = !empty($active_orders);

        $tables[] = [
            'table_id'       => $table_id,
            'table_name'     => $post->post_title,
            'restaurant_id' => $rest_id,
            'capacity'      => get_post_meta($table_id, '_table_capacity', true),
            'table_shape'   => get_post_meta($table_id, '_table_shape', true),
            'zone_id'       => $zone_id,
			'zone_name'    => get_post( $zone_id ) ? get_post( $zone_id )->post_title : '',
            'pos_x'         => get_post_meta($table_id, '_table_pos_x', true),
            'pos_y'         => get_post_meta($table_id, '_table_pos_y', true),
            'rotation'      => get_post_meta($table_id, '_table_rotation', true),
            'status'        => $is_occupied ? 'Dine In' : 'Available'
        ];
    }
}


		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Tables fetched successfully by zone.',
			'table_details' => $tables,
		], 200);
	}
	public function get_tables_by_restaurant_id($request)
	{
		$params = $request->get_params();
		// Handle restaurant ID
		$user_id = get_current_user_id();
		if (!isset($params['restaurant_id'])) {
			$restaurant_id = get_user_meta($user_id, 'user_restaurant_id', true);
		} else {
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}

		if (empty($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant ID is required.'
			], 422);
		}
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				
				$found = true;
				break;
			}
		}

		if (!$found) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant Not Exists.',
			], 409); // Conflict
		}
		$user_restaurant_id=intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($user_restaurant_id !== intval($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User Not assigned to Restaurant',
			], 409); // Conflict
		}
		$tables = [];

		// Step 3: Get all tables in this zone
		$table_query = new WP_Query([
			'post_type'      => 'tables',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_restaurant_id',
					'value'   => $restaurant_id,
					'compare' => '='
				]
			]
		]);
		if ($table_query->have_posts()) 
		{
			foreach ($table_query->posts as $table_post) {
				$tables[] = [
					'table_id'         => $table_post->ID,
					'table_name'       => $table_post->post_title,
					"restaurant_id"    => get_post_meta($table_post->ID, '_restaurant_id', true),
					'capacity'   => get_post_meta($table_post->ID, '_table_capacity', true),
					'shape'      => get_post_meta($table_post->ID, '_table_shape', true),
					'zone_id'    => get_post_meta($table_post->ID, '_table_zone_id', true),
					'pos_x'      => get_post_meta($table_post->ID, '_table_pos_x', true),
					'pos_y'      => get_post_meta($table_post->ID, '_table_pos_y', true),
					'rotation'   => get_post_meta($table_post->ID, '_table_rotation', true),
				];
			}
		}
		
		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Tables fetched successfully for the restaurant.',
			'table_details' => $tables,
		], 200);
	}




	public function get_all_tables_with_merge(WP_REST_Request $request) {
        $params        = $request->get_params();
	    $user_id       = get_current_user_id();
        //$restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : 0;

        //Get restaurant ID
	    $restaurant_id = isset($params['restaurant_id'])
	        ? sanitize_text_field($params['restaurant_id'])
		    : get_user_meta($user_id, 'user_restaurant_id', true);
	
	    if(empty($restaurant_id)){
		    return new WP_REST_Response([
			    'success'  => false,
			    'massage'  => 'Restaurant not assigned to login user.',
		    ], 422);
	    }

	    $restaurants = get_option('pinaka_pos_restaurants', []);
	    $found = false;

	    foreach($restaurants as $restaurant){
		    if($restaurant['id'] == $restaurant_id){
			    $found = true;
			    break;
		    }
	    }

	    if(!$found){
		    return new WP_REST_Response([
			    'success'  => false,
			    'message'  => 'Restaurant Not Exists.',
		    ], 409);
	    }

	    $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
	    if($user_restaurant_id !== intval($restaurant_id)){
		    return new WP_REST_Response([
			    'success' => false,
			    'message' => 'User Not assigned to Restaurant',
		    ], 409);
	    }

        // Get tables from get_all_tables API (response object → convert to array)
        $all_tables_response = $this->get_all_tables($request);
        $all_tables_data     = $all_tables_response->get_data();  
        $all_tables          = isset($all_tables_data['table_details']) ? $all_tables_data['table_details'] : [];

        $parent_tables = [];
        $child_tables  = [];

        foreach ($all_tables as $table) {
            $status     = $table['status'];
			//$is_merged  = isset($table['is_merged']) ? $table['is_merged'] : false;

			// if($is_merged){
			// 	continue;
			// }

            // Skip reserved tables
            if ($status === 'Reserve') {
                continue;
            }

            // Parent tables: Available, Seated, Ready to Pay
            if (in_array($status, ['Available', 'Dine in', 'Ready to Pay'])) {
                $parent_tables[] = $table;
            }

            // Child tables: Available only
            if ($status === 'Available') {
                $child_tables[] = $table;
            }
        }

        return new WP_REST_Response([
            'success'       => true,
            'message'       => 'All merge tables fetched successfully.',
            'parent_tables' => $parent_tables,
            'child_tables'  => $child_tables,
        ], 200);
    }

	public function create_merge_tables_with_table_status(WP_REST_Request $request){
        $params    = $request->get_params();
        $user_id   = get_current_user_id();

        // Get restaurant ID
        $restaurant_id = isset($params['restaurant_id'])
            ? sanitize_text_field($params['restaurant_id'])
            : get_user_meta($user_id, 'user_restaurant_id', true);

        if (empty($restaurant_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant not assigned to login user.',
            ], 422);
        }

        $restaurants = get_option('pinaka_pos_restaurants', []);
        $found = false;
        foreach($restaurants as $restaurant){
            if($restaurant['id'] == $restaurant_id){
                $found = true;
                break;
            }
        }

        if(!$found){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Restaurant Not Exists.',
            ], 409);
        }

        $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
        if($user_restaurant_id !== intval($restaurant_id)){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'User not assigned to Restaurant',
            ], 409);
        }

        $zone_name        = isset($params['zone_name']) ? sanitize_text_field($params['zone_name']) : '';
        $parent_table_id  = isset($params['parent_table_id']) ? intval($params['parent_table_id']) : 0;
        $child_table_ids  = isset($params['child_table_ids']) ? (array)$params['child_table_ids'] : [];

        if(empty($restaurant_id) || empty($zone_name) || empty($parent_table_id) || empty($child_table_ids)){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Zone name, Parent table id and Child table ids are required.',
            ], 422);
        }

        // Use get_all_tables() instead of DB check
        $all_tables_response = $this->get_all_tables($request);
        $all_tables_data     = $all_tables_response->get_data();
        $table_details       = isset($all_tables_data['table_details']) ? $all_tables_data['table_details'] : [];

        // Find parent table in computed tables
        $parent_table = null;
        foreach ($table_details as $t) {
            if ($t['table_id'] == $parent_table_id) {
                $parent_table = $t;
                break;
            }
        }
        if(!$parent_table){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Parent table not available.',
            ], 409);
        }

        // Validate child tables
        $child_tables = [];
        foreach ($child_table_ids as $cid) {
            $found = null;
            foreach ($table_details as $t) {
                if ($t['table_id'] == $cid) {
                    $found = $t;
                    break;
                }
            }
            if(!$found){
                return new WP_REST_Response([
                    'success' => false,
                    'message' => "Child table ID {$cid} not available."
                ], 409);
            }
            $child_tables[] = $found;
        }

        // ✅ Correct: parent should not have its own _parent_table_id
        update_post_meta($parent_table_id, '_is_merged', 'yes');
        delete_post_meta($parent_table_id, '_parent_table_id');
        update_post_meta($parent_table_id, '_child_table_ids', implode(',', $child_table_ids));


        // Update child meta
        foreach ($child_table_ids as $cid) {
            update_post_meta($cid, '_is_merged', 'yes');
            update_post_meta($cid, '_parent_table_id', $parent_table_id);
        }

        //Use dynamic status from get_all_tables()
        $parent_status = $parent_table['status'];
	    update_post_meta($parent_table_id, '_table_status', $parent_status);

        // Compute capacity
        $parent_capacity = intval(get_post_meta($parent_table_id, '_table_capacity', true));
        $total_child_capacity = 0;
        foreach ($child_tables as $t) {
            $total_child_capacity += intval(get_post_meta($t['table_id'], '_table_capacity', true));
        }
        $total_capacity = $parent_capacity + $total_child_capacity;

	    //Persist child tables status in DB
        foreach ($child_table_ids as $cid) {
            update_post_meta($cid, '_is_merged', 'yes');
            update_post_meta($cid, '_parent_table_id', $parent_table_id);
        }

        // Parent response
        $parent_table_response = [
            'table_id'   => $parent_table_id,
            'table_name' => $parent_table['table_name'],
            'capacity'   => $total_capacity,
            'status'     => $parent_status
        ];

        // Child response (preserve original status)
        $child_tables_response = [];
        foreach ($child_tables as $t) {
            update_post_meta($t['table_id'], '_table_status', 'Available');
            $child_tables_response[] = [
                'table_id'   => $t['table_id'],
                'table_name' => $t['table_name'],
                'capacity'   => 0,
                'status'     => $child_status ?: 'Available' // optional fallback
            ];
        }


        return new WP_REST_Response([
            'success' => true,
            'message' => 'Tables merged successfully.',
            'merged_tables' => [
                'restaurant_id' => $restaurant_id,
                'zone_name'     => $zone_name,
                'parent_table'  => $parent_table_response,
                'child_tables'  => $child_tables_response
            ]
        ], 200);
    }

	public function update_merge_tables_with_table_status(WP_REST_Request $request){
        $params    = $request->get_params();
        $user_id   = get_current_user_id();

        // Get restaurant ID
        $restaurant_id = isset($params['restaurant_id'])
            ? sanitize_text_field($params['restaurant_id'])
            : get_user_meta($user_id, 'user_restaurant_id', true);

        if(empty($restaurant_id)){
            return new WP_REST_Response([
                'success'  => false,
                'message'  => 'Restaurant not assigned to login user.',
            ], 422);
        }

        $parent_table_id  = isset($params['parent_table_id']) ? intval($params['parent_table_id']) : 0;
        $child_table_ids  = isset($params['child_table_ids']) ? (array)$params['child_table_ids'] : [];
        $zone_name        = isset($params['zone_name']) ? sanitize_text_field($params['zone_name']) : '';

        if(empty($parent_table_id) || empty($child_table_ids)){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Parent table id and Child table ids are required.',
            ], 422);
        }

        // Check parent exists
        $parent_post = get_post($parent_table_id);
        if(!$parent_post || $parent_post->post_type !== 'tables'){
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid parent table id.',
            ], 404);
        }

		

        // ---- HANDLE CHILDREN PREVIOUS PARENTS ----
        foreach($child_table_ids as $cid){
            $old_parent = get_post_meta($cid, '_parent_table_id', true);
            if($old_parent && intval($old_parent) !== $parent_table_id){
                // Remove child from old parent
                $old_parent_children = explode(',', get_post_meta($old_parent, '_child_table_ids', true));
                $old_parent_children = array_filter($old_parent_children, function($id) use ($cid){
                    return intval($id) !== intval($cid);
                });

                if(!empty($old_parent_children)){
                    update_post_meta($old_parent, '_child_table_ids', implode(',', $old_parent_children));
                } else {
                    // Old parent has no more children → fully demote it
                    update_post_meta($old_parent, '_is_merged', 'no');
                    delete_post_meta($old_parent, '_child_table_ids');
                    delete_post_meta($old_parent, '_parent_table_id');
                }
            }
        }

        // ---- RESET OLD CHILDREN OF NEW PARENT ----
        $old_child_ids = explode(',', get_post_meta($parent_table_id, '_child_table_ids', true));
        foreach($old_child_ids as $cid){
            if(!empty($cid)){
                update_post_meta($cid, '_is_merged', 'no');
                delete_post_meta($cid, '_parent_table_id');
            }
        }

        // ---- UPDATE NEW PARENT ----
        update_post_meta($parent_table_id, '_is_merged', 'yes');
        update_post_meta($parent_table_id, '_parent_table_id', $parent_table_id);
        update_post_meta($parent_table_id, '_child_table_ids', implode(',', $child_table_ids));

        // ---- UPDATE CHILDREN ----
        foreach ($child_table_ids as $cid) {
            update_post_meta($cid, '_is_merged', 'yes');
            update_post_meta($cid, '_parent_table_id', $parent_table_id);
        }

        // ---- CAPACITY & STATUS CALCULATION ----
        $parent_capacity = intval(get_post_meta($parent_table_id, '_table_capacity', true));
        $total_child_capacity = 0;

        $child_tables_response = [];
        foreach ($child_table_ids as $cid) {
            $child_name = get_the_title($cid);
            $child_cap  = intval(get_post_meta($cid, '_table_capacity', true));
            $total_child_capacity += $child_cap;
            $child_status = get_post_meta($cid, '_table_status', true) ?: 'Available'; //preserve child status

            $child_tables_response[] = [
                'table_id'   => $cid,
                'table_name' => $child_name,
                'capacity'   => 0,
                'status'     => $child_status
            ];
        }

        $total_capacity = $parent_capacity + $total_child_capacity;
        $parent_status  = get_post_meta($parent_table_id, '_table_status', true) ?: 'Available';

        $parent_table_response = [
            'table_id'   => $parent_table_id,
            'table_name' => get_the_title($parent_table_id),
            'capacity'   => $total_capacity,
            'status'     => $parent_status
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Merged tables updated successfully.',
            'merged_tables' => [
                'restaurant_id' => $restaurant_id,
                'zone_name'     => $zone_name,
                'parent_table'  => $parent_table_response,
                'child_tables'  => $child_tables_response
            ]
        ], 200);
    }

	public function delete_merge_tables_with_table_status(WP_REST_Request $request)
{
    $params  = $request->get_json_params();
    $user_id = get_current_user_id();

    // Required params check
    if (!isset($params['parent_table_id'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'parent_table_id is required.'
        ], 400);
    }
    if (!isset($params['zone_id'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'zone_id required.'
        ], 400);
    }

    // Validate required fields
    if (
        empty($params['parent_table_id']) || !is_numeric($params['parent_table_id']) ||
        empty($params['zone_id']) || !is_numeric($params['zone_id']) ||
        (!isset($params['restaurant_id']) && !get_user_meta($user_id, 'user_restaurant_id', true))
    ) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Valid parent_table_id, zone_id, and restaurant_id are required.'
        ], 400);
    }
	

    $parent_table_id = intval($params['parent_table_id']);
    $zone_id         = intval($params['zone_id']);
    $restaurant_id   = isset($params['restaurant_id'])
        ? sanitize_text_field($params['restaurant_id'])
        : get_user_meta($user_id, 'user_restaurant_id', true);

    // Validate restaurant exists
    $restaurants = get_option('pinaka_pos_restaurants', []);
    $found = false;
    foreach ($restaurants as $restaurant) {
        if ($restaurant['id'] == $restaurant_id) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Restaurant Not Exists.',
        ], 409);
    }

    // Validate user assigned to restaurant
    $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
    if ($user_restaurant_id !== intval($restaurant_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'User Not assigned to Restaurant',
        ], 409);
    }

    // Validate parent table exists
    $post = get_post($parent_table_id);
    if (!$post || $post->post_type !== 'tables' || $post->post_status !== 'publish') {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid or unpublished Parent Table ID.',
        ], 404);
    }

	

    // Check merge meta
    $is_merged = get_post_meta($parent_table_id, '_is_merged', true);
    $child_ids = get_post_meta($parent_table_id, '_child_table_ids', true);

    if ($is_merged !== 'yes' || empty($child_ids)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'This table is not a parent of any merged tables.'
        ], 409);
    }

    // Reset parent table
    update_post_meta($parent_table_id, '_is_merged', 'no');
    delete_post_meta($parent_table_id, '_child_table_ids');
    delete_post_meta($parent_table_id, '_parent_table_id');

    // Reset child tables
    $child_ids = explode(',', $child_ids);
    foreach ($child_ids as $cid) {
        update_post_meta($cid, '_is_merged', 'no');
        delete_post_meta($cid, '_parent_table_id');
    }

    return new WP_REST_Response([
        'success'        => true,
        'message'        => 'Table Merge successfully deleted.',
        'parent_table_id'=> $parent_table_id,
        'zone_id'        => $zone_id,
        'restaurant_id'  => intval($restaurant_id)
    ], 200);
}
public function get_all_merge_tables_with_reservation(WP_REST_Request $request){
		$params        = $request->get_params();
	    $user_id       = get_current_user_id();
        //$restaurant_id = isset($params['restaurant_id']) ? intval($params['restaurant_id']) : 0;

        //Get restaurant ID
	    $restaurant_id = isset($params['restaurant_id'])
	        ? sanitize_text_field($params['restaurant_id'])
		    : get_user_meta($user_id, 'user_restaurant_id', true);
	
	    if(empty($restaurant_id)){
		    return new WP_REST_Response([
			    'success'  => false,
			    'message'  => 'Restaurant not assigned to login user.',
		    ], 422);
	    }

	    $restaurants = get_option('pinaka_pos_restaurants', []);
	    $found = false;

	    foreach($restaurants as $restaurant){
		    if($restaurant['id'] == $restaurant_id){
			    $found = true;
			    break;
		    }
	    }

	    if(!$found){
		    return new WP_REST_Response([
			    'success'  => false,
			    'message'  => 'Restaurant Not Exists.',
		    ], 409);
	    }

	    $user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
	    if($user_restaurant_id !== intval($restaurant_id)){
		    return new WP_REST_Response([
			    'success' => false,
			    'message' => 'User Not assigned to Restaurant',
		    ], 409);
	    }

        // Get tables from get_all_tables API (response object → convert to array)
        $all_tables_response = $this->get_all_tables($request);
        $all_tables_data     = $all_tables_response->get_data();  
        $all_tables          = isset($all_tables_data['table_details']) ? $all_tables_data['table_details'] : [];

        $parent_tables = [];
        $child_tables  = [];

        foreach ($all_tables as $table) {
            $status     = $table['status'];
			$is_merged  = isset($table['is_merged']) ? $table['is_merged'] : false;

			if($is_merged){
				continue;
			}

            // Skip reserved, dine in, ready to pay tables
            if (in_array($status, ['Reserve','Dine in','Ready to Pay'])) {
                continue;
            }

            // Parent tables: Available
           if ($status === 'Available') {
                $parent_tables[] = $table;
            }

            // Child tables: Available only
            if ($status === 'Available') {
                $child_tables[] = $table;
            }
        }

        return new WP_REST_Response([
            'success'       => true,
            'message'       => 'All merge tables fetched successfully.',
            'parent_tables' => $parent_tables,
            'child_tables'  => $child_tables,
        ], 200);
	}


}