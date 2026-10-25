<?php
/**
 * REST API Zones API controller
 *
 * Handles requests to the Zones API endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Zones Controller class.
 */
class Pinaka_Zones_Api_Controller {

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
	protected $rest_base = 'zones';

	/**
	 * Register the routes for Zones.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-zone',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_zone_callback' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-zone',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_zone_callback' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-zone',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_zone_callback' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-zones',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAllZones' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-all-kot-zones',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAllKotZones' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-zones-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getZonesById' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
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
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$all_roles = array_keys( $wp_roles->get_names() );
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
	public function create_zone_callback(WP_REST_Request $request) 
	{
		$params = $request->get_json_params();
		$user_id = get_current_user_id();
		if (!isset($params['zone_name'])) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone name is required.'
			], 422);
		}
		$zone_name = sanitize_text_field($params['zone_name']);
		if(!isset($params['restaurant_id']))
		{
			$restaurant_id=get_user_meta($user_id, 'user_restaurant_id', true);
		}
		else
		{
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}
		if (empty($zone_name)) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone name is required.'
			], 422);
		}
		if (empty($restaurant_id)) 
		{
			return new WP_REST_Response([
				'success' => false,
				'message' => 'restaurant id is required.'
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
		// Check for existing zone with same name in same restaurant
		$existing_zones = get_posts([
			'post_type'   => 'zones',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_restaurant_id',
					'value' => $restaurant_id,
				],
			],
		]);
		
		foreach ($existing_zones as $zone) {
			if (strcasecmp($zone->post_title, $zone_name) === 0) {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Zone name already exists in this restaurant.',
				], 409); // Conflict
			}
		}

		$post_id = wp_insert_post(array(
			'post_title'  => sanitize_text_field($params['zone_name']),
			'post_type'   => 'zones',
			'post_status' => 'publish',
			'post_author' => $user_id
		));
	
		if(isset($params['zone_status']))
		{
			update_post_meta($post_id, '_status', $params['zone_status']);
		}
		else
		{
			update_post_meta($post_id, '_status', 'active');
		}
		if(isset($restaurant_id))
		{
			update_post_meta($post_id, '_restaurant_id', intval($restaurant_id));
		}
		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Zone successfully created',
			'zone_id'       => $post_id,
			'zone_status'   => get_post_meta($post_id,'_status',true),
			'restaurant_id' => get_post_meta($post_id,'_restaurant_id',true),
		], 200);
	}

    public function update_zone_callback(WP_REST_Request $request) 
	{
		$params = $request->get_json_params();
		// Validate zone_id
		if (!isset($params['zone_id']) || empty($params['zone_id'])) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		$zone_id = intval($params['zone_id']);
		$existing = get_post($zone_id);

		if (empty($existing) || $existing->post_type !== 'zones' || $existing->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone not found or not published.',
			], 409); // 409 Conflict
		}
		$user_id = get_current_user_id();

		// Get restaurant_id from param or user meta
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
		// Validate and fetch zone_name
		$zone_name = isset($params['zone_name']) ? sanitize_text_field($params['zone_name']) : '';
		if (isset($params['zone_name']) && $zone_name === '') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone name cannot be empty.'
			], 422);
		}

		// Check uniqueness of zone name (only if being updated)
		if (!empty($zone_name)) {
			$existing_zones = get_posts([
				'post_type'      => 'zones',
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'exclude'        => [$zone_id],
				'meta_query'     => [
					[
						'key'   => '_restaurant_id',
						'value' => $restaurant_id,
					],
				],
			]);

			foreach ($existing_zones as $zone) 
			{
				if (strcasecmp($zone->post_title, $zone_name) === 0) {
					return new WP_REST_Response([
						'success' => false,
						'message' => 'Cannot update. Zone name already exists in this restaurant.',
					], 409); // Conflict
				}
			}
		}

		// Prepare post update array
		$post_data = [
			'ID'          => $zone_id,
			'post_status' => 'publish',
			'post_type'   => 'zones',
			'post_author' => $user_id,
		];

		if (!empty($zone_name)) {
			$post_data['post_title'] = $zone_name;
		}

		$post_id = wp_update_post($post_data, true);

		if (is_wp_error($post_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to update zone: ' . $post_id->get_error_message(),
			], 400);
		}

		// Update post meta
		if (isset($params['zone_status'])) {
			update_post_meta($post_id, '_status', sanitize_text_field($params['zone_status']));
		}
		update_post_meta($post_id, '_restaurant_id', intval($restaurant_id));

		// Success response
		return new WP_REST_Response([
			'success'       => true,
			'message'       => 'Zone updated successfully',
			'zone_id'       => $post_id,
			'zone_status'   => get_post_meta($post_id, '_status', true),
			'restaurant_id' => get_post_meta($post_id, '_restaurant_id', true),
		], 200);
	}

	public function delete_zone_callback(WP_REST_Request $request) 
	{
		$params = $request->get_json_params();
		// Sanitize input
		if (!isset($params['zone_id'])) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		$zone_id = intval($params['zone_id']);
		$user_id = get_current_user_id();
		if(!isset($params['restaurant_id']))
		{
			$restaurant_id=get_user_meta($user_id, 'user_restaurant_id', true);
		}
		else
		{
			$restaurant_id = sanitize_text_field($params['restaurant_id']);
		}
		if (empty($zone_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}
		if (empty($restaurant_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'restaurant id is required.'
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
		// Use get_posts() to check if the zone belongs to the restaurant
		$matched_zones = get_posts([
			'post_type'   => 'zones',
			'post_status' => 'publish',
			'include'     => [$zone_id],
			'meta_query'  => [
				[
					'key'     => '_restaurant_id',
					'value'   => $restaurant_id,
					'compare' => '=',
					'type'    => 'NUMERIC',
				],
			],
			'fields'      => 'ids',
			'numberposts' => 1,
		]);

		if (empty($matched_zones)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone not found or does not belong to the specified restaurant.',
			], 404);
		}
		// Check if the zone exists
		$existing = get_post($zone_id);

		if (empty($existing) || $existing->post_type !== 'zones' || $existing->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone not found or not published.',
			], 409); // 409 Conflict
		}
		$query = new WP_Query([
			'post_type'      => 'tables',
			'post_status'    => 'publish',
			'posts_per_page' => 1, // we only need to know if at least one exists
			'meta_query'     => [
				[
					'key'     => '_table_zone_id',
					'value'   => $zone_id,
					'compare' => '='
				]
			]
		]);

		if ( ! $query->have_posts() ) {
			$deleted = wp_trash_post( $zone_id );
		} else {
			// Optional: return or throw error
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone cannot be deleted because it has tables assigned to it.',
			],400);
		}

		if (!$deleted || is_wp_error($deleted)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to delete zone.',
			], 400);
		}

		// Success
		return new WP_REST_Response([
			'success' => true,
			'message' => 'Zone deleted successfully.',
			'zone_id' => $zone_id,
			'restaurant_id' => $restaurant_id
		], 200);

	}

	public function getAllZones($request)
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		if(!isset($params['restaurant_id']))
		{
			$restaurant_id=get_user_meta($user_id, 'user_restaurant_id', true);
		}
		else
		{
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
		$existing = get_posts([
			'post_type'      => 'zones',
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

		$zones = [];
		foreach ($existing as $zone) {
			$zones[] = [
				"zone_id"       => $zone->ID,
				'zone_name'     => $zone->post_title,
				'zone_status'   => get_post_meta($zone->ID, '_status', true),
				'restaurant_id' => get_post_meta($zone->ID, '_restaurant_id', true),
			];
		}
		return new WP_REST_Response([
			'success'      => true,
			'message'      => count($zones) ? 'All zones fetched successfully.' : 'No zones found for your restaurant.',
			'zone_details' => $zones,
		], 200);
	}
	public function getAllKotZones($request)
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		if(!isset($params['restaurant_id']))
		{
			$restaurant_id=get_user_meta($user_id, 'user_restaurant_id', true);
		}
		else
		{
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
		$existing = get_posts([
			'post_type'      => 'zones',
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

		$zones = [];
		foreach ($existing as $zone) {
			$zones[] = [
				"zone_id"       => $zone->ID,
				'zone_name'     => $zone->post_title,
				'zone_status'   => get_post_meta($zone->ID, '_status', true),
				'restaurant_id' => get_post_meta($zone->ID, '_restaurant_id', true),
			];
		}
		if(!empty($zones))
		{
			$zones[] = [
				'zone_id'       => 0,
				'zone_name'     => 'All',
				'zone_status'   => 'Publish',
				'restaurant_id' => "0",
			];
		}
		return new WP_REST_Response([
			'success'      => true,
			'message'      => count($zones) ? 'All zones fetched successfully.' : 'No zones found for your restaurant.',
			'zone_details' => $zones,
		], 200);
	}
	public function getZonesById($request)
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		if(!isset($params['restaurant_id']))
		{
			$restaurant_id=get_user_meta($user_id, 'user_restaurant_id', true);
		}
		else
		{
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
		if (!isset($params['zone_id'])) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}

		$zone_id = intval($params['zone_id']);
		if (empty($zone_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone id is required.'
			], 422);
		}

		$zone = get_post($zone_id);
		if (!$zone || $zone->post_type !== 'zones' || $zone->post_status !== 'publish') {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone not found or not published.',
			], 404);
		}

		// ✅ Compare restaurant id
		$zone_restaurant_id = get_post_meta($zone->ID, '_restaurant_id', true);
		if ($restaurant_id != $zone_restaurant_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Zone does not belong to your restaurant.'
			], 403);
		}

		// Build response
		$zone_details = [
			"zone_id"       => $zone->ID,
			'zone_name'     => $zone->post_title,
			'zone_status'   => get_post_meta($zone->ID, '_status', true),
			'restaurant_id' => $zone_restaurant_id,
		];

		return new WP_REST_Response([
			'success'      => true,
			'message'      => 'Zone details fetched successfully.',
			'zone_details' => $zone_details,
		], 200);
	}
}