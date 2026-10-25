<?php
/**
 * The admin-customer info functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pinaka_Customer_Info_Api_Controller {

	protected $namespace = 'pinaka-restaurant-pos/v1';
	protected $rest_base = 'customer-info';

	public function register_routes() {
		register_rest_route($this->namespace, '/' . $this->rest_base . '/create-customer', [
			'methods'             => 'POST',
			'callback'            => [$this, 'create_customer_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/customer-orders', [
			'methods'             => 'GET',
			'callback'            => [$this, 'customer_orders_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/get-all-customers', [
			'methods'             => 'GET',
			'callback'            => [$this, 'get_all_customers_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		// Add this inside register_routes()
		register_rest_route($this->namespace, '/' . $this->rest_base . '/manual-add-customer', [
			'methods'             => 'POST',
			'callback'            => [$this, 'manual_add_customer_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/manual-update-customer', [
			'methods'             => 'POST',
			'callback'            => [$this, 'manual_update_customer_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/manual-delete-customer', [
			'methods'             => 'POST',
			'callback'            => [$this, 'manual_delete_customer_api'],
			'permission_callback' => [$this, 'check_user_role_permission'],
		]);

	}

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
	 * POST: /create-customer
	 */
	public function create_customer_api($request) 
	{
		$user_id = get_current_user_id();
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$restaurant_ids = array_column($restaurants, 'id');

		$params = $request->get_json_params();
		if (!is_array($params)) {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid JSON body.'], 400);
		}

		if(!isset($params['restaurant_id']))
		{
			$restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
		}
		else
		{
			$restaurant_id = intval(sanitize_text_field($params['restaurant_id']));
		}
		// Validate restaurant_id
		if (empty($restaurant_id)) {
			return new WP_REST_Response(['success' => false, 'message' => 'Restaurant ID is required.'], 422);
		}
		if (!in_array($restaurant_id, $restaurant_ids)) {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid restaurant ID.'], 404);
		}
		$user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($restaurant_id !== intval($user_restaurant_id)) 
		{
			return new WP_REST_Response(['success' => false, 'message' => 'User not assigned to this restaurant.'], 409);
		}

		if (empty($params['customer_name'])) {
			return new WP_REST_Response(['success' => false, 'message' => 'Customer name is required.'], 422);
		}
		$name = sanitize_text_field($params['customer_name']);

		// Validate customer_mobile (mandatory, must start 6-9 and 10 digits)
		if (empty($params['customer_mobile'])) {
			return new WP_REST_Response(['success' => false, 'message' => 'Customer mobile number is required.'], 422);
		}
		$mobile = preg_replace('/\s+/', '', sanitize_text_field($params['customer_mobile'])); // remove spaces
		if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid mobile number. Must start with 6-9 and be exactly 10 digits.'
			], 422);
		}
		if(!isset($params['customer_email'])){
			return new WP_REST_Response(['success' => false, 'message' => 'Customer email address is required.'], 422);
		}
		if(!empty($params['customer_email']) && !is_email($params['customer_email'])) {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid email address.'], 422);
		}

		$email = sanitize_email($params['customer_email']);
		$user = (!empty($email) && is_email($email)) ? get_user_by('email', $email) : null;
		
		// Step 2: If not found by email, try by mobile
		if (!$user && !empty($mobile)) {
			$user_query = new WP_User_Query([
				'meta_key'   => 'billing_phone',
				'meta_value' => $mobile,
				'role'       => 'customer',
				'number'     => 1,
			]);
			$users = $user_query->get_results();
			$user  = !empty($users) ? $users[0] : null;
		}
	
		// Step 3: If still no user, create new one
		if (!$user) 
		{
			$username = sanitize_user(str_replace(' ', '', strtolower($name)));

			$password = wp_generate_password(12, true);
		
			$user_id = wp_create_user($username, $password, $email);

			if (is_wp_error($user_id)) 
			{
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Failed to create customer: ' . $user_id->get_error_message(),
				], 500);
			}

			wp_update_user([
				'ID'           => $user_id,
				'display_name' => $name,
				'first_name'   => $name,
			]);

			update_user_meta($user_id, 'billing_phone', $mobile);
			update_user_meta($user_id, 'billing_email', $email);
			wp_update_user(['ID' => $user_id, 'role' => 'customer']);
		} 
		else 
		{
			$user_id = $user->ID;
		}

		$order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
		if (!$order_id) {
			return new WP_REST_Response(['success' => false, 'message' => 'Order ID is required.'], 400);
		}

		// $order = wc_get_order($order_id);
		// if (!$order) {
		// 	return new WP_REST_Response(['success' => false, 'message' => 'Order not found.'], 400);
		// }
		if (wc_get_order($order_id)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Customer is created but Order ID already exists. Please use a new Order ID.',
			], 400);
		}
		// $existing_customer_id = $order->get_customer_id();

		// if (!empty($existing_customer_id)) {
		// 	// Order already has customer — skip user creation/assignment
		// 	return new WP_REST_Response([
		// 		'success' => true,
		// 		'message' => 'Customer already existed for this order.',
		// 		'order_id' => $order_id,
		// 		'customer_id' => $existing_customer_id
		// 	]);
		// }

		// Step 4: Assign user to order
		$order->set_customer_id($user_id);

		$order->update_meta_data('_customer_name', $name);
		$order->update_meta_data('_customer_mobile', $mobile);
		$order->update_meta_data('_customer_email', $email);

		if (isset($params['customer_dob']) && !empty($params['customer_dob']) && preg_match('/^(0[1-9]|[12][0-9]|3[01])\-(0[1-9]|1[0-2])$/', $params['customer_dob'])) {
			$order->update_meta_data('_customer_dob', $params['customer_dob']);
		}

		$order->save();

		return new WP_REST_Response([
			'success'     => true,
			'message'     => $user ? 'Customer assigned to order.' : 'Customer created and assigned to order.',
			'order_id'    => $order_id,
			'customer_id' => $user_id
		]);
		
	}
	/**
	 * GET: /customer-orders
	 */
	public function customer_orders_api($request) 
	{
		$params = $request->get_params();
		$user_id = get_current_user_id();
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$restaurant_ids = array_column($restaurants, 'id');

		if(!isset($params['restaurant_id']))
		{
			$restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
		}
		else
		{
			$restaurant_id = intval(sanitize_text_field($params['restaurant_id']));
		}
		// Validate restaurant_id
		if (empty($restaurant_id)) {
			return new WP_REST_Response(['success' => false, 'message' => 'Restaurant ID is required.'], 422);
		}
		if (!in_array($restaurant_id, $restaurant_ids)) {
			return new WP_REST_Response(['success' => false, 'message' => 'Invalid restaurant ID.'], 404);
		}
		$user_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));
		if ($restaurant_id !== intval($user_restaurant_id)) 
		{
			return new WP_REST_Response(['success' => false, 'message' => 'User not assigned to this restaurant.'], 409);
		}

		$mobile = sanitize_text_field($request->get_param('mobile'));
		$name   = sanitize_text_field($request->get_param('name'));
		$email  = sanitize_email($request->get_param('email'));

		if (empty($mobile) && empty($name) && empty($email)) {
			return new WP_Error('invalid_data', 'Provide at least a mobile number or customer name or email.', ['status' => 400]);
		}

		$orders = $this->get_order_history($mobile, $name, $email);

		return [
			'success' => true,
			'orders'  => $orders
		];
	}
	
	public function get_order_history($mobile = '', $name = '', $email = '') 
	{
		global $wpdb;
		// 1. Build user query
		$user_query = [
			'role'       => 'customer',
			'fields'     => 'ID',
			'meta_query' => ['relation' => 'AND'], // AND logic between meta fields
		];

		if (!empty($mobile)) {
			$user_query['meta_query'][] = [
				'key'     => 'billing_phone',
				'value'   => $mobile,
				'compare' => '=',
			];
		}

		if (!empty($name)) {
			$user_query['meta_query'][] = [
				'key'     => 'first_name', 
				'value'   => $name,
				'compare' => '=',
			];
		}

		// Add email filter as exact match (not LIKE)
		if (!empty($email)) {
			$user_query['search']         = $email;
			$user_query['search_columns'] = ['user_email'];
		}

		// Abort if no filters provided
		if (empty($user_query['meta_query']) && empty($email)) {
			return [
				'success' => false,
				'message' => 'At least one filter (name, mobile, or email) is required.',
				'data'    => [],
			];
		}

		// 2. Get customer IDs
		$customer_ids = get_users($user_query);

		if (empty($customer_ids)) {
			return [
				'success' => false,
				'message' => 'No customers found matching the provided filters.',
				'data'    => [],
			];
		}

		$customer_ids = array_map('intval', $customer_ids);
		$placeholders = implode(',', $customer_ids);

		// 3. Fetch order IDs from wc_orders by customer_id
		$sql = "
			SELECT o.id
			FROM {$wpdb->prefix}wc_orders o
			WHERE o.customer_id IN ($placeholders)
		";
	
		$order_ids = $wpdb->get_col($sql);

		if (empty($order_ids)) {
			return [
				'success' => false,
				'message' => 'No orders found for the matching customer(s).',
				'data'    => [],
			];
		}

		// 4. Load orders via wc_get_order
		$results = [];
		foreach ($order_ids as $order_id) {
			$order = wc_get_order($order_id);
			if (!$order) continue;

			$results[] = [
				'order_id' => $order->get_id(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
				'status'   => wc_get_order_status_name($order->get_status()),
				'meta'     => [
					'customer_id'     => $order->get_customer_id(),
					'customer_name'   => $order->get_billing_first_name(),
					'customer_mobile' => $order->get_billing_phone(),
					'customer_email'  => $order->get_billing_email(),
					'customer_dob'    => $order->get_meta('_customer_dob'),
				],
			];
		}

		return $results;
	}
	public function get_all_customers_api($request) 
	{
		$args = [
			'role'    => 'customer',
			'orderby' => 'registered',
			'order'   => 'DESC',
			'number'  => -1,
		];

		$users = get_users($args);
		$customers = [];

		foreach ($users as $user) {
			$user_id = $user->ID;

			$total_orders = wc_get_customer_order_count($user_id);
			$total_spent  = wc_get_customer_total_spent($user_id);
			$aov = $total_orders > 0 ? round($total_spent / $total_orders, 2) : 0;

			$customers[] = [
				'id'              => $user_id,
				'name'            => $user->display_name,
				'username'        => $user->user_login,
				'email'           => $user->user_email,
				'phone'           => get_user_meta($user_id, 'billing_phone', true),
				'registered_at'   => $user->user_registered,
				'orders'          => $total_orders,
				'total_spent'     => floatval($total_spent),
				'aov'             => floatval($aov),
				'country'         => get_user_meta($user_id, 'billing_country', true),
				'city'            => get_user_meta($user_id, 'billing_city', true),
				'region'          => get_user_meta($user_id, 'billing_state', true),
				'postal_code'     => get_user_meta($user_id, 'billing_postcode', true),
			];
		}

		return rest_ensure_response([
			'success'   => true,
			'total'     => count($customers),
			'customers' => $customers,
		]);
	}


public function manual_add_customer_api($request)
{
    $params = $request->get_json_params();

    if (empty($params['customer_name'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Customer name is required.'], 422);
    }

    $name = sanitize_text_field($params['customer_name']);

    // Validate mobile
    if (empty($params['customer_mobile'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Customer mobile number is required.'], 422);
    }
    $mobile = preg_replace('/\s+/', '', sanitize_text_field($params['customer_mobile']));
    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid mobile number. Must start with 6-9 and be exactly 10 digits.'
        ], 422);
    }

    // Validate email
    if (empty($params['customer_email']) || !is_email($params['customer_email'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Valid email is required.'], 422);
    }

    $email = sanitize_email($params['customer_email']);
    $dob   = !empty($params['customer_dob']) ? sanitize_text_field($params['customer_dob']) : '';

    // Check existing customer by email
    $user = get_user_by('email', $email);

    // Check by phone if not found
    if (!$user) {
        $user_query = new WP_User_Query([
            'meta_key'   => 'billing_phone',
            'meta_value' => $mobile,
            'role'       => 'customer',
            'number'     => 1,
        ]);
        $users = $user_query->get_results();
        $user  = !empty($users) ? $users[0] : null;
    }

    // Create new WooCommerce customer if not found
    if (!$user) {
        $username = sanitize_user(str_replace(' ', '', strtolower($name))) . '_' . time();
        $password = wp_generate_password(12, true);

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Failed to create customer: ' . $user_id->get_error_message(),
            ], 500);
        }

        // Set basic user info
        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        ]);

        // Add WooCommerce customer role
        wp_update_user(['ID' => $user_id, 'role' => 'customer']);

        // Save billing info
        update_user_meta($user_id, 'billing_phone', $mobile);
        update_user_meta($user_id, 'billing_email', $email);

        if (!empty($dob)) {
            update_user_meta($user_id, 'customer_dob', $dob);
        }

        return new WP_REST_Response([
            'success'     => true,
            'message'     => 'Customer created successfully.',
            'customer_id' => $user_id,
            'data'        => [
                'name'   => $name,
                'email'  => $email,
                'mobile' => $mobile,
                'dob'    => $dob,
            ]
        ], 200);

    } else {
        // Customer exists
        $user_id = $user->ID;
        $existing_dob = get_user_meta($user_id, 'customer_dob', true);

        // Update DOB if provided and not empty
        if (!empty($dob)) {
            update_user_meta($user_id, 'customer_dob', $dob);
            $existing_dob = $dob;
        }

        return new WP_REST_Response([
            'success'     => false,
            'message'     => 'Customer already exists.',
            'customer_id' => $user_id,
            'data'        => [
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'mobile' => get_user_meta($user_id, 'billing_phone', true),
                'dob'    => $existing_dob,
            ]
        ], 200);
    }
}

public function manual_update_customer_api($request)
{
    $params = $request->get_json_params();

    // Validate customer ID
    if (empty($params['customer_id'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Customer ID is required.'], 422);
    }

    $customer_id = intval($params['customer_id']);
    $user = get_user_by('ID', $customer_id);

    if (!$user || !in_array('customer', (array) $user->roles)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid customer ID.'], 404);
    }

    // Sanitize input fields
    $name   = !empty($params['customer_name']) ? sanitize_text_field($params['customer_name']) : '';
    $email  = !empty($params['customer_email']) ? sanitize_email($params['customer_email']) : '';
    $mobile = !empty($params['customer_mobile']) ? preg_replace('/\s+/', '', sanitize_text_field($params['customer_mobile'])) : '';
    $dob    = !empty($params['customer_dob']) ? sanitize_text_field($params['customer_dob']) : '';

    // Update name
    if (!empty($name)) {
        wp_update_user([
            'ID'           => $customer_id,
            'display_name' => $name,
            'first_name'   => $name,
        ]);
    }

    // Update email (validate)
    if (!empty($email)) {
        if (!is_email($email)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Invalid email format.'], 422);
        }

        $email_exists = get_user_by('email', $email);
        if ($email_exists && $email_exists->ID != $customer_id) {
            return new WP_REST_Response(['success' => false, 'message' => 'Email already used by another customer.'], 409);
        }

        wp_update_user([
            'ID'         => $customer_id,
            'user_email' => $email,
        ]);
        update_user_meta($customer_id, 'billing_email', $email);
    }

    // Update mobile (validate)
    if (!empty($mobile)) {
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            return new WP_REST_Response(['success' => false, 'message' => 'Invalid mobile number. Must start with 6-9 and be exactly 10 digits.'], 422);
        }

        update_user_meta($customer_id, 'billing_phone', $mobile);
    }

    // Update DOB
    if (!empty($dob)) {
        update_user_meta($customer_id, 'customer_dob', $dob);
    }

    // Prepare response data
    $updated_user = get_user_by('ID', $customer_id);

    $response_data = [
        'customer_id' => $customer_id,
        'name'        => $updated_user->display_name,
        'email'       => $updated_user->user_email,
        'mobile'      => get_user_meta($customer_id, 'billing_phone', true),
        'dob'         => get_user_meta($customer_id, 'customer_dob', true),
    ];

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Customer details updated successfully.',
        'data'    => $response_data,
    ], 200);
}

public function manual_delete_customer_api($request)
{
    $params = $request->get_json_params();

    // Validate customer ID
    if (empty($params['customer_id'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Customer ID is required.'], 422);
    }

    $customer_id = intval($params['customer_id']);
    $user = get_user_by('ID', $customer_id);

    // Check if valid customer
    if (!$user || !in_array('customer', (array) $user->roles)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Invalid customer ID.'], 404);
    }

    // Prevent admin/merchant deletion by mistake
    if (in_array('administrator', (array) $user->roles) || in_array('merchant', (array) $user->roles)) {
        return new WP_REST_Response(['success' => false, 'message' => 'You cannot delete this user type.'], 403);
    }

    // Attempt deletion
    $deleted = wp_delete_user($customer_id);

    if ($deleted) {
        return new WP_REST_Response([
            'success' => true,
            'message' => 'Customer deleted successfully.',
            'deleted_customer_id' => $customer_id
        ], 200);
    } else {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Failed to delete customer. Please try again.'
        ], 500);
    }
}

}