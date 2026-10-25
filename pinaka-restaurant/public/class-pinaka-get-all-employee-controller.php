<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Get_All_Employee_Api_controller {
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'users';

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-employees',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_users_by_specific_roles'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );


        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-shifts',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_all_shifts'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-users',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_all_users_kitchen'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );

        register_rest_route(
            $this->namespace,
            '/users/create',
            [
                [
                    'methods'  => 'POST',
                    'callback' => [$this, 'create_user'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ]
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

    public function get_users_by_specific_roles($request) {
        // $target_roles = ['captain', 'manager', 'chef', 'employee'];
        global $wp_roles;
        $target_roles = array_keys( $wp_roles->roles );
        $curruser_id = get_current_user_id();


        $all_users = get_users(['number' => -1]);
        $filtered_users = [];

        foreach ($all_users as $user) {
            $user_roles = (array) $user->roles;
            $shift_id = get_user_meta( $user->ID, 'user_shift_number', true );
            $shifts_options = get_option('pinaka_pos_shift_settings');
            $shifts = $shifts_options['shifts'];

            $shift_name = isset($shifts[$shift_id]['name']) ? $shifts[$shift_id]['name'] : '';
            if (array_intersect($target_roles, $user_roles)) {
                if($curruser_id === $user->ID)
                {
                    $ispresent = true;
                }
                else
                {
                    $ispresent = false;
                }
                $filtered_users[] = [
                    'ID'       => $user->ID,
                    'username' => $user->user_login,
                    'name'     => $user->display_name . ' (' . $shift_name . ')',
                    'email'    => $user->user_email,
                    'role'     => implode(',', $user_roles),
                    'ispresent' => $ispresent
                ];
            }
        }

        return rest_ensure_response($filtered_users);
    }
    public function get_all_users_kitchen()
    {
        $target_roles = ['captain', 'manager', 'chef', 'admin' ,'administrator','merchant','shop_manager'];

        $all_users = get_users([
            'number' => -1,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);
     
        $filtered_users = [];

        foreach ($all_users as $user) {
            $user_roles = (array) $user->roles;

            if (array_intersect($target_roles, $user_roles)) {
                $filtered_users[] = [
                    'ID'       => $user->ID,
                    'username' => $user->user_login,
                    'name'     => $user->display_name,
                    'email'    => $user->user_email,
                    'role'     => implode(',', $user_roles)
                ];
            }
        }

        return rest_ensure_response($filtered_users);
    }
    public function get_public_item_schema() {
        return [];
    }

    // public function get_all_shifts() {
    //     $current_timestamp = current_time('timestamp'); // WordPress safe timestamp
    //     $current_time = date('H:i', $current_timestamp);

    //     $shifts_options = get_option('pinaka_pos_shift_settings');
    //     $shifts = $shifts_options['shifts'];
    //     $shifts_array = [];
    //     $i = 1;

    //     foreach ($shifts as $shift) {
    //         $shift_start_time = strtotime($shift['start']);
    //         $shift_start_timestamp = strtotime(date('Y-m-d') . ' ' . $shift['start'], $current_timestamp);

    //         // Calculate 10-minute window before and after shift start time
    //         $min_time = $shift_start_timestamp - (180 * 60); // 10 mins before
    //         $max_time = $shift_start_timestamp + (60 * 60); // 10 mins after

    //         // if ($current_timestamp >= $min_time && $current_timestamp <= $max_time) {
    //             $shifts_array[] = [
    //                 'id' => $i,
    //                 'name' => $shift['name'],
    //                 'start_time' => $shift['start'],
    //                 'end_time' => $shift['end'],
    //             ];
    //         // }
    //         $i++;
    //     }

    //     return rest_ensure_response($shifts_array);
    // }

    public function get_all_shifts() {

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

            // Convert shift start and end times to timestamps for today
            // $start_timestamp = strtotime("$current_date $start_time_str");
            // $end_timestamp   = strtotime("$current_date $end_time_str");

            // // Handle overnight shift (e.g., 22:00 to 02:00)
            // if ($end_timestamp < $start_timestamp) {
            //     $end_timestamp += 86400; // Add 24 hours
            // }

            // if ($current_timestamp >= $start_timestamp && $current_timestamp <= $end_timestamp) {
            //     $shifts_array[] = [
            //         'id'         => $i,
            //         'name'       => $shift['name'],
            //         'start_time' => $shift['start'],
            //         'end_time'   => $shift['end'],
            //     ];
            // }
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

        return rest_ensure_response($shifts_array);
    }


    public function create_user( WP_REST_Request $request ) {

    $username        = sanitize_user($request['username']);
    $name            = sanitize_text_field($request['name']);
    $email           = sanitize_email($request['email']);
    $password        = $request['password'];
    $role            = sanitize_text_field($request['role']);
    $login_pin       = sanitize_text_field($request['login_pin']);
    $user_login_pin  = sanitize_text_field($request['user_login_pin']);

    // Login PIN must be exactly 6 digits
    if (!preg_match('/^\d{6}$/', $login_pin)) {
        return new WP_Error(
            'invalid_login_pin',
            'Login PIN must be exactly 6 digits.',
            ['status' => 400]
        );
    }

    // User Login PIN must be exactly 4 digits
    if (!preg_match('/^\d{4}$/', $user_login_pin)) {
        return new WP_Error(
            'invalid_user_login_pin',
            'User Login PIN must be exactly 4 digits.',
            ['status' => 400]
        );
    }

    if (username_exists($username)) {
        return new WP_Error(
            'username_exists',
            'Username already exists',
            ['status' => 400]
        );
    }

    if (email_exists($email)) {
        return new WP_Error(
            'email_exists',
            'Email already exists',
            ['status' => 400]
        );
    }

    $userdata = [
        'user_login' => $username,
        'user_email' => $email,
        'user_pass'  => $password,
        'role'       => $role,
        'first_name' => $request['first_name'] ?? '',
        'last_name'  => $request['last_name'] ?? '',
    ];

    $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
        return $user_id;
    }

    // Save Login PIN
    update_user_meta($user_id, 'emp_login_pin', $login_pin);

    // Save User Login PIN
    update_user_meta($user_id, 'user_login_pin', $user_login_pin);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

    // Save custom meta if provided
    if (!empty($request['meta']) && is_array($request['meta'])) {
        foreach ($request['meta'] as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
    }

    return rest_ensure_response([
        'success' => true,
        'user_id' => $user_id,
        'username' => $username,
        'role' => $role,
        'message' => 'User created successfully.'
    ]);
}

}
