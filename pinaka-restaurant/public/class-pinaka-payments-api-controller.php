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
class Pinaka_Payments_Api_Controller {

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
	protected $rest_base = 'payments';
	public function __construct() 
	{
		add_action( 'shutdown', [ $this, 'capture_fatal_errors' ] );
	}
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/create-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'payment_create_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-user-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_user_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-shift-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_shift_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

		/**
		 * Register Update Payment API Route
		 */
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'payment_update_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/delete-payment',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'payment_delete_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payment-by-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payment_by_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-payments-by-order-id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_payments_by_order_id_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);


		register_rest_route(
			$this->namespace,
			$this->rest_base . '/void-payment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pos_api_void_razorpay_payment' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			$this->rest_base . '/void-order', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'void_order_callback' ),
			'permission_callback' => '__return_true', // Change to proper auth later
		) );

		//update payment meta
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/update-payment-meta',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_payment_meta_callback' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),
			)
		);
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/get-all-payments',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'get_all_payments_callback'),
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
			$this->log_payment_event(
				'error',
				'AUTH_FAILED',
				'User authentication failed',
				[ 'user_id' => $user_id ]
			);
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
		$this->log_payment_event(
			'error',
			'PERMISSION_FAILED',
			'You dont have permission to login',
			[ 'user_roles' => $user->roles ]
		);
		return new WP_Error(
			'pinakapos_rest_cannot_view',
			esc_html__( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Update Payment Meta API Callback
	 * payload: { post_id:123, key:'_payment_notes', value:'New note' }
	 * curl -X POST http://example.com/wp-json/pinaka-pos/v1/payments/update-payment-meta -H "Content-Type: application/json" -d '{"post_id":123,"key":"_payment_notes","value":"New note"}'
	 */

	public function update_payment_meta_callback( WP_REST_Request $request ) {

		$data = $request->get_json_params();

error_log(print_r($data, true));
		$post_id = isset( $data['payment_id'] ) ? intval( $data['payment_id'] ) : 0;

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		$meta_keys = array( 'key', 'value' );
		foreach ( $meta_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, sanitize_text_field( $data['key'] ), sanitize_text_field( $data['value'] ) );
			}
		}
		return array( 'payment_id' => $post_id, 'message' => 'Payment Meta Updated Successfully' );
	}

	public function payment_create_callback( WP_REST_Request $request ) 
	{
		$user_id = get_current_user_id();
		$data    = $request->get_json_params();

		// Validate required fields
		if ( empty( $data['order_id'] ) || ! isset( $data['amount'] ) || $data['payment_method'] === '' ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Missing required payment fields. Order ID, amount, and payment method are required',
				[
					'payload' => $data
				]
			);
			return new WP_Error( 'invalid_data', 'Order ID, amount, and payment method are required', array( 'status' => 400 ) );
		}

		if ( empty( $data['shift_id'] ) ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Shift Id Required or Invalid',
				[
					'shift_id' => 0,
				]
			);
			return new WP_Error( 'invalid_data', 'Shift Id Required or Invalid.', array( 'status' => 400 ) );
		}

		$order_id = absint( $data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'VALIDATION_FAILED',
				'Invalid order ID',
				[
					'order_id' => $order_id ?? null,
				]
			);
			return new WP_Error( 'invalid_order', 'Invalid order ID', array( 'status' => 404 ) );
		}

		// Create the Payment post
		$post_id = wp_insert_post( array(
        
            'post_title' => ucfirst(strtolower($data['payment_method'])) . ' Payment for Order #' . $order_id,
        
            'post_content' => '',
            'post_type'    => 'payments',
            'post_status'  => 'publish',
        ) );

		if ( is_wp_error( $post_id ) ) {
			$this->log_payment_event(
				'error',
				'PAYMENT_POST_TYPE_INSERTION_FAILED',
				'Payments post type not created',
				[
					'post_id' => 0,
					'payload' => $data
				]
			);
			return $post_id;
		}
		if(!isset($data['shift_id']))
		{
            $shift_id = $order->get_meta( 'shift_id', true );
            $data['shift_id'] = $shift_id;
		}
		// Save meta fields
		$meta_fields = array(
			'order_id'       => '_payment_order_id',
			'amount'         => '_payment_amount',
			'payment_method' => '_payment_method',
			'shift_id'       => '_payment_shift_id',
			'user_id'        => '_payment_user_id',
			'service_type'   => '_payment_service_type',
			'datetime'       => '_payment_datetime',
			'notes'          => '_payment_notes',
			'transaction_id' => '_payment_transaction_id',
		);

		foreach ( $meta_fields as $key => $meta_key ) {
			if ( isset( $data[ $key ] ) ) { // allow 0, disallow only null
				update_post_meta( $post_id, $meta_key, sanitize_text_field( $data[ $key ] ) );
			}
		}

		update_post_meta( $post_id, '_payment_user_id', $user_id );
		update_post_meta( $post_id, '_payment_datetime', date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );

		$void = false;
		update_post_meta( $post_id, '_payment_void', $void );

		if ( ! empty( $data['transaction_details'] ) && is_array( $data['transaction_details'] ) ) {
			update_post_meta( $post_id, '_payment_transaction_details', wp_json_encode( $data['transaction_details'] ) );
		}

		/*
		|--------------------------------------------------------------------------
		| Calculate Total Paid & Update Order Status
		|--------------------------------------------------------------------------
		*/
		$order_total = (float) $order->get_total();
		
// 		$service_charge_percentage = (float) $order->get_meta(
//             '_service_charge_percentage',
//             true
//         );
        
//         if (empty($service_charge_percentage)) {
//             $service_charge_percentage = 5;
//         }
        
//         $service_charge_value = round(
//     ($order_total * $service_charge_percentage) / 100,
//     2
// );

// $order_total = round($order_total + $service_charge_value, 2);

// // Apply round off
// $rounded_payable = round($order_total, 0);
// $round_off = round($rounded_payable - $order_total, 2);

// $order_total = round($order_total + $round_off, 2);

		$new_amt = $order->get_meta('new_netpay_amt');

		$order_total = ($new_amt !== '' && $new_amt !== null) ? floatval($new_amt) : $order_total;
		// Sum of all non-voided payments
//         $get_net_payable = (float) get_post_meta($order->get_id(),'_net_payable',true) ?: 0;
// 		if(!empty($get_net_payable))
// 		{
// 		    $order_total = $get_net_payable;
// 		}
        // $service_charge_val = get_post_meta(
        //     $order->get_id(),
        //     '_service_charge_value',
        //     true
        // );
        // if($service_charge_val > 0)
        // {
        //     $order_total += $service_charge_val;
        // }
        // $get_net_payable = (float) get_post_meta($order->get_id(),'_net_payable',true) ?: 0;
        // if($get_net_payable > $order_total)
        // {
        //     $order_total = $get_net_payable;
            $meta_rounded_payable = round($order_total);
            $meta_round_off = round($meta_rounded_payable - $order_total, 2);
            $order_total = round($order_total + $meta_round_off, 2);
            update_post_meta($order_id,'_pay_final_total',floatval($order_total));
        // }
		$existing_payments = get_posts( array(
			'post_type'   => 'payments',
			'numberposts' => -1,
			'meta_query'  => array(
				array(
					'key'   => '_payment_order_id',
					'value' => $order_id,
				),
			),
		) );

		$total_paid = 0;
		foreach ( $existing_payments as $payment_post ) {
			$is_void = get_post_meta( $payment_post->ID, '_payment_void', true );
			if ( $is_void && $is_void !== 'false' ) {
				continue; // skip void payments
			}
			$total_paid += (float) get_post_meta( $payment_post->ID, '_payment_amount', true );
		}
// 		if($order_total > $total_paid)
// 		{
// 		    $total_paid = $order_total;
// 		}
//         $rounded_payable = round($order_total, 0);
// 		$rounded_payable = round($rounded_payable, 2);

// 		$round_off = round($rounded_payable - $order_total, 2);
// 		$order_total -= $round_off;
		if ( $order->meta_exists( '_is_nc_order' ) && 'yes' === $order->get_meta( '_is_nc_order' ) ) {
			$total_paid = $order_total;
		}
		// Update order status
		$child_order_id = get_post_meta( $order_id, '_child_order', true );
        $child_order="";
        if ( ! empty( $child_order_id ) ) {
            $child_order = wc_get_order( $child_order_id );
        }
		if ( $total_paid < 0 ) {
			$order->update_status( 'processing', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
		} elseif ( $total_paid < $order_total ) {
			$order->update_status( 'pending', __( 'Partial payment received.', 'pinaka-pos' ) );
		} else {
			$order->payment_complete();
			$order->update_status( 'completed', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
		}
        
		$remaining_amount = max( 0, $order_total - $total_paid );
		$change_amt = max( 0, $total_paid - $order_total );
		
		if($remaining_amount <= 0)
		{
		    if ( !empty($child_order) ) {
                $child_order->update_status( 'preparing', 'Status updated.', true );
            }
            if ( 'rest-api' === $order->get_created_via() ) {
                $order->update_meta_data( '_table_status', 'Available' );
                $order->save();
            }
		    $reservation_id = (int) $order->get_meta('_reservation_id' ,true);
    		if($reservation_id)
    		{
    		    update_post_meta($reservation_id, '_table_status', 'Available');
                update_post_meta($reservation_id, '_reservation_status', 'Completed');
    		}
		}
// 		$tip_amount  = (float) $order->get_meta('_order_tip', true);
// 		$tip_amount  = round($tip_amount, 2);
// 		if($change_amt > 0 && $tip_amount > 0 && $change_amt >= $tip_amount)
// 		{
// 		    $change_amt -= $tip_amount;
// 		}
        // $tip_amount = round((float) $order->get_meta('_order_tip', true), 2);
        
        // // Check if tip has already been deducted
        // $tip_deducted = $order->get_meta('_tip_deducted', true);
        
        // if (
        //     empty($tip_deducted) &&
        //     $change_amt > 0 &&
        //     $tip_amount > 0 &&
        //     $change_amt >= $tip_amount && $order_total > $get_net_payable
        // ) {
        //     $change_amt -= $tip_amount;
        
        //     // Mark as deducted
        //     $order->update_meta_data('_tip_deducted', 'yes');
        //     $order->save();
        // }
		// Return unified response
		$change_amt = round($change_amt,2);
		$remaining_amount = round($remaining_amount,2);
		return array(
			'success'          => true,
			'message'          => 'Payment Created Successfully',
			'payment_id'       => $post_id,
			'order_id'         => $order_id,
			'paid_amount'      => (float) $data['amount'],
			'total_paid'       => $total_paid,
			'remaining_amount' => $remaining_amount,
			'change'		   => $change_amt,
			'order_total'      => $order_total,
			'order_status'     => $order->get_status(),
			'void'             => $void,
		);
	}

	public function get_payments_by_user_id_callback( WP_REST_Request $request ) 
	{
		$user_id = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			return new WP_Error( 'invalid_user', 'User ID is required', array( 'status' => 400 ) );
		}
	
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_user_id',
					'value'   => $user_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details' );
	
		foreach ( $payments as $payment ) {
			$meta_data = array();
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
			$results['payment_method'] = get_post_meta( $payment->ID, '_payment_method', true );
		}
		
		return rest_ensure_response( $results );
	}

	/**
	 * Update Payment API Callback
	 */
	public function payment_update_callback( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$post_id = isset( $data['post_id'] ) ? intval( $data['post_id'] ) : 0;

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'INVALID_PAYMENT_ID',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		$meta_keys = array( 'order_id', 'amount', 'payment_method', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details', 'void' );
		foreach ( $meta_keys as $key ) {
			if ( isset( $data[ $key ] ) ) {
				update_post_meta( $post_id, '_payment_' . $key, sanitize_text_field( $data[ $key ] ) );
			}
		}
		return array( 'post_id' => $post_id, 'message' => 'Payment Updated Successfully' );
	}

	/**
 	* Delete Payment API Callback
	*/
	public function payment_delete_callback( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'payment_id' );

		if ( empty( $post_id ) || get_post_type( $post_id ) !== 'payments' ) {
			$this->log_payment_event(
				'error',
				'INVALID_PAYMENT_ID',
				'Invalid Payment ID',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'invalid_post', 'Invalid Payment ID', array( 'status' => 400 ) );
		}

		if ( wp_delete_post( $post_id, true ) ) {
			return array( 'post_id' => $post_id, 'message' => 'Payment Deleted Successfully' );
		} else {
			$this->log_payment_event(
				'error',
				'PAYMENT_DELETED_FAILED',
				'Failed to delete payment',
				[
					'payment_id' => $post_id ?? null,
				]
			);
			return new WP_Error( 'delete_failed', 'Failed to delete payment', array( 'status' => 500 ) );
		}
	}

	/**
 	* Get Payment by ID API Callback
	*/
	public function get_payment_by_id_callback( WP_REST_Request $request ) {
		$payment_id = $request->get_param( 'payment_id' );
		if ( empty( $payment_id ) ) {
			return new WP_Error( 'invalid_payment', 'Payment ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'p'           => $payment_id,
		);

		$payments = get_posts( $args );

		if ( empty( $payments ) ) {
			return new WP_Error( 'not_found', 'Payment not found', array( 'status' => 404 ) );
		}

		$meta_keys = array(
			'order_id', 'amount', 'payment_method', 'shift_id',
			'vendor_id', 'user_id', 'service_type', 'datetime',
			'notes', 'transaction_id', 'transaction_details', 'void'
		);

		$results = array();

		foreach ( $payments as $payment ) {
			$meta_data = array();

			// Normal meta fields
			foreach ( $meta_keys as $key ) {
				$meta_data[ $key ] = get_post_meta( $payment->ID, '_payment_' . $key, true );
			}

			// Handle void as proper boolean
			$void_meta = get_post_meta( $payment->ID, '_payment_void', true );
			$meta_data['void'] = ( $void_meta == '1' || $void_meta === true ) ? true : false;

			// Add order status if order exists
			$order_status = null;
			if ( ! empty( $meta_data['order_id'] ) ) {
				$order = wc_get_order( $meta_data['order_id'] );
				if ( $order ) {
					$order_status = $order->get_status();
				}
			}
			$meta_data['order_status'] = $order_status;

			// Base payment data
			$payment_data = array(
				'ID'      => $payment->ID,
				'title'   => $payment->post_title,
				'content' => $payment->post_content,
				'date'    => $payment->post_date,
			);

			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}

	public function get_payments_by_order_id_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			return new WP_Error( 'invalid_user', 'Order ID is required', array( 'status' => 400 ) );
		}

		$args = array(
			'post_type'   => 'payments',
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
		$meta_keys = array( 'order_id', 'amount', 'shift_id', 'vendor_id', 'user_id', 'service_type', 'datetime', 'notes', 'transaction_id', 'transaction_details', 'void' );

		foreach ( $payments as $payment ) {
			$meta_data = array();

			// Load meta keys
			foreach ( $meta_keys as $key ) {
				$value = get_post_meta( $payment->ID, '_payment_' . $key, true );

				// Normalize void to boolean
				if ( $key === 'void' ) {
					$value = ( ! empty( $value ) && $value == '1' ) ? true : false;
				}

				$meta_data[ $key ] = $value;
			}

			// Payment method
			$meta_data['payment_method'] = get_post_meta( $payment->ID, '_payment_method', true );

			// Get order status if valid order ID exists
			$order_status = null;
			if ( ! empty( $meta_data['order_id'] ) && wc_get_order( $meta_data['order_id'] ) ) {
				$order = wc_get_order( $meta_data['order_id'] );
				$order_status = $order->get_status(); // e.g. "processing", "completed"
			}
			$meta_data['order_status'] = $order_status;

			// Merge payment object + meta
			$payment_data = (array) $payment;
			$results[] = array_merge( $payment_data, $meta_data );
		}

		return rest_ensure_response( $results );
	}

	function pos_api_void_razorpay_payment( WP_REST_Request $request ) {
		$order_id   = $request->get_param('order_id');
		$payment_id = $request->get_param('payment_id');

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'ORDER_NOT_FOUND',
				'Order not found',
				[
					'order_id' => $request->get_param('order_id') ?? null,
				]
			);
			return new WP_Error( 'no_order', 'Order not found', [ 'status' => 404 ] );
		}

		// 1. Mark the payment record as void
		update_post_meta( $payment_id, '_payment_void', true );

		// 2. Add order note
		$order->add_order_note( "Payment ID {$payment_id} marked as voided via POS (Razorpay)." );

		// 3. Recalculate total paid (excluding void payments)
		$order_total   = (float) $order->get_total();
		$new_amt = $order->get_meta('new_netpay_amt');

		$order_total = ($new_amt !== '' && $new_amt !== null) ? floatval($new_amt) : $order_total;
		$order_total = get_post_meta($order_id,'_pay_final_total',true);
		$existing_payments = get_posts( [
			'post_type'   => 'payments',
			'numberposts' => -1,
			'meta_query'  => [
				[
					'key'   => '_payment_order_id',
					'value' => $order_id,
				],
			],
		] );

		$total_paid = 0;
		foreach ( $existing_payments as $payment_post ) {
			$is_void = get_post_meta( $payment_post->ID, '_payment_void', true );
			if ( $is_void ) {
				continue; // skip voided payments
			}
			$total_paid += (float) get_post_meta( $payment_post->ID, '_payment_amount', true );
		}
        $child_order_id = get_post_meta( $order_id, '_child_order', true );
        $child_order="";
        if ( ! empty( $child_order_id ) ) {
            $child_order = wc_get_order( $child_order_id );
        }
		// 4. Update order status based on remaining paid
		if ( $total_paid < 0 ) {
			$order->update_status( 'processing', 'All payments voided/refunded, order back to processing.' );
			if ( !empty($child_order) ) {
                $child_order->update_status( 'wc-on-hold', 'Status updated.', true );
            }
		} elseif ( $total_paid < $order_total ) {
			$order->update_status( 'pending', 'Partial payment remains after void/refund.' );
			if ( !empty($child_order) ) {
                $child_order->update_status( 'wc-on-hold', 'Status updated.', true );
            }
		} else {
			$order->payment_complete();
			$order->update_status( 'completed', __( 'All payments voided/refunded or covered by coupons.', 'pinaka-pos' ) );
			if ( !empty($child_order) ) {
                $child_order->update_status( 'preparing', 'Status updated.', true );
            }
		}
		
        if($total_paid <= 0)
        {
            if ( !empty($child_order) ) {
                $child_order->update_status( 'wc-on-hold', 'Status updated.', true );
            }
        }
		// 5. Update order meta with tracking fields
		update_post_meta( $order_id, '_amount_paid', $total_paid );
		update_post_meta( $order_id, '_remaining_amount', max( 0, $order_total - $total_paid ) );
		return [
			'success'          => true,
			'message'          => 'Payment voided successfully',
			'void_payment_id'  => $payment_id,
			'order_id'         => $order_id,
			'order_total'      => $order_total,
			'total_paid'       => $total_paid,
			'remaining_amount' => max( 0, $order_total - $total_paid ),
			'order_status'     => $order->get_status(),
		];
	}

	public function void_order_callback( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		if ( empty( $order_id ) ) {
			$this->log_payment_event(
				'error',
				'ORDER_ID_REQUIRED',
				'Order ID is required',
				[
					'order_id' => $request->get_param('order_id') ?? null,
				]
			);
			return new WP_Error( 'invalid_order', 'Order ID is required', array( 'status' => 400 ) );
		}

		// Get the WooCommerce order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->log_payment_event(
				'error',
				'ORDER_NOT_FOUND',
				'Order not found',
				[
					'order_id' => $order_id ?? null,
				]
			);
			return new WP_Error( 'not_found', 'Order not found', array( 'status' => 404 ) );
		}

		// 1. Void the order
		$order->update_status( 'cancelled', 'Order voided via API' );

		// 2. Find related payments
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'   => '_payment_order_id',
					'value' => $order_id,
				),
			),
			'numberposts' => -1,
		);

		$payments = get_posts( $args );

		// 3. Mark payments as void
		foreach ( $payments as $payment ) {
			update_post_meta( $payment->ID, '_payment_void', true );
		}
		return rest_ensure_response( array(
			'success'     => true,
			'message'     => 'Order and payments voided successfully.',
			'order_id'    => $order_id,
			'order_status'=> $order->get_status(),
			'voided_payments' => wp_list_pluck( $payments, 'ID' ),
		) );
	}

	public function get_payments_by_shift_id_callback( WP_REST_Request $request ) {
		$shift_id   = $request->get_param('shift_id');
		$args = array(
			'post_type'   => 'payments',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'     => '_payment_shift_id',
					'value'   => $shift_id,
					'compare' => '='
				)
			),
			'numberposts' => -1
		);
	
		$payments = get_posts( $args );
		$results = array();
		$meta_keys = array( 'order_id', 'amount', 'method', 'shift_id', 'datetime', 'transaction_id', 'transaction_details', 'void');
	
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
	public function log_payment_event($level, $event, $message = '', $context = []) 
	{
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();

		$day    = current_time( 'Y-m-d' );
		$source = 'pinaka-pos-payment-logs-'. $day;

		$log_context = array_merge(
			[
				'source' => $source,
				'event'  => sanitize_text_field( $event ),
				'date'   => $day,
				'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
			],
			$context
		);

		$level = in_array( $level, [ 'info', 'warning', 'error', 'critical' ], true )
			? $level
			: 'info';

		$logger->log(
			$level,
			$message ?: $event,
			$log_context
		);
	}
	public function capture_fatal_errors() {

		$error = error_get_last();

		if ( $error && in_array(
			$error['type'],
			[ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ],
			true
		) ) {
			$this->log_payment_event(
				'critical',
				'APP_CRASH',
				$error['message'],
				[
					'file' => $error['file'],
					'line' => $error['line'],
				]
			);
		}
	}

	public function get_all_payments_callback( WP_REST_Request $request ) {

    $payment_method = $request->get_param('payment_method');

			$args = array(
				'post_type'      => 'payments',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

    if ( ! empty( $payment_method ) ) {

        $args['meta_query'] = array(
            array(
                'key'     => '_payment_method',
                'value'   => $payment_method,
                'compare' => '='
            )
        );

    }

    $payments = get_posts( $args );

    $results = array();

    $meta_keys = array(
        'order_id',
        'amount',
        'shift_id',
        'vendor_id',
        'user_id',
        'service_type',
        'datetime',
        'notes',
        'transaction_id',
        'transaction_details'
    );

    foreach ( $payments as $payment ) {

        $meta_data = array();

        foreach ( $meta_keys as $key ) {
            $meta_data[$key] = get_post_meta(
                $payment->ID,
                '_payment_' . $key,
                true
            );
        }

        $meta_data['payment_method'] = get_post_meta(
            $payment->ID,
            '_payment_method',
            true
        );

       $user_id = get_post_meta($payment->ID, '_payment_user_id', true);
		$user = get_userdata($user_id);

		$payment_data = array(
			'payment_id'     => $payment->ID,
			'order_id'       => get_post_meta($payment->ID, '_payment_order_id', true),
			'amount'         => get_post_meta($payment->ID, '_payment_amount', true),
			'payment_method' => get_post_meta($payment->ID, '_payment_method', true),
			'user_id'        => $user_id,
			'user_name'      => $user ? $user->display_name : '',
			'shift_id'       => get_post_meta($payment->ID, '_payment_shift_id', true),
			'datetime'       => get_post_meta($payment->ID, '_payment_datetime', true),
		);

$results[] = $payment_data;
    }

    return rest_ensure_response($results);
}
}
