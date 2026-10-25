<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.pinakapos.com/
 * @since      1.0.0
 *
 * @package    Pinaka_POS
 * @subpackage Pinaka_POS/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Pinaka_POS
 * @subpackage Pinaka_POS/includes
 * @author     Magnigeeks <info@pinakapos.com>
 */
class Pinaka_POS {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Pinaka_POS_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'Pinaka_POS_VERSION' ) ) {
			$this->version = Pinaka_POS_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'pinaka-pos';
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->create_extra_column();
		$this->create_log_table();
		// Add custom fields in coupon edit page
        add_action('woocommerce_coupon_options', [$this, 'add_coupon_fields']);
        add_action('woocommerce_coupon_options_save', [$this, 'save_coupon_fields']);

        // Validate coupons (start date + day condition)
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon'], 10, 2);

        // Auto apply coupons when cart/session loads
		add_action('woocommerce_cart_loaded_from_session', [$this, 'auto_apply_coupons']);
		add_action('woocommerce_before_calculate_totals', [$this, 'auto_apply_coupons']);
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Pinaka_POS_Loader. Orchestrates the hooks of the plugin.
	 * - Pinaka_POS_i18n. Defines internationalization functionality.
	 * - Pinaka_POS_Admin. Defines all hooks for the admin area.
	 * - Pinaka_POS_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	 /**
     * Add custom fields: Start Date + Offer Day
     */
    /**
     * Add custom fields: Start Date + Offer Day + Auto Apply
     */
    public function add_coupon_fields($coupon_id) {
        // Start Date
        woocommerce_wp_text_input([
            'id'          => 'coupon_start_date',
            'label'       => __('Coupon Start Date', 'pinaka'),
            'type'        => 'date',
            'value'       => get_post_meta($coupon_id, 'coupon_start_date', true),
            'description' => __('Date when the coupon becomes active.', 'pinaka'),
        ]);

        // Offer Type (daily or weekday)
        woocommerce_wp_select([
            'id'          => 'coupon_offer_day',
            'label'       => __('Offer Day', 'pinaka'),
            'options'     => [
                ''          => __('-- None --', 'pinaka'),
                'daily'     => __('Every Day', 'pinaka'),
                'monday'    => __('Monday', 'pinaka'),
                'tuesday'   => __('Tuesday', 'pinaka'),
                'wednesday' => __('Wednesday', 'pinaka'),
                'thursday'  => __('Thursday', 'pinaka'),
                'friday'    => __('Friday', 'pinaka'),
                'saturday'  => __('Saturday', 'pinaka'),
                'sunday'    => __('Sunday', 'pinaka'),
            ],
            'value'       => get_post_meta($coupon_id, 'coupon_offer_day', true),
            'description' => __('Choose when this coupon is valid.', 'pinaka'),
        ]);

        // Auto Apply checkbox
        woocommerce_wp_checkbox([
            'id'          => 'coupon_auto_apply',
            'label'       => __('Auto Apply Coupon', 'pinaka'),
            'description' => __('If checked, this coupon will be applied automatically when valid.', 'pinaka'),
            'value'       => get_post_meta($coupon_id, 'coupon_auto_apply', true),
        ]);
    }

    /**
     * Save custom fields
     */
    public function save_coupon_fields($coupon_id) {
        if (isset($_POST['coupon_start_date'])) {
            update_post_meta($coupon_id, 'coupon_start_date', sanitize_text_field($_POST['coupon_start_date']));
        }
        if (isset($_POST['coupon_offer_day'])) {
            update_post_meta($coupon_id, 'coupon_offer_day', sanitize_text_field($_POST['coupon_offer_day']));
        }
        $auto_apply = isset($_POST['coupon_auto_apply']) ? 'yes' : '';
        update_post_meta($coupon_id, 'coupon_auto_apply', $auto_apply);
    }

    /**
     * Validate coupon (start date + day condition)
     */
    public function validate_coupon($valid, $coupon) {
        $coupon_id   = $coupon->get_id();
        $start_date  = get_post_meta($coupon_id, 'coupon_start_date', true);
        $offer_day   = get_post_meta($coupon_id, 'coupon_offer_day', true);
        $today       = strtolower(date('l')); // e.g. "wednesday"

        // Start date check
        if ($start_date && strtotime($start_date) > time()) {
            return false;
        }

        // Offer day check
        if ($offer_day && $offer_day !== 'daily' && $offer_day !== $today) {
            return false;
        }

        return $valid;
    }

        /**
         * Auto apply only if checkbox is selected
         */
        public function auto_apply_coupons() {
            if (is_admin() && !defined('DOING_AJAX')) return;
    
            $cart = WC()->cart;
            if (!$cart) return;
    
            $coupons = get_posts([
                'post_type'      => 'shop_coupon',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ]);
    
            foreach ($coupons as $coupon_post) {
                $coupon_id  = $coupon_post->ID;
                $code       = $coupon_post->post_title;
                $coupon     = new WC_Coupon($code);
    
                $auto_apply = get_post_meta($coupon_id, 'coupon_auto_apply', true);
    
                // Only apply if auto apply is checked
                if ($auto_apply === 'yes' && $coupon->is_valid() && !$cart->has_discount($code)) {
                    $cart->apply_coupon($code);
            }
        }
    }
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/class-pinaka-pos-i18n.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'vendor/autoload.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-admin.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-admin-helper.php';

		/**
		 * The class responsible for defining all actions that releated to deleted data.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-mp-deleted-data-controller.php';

		/**
		 * The class responsible for defining all actions that releated to modify query.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-mp-modify-query-controller.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-pinaka-pos-public.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'public/class-mp-order-stock-controller.php';

		require_once PINAKA_POS_PLUGIN_DIR . 'public/jwt/class-pinaka-pos-auth.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'public/jwt/class-pinaka-pos-devices.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-vendor.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-shifts.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-payments.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-vendor-payments.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-fastkeys.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-zones.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-tables.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-modifier.php';
		// require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-product-modifiers.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-order-statuses.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-inventory.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-combo.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-safes.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-safedrops.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-UserRoles.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-discounts.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-kot-orders.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-custom-variations.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-reservation-list.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-dynamic-price.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-pos-dash.php';
		require_once PINAKA_POS_PLUGIN_DIR . 'includes/Admin/class-pinaka-shift-view.php';

		$this->loader = new Pinaka_POS_Loader();
	}

	/**
	 * Create extra column on table to get data for modify date .
	 *
	 * Table woocommerce_attribute_taxonomies
	 * column last_update.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function create_extra_column() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'woocommerce_attribute_taxonomies';

		$is_column = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = %s AND column_name = %s',
				$table_name,
				'mp_last_update',
			),
		);
		if ( empty( $is_column ) ) {
			$wpdb->query( "ALTER TABLE `{$table_name}` ADD `mp_last_update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
		}
	}


	/**
	 * Create a custom table for operation logs.
	 *
	 * This function creates a table to log operations performed by users.
	 * It includes fields for user ID, action, object type, object ID, details,
	 * and timestamp.
	 *
	 * @since    1.0.0
	 */
	function create_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'post_full_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			post_id BIGINT,
			post_type VARCHAR(100),
			action VARCHAR(50),
			user_id BIGINT,
			data LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Pinaka_POS_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Pinaka_POS_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
        $shift_view = new Pinaka_Shift_View($this->get_plugin_name(), $this->get_version());
		$plugin_admin = new Pinaka_POS_Admin( $this->get_plugin_name(), $this->get_version() );
		$vendor_admin = new Pinaka_POS_Vendor( $this->get_plugin_name(), $this->get_version() );
		$shifts_admin = new Pinaka_POS_Shifts( $this->get_plugin_name(), $this->get_version() );
		$payments_admin = new Pinaka_POS_Payments( $this->get_plugin_name(), $this->get_version() );
		$vendor_payments_admin = new Pinaka_Vendor_Payments( $this->get_plugin_name(), $this->get_version() );
		$fast_keys_admin = new Fast_Keys( $this->get_plugin_name(), $this->get_version() );
		$zones_admin = new Pinaka_POS_Zones( $this->get_plugin_name(), $this->get_version() );
		$tables_admin = new Pinaka_POS_Tables( $this->get_plugin_name(), $this->get_version() );
		$modifier_admin = new Pinaka_POS_Modifier( $this->get_plugin_name(), $this->get_version() );
		// $product_modifier_admin = new Product_Modifiers( $this->get_plugin_name(), $this->get_version() );
		$safes_admin = new Safes( $this->get_plugin_name(), $this->get_version() );
		$safedrops_admin = new Safe_Drops( $this->get_plugin_name(), $this->get_version() );
		$inventory_admin = new Pinaka_POS_Inventory( $this->get_plugin_name(), $this->get_version() );
		$order_statuses_admin = new Pinaka_POS_Order_Statuses( $this->get_plugin_name(), $this->get_version() );
		$discounts_admin = new Pinaka_POS_Discounts( $this->get_plugin_name(), $this->get_version() );
		$combo_admin = new Pinaka_POS_Combo( $this->get_plugin_name(), $this->get_version() );
		$user_roles_admin = new Pinaka_POS_UserRoles( $this->get_plugin_name(), $this->get_version() );
		$reserve_admin = new Pinaka_POS_Reservation_List( $this->get_plugin_name(), $this->get_version() );
		$dynamic_price_admin = new WC_Dynamic_Time_Pricing( $this->get_plugin_name(), $this->get_version() );
		$kots_admin = new WC_KOT_Display( $this->get_plugin_name(), $this->get_version() );

		if ( class_exists( 'WooCommerce' ) && class_exists( 'WC_REST_Product_Variations_Controller' ) ) {
			$variations_admin = new Custom_Variation_Controller( $this->get_plugin_name(), $this->get_version() );
		}
		$this->loader->add_action('woocommerce_before_calculate_totals', $discounts_admin, 'pinaka_apply_mix_and_match_discounts', 10, 1);
		$this->loader->add_action('woocommerce_checkout_create_order', $discounts_admin, 'pinaka_apply_mix_and_match_discounts_to_order', 10, 2);

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'Pinaka_POS_setup_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'handle_registration' );
		$this->loader->add_action( 'plugins_loaded', $this, 'init_vendor' );
		
		$this->loader->add_action('admin_menu', $reserve_admin, 'register_reservation_list_post_type');
		$this->loader->add_action('admin_menu', $vendor_admin, 'register_vendor_post_type');

		$this->loader->add_action('admin_menu', $shifts_admin, 'register_shifts_post_type');

		$this->loader->add_action('admin_menu', $payments_admin, 'register_payments_post_type');

		$this->loader->add_action('admin_menu', $vendor_payments_admin, 'register_vendor_payments_post_type');

		$this->loader->add_action('admin_menu', $fast_keys_admin, 'register_fast_keys_post_type');

		$this->loader->add_action('admin_menu', $zones_admin, 'register_zone_post_type');

		$this->loader->add_action('admin_menu', $tables_admin, 'register_table_post_type');

		$this->loader->add_action('admin_menu', $modifier_admin, 'register_modifier_post_type');
		
		$this->loader->add_action('admin_menu', $inventory_admin, 'register_inventory_post_type');
		
		$this->loader->add_action('admin_menu', $discounts_admin, 'register_discounts_post_type');

		$this->loader->add_action('admin_menu', $safes_admin, 'register_safes_post_type');
		
		$this->loader->add_action('admin_menu', $safedrops_admin, 'register_safedrops_post_type');

		$this->loader->add_action('admin_menu', $this, 'pinaka_hide_admin_menu_items', 999);

		$this->loader->add_action('admin_menu', $this, 'pinaka_replace_woocommerce_menu', 1000);

		$this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_scripts');
		$this->loader->add_action('wp_ajax_pinaka_product_search', $this, 'pinaka_product_search_callback');
		$this->loader->add_action('wp_ajax_pin_search_products', $this, 'pinaka_addon_search_products');
		$this->loader->add_action('wp_ajax_pin_save_addons', $this, 'pinaka_addon_save_addons');
		$this->loader->add_action('wp_ajax_pin_get_addons', $this, 'pinaka_modifier_get_addons');
		$this->loader->add_action('wp_ajax_update_admin_color_scheme', $this, 'update_admin_color_scheme' );
		$this->loader->add_action('wp_ajax_save_pinaka_pos_menu_settings', $this, 'save_pinaka_pos_menu_settings');
		$this->loader->add_action('admin_init', $this, 'add_custom_variation_field_to_woocomarce_product');
		$this->loader->add_action('admin_init', $this, 'add_employee_order_caps', 999);

		$this->loader->add_action('wp_ajax_create_custom_role', $this, 'create_custom_role');
		$this->loader->add_action('wp_ajax_delete_receipt_logo', $this, 'delete_receipt_logo');
		$this->loader->add_action('admin_init', $this, 'save_tube_safe_cash');
		// $this->loader->add_action('admin_init', $this, 'pinaka_pos_register_settings');

		////////////////////////////////////// kranthi start ////////////////////////////////////////////////
		$this->loader->add_action('admin_notices', $this, 'show_pin_errors');

		////////////////////////////////////// kranthi end ////////////////////////////////////////////////

		$discount_id = $this->pinaka_get_product_by_title('Discount');
		if ( ! $discount_id ) {
			$discount_id = wp_insert_post([
				'post_title'   => 'Discount',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => 'This product is used internally for discounts or promotions.',
				'meta_input'   => [
					'_price'              => 0,
					'_regular_price'      => 0,
					'_virtual'            => 'yes',
					'_downloadable'       => 'no',
					'_catalog_visibility' => 'hidden',
					'_tax_status'         => 'none',
				],
			]);

			if ( $discount_id && ! is_wp_error($discount_id) ) {
				// require_once ABSPATH . 'wp-admin/includes/media.php';
				// require_once ABSPATH . 'wp-admin/includes/file.php';
				// require_once ABSPATH . 'wp-admin/includes/image.php';

				// $image_url = plugins_url('/images/discount.png', __FILE__); // You can change path or reuse payout.png
				// $attach_id = media_sideload_image($image_url, $discount_id, 'Discount Image', 'id');

				// if ( ! is_wp_error($attach_id) ) {
				// 	set_post_thumbnail($discount_id, $attach_id);
				// }

				update_post_meta($discount_id, '_tax_status', 'none');
			}
		}

		if ( $discount_id && ! is_wp_error($discount_id) ) {
			update_option('pinaka_discount_product_id', $discount_id);
		}

		// Create Tip product if not exists
		$tip_id = $this->pinaka_get_product_by_title('Tip');
		if ( ! $tip_id ) {
			$tip_id = wp_insert_post([
				'post_title'   => 'Tip',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => 'This product is used internally for tips.',
				'meta_input'   => [
					'_price'              => 0,
					'_regular_price'      => 0,
					'_virtual'            => 'yes',
					'_downloadable'       => 'no',
					'_catalog_visibility' => 'hidden',
					'_tax_status'         => 'none',
				],
			]);

			if ( $tip_id && ! is_wp_error($tip_id) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';

				$image_url = plugins_url('/images/tip.png', __FILE__); // You can change path or reuse payout.png
				$attach_id = media_sideload_image($image_url, $tip_id, 'Tip Image', 'id');

				if ( ! is_wp_error($attach_id) ) {
					set_post_thumbnail($tip_id, $attach_id);
				}

				update_post_meta($tip_id, '_tax_status', 'none');
			}
		}

		if ( $tip_id && ! is_wp_error($tip_id) ) {
			update_option('pinaka_tip_product_id', $tip_id);
		}
		
		// Create Order Amount product if not exists
        $order_amount_product = $this->pinaka_get_product_by_title('Order Amount');
        if ( ! $order_amount_product ) {
        	$order_amount_product = wp_insert_post([
        		'post_title'   => 'Order Amount',
        		'post_type'    => 'product',
        		'post_status'  => 'publish',
        		'post_content' => 'This product is used internally for order amounts.',
        		'meta_input'   => [
        			'_price'              => 0,
        			'_regular_price'      => 0,
        			'_virtual'            => 'yes',
        			'_downloadable'       => 'no',
        			'_catalog_visibility' => 'hidden',
        			'_tax_status'         => 'none',
        		],
        	]);
        
        	if ( $order_amount_product && ! is_wp_error($order_amount_product) ) {
        		require_once ABSPATH . 'wp-admin/includes/media.php';
        		require_once ABSPATH . 'wp-admin/includes/file.php';
        		require_once ABSPATH . 'wp-admin/includes/image.php';
        
        		$image_url = plugins_url('/images/order_amount.png', __FILE__); // You can change path or reuse payout.png
        		$attach_id = media_sideload_image($image_url, $order_amount_product, 'Order Amount Image', 'id');
        
        		if ( ! is_wp_error($attach_id) ) {
        			set_post_thumbnail($order_amount_product, $attach_id);
        		}
        
        		update_post_meta($order_amount_product, '_tax_status', 'none');
        	}
        }
        
        if ( $order_amount_product && ! is_wp_error($order_amount_product) ) {
        	update_option('pinaka_order_amount_product_id', $order_amount_product);
        }
        $this->pinaka_add_caps_to_roles();
	}
    // function pinaka_add_caps_to_roles() {

    //     $capabilities = [
    //         // Basic WordPress
    //         'read'                     => true,
    //         'level_0'                  => true,
    //         'edit_posts'               => true,
    //         'upload_files'             => true,
    
    //         // WooCommerce Core
    //         'manage_woocommerce'       => true,
    //         'view_woocommerce_reports' => true,
    
    //         // WooCommerce REST API
    //         'read_wc_api'              => true,
    //         'edit_wc_api'              => true,
    //         'delete_wc_api'            => true,
    //         'read_private_shop_orders' => true,
    //         'read_private_products'    => true,
    
    //         // Products
    //         'edit_products'            => true,
    //         'read_products'            => true,
    //         'delete_products'          => true,
    //         'publish_products'         => true,
    //         'edit_published_products'  => true,
    //         'delete_published_products'=> true,
    //         'edit_product_terms'       => true,
    //         'assign_product_terms'     => true,
    //         'manage_product_terms'     => true,
    
    //         // Orders
    //         'edit_shop_orders'         => true,
    //         'read_shop_order'          => true,
    //         'delete_shop_orders'       => true,
    //         'publish_shop_orders'      => true,
    //         'edit_others_shop_orders'  => true,
    
    //         // Users
    //         'list_users'               => true,
    //     ];
    
    //     global $wp_roles;
    
    //     foreach ($wp_roles->roles as $role_name => $role_data) {
    
    //         // Skip customer role
    //         if ($role_name === 'customer') {
    //             continue;
    //         }
    
    //         $role = get_role($role_name);
    
    //         if (!$role) {
    //             continue;
    //         }
    
    //         foreach ($capabilities as $cap => $grant) {
    //             $role->add_cap($cap, $grant);
    //         }
    //     }
    // }
    function pinaka_add_caps_to_roles() {

        $admin = get_role('administrator');
    
        if (!$admin) {
            return;
        }
    
        global $wp_roles;
    
        foreach ($wp_roles->roles as $role_name => $role_data) {
    
            if ($role_name === 'customer') {
                continue;
            }
    
            $role = get_role($role_name);
    
            if (!$role) {
                continue;
            }
    
            foreach ($admin->capabilities as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }
    }
	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		delete_option('pinaka_employee_caps_added');	
		
		$plugin_public           = new Pinaka_POS_Public( $this->get_plugin_name(), $this->get_version() );
		
        $deleted_data_controller = new MP_Deleted_Data_Controller();
		$modify_query_controller = new MP_Modify_Query_Controller();
		$order_stock_controller  = new MP_Order_Stock_Controller();
        
		$this->loader->add_action( 'admin_notices', $plugin_public, 'check_required_plugins' );

		$this->loader->add_action( 'rest_api_init', $plugin_public, 'add_Pinaka_POS_api_routes' );
		$this->loader->add_action( 'woocommerce_new_order', $plugin_public, 'new_order_notification', 10, 2 );
		
        $this->loader->add_action( 'magnipos_customer_billing_added', $plugin_public, 'new_customer_billing_added', 10, 1 );

		$this->loader->add_action( 'woocommerce_after_order_object_save', $modify_query_controller, 'calculate_profit_order', 10, 2 );
		$this->loader->add_action( 'woocommerce_refund_created', $modify_query_controller, 'new_refund_created', 10, 2 );

		$this->loader->add_filter( 'woocommerce_rest_prepare_report_sales', $modify_query_controller, 'get_custom_wc_report_sale', 10, 3 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_shop_order_object', $modify_query_controller, 'custom_wc_rest_prepare_shop_order_object', 10, 1 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_shop_order_refund_object', $modify_query_controller, 'custom_wc_rest_prepare_shop_order_object', 10, 1 );
		$this->loader->add_filter( 'woocommerce_rest_prepare_product_cat', $modify_query_controller, 'get_custom_wc_cat_child_count', 10, 3 );

		$this->loader->add_filter( 'woocommerce_reports_get_order_report_data_args', $modify_query_controller, 'get_wc_order_report_data_args', 10, 1 );
		$this->loader->add_filter( 'upload_mimes', $this, 'my_own_mime_types' );

		$this->loader->add_action( 'delete_term', $deleted_data_controller, 'save_deleted_term', 10, 5 );
		$this->loader->add_action( 'after_delete_post', $deleted_data_controller, 'save_deleted_post', 10, 2 );
		$this->loader->add_action( 'delete_attachment', $deleted_data_controller, 'save_deleted_post', 10, 2 );
		$this->loader->add_action( 'delete_user', $deleted_data_controller, 'save_deleted_user', 10, 1 );
		$this->loader->add_action( 'woocommerce_tax_rate_deleted', $deleted_data_controller, 'save_deleted_tax', 10, 1 );
		$this->loader->add_action( 'woocommerce_attribute_deleted', $deleted_data_controller, 'save_deleted_attribute', 10, 3 );

		$this->loader->add_action( 'woocommerce_attribute_updated', $modify_query_controller, 'update_last_update_column', 10, 3 );

		$this->loader->add_filter( 'woocommerce_rest_product_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_orders_prepare_object_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_variation_query', $modify_query_controller, 'add_modified_after_filter_to_post', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_cat_query', $modify_query_controller, 'add_modified_after_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_product_tag_query', $modify_query_controller, 'add_modified_after_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_customer_query', $modify_query_controller, 'add_customer_filter_to_meta', 10, 2 );
		$this->loader->add_filter( 'woocommerce_rest_orders_prepare_object_query', $modify_query_controller, 'add_shop_order_filter_to_meta', 10, 2 );

		$this->loader->add_filter( 'woocommerce_customer_get_order_count', $modify_query_controller, 'custom_customer_get_order_count', 10, 2 );
		$this->loader->add_filter( 'woocommerce_customer_get_total_spent', $modify_query_controller, 'custom_customer_get_total_spent', 10, 2 );

		$this->loader->add_action( 'woocommerce_rest_insert_shop_order_object', $modify_query_controller, 'add_post_author_to_order', 10, 2 );

		$this->loader->add_action( 'woocommerce_product_options_pricing', $modify_query_controller, 'add_custom_purchase_price_woocommerce', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_product_meta', $modify_query_controller, 'save_custom_purchase_price_woocommerce', 10, 2 );

		$this->loader->add_filter( 'edit_terms', $modify_query_controller, 'add_modified_date_terms_meta', 10, 1 );
		$this->loader->add_filter( 'create_term', $modify_query_controller, 'add_modified_date_terms_meta', 10, 1 );

		$this->loader->add_filter( 'jwt_auth_whitelist', $plugin_public, 'white_listing_endpoints' );

		$auth    = new Pinaka_POS_Auth();
		$devices = new Pinaka_POS_Devices();

		$this->loader->add_action( 'rest_api_init', $auth, 'register_rest_routes' );
		$this->loader->add_filter( 'rest_api_init', $auth, 'add_cors_support' );
		$this->loader->add_filter( 'rest_pre_dispatch', $auth, 'rest_pre_dispatch', 10, 3 );
		$this->loader->add_filter( 'determine_current_user', $auth, 'determine_current_user' );

		$this->loader->add_action( 'init', $modify_query_controller, 'woocommerce_stock_amount_filters', 999999 );
		$this->loader->add_filter( 'woocommerce_rest_shop_order_schema', $modify_query_controller, 'rest_shop_order_schema' );
		$this->loader->add_filter( 'woocommerce_rest_product_schema', $modify_query_controller, 'rest_product_schema' );
		$this->loader->add_filter( 'woocommerce_rest_product_variation_schema', $modify_query_controller, 'rest_product_schema' );
		$this->loader->add_filter( 'posts_where', $modify_query_controller, 'add_search_criteria_to_wp_query_where', 20, 1 );
		$this->loader->add_filter( 'woocommerce_get_catalog_ordering_args', $modify_query_controller, 'stock_status_value_on_order_item_view' );

		$this->loader->add_action( 'woocommerce_variation_options_pricing', $modify_query_controller, 'add_variation_options_pricing', 10, 3 );
		$this->loader->add_action( 'woocommerce_save_product_variation', $modify_query_controller, 'save_variation_options_pricing', 10, 2 );
		$this->loader->add_filter( 'woocommerce_available_variation', $modify_query_controller, 'add_custom_field_variation_data', 10, 1 );
        $this->loader->add_filter( 'woocommerce_reports_get_order_report_query', $modify_query_controller, 'add_custom_order_report_query', 10, 1 );

		// supplier module.
		$this->loader->add_filter( 'woocommerce_register_shop_order_post_statuses', $plugin_public, 'add_custom_shop_order_post_statuses', 10, 1 );
		$this->loader->add_filter( 'woocommerce_order_status_changed', $order_stock_controller, 'update_order_status_callback', 10, 3 );
		$this->loader->add_filter( 'mp_order_item_removed', $order_stock_controller, 'mp_order_item_removed_callbacked', 10, 2 );

		////////////////////////////////////// kranthi start ////////////////////////////////////////////////
		// Hook to initialize meta field
		$this->loader->add_action('init', $this, 'register_emp_login_pin_meta');
		// Hook to initialize user meta field
		$this->loader->add_action('init', $this, 'register_user_login_pin_meta');
		// Hook to initialize user meta field
		$this->loader->add_action('init', $this, 'register_restaurants');
		$this->loader->add_action('init', $this, 'register_device_id_meta');
		$this->loader->add_action('init', $this, 'register_pos_theme_meta');






		// $this->loader->add_action('init', $this, 'register_shifts');

		// Hook to save the field when updated
		$this->loader->add_action('personal_options_update', $this, 'save_emp_login_pin_field');
		$this->loader->add_action('edit_user_profile_update', $this, 'save_emp_login_pin_field');


		// Hook to display the field in user profile
		$this->loader->add_action('show_user_profile', $this, 'add_emp_login_pin_field');
		$this->loader->add_action('edit_user_profile', $this, 'add_emp_login_pin_field');

		//for user login pin
		
		// Hook to save the field when updated
		$this->loader->add_action('personal_options_update', $this, 'save_user_login_pin_field');
		$this->loader->add_action('edit_user_profile_update', $this, 'save_user_login_pin_field');

		// Hook to display the field in user profile
		$this->loader->add_action('show_user_profile', $this, 'add_user_login_pin_field');
		$this->loader->add_action('edit_user_profile', $this, 'add_user_login_pin_field');

		$this->loader->add_action('show_user_profile', $this, 'add_user_device_id_field');
		$this->loader->add_action('edit_user_profile', $this, 'add_user_device_id_field');

		$this->loader->add_action('show_user_profile', $this, 'add_user_pos_theme_field');
		$this->loader->add_action('edit_user_profile', $this, 'add_user_pos_theme_field');



		$this->loader->add_action('show_user_profile', $this,'pinaka_add_restaurant_dropdown');
		$this->loader->add_action('edit_user_profile', $this,'pinaka_add_restaurant_dropdown');
		

		$this->loader->add_action('personal_options_update',$this, 'pinaka_save_restaurant_dropdown');
		$this->loader->add_action('edit_user_profile_update', $this,'pinaka_save_restaurant_dropdown');

		$this->loader->add_action('show_user_profile', $this,'pinaka_add_shift_dropdown');
		$this->loader->add_action('edit_user_profile', $this,'pinaka_add_shift_dropdown');
		

		$this->loader->add_action('personal_options_update',$this, 'pinaka_save_shift_dropdown');
		$this->loader->add_action('edit_user_profile_update', $this,'pinaka_save_shift_dropdown');

		$this->loader->add_action('personal_options_update',$this, 'register_device_id');
		$this->loader->add_action('edit_user_profile_update', $this,'register_device_id');

		$this->loader->add_action('personal_options_update',$this, 'register_pos_theme');
		$this->loader->add_action('edit_user_profile_update', $this,'register_pos_theme');

		
		$this->loader->add_action('wp_ajax_save_multiple_restaurants', $this, 'save_multiple_restaurants');
		$this->loader->add_action('wp_ajax_delete_single_restaurant', $this, 'delete_single_restaurant');
		$this->loader->add_action('wp_ajax_save_shift_settings', $this, 'ajax_save_shift_settings');
		////////////////////////////////////// kranthi end ////////////////////////////////////////////////

		$this->loader->add_action('wp_ajax_save_denominations', $this, 'save_denominations');
		$this->loader->add_action('wp_ajax_save_safedrop_denominations', $this, 'save_safedrop_denominations');
		$this->loader->add_action('wp_ajax_save_coins_denominations', $this, 'save_coins_denominations');
		$this->loader->add_action('wp_ajax_save_safe_denominations', $this, 'save_safe_denominations');

		$this->loader->add_action('save_post', $this, 'log_post_save', 10, 3);

		
		// this will remove the roles from the system
		
		// add_action( 'wp_loaded', function() {
		// 	$roles_to_remove = [
		// 		'eac_accountant',
		// 		'eac_manager',
		// 		'vtpos-outlet-manager',
		// 		'vtpos-cashier',
		// 		'supplier',
		// 		'employee',
		// 		'captain',
		// 		'shopkeeper'
		// 	];

		// 	foreach ( $roles_to_remove as $role ) {
		// 		if ( get_role( $role ) ) {
		// 			remove_role( $role );
		// 		}
		// 	}
		// });	
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Pinaka_POS_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
	
	/**
	 * Set the timezone for the plugin.
	 */
	function your_plugin_set_timezone() {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone ) {
			date_default_timezone_set( $timezone );
		}
	}

	/**
	 * Save the tube size, safe drop amount, and cash drawer amount settings.
	 */
	function save_tube_safe_cash(){

		if (isset($_POST['save_tube_safe_cash'])) {
			if (!isset($_POST['tube_safe_cash_nonce']) || !wp_verify_nonce($_POST['tube_safe_cash_nonce'], 'save_tube_safe_cash_settings')) {
				return;
			}
			update_option('tube_size', sanitize_text_field($_POST['tube_size']));
			update_option('safe_drop_amount', sanitize_text_field($_POST['safe_drop_amount']));
			update_option('cash_drawer_amount', sanitize_text_field($_POST['cash_drawer_amount']));

			add_action('admin_notices', function () {
				echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
			});
		}
	}

	/**
	 * log the transactions.
	 */
	function log_post_save($post_id, $post, $update) {
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		$post_type = get_post_type($post_id);
		if ($post_type === 'revision') return;

		// Get post meta
		$meta = get_post_meta($post_id);

		// Combine post object and meta
		$data = [
			'post' => $post,
			'meta' => $meta
		];

		global $wpdb;
		$wpdb->insert($wpdb->prefix . 'post_full_logs', [
			'post_id'   => $post_id,
			'post_type'=> $post_type,
			'action'    => $update ? 'update' : 'create',
			'user_id'   => get_current_user_id(),
			'data'      => json_encode($data),
		]);
	}

	
	function my_own_mime_types( $mimes ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}
	/**
	 *	 Apply mix and match discounts to the cart.
	 */

	

	// Hide specific menu items for certain roles
	public function pinaka_hide_admin_menu_items() {
		// Get the current user
		$current_user = wp_get_current_user();
		// print_r($current_user->roles);
		// die;

		// Check if the user has a specific role
		if (in_array('Employee', $current_user->roles) || in_array('administrator', $current_user->roles)) {  
			// Remove specific menu items for 'employee' role
			// remove_menu_page('edit.php');                  // Posts
			// remove_menu_page('upload.php');                // Media
			// remove_menu_page('edit.php?post_type=page');   // Pages
			// remove_menu_page('edit-comments.php');         // Comments
			// remove_menu_page('themes.php');               // Appearance/
			// remove_menu_page('plugins.php');              // Plugins
			// remove_menu_page('users.php');                // Users
			// remove_menu_page('tools.php');                // Tools
			// remove_menu_page('options-general.php');      // Settings
		}
	}


	// public function pinaka_pos_register_settings() {
	// 	register_setting('pinaka_pos_settings', 'pinaka_pos_theme_color');
	// 	add_settings_section('pinaka_pos_section', 'Appearance Settings', null, 'pinaka-pos-settings');
	// 	add_settings_field(
	// 		'pinaka_pos_theme_color',
	// 		'Theme Color',
	// 		'pinaka_pos_color_picker_callback',
	// 		'pinaka-pos-settings',
	// 		'pinaka_pos_section'
	// 	);
	// }


	public function enqueue_admin_scripts() {
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		
		wp_enqueue_script('wc-enhanced-select');
		wp_enqueue_style('woocommerce_admin_styles'); // contains select2 styling
		
		wp_enqueue_script(
			'pinaka-admin-product-search',
			plugin_dir_url(__FILE__) . '../assests/js/pinaka-product-search.js',
			['jquery', 'select2'],
			false,
			true
		);

		wp_localize_script('pinaka-admin-product-search', 'pinakaProductSearch', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('pinaka_product_search'),
		]);

		wp_enqueue_script(
			'pinaka-admin-reg-restaurant',
			plugin_dir_url(__FILE__) . '../assests/js/restaurant-script.js',
			['jquery'],
			false,
			true
		);

		wp_localize_script('pinaka-admin-reg-restaurant', 'pinakaRestaurantData', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('save_multiple_restaurants_action')
		]);

		wp_enqueue_script(
			'pinaka-delete-reg-restaurant',
			plugin_dir_url(__FILE__) . '../assests/js/restaurant-script-delete.js',
			['jquery'],
			false,
			true
		);

		wp_localize_script('pinaka-delete-reg-restaurant', 'pinakaDelRestaurantData', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('delete_single_restaurant_nonce')
		]);

		$screen = get_current_screen();
		if ( $screen && isset($screen->post_type) && $screen->post_type === 'product' ) {
			wp_enqueue_script('woocommerce_admin');
			wp_enqueue_script(
				'pinaka-dynamic-price-admin',
				plugin_dir_url(__FILE__) . '../assests/js/admin-dynamic-price.js',
				['jquery', 'woocommerce_admin'],
				time(),
				true
			);
		}
		wp_enqueue_script( 'jquery-ui-autocomplete' );

        wp_enqueue_script(
            'pinaka-addon-js',
            plugin_dir_url( __FILE__ ) . '../assests/js/modifier-addon-admin.js',
            [ 'jquery', 'jquery-ui-autocomplete' ],
            time(),
            true
        );

        $restaurants = get_option( 'pinaka_pos_restaurants', [] );
        $user_id = get_current_user_id();
        $user_restaurant = get_user_meta( $user_id, 'user_restaurant_id', true );

        wp_localize_script( 'pinaka-addon-js', 'PINAKA_ADDON', [
            'ajax' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'pinaka_addon_nonce' ),
            'restaurants' => $restaurants,
            'user_restaurant' => $user_restaurant
        ]);
	}
	function pinaka_addon_search_products()
	{
		check_ajax_referer( 'pinaka_addon_nonce', 'nonce' );

        $term = sanitize_text_field( $_POST['term'] ?? '' );

        if ( ! $term ) {
            wp_send_json( [] );
        }

        $results = [];

        $query = new WP_Query([
            'post_type'      => [ 'product', 'product_variation' ],
            'post_status'    => 'publish',
            'posts_per_page' => 30,
            's'              => $term,
            'fields'         => 'ids'
        ]);

        foreach ( $query->posts as $post_id ) {

            $product = wc_get_product( $post_id );
            if ( ! $product ) continue;

            // Direct variation
            if ( $product->is_type( 'variation' ) ) {

                if ( ! $product->is_purchasable() ) continue;

                $results[ $product->get_id() ] = [
                    'id'    => $product->get_id(),
                    'label'=> $product->get_name()
                ];
                continue;
            }

            // Variable → expand variations
            if ( $product->is_type( 'variable' ) ) {

                foreach ( $product->get_children() as $variation_id ) {

                    if ( isset( $results[ $variation_id ] ) ) continue;

                    $variation = wc_get_product( $variation_id );
                    if ( ! $variation || ! $variation->is_purchasable() ) continue;

                    $results[ $variation_id ] = [
                        'id'    => $variation->get_id(),
                        'label'=> $variation->get_name()
                    ];
                }
                continue;
            }

            // Simple
            if ( $product->is_type( 'simple' ) ) {
                $results[ $product->get_id() ] = [
                    'id'    => $product->get_id(),
                    'label'=> $product->get_name()
                ];
            }
        }

        wp_send_json( array_values( $results ) );
	}
	function pinaka_addon_save_addons()
	{
		check_ajax_referer( 'pinaka_addon_nonce', 'nonce' );

        $rows = $_POST['rows'] ?? [];
        $category_addon_map = get_option( 'pinaka_category_addon_map', [] );
		$category_modifier_map = get_option( 'pinaka_category_modifier_map', [] );
		// $ex_args = [
		// 	'post_type'      => 'modifier',
		// 	'post_status'   => 'publish',
		// 	'posts_per_page'=> -1
		// ];
		// $args['meta_query'][] = [
		// 	'key'   => 'modifier_type',
		// 	'value' => 'add-on'
		// ];
		// $query = new WP_Query( $ex_args );
		// $ex_rows = [];
		// foreach ( $query->posts as $post ) {
		// 	$rest_iid = get_post_meta( $post->ID, 'restaurant_id', true );
		// 	$modifier_type = get_post_meta( $post->ID, 'modifier_type', true );
		// 	$ex_rows[$rest_iid][$modifier_type][$post->post_title] = $post->ID;
		// }
        foreach ( $rows as $row ) {

            $addon_id     = absint( $row['addon_id'] ?? 0 );
            $restaurant_id = absint( $row['restaurant_id'] ?? 0 );
            $product_id  = absint( $row['product_id'] ?? 0 );
            $addon_name  = sanitize_text_field( $row['addon_name'] ?? '' );
            $price       = wc_format_decimal( $row['price'] ?? 0 );
			$addon_type  = sanitize_text_field( $row['type'] ?? '' );
            if ( ! $restaurant_id || ! $product_id || ! $addon_name || !$addon_type ) {
                continue;
            }

            // Categories
            $terms = wp_get_post_terms( $product_id, 'product_cat' );
            $category_ids = [];
            $subcategory_ids = [];

            foreach ( $terms as $term ) {
                if ( $term->parent === 0 ) {
                    $category_ids[] = $term->term_id;
                } else {
                    $subcategory_ids[] = $term->term_id;
                }
            }
			// if(isset($ex_rows[$addon_name]))
			// {
			// 	$addon_id = $ex_rows[$addon_name];
			// }
            // Insert or Update
            if ( $addon_id ) {
                wp_update_post([
                    'ID' => $addon_id,
                    'post_title' => $addon_name
                ]);
            } else {
                $addon_id = wp_insert_post([
                    'post_title' => $addon_name,
                    'post_type' => 'modifier',
                    'post_status' => 'publish'
                ]);
            }

            update_post_meta( $addon_id, 'restaurant_id', $restaurant_id );
            update_post_meta( $addon_id, 'modifier_type', $addon_type );
            update_post_meta( $addon_id, 'modifier_price', $price );
            update_post_meta( $addon_id, 'category_ids', array_values( array_unique( $category_ids ) ) );
            update_post_meta( $addon_id, 'subcategory_ids', array_values( array_unique( $subcategory_ids ) ) );
			
            $existing_product_ids = get_post_meta( $addon_id, 'product_ids', true );

			if ( ! is_array( $existing_product_ids ) ) {
				$existing_product_ids = [];
			}

			$existing_product_ids[] = $product_id;

			update_post_meta(
				$addon_id,
				'product_ids',
				array_values( array_unique( $existing_product_ids ) )
			);

            foreach ( array_unique( array_merge( $subcategory_ids, $category_ids ) ) as $cat_id ) {
				$category_addon_map[ $cat_id ] = $category_addon_map[ $cat_id ] ?? [];
				$category_addon_map[ $cat_id ][] = $addon_id;
			}
			foreach ( array_unique( array_merge( $subcategory_ids, $category_ids ) ) as $cat_id ) {
				$category_modifier_map[ $cat_id ] = $category_modifier_map[ $cat_id ] ?? [];
				$category_modifier_map[ $cat_id ][] = $addon_id;
			}
			
        }

        // Deduplicate map
        foreach ( $category_addon_map as $cat_id => $addon_ids ) {
            $category_addon_map[ $cat_id ] = array_values( array_unique( $addon_ids ) );
        }

        update_option( 'pinaka_category_addon_map', $category_addon_map, false );

		foreach ( $category_modifier_map as $cat_id => $modifier_ids ) {
            $category_modifier_map[ $cat_id ] = array_values( array_unique( $modifier_ids ) );
        }
		update_option( 'pinaka_category_modifier_map', $category_modifier_map, false );
        wp_send_json_success( 'Add-ons saved successfully' );
	}
	function pinaka_modifier_get_addons()
	{
		check_ajax_referer( 'pinaka_addon_nonce', 'nonce' );
		$args = [
			'post_type'      => 'modifier',
			'post_status'   => 'publish',
			'posts_per_page'=> -1
		];
		// $args['meta_query'][] = [
		// 	'key'   => 'modifier_type',
		// 	'value' => 'add-on'
		// ];
		$query = new WP_Query( $args );
		$rows = [];
		$cnt = 0;
		foreach ( $query->posts as $post ) {
			$cnt++;
			$product_ids = (array) get_post_meta( $post->ID, 'product_ids', true );
			$product_id  = $product_ids[0] ?? 0;
			$product     = $product_id ? wc_get_product( $product_id ) : null;
			$rows[] = [
				'count'         => $cnt,
				'addon_id'       => $post->ID,
				'restaurant_id' => get_post_meta( $post->ID, 'restaurant_id', true ),
				'product_id'    => $product_id,
				'product_name' => $product ? $product->get_name() : '',
				'addon_name'   => $post->post_title,
				'type' => get_post_meta( $post->ID, 'modifier_type', true ),
				'price'        => get_post_meta( $post->ID, 'modifier_price', true )
			];
		}

		wp_send_json_success( $rows );
	}

	function pinaka_product_search_callback() {
		check_ajax_referer('pinaka_product_search', 'security');

		$term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => -1,
		];

		$products = get_posts($args);
		$results = [];

		foreach ($products as $product) {
			$results[] = [
				'id'   => $product->ID,
				'text' => $product->post_title,
			];
		}

		wp_send_json($results);
	}
	
	function update_admin_color_scheme() {
		// Security Check
		check_ajax_referer( 'update_admin_color_scheme', 'color_scheme_nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'User not authenticated' ) );
		}

		$user_id = get_current_user_id();
		$new_color = sanitize_text_field( $_POST['admin_color'] ?? '' );

		if ( empty( $new_color ) ) {
			wp_send_json_error( array( 'message' => 'Invalid color scheme selected' ) );
		}

		update_user_option( $user_id, 'admin_color', $new_color );

		wp_send_json_success( array( 'message' => 'Color scheme updated successfully!' ) );
	}
	public function delete_receipt_logo()
	{
		check_ajax_referer('delete_receipt_logo_nonce', '_wpnonce');

		// Instead of deleting, set it to empty
		update_option('pinaka_receipt_logo', '');

		wp_send_json_success([
			'message' => 'Receipt logo cleared successfully.'
		]);
	}
	function create_custom_role(){
		check_ajax_referer('create_custom_role_action', 'create_custom_role_nonce');

		$role_key = sanitize_key($_POST['new_role_key'] ?? '');
		$role_name = sanitize_text_field($_POST['new_role_name'] ?? '');

		if (empty($role_key) || empty($role_name)) {
			wp_send_json_error(['message' => 'Both Role Key and Role Name are required.']);
		}

		if (get_role($role_key)) {
			wp_send_json_error(['message' => 'Role already exists.']);
		}

		// add_role($role_key, $role_name, 
		// 	[
		// 	'read' => true,
		// 	'level_0'    => true, // Grants access to wp-admin
    	// 	'edit_posts' => true, // Optional: allows minimal dashboard functionality
		// 	'manage_options' => true, // <-- This is the key
		// 	]
		// );
		$capabilities = [
			// Basic WordPress
			'read'                     => true,
			'level_0'                  => true,
			'edit_posts'               => true,
			'upload_files'             => true,

			// WooCommerce Core Capabilities
			'manage_woocommerce'       => true,
			'view_woocommerce_reports' => true,

			// WooCommerce REST API Access
			'read_wc_api'              => true,
			'edit_wc_api'              => true,
			'delete_wc_api'            => true,
			'read_private_shop_orders' => true,
			'read_private_products'    => true,

			// Product Capabilities
			'edit_products'            => true,
			'read_products'            => true,
			'delete_products'          => true,
			'publish_products'         => true,
			'edit_published_products'  => true,
			'delete_published_products'=> true,
			'edit_product_terms'       => true,
			'assign_product_terms'     => true,
			'manage_product_terms'     => true,

			// Order Capabilities
			'edit_shop_orders'         => true,
			'read_shop_order'          => true,
			'delete_shop_orders'       => true,
			'publish_shop_orders'      => true,
			'edit_others_shop_orders'  => true,

			// Customer/User Management
			'list_users'               => true,
		];
		$role_result = add_role($role_key, $role_name, $capabilities);

		if (!$role_result) {
			wp_send_json_error(['message' => 'Role creation failed.']);
		}
		wp_send_json_success(['message' => 'Role created successfully.', 'role' => $role_key]);
	}

	function save_pinaka_pos_menu_settings() {
		// Verify nonce for security
		if (!isset($_POST['pinaka_pos_menu_nonce']) || !wp_verify_nonce($_POST['pinaka_pos_menu_nonce'], 'pinaka_pos_menu_settings_action')) {
			wp_send_json_error(['message' => 'Security check failed!'], 403);
		}

		// Ensure the user has admin privileges
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized action'], 403);
		}

		// Ensure role is passed and valid
		if (empty($_POST['role']) || !isset(wp_roles()->roles[$_POST['role']])) {
			wp_send_json_error(['message' => 'Invalid or missing role'], 400);
		}

		$role = sanitize_key($_POST['role']);
		$selected_menus = isset($_POST['menu_items']) ? array_map('sanitize_text_field', $_POST['menu_items']) : [];

		// Save role-specific menu settings
		$all_settings = get_option('pinaka_pos_menu_settings', []);
		$all_settings[$_POST['role']] = $selected_menus;
		update_option('pinaka_pos_menu_settings', $all_settings);

		wp_send_json_success(['message' => 'Menu settings updated successfully!']);
	}

	/**
	 * Save denominations.
	 */
	public function save_denominations() {
		check_ajax_referer('save_denominations_action', 'save_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_denominations', $structured);

		wp_send_json_success(['message' => 'Denominations saved successfully.']);
	}

	public function save_safedrop_denominations() {
		check_ajax_referer('save_safedrop_denominations_action', 'save_safedrop_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denominations = isset($_POST['safedrop_denominations']) ? $_POST['safedrop_denominations'] : [];
		$limits        = isset($_POST['safedrop_denominations_limit']) ? $_POST['safedrop_denominations_limit'] : [];
		$symbols       = isset($_POST['safedrop_denominations_symbol']) ? $_POST['safedrop_denominations_symbol'] : [];

		$structured_data = [];

		// Ensure all arrays are the same length
		if (count($denominations) === count($limits) && count($limits) === count($symbols)) {
			for ($i = 0; $i < count($denominations); $i++) {
				$denom  = floatval($denominations[$i]);  // ✅ Allow decimal values
				$limit  = intval($limits[$i]);           // Limit remains integer
				$symbol = sanitize_text_field($symbols[$i]);

				// ✅ Allow 0.01 and above, reject zero or negative
				if ($denom < 0.01) {
					continue;
				}

				$structured_data[] = [
					'denom'  => round($denom, 2),  // Optional: Round to 2 decimal places
					'tube_limit'  => $limit,
					'symbol' => $symbol
				];
			}

			// ✅ Sort by value ascending
			usort($structured_data, function($a, $b) {
				return $a['value'] <=> $b['value'];
			});
		}

		update_option('pinaka_pos_safedrop_denominations', $structured_data);

		wp_send_json_success(['message' => 'Safe Drop Denominations saved successfully.']);
	}

	/**
	 * Save coins denominations.
	 */
	public function save_coins_denominations() {

		check_ajax_referer('save_coins_denominations_action', 'save_coins_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['coins_denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['coins_denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_coins_denominations', $structured);
		wp_send_json_success(['message' => 'Coins Denominations saved successfully.']);
	}
	/**
	 * Save safe denominations.
	 */
	public function save_safe_denominations() 
	{
		check_ajax_referer('save_safe_denominations_action', 'save_safe_denominations_nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		$denoms = $_POST['safe_denominations']['denom'] ?? [];  // <-- this matches your input
		$images = $_POST['safe_denominations']['image'] ?? [];

		$structured = [];

		for ($i = 0; $i < count($denoms); $i++) {
			$denom = sanitize_text_field($denoms[$i]);
			$image = esc_url_raw($images[$i] ?? '');

			if ($denom !== '') {
				$structured[] = [
					'denom' => $denom,
					'image' => $image,
				];
			}
		}

		usort($structured, function ($a, $b) {
			return strcmp($a['denom'], $b['denom']);
		});

		update_option('pinaka_pos_safe_denominations', $structured);
		wp_send_json_success(['message' => 'Coins Denominations saved successfully.']);
	}
	////////////////////////////////////// kranthi start ////////////////////////////////////////////////

	// public function save_multiple_restaurants() 
	// {
	// 	if (!current_user_can('manage_options')) {
	// 		wp_send_json_error(['message' => 'Unauthorized']);
	// 	}

	// 	check_ajax_referer('save_multiple_restaurants_action');

	// 	$slugs     = $_POST['restaurant_slug'] ?? [];
	// 	$names     = $_POST['restaurant_name'] ?? [];
	// 	$addresses = $_POST['restaurant_address'] ?? [];

	// 	// Validate input length consistency
	// 	if (count($slugs) !== count($names) || count($slugs) !== count($addresses)) {
	// 		wp_send_json_error(['message' => 'Mismatched input rows']);
	// 	}

	// 	$restaurants = get_option('pinaka_pos_restaurants', []);
	// 	if (!is_array($restaurants)) $restaurants = [];

	// 	// Collect existing names for duplicate check
	// 	$existing_names = array_map('strtolower', array_column($restaurants, 'name'));
	// 	$max_id = count($restaurants) ? max(array_column($restaurants, 'id')) : 0;

	// 	foreach ($slugs as $i => $slug) {
	// 		$slug    = sanitize_title($slug);
	// 		$name    = sanitize_text_field($names[$i]);
	// 		$address = sanitize_textarea_field($addresses[$i]);

	// 		if (in_array(strtolower($name), $existing_names)) {
	// 			continue; // Skip duplicate names
	// 		}

	// 		$restaurants[] = [
	// 			'id'      => ++$max_id,
	// 			'slug'    => $slug,
	// 			'name'    => $name,
	// 			'address' => $address,
	// 		];

	// 		$existing_names[] = strtolower($name); // Add to avoid multiple entries in same batch
	// 	}

	// 	update_option('pinaka_pos_restaurants', $restaurants);
	// 	wp_send_json_success(['message' => 'New restaurants saved successfully.']);
	// }
	public function save_multiple_restaurants() 
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		// check_ajax_referer('save_multiple_restaurants_action');
		
		$slugs     = $_POST['restaurant_slug'] ?? [];
		$names     = $_POST['restaurant_name'] ?? [];
		$addresses = $_POST['restaurant_address'] ?? [];

		// Validate input length consistency
		if (count($slugs) !== count($names) || count($slugs) !== count($addresses)) {
			wp_send_json_error(['message' => 'Mismatched input rows']);
		}

		$restaurants = get_option('pinaka_pos_restaurants', []);
		if (!is_array($restaurants)) $restaurants = [];

		// Existing values for duplicate check
		$existing_slugs = array_map('strtolower', array_column($restaurants, 'slug'));
		$existing_names = array_map('strtolower', array_column($restaurants, 'name'));
		$max_id = count($restaurants) ? max(array_column($restaurants, 'id')) : 0;

		$new_slugs = [];
		$new_names = [];

		foreach ($slugs as $i => $slug_raw) {
			$slug    = sanitize_title($slug_raw);
			$name    = sanitize_text_field($names[$i]);
			$address = sanitize_textarea_field($addresses[$i]);

			if (empty($slug) || empty($name)) {
				wp_send_json_error(['message' => 'Slug and name cannot be empty.']);
			}

			$slug_lower = strtolower($slug);
			$name_lower = strtolower($name);

			// Check duplicate slug or name within form
			if (in_array($slug_lower, $new_slugs)) {
				wp_send_json_error(['message' => "Duplicate slug '{$slug}' in submitted list."]);
			}
			if (in_array($name_lower, $new_names)) {
				wp_send_json_error(['message' => "Duplicate name '{$name}' in submitted list."]);
			}

			// Check duplicate slug or name in existing data
			if (in_array($slug_lower, $existing_slugs)) {
				wp_send_json_error(['message' => "Slug '{$slug}' already exists. Use a different slug."]);
			}
			if (in_array($name_lower, $existing_names)) {
				wp_send_json_error(['message' => "Name '{$name}' already exists. Use a different name."]);
			}

			$new_slugs[] = $slug_lower;
			$new_names[] = $name_lower;

			// Store the new restaurant
			$restaurants[] = [
				'id'      => ++$max_id,
				'slug'    => $slug,
				'name'    => $name,
				'address' => $address,
			];
		}

		update_option('pinaka_pos_restaurants', $restaurants);
		wp_send_json_success(['message' => 'New restaurants saved successfully.']);
	}

	public function delete_single_restaurant() 
	{
		check_ajax_referer('delete_single_restaurant_nonce');

		$restaurant_id = intval($_POST['restaurant_id'] ?? 0);
		if (!$restaurant_id) {
			wp_send_json_error(['message' => 'Invalid restaurant ID.']);
		}

		$restaurants = get_option('pinaka_pos_restaurants', []);
		$found = false;

		foreach ($restaurants as $index => $restaurant) {
			if ($restaurant['id'] == $restaurant_id) {
				unset($restaurants[$index]);
				$found = true;
				break;
			}
		}

		if (!$found) {
			wp_send_json_error(['message' => 'Restaurant not found.']);
		}

		$restaurants = array_values($restaurants); // Re-index
		update_option('pinaka_pos_restaurants', $restaurants);

		wp_send_json_success(['message' => 'Restaurant deleted successfully.']);
	}

	// public function ajax_save_shift_settings() 
	// {
	// 	if (!current_user_can('manage_options')) {
	// 		wp_send_json_error(['message' => 'Unauthorized']);
	// 	}

	// 	check_ajax_referer('save_shift_settings', 'shift_settings_nonce');

	// 	// $current_user = wp_get_current_user();
	// 	// $user_roles = $current_user->roles;
	// 	// $saved_permissions = get_option('pinaka_roles_permissions', []);
	// 	// $has_people_permission = false;
	// 	// foreach ($user_roles as $role) 
	// 	// {
	// 	// 	if($role=="administrator")
	// 	// 	{
	// 	// 		$role = "admin";
	// 	// 	}
	// 	// 	if (!empty($saved_permissions[$role]) && in_array('People', $saved_permissions[$role])) {
	// 	// 		$has_people_permission = true;
	// 	// 		break;
	// 	// 	}
	// 	// }

	// 	// if ($has_people_permission) 
	// 	// {
	// 		$shift_count = absint($_POST['shift_count']);
	// 		$expected_hours = absint($_POST['total_shift_hours']);
	// 		$shifts = [];
	// 		$names = [];
	// 		$starts = [];
	// 		$ends = [];
	// 		$errors = [];

	// 		for ($i = 0; $i < $shift_count; $i++) {
	// 			$name  = sanitize_text_field($_POST["shift_name_$i"] ?? '');
	// 			$start = sanitize_text_field($_POST["shift_start_$i"] ?? '');
	// 			$end   = sanitize_text_field($_POST["shift_end_$i"] ?? '');

	// 			if (!$name) {
	// 				$errors[] = "Shift ".($i+1)." name is required.";
	// 			}
	// 			if (!$start || !$end) {
	// 				$errors[] = "Shift ".($i+1)." times are required.";
	// 			}

	// 			$names[]  = strtolower($name);
	// 			$starts[] = $start;
	// 			$ends[]   = $end;

	// 			$shifts[] = [
	// 				'shift_id' => $i+1,
	// 				'name'     => $name,
	// 				'start'    => $start,
	// 				'end'      => $end
	// 			];
	// 		}

	// 		if (count(array_unique($names)) < count($names)) {
	// 			$errors[] = "Duplicate shift names found.";
	// 		}
	// 		if (count(array_unique($starts)) < count($starts)) {
	// 			$errors[] = "Duplicate start times found.";
	// 		}
	// 		if (count(array_unique($ends)) < count($ends)) {
	// 			$errors[] = "Duplicate end times found.";
	// 		}

	// 		if (empty($errors) && !$this->validate_shifts_cover_hours($shifts, $shift_count, $expected_hours)) {
	// 			$errors[] = "Shifts must exactly cover {$expected_hours} hours with no overlaps.";
	// 		}

	// 		if (empty($errors)) {
	// 			update_option('pinaka_pos_shift_settings', [
	// 				'shift_count' => $shift_count,
	// 				'total_hours' => $expected_hours,
	// 				'shifts'      => $shifts
	// 			]);
	// 			wp_send_json_success([
	// 				'message' => 'Shift settings saved successfully.',
	// 				'shifts'  => $shifts
	// 			]);
	// 		} else {
	// 			wp_send_json_error([
	// 				'message' => implode('<br>', array_map('esc_html', $errors))
	// 			]);
	// 		}

	// 	// }
	// 	// else
	// 	// {
	// 	// 	wp_send_json_error(['message' => 'user has no permission to manage shifts']);
	// 	// }
	// }
	
	public function ajax_save_shift_settings() 
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'Unauthorized']);
		}

		check_ajax_referer('save_shift_settings', 'shift_settings_nonce');

		// $current_user = wp_get_current_user();
		// $user_roles = $current_user->roles;
		// $saved_permissions = get_option('pinaka_roles_permissions', []);
		// $has_people_permission = false;
		// foreach ($user_roles as $role) 
		// {
		// 	if($role=="administrator")
		// 	{
		// 		$role = "admin";
		// 	}
		// 	if (!empty($saved_permissions[$role]) && in_array('People', $saved_permissions[$role])) {
		// 		$has_people_permission = true;
		// 		break;
		// 	}
		// }

		// if ($has_people_permission) 
		// {
			$shift_count = absint($_POST['shift_count']);
			$expected_hours = $_POST['total_shift_hours'];
			$shifts = [];
			$names = [];
			$starts = [];
			$ends = [];
			$errors = [];

			for ($i = 0; $i < $shift_count; $i++) {
				$name  = sanitize_text_field($_POST["shift_name_$i"] ?? '');
				$start = sanitize_text_field($_POST["shift_start_$i"] ?? '');
				$end   = sanitize_text_field($_POST["shift_end_$i"] ?? '');

				if (!$name) {
					$errors[] = "Shift ".($i+1)." name is required.";
				}
				if (!$start || !$end) {
					$errors[] = "Shift ".($i+1)." times are required.";
				}

				$names[]  = strtolower($name);
				$starts[] = $start;
				$ends[]   = $end;

				$shifts[] = [
					'shift_id' => $i+1,
					'name'     => $name,
					'start'    => $start,
					'end'      => $end
				];
			}

			if (count(array_unique($names)) < count($names)) {
				$errors[] = "Duplicate shift names found.";
			}
			if (count(array_unique($starts)) < count($starts)) {
				$errors[] = "Duplicate start times found.";
			}
			if (count(array_unique($ends)) < count($ends)) {
				$errors[] = "Duplicate end times found.";
			}
			 
			if (empty($errors) && !$this->validate_shifts_cover_hours($shifts, $shift_count, $expected_hours)) {
				$errors[] = "Shifts must exactly cover {$expected_hours} hours with no overlaps.";
			}

			if (empty($errors)) {
				update_option('pinaka_pos_shift_settings', [
					'shift_count' => $shift_count,
					'total_hours' => $expected_hours,
					'shifts'      => $shifts
				]);
				wp_send_json_success([
					'message' => 'Shift settings saved successfully.',
					'shifts'  => $shifts
				]);
			} else {
				wp_send_json_error([
					'message' => implode('<br>', array_map('esc_html', $errors))
				]);
			}

		// }
		// else
		// {
		// 	wp_send_json_error(['message' => 'user has no permission to manage shifts']);
		// }
	}
	// private function validate_shifts_cover_hours($shifts, $shift_count, $expected_hours)
	// {
	// 	$expected_minutes = $expected_hours * 60;
	// 	$total_minutes = 0;
	// 	$minutes_covered = array_fill(0, 1440, false); // each minute of the day

	// 	foreach ($shifts as $shift) {
	// 		if (empty($shift['start']) || empty($shift['end'])) {
	// 			return false; // incomplete data
	// 		}

	// 		list($start_h, $start_m) = array_pad(explode(':', $shift['start']), 2, 0);
	// 		list($end_h, $end_m)     = array_pad(explode(':', $shift['end']), 2, 0);

	// 		$start_minute = intval($start_h) * 60 + intval($start_m);
	// 		$end_minute   = intval($end_h) * 60 + intval($end_m);

	// 		if ($end_minute <= $start_minute) {
	// 			$end_minute += 1440; // wrap over midnight
	// 		}

	// 		$duration = $end_minute - $start_minute;
	// 		$total_minutes += $duration;

	// 		for ($i = $start_minute; $i < $end_minute; $i++) {
	// 			$idx = $i % 1440;
	// 			if ($minutes_covered[$idx]) {
	// 				return false; // overlap detected
	// 			}
	// 			$minutes_covered[$idx] = true;
	// 		}
	// 	}

	// 	return $total_minutes === $expected_minutes;
	// }
	private function validate_shifts_cover_hours($shifts, $shift_count, $expected_hours)
	{
		$expected_minutes = $this->hours_to_minutes($expected_hours);
		$total_minutes = 0;
		$minutes_covered = array_fill(0, 1440, false); // each minute of the day

		foreach ($shifts as $shift) {
			if (empty($shift['start']) || empty($shift['end'])) {
				return false; // incomplete data
			}

			$start_time = strtotime($shift['start']);
			$end_time   = strtotime($shift['end']);

			if ($start_time === false || $end_time === false) {
				return false; // invalid time format
			}

			$start_minute = (int) date('H', $start_time) * 60 + (int) date('i', $start_time);
			$end_minute   = (int) date('H', $end_time) * 60 + (int) date('i', $end_time);

			if ($end_minute <= $start_minute) {
				$end_minute += 1440; // wrap over midnight
			}

			$duration = $end_minute - $start_minute;
			$total_minutes += $duration;

			for ($i = $start_minute; $i < $end_minute; $i++) {
				$idx = $i % 1440;
				if ($minutes_covered[$idx]) {
					return false; // overlap detected
				}
				$minutes_covered[$idx] = true;
			}
		}

		return abs($total_minutes - $expected_minutes) < 1;
	}
	public function hours_to_minutes($hours) {
		$hours = trim((string) $hours);
		// Handle "HH:MM" or "H:MM"
		if (strpos($hours, ':') !== false) {
			list($h, $m) = array_pad(explode(':', $hours, 2), 2, '0');
			return (intval($h) * 60) + intval($m);
		}

		// Handle "2.20" format (hours.minutes)
		if (strpos($hours, '.') !== false) {
			list($h, $m) = array_pad(explode('.', $hours, 2), 2, '0');
			// If user entered "2.20", interpret second part as minutes (not decimal)
			if (strlen($m) === 2 && intval($m) <= 59) {
				return (intval($h) * 60) + intval($m);
			}
			// Else treat as decimal hours
			return round(floatval($hours) * 60);
		}

		// If only number (e.g. 3)
		return intval($hours) * 60;
	}
	/**
	 * Register the emp_login_pin user meta field.
	 */
	function register_emp_login_pin_meta() {
		register_meta('user', 'emp_login_pin', array(
			'type'         => 'string',
			'description'  => 'Employee Login PIN (6-digit)',
			'single'       => true,
			'show_in_rest' => true, // Allows API access
			'auth_callback' => function() {
				return current_user_can('edit_users'); // Restrict access
			}
		));
	}


	/**
	 * Register the device meta field.
	 */
	function register_device_id_meta() {
		register_meta('user', 'user_device_id', array(
			'type'         => 'string',
			'description'  => 'Device ID',
			'single'       => true,
			'show_in_rest' => true, // Allows API access
			'auth_callback' => function() {
				return current_user_can('edit_users'); // Restrict access
			}
		));
	}

	/**
	 * Register the POS Theme meta field.
	 */
	function register_pos_theme_meta() {
		register_meta('user', 'user_pos_theme', array(
			'type'         => 'string',
			'description'  => 'Pos Theme ',
			'single'       => true,
			'show_in_rest' => true, // Allows API access
			'auth_callback' => function() {
				return current_user_can('edit_users'); // Restrict access
			}
		));
	}



	/**
	 * Register the emp_login_pin user meta field.
	 */
	function register_user_login_pin_meta() {
		register_meta('user', 'user_login_pin', array(
			'type'         => 'string',
			'description'  => 'User Login PIN (4-digit)',
			'single'       => true,
			'show_in_rest' => true, // Allows API access
			'auth_callback' => function() {
				return current_user_can('edit_users'); // Restrict access
			}
		));
	}
	function register_restaurants()
	{
		register_meta('user', 'user_restaurant_id', [
			'type'         => 'integer',
			'description'  => 'Assigned Restaurant ID',
			'single'       => true,
			'show_in_rest' => [
				'schema' => [
					'type'    => 'integer',
				]
			],
			'auth_callback' => function () {
				return current_user_can('edit_users');
			},
		]);
	}


	function register_shifts()
	{
		register_meta('user', 'user_shift_id', [
			'type'         => 'integer',
			'description'  => 'Assigned Shift ID',
			'single'       => true,
			'show_in_rest' => [
				'schema' => [
					'type'    => 'integer',
				]
			],
			'auth_callback' => function () {
				return current_user_can('edit_users');
			},
		]);
	}
	
	function pinaka_add_restaurant_dropdown($user) 
	{
		// if (!$user) return;
		// // Get user roles
		// $roles = (array) $user->roles;
		// // Check if user is administrator or merchant
		// if (in_array('administrator', $roles) || in_array('merchant', $roles)) 
		// {
			$restaurants = get_option('pinaka_pos_restaurants', []);
			$current_restaurant = get_user_meta($user->ID, 'user_restaurant_id', true);
			?>
			<h3>Assign Restaurant </h3>
			<table class="form-table">
				<tr>
					<th><label for="user_restaurant_id">Restaurant</label></th>
					<td>
						<select name="user_restaurant_id" id="user_restaurant_id" class="regular-text">
							<option value="">-- Select Restaurant --</option>
							<?php foreach ($restaurants as $restaurant): ?>
								<option value="<?php echo esc_attr($restaurant['id']); ?>" <?php selected($current_restaurant, $restaurant['id']); ?>>
									<?php echo esc_html($restaurant['name']); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">Select the restaurant this user is assigned to.</p>
					</td>
				</tr>
			</table>
			<?php
		// }
	}
	
	function pinaka_save_restaurant_dropdown($user_id) 
	{
		if (!current_user_can('edit_user', $user_id)) return;

		// if (isset($_POST['user_restaurant_id'])) {
		// 	update_user_meta($user_id, 'user_restaurant_id', intval($_POST['user_restaurant_id']));
		// }
		if (isset($_POST['user_restaurant_id'])) 
		{
			$new_restaurant_id = intval($_POST['user_restaurant_id']);
			$old_restaurant_id = intval(get_user_meta($user_id, 'user_restaurant_id', true));

			// Only log if changed
			if ($new_restaurant_id !== $old_restaurant_id) {
				update_user_meta($user_id, 'user_restaurant_id', $new_restaurant_id);

				$restaurants = get_option('pinaka_pos_restaurants', []);
				$old_name = 'None';
				$new_name = 'None';

				foreach ($restaurants as $res) 
				{
					if (!isset($res['id'], $res['name'])) continue;

					$res_id = intval($res['id']);

					if ($res_id === $old_restaurant_id) {
						$old_name = $res['name'];
					}
					if ($res_id === $new_restaurant_id) {
						$new_name = $res['name'];
					}
				}

				$user = get_userdata($user_id);
				$log_entry = sprintf(
					'User %s (ID: %d) changed assigned restaurant from "%s" (ID: %d) to "%s" (ID: %d) on %s',
					$user->user_login,
					$user_id,
					$old_name,
					$old_restaurant_id ?: 0,
					$new_name,
					$new_restaurant_id ?: 0,
					current_time('mysql')
				);

				$logs = get_option('pinaka_restaurant_change_logs', []);
				$logs[] = $log_entry;
				update_option('pinaka_restaurant_change_logs', $logs);
			}
		}
	}

	function pinaka_add_shift_dropdown($user) 
	{
		// if (!$user) return;
		// // Get user roles
		// $roles = (array) $user->roles;

		// $saved_permissions = get_option('pinaka_roles_permissions', []);
		// $has_people_permission = false;
		// foreach ($roles as $role) 
		// {
		// 	if($role=="administrator")
		// 	{
		// 		$role = "admin";
		// 	}
		// 	if (!empty($saved_permissions[$role]) && in_array('People', $saved_permissions[$role])) {
		// 		$has_people_permission = true;
		// 		break;
		// 	}
		// }

		// if ($has_people_permission) 
		// {
			// Get shift settings
			$shift_settings = get_option('pinaka_pos_shift_settings', [
				'shift_count' => 1,
				'total_hours' => 24,
				'shifts' => [['shift_id' => 1, 'name' => '', 'start' => '', 'end' => '']]
			]);
			$current_shift_id = get_user_meta($user->ID, 'user_shift_number', true);
			?>
			<h3>Assign Shift</h3>
			<table class="form-table">
				<tr>
					<th><label for="user_shift_id">Shift</label></th>
					<td>
						<select name="user_shift_id" id="user_shift_id" class="regular-text">
							<option value="">-- Select Shift --</option>
							<?php foreach ($shift_settings['shifts'] as $shift): ?>
								<?php 
									$shift_id = $shift['shift_id'] ?? null;
									if ($shift_id && !empty($shift['start']) && !empty($shift['end'])):
										$label = esc_html($shift['name']) . ' (' . esc_html($shift['start']) . ' - ' . esc_html($shift['end']) . ')';
								?>
									<option value="<?= esc_attr($shift_id) ?>" <?= selected($current_shift_id, $shift_id, false) ?>>
										<?= $label ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
						<p class="description">Select the shift this user is assigned to.</p>
					</td>
				</tr>
			</table>
			<?php
		// }
	}
	
	function pinaka_save_shift_dropdown($user_id) 
	{
		if (!current_user_can('edit_user', $user_id)) return;

		if (isset($_POST['user_shift_id']) && $_POST['user_shift_id']!=='') {
			update_user_meta($user_id, 'user_shift_number', intval($_POST['user_shift_id']));
		}
	}
	function register_device_id($user_id)
	{
		if (!current_user_can('edit_user', $user_id)) return;	
		
		if (isset($_POST['user_device_id']) && $_POST['user_device_id']!=='') {
			update_user_meta($user_id, 'user_device_id', $_POST['user_device_id']);
		}
	}

	function register_pos_theme($user_id)
	{
		if (!current_user_can('edit_user', $user_id)) return;	
		
		if (isset($_POST['user_pos_theme']) && $_POST['user_pos_theme']!=='') {
			update_user_meta($user_id, 'user_pos_theme', $_POST['user_pos_theme']);
		}
	}


	/**
	 * emp_login_pin field to the user profile page (Only Numbers Allowed).
	 */
	function add_emp_login_pin_field($user) 
	{
		// if (!$user) return;
		// // Get user roles
		// $roles = (array) $user->roles;
		// // Check if user is administrator or merchant
		// if (in_array('administrator', $roles) || in_array('merchant', $roles)) 
		// {
			?>
			<h3>Employee Login PIN</h3>
			<table class="form-table">
				<tr>
					<th><label for="emp_login_pin">Login PIN (6-digit)</label></th>
					<td>
						<input type="number" name="emp_login_pin" id="emp_login_pin" value="<?php echo esc_attr(get_user_meta($user->ID, 'emp_login_pin', true)); ?>" class="regular-text" minlength="6" maxlength="6" pattern="\d{6}">
						<p class="description">Enter a 6-digit numeric PIN for employee login.</p>
					</td>
				</tr>
			</table>
			<script>
				document.getElementById('emp_login_pin').addEventListener('input', function (e) {
					e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6); // Only allow numbers, max length 6
				});
			</script>
			<?php
		// }
	}
	/**
	 * Save emp_login_pin field when updating user profile (Only Numbers Allowed).
	 */
	function save_emp_login_pin_field($user_id) 
	{
		// Check user permissions
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}
		
		// Validate PIN (must be exactly 6 digits and numeric)
		if (!empty($_POST['emp_login_pin']) && preg_match('/^\d{6}$/', $_POST['emp_login_pin'])) {
			$new_emp_pin = sanitize_text_field($_POST['emp_login_pin']);

			// Check if PIN is already used by another user
			$emp_pin_args = [
				'meta_key'     => 'emp_login_pin',
				'meta_value'   => $new_emp_pin,
				'exclude'      => [$user_id],
				'number'       => 1,
				'count_total'  => false,
				'fields'       => 'ID'
			];
		
			$emp_pin_users = get_users($emp_pin_args);

			if (empty($emp_pin_users)) 
			{
				update_user_meta($user_id, 'emp_login_pin', sanitize_text_field($_POST['emp_login_pin']));
			}
			else
			{
				update_user_meta(get_current_user_id(), '_emp_pin_error_flag', true);
			}
			
		} else {
			delete_user_meta($user_id, 'emp_login_pin'); // Remove if invalid
		}
	}
	/**
	 * user_login_pin field to the user profile page (Only Numbers Allowed).
	 */
	function add_user_login_pin_field($user) 
	{
		// if (!$user) return;
		// // Get user roles
		// $roles = (array) $user->roles;

		// // Check if user is administrator or merchant
		// if (in_array('administrator', $roles) || in_array('merchant', $roles)) 
		// {
			?>
			<h3>User Login PIN</h3>
			<table class="form-table">
				<tr>
					<th><label for="user_login_pin">Login PIN (4-digit)</label></th>
					<td>
						<input type="number" name="user_login_pin" id="user_login_pin" value="<?php echo esc_attr(get_user_meta($user->ID, 'user_login_pin', true)); ?>" class="regular-text" minlength="4" maxlength="4" pattern="\d{4}">
						<p class="description">Enter a 4-digit numeric PIN for user login.</p>
					</td>
				</tr>
			</table>
			<script>
				document.getElementById('user_login_pin').addEventListener('input', function (e) {
					e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4); // Only allow numbers, max length 6
				});
			</script>
			<?php
		// }
	}
	function add_user_device_id_field($user)
	{
		?>
		<h3>Device ID</h3>
		<table class="form-table">
			<tr>
				<th><label for="user_device_id">Device ID</label></th>
				<td>
					<input type="text" name="user_device_id" id="user_device_id" value="<?php echo esc_attr(get_user_meta($user->ID, 'user_device_id', true)); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}


	function add_user_pos_theme_field($user)
	{
		// Get current value
		$current_theme = get_user_meta($user->ID, 'user_pos_theme', true);
		?>
		<h3>POS Theme</h3>
		<table class="form-table">
			<tr>
				<th><label for="user_pos_theme">Theme</label></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="user_pos_theme" value="dark" <?php checked($current_theme, 'dark'); ?> />
							Dark
						</label><br>
						<label>
							<input type="radio" name="user_pos_theme" value="light" <?php checked($current_theme, 'light'); ?> />
							Light
						</label><br>
						<label>
							<input type="radio" name="user_pos_theme" value="system" <?php checked($current_theme, 'system'); ?> />
							System
						</label>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
	}

public function pinaka_replace_woocommerce_menu() {
    // Only run when WooCommerce exists
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    // Remove main WooCommerce menu (hides parent and its submenus)
    remove_menu_page( 'woocommerce' );

    
    add_menu_page( 'Orders', 'Orders', 'manage_woocommerce', 'edit.php?post_type=shop_order', '', 'dashicons-cart', 54 );
    // add_menu_page( 'Products', 'Products', 'manage_woocommerce', 'edit.php?post_type=product', '', 'dashicons-products', 55 );
    add_menu_page( 'Customers', 'Customers', 'manage_woocommerce', 'admin.php?page=wc-admin&path=%2Fcustomers', '', 'dashicons-groups', 55 );
	add_menu_page( 'Coupons', 'Coupons', 'manage_woocommerce', 'edit.php?post_type=shop_coupon&legacy_coupon_menu=1', '', 'dashicons-tickets', 56 );
	add_menu_page( 'Reports', 'Reports', 'manage_woocommerce', 'admin.php?page=wc-reports', '', 'dashicons-analytics', 57 );
	add_menu_page( 'Status', 'Status', 'manage_woocommerce', 'admin.php?page=wc-status', '', 'dashicons-update', 58 );

    // add_menu_page( 'Analytics', 'Analytics', 'manage_woocommerce', 'admin.php?page=wc-admin&path=/analytics', '', 'dashicons-chart-bar', 60 );
    add_menu_page( 'Extensions', 'Extensions', 'manage_woocommerce', 'admin.php?page=wc-admin&path=/extensions', '', 'dashicons-admin-plugins', 59 );

    add_menu_page( 'Settings', 'Settings', 'manage_options', 'admin.php?page=wc-settings', '', 'dashicons-admin-generic', 59 );
	}
	
	/**
	 * Save user_login_pin field when updating user profile (Only Numbers Allowed).
	 */
	function save_user_login_pin_field($user_id) {
		// Check user permissions
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}

		// Validate PIN (must be exactly 4 digits and numeric)
		if (!empty($_POST['user_login_pin']) && preg_match('/^\d{4}$/', $_POST['user_login_pin'])) {
			$new_user_pin = sanitize_text_field($_POST['user_login_pin']);

			// Check if PIN is already used by another user
			$user_pin_args = [
				'meta_key'     => 'user_login_pin',
				'meta_value'   => $new_user_pin,
				'exclude'      => [$user_id],
				'number'       => 1,
				'count_total'  => false,
				'fields'       => 'ID'
			];
		
			$user_pin_users = get_users($user_pin_args);
			if (empty($user_pin_users)) 
			{
				update_user_meta($user_id, 'user_login_pin', sanitize_text_field($_POST['user_login_pin']));
			}
			else
			{
				update_user_meta(get_current_user_id(), '_user_pin_error_flag', true);
			}
		} else {
			// Invalid or empty PIN
			delete_user_meta($user_id, 'user_login_pin');
		}
	}

	////////////////////////////////////// kranthi end ////////////////////////////////////////////////

	// Add custom field to product variations for variation name
	function add_custom_variation_field_to_woocomarce_product() {

		// Add custom field(s) to product variations
		add_action( 'woocommerce_variation_options_pricing', 'add_variation_setting_fields', 10, 3 );
		function add_variation_setting_fields( $loop, $variation_data, $variation ) {
			$field_key = 'custom_name';
			
			woocommerce_wp_text_input( array(
				'id'            => $field_key.'['.$loop.']',
				'label'         => __('Variation Name', 'woocommerce'),
				'wrapper_class' => 'form-row',
				'description'   => __('Add a custom Variation Name', 'woocommerce'),
				'desc_tip'      => true,
				'value'         => get_post_meta($variation->ID, $field_key, true)
			) );
		}

		// Save the custom field from product variations
		add_action('woocommerce_admin_process_variation_object', 'save_variation_setting_fields', 10, 2 );
		function save_variation_setting_fields($variation, $i) {
			$field_key = 'custom_name';

			if ( isset($_POST[$field_key][$i]) ) {
				$variation->update_meta_data($field_key, sanitize_text_field($_POST[$field_key][$i]));
			}
		}


		add_filter( 'woocommerce_product_title', 'custom_variation_title', 10, 2 );
			function custom_variation_title( $product_title, $variation ) {
				if ( $custom_title = $variation->get_meta('custom_name') ) {
					return $custom_title;
				}
				return $product_title;
			}

			add_filter( 'woocommerce_product_variation_get_name', 'custom_variation_name', 10, 2 );
			function custom_variation_name( $product_name, $variation ) {
				if ( $custom_name = $variation->get_meta('custom_name') ) {
					return $custom_name;
				}
				return $product_name;
		}
	}


	function apply_custom_discount_code_from_session(WC_Cart $cart) {
		if (is_admin() && !defined('DOING_AJAX')) return;
	
		$code = WC()->session->get('custom_discount_code');
		if (!$code) return;
	
		$custom_discounts = [
			'BULK10' => ['type' => 'percent', 'amount' => 10, 'min_spend' => 20],
			'FLAT20' => ['type' => 'fixed', 'amount' => 20, 'min_spend' => 100],
		];
	
		if (!isset($custom_discounts[$code])) {
			wc_add_notice(__('Invalid custom discount code.'), 'error');
			WC()->session->__unset('custom_discount_code');
			return;
		}
	
		$discount = $custom_discounts[$code];
	
		if ($cart->subtotal < $discount['min_spend']) {
			wc_add_notice(sprintf(__('Minimum spend for %s is $%s.', 'woocommerce'), $code, $discount['min_spend']), 'error');
			return;
		}
	
		$value = ($discount['type'] === 'percent') ? $cart->subtotal * ($discount['amount'] / 100) : $discount['amount'];
		$label = sprintf(__('Custom Discount (%s)', 'woocommerce'), $code);
		$cart->add_fee($label, -$value);
	}

	function save_custom_discount_code_to_order($order, $data) {
		$code = WC()->session->get('custom_discount_code');
		if ($code) {
			$order->update_meta_data('_custom_discount_code', $code);
		}
	}

	function add_employee_order_caps() 
	{
		$role = get_role('employee');
		if ($role && !get_option('pinaka_employee_caps_added')) {
			// Basic caps
			$role->add_cap('read');
			$role->add_cap('edit_posts');
			$role->add_cap('upload_files');

			// WooCommerce management
			$role->add_cap('manage_woocommerce');
			$role->add_cap('view_woocommerce_reports');

			// Product capabilities
			$role->add_cap('edit_products');
			$role->add_cap('delete_products');
			$role->add_cap('publish_products');
			$role->add_cap('edit_published_products');
			$role->add_cap('delete_published_products');

			// Order capabilities
			$role->add_cap('edit_shop_orders');
			$role->add_cap('read_shop_order');
			$role->add_cap('edit_shop_order');
			$role->add_cap('delete_shop_orders');
			$role->add_cap('publish_shop_orders');
			$role->add_cap('edit_others_shop_orders');
			$role->add_cap('read_private_shop_orders');

			// List users (used in some contexts)
			$role->add_cap('list_users');

			update_option('pinaka_employee_caps_added', true);
		}
	}
	public function pinaka_get_product_by_title( $title ) {
		$query = new WP_Query( [
			'post_type'      => 'product',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );

		if ( $query->have_posts() ) {
			return $query->posts[0]; // product ID
		}

		return false;
	}
////////////////////////////////////// kranthi start ////////////////////////////////////////////////
	public function show_pin_errors() {
		// Finally redirect — add error flags if needed
		$flag_emp_pin = get_user_meta(get_current_user_id(), '_emp_pin_error_flag', true);
		if ($flag_emp_pin) 
		{
			echo '<div class="notice notice-error is-dismissible">
				<p><strong>Error:</strong> This Employee PIN is already assigned to another user. Please create a unique 6-digit PIN.</p>
			</div>';
			delete_user_meta(get_current_user_id(), '_emp_pin_error_flag');
		}
		$flag_user_pin = get_user_meta(get_current_user_id(), '_user_pin_error_flag', true);
		if ($flag_user_pin) 
		{
			echo '<div class="notice notice-error is-dismissible">
				<p><strong>Error:</strong> This User PIN is already assigned to another user. Please create a unique 4-digit PIN.</p>
			</div>';
			delete_user_meta(get_current_user_id(), '_user_pin_error_flag');
		}
	}
////////////////////////////////////// kranthi end ////////////////////////////////////////////////
}




// ==============================
// Add Monthly Cron Schedule
// ==============================
add_filter('cron_schedules', function($schedules) {
    $schedules['monthly'] = [
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => __('Once Monthly'),
    ];
    return $schedules;
});

// ==============================
// Schedule monthly event
// ==============================
register_activation_hook(__FILE__, 'pinaka_schedule_favorite_items_event');
function pinaka_schedule_favorite_items_event() {
    if (!wp_next_scheduled('pinaka_update_favorite_items')) {
        wp_schedule_event(time(), 'monthly', 'pinaka_update_favorite_items');
    }
}

// Clear scheduled event on deactivation
register_deactivation_hook(__FILE__, 'pinaka_clear_favorite_items_event');
function pinaka_clear_favorite_items_event() {
    wp_clear_scheduled_hook('pinaka_update_favorite_items');
}

// Hook into WP-Cron
add_action('pinaka_update_favorite_items', 'pinaka_assign_favorite_items_category');

// ==============================
// Get Last Month's Top Selling Products
// ==============================
function pinaka_get_top_selling_products($limit = 10) {
    global $wpdb;

    // Last month's date range
    $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
    $end_date   = date('Y-m-t 23:59:59', strtotime('last day of last month'));

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT opl.product_id, SUM(opl.product_qty) as qty_sold
        FROM {$wpdb->prefix}wc_order_product_lookup opl
        INNER JOIN {$wpdb->prefix}wc_order_stats os
            ON opl.order_id = os.order_id
        WHERE os.status IN ('wc-completed','wc-processing')
          AND os.date_created >= %s
          AND os.date_created <= %s
        GROUP BY opl.product_id
        ORDER BY qty_sold DESC
        LIMIT %d
    ", $start_date, $end_date, $limit));

    return $results;
}

// ==============================
// Assign Favorite Items -> Quick Bites Sub-Category
// ==============================
function pinaka_assign_favorite_items_category() {
    $parent_slug = 'favorite-items'; // parent
    $child_slug  = 'quick-bites';    // sub-category

    // ✅ Ensure parent exists
    $parent_cat = get_term_by('slug', $parent_slug, 'product_cat');
    if (!$parent_cat) {
        $parent_cat = wp_insert_term('Favorite Items', 'product_cat', [
            'slug' => $parent_slug,
        ]);
    }
    if (is_wp_error($parent_cat)) {
        return;
    }
    $parent_cat_id = is_array($parent_cat) ? $parent_cat['term_id'] : $parent_cat->term_id;

    // ✅ Ensure sub-category exists
    $child_cat = get_term_by('slug', $child_slug, 'product_cat');
    if (!$child_cat) {
        $child_cat = wp_insert_term('Quick Bites', 'product_cat', [
            'slug'   => $child_slug,
            'parent' => $parent_cat_id,
        ]);
    }
    if (is_wp_error($child_cat)) {
        return;
    }
    $child_cat_id = is_array($child_cat) ? $child_cat['term_id'] : $child_cat->term_id;

    // ✅ Get limit from settings
    $limit   = get_option('pinaka_favorite_items_limit', 10);
    $results = pinaka_get_top_selling_products($limit);

    if ($results) {
        // ✅ Remove all old assignments (rotate monthly)
        pinaka_reset_favorite_items_category();

        // ✅ Assign new products
        foreach ($results as $row) {
            if ($row->product_id) {
                $product = wc_get_product($row->product_id);
                if ($product && $product->get_status() === 'publish') {
                    wp_set_object_terms($row->product_id, $child_cat_id, 'product_cat', true);
                }
            }
        }
    }
}

// ==============================
// Reset Function
// ==============================
function pinaka_reset_favorite_items_category() {
    $child_slug = 'quick-bites';
    $child_cat  = get_term_by('slug', $child_slug, 'product_cat');

    if ($child_cat && !is_wp_error($child_cat)) {
        $child_cat_id = is_array($child_cat) ? $child_cat['term_id'] : $child_cat->term_id;

        $old_products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $child_slug,
                ]
            ]
        ]);

        foreach ($old_products as $old_id) {
            wp_remove_object_terms($old_id, $child_cat_id, 'product_cat');
        }
    }
}

// ==============================
// WooCommerce Settings Field
// ==============================
add_filter('woocommerce_settings_tabs_array', function($tabs) {
    $tabs['pinaka_favorite_items'] = 'Favorite Items';
    return $tabs;
}, 50);

add_action('woocommerce_settings_tabs_pinaka_favorite_items', function() {
    woocommerce_admin_fields([
        'section_title' => [
            'name' => 'Favorite Items Settings',
            'type' => 'title',
            'desc' => 'Configure Favorite Items category behavior.',
            'id'   => 'pinaka_favorite_items_section_title'
        ],
        'pinaka_favorite_items_limit' => [
            'name'    => 'Number of Products',
            'type'    => 'number',
            'desc'    => 'How many products should appear in Favorite Items → Quick Bites (monthly)?',
            'id'      => 'pinaka_favorite_items_limit',
            'default' => '10',
            'css'     => 'width:60px;',
            'autoload'=> false,
        ],
        'section_end' => [
            'type' => 'sectionend',
            'id'   => 'pinaka_favorite_items_section_end'
        ]
    ]);
});

add_action('woocommerce_update_options_pinaka_favorite_items', function() {
    woocommerce_update_options([
        'pinaka_favorite_items_limit' => [
            'name' => 'Number of Products',
            'type' => 'number',
            'id'   => 'pinaka_favorite_items_limit'
        ]
    ]);
});

// Clear old daily cron if it exists
add_action('init', function() {
    $timestamp = wp_next_scheduled('pinaka_update_favorite_items');
    if ($timestamp) {
        wp_clear_scheduled_hook('pinaka_update_favorite_items');
    }
});

// ==============================
// Manual Trigger (Update + Reset)
// ==============================
add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Update Favorite Items',
        'Update Favorite Items',
        'manage_woocommerce',
        'update-favorite-items',
        'pinaka_favorite_items_admin_page'
    );
});

function pinaka_favorite_items_admin_page() {
    if (isset($_POST['pinaka_update_favorites'])) {
        pinaka_assign_favorite_items_category();
        echo '<div class="notice notice-success"><p>Favorite Items → Quick Bites updated successfully (last month’s sales)!</p></div>';
    }

    if (isset($_POST['pinaka_reset_favorites'])) {
        pinaka_reset_favorite_items_category();
        echo '<div class="notice notice-warning"><p>Favorite Items → Quick Bites has been reset (all products removed).</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Favorite Items → Quick Bites</h1>
        <form method="post">
            <p>
                <input type="submit" name="pinaka_update_favorites" class="button button-primary" value="Update Favorite Items">
                <input type="submit" name="pinaka_reset_favorites" class="button button-secondary" value="Reset Favorite Items">
            </p>
        </form>
    </div>
    <?php
}
add_action('pinaka_check_shift_status_cron', 'pinaka_auto_close_shifts');
add_action('update_option_pinaka_pos_shift_settings', 'pinaka_reschedule_shift_cron', 10, 2);
function pinaka_auto_close_shifts() 
{
	$open_shifts = get_posts([
		'post_type' => 'shifts',
		'post_status' => 'publish',
		'meta_query' => [
			[
				'key' => '_shift_status',
				'value' => 'open',
				'compare' => '=',
			],
			[
				'key' => '_shift_date',
				'value' => wp_date('Y-m-d'),
				'compare' => '=',
			]
		],
		'posts_per_page' => -1
	]);

	if (empty($open_shifts)) {
		return;
	}

	foreach ($open_shifts as $shift_post) {
		update_post_meta($shift_post->ID, '_shift_status', 'closed');
		wp_update_post([
			'ID' => $shift_post->ID,
			'post_status' => 'closed'
		]);
	}
}
// function pinaka_reschedule_shift_cron($old_value, $new_value) 
// {
//     $timestamp = wp_next_scheduled('pinaka_check_shift_status_cron');
//     if ($timestamp) {
//         wp_unschedule_event($timestamp, 'pinaka_check_shift_status_cron');
//     }

//     if (empty($new_value['shifts']) || !is_array($new_value['shifts'])) {
//         return;
//     }
// 	$current_timestamp = current_time('timestamp');
//     $today = current_time('Y-m-d');
// 	$current_date = date('Y-m-d', $current_timestamp);
// 	$last_end_timestamp = null;
// 	foreach ($new_value['shifts'] as $shift) {
// 		$start_time_str = $shift['start'] ?? '';
// 		$end_time_str   = $shift['end'] ?? '';

// 		if (!$start_time_str || !$end_time_str) {
// 			continue;
// 		}

// 		// Create DateTime objects from shift times
// 		$start_datetime = new DateTime("$current_date $start_time_str");
// 		$end_datetime   = new DateTime("$current_date $end_time_str");

// 		// Handle overnight shift (e.g., 6:00 PM to 12:00 AM — technically next day)
// 		if ($end_datetime < $start_datetime) {
// 			$end_datetime->modify('+1 day');
// 		}

// 		if (!empty($shift['end'])) {
// 			$end_time = strtotime($end_datetime->format('Y-m-d') . ' ' . $shift['end']);
// 			$start_datetime = new DateTime("$current_date $start_time_str");
// 			$end_datetime   = new DateTime("$current_date $end_time_str");

// 			// Handle overnight shift (e.g., 6:00 PM to 12:00 AM — technically next day)
// 			if ($end_datetime < $start_datetime) {
// 				$end_datetime->modify('+1 day');
// 			}
// 			if ($last_end_timestamp === null || $end_time > $last_end_timestamp) {
				
// 				$last_end_timestamp = $end_time;
// 			}
// 		}
// 	}
// 	if (!$last_end_timestamp) {
// 		return;
// 	}

// 	// $cron_time = $last_end_timestamp + 60;
// 	$cron_time = $last_end_timestamp + (10 * MINUTE_IN_SECONDS);
// 	$current_time = current_time('timestamp');
// 	error_log(date('Y-m-d h:i:s',$current_time) .'>='. date('Y-m-d h:i:s',$cron_time));
// 	if ($current_time >= $cron_time) {
// 		$cron_time = strtotime('+1 day', $cron_time);
// 	}
// 	$start_datetime = new DateTime("$current_date $start_time_str");
// 			$end_datetime   = new DateTime("$current_date $end_time_str");

// 			// Handle overnight shift (e.g., 6:00 PM to 12:00 AM — technically next day)
// 			if ($end_datetime < $start_datetime) {
// 				$end_datetime->modify('+1 day');
// 			}
// 	$gmt_offset = get_option('gmt_offset');
// 	$utc_cron_time = $cron_time - ($gmt_offset * HOUR_IN_SECONDS);
// 	wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');

// 	// $wp_timezone = wp_timezone();
// 	// $datetime_local = new DateTime('now', $wp_timezone);
// 	// $datetime_local->setTimestamp($cron_time);

// 	// // Convert to UTC timestamp
// 	// $datetime_local->setTimezone(new DateTimeZone('UTC'));
// 	// $utc_cron_time = $datetime_local->getTimestamp();

// 	// // Schedule daily cron at correct UTC time
// 	// wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');
// }
function pinaka_reschedule_shift_cron($old_value, $new_value) {
    // Clear any existing event
    wp_clear_scheduled_hook('pinaka_check_shift_status_cron');

    // Validate shifts array
    if (empty($new_value['shifts']) || !is_array($new_value['shifts'])) {
        return;
    }

    $wp_timezone = wp_timezone(); // WordPress timezone object (DST-aware)
    $current_date = wp_date('Y-m-d', null, $wp_timezone);
    $last_end_timestamp = null;

    // Loop through shifts to find the latest end time
    foreach ($new_value['shifts'] as $shift) {
        $start_time_str = trim($shift['start'] ?? '');
        $end_time_str   = trim($shift['end'] ?? '');

        if (!$start_time_str || !$end_time_str) {
            continue;
        }

        try {
            $start_datetime = new DateTime("$current_date $start_time_str", $wp_timezone);
            $end_datetime   = new DateTime("$current_date $end_time_str", $wp_timezone);
	
            if ($end_datetime < $start_datetime) {
                $end_datetime->modify('+1 day');
            }

            $end_timestamp = $end_datetime->getTimestamp();

            if ($last_end_timestamp === null || $end_timestamp > $last_end_timestamp) {
                $last_end_timestamp = $end_timestamp;
            }

        } catch (Exception $e) {
            // error_log("[PINAKA CRON] Invalid shift time format: " . $e->getMessage());
            continue;
        }
    }

    // No valid shifts found
    if (!$last_end_timestamp) {
        return;
    }

    // Add 10 minutes after last shift
    $cron_time = $last_end_timestamp + (10 * MINUTE_IN_SECONDS);

    // Get current local time
    $current_time = current_time('timestamp');

    // If already past today's cron time, schedule for next day
    if ($current_time >= $cron_time) {
        $cron_time = strtotime('+1 day', $cron_time);
    }

    // Convert local $cron_time → UTC (WordPress cron runs in UTC)
    $datetime_local = new DateTime('@' . $cron_time);
    $datetime_local->setTimezone($wp_timezone);
    $datetime_local->setTimezone(new DateTimeZone('UTC'));
    $utc_cron_time = $datetime_local->getTimestamp();

    // Schedule daily cron
	// $utc = new DateTimeZone( 'UTC' );
	// $dt  = new DateTime( gmdate( 'Y-m-d' ) . ' 14:40:00', $utc );
	// $utc_cron_time = $dt->getTimestamp();

    wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');

    // Debug logs for verification
    // if (defined('WP_DEBUG') && WP_DEBUG) {
    //     $local_time_str = wp_date('Y-m-d H:i:s', $cron_time, $wp_timezone);
    //     $utc_time_str   = gmdate('Y-m-d H:i:s', $utc_cron_time);
    //     error_log("[PINAKA CRON] Shift cron rescheduled -> Local: {$local_time_str}, UTC: {$utc_time_str}");
    // }
}
add_filter('manage_edit-shop_coupon_columns', function ($columns) {

    $columns['campaign_code'] = 'Campaign Name';
	$columns['coupon_barcode'] = __('Barcode', 'woocommerce');
    return $columns;
});

add_action('manage_shop_coupon_posts_custom_column', function ($column, $post_id) {

    if ($column === 'campaign_code') {

        $campaign = get_post_meta(
            $post_id,
            '_campaign_code',
            true
        );

        echo esc_html($campaign ?: '-');
    }
	if ($column === 'coupon_barcode') {
        $coupon_code = get_post_field('post_title', $post_id);

        if (!$coupon_code) {
            echo '—';
            return;
        }

        // PNG barcode URL
        $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . rawurlencode($coupon_code) . '&code=Code128&dpi=96&filetype=png&translate-esc=true';


        echo '<div style="text-align:center;">';
        echo '<img src="' . esc_url($barcode_url) . '" height="50" alt="Barcode" /><br>';

        $download_url = admin_url('edit.php?post_type=shop_coupon&barcode_download=1&code=' . urlencode($coupon_code));

		echo '<a href="' . esc_url($download_url) . '" 
				style="text-decoration:none;font-size:14px;display:inline-block;margin-top:6px;" 
				title="Download Barcode PNG">
				⬇️ Download
			</a>';

    }
}, 10, 2);

add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'shop_coupon') {
        return;
    }
    ?>
    <input
        type="text"
        id="campaign_code"
        placeholder="Campaign Code"
        autocomplete="off"
        style="margin-right:10px;"
    />
    <input
        type="text"
        id="coupon_amount"
        placeholder="Coupon Amount"
        autocomplete="off"
        style="margin-right:10px;"
    />
    <button
        type="button"
        class="button button-primary"
        id="generate_coupon_export">
        Export Coupons
    </button>
    <span id="coupon_export_status" style="margin-left:10px;color:#666;"></span>
    <?php
});

// Enqueue script separately — no mixed PHP/JS
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'edit.php') {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'shop_coupon') {
        return;
    }

    wp_register_script(
        'pinaka-coupon-export',
        false, // inline script, no file
        ['jquery'],
        null,
        true
    );

    // Pass ajaxurl + nonce safely from PHP to JS
    wp_localize_script('pinaka-coupon-export', 'pinakaExport', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('pinaka_coupon_export'),
    ]);

    wp_add_inline_script('pinaka-coupon-export', "
        jQuery(function(\$) {
            \$('#generate_coupon_export').on('click', function () {

                const campaign = \$('#campaign_code').val().trim();
                const amount   = \$('#coupon_amount').val().trim();

                if (!campaign) { alert('Enter Campaign Code'); return; }
                if (!amount)   { alert('Enter Coupon Amount'); return; }

                const \$btn    = \$(this);
                const \$status = \$('#coupon_export_status');

                \$btn.prop('disabled', true).text('Generating...');
                \$status.text('Please wait, generating coupons...');

                const downloadUrl = pinakaExport.ajaxurl
                    + '?action=pinaka_generate_coupon_export'
                    + '&campaign_code=' + encodeURIComponent(campaign)
                    + '&coupon_amount=' + encodeURIComponent(amount)
                    + '&_wpnonce='      + encodeURIComponent(pinakaExport.nonce);

                fetch(downloadUrl)
                    .then(function(response) {
                        if (!response.ok) {
                            return response.text().then(function(text) {
                                throw new Error(text || 'Server error: ' + response.status);
                            });
                        }
                        return response.blob();
                    })
                    .then(function(blob) {
                        const url  = URL.createObjectURL(blob);
                        const a    = document.createElement('a');
                        a.href     = url;
                        a.download = 'coupon_export.xls';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);

                        \$status.text('Done! Reloading...');
                        setTimeout(function () {
                            window.location.reload();
                        }, 1500);
                    })
                    .catch(function(err) {
                        \$status.text('');
                        alert('Export failed: ' + err.message);
                    })
                    .finally(function() {
                        \$btn.prop('disabled', false).text('Export Coupons');
                    });
            });
        });
    ");

    wp_enqueue_script('pinaka-coupon-export');
});
add_action(
    'wp_ajax_pinaka_generate_coupon_export',
    'pinaka_generate_coupon_export'
);

function pinaka_get_last_coupon_number($campaign_code)
{
    global $wpdb;

    $last_number = $wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT MAX(CAST(pm2.meta_value AS UNSIGNED))
            FROM {$wpdb->postmeta} pm1
            INNER JOIN {$wpdb->postmeta} pm2
                ON pm1.post_id = pm2.post_id
            WHERE pm1.meta_key = '_campaign_code'
            AND pm1.meta_value = %s
            AND pm2.meta_key = '_coupon_sequence'
            ",
            $campaign_code
        )
    );

    return absint($last_number);
}

function pinaka_generate_coupon_export()
{
	if (!current_user_can('manage_woocommerce')) {
		wp_die('Permission denied');
	}

	$campaign_code = sanitize_text_field(
		$_GET['campaign_code'] ?? ''
	);

	$campaign_code = strtoupper(trim($campaign_code));

	// Get coupon amount input
	$amount_input = trim($_GET['coupon_amount'] ?? '');

	// Detect discount type automatically
	if (strpos($amount_input, '%') !== false) {
		$discount_type = 'percent';
		$coupon_amount = (float) str_replace('%', '', $amount_input);
	} else {
		$discount_type = 'fixed_cart';
		$coupon_amount = (float) $amount_input;
	}

	$allowed_discount_types = [
		'fixed_cart',
		'percent',
	];

	if (!in_array($discount_type, $allowed_discount_types, true)) {
		wp_die('Invalid discount type.');
	}

	if (empty($campaign_code) || $coupon_amount <= 0) {
		wp_die('Campaign Code and Coupon Amount are required.');
	}

	// Percentage validation
	if (
		$discount_type === 'percent' &&
		($coupon_amount <= 0 || $coupon_amount > 100)
	) {
		wp_die('Percentage discount must be between 1 and 100.');
	}

	// Get last sequence
	$last_number = pinaka_get_last_coupon_number(
		$campaign_code
	);

	$rows = [];
	$generated = 0;
	$sequence = $last_number;
	$attempts = 0;
	$max_attempts = 500;

	while ($generated < 100 && $attempts < $max_attempts) {

		$attempts++;
		$sequence++;

		$coupon_code = date('YHis') . $sequence;

		if (wc_get_coupon_id_by_code($coupon_code)) {
			continue;
		}

		try {

			$coupon = new WC_Coupon();

			$coupon->set_code($coupon_code);
			$coupon->set_discount_type($discount_type);
			$coupon->set_amount($coupon_amount);
			$coupon->set_usage_limit(1);

			// Expiry after 4 months
			$expiry_date = new WC_DateTime();
			$expiry_date->modify('+4 months');
			$coupon->set_date_expires($expiry_date);

			// Description
			$coupon->set_description(
				'Initial Welcome Discount'
			);

			$coupon_id = $coupon->save();

			if (!$coupon_id) {
				continue;
			}

			update_post_meta(
				$coupon_id,
				'_campaign_code',
				$campaign_code
			);

			update_post_meta(
				$coupon_id,
				'_coupon_sequence',
				$sequence
			);

			$rows[] = [
				'coupon_code' => $coupon_code,
				'campaign_code' => $campaign_code,
				'discount_type' => $discount_type,
				'worth' => $coupon_amount,
				'usage_limit' => 1,
				'expiry_date' => $expiry_date->date('Y-m-d'),
			];

			$generated++;

		} catch (Exception $e) {

			error_log(
				'Coupon Generation Error: ' .
				$e->getMessage()
			);
		}
	}

	if (empty($rows)) {
		wp_die('No coupons were generated.');
	}

	pinaka_export_coupon_excel($rows);
}

function pinaka_export_coupon_excel($rows)
{
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename = 'coupon_export_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: max-age=0');

    echo "\xEF\xBB\xBF"; // UTF-8 BOM

    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns="http://www.w3.org/TR/REC-html40">

    <head>
        <meta charset="UTF-8">

        <!-- Excel Compatibility -->
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>Coupons</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
    </head>

    <body>

    <table border="1">
        <thead>
            <tr>
                <th>Coupon Code</th>
                <th>Campaign Name</th>
                <th>Worth</th>
                <th>Usage Limit</th>
                <th>Expiry Date</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html((string)$row['coupon_code']); ?></td>
                    <td><?php echo esc_html($row['campaign_code']); ?></td>
                    <td><?php echo esc_html($row['worth']); ?></td>
                    <td><?php echo esc_html($row['usage_limit']); ?></td>
                    <td><?php echo esc_html($row['expiry_date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    </body>
    </html>
    <?php

    exit;
}

add_action('admin_init', function() {
    if (!isset($_GET['barcode_download']) || !isset($_GET['code'])) {
        return;
    }

    $coupon_code = sanitize_text_field($_GET['code']);
    $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($coupon_code) . '&code=Code128&dpi=96&filetype=png';

    $image_data = wp_remote_retrieve_body(wp_remote_get($barcode_url));

    if (!$image_data) {
        wp_die('Error generating barcode.');
    }

    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="barcode-' . $coupon_code . '.png"');
    echo $image_data;
    exit;
});
