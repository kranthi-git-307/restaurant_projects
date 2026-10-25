<?php

/**
 * REST API Categories controller
 *
 * Handles requests to the / endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   1.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API Categories controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Dashboard_Restuarant 
{
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
    protected $rest_base = 'attendance-all';

    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_all_employees'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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
     * 1️⃣ Fetch all employees for the day
     */
    public function get_all_employees(WP_REST_Request $request) {
        $date = sanitize_text_field($request->get_param('date')) ?: current_time('Y-m-d');

        // Fetch today's shifts
        $shifts = get_posts([
            'post_type'   => 'shifts',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_shift_date',
                    'value' => $date,
                ]
            ]
        ]);

        $present_ids = [];
        $absent_ids  = [];
        $shift_map   = []; // Map employee_id => timing

        foreach ($shifts as $shift) {
            $shift_from = get_post_meta($shift->ID, '_shift_start_time', true);
            $shift_to   = get_post_meta($shift->ID, '_shift_end_time', true);

            // Format into 12-hour timing
            $shift_timing = '';
            if ($shift_from && $shift_to) {
                $shift_timing = date("h:i A", strtotime($shift_from)) . " - " . date("h:i A", strtotime($shift_to));
            }

            $shift_managers = (array) get_post_meta($shift->ID, '_shift_managers', true);
            $shift_absents  = (array) get_post_meta($shift->ID, '_shift_absent_emp', true);

            $present_ids = array_merge($present_ids, $shift_managers);
            $absent_ids  = array_merge($absent_ids, $shift_absents);

            foreach ($shift_managers as $emp_id) {
                $shift_map[$emp_id] = $shift_timing;
            }
        }

        $present_ids = array_unique($present_ids);
        $absent_ids  = array_unique($absent_ids);

        // ✅ Get all users except admin, merchant, manager
        $excluded_roles = ['administrator', 'merchant'];
        $all_users = get_users([
            'orderby' => 'ID',
            'order'   => 'ASC',
            'fields'  => ['ID', 'display_name'],
        ]);

        $employees = [];

        foreach ($all_users as $user) {
            $user_data = get_userdata($user->ID);
            $roles     = $user_data->roles;

            if (array_intersect($excluded_roles, $roles)) {
                continue;
            }

            $designation = ucfirst($roles[0] ?? 'Employee');
            $user_id     = $user->ID;

            if (in_array($user_id, $present_ids)) {
                $status = 'Present';
            } elseif (in_array($user_id, $absent_ids)) {
                $status = 'Absent';
            } else {
                $status = '-'; // not marked
            }

            $phone = get_user_meta($user_id, 'phone_number', true);
            $current_shift_id = get_user_meta($user_id, 'user_shift_number', true);
        
            $shift_label = '';
            $shift_settings = get_option('pinaka_pos_shift_settings');
            $shifts = $shift_settings['shifts'] ?? [];
            
            if (!empty($current_shift_id) && !empty($shifts)) {
                foreach ($shifts as $shift) {
                    if (!empty($shift['shift_id']) && $shift['shift_id'] == $current_shift_id) {
                        $shift_label = $shift['name'];
                        break;
                    }
                }
            }
            
            $shift_label = !empty($shift_label) ? esc_html($shift_label) : '-';

            // Output the display name
        
            $employees[] = [
                'user_id'           => (int) $user_id,
                'name'              => $user->display_name,
                'phone'             => $phone ?: '',
                'designation'       => $designation,
                'attendance_status' => $status,
                'shift_timing'    => $shift_label
            ];
        }

        return new WP_REST_Response([
            'success'   => true,
            'date'      => $date,
            'employees' => $employees,
        ], 200);
    }


    /**
     * 2️⃣ Fetch only current shift employees
     */
    public function get_current_shift_employees(WP_REST_Request $request) {
        $date = sanitize_text_field($request->get_param('date')) ?: current_time('Y-m-d');
        $time = sanitize_text_field($request->get_param('time')); // ✅ define $time

        // If time param given, parse it as 12-hour format, else use current time
        if ($time) {
            $current_time = strtotime($date . ' ' . $time);
        } else {
            $current_time = strtotime(current_time('Y-m-d H:i:s'));
        }

        // Fetch shifts for the given date & open status
        $shifts = get_posts([
            'post_type'   => 'shifts',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_shift_date',
                    'value' => $date,
                ],
                [
                    'key'   => '_shift_status',
                    'value' => 'open'
                ],
            ]
        ]);

        if (empty($shifts)) {
            return new WP_REST_Response([
                'success'   => false,
                'message'   => 'No shift found for the given date/time.',
                'employees' => [],
            ], 200);
        }

        // ✅ Take the first open shift (or loop if multiple)
        $current_shift = $shifts[0];

        $present_ids = (array) get_post_meta($current_shift->ID, '_shift_managers', true);
        $absent_ids  = (array) get_post_meta($current_shift->ID, '_shift_absent_emp', true);

        $all_users = get_users(); 
        $employees = [];

        foreach ($all_users as $user_data) {
            $user_id = $user_data->ID;
            $roles   = (array) $user_data->roles;

            // Skip admin & merchant roles
            if (in_array('administrator', $roles) || in_array('merchant', $roles) || in_array('shop_manager', $roles)) {
                continue;
            }

            $designation = ucfirst($roles[0] ?? 'Employee');

            if (in_array($user_id, $present_ids)) {
                $status = 'Present';
            } elseif (in_array($user_id, $absent_ids)) {
                $status = 'Absent';
            } else {
                $status = ' '; // not marked
            }

            $employees[] = [
                'user_id'           => (int) $user_id,
                'name'              => $user_data->display_name,
                'designation'       => $designation,
                'attendance_status' => $status,
            ];
        }

        return new WP_REST_Response([
            'success'   => true,
            'date'      => $date,
            'time'      => $time ? date("h:i A", strtotime($time)) : current_time('h:i A'),
            'shift_id'  => $current_shift->ID,
            'employees' => $employees,
        ], 200);
    }

    public function get_all_completed_orders(WP_REST_Request $request){
        global $wpdb;

        $results = $wpdb->get_results("
            SELECT id as order_id
            FROM {$wpdb->prefix}wc_orders
            WHERE status = 'wc-completed'
            ORDER BY id DESC
            LIMIT 5
        ");

        $orders = [];

        foreach($results as $row){
            $order = wc_get_order($row->order_id);
            if(!$order){
                continue;
            }

            // Detect order type
            if($order->get_created_via() === 'rest-api'){
                $order_type = 'Dine In';
            }
            else if($order->get_created_via() === 'takeaway'){
                $order_type = 'Takeaways';
            }
            else {
                continue;
            }

            // ✅ Detect payment type (normalize to Cash / UPI / Card)
            $payment_method = strtolower($order->get_payment_method());

            if (strpos($payment_method, 'upi') !== false) {
                $payment_type = 'UPI';
            } elseif (strpos($payment_method, 'card') !== false) {
                $payment_type = 'Card';
            } elseif (strpos($payment_method, 'cash') !== false) {
                $payment_type = 'Cash';
            } else {
                $payment_type = 'Cash'; // fallback if not set
            }

            $order_data = [
                'order_id'       => $order->get_id(),
                'order_type'     => $order_type,
                'date'           => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
                'customer_name'  => $order->get_formatted_billing_full_name(),
                'customer_phone' => $order->get_billing_phone(),
                'payment_type'   => $payment_type,
                'amount'         => $order->get_subtotal(),
                'discount'       => $order->get_discount_total(),
                'total'          => $order->get_total(),
                'status'         => $order->get_status(),
            ];

            $orders[] = $order_data;
        }

        return new WP_REST_Response($orders, 200);
    }

    public function get_inventory_alerts(WP_REST_Request $request) {
        $args = [
            'post_type'      => 'inventory',
            'post_status'    => 'publish',
            'posts_per_page' => -1, // get all first
            'fields'         => 'ids',
        ];

        $query  = new WP_Query($args);
        $alerts = [];

        foreach ($query->posts as $post_id) {
            $qty       = floatval(get_post_meta($post_id, '_inventory_quantity', true));
            $threshold = floatval(get_post_meta($post_id, '_inventory_threshold', true));
            $unit      = get_post_meta($post_id, '_inventory_quantity_unit', true);

            if ($qty <= $threshold) {
                $alerts[] = [
                    'id'        => $post_id,
                    'name'      => get_the_title($post_id),
                    'restaurant_id'   => get_post_meta($post_id, '_inventory_restaurant_id', true),
                    'restaurant_name' => get_post_meta($post_id, '_inventory_restaurant_name', true),
                    'alert'            => 'Reorder',
                ];
            }
        }

        // Limit to 5 alerts only
        $alerts = array_slice($alerts, 0, 5);

        return rest_ensure_response($alerts);
    }

}
