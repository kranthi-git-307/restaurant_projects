<?php
/**
 * Setup JWT-Auth.
 *
 * @package pinaka-pos
 */

use Firebase\JWT\JWT;

/**
 * The public-facing functionality of the plugin.
 */
class Pinaka_Pos_Auth {
	/**
	 * The namespace to add to the api calls.
	 *
	 * @var string The namespace to add to the api call
	 */
	private $namespace;

	/**
	 * Store errors to display if the JWT is wrong
	 *
	 * @var WP_REST_Response
	 */
	private $jwt_error = null;

	/**
	 * Collection of translate-able messages.
	 *
	 * @var array
	 */
	private $messages = array();

	/**
	 * The REST API slug.
	 *
	 * @var string
	 */
	private $rest_api_slug = 'wp-json';

	/**
	 * Setup action & filter hooks.
	 */
	public function __construct() {

		$this->namespace = 'pinaka-restaurant-pos/v1';
		$this->messages  = array(
			'jwt_auth_no_auth_header'  => esc_html__( 'Authorization header not found.', 'pinaka-pos' ),
			'jwt_auth_bad_auth_header' => esc_html__( 'Authorization header malformed.', 'pinaka-pos' ),
		);
	}

	/**
	 * Add the endpoints to the API
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->namespace,
			'token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_token' ),
				'permission_callback' => '__return_true',
			)
		);
        
        register_rest_route(
        	$this->namespace,
        	'token/logout-by-id',
        	array(
        		'methods'             => 'POST',
        		'callback'            => array( $this, 'logout_by_user_id' ),
        		'permission_callback' => '__return_true', // change if you want restrictions
        		'args'                => array(
        			'emp_login_pin' => array(
        				'required' => true,
        				'type'     => 'integer',
        			),
        		),
        	)
        );
        
		// register_rest_route(
		// 	$this->namespace,
		// 	'logout',
		// 	array(
		// 		'methods'             => 'POST',
		// 		'callback'            => array( $this, 'myplugin_process_logout' ),
		// 		'permission_callback' => '__return_true',
		// 	)
		// );

		register_rest_route(
			$this->namespace,
			'emp-order-pin-validation',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'emp_order_pin_validation' ),
				'permission_callback' => '__return_true',
			)
		);


        register_rest_route($this->namespace, 
            '/logout', 
            [
                'methods'  => 'POST',
                'callback' => [$this, 'logout_user'],
                'permission_callback' => '__return_true', // You can secure it with JWT middleware if needed
            ]
        );

        register_rest_route(
			$this->namespace,
			'emp-order-pin-logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'emp_order_pin_logout' ),
				'permission_callback' => '__return_true',
			)
		);
		
		register_rest_route(
            'pinaka-auth/v1',
            '/email-login',
            [
                'methods' => 'POST',
                'callback' => [$this, 'get_token_by_email'],
                'permission_callback' => '__return_true',
            ]
        );
        
        register_rest_route(
            'pinaka-auth/v1',
            '/logout',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'logout' ],
                'permission_callback' => '__return_true',
            ]
        );
	}
	
	public function get_token_by_email( WP_REST_Request $request ) {

        $secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;
    
        $email    = sanitize_email( $request->get_param( 'email' ) );
        $password = $request->get_param( 'password' );
    
        if ( ! $secret_key ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'JWT is not configured properly.'
                ],
                403
            );
        }
    
        if ( empty( $email ) || empty( $password ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Email and password are required.'
                ],
                400
            );
        }
    
        // Get user
        $user = get_user_by( 'email', $email );
    
        if ( ! $user ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ],
                403
            );
        }
    
        // Verify password
        if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ],
                403
            );
        }
    
        // Restaurant ID
        $restaurant_id = get_user_meta( $user->ID, 'user_restaurant_id', true );
    
        $restaurants = get_option( 'pinaka_pos_restaurants', [] );
    
        $restaurant_details = array_values(
            array_filter(
                $restaurants,
                function ( $restaurant ) use ( $restaurant_id ) {
                    return $restaurant['id'] == $restaurant_id;
                }
            )
        );
    
        if ( empty( $restaurant_details ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Restaurant details not found.'
                ],
                404
            );
        }
    
        $restaurant = $restaurant_details[0];
    
        $restaurant_data = [
            'id'   => $restaurant['id'],
            'name' => $restaurant['name']
        ];
    
        // Generate token
        $response = $this->generate_token(
            $restaurant_data,
            $user,
            false
        );
    
        // If token generation failed
        if ( is_wp_error( $response ) ) {
            return $response;
        }
    
        // Get response data
        if ( $response instanceof WP_REST_Response ) {
            $response_data = $response->get_data();
        } else {
            $response_data = $response;
        }
    
        // Store generated token
        if ( ! empty( $response_data['data']['token'] ) ) {
    
            update_user_meta(
                $user->ID,
                'current_jwt_token',
                $response_data['data']['token']
            );
    
            update_user_meta(
                $user->ID,
                'token_created_at',
                current_time( 'timestamp' )
            );
        }
    
        return $response;
    }
    
    public function logout( WP_REST_Request $request ) {
    
        $email = sanitize_email( $request->get_param( 'email' ) );
    
        if ( empty( $email ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Email is required.'
                ],
                400
            );
        }
    
        $user = get_user_by( 'email', $email );
    
        if ( ! $user ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'User not found.'
                ],
                404
            );
        }
    
        delete_user_meta( $user->ID, 'current_jwt_token' );
        delete_user_meta( $user->ID, 'token_created_at' );
    
        return new WP_REST_Response(
            [
                'success'    => true,
                'statusCode' => 200,
                'code'       => 'jwt_auth_logout_success',
                'message'    => 'Logged out successfully.',
                'data'       => [],
            ],
            200
        );
    }

	/**
	 * Add CORs suppot to the request.
	 */
	public function add_cors_support() {
		$enable_cors = defined( 'MAGNI_JWT_AUTH_CORS_ENABLE' ) ? MAGNI_JWT_AUTH_CORS_ENABLE : false;

		if ( $enable_cors ) {
			$headers = apply_filters( 'jwt_auth_cors_allow_headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization' );

			header( sprintf( 'Access-Control-Allow-Headers: %s', $headers ) );
		}
	}

	/**
	 * Authenticate user either via wp_authenticate or custom auth (e.g: OTP).
	 *
	 * @param string $username The username.
	 * @param string $password The password.
	 * @param mixed  $custom_auth The custom auth data (if any).
	 *
	 * @return WP_User|WP_Error $user Returns WP_User object if success, or WP_Error if failed.
	 */
	public function authenticate_user( $username, $password, $custom_auth = '' ) {

		// If using custom authentication.
		if ( $custom_auth ) {
			$custom_auth_error = new WP_Error( 'jwt_auth_custom_auth_failed', esc_html__( 'Custom authentication failed.', 'pinaka-pos' ) );

			/**
			 * Do your own custom authentication and return the result through this filter.
			 * It should return either WP_User or WP_Error.
			 */
			$user = apply_filters( 'jwt_auth_do_custom_auth', $custom_auth_error, $username, $password, $custom_auth );
		} else {
			$user = wp_authenticate( $username, $password );
		}

		return $user;
	}

	/**
	 * Get token by sending POST request to pinaka-restaurant-pos/v1/token with a 6-digit PIN.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	public function get_token(WP_REST_Request $request) {

		$secret_key = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : false;
		$emp_login_pin   = $request->get_param('emp_login_pin'); // Get PIN from request
		// Check if secret key exists
		if (!$secret_key) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_config',
					'message'    => esc_html__('JWT is not configured properly.', 'pinaka-pos'),
					'data'       => array(),
				),
				403
			);
		}

		// Validate PIN (must be exactly 6 digits)
		if (!preg_match('/^\d{6}$/', $emp_login_pin)) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'invalid_pin_format',
					'message'    => esc_html__('PIN must be exactly 6 digits.', 'pinaka-pos'),
					'data'       => array(),
				),
				400
			);
		}
        

		// Authenticate user by PIN
		$user = $this->authenticate_by_pin($emp_login_pin);
// 		$existing_token = get_user_meta($user->ID, 'current_jwt_token', true);
// 		$token_created_at = get_user_meta($user->ID, 'token_created_at', true);

// 		if ( $existing_token && $token_created_at ) {
// 			$expire = $token_created_at + ( DAY_IN_SECONDS * 30 );
// 			if ( $expire > time() ) {
// 				return new WP_REST_Response(
// 					array(
// 						'success'    => false,
// 						'statusCode' => 403,
// 						'code'       => 'session_active_elsewhere',
// 						'message'    => __('Your session is already active on another console. Please logout first to continue.', 'pinaka-pos'),
// 						'data'       => array(),
// 					),
// 					403
// 				);
// 			}
// 		}
		// If authentication fails, return error
		if (is_wp_error($user)) {
			$error_code = $user->get_error_code();

			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => $error_code,
					'message'    => wp_strip_all_tags($user->get_error_message($error_code)),
					'data'       => array(),
				),
				403
			);
		}
		$restaurant_id = get_user_meta($user->ID, 'user_restaurant_id', true);
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$restaurant_ids = array_column($restaurants, 'id');

		if (!in_array($restaurant_id, $restaurant_ids)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Invalid restaurant ID.',
			], 404);
		}
		
		// Find the restaurant details
		$restaurant_details = array_values(array_filter($restaurants, function ($restaurant) use ($restaurant_id) {
			return $restaurant['id'] == $restaurant_id;
		}));

		if (empty($restaurant_details)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Restaurant details not found.',
			], 404);
		}

		// Extract only required fields
		$restaurant = $restaurant_details[0];
		$restaurant_data = [
			'id'      => $restaurant['id'],
			'name'    => $restaurant['name'] ?? '',
		];

		// Valid PIN, generate token
		return $this->generate_token($restaurant_data, $user, false);
	}

    /**
	 * Logout.
	 *
	 */
    public function logout_user(WP_REST_Request $request) {
        $token = $request->get_header('Authorization');

        if (!$token) {
            return new WP_Error('no_token', __('Authorization header not found', 'pinaka-pos'), ['status' => 401]);
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Store the token in a blacklist (could be a transient, DB, or cache)
        $blacklist = get_option('pinaka_token_blacklist', []);
        $blacklist[$token] = time(); // You could store expiration too
        update_option('pinaka_token_blacklist', $blacklist);

        return rest_ensure_response([
            'success' => true,
            'message' => __('Logged out successfully.', 'pinaka-pos')
        ]);
    }


	/**
	 * Authenticate user using PIN code.
	 *
	 * @param string $emp_login_pin The PIN code.
	 * @return WP_User|WP_Error User object if authenticated, otherwise error.
	 */
	private function authenticate_by_pin($emp_login_pin) {
		// Search for user with matching PIN
		$users = get_users([
			'meta_key'   => 'emp_login_pin',
			'meta_value' => $emp_login_pin,
			'number'     => 1,
		]);

		if (empty($users)) {
			return new WP_Error('invalid_pin', __('Invalid PIN or user not found.', 'pinaka-pos'));
		}

		$user = $users[0];

		// Check expiration date
		$expiration_date = get_option('pinaka_license_expiration');
		
		if ($expiration_date && strtotime($expiration_date) < time()) {
			return new WP_Error('license_expired', __('Your POS license has expired. Please renew to continue.', 'pinaka-pos'));
		}

		return $user;
	}


	/**
	 * Generate token
	 *
	 * @param WP_User $user The WP_User object.
	 * @param bool    $return_raw Whether or not to return as raw token string.
	 *
	 * @return WP_REST_Response|string Return as raw token string or as a formatted WP_REST_Response.
	 */
	public function generate_token( $restaurant_data, $user, $return_raw = true ) 
	{
		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;
		$issued_at  = time();
		$not_before = $issued_at;
		$not_before = apply_filters( 'jwt_auth_not_before', $not_before, $issued_at );
		$expire     = $issued_at + ( DAY_IN_SECONDS * 30 );
		$expire     = apply_filters( 'jwt_auth_expire', $expire, $issued_at );

		$payload = array(
			'iss'  => $this->get_iss(),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->ID,
				),
			),
		);

		$alg = $this->get_alg();

		// Let the user modify the token data before the sign.
		$token = JWT::encode( apply_filters( 'jwt_auth_payload', $payload, $user ), $secret_key, $alg );

		// If return as raw token string.
		if ( $return_raw ) {
			return $token;
		}
		//update_user_meta( $user->ID, '_jwt_token', $token );
        $saved_permissions = get_option('pinaka_roles_permissions', []);
		// echo json_encode($saved_permissions);
		// die;
        if("administrator" == $user->roles[0]){
            $capabulities = $saved_permissions['admin'];
        }else{
            $capabulities = $saved_permissions[$user->roles[0]];
        }
		$permissions = [];

		if ( ! empty( $capabulities ) && is_array( $capabulities ) ) 
		{
			foreach ( $capabulities as $key => $val ) 
			{
				if ( $key === 'canDefaultLayout' ) {
					$permissions[$key] = $val;
				} else {
					// Convert boolean or numeric truthy values to true/false
					if ( $val === true || $val === '1' || $val === 1 ) {
						$permissions[$key] = true;
					} 
					else {
						$permissions[$key] = false;
					}
				}
			}
		}
		$currency_symbol = get_woocommerce_currency_symbol();
		// The token is signed, now create object with basic info of the user.
		$print_settings = get_option( 'pinaka_print_settings', array() );

        if ( ! is_array( $print_settings ) ) {
            $print_settings = array( $print_settings );
        }
        
        // $print_settings = implode( ',', array_filter( $print_settings ) );
		$response = array(
			'success'    => true,
			'statusCode' => 200,
			'code'       => 'jwt_auth_valid_credential',
			'message'    => __( 'Credential is valid', 'pinaka-pos' ),
			'data'       => array(
				'token'       => $token,
				'id'          => $user->ID,
				'email'       => $user->user_email,
				'nicename'    => $user->user_nicename,
				'firstName'   => $user->first_name,
				'lastName'    => $user->last_name,
				'print_settings'  => $print_settings,
				'displayName' => $user->display_name,
				'currency_symbol' => html_entity_decode( $currency_symbol, ENT_QUOTES, 'UTF-8' ),
				'role'        => $user->roles[0],
				'restaurant_id' => $restaurant_data['id'],
				'restaurant_name' => $restaurant_data['name'],
				
				// 'avatar'      => get_avatar_url( $user->ID ),
				'avatar'      => get_user_meta($user->ID, 'profilepicture', true) ? get_user_meta($user->ID, 'profilepicture', true) : get_avatar_url( $user->ID ),
                // 'permissions' => $capabulities,
				'permissions' => $permissions,
			),
		);
// 		update_user_meta($user->ID, 'current_jwt_token', $token);
// 		update_user_meta($user->ID, 'token_created_at', time());
		// Let the user modify the data before send it back.
		return apply_filters( 'jwt_auth_valid_credential_response', $response, $user );
	}
	// public function myplugin_process_logout($request) 
	// {
	// 	$params = $request->get_json_params();
	// 	$pin = isset($params['emp_login_pin']) ? intval($params['emp_login_pin']) : '';
	// 	if (empty($pin)) {
	// 		return new WP_Error('missing_data', 'PIN or logout time missing', ['status' => 400]);
	// 	}
	// 	$user_id = get_current_user_id();
	// 	$current_pin = get_user_meta($user_id, 'emp_login_pin', true);

	// 	if ($current_pin != $pin) {
	// 		return new WP_Error('invalid_pin', 'Invalid PIN entered. Please try again.', array('status' => 401));
	// 	}
	// 	$logout_time = current_time('H:i');
	// 	$log_day = current_time('Y-m-d');
	// 	$logout_user = get_current_user_id();
	// 	$posts = get_posts([
	// 		'post_type'   => 'shifts',
	// 		'numberposts' => 1,
	// 		'meta_query'  => [
	// 			'relation' => 'AND',
	// 			[
	// 				'key'     => '_shift_date',
	// 				'value'   => $log_day,
	// 				'compare' => '='
	// 			],
	// 			[
	// 				'key'     => '_shift_start_time',
	// 				'value'   => '',
	// 				'compare' => '!='
	// 			],
	// 			[
	// 				'key'     => '_shift_end_time',
	// 				'value'   => '',
	// 				'compare' => '='
	// 			]
	// 		]
	// 	]);
	// 	if (!empty($posts)) 
	// 	{	
	// 		$post_id = $posts[0]->ID;
	// 		update_post_meta($post_id, '_shift_end_time', $logout_time);
	// 		return rest_ensure_response([
	// 			'status' => 'success',
	// 			'message' => 'Logout time updated successfully',
	// 			'post_id' => $post_id,
	// 			'logout_time' => $logout_time
	// 		]);
	// 	}
	// 	else
	// 	{
	// 		return rest_ensure_response([
	// 			'status'  => 'false',
	// 			'message' => 'No open shift found for today.'
	// 		]);
	// 	}
	// }

	/**
	 * Get the token issuer.
	 *
	 * @return string The token issuer (iss).
	 */
	public function get_iss() {
		return apply_filters( 'jwt_auth_iss', get_bloginfo( 'url' ) );
	}

	/**
	 * Get the supported jwt auth signing algorithm.
	 *
	 * @see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
	 *
	 * @return string $alg
	 */
	public function get_alg() {
		return apply_filters( 'jwt_auth_alg', 'HS256' );
	}

	/**
	 * Determine if given response is an error response.
	 *
	 * @param mixed $response The response.
	 * @return boolean
	 */
	public function is_error_response( $response ) {
		if ( ! empty( $response ) && property_exists( $response, 'data' ) && is_array( $response->data ) ) {
			if ( ! isset( $response->data['success'] ) || ! $response->data['success'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Main validation function, this function try to get the Autentication
	 * headers and decoded.
	 *
	 * @param bool $return_response Either to return full WP_REST_Response or to return the payload only.
	 *
	 * @return WP_REST_Response | Array Returns WP_REST_Response or token's $payload.
	 */
	public function validate_token( $return_response = true ) {
		/**
		 * Looking for the HTTP_AUTHORIZATION header, if not present just
		 * return the user.
		 */
		$headerkey = apply_filters( 'jwt_auth_authorization_header', 'HTTP_AUTHORIZATION' );
		$auth      = isset( $_SERVER[ $headerkey ] ) ? $_SERVER[ $headerkey ] : false;

		// Double check for different auth header string (server dependent).
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
		}

		if ( ! $auth ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_no_auth_header',
					'message'    => $this->messages['jwt_auth_no_auth_header'],
					'data'       => array(),
				)
			);
		}

		/**
		 * The HTTP_AUTHORIZATION is present, verify the format.
		 * If the format is wrong return the user.
		 */
		list($token) = sscanf( $auth, 'Bearer %s' );

		if ( ! $token ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_auth_header',
					'message'    => $this->messages['jwt_auth_bad_auth_header'],
					'data'       => array(),
				)
			);
		}

		// Get the Secret Key.
		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;

		if ( ! $secret_key ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_config',
					'message'    => esc_html__( 'JWT is not configured properly.', 'pinaka-pos' ),
					'data'       => array(),
				),
				403
			);
		}

		// Try to decode the token.
		try {
			$alg     = $this->get_alg();
			$payload = JWT::decode( $token, $secret_key, array( $alg ) );

			// The Token is decoded now validate the iss.
			if ( $payload->iss !== $this->get_iss() ) {
				// The iss do not match, return error.
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_bad_iss',
						'message'    => esc_html__( 'The iss do not match with this server.', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// Check the user id existence in the token.
			if ( ! isset( $payload->data->user->id ) ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_bad_request',
						'message'    => esc_html__( 'User ID not found in the token.', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// So far so good, check if the given user id exists in db.
			$user = get_user_by( 'id', $payload->data->user->id );

            //$stored_token = get_user_meta( $user->ID, '_jwt_token', true );

            //if ( ! empty( $stored_token ) && $token !== $stored_token ) {
                //return new WP_REST_Response(
                    //array(
                        //'success'    => false,
                        //'statusCode' => 403,
                        //'code'       => 'jwt_auth_token_replaced',
                        //'message'    => esc_html__( 'Your token has been replaced.', 'pinaka-pos' ),
                        //'data'       => array(),
                    //),
				    //403
			    //);
            //}
			if ( ! $user ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_user_not_found',
						'message'    => esc_html__( "User doesn't exist", 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			} elseif ( in_array( 'administrator', $user->roles ) ) {
				if ( isset( $_REQUEST['user_id'] ) ) {
					$payload->data->user->id = $_REQUEST['user_id'];
				}
			}

			// Check extra condition if exists.
			$failed_msg = apply_filters( 'jwt_auth_extra_token_check', '', $user, $token, $payload );

			if ( ! empty( $failed_msg ) ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_obsolete_token',
						'message'    => esc_html__( 'Token is obsolete', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// Everything looks good, return the payload if $return_response is set to false.
			if ( ! $return_response ) {
				return $payload;
			}

			$response = array(
				'success'    => true,
				'statusCode' => 200,
				'code'       => 'jwt_auth_valid_token',
				'message'    => esc_html__( 'Token is valid', 'pinaka-pos' ),
				'data'       => array(),
			);

			$response = apply_filters( 'jwt_auth_valid_token_response', $response, $user, $token, $payload );

			// Otherwise, return success response.
			return new WP_REST_Response( $response );
		} catch ( Exception $e ) {
			// Something is wrong when trying to decode the token, return error response.
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_invalid_token',
					'message'    => $e->getMessage(),
					'data'       => array(),
				),
				403
			);
		}
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the token sent.
	 *
	 * @param int|bool $user_id User ID if one has been determined, false otherwise.
	 * @return int|bool User ID if one has been determined, false otherwise.
	 */
	public function determine_current_user( $user_id ) {
		/**
		 * This hook only should run on the REST API requests to determine
		 * if the user in the Token (if any) is valid, for any other
		 * normal call ex. wp-admin/.* return the user.
		 *
		 * @since 1.2.3
		 */
		$request_uri = $_SERVER['REQUEST_URI'];

		$this->rest_api_slug = get_option( 'permalink_structure' ) ? rest_get_url_prefix() : '?rest_route=/';

		$valid_api_uri = strpos( $request_uri, $this->rest_api_slug );

		if ( ! $valid_api_uri ) {
			return $user_id;
		}

		/**
		 * If the request URI is for validate the token don't do anything,
		 * This avoid double calls to the validate_token function.
		 */
		$validate_uri = strpos( $request_uri, 'token/validate' );

		if ( $validate_uri > 0 ) {
			return $user_id;
		}

		$payload = $this->validate_token( false );

		// If $payload is an error response, then return the default $user_id.
		if ( $this->is_error_response( $payload ) ) {
			if ( 'jwt_auth_no_auth_header' === $payload->data['code'] ||
				'jwt_auth_bad_auth_header' === $payload->data['code']
			) {
				$rest_api_slug = home_url( '/' . $this->rest_api_slug, 'relative' );

				if ( $rest_api_slug . '/pinaka-restaurant-pos/v1/token' !== $request_uri ) {
					// Whitelist some endpoints by default (without trailing * char).
					$default_whitelist = array(
						// // WooCommerce namespace.
						// $rest_api_slug . '/wc/',
						// $rest_api_slug . '/wc-admin/',
						// $rest_api_slug . '/wc-auth/',
						// $rest_api_slug . '/wc-analytics/',

						// // WordPress namespace.
						// $rest_api_slug . '/wp/v2/',
						// $rest_api_slug . '/oembed/',
					);

					// Well, we let you adjust this default whitelist :).
					$default_whitelist = apply_filters( 'jwt_auth_default_whitelist', $default_whitelist );

					$is_ignored    = true;
					$pinaka_pos_url = $rest_api_slug . '/pinaka-restaurant-pos/v1';
					if ( false !== stripos( $request_uri, $pinaka_pos_url ) ) {
						$is_ignored = false;
					}

					foreach ( $default_whitelist as $endpoint ) {
						if ( false !== stripos( $request_uri, $endpoint ) ) {
							$is_ignored = true;

							break;
						}
					}

					if ( ! $is_ignored ) {
						if ( ! $this->is_whitelisted() ) {
							$this->jwt_error = $payload;
						}
					}
				}
			} else {
				$this->jwt_error = $payload;
			}

			return $user_id;
		}
		// Everything is ok here, return the user ID stored in the token.
		return $payload->data->user->id;
	}

	/**
	 * Check whether or not current endpoint is whitelisted.
	 *
	 * @return bool
	 */
	public function is_whitelisted() {
		$whitelist = apply_filters( 'jwt_auth_whitelist', array() );

		if ( empty( $whitelist ) || ! is_array( $whitelist ) ) {
			return false;
		}

		$request_uri    = $_SERVER['REQUEST_URI'];
		$request_method = $_SERVER['REQUEST_METHOD'];

		$prefix      = get_option( 'permalink_structure' ) ? rest_get_url_prefix() : '?rest_route=/';
		$split       = explode( $prefix, $request_uri );
		$request_uri = '/' . $prefix . ( ( count( $split ) > 1 ) ? $split[1] : $split[0] );

		// Only use string before "?" sign if permalink is enabled.
		if ( get_option( 'permalink_structure' ) && false !== stripos( $request_uri, '?' ) ) {
			$split       = explode( '?', $request_uri );
			$request_uri = $split[0];
		}

		// Let's remove trailingslash for easier checking.
		$request_uri = untrailingslashit( $request_uri );

		foreach ( $whitelist as $endpoint ) {
			if ( is_array( $endpoint ) ) {
				$method = $endpoint['method'];
				$path   = $endpoint['path'];
			} else {
				$method = null;
				$path   = $endpoint;
			}
			// If the endpoint doesn't contain * sign.
			if ( false === stripos( $path, '*' ) ) {
				$path = untrailingslashit( $path );

				if ( $path === $request_uri && ( ! isset( $method ) || $method === $request_method ) ) {
					return true;
				}
			} else {
				$regex = '/' . str_replace( '/', '\/', $path ) . '/';

				if ( preg_match( $regex, $request_uri ) && ( ! isset( $method ) || $method === $request_method ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Filter to hook the rest_pre_dispatch, if there is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param mixed           $result Can be anything a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server Server instance.
	 * @param WP_REST_Request $request The request.
	 *
	 * @return mixed $result
	 */
	public function rest_pre_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {

		if ( null !== $this->jwt_error ) {
			$data   = $this->jwt_error->data;
			$method = $request->get_method();
			if ( $method == 'OPTIONS' ) {
				// only for cors request...
				return $result;
			} else {
				return new WP_REST_Response(
					$data,
					403
				);
			}
		}

		if ( empty( $result ) ) {
			return $result;
		}

		return $result;
	}

	public function emp_order_pin_validation($request){
		if (!isset($request['emp_order_pin'])) {
			return new WP_Error('invalid_pin', __('PIN is mandatory.', 'pinaka-pos'));
		}
		$emp_order_pin   = $request->get_param('emp_order_pin'); // Get PIN from request
		// Check if secret key exists
		
		// Validate PIN (must be exactly 6 digits)
		if (!preg_match('/^\d{4}$/', $emp_order_pin)) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'invalid_pin_format',
					'message'    => esc_html__('PIN must be exactly 4 digits.', 'pinaka-pos'),
					'data'       => array(),
				),
				400
			);
		}
		
		// Search for user with matching PIN
		$users = get_users([
			'meta_key'   => 'user_login_pin',
			'meta_value' => $emp_order_pin,
			'number'     => 1,
		]);
		
		if (empty($users)) {
			return new WP_Error('invalid_pin', __('Invalid PIN or user not found.', 'pinaka-pos'));
		}

		$user = $users[0];
		$target_roles = ['captain', 'manager', 'chef', 'employee'];
		$user_roles = (array) $user->roles;
		$has_valid_role = array_intersect($target_roles, $user_roles);

		if (empty($has_valid_role)) {
			return new WP_Error(
				'unauthorized_role',
				__('You are not authorized to take orders. Your role is restricted.', 'pinaka-pos'),
				['success' => false, 'status' => 403]
			);
		}
		// Get today's date
		$today = current_time('Y-m-d');

		// Fetch today's open shift
		$open_shift = get_posts([
			'post_type'      => 'shifts',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => '_shift_date',
					'value'   => $today,
					'compare' => '=',
				],
				[
					'key'     => '_shift_status',
					'value'   => 'open',
					'compare' => '=',
				],
			],
			'fields' => 'ids',
		]);

		if (!empty($open_shift)) 
		{
			$shift_id = $open_shift[0];
			
			$all_users = get_users([
				'role__in' => $target_roles,
				'number'   => -1,
				'fields'   => 'ID',
			]);
			$pre_employees = get_post_meta($shift_id, '_shift_managers', true);
			$absent_employees = get_post_meta($shift_id, '_shift_absent_emp', true);
			if (!is_array($absent_employees)) {
				$absent_employees = [];
			}

			if (in_array($user->ID, $absent_employees)) {
				return new WP_Error('user_absent', __('You are marked as absent for this shift and cannot take orders.', 'pinaka-pos'), ['success'    => false,'status' => 403]);
			}
			$filtered_users = array_filter($all_users, function($user_id) use ($pre_employees, $absent_employees) {
				return !in_array($user_id, $pre_employees) && !in_array($user_id, $absent_employees);
			});
			if (in_array($user->ID, $filtered_users)) {
				return new WP_Error('user_invalid_shift', __('You are neither present nor absent for this shift so you cannot take orders.', 'pinaka-pos'), ['success'    => false,'status' => 403]);
			}
		}
		
		$saved_permissions = get_option('pinaka_roles_permissions', []);
        if("administrator" == $user->roles[0]){
            $capabulities = $saved_permissions['admin'];
        }else{
            $capabulities = $saved_permissions[$user->roles[0]];
        }
		$permissions = [];

		if ( ! empty( $capabulities ) && is_array( $capabulities ) ) 
		{
			foreach ( $capabulities as $key => $val ) 
			{
				if ( $key === 'canDefaultLayout' ) {
					$permissions[$key] = $val;
				} else {
					// Convert boolean or numeric truthy values to true/false
					if ( $val === true || $val === '1' || $val === 1 ) {
						$permissions[$key] = true;
					} 
					else {
						$permissions[$key] = false;
					}
				}
			}
		}
		if($user){
			$response = array(
				'success'    => true,
				'statusCode' => 200,
				'message'    => __( 'Credential is valid', 'pinaka-pos' ),
				'data'       => array(
					'id'          => $user->ID,
					'email'       => $user->user_email,
					'nicename'    => $user->user_nicename,
					'firstName'   => $user->first_name,
					'lastName'    => $user->last_name,
					'displayName' => $user->display_name,
					'role'        => $user->roles[0],
					'restaurant_id' => $restaurant_data['id'],
					'restaurant_name' => $restaurant_data['name'],
					// 'avatar'      => get_avatar_url( $user->ID ),
					'avatar'      => get_user_meta($user->ID, 'profilepicture', true) ? get_user_meta($user->ID, 'profilepicture', true) : get_avatar_url( $user->ID ),
					// 'permissions' => $capabulities,
					'permissions' => $permissions,
				),
			);

			return rest_ensure_response($response);
		}
	}

	public function emp_order_pin_logout($request){
		$emp_order_pin   = $request->get_param('emp_order_pin'); // Get PIN from request
		// Check if secret key exists

		return rest_ensure_response([
            'success' => true,
            'message' => __('Logged out successfully.', 'pinaka-pos')
        ]);
	}
	public function logout_by_user_id( WP_REST_Request $request ) {

    	// Get PIN from request
    	$emp_login_pin = intval( $request->get_param( 'emp_login_pin' ));
    
    	if ( ! $emp_login_pin ) {
    		return new WP_REST_Response(
    			array(
    				'success'    => false,
    				'statusCode' => 400,
    				'code'       => 'missing_pin',
    				'message'    => __( 'Employee login PIN is required.', 'pinaka-pos' ),
    				'data'       => array(),
    			),
    			400
    		);
    	}
    	// Find user by PIN
    	$users = get_users( [
            'meta_key'   => 'emp_login_pin',
            'meta_value' => $emp_login_pin,
            'number'     => 1,
            'fields'     => 'ids',
        ] );
        
        if ( empty( $users ) ) {
            return new WP_REST_Response(
                [
                    'success'    => false,
                    'statusCode' => 404,
                    'code'       => 'user_not_found',
                    'message'    => __( 'User not found.', 'pinaka-pos' ),
                    'data'       => [],
                ],
                404
            );
        }
        
        $user_id = $users[0];

    	if( ! $user_id ) {
    		return new WP_REST_Response(
    			array(
    				'success'    => false,
    				'statusCode' => 404,
    				'code'       => 'user_not_found',
    				'message'    => __( 'User not found.', 'pinaka-pos' ),
    				'data'       => array(),
    			),
    			404
    		);
    
    	}else{
    
    		// Delete stored token meta (invalidates JWT instantly)
    // 		delete_user_meta( $user_id, 'current_jwt_token' );
    // 		delete_user_meta( $user_id, 'token_created_at' );
    
    		return new WP_REST_Response(
    			array(
    				'success'    => true,
    				'statusCode' => 200,
    				'code'       => 'jwt_auth_logout_success',
    				'message'    => __( 'User logged out successfully.', 'pinaka-pos' ),
    				'data'       => array(),
    			)
    		);
    	}
    
    }
}
