<?php
if(!defined('ABSPATH')){
    exit;
}

class Pinaka_Settings_Api_Controller{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'settings';

    public function register_routes(){
        // register_rest_route(
        //     $this->namespace,
        //     '/' . $this->rest_base . '/general-settings',
        //     array(
        //         'methods'        => 'POST',
        //         'callback'       => array($this, 'pinaka_create_general_settings_rest'),
        //         'permission_callback' => array($this, 'check_user_role_permission'),
        //     )
        // );

        // register_rest_route(
        //     $this->namespace,
        //         '/' . $this->rest_base . '/device-settings',   
        //         array(
        //             'methods'             => 'POST',
        //             'callback'            => array($this, 'pinaka_create_device_settings_rest'),
        //             'permission_callback' => array($this, 'check_user_role_permission'), // or '__return_true'
        //         )
        // );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-general-settings',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'pinaka_get_general_settings_rest'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/save-general-settings',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'pinaka_create_general_settings_rest'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/edit-receipt-image',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'pinaka_edit_general_settings_rest'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/edit-profile-image',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'pinaka_edit_profile_image'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        // register_rest_route(
        //     $this->namespace,
        //     '/' . $this->rest_base . '/delete-receipt-image',
        //     [
        //         'methods'             => 'POST',
        //         'callback'            => [$this, 'pinaka_delete_general_settings_rest'],
        //         'permission_callback' => [$this, 'check_user_role_permission'],
        //     ]
        // );
    }

    // public function check_user_role_permission($request){
    //     $user_id = get_current_user_id();
    //     $user    = get_userdata($user_id);
    //     if($user == null){
    //         return new WP_Error('pinakapos_rest_cannot_view', esc_html__('sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_required_code()));
    //     }
    //     if(in_array('administrator', (array) $user->roles)){
    //         return true;
    //     }elseif(in_array('shop_manager',(array) $user->roles)){
    //         return true;
    //     }elseif(in_array('employee', (array) $user->roles)){
    //         return true;
    //     }else{
    //         return new WP_Error('pinakapos_rest_cannot_view', esc_html__('Sorry, you cannot give permission.', 'pinaka-pos'), array('status' => rest_authorization_required_code()));
    //     }
    // }
    // public function check_user_role_permission( $request ) {

	// 	$user_id = get_current_user_id();
	// 	$user    = get_userdata( $user_id );

	// 	if ( ! $user ) {
	// 		return new WP_Error(
	// 			'pinakapos_rest_cannot_view',
	// 			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
	// 			[ 'status' => rest_authorization_required_code() ]
	// 		);
	// 	}

	// 	// Get all registered roles
	// 	global $wp_roles;
	// 	if ( ! isset( $wp_roles ) ) {
	// 		$wp_roles = new WP_Roles();
	// 	}
	// 	// $all_roles = array_keys( $wp_roles->get_names() );
    //     // $allowed_roles = ['administrator', 'shop_manager'];
    //     foreach ($allowed_roles as $role) {
    //         if (in_array($role, (array) $user->roles)) {
    //             return true;
    //         }
    //     }
	// 	// foreach ( $user->roles as $role ) {
	// 	// 	if ( in_array( $role, $all_roles, true ) ) {
	// 	// 		return true;
	// 	// 	}
	// 	// }

	// 	return new WP_Error(
	// 		'pinakapos_rest_cannot_view',
	// 		esc_html__( 'Sorry, you dont have permission.', 'pinaka-pos' ),
	// 		[ 'status' => rest_authorization_required_code() ]
	// 	);
	// }
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
    public function pinaka_create_general_settings_rest(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Unauthorized. Please log in to update settings.'
            ], 401);
        }

        $data = $request->get_json_params();

        update_option('pinaka_header_text', sanitize_text_field($data['header_text'] ?? ''));
        update_option('pinaka_footer_text', sanitize_text_field($data['footer_text'] ?? ''));
        update_option('pinaka_print_settings', sanitize_text_field($data['print_settings'] ?? ''));

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        if (isset($_FILES['receipt_logo']) && !empty($_FILES['receipt_logo']['name'])) {
            // Load required WP file handling libraries
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $file = $_FILES['receipt_logo'];
            $max_size = 5 * 1024 * 1024; // 5MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg']; // broader type support

            if ($file['size'] > $max_size) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Receipt logo exceeds the maximum size of 5MB.'
                ], 400);
            }

            $file_type = wp_check_filetype($file['name']);
            if (empty($file_type['type']) || !in_array($file_type['type'], $allowed_types, true)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Invalid receipt logo type. Only JPG and PNG formats are allowed.'
                ], 400);
            }

            $uploaded = media_handle_upload('receipt_logo', 0);

            if (is_wp_error($uploaded)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Error uploading receipt logo: ' . $uploaded->get_error_message()
                ], 400);
            }

            $uploaded_url = wp_get_attachment_url($uploaded);
            update_option('pinaka_receipt_logo', esc_url_raw($uploaded_url));

        } 
        elseif (!empty($data['receipt_logo_url'])) {
            $logo_url = esc_url_raw($data['receipt_logo_url']);
            $path_info = pathinfo(parse_url($logo_url, PHP_URL_PATH));
            $ext = strtolower($path_info['extension'] ?? '');

            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return new WP_REST_Response([
                    'success' => false,
                    'message' => 'Invalid receipt logo URL. Only JPG and PNG formats are allowed.'
                ], 400);
            }

            update_option('pinaka_receipt_logo', $logo_url);
        }
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error(['message' => 'You must be logged in to view this page.'], 401);
            return;
        }

        $current_user = get_user_by('id', $current_user_id);
        if (!$current_user) {
            wp_send_json_error(['message' => 'User not found.'], 404);
            return;
        }

        // Basic user data
        $first_name   = $current_user->first_name ?? '';
        $last_name    = $current_user->last_name ?? '';
        $email        = $current_user->user_email ?? '';

        // Phone number fallback
        $phone_number = get_user_meta($current_user_id, 'phone_number', true);
        if (empty($phone_number)) 
        {
            $phone_number = get_user_meta($current_user_id, 'billing_phone', true);
        }

        // Other user-specific data
        $user_device_id = get_user_meta($current_user_id, 'user_device_id', true) ?: '';
        $gstin          = get_option("pinaka_pos_gstin");

        // Profile picture
        $profilepicturepath    = get_user_meta($current_user_id, 'profilepicture', true);

        // Global site options (e.g., receipt logo or restaurant name)
        $receipt_logo = get_option('pinaka_receipt_logo');
        $company_name    = get_option("pinaka_pos_name");

        $response_data = [
            'user_id'        => $current_user_id,
            'full_name'     => $first_name.''.$last_name,
            'email'          => $email,
            'phone_number'   => $phone_number ?: null,
            'user_device_id' => $user_device_id ?: null,
            'gstin'          => $gstin ?: null,
            'company_name'   => $company_name ?: null,
            'profile_url'    => $profilepicturepath ?: null,
            'header_text'    => get_option('pinaka_header_text'),
            'footer_text'    => get_option('pinaka_footer_text'),
            'print_settings' => get_option('pinaka_print_settings'),
            'receipt_logo'   => get_option('pinaka_receipt_logo'),
        ];

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data'    => $response_data,
        ], 200);
    }
    public function pinaka_edit_general_settings_rest(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        $logo_url = $data['receipt_logo_url'];
        update_option('pinaka_receipt_logo', $logo_url);
        return new WP_REST_Response([
            'success' => true,
            'message' => 'logo updated successfully.',
            'logo_url'    => $logo_url,
        ], 200);
    }
    public function pinaka_edit_profile_image(WP_REST_Request $request)
    {
        $data = $request->get_json_params();
        $profile_image_url = $data['profile_image_url'];
        $current_user_id = get_current_user_id();
        update_user_meta($current_user_id, 'profilepicture', $profile_image_url);
        return new WP_REST_Response([
            'success' => true,
            'message' => 'profile updated successfully.',
            'logo_url'    => $profile_image_url,
        ], 200);
    }
    public function pinaka_get_general_settings_rest($request)
    {
        $user_id = $request->get_param('user_id') ?? get_current_user_id();
        $current_user_id = $user_id;
        if (!$current_user_id) {
            wp_send_json_error(['message' => 'You must be logged in to view this page.'], 401);
            return;
        }

        $current_user = get_user_by('id', $current_user_id);
        if (!$current_user) {
            wp_send_json_error(['message' => 'User not found.'], 404);
            return;
        }

        // Basic user data
        $first_name   = $current_user->first_name ?? '';
        $last_name    = $current_user->last_name ?? '';
        $email        = $current_user->user_email ?? '';

        // Phone number fallback
        $phone_number = get_user_meta($current_user_id, 'phone_number', true);
        if (empty($phone_number)) 
        {
            $phone_number = get_user_meta($current_user_id, 'billing_phone', true);
        }

        // Other user-specific data
        $user_device_id = get_user_meta($current_user_id, 'user_device_id', true) ?: '';
        $gstin          = get_user_meta($current_user_id, 'user_gstin', true) ?: '';

        // Profile picture
        $profilepicturepath    = get_user_meta($current_user_id, 'profilepicture', true);

        // Global site options (e.g., receipt logo or restaurant name)
        $receipt_logo = get_option('pinaka_receipt_logo');
        $company_name    = get_option("pinaka_pos_name");

        // Response array
        $data = [
            'user_id'        => intval($current_user_id),
            'full_name'     => $first_name.''.$last_name,
            'email'          => $email,
            'phone_number'   => $phone_number ?: null,
            'user_device_id' => $user_device_id ?: null,
            'gstin'          => get_option("pinaka_pos_gstin"),
            'company_name'   => $company_name ?: 'Alekya Tech Solutions',
            'profile_url'    => $profilepicturepath,
            'header_text'    => get_option('pinaka_header_text'),
            'footer_text'    => get_option('pinaka_footer_text'),
            'print_settings' => get_option('pinaka_print_settings'),
            'receipt_logo'   => get_option('pinaka_receipt_logo'),
        ];

        // Return success response
        wp_send_json_success($data);
    }

    public function pinaka_create_device_settings_rest(WP_REST_Request $request) {
        $data = $request->get_json_params();
        if (empty($data)) {
            $data = $request->get_body_params();
        }

        $settings = [
            'kot_printer'     => sanitize_text_field($data['kot_printer'] ?? ''),
            'receipt_printer' => sanitize_text_field($data['receipt_printer'] ?? ''),
            'paper_size'      => sanitize_text_field($data['paper_size'] ?? ''),
            'connection_type' => sanitize_text_field($data['connection_type'] ?? ''),
            'kitchen_printer' => sanitize_text_field($data['kitchen_printer'] ?? ''),
            'bar_printer'     => sanitize_text_field($data['bar_printer'] ?? ''),
        ];
        update_option('pinaka_device_settings', $settings);
        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => 'Device settings updated successfully',
                'data'    => $settings
            ),
            200
        );
    }
}