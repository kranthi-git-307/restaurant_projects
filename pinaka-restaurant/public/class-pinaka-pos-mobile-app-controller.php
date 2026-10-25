<?php

if (!defined('ABSPATH')) {
    exit;
}

class Pinaka_Mobile_App_Controller {

    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'mobile-app';

    public function register_routes() {
        
        register_rest_route(
			$this->namespace,
            '/' . $this->rest_base . '/get-all-order-types',
			[
				'methods'              => 'GET',
				'callback'             => [$this, 'get_all_order_types'],
				'permission_callback'  => [$this, 'check_user_role_permission'],
            ]
		);
		
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order-counts-for-admin-app', 
			array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_all_order_status_counts'),
			'permission_callback' => array( $this, 'check_user_role_permission' )
			)
		);

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-orders',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_all_orders'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-daily-sales',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_daily_sales'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-payments',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_all_payments'],
                'permission_callback' => [$this, 'check_user_role_permission'],
            ]
        );
        
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-all-vendors',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_all_vendors'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-user-profile',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_user_profile'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-image',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_user_image'),
                'permission_callback' => array($this, 'check_user_role_permission'),
            )
        );
        

            register_rest_route(
                $this->namespace,
                '/reports-new/sales',
                [
                    'methods'  => 'GET',
                    'callback' => [$this, 'get_sales_report'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ]
            );
        
            register_rest_route(
                $this->namespace,
                '/reports-new/shift-sales',
                [
                    'methods'  => 'GET',
                    'callback' => [$this, 'get_shift_sales_report'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ]
            );
            register_rest_route(
                $this->namespace,
                '/reports-new/shift-sales-filter',
                [
                    'methods'  => 'GET',
                    'callback' => [$this, 'get_shift_sales_filter_report'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ]
            );
              register_rest_route(
                $this->namespace,
               '/' . $this->rest_base . '/create-vendor',
                [
                    'methods'  => 'POST',
                    'callback' => [$this, 'create_vendor'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ]
            );
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/update-vendor',
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'update_vendor'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/delete-vendor',
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'delete_vendor'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/get-all-coupons',
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'get_all_coupons'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/create-coupon',
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'create_coupon'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/update-coupon',
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'update_coupon'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/delete-coupon',
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'delete_coupon'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/get-coupon-by-id',
                array(
                    'methods' => 'GET',
                    'callback' => array($this, 'get_coupon_by_id'),
                    'permission_callback' => array($this, 'check_user_role_permission'),
                )
            );
            
            register_rest_route(
                'pinaka-restaurant-pos/v1',
                '/users',
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_users' ),
                    'permission_callback' => function () {
                        return current_user_can( 'list_users' );
                    },
                )
            );
            register_rest_route(
                'pinaka-restaurant-pos/v1',
                '/settings/enable-taxes',
                array(
                    'methods'  => 'POST',
                    'callback' => array($this, 'enable_taxes'),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    }
                )
            );
            register_rest_route(
                'pinaka-restaurant-pos/v1',
                '/settings/enable-coupons',
                array(
                    'methods'  => 'POST',
                    'callback' => array($this, 'enable_coupons'),
                    'permission_callback' => function () {
                        return current_user_can('manage_options');
                    }
                )
            );
    }

    public function check_user_role_permission( $request ) {

        $user_id = get_current_user_id();
        $user    = get_userdata( $user_id );
    
        if ( ! $user ) {
            return new WP_Error(
                'pinakapos_rest_cannot_view',
                __( 'Sorry, you cannot give permission.', 'pinaka-pos' ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }
    
        $auth = $request->get_header( 'Authorization' );
    
        if ( empty( $auth ) ) {
            return new WP_Error(
                'jwt_auth_no_auth_header',
                'Authorization header missing.',
                [ 'status' => 401 ]
            );
        }
    
        $incoming_token = trim( str_replace( 'Bearer', '', $auth ) );
    
        $stored_token = get_user_meta( $user_id, 'current_jwt_token', true );
    
        if ( empty( $stored_token ) || $stored_token !== $incoming_token ) {
            return new WP_Error(
                'jwt_auth_invalid_token',
                'Token has expired. Please login again.',
                [ 'status' => 401 ]
            );
        }
    
        return true;
    }
    
    public function get_user_profile() {

        $user_id = get_current_user_id();
        $user    = get_userdata($user_id);

        $attachment_id = get_user_meta($user_id, 'profile_image', true);

        $profile_image = $attachment_id
            ? wp_get_attachment_image_url($attachment_id, 'thumbnail')
            : '';

        return [
            'first_name' => $user->first_name,
            'lastname' => $user->last_name,
            'username' => $user->user_login,
            'email'    => $user->user_email,
            'gender'   => get_user_meta($user_id, 'gender', true),
            'phone'    => get_user_meta($user_id, 'phone', true),
            'profile_image' => $profile_image,
            'role' => !empty($user->roles) ? $user->roles[0] : ''
        ];
    }
    
    public function get_user_image( WP_REST_Request $request ) {

    $user_id = get_current_user_id();

    if ( ! $user_id ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'User not found.'
            ),
            404
        );
    }

    $profile_image = get_user_meta( $user_id, 'profilepicture', true );

    return new WP_REST_Response(
        array(
            'success'       => true,
            'user_id'       => $user_id,
            'profile_image' => ! empty( $profile_image ) ? esc_url( $profile_image ) : ''
        ),
        200
    );
}
    public function get_daily_sales( WP_REST_Request $request ) {
        
        global $wpdb;
        
        $order_type_filter = sanitize_text_field( $request->get_param( 'order_type' ) );

        $results = $wpdb->get_results("
            SELECT
                id as order_id,
                type as order_type,
                date_created_gmt as order_date,
                status
            FROM {$wpdb->prefix}wc_orders
            WHERE parent_order_id = 0 OR parent_order_id IS NULL
            ORDER BY id DESC
        ");

        $orders = array();
        $order_types = [
            'rest-api' => 'Dine In',
            'takeaway' => 'Takeaways',
            'online'   => 'Online Orders'
        ];
        $allowed_dinein_types = [
            'Swiggy Dine In',
            'Zomato Dine In',
            'Ubereats Dine In',
        ];
        foreach ( $results as $row ) {

            $order = wc_get_order( $row->order_id );
            if ( ! $order ) {
                continue;
            }

            $payment_method = $this->get_order_payment_method_from_payments( $order->get_id() );

            $restaurant_id = $order->get_meta('_restaurant_id', true);
            $zone_id       = $order->get_meta('_zone_id', true);
            $table_id      = $order->get_meta('_table_id', true);

            $total = (float) $order->get_total();

            /* ================================
            CUSTOMER FETCH LOGIC
            ================================= */

            $customer_name  = $order->get_meta('_customer_name', true);
            $customer_phone = $order->get_meta('_customer_phone', true);

            // Fallback to reservation
            if ( empty($customer_name) || empty($customer_phone) ) {

                $reservation_id = $order->get_meta('_reservation_id', true);

                if ( $reservation_id ) {
                    if ( empty($customer_name) ) {
                        $customer_name = get_post_meta($reservation_id, '_customer_name', true);
                    }

                    if ( empty($customer_phone) ) {
                        $customer_phone = get_post_meta($reservation_id, '_customer_phone', true);
                    }
                }
            }

            // Final fallback to billing (rare in POS)
            if ( empty($customer_name) ) {
                $customer_name = $order->get_formatted_billing_full_name();
            }

            if ( empty($customer_phone) ) {
                $customer_phone = $order->get_billing_phone();
            }
            
            $service_charge_percentage = $order->get_meta(
                '_service_charge_percentage',
                true
            );
            
            if ($service_charge_percentage === '') {
                $service_charge_percentage = 5;
            }

            // Merchant discount (force positive)
            $merchant_discount = (float) $order->get_meta('_order_discount', true);
            $merchant_discount = abs($merchant_discount);

            // Get child KOT order IDs
            $child_ids = $wpdb->get_col( $wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = %d
            ", $order->get_id() ) );

            // Load child KOT orders
            $kot_orders = [];
            foreach ( $child_ids as $child_id ) {
                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;
                if ( $child_order->has_status('cancelled') ) continue;
                $kot_orders[] = $child_order;
            }

            /* ===============================
            MODIFIER TAX DETECTION
            ================================ */
            $modifier_tax_total = 0;

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items( 'fee' ) as $fee_item ) {

                    $linked_line_item_id = absint(
                        $fee_item->get_meta( '_linked_line_item_id', true )
                    );

                    if ( ! $linked_line_item_id ) {
                        continue;
                    }

                    $modifier_tax_total += (float) $fee_item->get_total_tax();
                }
            }

            $modifiers_taxable = $modifier_tax_total > 0 ? 'yes' : 'no';

            

            // grand_total + tax_total (from KOT items)
            $grand_total    = 0;
            $tax_total      = 0;
            $items_subtotal = 0; // subtotal + tax
            $gross_total    = 0; // total of all items amount (subtotal only)

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items() as $item_id => $item ) {
                    if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                        continue;
                    }
                    $item_subtotal     = (float) $item->get_subtotal();
                    $item_subtotal_tax = (float) $item->get_subtotal_tax();

                    // modifier amount from meta
                    $modifier_amount = 0;
                    $meta_data = $item->get_meta_data();

                    foreach ( $meta_data as $meta ) {
                        if ( $meta->key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta->value;
                        }
                    }

                    // gross_total includes modifiers now
                    $gross_total    += ($item_subtotal + $modifier_amount);

                    $items_subtotal += ($item_subtotal + $modifier_amount) + $item_subtotal_tax;

                    $grand_total += (float) $item->get_total();
                    $tax_total   += (float) $item->get_total_tax();
                }
            }


            // Fee tax (from parent order fees)
            $fee_total     = 0;
            $fee_tax_total = 0;

            foreach ( $order->get_items('fee') as $fee ) {
                $fee_total     += (float) $fee->get_total();
                $fee_tax_total += (float) $fee->get_total_tax();
            }

            // Coupon Calculation
            $coupon_total      = 0;
            $coupon_tax_total  = 0;
            $coupon_data       = [];

            foreach ( $order->get_items('coupon') as $coupon ) {

                $coupon_code = $coupon->get_code();
                if ( empty($coupon_code) ) {
                    continue;
                }

                $wc_coupon     = new WC_Coupon( $coupon_code );
                $discount_type = $wc_coupon->get_discount_type();
                $coupon_amount = 0;

                if ( $discount_type === 'fixed_cart' ) {

                    $coupon_amount = (float) $wc_coupon->get_amount();

                } elseif ( $discount_type === 'percent' ) {

                    $discount_percentage = (float) $wc_coupon->get_amount();
                    $coupon_amount = ( $discount_percentage / 100 ) * $grand_total;

                } elseif ( $discount_type === 'fixed_product' ) {

                    $product_ids = $wc_coupon->get_product_ids();
                    $coupon_amount_per_item = (float) $wc_coupon->get_amount();

                    foreach ( $kot_orders as $kot ) {
                        foreach ( $kot->get_items() as $item ) {
                            $product_id = (int) $item->get_product_id();

                            if ( empty($product_ids) || in_array($product_id, $product_ids) ) {
                                $coupon_amount += $coupon_amount_per_item * (float) $item->get_quantity();
                            }
                        }
                    }
                }

                $coupon_tax = (float) $coupon->get_discount_tax();

                $coupon_total     += $coupon_amount;
                $coupon_tax_total += $coupon_tax;

                $coupon_data[] = [
                    'code'  => $coupon_code,
                    'value' => round($coupon_amount, 2),
                ];
            }

            // discount = coupon + merchant discount
            $discount = round( abs($coupon_total) + abs($merchant_discount), 2 );

            // Final tax = KOT item tax + fee tax - coupon tax
            $final_tax = round(
                ($tax_total + $fee_tax_total + $modifier_tax_total) - $coupon_tax_total,
                2
            );

            if ( $final_tax < 0 ) {
                $final_tax = 0;
            }

            // sub_total = gross_total - coupon discount
            $sub_total = round( $gross_total - abs($coupon_total), 2 );
            if ( $sub_total < 0 ) {
                $sub_total = 0;
            }

            // net_total = sub_total + final_tax
            $net_total = round( $sub_total + $final_tax, 2 );
            if ( $net_total < 0 ) {
                $net_total = 0;
            }

            // net_payable (before round off) = net_total - merchant_discount
            // net_payable before service charge
            $net_payable = round(($net_total - $merchant_discount), 2);
            if ($net_payable < 0) {
                $net_payable = 0;
            }
            
            $service_charge_percentage = (float) $service_charge_percentage;
            
            $service_charge_value = round(
                ($gross_total * $service_charge_percentage) / 100,
                2
            );
            
            $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // Add service charge & tip first
            $net_payable = round(
                $net_payable + $service_charge_value + $tip_amt,
                2
            );
            
            // Calculate round off on final payable
            $rounded_payable = round($net_payable, 0);
            $round_off = round($rounded_payable - $net_payable, 2);
            
            // Final payable after round off
            $net_payable = round($net_payable + $round_off, 2);
            
            if ($net_payable < 0) {
                $net_payable = 0;
            }

            $is_nc_order = $order->get_meta('_is_nc_order', true);
            $order_prev_total = $total;
            $initial_items = $order->get_meta('_kot_initial_items', true);

            // $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // $net_payable = round(
            //     $net_payable + $service_charge_value + $tip_amt,
            //     2
            // );
            
            $created_via = $order->get_created_via();
            
            $order_type = isset($order_types[$created_via])
                ? $order_types[$created_via]
                : '';
            $order_type = $order->get_meta('_pinaka_order_type', true) ?: $order_type;
            $order_type = isset($allowed_dinein_types[$order_type]) ? $allowed_dinein_types[$order_type] : $order_type;
            
            if (
                ! empty( $order_type_filter ) &&
                strtolower( trim( $order_type ) ) !== strtolower( trim( $order_type_filter ) )
            ) {
                continue;
            }
            $order_datetime = $order->get_meta('_order_datetime');

            // Parent order response data
            $order_data = array(
                'order_id'       => $order->get_id(),
                'order_type'     => $order_type,
                'date'           => $order->get_date_created() ? wp_date(
        get_option( 'date_format' ),
        $order->get_date_created()->getTimestamp()
    ) : '',
                'customer_name'  => $customer_name ?: '',
                'customer_phone' => $customer_phone ?: '',
                'payment_type'   => $payment_method ?: 'Unpaid',
                'order_prev_total' => get_post_meta($order->get_id() ,'_order_prev_total', true) ?: $order_prev_total,
                'is_updated'   => $order->get_meta('is_voided', true) ?: 'no',
                'updated_remarks' => $order->get_meta('order_edit_remarks', true) ?: '',
                'gross_total'        => (float) number_format($gross_total, 2, '.', ''),
                'coupon_details'     => $coupon_data,
                'sub_total'          => (float) number_format($sub_total, 2, '.', ''),
                'modifiers_taxable' => $modifiers_taxable,
                
                'total_tax'          => (float) number_format($final_tax, 2, '.', ''),
                'net_total'          => (float) number_format($net_total, 2, '.', ''),
                'is_nc_order' => ($is_nc_order === 'yes') ? 'yes' : 'no',
                //'merchant_discount'  => (float) number_format($merchant_discount, 2, '.', ''),
                'merchant_discount'  => $is_nc_order === 'yes' ? $net_payable : $merchant_discount,
                'tip_amt'            => floatval($tip_amt),
                'service_charge_percentage' => $is_nc_order === 'yes'
                ? 0
                : $service_charge_percentage,
        
            'service_charge_value' => $is_nc_order === 'yes'
                ? 0
                : $service_charge_value,
                'round_off' => (float) number_format($round_off, 2, '.', ''),
                //'net_payable'        => (float) number_format($net_payable, 2, '.', ''),
                'net_payable'        => $is_nc_order === 'yes' ? 0 : $net_payable,
        

                'restaurant_id'  => (int) $restaurant_id,
                'zone_id'        => (int) $zone_id,
                'table_id'       => (int) $table_id,
                'table_status'   => $order->get_meta('_table_status', true),

                'zone_name'      => $zone_id ? get_the_title($zone_id) : '',
                'table_name'     => $table_id ? get_the_title($table_id) : '',

                'status'         => ucfirst($order->get_status()),

                'is_parent'      => true,
                'initial_kot_items' => $initial_items ?: [],
                'kot_orders'     => array(),
            );

            // Add KOT order line items with modifiers/addons
            foreach ( $child_ids as $child_id ) {

                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;

                $kot_items = [];

                foreach ( $child_order->get_items() as $item_id => $item ) {
                //  if ( 'cancelled' === wc_get_order_item_meta( $item_id, '_item_status', true ) ) {
                //     continue;
                // }
                $product = $item->get_product();

                /* ===============================
                COMBO ITEMS LOGIC
                ================================ */
                $combo_items = [];

                if ( $product && $product->is_type( 'combo_bundle' ) ) {

                    $combo_meta = json_decode(
                        get_post_meta( $product->get_id(), '_combo_bundle_items', true ),
                        true
                    );

                    if ( is_array( $combo_meta ) ) {

                        foreach ( $combo_meta as $combo_item ) {

                            $child_product = wc_get_product( $combo_item['id'] );

                            if ( $child_product ) {
                                $combo_items[] = [
                                    'id'       => (int) $child_product->get_id(),
                                    'name'     => $child_product->get_name(),
                                    'quantity' => (int) $combo_item['qty'] * (float) $item->get_quantity(),
                                ];
                            }
                        }
                    }
                }

                    $quantity    = (float) $item->get_quantity();
                    $subtotal    = (float) $item->get_subtotal();
                    $unit_price  = $quantity > 0 ? ($subtotal / $quantity) : 0;

                    $product = $item->get_product();
                    $tax     = (float) $item->get_subtotal_tax();
                    $amount  = (float) $item->get_subtotal();

                    // modifiers/addons + extra modifier amount
                    $global_modifiers = [];
                    $modifier_amount  = 0;

                    $meta_data = $item->get_meta_data();
                    
                    foreach ( $meta_data as $meta ) {

                        $meta_key   = $meta->key;
                        $meta_value = $meta->value;

                        if ( in_array( $meta_key, [ '_addons', '_modifiers' ], true ) && is_array( $meta_value ) ) {

                            foreach ( $meta_value as $mod ) {

                                if ( ! empty( $mod['id'] ) ) {

                                    $mod_post = get_post( $mod['id'] );
                                    if ( $mod_post && $mod_post->post_type === 'modifier' ) {
                                        $global_modifiers[] = $mod_post->post_title;
                                    }

                                } else {

                                    if ( ! empty( $mod['name'] ) ) {
                                        $global_modifiers[] = $mod['name'];
                                    }
                                }
                            }
                        }

                        if ( $meta_key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta_value;
                        }
                    }

                    // total including modifier amount
                    $total_with_modifiers = ($amount + $modifier_amount) + $tax;
                    $total_wo_tax = $amount + $modifier_amount;
                    $item_status = wc_get_order_item_meta( $item_id, '_item_status', true );

                     $is_cancelled = ( strtolower( $item_status ) === 'cancelled' ) ? 'yes' : 'no';
                    $kot_items[] = [
                        'line_item_id'    => (int) $item_id,
                        'item_id'         => $product ? (int) $product->get_id() : 0,
                        'is_cancelled' => $is_cancelled,
                        'name'            => $item->get_name(),
                        'quantity'        => (float) $item->get_quantity(),
                        'tax'             => (float) number_format($tax, 2, '.', ''),
                        'item_price'      => (float) number_format($unit_price, 2, '.', ''),

                        'amount'          => (float) number_format($amount, 2, '.', ''),
                        'modifier_amount' => (float) number_format($modifier_amount, 2, '.', ''),
                        'modifiers'       => $global_modifiers,
                        'combo_items'     => $combo_items,
                        'total_wo_tax'    => (float) number_format($total_wo_tax, 2, '.', ''),
                        'total'           => (float) number_format($total_with_modifiers, 2, '.', ''),
                    ];
                }

                /* =========================
                FETCH VOIDED ITEMS FOR THIS KOT
                ========================= */

                $voided_items = [];

                $table_name = $wpdb->prefix . 'order_void_logs';

                $table_exists = $wpdb->get_var(
                    $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
                );

                if ( $table_exists === $table_name ) {

                    $void_logs = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT data, created_at
                            FROM {$table_name}
                            WHERE post_id = %d
                            ORDER BY id DESC",
                            $child_id   // 🔥 IMPORTANT CHANGE
                        ),
                        ARRAY_A
                    );

                    foreach ( (array) $void_logs as $log ) {

                        $log_data = json_decode( $log['data'], true );

                        if ( ! is_array( $log_data ) ) {
                            continue;
                        }

                        $voided_items[] = [
                            'item_id'   => (int) ($log_data['item_id'] ?? 0),
                            'product'   => $log_data['product'] ?? '',
                            //'orig_qty'  => (int) ($log_data['orig_qty'] ?? 0),
                            'new_qty'   => (int) ($log_data['new_qty'] ?? 0),
                            'remarks'   => $log_data['remarks'] ?? '',
                            'voided_by' => (int) ($log_data['voided_by'] ?? 0),
                            'voided_at' => $log['created_at'],
                        ];
                    }
                }



                $order_data['kot_orders'][] = [
                    'kot_order_id' => (int) $child_id,
                    'status'       => $child_order->get_status(),
                    'total'        => (float) $child_order->get_total(),
                    'created_at'   => $child_order->get_date_created() ? wp_date(
                        get_option( 'date_format' ),
                        $child_order->get_date_created()->getTimestamp()
                    ) : '',
                    'is_parent'    => false,
                    'voided_items' => $voided_items,

                    'line_items'   => $kot_items,
                ];
            }

            $orders[] = $order_data;
        }

        return new WP_REST_Response( $orders, 200 );
    }
    
    public function get_all_order_types( $request ) 
    {
        $ordertypes = ['Dine In','Takeaways','Online Orders'];
        return new WP_REST_Response([
            'success'     => true,
            'order_types' => $ordertypes
        ]);
    }
    
    public function get_all_order_status_counts( WP_REST_Request $request ) {

	global $wpdb;

	$status     = sanitize_text_field( $request->get_param( 'status' ) );
	$from_date  = sanitize_text_field( $request->get_param( 'from_date' ) );
	$to_date    = sanitize_text_field( $request->get_param( 'to_date' ) );
	$order_type = sanitize_text_field( $request->get_param( 'order_type' ) );

	if ( empty( $status ) ) {
		return rest_ensure_response( [
			'success' => false,
			'message' => 'Status required',
		] );
	}

	$clean_status = strtolower( str_replace( 'wc-', '', $status ) );

	$args = [
		'status' => $clean_status,
		'limit'  => -1,
	];

	if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
		$args['date_created'] = $from_date . '...' . $to_date;
	}

	$orders = wc_get_orders( $args );
	
	

	$order_types = [
		'rest-api' => 'Dine In',
		'takeaway' => 'Takeaways',
		'online'   => 'Online Orders',
	];

	$allowed_dinein_types = [
		'Swiggy Dine In',
		'Zomato Dine In',
		'Ubereats Dine In',
	];

	$count = 0;

	foreach ( $orders as $order ) {

		// Skip child (KOT) orders
		if ( $order->get_parent_id() ) {
			continue;
		}

		$created_via = $order->get_created_via();

		$current_order_type = isset( $order_types[ $created_via ] )
			? $order_types[ $created_via ]
			: '';

		$pinaka_order_type = $order->get_meta( '_pinaka_order_type', true );

		if ( ! empty( $pinaka_order_type ) ) {
			$current_order_type = $pinaka_order_type;
		}

		if ( isset( $allowed_dinein_types[ $current_order_type ] ) ) {
			$current_order_type = $allowed_dinein_types[ $current_order_type ];
		}

		// All Order Types
		if ( empty( $order_type ) || strtolower( $order_type ) === 'all' ) {
			$count++;
			continue;
		}

		// Selected Order Type
		if ( strcasecmp( trim( $current_order_type ), trim( $order_type ) ) === 0 ) {
			$count++;
		}
	}

	return rest_ensure_response( [
		'success'    => true,
		'status'     => ucfirst( $clean_status ),
		'order_type' => empty( $order_type ) ? 'All' : $order_type,
		'from'       => $from_date,
		'to'         => $to_date,
		'total'      => $count,
	] );
}

    public function get_all_orders(WP_REST_Request $request) {

        global $wpdb;
        
        $from_date = sanitize_text_field( $request->get_param( 'from_date' ) );
        $to_date   = sanitize_text_field( $request->get_param( 'to_date' ) );
        $order_type_filter = sanitize_text_field( $request->get_param( 'order_type' ) );
        
        $page     = max(1, absint($request->get_param('page')));
        $per_page = max(1, absint($request->get_param('per_page')));
        $search = sanitize_text_field($request->get_param('search'));
        $status = sanitize_text_field($request->get_param('status'));
        
        $offset = ($page - 1) * $per_page;
        
        $where = "WHERE (parent_order_id = 0 OR parent_order_id IS NULL)";
        
        if ( ! empty( $from_date ) && empty( $to_date ) ) {

            // Filter only one day
            $where .= $wpdb->prepare(
                " AND DATE(date_created_gmt) = %s",
                $from_date
            );
        
        } elseif ( ! empty( $from_date ) && ! empty( $to_date ) ) {
        
            // Filter between dates
            $where .= $wpdb->prepare(
                " AND DATE(date_created_gmt) BETWEEN %s AND %s",
                $from_date,
                $to_date
            );
        
        }
        
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND CAST(id AS CHAR) LIKE %s",
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        if (!empty($status)) {
            $where .= $wpdb->prepare(
                " AND status = %s",
                'wc-' . $status
            );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id as order_id,
                    type as order_type,
                    date_created_gmt as order_date,
                    status
                FROM {$wpdb->prefix}wc_orders
                {$where}
                ORDER BY id DESC
                LIMIT %d OFFSET %d
                ",
                $per_page,
                $offset
            )
        );

        $orders = array();
        $order_types = [
            'rest-api' => 'Dine In',
            'takeaway' => 'Takeaways',
            'online'   => 'Online Orders'
        ];
        $allowed_dinein_types = [
            'Swiggy Dine In',
            'Zomato Dine In',
            'Ubereats Dine In',
        ];
        foreach ( $results as $row ) {

            $order = wc_get_order( $row->order_id );
            if ( ! $order ) {
                continue;
            }

            $payment_method = $this->get_order_payment_method_from_payments( $order->get_id() );

            $restaurant_id = $order->get_meta('_restaurant_id', true);
            $zone_id       = $order->get_meta('_zone_id', true);
            $table_id      = $order->get_meta('_table_id', true);

            $total = (float) $order->get_total();

            /* ================================
            CUSTOMER FETCH LOGIC
            ================================= */

            $customer_name  = $order->get_meta('_customer_name', true);
            $customer_phone = $order->get_meta('_customer_phone', true);

            // Fallback to reservation
            if ( empty($customer_name) || empty($customer_phone) ) {

                $reservation_id = $order->get_meta('_reservation_id', true);

                if ( $reservation_id ) {
                    if ( empty($customer_name) ) {
                        $customer_name = get_post_meta($reservation_id, '_customer_name', true);
                    }

                    if ( empty($customer_phone) ) {
                        $customer_phone = get_post_meta($reservation_id, '_customer_phone', true);
                    }
                }
            }

            // Final fallback to billing (rare in POS)
            if ( empty($customer_name) ) {
                $customer_name = $order->get_formatted_billing_full_name();
            }

            if ( empty($customer_phone) ) {
                $customer_phone = $order->get_billing_phone();
            }
            
            $service_charge_percentage = $order->get_meta(
                '_service_charge_percentage',
                true
            );
            
            if ($service_charge_percentage === '') {
                $service_charge_percentage = 5;
            }

            // Merchant discount (force positive)
            $merchant_discount = (float) $order->get_meta('_order_discount', true);
            $merchant_discount = abs($merchant_discount);

            // Get child KOT order IDs
            $child_ids = $wpdb->get_col( $wpdb->prepare("
                SELECT id FROM {$wpdb->prefix}wc_orders
                WHERE parent_order_id = %d
            ", $order->get_id() ) );

            // Load child KOT orders
            $kot_orders = [];
            foreach ( $child_ids as $child_id ) {
                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;
                if ( $child_order->has_status('cancelled') ) continue;
                $kot_orders[] = $child_order;
            }

            /* ===============================
            MODIFIER TAX DETECTION
            ================================ */
            $modifier_tax_total = 0;

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items( 'fee' ) as $fee_item ) {

                    $linked_line_item_id = absint(
                        $fee_item->get_meta( '_linked_line_item_id', true )
                    );

                    if ( ! $linked_line_item_id ) {
                        continue;
                    }

                    $modifier_tax_total += (float) $fee_item->get_total_tax();
                }
            }

            $modifiers_taxable = $modifier_tax_total > 0 ? 'yes' : 'no';

            

            // grand_total + tax_total (from KOT items)
            $grand_total    = 0;
            $tax_total      = 0;
            $items_subtotal = 0; // subtotal + tax
            $gross_total    = 0; // total of all items amount (subtotal only)

            foreach ( $kot_orders as $kot ) {
                foreach ( $kot->get_items() as $item ) {

                    $item_subtotal     = (float) $item->get_subtotal();
                    $item_subtotal_tax = (float) $item->get_subtotal_tax();

                    // modifier amount from meta
                    $modifier_amount = 0;
                    $meta_data = $item->get_meta_data();

                    foreach ( $meta_data as $meta ) {
                        if ( $meta->key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta->value;
                        }
                    }

                    // gross_total includes modifiers now
                    $gross_total    += ($item_subtotal + $modifier_amount);

                    $items_subtotal += ($item_subtotal + $modifier_amount) + $item_subtotal_tax;

                    $grand_total += (float) $item->get_total();
                    $tax_total   += (float) $item->get_total_tax();
                }
            }


            // Fee tax (from parent order fees)
            $fee_total     = 0;
            $fee_tax_total = 0;

            foreach ( $order->get_items('fee') as $fee ) {
                $fee_total     += (float) $fee->get_total();
                $fee_tax_total += (float) $fee->get_total_tax();
            }

            // Coupon Calculation
            $coupon_total      = 0;
            $coupon_tax_total  = 0;
            $coupon_data       = [];

            foreach ( $order->get_items('coupon') as $coupon ) {

                $coupon_code = $coupon->get_code();
                if ( empty($coupon_code) ) {
                    continue;
                }

                $wc_coupon     = new WC_Coupon( $coupon_code );
                $discount_type = $wc_coupon->get_discount_type();
                $coupon_amount = 0;

                if ( $discount_type === 'fixed_cart' ) {

                    $coupon_amount = (float) $wc_coupon->get_amount();

                } elseif ( $discount_type === 'percent' ) {

                    $discount_percentage = (float) $wc_coupon->get_amount();
                    $coupon_amount = ( $discount_percentage / 100 ) * $grand_total;

                } elseif ( $discount_type === 'fixed_product' ) {

                    $product_ids = $wc_coupon->get_product_ids();
                    $coupon_amount_per_item = (float) $wc_coupon->get_amount();

                    foreach ( $kot_orders as $kot ) {
                        foreach ( $kot->get_items() as $item ) {
                            $product_id = (int) $item->get_product_id();

                            if ( empty($product_ids) || in_array($product_id, $product_ids) ) {
                                $coupon_amount += $coupon_amount_per_item * (float) $item->get_quantity();
                            }
                        }
                    }
                }

                $coupon_tax = (float) $coupon->get_discount_tax();

                $coupon_total     += $coupon_amount;
                $coupon_tax_total += $coupon_tax;

                $coupon_data[] = [
                    'code'  => $coupon_code,
                    'value' => round($coupon_amount, 2),
                ];
            }

            // discount = coupon + merchant discount
            $discount = round( abs($coupon_total) + abs($merchant_discount), 2 );

            // Final tax = KOT item tax + fee tax - coupon tax
            $final_tax = round(
                ($tax_total + $fee_tax_total + $modifier_tax_total) - $coupon_tax_total,
                2
            );

            if ( $final_tax < 0 ) {
                $final_tax = 0;
            }

            // sub_total = gross_total - coupon discount
            $sub_total = round( $gross_total - abs($coupon_total), 2 );
            if ( $sub_total < 0 ) {
                $sub_total = 0;
            }

            // net_total = sub_total + final_tax
            $net_total = round( $sub_total + $final_tax, 2 );
            if ( $net_total < 0 ) {
                $net_total = 0;
            }

            // net_payable (before round off) = net_total - merchant_discount
            // net_payable before service charge
            $net_payable = round(($net_total - $merchant_discount), 2);
            if ($net_payable < 0) {
                $net_payable = 0;
            }
            
            $service_charge_percentage = (float) $service_charge_percentage;
            
            $service_charge_value = round(
                ($gross_total * $service_charge_percentage) / 100,
                2
            );
            
            $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // Add service charge & tip first
            $net_payable = round(
                $net_payable + $service_charge_value + $tip_amt,
                2
            );
            
            // Calculate round off on final payable
            $rounded_payable = round($net_payable, 0);
            $round_off = round($rounded_payable - $net_payable, 2);
            
            // Final payable after round off
            $net_payable = round($net_payable + $round_off, 2);
            
            if ($net_payable < 0) {
                $net_payable = 0;
            }

            $is_nc_order = $order->get_meta('_is_nc_order', true);
            $order_prev_total = $total;
            $initial_items = $order->get_meta('_kot_initial_items', true);

            // $tip_amt = (float) $order->get_meta('_order_tip', true);
            
            // $net_payable = round(
            //     $net_payable + $service_charge_value + $tip_amt,
            //     2
            // );
            
            $created_via = $order->get_created_via();
            
            $order_type = isset($order_types[$created_via])
                ? $order_types[$created_via]
                : '';
            $order_type = $order->get_meta('_pinaka_order_type', true) ?: $order_type;
            
            if (
                ! empty($order_type_filter) &&
                strtolower(trim($order_type)) !== strtolower(trim($order_type_filter))
            ) {
                continue;
            }
            $order_type = isset($allowed_dinein_types[$order_type]) ? $allowed_dinein_types[$order_type] : $order_type;
            $order_datetime = $order->get_meta('_order_datetime');

            // Parent order response data
            $order_data = array(
                'order_id'       => $order->get_id(),
                'order_type'     => $order_type,
                'date'           => $order->get_date_created() ? wp_date(
                    get_option( 'date_format' ),
                    $order->get_date_created()->getTimestamp()
                ) : '',
                'customer_name'  => $customer_name ?: '',
                'customer_phone' => $customer_phone ?: '',
                'payment_type'   => $payment_method ?: 'Unpaid',
                'order_prev_total' => get_post_meta($order->get_id() ,'_order_prev_total', true) ?: $order_prev_total,
                'is_updated'   => $order->get_meta('is_voided', true) ?: 'no',
                'updated_remarks' => $order->get_meta('order_edit_remarks', true) ?: '',
                'gross_total'        => (float) number_format($gross_total, 2, '.', ''),
                'coupon_details'     => $coupon_data,
                'sub_total'          => (float) number_format($sub_total, 2, '.', ''),
                'modifiers_taxable' => $modifiers_taxable,
                
                'total_tax'          => (float) number_format($final_tax, 2, '.', ''),
                'net_total'          => (float) number_format($net_total, 2, '.', ''),
                'is_nc_order' => ($is_nc_order === 'yes') ? 'yes' : 'no',
                //'merchant_discount'  => (float) number_format($merchant_discount, 2, '.', ''),
                'merchant_discount'  => $is_nc_order === 'yes' ? $net_payable : $merchant_discount,
                'tip_amt'            => floatval($tip_amt),
                'service_charge_percentage' => $is_nc_order === 'yes'
                ? 0
                : $service_charge_percentage,
        
                'service_charge_value' => $is_nc_order === 'yes'
                ? 0
                : $service_charge_value,
                'round_off' => (float) number_format($round_off, 2, '.', ''),
                //'net_payable'        => (float) number_format($net_payable, 2, '.', ''),
                'net_payable'        => $is_nc_order === 'yes' ? 0 : $net_payable,
        

                'restaurant_id'  => (int) $restaurant_id,
                'zone_id'        => (int) $zone_id,
                'table_id'       => (int) $table_id,
                'table_status'   => $order->get_meta('_table_status', true),

                'zone_name'      => $zone_id ? get_the_title($zone_id) : '',
                'table_name'     => $table_id ? get_the_title($table_id) : '',

                'status'         => ucfirst($order->get_status()),

                'is_parent'      => true,
                'initial_kot_items' => $initial_items ?: [],
                'kot_orders'     => array(),
            );

            // Add KOT order line items with modifiers/addons
            foreach ( $child_ids as $child_id ) {

                $child_order = wc_get_order( $child_id );
                if ( ! $child_order ) continue;

                $kot_items = [];

                foreach ( $child_order->get_items() as $item_id => $item ) {

                $product = $item->get_product();

                /* ===============================
                COMBO ITEMS LOGIC
                ================================ */
                $combo_items = [];

                if ( $product && $product->is_type( 'combo_bundle' ) ) {

                    $combo_meta = json_decode(
                        get_post_meta( $product->get_id(), '_combo_bundle_items', true ),
                        true
                    );

                    if ( is_array( $combo_meta ) ) {

                        foreach ( $combo_meta as $combo_item ) {

                            $child_product = wc_get_product( $combo_item['id'] );

                            if ( $child_product ) {
                                $combo_items[] = [
                                    'id'       => (int) $child_product->get_id(),
                                    'name'     => $child_product->get_name(),
                                    'quantity' => (int) $combo_item['qty'] * (float) $item->get_quantity(),
                                ];
                            }
                        }
                    }
                }

                    $quantity    = (float) $item->get_quantity();
                    $subtotal    = (float) $item->get_subtotal();
                    $unit_price  = $quantity > 0 ? ($subtotal / $quantity) : 0;

                    $product = $item->get_product();
                    $tax     = (float) $item->get_subtotal_tax();
                    $amount  = (float) $item->get_subtotal();

                    // modifiers/addons + extra modifier amount
                    $global_modifiers = [];
                    $modifier_amount  = 0;

                    $meta_data = $item->get_meta_data();
                    
                    foreach ( $meta_data as $meta ) {

                        $meta_key   = $meta->key;
                        $meta_value = $meta->value;

                        if ( in_array( $meta_key, [ '_addons', '_modifiers' ], true ) && is_array( $meta_value ) ) {

                            foreach ( $meta_value as $mod ) {

                                if ( ! empty( $mod['id'] ) ) {

                                    $mod_post = get_post( $mod['id'] );
                                    if ( $mod_post && $mod_post->post_type === 'modifier' ) {
                                        $global_modifiers[] = $mod_post->post_title;
                                    }

                                } else {

                                    if ( ! empty( $mod['name'] ) ) {
                                        $global_modifiers[] = $mod['name'];
                                    }
                                }
                            }
                        }

                        if ( $meta_key === '_extra_modifier_amount' ) {
                            $modifier_amount += (float) $meta_value;
                        }
                    }

                    // total including modifier amount
                    $total_with_modifiers = ($amount + $modifier_amount) + $tax;
                    $total_wo_tax = $amount + $modifier_amount;
                    
                    $image = '';

                    if ( $product && $product->get_image_id() ) {
                        $image = wp_get_attachment_image_url(
                            $product->get_image_id(),
                            'thumbnail'
                        );
                    }

                    $kot_items[] = [
                        'line_item_id'    => (int) $item_id,
                        'item_id'         => $product ? (int) $product->get_id() : 0,
                        'name'            => $item->get_name(),
                        'image'           => $image,
                        'quantity'        => (float) $item->get_quantity(),
                        'tax'             => (float) number_format($tax, 2, '.', ''),
                        'item_price'      => (float) number_format($unit_price, 2, '.', ''),

                        'amount'          => (float) number_format($amount, 2, '.', ''),
                        'modifier_amount' => (float) number_format($modifier_amount, 2, '.', ''),
                        'modifiers'       => $global_modifiers,
                        'combo_items'     => $combo_items,
                        'total_wo_tax'    => (float) number_format($total_wo_tax, 2, '.', ''),
                        'total'           => (float) number_format($total_with_modifiers, 2, '.', ''),
                    ];
                }

                /* =========================
                FETCH VOIDED ITEMS FOR THIS KOT
                ========================= */

                $voided_items = [];

                $table_name = $wpdb->prefix . 'order_void_logs';

                $table_exists = $wpdb->get_var(
                    $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
                );

                if ( $table_exists === $table_name ) {

                    $void_logs = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT data, created_at
                            FROM {$table_name}
                            WHERE post_id = %d
                            ORDER BY id DESC",
                            $child_id   // 🔥 IMPORTANT CHANGE
                        ),
                        ARRAY_A
                    );

                    foreach ( (array) $void_logs as $log ) {

                        $log_data = json_decode( $log['data'], true );

                        if ( ! is_array( $log_data ) ) {
                            continue;
                        }

                        $voided_items[] = [
                            'item_id'   => (int) ($log_data['item_id'] ?? 0),
                            'product'   => $log_data['product'] ?? '',
                            //'orig_qty'  => (int) ($log_data['orig_qty'] ?? 0),
                            'new_qty'   => (int) ($log_data['new_qty'] ?? 0),
                            'remarks'   => $log_data['remarks'] ?? '',
                            'voided_by' => (int) ($log_data['voided_by'] ?? 0),
                            'voided_at' => $log['created_at'],
                        ];
                    }
                }



                $order_data['kot_orders'][] = [
                    'kot_order_id' => (int) $child_id,
                    'status'       => $child_order->get_status(),
                    'total'        => (float) $child_order->get_total(),
                    'created_at'   => $child_order->get_date_created() ? wp_date(
                        get_option( 'date_format' ),
                        $child_order->get_date_created()->getTimestamp()
                    ) : '',
                    'is_parent'    => false,
                    'voided_items' => $voided_items,

                    'line_items'   => $kot_items,
                ];
            }

            $orders[] = $order_data;
        }

        return new WP_REST_Response( $orders, 200 );

    }
    
    private function get_order_payment_method_from_payments( $order_id ) {

        $payments = get_posts( [
            'post_type'   => 'payments',
            'numberposts' => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'meta_query'  => [
                [
                    'key'   => '_payment_order_id',
                    'value' => $order_id,
                ],
                [
                    'key'     => '_payment_void',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ] );

        if ( empty( $payments ) ) {
            return '';
        }

        return (string) get_post_meta( $payments[0]->ID, '_payment_method', true );
    }
    
    public function get_all_payments(WP_REST_Request $request)
    {
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
    
foreach ($payments as $payment) {

    $order_id = get_post_meta($payment->ID, '_payment_order_id', true);

    $order_total = '';
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order_total = $order->get_total();
        }
    }

    $user_id = get_post_meta($payment->ID, '_payment_user_id', true);
    $user = get_userdata($user_id);

    // Transaction ID
    $transaction_id = get_post_meta($payment->ID, '_payment_transaction_id', true);

    // Fallback to payment notes
    if (empty($transaction_id)) {

        $notes = get_post_meta($payment->ID, '_payment_notes', true);

        if (!empty($notes)) {

            $decoded = json_decode($notes, true);

            if (
                json_last_error() === JSON_ERROR_NONE &&
                !empty($decoded['sunmiTransactionId'])
            ) {
                $transaction_id = $decoded['sunmiTransactionId'];
            }
        }
    }

    // Final fallback
    if (empty($transaction_id)) {
        $transaction_id = $payment->ID;
    }

    $results[] = array(
        'order_id'       => $order_id,
        'order_total'    => $order_total,
        'transaction_id' => $transaction_id,
        'tender_amount'  => get_post_meta($payment->ID, '_payment_amount', true),
        'pay_mode'       => get_post_meta($payment->ID, '_payment_method', true),
        'accepted_by'    => $user ? $user->display_name : '',
        'created_at'     => get_post_meta($payment->ID, '_payment_datetime', true),
    );
}

return rest_ensure_response($results);
        }
    
    
    public function get_all_vendors(WP_REST_Request $request)
{
    $args = array(
        'post_type'      => 'vendor',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $vendors = get_posts($args);

    $results = array();

    foreach ($vendors as $vendor) {

        $results[] = array(
            'vendor_id'        => $vendor->ID,
            'vendor_name'      => $vendor->post_title,
            'phone_number'     => get_post_meta($vendor->ID, '_vendor_phone', true),
            'email'            => get_post_meta($vendor->ID, '_vendor_email', true),
            'address'          => get_post_meta($vendor->ID, '_vendor_address', true),
            'linked_payments'  => get_post_meta($vendor->ID, '_vendor_linked_payments', true),
        );
    }

    return rest_ensure_response(array(
        'success' => true,
        'message' => 'Vendors fetched successfully.',
        'data'    => $results,
    ));
}
public function get_sales_report( WP_REST_Request $request ) {

    $type       = $request->get_param('period') ?: 'daily';
    $order_type = strtolower($request->get_param('order_type') ?: 'all');

    $total_sales  = 0;
    $total_orders = 0;
    $chart        = [];

    $filter_orders = function($orders) use ($order_type) {

        if ($order_type === 'all') {
            return $orders;
        }

        return array_values(array_filter($orders, function($order) use ($order_type) {

            $created_via = $order->get_created_via();

            if ($order_type === 'dinein') {
                return $created_via === 'rest-api';
            }

            if ($order_type === 'takeaway') {
                return $created_via === 'takeaway';
            }

            return true;
        }));
    };

    // ================= DAILY =================

    if ($type === 'daily') {

        $start = strtotime('monday this week');
        $end   = strtotime('sunday this week');

        while ($start <= $end) {

            $from = date('Y-m-d 00:00:00', $start);
            $to   = date('Y-m-d 23:59:59', $start);

            $orders = wc_get_orders([
                'limit'        => -1,
                'status'       => ['wc-processing', 'wc-completed'],
                'date_created' => $from . '...' . $to,
            ]);

            $orders = $filter_orders($orders);

            $sales = 0;

            foreach ($orders as $order) {
                $sales += (float)$order->get_total();
            }

            if (date('Y-m-d', $start) === date('Y-m-d')) {
                $total_sales  = $sales;
                $total_orders = count($orders);
            }

            $chart[] = [
                'label'  => date('D', $start),
                'orders' => count($orders),
                'sales'  => $sales
            ];

            $start = strtotime('+1 day', $start);
        }
    }

    // ================= WEEKLY =================

    elseif ($type === 'weekly') {

        $month_start = strtotime(date('Y-m-01'));
        $month_end   = strtotime(date('Y-m-t'));

        $week = 1;

        while ($month_start <= $month_end) {

            $from = date('Y-m-d 00:00:00', $month_start);

            $week_end = strtotime('+6 days', $month_start);

            if ($week_end > $month_end) {
                $week_end = $month_end;
            }

            $to = date('Y-m-d 23:59:59', $week_end);

            $orders = wc_get_orders([
                'limit'        => -1,
                'status'       => ['wc-processing', 'wc-completed'],
                'date_created' => $from . '...' . $to,
            ]);

            $orders = $filter_orders($orders);

            $sales = 0;

            foreach ($orders as $order) {
                $sales += (float)$order->get_total();
            }

            if (
                time() >= strtotime($from) &&
                time() <= strtotime($to)
            ) {
                $total_sales  = $sales;
                $total_orders = count($orders);
            }

            $chart[] = [
                'label'  => 'Week ' . $week,
                'orders' => count($orders),
                'sales'  => $sales
            ];

            $month_start = strtotime('+7 days', $month_start);
            $week++;
        }
    }

    // ================= MONTHLY =================

    else {

        $year = date('Y');

        for ($m = 1; $m <= 12; $m++) {

            $from = date($year . '-' . str_pad($m,2,'0',STR_PAD_LEFT) . '-01 00:00:00');
            $to   = date($year . '-' . str_pad($m,2,'0',STR_PAD_LEFT) . '-t 23:59:59');

            $orders = wc_get_orders([
                'limit'        => -1,
                'status'       => ['wc-processing', 'wc-completed'],
                'date_created' => $from . '...' . $to,
            ]);

            $orders = $filter_orders($orders);

            $sales = 0;

            foreach ($orders as $order) {
                $sales += (float)$order->get_total();
            }

            if ($m == date('n')) {
                $total_sales  = $sales;
                $total_orders = count($orders);
            }

            $chart[] = [
                'label'  => date('M', strtotime($from)),
                'orders' => count($orders),
                'sales'  => $sales
            ];
        }
    }

    return rest_ensure_response([
        'total_sales'  => $total_sales,
        'total_orders' => $total_orders,
        'chart'        => $chart,
    ]);
}
public function get_shift_sales_report( WP_REST_Request $request ) {

    $date       = $request->get_param('date');
    $order_type = strtolower($request->get_param('order_type') ?: 'all');

    if ( empty($date) ) {
        $date = date('Y-m-d');
    }

    $shifts = get_posts([
        'post_type'      => 'shifts',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'year'  => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day'   => date('d', strtotime($date)),
            ]
        ]
    ]);

    $shift_results = [];
    $total_sales   = 0;
    $total_orders  = 0;

    foreach ($shifts as $shift) {

        $shift_id   = $shift->ID;
        $shift_name = $shift->post_title;

        $orders = wc_get_orders([
    'limit'      => -1,
    'status'     => ['wc-processing', 'wc-completed'],
    'meta_query' => [
        [
            'key'   => 'shift_id',
            'value' => $shift_id,
        ]
    ]
]);

// Filter by order type
if ($order_type !== 'all') {

$orders = array_filter($orders, function($order) use ($order_type) {

    $is_dinein   = $order->get_meta('_parent_order') === 'yes';
    $is_takeaway = $order->get_meta('_takeaway_order') === 'yes';

    if ($order_type === 'dinein') {
        return $is_dinein;
    }

    if ($order_type === 'takeaway') {
        return $is_takeaway;
    }

    return true;
});
    $orders = array_values($orders);
}

        // Filter by order type
     foreach ($orders as $order) {

    error_log('Order ID: '.$order->get_id());

    error_log('created_via = '.$order->get_created_via());

    error_log('_takeaway_order = '.$order->get_meta('_takeaway_order'));

    error_log('_parent_order = '.$order->get_meta('_parent_order'));

    error_log('_table_status = '.$order->get_meta('_table_status'));

    error_log('----------------------------');
}

        $shift_sales  = 0;
        $shift_orders = count($orders);

        foreach ($orders as $order) {
            $shift_sales += (float) $order->get_total();
        }

        $total_sales  += $shift_sales;
        $total_orders += $shift_orders;

        $shift_results[] = [
            'shift_id'   => $shift_id,
            'shift_name' => $shift_name,
            'orders'     => $shift_orders,
            'sales'      => round($shift_sales, 2),
        ];
    }

    return rest_ensure_response([
        'date'         => $date,
        'total_sales'  => round($total_sales, 2),
        'total_orders' => $total_orders,
        'shifts'       => $shift_results,
    ]);
}
public function get_shift_sales_filter_report( WP_REST_Request $request ) {

    $date       = $request->get_param('date');
    $order_type = strtolower($request->get_param('order_type') ?: 'all');

    if (empty($date)) {
        $date = date('Y-m-d');
    }

    $shifts = get_posts([
        'post_type'      => 'shifts',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'year'  => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day'   => date('d', strtotime($date)),
            ]
        ]
    ]);

    $shift_results = [];
    $total_sales   = 0;
    $total_orders  = 0;

    foreach ($shifts as $shift) {

        $shift_id   = $shift->ID;
        $shift_name = $shift->post_title;

        $orders = wc_get_orders([
            'limit'      => -1,
            'status'     => ['wc-processing', 'wc-completed'],
            'meta_query' => [
                [
                    'key'   => 'shift_id',
                    'value' => $shift_id,
                ]
            ]
        ]);

        // Filter Orders
        if ($order_type !== 'all') {

$orders = array_filter($orders, function($order) use ($order_type) {

    $is_dinein   = $order->get_meta('_parent_order') === 'yes';
    $is_takeaway = $order->get_meta('_takeaway_order') === 'yes';

    if ($order_type === 'dinein') {
        return $is_dinein;
    }

    if ($order_type === 'takeaway') {
        return $is_takeaway;
    }

    return true;
});
            $orders = array_values($orders);
        }

        $sales  = 0;
        $count  = count($orders);

        foreach ($orders as $order) {
            $sales += (float)$order->get_total();
        }

        $total_sales  += $sales;
        $total_orders += $count;

        $shift_results[] = [
            'shift_id'   => $shift_id,
            'shift_name' => $shift_name,
            'sales'      => round($sales, 2),
            'orders'     => $count,
        ];
    }

    return rest_ensure_response([
        'date'         => $date,
        'order_type'   => $order_type,
        'total_sales'  => round($total_sales, 2),
        'total_orders' => $total_orders,
        'shifts'       => $shift_results,
    ]);
}
public function create_vendor(WP_REST_Request $request)
{
    $vendor_name = sanitize_text_field($request->get_param('vendor_name'));
    $phone       = sanitize_text_field($request->get_param('phone_number'));
    $email       = sanitize_email($request->get_param('email'));
    $address     = sanitize_textarea_field($request->get_param('address'));
    $linked_payments = sanitize_text_field($request->get_param('linked_payments'));

    if (empty($vendor_name)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Vendor name is required.'
        ]);
    }

    $vendor_id = wp_insert_post([
        'post_type'   => 'vendor',
        'post_status' => 'publish',
        'post_title'  => $vendor_name,
    ]);

    if (is_wp_error($vendor_id)) {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Unable to create vendor.'
        ]);
    }

    update_post_meta($vendor_id, '_vendor_phone', $phone);
    update_post_meta($vendor_id, '_vendor_email', $email);
    update_post_meta($vendor_id, '_vendor_address', $address);
    update_post_meta($vendor_id, '_vendor_linked_payments', $linked_payments);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Vendor created successfully.',
        'data' => [
            'vendor_id' => $vendor_id,
            'vendor_name' => $vendor_name,
            'phone_number' => $phone,
            'email' => $email,
            'address' => $address,
            'linked_payments' => $linked_payments,
        ]
    ]);
}
public function update_vendor(WP_REST_Request $request)
{
    $vendor_id = absint($request->get_param('vendor_id'));

    if (!$vendor_id || get_post_type($vendor_id) !== 'vendor') {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Vendor not found.'
        ]);
    }

    $vendor_name      = sanitize_text_field($request->get_param('vendor_name'));
    $phone_number     = sanitize_text_field($request->get_param('phone_number'));
    $email            = sanitize_email($request->get_param('email'));
    $address          = sanitize_textarea_field($request->get_param('address'));
    $linked_payments  = sanitize_text_field($request->get_param('linked_payments'));

    wp_update_post([
        'ID'         => $vendor_id,
        'post_title' => $vendor_name,
    ]);

    update_post_meta($vendor_id, '_vendor_phone', $phone_number);
    update_post_meta($vendor_id, '_vendor_email', $email);
    update_post_meta($vendor_id, '_vendor_address', $address);
    update_post_meta($vendor_id, '_vendor_linked_payments', $linked_payments);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Vendor updated successfully.',
        'data' => [
            'vendor_id' => $vendor_id
        ]
    ]);
}
public function delete_vendor(WP_REST_Request $request)
{
    $vendor_id = absint($request->get_param('vendor_id'));

    if (!$vendor_id || get_post_type($vendor_id) !== 'vendor') {
        return rest_ensure_response([
            'success' => false,
            'message' => 'Vendor not found.'
        ]);
    }

    wp_delete_post($vendor_id, true);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Vendor deleted successfully.'
    ]);
}
public function get_all_coupons(WP_REST_Request $request)
{
    $args = array(
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $posts = get_posts($args);

    $coupons = array();

    foreach ($posts as $post) {

        $coupon = new WC_Coupon($post->ID);

        $expiry = $coupon->get_date_expires();

        $coupons[] = array(
            'coupon_id'      => $coupon->get_id(),
            'coupon_code'    => $coupon->get_code(),
            'coupon_amount'  => $coupon->get_amount(),
            'discount_type'  => $coupon->get_discount_type(),
            'usage_limit'    => $coupon->get_usage_limit(),
            'usage_count'    => $coupon->get_usage_count(),
            'date_expires'   => $expiry ? $expiry->date('Y-m-d') : null,
            'description'    => $coupon->get_description(),
            'minimum_amount' => $coupon->get_minimum_amount(),
            'maximum_amount' => $coupon->get_maximum_amount(),
            'free_shipping'  => $coupon->get_free_shipping(),
        );
    }

    return rest_ensure_response($coupons);
}
public function create_coupon(WP_REST_Request $request)
{
    $coupon_code = sanitize_text_field($request->get_param('coupon_code'));

    if (empty($coupon_code)) {
        return array(
            'success' => false,
            'message' => 'Coupon code is required.'
        );
    }

    $coupon_id = wp_insert_post(array(
        'post_title'  => $coupon_code,
        'post_type'   => 'shop_coupon',
        'post_status' => 'publish'
    ));

    if (is_wp_error($coupon_id)) {
        return array(
            'success' => false,
            'message' => 'Failed to create coupon.'
        );
    }

    // Save coupon meta
    update_post_meta($coupon_id, 'discount_type', sanitize_text_field($request->get_param('discount_type')));
    update_post_meta($coupon_id, 'coupon_amount', sanitize_text_field($request->get_param('coupon_amount')));
    update_post_meta($coupon_id, 'usage_limit', absint($request->get_param('usage_limit')));
    update_post_meta($coupon_id, 'minimum_amount', sanitize_text_field($request->get_param('minimum_amount')));
    update_post_meta($coupon_id, 'maximum_amount', sanitize_text_field($request->get_param('maximum_amount')));
    update_post_meta($coupon_id, 'free_shipping', $request->get_param('free_shipping') ? 'yes' : 'no');

    // Optional description
    if ($request->get_param('description')) {
        wp_update_post(array(
            'ID' => $coupon_id,
            'post_excerpt' => sanitize_text_field($request->get_param('description'))
        ));
    }

    return array(
        'success' => true,
        'message' => 'Coupon created successfully.',
        'coupon' => array(
            'coupon_id'      => $coupon_id,
            'coupon_code'    => get_the_title($coupon_id),
            'coupon_amount'  => get_post_meta($coupon_id, 'coupon_amount', true),
            'discount_type'  => get_post_meta($coupon_id, 'discount_type', true),
            'usage_limit'    => get_post_meta($coupon_id, 'usage_limit', true),
            'usage_count'    => get_post_meta($coupon_id, 'usage_count', true),
            'minimum_amount' => get_post_meta($coupon_id, 'minimum_amount', true),
            'maximum_amount' => get_post_meta($coupon_id, 'maximum_amount', true),
            'free_shipping'  => get_post_meta($coupon_id, 'free_shipping', true),
            'description'    => get_post_field('post_excerpt', $coupon_id),
            'date_expires'   => get_post_meta($coupon_id, 'date_expires', true)
        )
    );
}
public function update_coupon(WP_REST_Request $request)
{
    $coupon_id = absint($request->get_param('coupon_id'));

    if (!$coupon_id) {
        return array(
            'success' => false,
            'message' => 'Invalid coupon.'
        );
    }

    wp_update_post(array(
        'ID' => $coupon_id,
        'post_title' => sanitize_text_field($request->get_param('coupon_code'))
    ));

    update_post_meta($coupon_id, 'discount_type', sanitize_text_field($request->get_param('discount_type')));
    update_post_meta($coupon_id, 'coupon_amount', sanitize_text_field($request->get_param('coupon_amount')));
    update_post_meta($coupon_id, 'usage_limit', sanitize_text_field($request->get_param('usage_limit')));
    update_post_meta($coupon_id, 'minimum_amount', sanitize_text_field($request->get_param('minimum_amount')));
    update_post_meta($coupon_id, 'maximum_amount', sanitize_text_field($request->get_param('maximum_amount')));
    update_post_meta($coupon_id, 'free_shipping', $request->get_param('free_shipping') ? 'yes' : 'no');

    return array(
        'success' => true,
        'message' => 'Coupon updated successfully.'
    );
}
public function delete_coupon(WP_REST_Request $request)
{
    $coupon_id = absint($request->get_param('coupon_id'));

    if (!$coupon_id) {
        return array(
            'success' => false,
            'message' => 'Invalid coupon.'
        );
    }

    wp_delete_post($coupon_id, true);

    return array(
        'success' => true,
        'message' => 'Coupon deleted successfully.'
    );
}
public function get_coupon_by_id(WP_REST_Request $request)
{
    $coupon_id = absint($request->get_param('coupon_id'));

    $coupon = get_post($coupon_id);

    if (!$coupon || $coupon->post_type !== 'shop_coupon') {
        return [
            'success' => false,
            'message' => 'Coupon not found.'
        ];
    }

    return [
        'success' => true,
        'data' => [
            'coupon_id' => $coupon->ID,
            'coupon_code' => $coupon->post_title,
            'discount_type' => get_post_meta($coupon->ID, 'discount_type', true),
            'coupon_amount' => get_post_meta($coupon->ID, 'coupon_amount', true),
            'usage_limit' => get_post_meta($coupon->ID, 'usage_limit', true),
            'minimum_amount' => get_post_meta($coupon->ID, 'minimum_amount', true),
            'maximum_amount' => get_post_meta($coupon->ID, 'maximum_amount', true),
        ]
    ];
}
public function get_users( WP_REST_Request $request ) {

    $users = get_users();

    $response = array();

    foreach ( $users as $user ) {

        $response[] = array(
            'id'         => $user->ID,
            'username'   => $user->user_login,
            'email'      => $user->user_email,
            'first_name' => get_user_meta( $user->ID, 'first_name', true ),
            'last_name'  => get_user_meta( $user->ID, 'last_name', true ),
            'roles'      => $user->roles,
            'meta'       => array(
                'emp_login_pin' => get_user_meta( $user->ID, 'emp_login_pin', true ),
                'user_phone'    => get_user_meta( $user->ID, 'user_phone', true ),
            ),
        );
    }

    return rest_ensure_response( $response );
}
public function enable_taxes( WP_REST_Request $request ) {

    $params = $request->get_json_params();

    if ( empty( $params ) ) {
        $params = $request->get_params();
    }

    $enable = isset( $params['enable_taxes'] ) ? (int) $params['enable_taxes'] : null;

    if ( $enable === null ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'enable_taxes is required'
            ),
            400
        );
    }

    update_option(
        'woocommerce_calc_taxes',
        $enable ? 'yes' : 'no'
    );

    return new WP_REST_Response(
        array(
            'success' => true,
            'message' => $enable ? 'Taxes enabled successfully' : 'Taxes disabled successfully',
            'enable_taxes' => $enable
        ),
        200
    );
}
    public function enable_coupons( WP_REST_Request $request ) {

    $params = $request->get_json_params();

    if ( empty( $params ) ) {
        $params = $request->get_params();
    }

    $enable = isset( $params['enable_coupons'] ) ? (int) $params['enable_coupons'] : null;

    if ( $enable === null ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => 'enable_coupons is required'
            ),
            400
        );
    }

    update_option(
        'woocommerce_enable_coupons',
        $enable ? 'yes' : 'no'
    );

    return new WP_REST_Response(
        array(
            'success' => true,
            'message' => $enable ? 'Coupons enabled successfully' : 'Coupons disabled successfully',
            'enable_coupons' => $enable
        ),
        200
    );
}
}