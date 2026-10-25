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
class Pinaka_Vendor_Payments_Api_Controller {

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
	protected $rest_base = 'vendor_payments';


    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-vendor-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'vendor_payment_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-user-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		/**
		 * Register Update Payment API Route
		 */
		register_rest_route(
            $this->namespace,
            $this->rest_base . '/update-vendor-payment',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'vendor_payment_update_callback' ),
                'permission_callback' => array( $this, 'check_user_role_permission' ),
            )
        );

		register_rest_route(
            $this->namespace,
            $this->rest_base . '/delete-vendor-payment',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'delete_vendor_payment_callback' ),
                'permission_callback' => array( $this, 'check_user_role_permission' ),
            )
        );
        register_rest_route(
            $this->namespace,
            $this->rest_base . '/get-vendor-payments',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_vendor_payments_callback' ),
                'permission_callback' => array( $this, 'check_user_role_permission' ),
            )
        );

		register_rest_route(
            $this->namespace,
            $this->rest_base . '/get-vendor-payment-by-id',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_vendor_payment_by_id_callback' ),
                'permission_callback' => array( $this, 'check_user_role_permission' ),
            )
        );

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-vendor-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_vendor_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-vendor-payments-by-shift-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vendor_payments_by_shift_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		register_rest_route(
            $this->namespace,
            $this->rest_base . '/get-vendors',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_vendors_callback' ),
                'permission_callback' => array( $this, 'check_user_role_permission' ),
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
	 * Get token by sending POST request to pinaka-restaurant-pos/v1/token.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	 public function vendor_payment_create_callback( WP_REST_Request $request ) {

        $data = $request->get_json_params();
    
        // Validate Required Fields
        if (
            empty( $data['vendor_id'] ) ||
            empty( $data['amount'] ) ||
            empty( $data['payment_method'] ) ||
            empty( $data['purpose'] )
        ) {
    
            return new WP_Error(
                'invalid_data',
                'Vendor, Amount, Payment Method and Purpose are required.',
                array( 'status' => 400 )
            );
        }
    
        // Verify Vendor Exists
        $vendor = get_post( intval( $data['vendor_id'] ) );
    
        if (
            ! $vendor ||
            'vendor' !== $vendor->post_type
        ) {
    
            return new WP_Error(
                'invalid_vendor',
                'Invalid Vendor.',
                array( 'status' => 400 )
            );
        }
    
        // Create Vendor Payment CPT Entry
       $user = wp_get_current_user();

        $post_title = 'Vendor Payment - ' . $user->display_name;

        $post_id = wp_insert_post(
            array(
                'post_type'   => 'vendor_payments',
                'post_status' => 'publish',
                'post_title'  => $post_title,
            ),
            true
        );
    
        // Required Fields
        update_post_meta(
            $post_id,
            '_vendor_payment_vendor_id',
            intval( $data['vendor_id'] )
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_amount',
            floatval( $data['amount'] )
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_method',
            sanitize_text_field( $data['payment_method'] )
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_purpose',
            sanitize_text_field( $data['purpose'] )
        );
    
        // Optional Invoice Number
        if ( isset( $data['invoice_no'] ) ) {
    
            update_post_meta(
                $post_id,
                '_vendor_payment_invoice_no',
                sanitize_text_field( $data['invoice_no'] )
            );
        }
    
        // Optional Notes
        if ( isset( $data['notes'] ) ) {
    
            update_post_meta(
                $post_id,
                '_vendor_payment_notes',
                sanitize_textarea_field( $data['notes'] )
            );
        }
    
        // Auto Generated Payment Date & Time
        update_post_meta(
            $post_id,
            '_vendor_payment_datetime',
            current_time( 'mysql' )
        );
    
        return rest_ensure_response(
            array(
                'success' => true,
                'vendor_payment_id' => $post_id,
                'message' => 'Vendor payment created successfully.'
            )
        );
    }


	public function get_vendor_payments_by_user_id_callback( WP_REST_Request $request ) {

		$user_id = $request->get_param( 'user_id' ) ?? get_current_user_id();
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_vendor_payment_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_fields = array(
			'_vendor_payment_amount','_vendor_payment_method','_vendor_payment_shift_id','_vendor_id','_vendor_payment_service_type','_vendor_payment_notes'
		);
		
		foreach ( $payments as $payment ) {
			// Get vendor ID from post meta
			$vendor_id = get_post_meta( $payment->ID, '_vendor_id', true );
			$vendor_name = get_post( $vendor_id, 'post_title', true );
			$meta_data = array();
			foreach ( $meta_fields as $meta_key ) {
				$meta_data[ str_replace( '_vendor_payment_', '', $meta_key ) ] = get_post_meta( $payment->ID, $meta_key, true );
			}
			$payment_data = (array) $payment;
			// Add vendor name to the payment data
			$payment_data['vendor_name'] = $vendor_name->post_title;
			
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}


	/**
	 * Update Payment API Callback
	 */
	public function vendor_payment_update_callback( WP_REST_Request $request ) {

        $data = $request->get_json_params();
    
        // 1. Validate Payment ID
        if ( empty( $data['vendor_payment_id'] ) ) {
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment ID is required.',
                array( 'status' => 400 )
            );
        }
    
        $post_id = absint( $data['vendor_payment_id'] );
    
        // 2. Get existing payment record
        $payment_post = get_post( $post_id );
    
        if (
            ! $payment_post ||
            'vendor_payments' !== $payment_post->post_type ||
            'trash' === $payment_post->post_status
        ) {
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment record not found.',
                array( 'status' => 404 )
            );
        }
    
        // 3. Validate required fields
        if (
            empty( $data['vendor_id'] ) ||
            ! isset( $data['amount'] ) ||
            empty( $data['payment_method'] ) ||
            empty( $data['purpose'] )
        ) {
            return new WP_Error(
                'invalid_data',
                'Vendor, Amount, Payment Method and Purpose are required.',
                array( 'status' => 400 )
            );
        }
    
        // 4. Validate amount
        $amount = floatval( $data['amount'] );
    
        if ( $amount <= 0 ) {
            return new WP_Error(
                'invalid_amount',
                'Amount must be greater than zero.',
                array( 'status' => 400 )
            );
        }
    
        // 5. Verify Vendor Exists
        $vendor_id = absint( $data['vendor_id'] );
        $vendor    = get_post( $vendor_id );
    
        if (
            ! $vendor ||
            'vendor' !== $vendor->post_type ||
            'publish' !== $vendor->post_status
        ) {
            return new WP_Error(
                'invalid_vendor',
                'Invalid Vendor.',
                array( 'status' => 400 )
            );
        }
    
        // 6. Update existing record meta only
        update_post_meta(
            $post_id,
            '_vendor_payment_vendor_id',
            $vendor_id
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_amount',
            $amount
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_method',
            sanitize_text_field( $data['payment_method'] )
        );
    
        update_post_meta(
            $post_id,
            '_vendor_payment_purpose',
            sanitize_text_field( $data['purpose'] )
        );
    
        // 7. Optional Invoice Number
        update_post_meta(
            $post_id,
            '_vendor_payment_invoice_no',
            isset( $data['invoice_no'] )
                ? sanitize_text_field( $data['invoice_no'] )
                : ''
        );
    
        // 8. Optional Notes
        update_post_meta(
            $post_id,
            '_vendor_payment_notes',
            isset( $data['notes'] )
                ? sanitize_textarea_field( $data['notes'] )
                : ''
        );
    
        // 9. Update timestamp
        update_post_meta(
            $post_id,
            '_vendor_payment_updated_datetime',
            current_time( 'mysql' )
        );
    
        // 10. Return updated existing record
        return rest_ensure_response(
            array(
                'success'           => true,
                'vendor_payment_id' => $post_id,
                'message'           => 'Vendor payment updated successfully.'
            )
        );
    }

	/**
 	* Delete Payment API Callback
	*/
	public function delete_vendor_payment_callback( WP_REST_Request $request ) {

        $payment_id = intval(
            $request->get_param( 'vendor_payment_id' )
        );
    
        if ( empty( $payment_id ) ) {
    
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment ID is required.',
                array( 'status' => 400 )
            );
        }
    
        $payment = get_post( $payment_id );
    
        if (
            ! $payment ||
            'vendor_payments' !== $payment->post_type
        ) {
    
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment not found.',
                array( 'status' => 404 )
            );
        }
    
        $deleted = wp_delete_post(
            $payment_id,
            true // permanent delete
        );
    
        if ( ! $deleted ) {
    
            return new WP_Error(
                'delete_failed',
                'Failed to delete vendor payment.',
                array( 'status' => 500 )
            );
        }
    
        return rest_ensure_response(
            array(
                'success' => true,
                'vendor_payment_id' => $payment_id,
                'message' => 'Vendor payment deleted successfully.'
            )
        );
    }
    //get all vendor Payments
    public function get_vendor_payments_callback( WP_REST_Request $request ) {

    $search = sanitize_text_field(
        $request->get_param( 'search' )
    );

    $date = sanitize_text_field(
        $request->get_param( 'date' )
    );

    $payments = get_posts(
        array(
            'post_type'      => 'vendor_payments',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
        )
    );

    $response = array();

    // Counts
    $total_count = count( $payments );
    $today_payments_count = 0;
    $today = current_time( 'Y-m-d' );

    foreach ( $payments as $payment ) {

        $payment_id = $payment->ID;

        $vendor_id = get_post_meta(
            $payment_id,
            '_vendor_payment_vendor_id',
            true
        );

        $vendor = get_post( $vendor_id );

        $vendor_name = $vendor
            ? $vendor->post_title
            : '';

        $phone_number = get_post_meta(
            $vendor_id,
            '_vendor_phone',
            true
        );

        $invoice_no = get_post_meta(
            $payment_id,
            '_vendor_payment_invoice_no',
            true
        );

        $amount = get_post_meta(
            $payment_id,
            '_vendor_payment_amount',
            true
        );

        $purpose = get_post_meta(
            $payment_id,
            '_vendor_payment_purpose',
            true
        );

        $payment_method = get_post_meta(
            $payment_id,
            '_vendor_payment_method',
            true
        );

        $notes = get_post_meta(
            $payment_id,
            '_vendor_payment_notes',
            true
        );

        $created_datetime = get_post_meta(
            $payment_id,
            '_vendor_payment_datetime',
            true
        );

        $payment_date = '';

        if ( ! empty( $created_datetime ) ) {

            $payment_date = wp_date(
                get_option( 'date_format' ),
                strtotime( $created_datetime )
            );

            // Today's Payments Count
            if ( date( 'Y-m-d', strtotime( $created_datetime ) ) === $today ) {
                $today_payments_count++;
            }
        }

        /**
         * Search Filter
         */
        if ( ! empty( $search ) ) {

            $found = false;

            if ( stripos( $vendor_name, $search ) !== false ) {
                $found = true;
            }

            if ( stripos( $phone_number, $search ) !== false ) {
                $found = true;
            }

            if ( ! $found ) {
                continue;
            }
        }

        /**
         * Date Filter
         */
        if (
            ! empty( $date ) &&
            $payment_date !== $date
        ) {
            continue;
        }

        $response[] = array(
            'vendor_payment_id' => $payment_id,
            'vendor_id'         => $vendor_id,
            'vendor_name'       => $vendor_name,
            'phone_number'      => $phone_number,
            'invoice_no'        => $invoice_no ?: '',
            'amount'            => $amount,
            'purpose'           => $purpose,
            'payment_method'    => $payment_method,
            'notes'             => $notes,
            'payment_date'      => $payment_date,
            'created_datetime'  => $created_datetime,
        );
    }

    return rest_ensure_response(
        array(
            'success'               => true,
            'total_count'           => $total_count,
            'filtered_count'        => count( $response ),
            'today_payments_count'  => $today_payments_count,
            'data'                  => $response,
        )
    );
}

	/**
 	* Get Payment by ID API Callback
	*/
	public function get_vendor_payment_by_id_callback( WP_REST_Request $request ) {

        $payment_id = intval(
            $request->get_param( 'vendor_payment_id' )
        );
    
        if ( empty( $payment_id ) ) {
    
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment ID is required.',
                array( 'status' => 400 )
            );
        }
    
        $payment = get_post( $payment_id );
    
        if (
            ! $payment ||
            'vendor_payments' !== $payment->post_type
        ) {
    
            return new WP_Error(
                'invalid_payment',
                'Vendor Payment not found.',
                array( 'status' => 404 )
            );
        }
    
        $vendor_id = get_post_meta(
            $payment_id,
            '_vendor_payment_vendor_id',
            true
        );
    
        $vendor = get_post( $vendor_id );
    
        $vendor_name = $vendor
            ? $vendor->post_title
            : '';
    
        $phone_number = get_post_meta(
            $vendor_id,
            '_vendor_phone',
            true
        );
    
        $response = array(
            'vendor_payment_id' => $payment_id,
            'vendor_id'         => $vendor_id,
            'vendor_name'       => $vendor_name,
            'phone_number'      => $phone_number,
            'invoice_no'        => get_post_meta(
                $payment_id,
                '_vendor_payment_invoice_no',
                true
            ),
            'amount'            => get_post_meta(
                $payment_id,
                '_vendor_payment_amount',
                true
            ),
            'payment_method'    => get_post_meta(
                $payment_id,
                '_vendor_payment_method',
                true
            ),
            'purpose'           => get_post_meta(
                $payment_id,
                '_vendor_payment_purpose',
                true
            ),
            'notes'             => get_post_meta(
                $payment_id,
                '_vendor_payment_notes',
                true
            ),
            'created_datetime'  => get_post_meta(
                $payment_id,
                '_vendor_payment_datetime',
                true
            ),
        );
    
        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => $response
            )
        );
    }
    
    //get all vendor_payments
    public function get_vendors_callback( WP_REST_Request $request ) {

        $vendors = get_posts( array(
            'post_type'      => 'vendor',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
    
        $response = array();
    
        foreach ( $vendors as $vendor ) {
    
            $response[] = array(
                'vendor_id'    => $vendor->ID,
                'vendor_name'  => $vendor->post_title,
                'phone_number' => get_post_meta(
                    $vendor->ID,
                    '_vendor_phone',
                    true
                ),
                'email' => get_post_meta(
                    $vendor->ID,
                    '_vendor_email',
                    true
                ),
            );
        }
    
        return rest_ensure_response( array(
            'success' => true,
            'vendor_count' => count( $vendors ),
            'data'    => $response
        ) );
    }


	public function get_vendor_payments_by_vendor_id_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			return new WP_Error( 'invalid_user', 'Order ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_order_id',
					'value'   => $order_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'payment_method', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes',  'transaction_id', 'transaction_details');
	
		foreach ( $payments as $payment ) {
			$meta_data = array();
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
		}
	
		return rest_ensure_response( $results );
	}


	public function get_vendor_payments_by_shift_id_callback( WP_REST_Request $request ) {

		$shift_id = $request->get_param( 'shift_id' );
		if ( empty( $shift_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'vendor_payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_vendor_payment_shift_id',
					'value'   => $shift_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_fields = array(
			'_vendor_payment_amount','_vendor_payment_method','_vendor_payment_shift_id','_vendor_id','_vendor_payment_service_type','_vendor_payment_notes'
		);
		
		foreach ( $payments as $payment ) {
			// Get vendor ID from post meta
			$vendor_id = get_post_meta( $payment->ID, '_vendor_id', true );
			$vendor_name = get_post( $vendor_id, 'post_title', true );
			$meta_data = array();
			foreach ( $meta_fields as $meta_key ) {
				$meta_data[ $meta_key ] = get_post_meta( $payment->ID, $meta_key, true );
			}
			$payment_data = (array) $payment;
			// Add vendor name to the payment data
			$payment_data['_vendor_name'] = $vendor_name->post_title;
			
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}
}
