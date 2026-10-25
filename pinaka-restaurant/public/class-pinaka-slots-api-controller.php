<?php
if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Slots_API_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'slots';

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-slots',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_all_slots_with_status' ],
                'permission_callback' => [ $this, 'check_user_role_permission' ],
            ]
        );
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
    private function generate_time_intervals($start, $end) {
        $intervals = [];

        $start_time = strtotime($start);
        $end_time   = strtotime($end);

        while ($start_time <= $end_time) {
            $intervals[] = [
                'time_24' => date('H:i', $start_time),
                'time_12' => date('h:i A', $start_time)
            ];
            $start_time += 15 * 60;
        }

        return $intervals;
    }

    public function get_all_slots_with_status($request) 
    {
        // $slots = [
        //     ['id' => 1,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '09:00 AM'],
        //     ['id' => 2,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '09:15 AM'],
        //     ['id' => 3,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '09:30 AM'],
        //     ['id' => 4,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '09:45 AM'],
        //     ['id' => 5,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '10:00 AM'],
        //     ['id' => 6,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '10:15 AM'],
        //     ['id' => 7,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '10:30 AM'],
        //     ['id' => 8,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '10:45 AM'],
        //     ['id' => 9,  'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '11:00 AM'],
        //     ['id' => 10, 'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '11:15 AM'],
        //     ['id' => 11, 'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '11:30 AM'],
        //     ['id' => 12, 'restaurant_id' => 1, 'type' => 'breakfast', 'time' => '11:45 AM'],

        //     ['id' => 13, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '12:00 PM'],
        //     ['id' => 14, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '12:15 PM'],
        //     ['id' => 15, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '12:30 PM'],
        //     ['id' => 16, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '12:45 PM'],
        //     ['id' => 17, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '01:00 PM'],
        //     ['id' => 18, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '01:15 PM'],
        //     ['id' => 19, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '01:30 PM'],
        //     ['id' => 20, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '01:45 PM'],
        //     ['id' => 21, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '02:00 PM'],
        //     ['id' => 22, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '02:15 PM'],
        //     ['id' => 23, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '02:30 PM'],
        //     ['id' => 24, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '02:45 PM'],
        //     ['id' => 25, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '03:00 PM'],
        //     ['id' => 26, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '03:15 PM'],
        //     ['id' => 27, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '03:30 PM'],
        //     ['id' => 28, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '03:45 PM'],
        //     ['id' => 29, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '04:00 PM'],
        //     ['id' => 30, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '04:15 PM'],
        //     ['id' => 31, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '04:30 PM'],
        //     ['id' => 32, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '04:45 PM'],
        //     ['id' => 33, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '05:00 PM'],
        //     ['id' => 34, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '05:15 PM'],
        //     ['id' => 35, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '05:30 PM'],
        //     ['id' => 36, 'restaurant_id' => 1, 'type' => 'lunch', 'time' => '05:45 PM'],

        //     ['id' => 37, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '06:00 PM'],
        //     ['id' => 38, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '06:15 PM'],
        //     ['id' => 39, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '06:30 PM'],
        //     ['id' => 40, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '06:45 PM'],
        //     ['id' => 41, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '07:00 PM'],
        //     ['id' => 42, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '07:15 PM'],
        //     ['id' => 43, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '07:30 PM'],
        //     ['id' => 44, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '07:45 PM'],
        //     ['id' => 45, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '08:00 PM'],
        //     ['id' => 46, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '08:15 PM'],
        //     ['id' => 47, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '08:30 PM'],
        //     ['id' => 48, 'restaurant_id' => 1, 'type' => 'dinner', 'time' => '08:45 PM'],
        // ];
        // update_option('pinaka_pos_slots', serialize($slots));
        
        $reservation_date = $request->get_param('reservation_date');

        if ($reservation_date) {
            $reservation_date = sanitize_text_field($reservation_date);
        } else {
            $reservation_date = current_time('Y-m-d');  // fallback to today
        }
        // Validate reservation date format (optional)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reservation_date)) {
            return new WP_Error(
                'invalid_date',
                __('Invalid reservation date format. Use YYYY-MM-DD.', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        $stored = get_option('pinaka_pos_slots', []);
        $slots = maybe_unserialize($stored);
        if (empty($slots) || !is_array($slots)) {
            return new WP_Error(
                'no_slots_found',
                __('No time slots are configured.', 'pinaka-pos'),
                ['status' => 404]
            );
        }
        $today_date = current_time('Y-m-d');
        $current_time = current_time('H:i');

        $result = $slot_types = [];
        $restaurants = get_option('pinaka_pos_restaurants', []);

        $restaurants_by_id = [];
        foreach ($restaurants as $rest) {
            if (isset($rest['id'], $rest['name'])) {
                $restaurants_by_id[$rest['id']] = $rest['name'];
            }
        }
        
        if (empty($restaurants_by_id) || !is_array($restaurants_by_id)) {
            return new WP_Error(
                'no_restaurants_found',
                __('No Restaurants are found.', 'pinaka-pos'),
                ['status' => 404]
            );
        }
        foreach ($slots as $slot) 
        {
            if (!isset($slot['type'], $slot['time'], $slot['restaurant_id'])) continue;

            $restaurant_id = $slot['restaurant_id'];
            $slot_type = ucfirst($slot['type']);
            $slot_time_24 = date('H:i', strtotime($slot['time']));
      
            if ($reservation_date === $today_date) {
                // Reservation is for today,
                // slot active if slot time is later or equal to current time
                $is_active = strtotime($slot_time_24) >= strtotime($current_time);
            } elseif ($reservation_date > $today_date) {
                // Reservation is for a future date, all time slots considered active
                $is_active = true;
            } else {
                // Reservation is in the past, all slots inactive
                $is_active = false;
            }
            if (!isset($result[$restaurant_id])) 
            {
                $result[$restaurant_id] = [
                    'id' => $restaurant_id,
                    'name' => isset($restaurants_by_id[$restaurant_id]) ? $restaurants_by_id[$restaurant_id] : '',
                    'slots' => []
                ];
            }

            if (!isset($result[$restaurant_id]['slots'][$slot_type])) {
                $result[$restaurant_id]['slots'][$slot_type] = [];
            }
    
            $result[$restaurant_id]['slots'][$slot_type][] = [
                'id' => $slot['id'],
                'Time Slot' => $slot['time'],
                'is_active' => $is_active,
            ]; 
            $slot_types[$slot_type] = $slot_type;
        }
        foreach ($result as &$restaurant_data) {
            foreach ($restaurant_data['slots'] as &$slots_by_type) {
                usort($slots_by_type, function ($a, $b) {
                    return strtotime($a['Time Slot']) <=> strtotime($b['Time Slot']);
                });
            }
        }
        unset($restaurant_data, $slots_by_type);
        
        if(count($slot_types) > 0)
        {
            $slot_types = array_values($slot_types);
        }
        else
        {
            return new WP_Error(
                'no_slots_found',
                __('No slots are configured.', 'pinaka-pos'),
                ['status' => 404]
            );
        }
        return rest_ensure_response([
            'status' => 'success',
            'reservation_date' => date('Y-m-d', strtotime($reservation_date)),
            'current_time' => date('h:i A', strtotime($current_time)),
            'Meal' => $slot_types,
            'data' => $result,
        ]);
    }
}