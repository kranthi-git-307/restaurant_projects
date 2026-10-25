<?php
/**
 * REST API Payment API controller
 *
 * Handles requests to the Payments API endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use WooCommerce\RestApi;

/**
 * REST API Report Top Sellers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Report_Sales_V1_Controller
 */
class Pinaka_Shifts_Api_Controller {

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
	protected $rest_base = 'shifts';

	public $curret_time;
	/**
	 * The timezone for the site.
	 *
	 * @var string
	 */

	/**
	 * Constructor.
	 * Sets the timezone to the site's timezone.
	 */
	public function __construct() {
		$this->curret_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ); // safer with date() // Should now return IST
	}


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-shift',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'shift_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-shift',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'shift_update_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-shifts-by-user',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shifts_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);


		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-shift-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shifts_by_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/close-shift',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'shift_logout_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

        register_rest_route(
			$this->namespace,
			$this->rest_base . '/current-shift',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_current_shift_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/today-shifts',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'get_today_shifts_callback'],
				'permission_callback' => [$this, 'check_user_role_permission'],
			]
		);
		
		register_rest_route(
            $this->namespace,
            $this->rest_base . '/get-all-shifts',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_all_shifts_callback'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
           $this->rest_base . '/reports/shift-report',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_shift_report'],
                'permission_callback' => [$this, 'check_user_role_permission']
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

    /**
	 * Create a new shift post and calculate over/short.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */

	public function shift_create_callback(WP_REST_Request $request) {
        $data = $request->get_json_params();           
        // Validate required field
        if (empty($data['shift_emp'])) {
            return new WP_Error('pinakapos_rest_cannot_create', __('Shift employees are required.', 'pinaka-pos'), ['status' => 400]);
        }

		$current_timestamp = current_time('timestamp'); // UNIX timestamp
        $current_date = date('Y-m-d', $current_timestamp);

        $shifts_options = get_option('pinaka_pos_shift_settings');
        $shifts = $shifts_options['shifts'] ?? [];
        $shifts_array = [];
        $i = 1;

        foreach ($shifts as $shift) {
            $start_time_str = $shift['start'] ?? '';
            $end_time_str   = $shift['end'] ?? '';

            if (!$start_time_str || !$end_time_str) {
                continue;
            }

            // Create DateTime objects from shift times
			$start_datetime = new DateTime("$current_date $start_time_str");
			$end_datetime   = new DateTime("$current_date $end_time_str");

			// Handle overnight shift (e.g., 6:00 PM to 12:00 AM — technically next day)
			if ($end_datetime < $start_datetime) {
				$end_datetime->modify('+1 day');
			}

			$start_timestamp = $start_datetime->getTimestamp();
			$end_timestamp   = $end_datetime->getTimestamp();

			// Check if current time is within this shift
			if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
				$shifts_array = [
					'id'         => $i,
					'name'       => $shift['name'],
					// Properly formatted 12-hour AM/PM time
					'start_time' => $start_datetime->format('g:iA'),
					'end_time'   => $end_datetime->format('g:iA'),
				];
			}

            $i++;
        }
		

        $user_id    = get_current_user_id();
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name  = get_user_meta($user_id, 'last_name', true);

        $title = sanitize_text_field($data['title'] ?? ('Shift Start - ' . $first_name . ' ' . $last_name . ' ' . current_time('mysql')));
        $shift_name = strtolower(trim($title));
        $shift_date = sanitize_text_field($data['shift_date'] ?? current_time('Y-m-d'));
        $shift_start = sanitize_text_field($data['start_time'] ?? current_time('mysql'));
        $shift_end = sanitize_text_field($data['end_time'] ?? '');

        // ❗ Check if there is already an open shift for today
        $open_shift = get_posts([
            'post_type'      => 'shifts',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_shift_date',
                    'value'   => $shift_date,
                    'compare' => '='
                ],
                [
                    'key'     => '_shift_status',
                    'value'   => 'open',
                    'compare' => '='
                ]
            ]
        ]);

        if (!empty($open_shift)) {
            return new WP_Error(
                'pinakapos_shift_open_exists',
                __('An open shift already exists for today. Please close it before creating a new one.', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        // Create the shift post
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_type'    => 'shifts',
            'post_status'  => 'publish',
        ]);

        if (is_wp_error($post_id)) {
            return new WP_Error('pinakapos_rest_insert_failed', __('Failed to create shift.', 'pinaka-pos'), ['status' => 500]);
        }

        // Save meta
        update_post_meta($post_id, '_shift_user_id', $user_id);
        update_post_meta($post_id, '_shift_start_time', $shift_start);
        update_post_meta($post_id, '_shift_date', $shift_date);
        update_post_meta($post_id, '_shift_status', 'open');
        update_post_meta($post_id, '_shift_start_notes', sanitize_textarea_field($data['shift_start_notes'] ?? ''));
        update_post_meta($post_id, '_shift_start_employee', $user_id);
        update_post_meta($post_id, '_shift_timing',  $shifts_array);

        if (!empty($data['end_time'])) {
            update_post_meta($post_id, '_shift_end_time', $shift_end);
        }

        // if (isset($data['shift_emp']) && is_array($data['shift_emp'])) {
        //     $managers = array_map('intval', $data['shift_emp']);
        //     update_post_meta($post_id, '_shift_managers', $managers);
        // }

        // if (isset($data['shift_absent_emp']) && is_array($data['shift_absent_emp'])) {
        //     $absentees = array_map('intval', $data['shift_absent_emp']);
        //     update_post_meta($post_id, '_shift_absent_emp', $absentees);
        // }

		// if (isset($data['shift_emp']) && is_array($data['shift_emp'])) {
			// Convert all to integers and remove duplicates
			// $managers = array_map('intval', $data['shift_emp']);
			// Ensure $user_id is included in the managers list
			// if (!in_array((int) $user_id, $managers, true)) {
			// 	$managers[] = (int) $user_id;
			// }
			// // Remove duplicates just in case
			// $managers = array_values(array_unique($managers));
		// 	update_post_meta($post_id, '_shift_managers', $managers);
		// }

		// if (isset($data['shift_absent_emp']) && is_array($data['shift_absent_emp'])) {
		// 	$absentees = array_map('intval', $data['shift_absent_emp']);
			// If the current user is in managers list, ensure they're NOT marked absent
			// if (isset($managers) && in_array((int) $user_id, $managers, true)) {
			// 	// Remove $user_id from $absentees if found
			// 	$index = array_search((int) $user_id, $absentees, true);
			// 	if ($index !== false) {
			// 		unset($absentees[$index]);
			// 	}
			// }
			// if (isset($managers) && is_array($managers)) {
			// 	$absentees = array_diff($absentees, $managers);
			// }
			// // Reindex array to avoid gaps
			// $absentees = array_values(array_unique($absentees));
		// 	update_post_meta($post_id, '_shift_absent_emp', $absentees);
		// }
		if (!empty($data['shift_emp']) && is_array($data['shift_emp'])) {
			$managers = array_values(array_unique(array_map('intval', $data['shift_emp'])));
			if (!in_array((int) $user_id, $managers, true)) {
				$managers[] = (int) $user_id;
			}
			update_post_meta($post_id, '_shift_managers', $managers);
		}

		if (!empty($data['shift_absent_emp']) && is_array($data['shift_absent_emp'])) {
			$absentees = array_values(array_unique(array_map('intval', $data['shift_absent_emp'])));
			if (!empty($managers) && in_array((int) $user_id, $managers, true)) {
				$absentees = array_diff($absentees, [(int) $user_id]);
			}
			update_post_meta($post_id, '_shift_absent_emp', array_values($absentees));
		}
        // return rest_ensure_response([
        //     'shift_id'          => $post_id,
        //     'user'              => trim($first_name . ' ' . $last_name),
        //     'status'            => 'open',
        //     'over_short'        => 0,
		// 	'shift_details' 	=> "",
        //     'shift_emp'         => json_encode(get_post_meta($post_id, '_shift_managers', true)),
        //     'shift_absent_emp'  => json_encode(get_post_meta($post_id, '_shift_absent_emp', true)),
		// 	'shift_timings'		=> json_encode(get_post_meta($post_id, '_shift_timing', true))
        // ]);

		return rest_ensure_response([
            'shift_id'          => $post_id,
            'user'              => trim($first_name . ' ' . $last_name),
            'status'            => 'open',
            'over_short'        => 0,
			'shift_details' 	=> "",
            'shift_emp'         => (array) get_post_meta($post_id, '_shift_managers', true),
            'shift_absent_emp'  => (array) get_post_meta($post_id, '_shift_absent_emp', true),
			'shift_timings'		=> (array) get_post_meta($post_id, '_shift_timing', true)
        ]);
    }


	function shift_update_callback(WP_REST_Request $request ) {
        $data = $request->get_json_params();

		$shift_id = intval( $data['shift_id'] );

		if ( ! $shift_id || get_post_type( $shift_id ) !== 'shifts' ) {
			return new WP_Error( 'invalid_shift', 'Invalid shift ID.', [ 'status' => 400 ] );
		}

		// --- Validate and calculate drawer total ---
		$calculated_drawer_total = 0;
		if ( ! empty( $data['drawer_closing_denom'] ) && is_array( $data['drawer_closing_denom'] ) ) {
			foreach ( $data['drawer_closing_denom'] as $denom ) {
				$calculated_drawer_total += floatval( $denom['denom'] ) * intval( $denom['denom_count'] );
			}
			if ( abs( $calculated_drawer_total - floatval( $data['drawer_closing_total_amount'] ) ) > 0.01 ) {
				// return new WP_Error( 'invalid_data', 'Drawer total does not match denominations.', [ 'status' => 400 ] );
			}
		}
		
		// --- Validate and calculate tube total ---
		$calculated_tube_total = 0;
		if ( ! empty( $data['tube_closing_denom'] ) && is_array( $data['tube_closing_denom'] ) ) {
			foreach ( $data['tube_closing_denom'] as $item ) {
				if ( ! isset( $item['denomination'], $item['tube_count'], $item['cell_count'], $item['total'] ) ) {
					// return new WP_Error( 'invalid_data', 'Invalid tube denomination format.', [ 'status' => 400 ] );
				}
				$calculated = floatval( $item['denomination'] ) * intval( $item['tube_count'] ) * intval( $item['cell_count'] );
				if ( abs( $item['total'] - $calculated ) > 0.01 ) {
					// return new WP_Error( 'invalid_data', 'Tube denomination total mismatch.', [ 'status' => 400 ] );
				}
				$calculated_tube_total += $calculated;
			}
			if ( abs( $calculated_tube_total - floatval( $data['tube_closing_total_amount'] ) ) > 0.01 ) {
				// return new WP_Error( 'invalid_data', 'Tube total does not match denominations.', [ 'status' => 400 ] );
			}
		}
		
		// --- Validate overall total ---
		$combined_total = floatval( $data['drawer_closing_total_amount'] ) + floatval( $data['tube_closing_total_amount'] );
		if ( abs( $combined_total - floatval( $data['total_amount'] ) ) > 0.01 ) {
			// return new WP_Error( 'invalid_data', 'Combined closing total mismatch.', [ 'status' => 400 ] );
		}

		// --- Save to post meta ---
		update_post_meta( $shift_id, '_shift_closing_balance', $combined_total );
		update_post_meta( $shift_id, '_shift_drawer_closing_denom', wp_json_encode( $data['drawer_closing_denom'] ) );
		update_post_meta( $shift_id, '_shift_drawer_closing_total', floatval( $data['drawer_closing_total_amount'] ) );
		update_post_meta( $shift_id, '_shift_tube_closing_denom', wp_json_encode( $data['tube_closing_denom'] ) );
		update_post_meta( $shift_id, '_shift_tube_closing_total', floatval( $data['tube_closing_total_amount'] ) );

		// --- Update optional status ---
		if ( ! empty( $data['status'] ) ) {
			update_post_meta( $shift_id, '_shift_status', sanitize_text_field( $data['status'] ) );
		}
		// --- Set end time ---
		update_post_meta( $shift_id, '_shift_end_time', sanitize_text_field( $data['end_time'] ?? current_time( 'mysql' ) ) );

		// --- Closing notes ---
		if ( isset( $data['shift_closing_notes'] ) ) {
			update_post_meta( $shift_id, '_shift_closing_notes', sanitize_text_field( $data['shift_closing_notes'] ) );
		}

		$user_id    = get_current_user_id();
		$first_name = get_user_meta($user_id, 'first_name', true);
		$last_name  = get_user_meta($user_id, 'last_name', true);

		// --- Safe drop (optional) ---

		$safe_drop = $this->get_shift_safe_drops($shift_id);
		$total_safe_drop = $safe_drop['total_safe_drop'] ?? 0;
		// --- Calculations ---
	    $opening_balance = floatval( get_post_meta( $shift_id, '_shift_opening_balance', true ) );

			// Get all payments for this shift
			$payments = get_posts([
				'post_type'   => 'payments',
				'post_status' => 'publish',
				'numberposts' => -1,
				'meta_query'  => [
					[
						'key'   => '_payment_shift_id',
						'value' => $shift_id,
					]
				],
			]);

			$total_sale_amount = 0;

			foreach ( $payments as $payment ) {
				$total_sale_amount += (float) get_post_meta(
					$payment->ID,
					'_payment_amount',
					true
				);
			}

			$total_sales_in_cash = $this->get_shift_sales_total( $shift_id );
			$sale_amount_in_bank = $total_sale_amount - $total_sales_in_cash;

		$total_payouts   = $this->get_shift_vendor_payouts_total( $shift_id )['total_vendor_payments'] ?? 0;
		$expected_cash = $opening_balance + $total_sales_in_cash + $calculated_tube_total - ($total_payouts + $total_safe_drop);
		$over_short    = $combined_total - $expected_cash;

		update_post_meta( $shift_id, '_shift_total_sales', $total_sale_amount );
		update_post_meta( $shift_id, '_shift_total_sales_cash', round( $total_sales_in_cash, 2 ) );
		update_post_meta( $shift_id, '_shift_total_sales_card', round( $sale_amount_in_bank, 2 ) );

		update_post_meta( $shift_id, '_shift_total_payouts', $total_payouts );
		update_post_meta( $shift_id, '_shift_expected_cash', round( $expected_cash, 2 ) );
		update_post_meta( $shift_id, '_shift_over_short', round( $over_short, 2 ) );
		update_post_meta( $shift_id, '_shift_safe_drop_total', round( $total_safe_drop, 2 ) );
		update_post_meta( $shift_id, '_shift_closed_by', $user_id );

        // if (isset($data['shift_emp']) && is_array($data['shift_emp'])) {
        //     $managers = array_map('intval', $data['shift_emp']);
			// if (!in_array((int) $user_id, $managers, true)) {
			// 	$managers[] = (int) $user_id;
			// }
			// // Remove duplicates just in case
			// $managers = array_values(array_unique($managers));
        //     update_post_meta($shift_id, '_shift_managers', $managers);
        // }

        // if (isset($data['shift_absent_emp']) && is_array($data['shift_absent_emp'])) {
        //     $absentees = array_map('intval', $data['shift_absent_emp']);
			// if (isset($managers) && is_array($managers)) {
			// 	$absentees = array_diff($absentees, $managers);
			// }
			// $absentees = array_values(array_unique($absentees));
        //     update_post_meta($shift_id, '_shift_absent_emp', $absentees);
        // }
		// if (!empty($data['shift_emp']) && is_array($data['shift_emp'])) {
		// 	$managers = array_values(array_unique(array_map('intval', $data['shift_emp'])));
		// 	if (!in_array((int) $user_id, $managers, true)) {
		// 		$managers[] = (int) $user_id;
		// 	}
		// 	update_post_meta($shift_id, '_shift_managers', $managers);
		// }

		// if (!empty($data['shift_absent_emp']) && is_array($data['shift_absent_emp'])) {
		// 	$absentees = array_values(array_unique(array_map('intval', $data['shift_absent_emp'])));
		// 	if (!empty($managers) && in_array((int) $user_id, $managers, true)) {
		// 		$absentees = array_diff($absentees, [(int) $user_id]);
		// 	}
		// 	update_post_meta($shift_id, '_shift_absent_emp', array_values($absentees));
		// }
		// 1️⃣ Get previously saved data
		$prev_managers  = get_post_meta($shift_id, '_shift_managers', true);
		$prev_absentees = get_post_meta($shift_id, '_shift_absent_emp', true);

		$prev_managers  = is_array($prev_managers) ? $prev_managers : [];
		$prev_absentees = is_array($prev_absentees) ? $prev_absentees : [];

		$new_managers  = (!empty($data['shift_emp']) && is_array($data['shift_emp']))
			? array_map('intval', $data['shift_emp'])
			: [];

		$new_absentees = (!empty($data['shift_absent_emp']) && is_array($data['shift_absent_emp']))
			? array_map('intval', $data['shift_absent_emp'])
			: [];

		// $managers  = array_values(array_unique(array_merge($prev_managers, $new_managers)));
		// $absentees = array_values(array_unique(array_merge($prev_absentees, $new_absentees)));

		// if (!in_array((int) $user_id, $managers, true)) {
		// 	$managers[] = (int) $user_id;
		// }

		// $absentees = array_diff($absentees, $managers);

		// update_post_meta($shift_id, '_shift_managers', array_values($managers));
		// update_post_meta($shift_id, '_shift_absent_emp', array_values($absentees));

		// Get new values from request
		$managers = (!empty($data['shift_emp']) && is_array($data['shift_emp']))
			? array_values(array_unique(array_map('intval', $data['shift_emp'])))
			: [];

		$absentees = (!empty($data['shift_absent_emp']) && is_array($data['shift_absent_emp']))
			? array_values(array_unique(array_map('intval', $data['shift_absent_emp'])))
			: [];

		// ensure no duplicates across lists
		$absentees = array_diff($absentees, $managers);

		// save
		update_post_meta($shift_id, '_shift_managers', $managers);
		update_post_meta($shift_id, '_shift_absent_emp', array_values($absentees));


		// --- Return result ---
		// return [
		// 	'shift_id'        => $shift_id,
		// 	'total_amount'   => round( $expected_cash, 2 ),
		// 	'status'          => get_post_meta( $shift_id, '_shift_status', true ),
		// 	'over_short'      => round( $over_short, 2 ),
        //     'shift_emp'         => json_encode(get_post_meta($shift_id, '_shift_managers', true)),
        //     'shift_absent_emp'  => json_encode(get_post_meta($shift_id, '_shift_absent_emp', true)),
		// 	'shift_timings'		=> get_post_meta($shift_id, '_shift_timing', true)
		// ];
		return [
			'shift_id'        => $shift_id,
			'total_amount'   => round( $expected_cash, 2 ),
			'status'          => get_post_meta( $shift_id, '_shift_status', true ),
			'over_short'      => round( $over_short, 2 ),
            'shift_emp'         => (array) get_post_meta($shift_id, '_shift_managers', true),
            'shift_absent_emp'  => (array) get_post_meta($shift_id, '_shift_absent_emp', true),
			'shift_timings'		=> (array) get_post_meta($shift_id, '_shift_timing', true)
		];
	}


    public function get_current_shift_callback() {

        $open_shift = get_posts([
            'post_type'      => 'shifts',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            // 'meta_query'     => [
            //     [
            //         'key'     => '_shift_status',
            //         'value'   => 'open',
            //         'compare' => '='
            //     ]
            // ]
        ]);
		if(isset($open_shift[0]) && !empty($open_shift[0])) 
		{
        	$shift = $open_shift[0];
		} 
		else 
		{	
			// $latest_shift = get_posts([
			// 	'post_type'       => 'shifts',
			// 	'post_status'     => 'publish',
			// 	'posts_per_page'  => 1,
			// 	'fields'          => 'ids',
			// 	'orderby'         => 'ID',
			// 	'order'           => 'DESC',
			// ]);
			$latest_shift = get_posts([
				'post_type'       => 'shifts',
				'post_status'     => 'publish',
				'posts_per_page'  => 1,
				'fields'          => 'ids',
				'orderby'         => 'modified', // order by last modified
				'order'           => 'DESC',     // most recently updated first
			]);
			$shift = $latest_shift[0];
		}
		
        $meta_keys = array(
			'start_time', 'end_time', 'assigned_staff', 'total_sales', 'safe_drop_total',
			'opening_balance', 'closing_balance', 'notes', 'cash_count_details',
			'status', 'user_id', 'opening_denominations', 'closing_denominations', 'over_short'
		);

		$sales_info = $this->get_shift_no_of_sales( $shift );

		// Calculate total sales amount
		$total_sales = count( $sales_info );

		$total_sale_amount = 0;
		foreach ($sales_info as $sale) {
			$total_sale_amount += floatval($sale['total']);
		}

		$safe_drops = $this->get_shift_safe_drops( $shift );
		
		$vendor_payouts = $this->get_shift_vendor_payouts_total( $shift );
	
		$opening_balance = floatval( get_post_meta( $shift, '_shift_opening_balance', true ) );

		$start_time = get_post_meta( $shift, '_shift_start_time', true );

		$end_time = get_post_meta( $shift, '_shift_end_time', true );
		$shift_status = get_post_meta( $shift, '_shift_status', true ) ?: 'open';
		$over_short = get_post_meta( $shift, '_shift_over_short', true ) ?: 0;
        $shift_emp = get_post_meta( $shift, '_shift_managers', true ) ?: array();
        $shift_absent_emp = get_post_meta( $shift, '_shift_absent_emp', true ) ?: array();
		return rest_ensure_response(array(
			'shift_id'                => $shift,
			'title'                   => get_the_title( $shift ),
			'user_id'                 => intval( get_post_meta( $shift, '_shift_user_id', true ) ),
			'user_name'               => get_the_author_meta( 'display_name', get_post_meta( $shift, '_shift_user_id', true ) ),
			'assigned_staff'          => intval( get_post_meta( $shift, '_shift_assigned_staff', true ) ),
			'shift_date'              => get_post_meta( $shift, '_shift_date', true ),
			'start_time'              => $start_time,
			'end_time'                => $end_time,
			'total_sales'             => $total_sales ?? 0,
			'total_sale_amount'       => round($total_sale_amount, 2) ?? 0,
			'safe_drop_total'         => floatval( @$safe_drops['total_safe_drop'] ) ?? 0,
			'safe_drops'              => $safe_drops['safe_drops'] ?? array(),
			'vendor_payouts'    	  => $vendor_payouts['vendor_payments'] ?? array(),
			'total_vendor_payments'	  => $vendor_payouts['total_vendor_payments'] ?? 0,
			'opening_balance'         => round( $opening_balance, 2 ) ?? 0,
			'closing_balance'         => round( floatval( get_post_meta( $shift, '_shift_closing_balance', true ) ), 2 ) ?? 0,
			'notes'                   => sanitize_textarea_field( get_post_meta( $shift, '_shift_start_notes', true ) ) ?? '',
			'shift_closing_notes'     => sanitize_textarea_field( get_post_meta( $shift, '_shift_closing_notes', true ) )?? '',
			'shift_status'			  => $shift_status,
			'over_short'			  => $over_short,
            'shift_emp'               => $shift_emp,
            'shift_absent_emp'        => $shift_absent_emp,
			'shift_timings'			  => (array) get_post_meta($shift, '_shift_timing', true)
		));
    }

	private function calculate_cash_total( $denominations ) {
		if ( ! is_array( $denominations ) ) {
			$denominations = json_decode( $denominations, true );
		}
		$total = 0;
		foreach ( $denominations as $denomination => $count ) {
			$total += floatval( $denomination ) * intval( $count );
		}
		return round( $total, 2 );
	}

	public function get_shifts_by_user_id_callback( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'shifts',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => array(
				array(
					'key'     => '_shift_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'date_query'  => array(
				array(
					'after'     => date( 'Y-m-d', strtotime( '-7 days' ) ),
					'inclusive' => true,
				)
			),
		);


		$shifts = get_posts( $args );

		$results = array();


		foreach ( $shifts as $shift ) {
			$results[] = $this->prepare_shift_data( $shift );
		}
		return rest_ensure_response( $results );
	}

	public function get_shifts_by_id_callback( WP_REST_Request $request ) {
		$shift_id = $request->get_param( 'shift_id' );
		if ( empty( $shift_id ) ) {
			return new WP_Error( 'invalid_shift_id', 'Shift ID is required', array( 'status' => 400 ) );
		}

		$shift = get_post( $shift_id );
		if ( ! $shift || $shift->post_type !== 'shifts' ) {
			return new WP_Error( 'not_found', 'Shift not found', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->prepare_shift_data( $shift ) );
	}

	/**
	 * Helper method to structure shift data consistently
	 */
	private function prepare_shift_data( $shift ) {
		$meta_keys = array(
			'start_time', 'end_time', 'assigned_staff', 'total_sales', 'safe_drop_total',
			'opening_balance', 'closing_balance', 'notes', 'cash_count_details',
			'status', 'user_id', 'opening_denominations', 'closing_denominations', 'over_short'
		);

		$sales_info = $this->get_shift_no_of_sales( $shift->ID );

		// Calculate total sales amount
		$total_sales = count( $sales_info );

		$total_sale_amount = 0;
		foreach ($sales_info as $sale) {
			$total_sale_amount += floatval($sale['total']);
		}


		$safe_drops = $this->get_shift_safe_drops( $shift->ID );
		
		$vendor_payouts = $this->get_shift_vendor_payouts_total( $shift->ID );
	
		$opening_balance = floatval( get_post_meta( $shift->ID, '_shift_opening_balance', true ) );

        $start_time = get_post_meta( $shift->ID, '_shift_start_time', true );
        $end_time   = get_post_meta( $shift->ID, '_shift_end_time', true );
        
        if (!empty($start_time)) {
            $start_time = date('g:iA', strtotime($start_time));
        }
        
        if (!empty($end_time)) {
            $end_time = date('g:iA', strtotime($end_time));
        }
		$shift_status = get_post_meta( $shift->ID, '_shift_status', true ) ?: 'open';
		$over_short = get_post_meta( $shift->ID, '_shift_over_short', true ) ?: 0;
        $shift_emp = get_post_meta( $shift->ID, '_shift_managers', true ) ?: array();
        $shift_absent_emp = get_post_meta( $shift->ID, '_shift_absent_emp', true ) ?: array();
		return array(
			'shift_id'                => $shift->ID,
			'title'                   => $shift->post_title,
			'user_id'                 => intval( get_post_meta( $shift->ID, '_shift_user_id', true ) ),
			'user_name'               => get_the_author_meta( 'display_name', get_post_meta( $shift->ID, '_shift_user_id', true ) ),
			'assigned_staff'          => intval( get_post_meta( $shift->ID, '_shift_assigned_staff', true ) ),
			'shift_date'              => get_post_meta($shift->ID, '_shift_date', true),
			'start_time'              => $start_time,
			'end_time'                => $end_time,
			'total_sales'             => $total_sales ?? 0,
			'total_sale_amount'       => round($total_sale_amount, 2) ?? 0,
			'safe_drop_total'         => floatval( @$safe_drops['total_safe_drop'] ) ?? 0,
			'safe_drops'              => $safe_drops['safe_drops'] ?? array(),
			'vendor_payouts'    	  => $vendor_payouts['vendor_payments'] ?? array(),
			'total_vendor_payments'	  => $vendor_payouts['total_vendor_payments'] ?? 0,
			'opening_balance'         => round( $opening_balance, 2 ) ?? 0,
			'closing_balance'         => round( floatval( get_post_meta( $shift->ID, '_shift_closing_balance', true ) ), 2 ) ?? 0,
			'notes'                   => sanitize_textarea_field( get_post_meta( $shift->ID, '_shift_start_notes', true ) ) ?? '',
			'shift_closing_notes'     => sanitize_textarea_field( get_post_meta( $shift->ID, '_shift_closing_notes', true ) )?? '',
			'shift_status'			  => $shift_status,
			'over_short'			  => $over_short,
            'shift_emp'               => $shift_emp,
            'shift_absent_emp'        => $shift_absent_emp
		);
	}

	/**
	 * Get the total sales amount for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return float The total sales amount.
	 */
	private function get_shift_sales_total( $shift_id ) {
		$payments = get_posts( [
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_shift_id',
					'value' => $shift_id,
				],
				[
					'key'     => '_payment_method',
					'value'   => 'Cash',
					'compare' => '='
				]
			],
		] );

		$total = 0;
		foreach ( $payments as $payment ) {
			$total += floatval( get_post_meta( $payment->ID, '_payment_amount', true ) );
		}

		return round( $total, 2 );
	}


	/**
	 * Get the full order data for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return array The order data array.
	 */
	public function get_shift_no_of_sales( $shift_id ) {
		if ( ! $shift_id ) {
			return [];
		}

		$query = new \WC_Order_Query([
			'limit'      => -1, // get all results
			'status'     => array('wc-completed', 'wc-processing', 'wc-on-hold', 'wc-pending'), // or remove to get all
			'meta_query' => [
				[
					'key'     => 'shift_id',
					'value'   => $shift_id,
					'compare' => '=',
				],
			],
		]);

		$orders = $query->get_orders();

		$order_data = [];

		foreach ( $orders as $order ) {
			$order_data[] = [
				'id'            => $order->get_id(),
				'status'        => $order->get_status(),
				'date_created'  => $order->get_date_created()->date('Y-m-d H:i:s'),
				'total'         => $order->get_total(),
				'payment_method'=> $order->get_payment_method_title(),
				'billing_name'  => $order->get_formatted_billing_full_name(),
				'line_items' => array_map(function($item) {
                        return [
                            'product_id' => $item->get_product_id(),
                            'name'       => $item->get_name(),
                            'qty'        => $item->get_quantity(),
                            'total'      => $item->get_total(),
                            'subtotal'   => $item->get_subtotal(),
                        ];
                    }, $order->get_items()),
			];
		}

		return $order_data;
	}



	private function get_shift_payouts_total( $shift_id ) {
		$payouts = get_posts([
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_shift_id',
					'value' => $shift_id,
				],
				[
					'key'     => '_payment_method',
					'value'   => 'Cash',
					'compare' => '='
				]
			],
		]);

		$total = 0;
		foreach ( $payouts as $payout ) {
			$total += floatval( get_post_meta( $payout->ID, '_payment_amount', true ) );
		}
		return round( $total, 2 );
	}

	public function get_shift_safe_drops( $shift_id ) {
		$safe_drops = get_posts([
			'post_type'   => 'safedrops',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_safedrops_shift_id',
					'value' => $shift_id,
				],
			],
		]);

		$results = [];
		$total_safe_drop = 0;
		foreach ( $safe_drops as $drop ) {
			$time = get_post($drop->ID, 'post_date', true);
			$total_safe_drop += floatval(get_post_meta($drop->ID, '_safedrops_total', true));
			// print_r($time->post_date);
			$results['safe_drops'][] = [
				'id'        => $drop->ID,
				'total'     => floatval(get_post_meta($drop->ID, '_safedrops_total', true)),
				'denominations' => json_decode(get_post_meta($drop->ID, '_safedrops_data', true), true),
				'note'      => get_post_meta($drop->ID, '_safe_drop_note', true),
				'time'      => $time->post_date,
			];
			$results['total_safe_drop'] = $total_safe_drop ?? 0;
		}
		
		return $results;
	}

	/**
	 * Get the total vendor payouts for a given shift.
	 *
	 * @param int $shift_id The ID of the shift.
	 * @return float The total vendor payouts amount.
	 */
	public function get_shift_vendor_payouts_total( $shift_id ) {
		$payouts = get_posts([
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_vendor_payment_shift_id',
					'value' => $shift_id,
				]
			],
		]);

		$total_vendor_payouts = 0;
		$results = [];
		foreach ( $payouts as $payout ) {
			$total_vendor_payouts += floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true));
			$results['vendor_payments'][] = [
				'id'        => $payout->ID,
				'amount'    => floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true)),
				'note'      => get_post_meta($payout->ID, '_vendor_payment_note', true),
				'payment_method' => get_post_meta($payout->ID, '_vendor_payment_method', true),
				'time'      => get_post($payout->ID, 'post_date', true)->post_date,
				'vendor_name' => get_post($payout->ID, '_vendor_payment_vendor_name', true)->post_title,
				'service_type'	 => get_post_meta($payout->ID, '_vendor_payment_service_type', true),
				'vendor_id' => get_post_meta($payout->ID, '_vendor_id', true),
			];
			$results['total_vendor_payments'] = $total_vendor_payouts;
		}
		return $results;
	}

	public function shift_logout_callback(WP_REST_Request $request) 
	{
		$data = $request->get_json_params();

		// Validate shift_id
		if (!isset($data['shift_id'])) {
			return new WP_Error('missing_shift_id', 'Shift ID is required.', ['status' => 400]);
		}
		$shift_id = intval($data['shift_id']);
		if (!$shift_id || get_post_type($shift_id) !== 'shifts') {
			return new WP_Error('invalid_shift', 'Invalid shift ID.', ['status' => 400]);
		}

		// Check if already closed
		$current_status = get_post_meta($shift_id, '_shift_status', true);
		if ($current_status === 'closed') {
			return new WP_Error('shift_already_closed', 'This shift has already been closed and cannot be updated again.', ['status' => 400]);
		}

		// Validate present_emps
		// if (!isset($data['present_emps'])) {
		// 	return new WP_Error('missing_present_emps', 'Present employees are required.', ['status' => 400]);
		// }
		// if (!is_array($data['present_emps'])) {
		// 	return new WP_Error('invalid_present_emps', 'Present employees must be an array.', ['status' => 400]);
		// }
		// $present_emps = array_map('intval', $data['present_emps']);

		// // Get all assigned employees on this shift
		// $shift_emps = get_post_meta($shift_id, '_shift_managers', true);
		// $shift_emps = is_array($shift_emps) ? $shift_emps : [];

		// // Compute absent employees
		// $absent_emps = array_diff($shift_emps, $present_emps);

		// Get start time, set end time
		$start_time = get_post_meta($shift_id, '_shift_start_time', true);
		$end_time   = $data['end_time'];

		// Save meta
		update_post_meta($shift_id, '_shift_status', 'closed');
		update_post_meta($shift_id, '_shift_end_time', $end_time);
		// update_post_meta($shift_id, '_shift_present_employees', wp_json_encode($present_emps));
		// update_post_meta($shift_id, '_shift_absent_employees', wp_json_encode($absent_emps));

		$user_id = get_current_user_id();
		update_post_meta($shift_id, '_shift_closed_by', $user_id);

		// return [
		// 	'shift_id'     => $shift_id,
		// 	'status'       => 'closed',
		// 	'start_time'   => $start_time,
		// 	'end_time'     => $end_time,
		// 	'present_emps' => $present_emps,
		// 	'absent_emps'  => $absent_emps
		// ];
        $sales_info = $this->get_shift_no_of_sales( $shift_id );

		// Calculate total sales amount
		$total_sales = count( $sales_info );

		$total_sale_amount = 0;
		foreach ($sales_info as $sale) {
			$total_sale_amount += floatval($sale['total']);
		}
        
		$safe_drops = $this->get_shift_safe_drops( $shift_id );
		
		$vendor_payouts = $this->get_shift_vendor_payouts_total( $shift_id );
	
		$opening_balance = floatval( get_post_meta( $shift_id, '_shift_opening_balance', true ) );

		$start_time = get_post_meta( $shift_id, '_shift_start_time', true );

		$end_time = get_post_meta( $shift_id, '_shift_end_time', true );
		$shift_status = get_post_meta( $shift_id, '_shift_status', true ) ?: 'open';
		$over_short = get_post_meta( $shift_id, '_shift_over_short', true ) ?: 0;
        $shift_emp = get_post_meta( $shift_id, '_shift_managers', true ) ?: array();
        $shift_absent_emp = get_post_meta( $shift_id, '_shift_absent_emp', true ) ?: array();
        update_post_meta( $shift_id, '_shift_total_sales', $total_sale_amount );
        
        return rest_ensure_response(array(
			'shift_id'                => $shift_id,
			'title'                   => get_the_title( $shift_id ),
			'user_id'                 => intval( get_post_meta( $shift_id, '_shift_user_id', true ) ),
			'user_name'               => get_the_author_meta( 'display_name', get_post_meta( $shift_id, '_shift_user_id', true ) ),
			'assigned_staff'          => intval( get_post_meta( $shift_id, '_shift_assigned_staff', true ) ),
			'start_time'              => $start_time,
			'end_time'                => $end_time,
			'total_sales'             => $total_sales ?? 0,
			'total_sale_amount'       => round($total_sale_amount, 2) ?? 0,
			'safe_drop_total'         => floatval( @$safe_drops['total_safe_drop'] ) ?? 0,
			'safe_drops'              => $safe_drops['safe_drops'] ?? array(),
			'vendor_payouts'    	  => $vendor_payouts['vendor_payments'] ?? array(),
			'total_vendor_payments'	  => $vendor_payouts['total_vendor_payments'] ?? 0,
			'opening_balance'         => round( $opening_balance, 2 ) ?? 0,
			'closing_balance'         => round( floatval( get_post_meta( $shift_id, '_shift_closing_balance', true ) ), 2 ) ?? 0,
			'notes'                   => sanitize_textarea_field( get_post_meta( $shift_id, '_shift_start_notes', true ) ) ?? '',
			'shift_closing_notes'     => sanitize_textarea_field( get_post_meta( $shift_id, '_shift_closing_notes', true ) )?? '',
			'shift_status'			  => $shift_status,
			'over_short'			  => $over_short,
            'shift_emp'               => $shift_emp,
            'shift_absent_emp'        => $shift_absent_emp,
		));
	}

	public function get_today_shifts_callback() {

    $today = current_time('Y-m-d');

    $shifts = get_posts([
        'post_type'      => 'shifts',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_shift_date',
                'value'   => $today,
                'compare' => '='
            ]
        ]
    ]);

    $results = [];

    foreach ($shifts as $shift) {
        $results[] = $this->prepare_shift_data($shift);
    }

    return rest_ensure_response($results);
}
public function get_all_shifts_callback( WP_REST_Request $request ) {

    $page      = max(1, intval($request->get_param('page')));
    $per_page  = intval($request->get_param('per_page')) ?: 10;

    $search    = sanitize_text_field($request->get_param('search'));
    $status    = sanitize_text_field($request->get_param('status'));
    $from_date = sanitize_text_field($request->get_param('from_date'));
    $to_date   = sanitize_text_field($request->get_param('to_date'));

    $args = [
        'post_type'      => 'shifts',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    ];

    $meta_query = ['relation' => 'AND'];

    if (!empty($status)) {
        $meta_query[] = [
            'key'   => '_shift_status',
            'value' => $status,
        ];
    }

    if (!empty($from_date) || !empty($to_date)) {
        $date_query = [];

        if (!empty($from_date)) {
            $date_query['after'] = $from_date;
        }

        if (!empty($to_date)) {
            $date_query['before'] = $to_date;
        }

        $date_query['inclusive'] = true;

        $args['date_query'] = [$date_query];
    }

    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);

    $data = [];

    foreach ($query->posts as $shift) {
        $data[] = $this->prepare_shift_data($shift);
    }

    return rest_ensure_response([
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $per_page,
            'total'        => $query->found_posts,
            'total_pages'  => $query->max_num_pages,
        ]
    ]);
}
public function get_shift_report(WP_REST_Request $request)
{
    try {

$period = strtolower(trim(sanitize_text_field($request->get_param('period'))));
$period = str_replace(' ', '_', $period);

$from = sanitize_text_field($request->get_param('from'));
$to   = sanitize_text_field($request->get_param('to'));

// Convert datetime to date only
if (!empty($from)) {
    $from = date('Y-m-d', strtotime($from));
}

if (!empty($to)) {
    $to = date('Y-m-d', strtotime($to));
}

        if (empty($from) || empty($to)) {

            switch ($period) {

                case 'today':
                    $from = current_time('Y-m-d');
                    $to   = $from;
                    break;

                case 'yesterday':
                    $from = date('Y-m-d', strtotime(current_time('Y-m-d') . ' -1 day'));
                    $to   = $from;
                    break;

                case 'this_week':
                    $from = date('Y-m-d', strtotime('monday this week'));
                    $to   = current_time('Y-m-d');
                    break;

                case 'this_month':
                    $from = date('Y-m-01');
                    $to   = current_time('Y-m-d');
                    break;

                case 'last_month':
                    $from = date('Y-m-01', strtotime('first day of last month'));
                    $to   = date('Y-m-t', strtotime('last day of last month'));
                    break;

                default:
                    $from = current_time('Y-m-d');
                    $to   = $from;
            }
        }

        $query = new WP_Query([
            'post_type'      => 'shifts',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);

        $summary = [
            'total_shifts'           => 0,
            'closed_shifts'          => 0,
            'open_shifts'            => 0,
            'total_sales'            => 0,
            'total_orders'           => 0,
            'average_sale_per_shift' => 0,
        ];

            $shiftTrend    = [];
            $employeeSales = [];
            $shiftDetails  = [];
            
            $productSales  = [];
            $categorySales = [];
            
            $topProducts   = [];
            $topCategories = [];

        foreach ($query->posts as $post) {

            $shift_date = get_post_meta($post->ID, '_shift_date', true);
            
            if (!empty($shift_date)) {
                $shift_date = date('Y-m-d', strtotime($shift_date));
            }
            error_log("Shift Date: $shift_date | From: $from | To: $to");

            if (empty($shift_date)) {
                continue;
            }

            if ($shift_date < $from || $shift_date > $to) {
                continue;
            }

            $summary['total_shifts']++;

            $status = strtolower(get_post_meta($post->ID, '_shift_status', true));

            if ($status === 'closed') {
                $summary['closed_shifts']++;
            } else {
                $summary['open_shifts']++;
            }

            $sales = (float)get_post_meta($post->ID, '_shift_total_sales', true);
            $summary['total_sales'] += $sales;

            $orders = $this->get_shift_no_of_sales($post->ID);

            if (!is_array($orders)) {
                $orders = [];
            }

            $orderCount = count($orders);
            $summary['total_orders'] += $orderCount;

            $user_id = (int)get_post_meta($post->ID, '_shift_user_id', true);
            $user = get_userdata($user_id);

            $employee = $user ? $user->display_name : 'Unknown';
            
            // Per Shift Sales
$shiftProductSales = [];
$shiftCategorySales = [];

                if (!isset($employeeSales[$employee])) {
                
                    $employeeSales[$employee] = [
                        'employee' => $employee,
                        'sales'    => 0,
                        'orders'   => 0,
                    ];
                }
                
                $employeeSales[$employee]['sales'] += $sales;
                $employeeSales[$employee]['orders'] += $orderCount;

            if (!isset($shiftTrend[$shift_date])) {
                $shiftTrend[$shift_date] = 0;
            }

            $shiftTrend[$shift_date]++;

            

          foreach ($orders as $order) {

    if (empty($order['line_items']) || !is_array($order['line_items'])) {
        continue;
    }

    foreach ($order['line_items'] as $item) {

        $productName = $item['name'] ?? 'Unknown';
        $qty = (int)($item['qty'] ?? 0);

        // Overall report totals
        if (!isset($productSales[$productName])) {
            $productSales[$productName] = 0;
        }
        $productSales[$productName] += $qty;

        // THIS SHIFT ONLY
        if (!isset($shiftProductSales[$productName])) {
            $shiftProductSales[$productName] = 0;
        }
        $shiftProductSales[$productName] += $qty;

        if (!empty($item['product_id'])) {

            $terms = get_the_terms($item['product_id'], 'product_cat');

            if (!empty($terms) && !is_wp_error($terms)) {

                foreach ($terms as $term) {

                    // Overall report totals
                    if (!isset($categorySales[$term->name])) {
                        $categorySales[$term->name] = 0;
                    }
                    $categorySales[$term->name] += $qty;

                    // THIS SHIFT ONLY
                    if (!isset($shiftCategorySales[$term->name])) {
                        $shiftCategorySales[$term->name] = 0;
                    }
                    $shiftCategorySales[$term->name] += $qty;
                }
            }
        }
    }
}
arsort($shiftProductSales);

$shiftTopProducts = [];

foreach (array_slice($shiftProductSales, 0, 5, true) as $name => $qty) {
    $shiftTopProducts[] = [
        'product' => $name,
        'quantity' => $qty,
    ];
}

arsort($shiftCategorySales);

$shiftTopCategories = [];

foreach (array_slice($shiftCategorySales, 0, 5, true) as $name => $qty) {
    $shiftTopCategories[] = [
        'category' => $name,
        'quantity' => $qty,
    ];
}

         $shiftDetails[] = [
    'shift_id' => $post->ID,
    'shift_name' => get_the_title($post->ID),
    'employee' => $employee,
    'date' => $shift_date,
    'sales' => round($sales, 2),
    'orders' => $orderCount,
    'status' => ucfirst($status),

    'top_products' => $shiftTopProducts,
    'top_categories' => $shiftTopCategories,
];
        }

        wp_reset_postdata();

        $summary['average_sale_per_shift'] =
            $summary['total_shifts'] > 0
                ? round($summary['total_sales'] / $summary['total_shifts'], 2)
                : 0;

        $trend = [];

        foreach ($shiftTrend as $date => $count) {
            $trend[] = [
                'date' => $date,
                'shift_count' => $count,
            ];
        }

        $employeeSales = array_values($employeeSales);
        
        usort($employeeSales, function ($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });
        
        arsort($productSales);
        
        foreach (array_slice($productSales, 0, 10, true) as $name => $qty) {
        
            $topProducts[] = [
                'product'  => $name,
                'quantity' => $qty,
            ];
        }
        
        arsort($categorySales);
        
        foreach (array_slice($categorySales, 0, 10, true) as $name => $qty) {
        
            $topCategories[] = [
                'category' => $name,
                'quantity' => $qty,
            ];
        }
        
        usort($shiftDetails, function ($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });

        return new WP_REST_Response([
            'success' => true,
            'period'  => $period,
            'from'    => $from,
            'to'      => $to,
            'summary' => $summary,
            'shift_trend' => $trend,
            'employee_sales' => $employeeSales,
            'highest_selling_employee' => $employeeSales[0] ?? null,
            'highest_selling_shift' => $shiftDetails[0] ?? null,
            'top_products' => $topProducts,
            'top_categories' => $topCategories,
            'shift_details' => $shiftDetails,
        ], 200);

    } catch (\Throwable $e) {

        return new WP_REST_Response([
            'success' => false,
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString()
        ], 500);
    }
}
}
