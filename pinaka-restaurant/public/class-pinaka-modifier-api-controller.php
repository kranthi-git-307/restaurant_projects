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
class Pinaka_Modifiers_Api_Controller {

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
	protected $rest_base = 'modifiers';

	/**
	 * Register the routes for Zones.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-modifiers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_product_modifiers' ),
				'permission_callback' => array($this, 'check_user_role_permission'),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/calculate-price',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate_total_price' ),
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

	public function get_product_modifiers($request)
	{
		$params = $request->get_params();
        $product_id = $params['product_id'];
		
		$query = new WP_Query([
            'post_type'      => 'modifier',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'product_ids',
                    // 'value'   => '"' . $product_id . '"',
					'value'   => 'i:' . intval($product_id) . ';',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
		// print_r($query->posts);die;
        $data = [];
        foreach ($query->posts as $post) {
            $data[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => get_post_meta($post->ID, 'modifier_type', true),
                'price' => get_post_meta($post->ID, 'modifier_price', true)
            ];
        }

        return rest_ensure_response($data);
	}
	function calculate_total_price($request) 
	{
		$product_id = $request->get_param('product_id');
		$modifiers = $request->get_param('selected_modifiers');

		$base_price = floatval(get_post_meta($product_id, '_price', true));
		$modifier_total = 0;

		foreach ($modifiers as $mod_id) {
			$mod_price = floatval(get_post_meta($mod_id, 'modifier_price', true));
			$modifier_total += $mod_price;
		}

		$subtotal = $base_price + $modifier_total;

		return rest_ensure_response([
			'product' => [
				'id' => $product_id,
				'name' => get_the_title($product_id),
				'base_price' => $base_price
			],
			'modifiers' => array_map(function($mod_id) {
				return [
					'id' => $mod_id,
					'name' => get_the_title($mod_id),
					'price' => floatval(get_post_meta($mod_id, 'modifier_price', true))
				];
			}, $modifiers),
			'subtotal' => $subtotal,
			'currency' => 'INR'
		]);
	}

}