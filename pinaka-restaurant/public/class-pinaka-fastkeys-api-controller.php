<?php
/**
 * REST API FastKeys API controller
 *
 * Handles requests to the FastKeys API endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API FastKeys Controller class.
 */
class Pinaka_FastKeys_Api_Controller {

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
	protected $rest_base = 'fastkeys';

	/**
	 * Register the routes for FastKeys.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_fast_key_api' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/add-products',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_products_to_fast_key' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-user',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_fastkeys_by_user' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-fastkey-id/(?P<fastkey_id>\d+)?',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_products_by_fastkey_id' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-by-name/(?P<employee_name>[a-zA-Z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_fastkeys_by_name' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-fastkey', 
			array(
				'methods'  => 'POST',
				'callback' => array($this, 'delete_fast_key_api'), // Ensure method is inside class
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

	/**
	 * Create FastKeys entry with existing image path.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	function create_fast_key_api(WP_REST_Request $request) {
		$params = $request->get_params();
		$user_id = get_current_user_id();

		// Validate required fields
		if (empty($params['fastkey_title']) || empty($params['fastkey_index'])) {
			return new WP_REST_Response(array('message' => 'Missing required fields'), 400);
		}

		// Insert new Fast Key post
		$post_id = wp_insert_post(array(
			'post_title'  => sanitize_text_field($params['fastkey_title']),
			'post_type'   => 'fast_keys',
			'post_status' => 'publish',
		));

		if ($post_id) {
			// Store meta fields
			update_post_meta($post_id, '_fast_keys_user_id', $user_id);
			update_post_meta($post_id, '_fast_key_index', intval($params['fastkey_index']));

			// Handle existing image path
			$fastkey_image_url = '';
			if (!empty($params['fastkey_image'])) {
				$upload_dir = wp_upload_dir();
				$fastkey_image_url = $upload_dir['baseurl'] . '/' . ltrim($params['fastkey_image'], '/');

				// Get attachment ID by URL
				$attachment_id = attachment_url_to_postid($fastkey_image_url);
				if ($attachment_id) {
					set_post_thumbnail($post_id, $attachment_id);
				}
			}

			return new WP_REST_Response(array(
				'status'        => 'success',
				'message'       => 'Fast Key created',
				'fastkey_id'    => $post_id,
				'fastkey_title' => $params['fastkey_title'],
				'fastkey_index' => intval($params['fastkey_index']),
				'fastkey_image' => $fastkey_image_url
			), 201);
		}

		return new WP_REST_Response(array('message' => 'Error creating Fast Key', 'status' => 'error'), 500);
	}


	public function add_products_to_fast_key( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if (empty($data['fastkey_id']) || empty($data['products'])) {
			return new WP_REST_Response(array('message' => 'Missing required fields', 'status' => 'error'), 400);
		}
	
		$fastkey_id = intval($data['fastkey_id']);
		$existing_fastkeys_json = get_post_meta($fastkey_id, '_fast_keys_data', true);
		$fastkey_index = get_post_meta($fastkey_id, '_fast_key_index', true);
		$existing_fastkeys = json_decode($existing_fastkeys_json, true) ?: [];
	
		$new_products = $data['products'];
		$updated_products = $existing_fastkeys;
		$failed_products = [];
	
		foreach ($new_products as $new_product) {
			$product_id = intval($new_product['product_id']);
			$sl_number = intval($new_product['sl_number']);
			$product = wc_get_product($product_id);
	
			if (!$product) {
				$failed_products[] = array(
					'product_id' => $product_id,
					'message' => 'Product does not exist',
					'status' => 'failed',
				);
				continue;
			}
	
			// Check if product already exists
			$exists = false;
			foreach ($updated_products as &$existing_product) {
				if ($existing_product['product_id'] === $product_id) {
					$exists = true;
					break;
				}
			}
	
			if (!$exists) {
				$updated_products[] = array(
					'product_id'   => $product_id,
					'sl_number'    => $sl_number
				);
			}
		}
	
		// Update post meta with modified list
		update_post_meta($fastkey_id, '_fast_keys_data', wp_json_encode($updated_products));
	
		$enhanced_fastkeys = [];
		
		// Loop through assigned products
		foreach ($updated_products as $fastkey) {
			$product_id = intval($fastkey['product_id']);
			$product = wc_get_product($product_id);
	
			if ($product) {
				$enhanced_fastkeys[] = array(
					'product_id'   => $product_id,
					'name'         => $product->get_name(),
					'price'        => $product->get_price(),
					'image'        => wp_get_attachment_url($product->get_image_id()),
					'category'     => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
					'sl_number'   => $fastkey['sl_number'], // Assuming `sl_number` is sort order
				);
			}
		}
	
		// Get product count
		$product_count = count($updated_products);
	
		return array(
			'fastkey_id'  => $fastkey_id,
			'fastkey_title' => get_the_title($fastkey_id),
			'fastkey_image' => get_the_post_thumbnail_url($fastkey_id, 'full'),
			'fastkey_index' => intval($fastkey_index),
			'itemCount' => $product_count, // Added product count
			'message'  => !empty($failed_products) ? 'Some products failed to add' : 'FastKeys updated successfully',
			'status' => !empty($failed_products) ? 'partial_success' : 'success',
			'products' => $enhanced_fastkeys,
			'failed_products' => $failed_products,
		);
	}
	
	
	
	

	/**
	 * Get FastKeys for a user by user ID with product details.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function get_fastkeys_by_user() {

		$user_id = get_current_user_id();
		
		// Retrieve FastKeys for the user
		$posts = get_posts(array(
			'post_type'  => 'fast_keys',
			'numberposts' => -1, // Get all posts
			'meta_query' => array(
				array(
					'key'   => '_fast_keys_user_id',
					'value' => $user_id,
				),
			),
		));

		$all_fastkeys = [];

		if (empty($posts)) {
			return array(
				'user_id'   => $user_id,
				'message'   => 'FastKeys retrieved successfully',
				'status'    => 'success',
				'fastkeys'  => [],  // Return empty fastkeys array instead of an error
			);
		}

		foreach ($posts as $post) {
			// Get FastKey meta data
			$fastkeys_json = get_post_meta($post->ID, '_fast_keys_data', true);
			$fastkeys_data = json_decode($fastkeys_json, true) ?: [];

			// Get Post Title & Featured Image
			$fastkey_title = get_the_title($post->ID);
			$fastkey_image = get_the_post_thumbnail_url($post->ID, 'full') ?: ''; // Ensure it's not null
			$fastkey_index = get_post_meta($post->ID, '_fast_key_index', true) ?: 0; // Ensure index has a value

			$enhanced_fastkeys = [];

			// Loop through assigned products
			foreach ($fastkeys_data as $fastkey) {
				$product_id = intval($fastkey['product_id']);
				$product = wc_get_product($product_id);

				if ($product) {
					$enhanced_fastkeys[] = array(
						'product_id'   => $product_id,
						'name'         => $product->get_name(),
						'price'        => $product->get_price(),
						'image'        => wp_get_attachment_url($product->get_image_id()) ?: '',
						'category'     => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
						'sl_number'    => $fastkey['sl_number'] ?? 0, // Ensure `sl_number` is set
					);
				}
			}

			$all_fastkeys[] = array(
				'fastkey_id'    => $post->ID,
				'fastkey_title' => $fastkey_title,
				'fastkey_image' => $fastkey_image,
				'itemCount'     => count($fastkeys_data), // Product count
				'user_id'       => $user_id,
				'fastkey_index' => intval($fastkey_index),
				'products'      => $enhanced_fastkeys
			);
		}

		return array(
			'user_id'   => $user_id,
			'message'   => 'FastKeys retrieved successfully',
			'status'    => 'success',
			'fastkeys'  => $all_fastkeys,
		);
	}

	
	/**
	 * Get products by FastKey ID with product details.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */

	public function get_products_by_fastkey_id( WP_REST_Request $request ) {

		$post_id = $request['fastkey_id'];
		if ( empty( $post_id ) ) {
			return new WP_Error( 'invalid_data', 'Fast Key ID is required', array( 'status' => 400 ) );
		}

		$fastkeys_json = get_post_meta($post_id, '_fast_keys_data', true);
	
		$fastkeys_data = json_decode($fastkeys_json, true) ?: [];

		// Get Post Title & Featured Image
		$fastkey_title = get_the_title($post_id);
		$fastkey_image = get_the_post_thumbnail_url($post_id, 'full');
		$fastkey_index = get_post_meta($post_id, '_fast_key_index', true);

		$enhanced_fastkeys = [];

		// Loop through assigned products
		foreach ($fastkeys_data as $fastkey) {
			$product_id = intval($fastkey['product_id']);
			$product = wc_get_product($product_id);

			if ($product) {
				$enhanced_fastkeys[] = array(
					'product_id'   => $product_id,
					'name'         => $product->get_name(),
					'price'        => $product->get_price(),
					'image'        => wp_get_attachment_url($product->get_image_id()),
					'category'     => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
					'sl_number'   => $fastkey['sl_number'], // Assuming `sl_number` is sort order
				);
			}
		}
	
		return array(
			'fastkey_id'  => $post_id,
			'fastkey_title' => $fastkey_title,
			'fastkey_image' => $fastkey_image,
			'fastkey_index' => intval($fastkey_index),
			'products' => $enhanced_fastkeys, // Now includes product details
			'message' => 'FastKey products retrieved successfully',
			'status' => 'success',
		);
	}

	/**
	 * Delete a FastKey by ID.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	function delete_fast_key_api(WP_REST_Request $request) {
		$parameters = $request->get_params();
		$fastkey_id = isset($parameters['fastkey_id']) ? intval($parameters['fastkey_id']) : 0;
		$user_id = get_current_user_id();
	
		if (!$fastkey_id) {
			return new WP_REST_Response(array('message' => 'FastKey ID is required', 'status' => 'error'), 400);
		}
	
		$fastkey_post = get_post($fastkey_id);
		if (!$fastkey_post || $fastkey_post->post_type !== 'fast_keys') {
			return new WP_REST_Response(array('message' => 'FastKey not found', 'status' => 'error'), 404);
		}
	
		// Validate ownership
		$owner_id = get_post_meta($fastkey_id, '_fast_keys_user_id', true);
		if ($owner_id != $user_id) {
			return new WP_REST_Response(array('message' => 'Unauthorized access', 'status' => 'error'), 403);
		}
	
		// Delete the FastKey
		$deleted = wp_delete_post($fastkey_id, true);
		if ($deleted) {
			return new WP_REST_Response(array('message' => 'FastKey deleted successfully', 'status' => 'success', 'fastkey_id' => $fastkey_id), 200);
		}
	
		return new WP_REST_Response(array('message' => 'Error deleting FastKey', 'status' => 'error'), 500);
	}
}
