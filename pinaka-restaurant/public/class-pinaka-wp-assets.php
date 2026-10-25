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
class Pinaka_Assets_Api_Controller {

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
	protected $rest_base = 'assets';

    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_wp_assets' ),
				'permission_callback' => array( $this, 'check_user_role_permission' ),

			)
		);

        register_rest_route(
            $this->namespace,
            $this->rest_base . '/store-license-data',
            [
                'methods'  => 'POST',
                'callback' => array( $this, 'pinaka_receive_license_data'),
                'permission_callback' => '__return_true', // Optional: make private later
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

    public function get_wp_assets( ) {
        global $wp_roles;

        // 1. Base URL
        $base_url = get_site_url();

        // 2. Media (limit to latest 100 for performance)
        $media_query = new WP_Query([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1, // Use -1 to get all attachments
        ]);

        $media = array_map(function ($attachment) {
            return [
                'id'    => $attachment->ID,
                'title' => $attachment->post_title,
                'url'   => wp_get_attachment_url($attachment->ID),
            ];
        }, $media_query->posts);

        // 3. WooCommerce Tax Classes (Standard + Additional)
        $taxes = [];

        $classes = WC_Tax::get_tax_classes(); // returns array of slugs like ['reduced-rate', 'zero-rate']

        // Add the "Standard rate" manually
        $taxes[] = [
            'slug' => '',
            'name' => 'Standard',
        ];

        foreach ($classes as $class) {
            $taxes[] = [
                'slug' => sanitize_title($class),
                'name' => $class,
            ];
        }


        // 4. WooCommerce Coupons
        $coupons = [];
        $args = [
            'post_type'      => 'shop_coupon',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];
        $coupon_posts = get_posts($args);

        foreach ($coupon_posts as $post) {
            $coupons[] = [
                'id'         => $post->ID,
                'code'       => get_post_meta($post->ID, 'coupon_code', true) ?: $post->post_title,
                'amount'     => get_post_meta($post->ID, 'coupon_amount', true),
                'discount_type' => get_post_meta($post->ID, 'discount_type', true),
                'usage_limit' => get_post_meta($post->ID, 'usage_limit', true),
                'expiry_date' => get_post_meta($post->ID, 'date_expires', true),
            ];
        }

        // 5. WooCommerce Order Statuses (formatted array)
        $order_statuses_raw = wc_get_order_statuses(); // e.g., ['wc-pending' => 'Pending payment']
        $order_statuses = [];

        foreach ($order_statuses_raw as $key => $label) {
            $order_statuses[] = [
                'slug' => str_replace('wc-', '', $key),
                'name' => $label,
            ];
        }

        // 6. WooCommerce Currency
        $currency = get_woocommerce_currency(); // e.g., 'USD' 
        $currency_symbol = '\u{20B9}'; // e.g., '₹'

        // 7. Return the response of user roles
        $roles = [];

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        foreach ($wp_roles->roles as $role_key => $role_data) {
            $roles[] = [
                'slug' => $role_key,
                'name' => $role_data['name'],
            ];
        }

        // 8. Return the response of subscription plans
        $subscription_plans = [
            'type'       => get_option("pinaka_license_type"),
            'key'        => get_option("pinaka_license_key"),
            'expiration' => get_option("pinaka_license_expiration"),
            'origin'     => get_option("pinaka_license_origin"),
            'store_id'   => get_option("pinaka_license_id")
        ];

        $store_details = [
            'name'        => get_option("pinaka_pos_name"),
            'address'     => get_option("pinaka_pos_business_address"),
            'city'        => get_option("pinaka_pos_business_city"),
            'state'       => get_option("pinaka_pos_business_state"),
            'country'     => get_option("pinaka_pos_business_country"),
            'zip_code'    => get_option("pinaka_pos_business_postcode"),
            'phone_number'=> get_option("pinaka_pos_phone_number"),
        ];

        // 9. Denominations
        $denominations = get_option('pinaka_pos_denominations', []); // Stored as an array of values like [1, 2, 5, 10, 20]

        // 10. Safe Drop Tube Denominations
        $tubes_denom = get_option('pinaka_pos_safedrop_denominations', []); // Stored as an array of values like [1, 2, 5, 10, 20]

        // 11. Coins Denominations
        $coins_denom = get_option('pinaka_pos_coins_denominations', []); // Stored as an array of values like [1, 2, 5, 10, 20]

        // 12. Safe Drop Tube Size
        $safe_drop_tube_size = get_option('tube_size', ''); // e.g., 'small', 'medium', 'large'    

        // 13. Safe Drop Amount
        $safe_drop_amount = get_option('safe_drop_amount', 0); // e.g., 1000

        // 14. Drawer Amount
        $drawer_amount = get_option('cash_drawer_amount', 0); // e.g., 5000

        // 15. Vendor Data
        $vendors = [];
        $vendor_args = [
            'post_type'      => 'vendor',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];
        $vendor_data = get_posts($vendor_args);

        foreach ($vendor_data as $post) {
            $vendors[] = [
                'id'            => $post->ID,
                'vendor_name'   => $post->post_title
            ];
        }

        // 16. Safe Denominations
        $safe_denom = get_option('pinaka_pos_safe_denominations', []); // Stored as an array of values like [1, 2, 5, 10, 20]
        if (empty($safe_denom)) {
            $safe_denom = $denominations; // Fallback to denominations if not set
        }

        //Vendor Data
        $vendor_payment_types = [
            "Cash",
            "Card",
            "Check",
            "Online",
            "Other"
        ];
        $vendor_payment_purpose = [
            "Expenses", "Purchase"
        ];

        // Return the response
        return rest_ensure_response([
            'base_url' => $base_url,
            'media'    => $media,
            'taxes'    => $taxes,
            'coupons'  => $coupons,
            'order_statuses' => $order_statuses,
            'currency'       => $currency,
            'currency_symbol' => $currency_symbol,
            'roles'          => $roles,
            'subscription_plans' => $subscription_plans,
            'store_details'  => $store_details, 
            'notes_denom'  => $denominations,
            'coin_denom' => $coins_denom,
            'safe_denom' => $safe_denom,
            'tubes_denom' => $tubes_denom,
            'max_tubes_count' => $safe_drop_tube_size,
            'safe_drop_amount' => $safe_drop_amount,
            'drawer_amount' => $drawer_amount,
            'vendors' => $vendors,
            'vendor_payment_types' => $vendor_payment_types,
            'vendor_payment_purpose' => $vendor_payment_purpose
        ]);
    }

    /**
     * Store the pos subscription plan details
     * @param array $data   
     * @return array
     */


     function pinaka_receive_license_data(WP_REST_Request $request) {

        $license_type    = sanitize_text_field($request->get_param('license_type'));
        $activation_key  = sanitize_text_field($request->get_param('activation_key'));
        $store_id        = sanitize_text_field($request->get_param('store_id'));
        $expiration_date = sanitize_text_field($request->get_param('expiration_date'));
        $site_origin     = esc_url_raw($request->get_param('site_origin'));
    
        if (empty($store_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Missing store_id.'
            ], 400);
        }
    
        // Save each value to the options table with store_id prefix
        update_option("pinaka_license_type", $license_type);
        update_option("pinaka_license_key", $activation_key);
        update_option("pinaka_license_expiration", $expiration_date);
        update_option("pinaka_license_origin", $site_origin);
        update_option("pinaka_license_id", $store_id);
    
        return new WP_REST_Response([
            'success' => true,
            'message' => 'License data stored successfully.',
            'stored_keys' => [
                "pinaka_license_type" => $license_type,
                "pinaka_license_key" => $activation_key,
                "pinaka_license_expiration" => $expiration_date,
                "pinaka_license_origin" => $site_origin,
                "pinaka_license_id" => $store_id,
            ]
        ], 200);
    }
    

}