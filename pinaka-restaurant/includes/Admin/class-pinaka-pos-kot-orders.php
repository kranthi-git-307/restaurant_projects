<?php
if (!defined('ABSPATH')) exit;

class WC_KOT_Display {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // add_action('init', [$this, 'register_custom_order_statuses']);
        // add_filter('wc_order_statuses', [$this, 'add_custom_order_statuses_to_dropdown']);

        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_kot_column'], 20);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'render_kot_column'], 10, 2);
        
        add_action('admin_head', [$this, 'add_kot_column_styles']);
        add_action('woocommerce_order_status_changed',[$this,'reduce_stock_on_kot_processed'],10,3);
        // add_action('restrict_manage_posts', [$this, 'all_created_via_channels'],20);
        // add_action( 'pre_get_posts', [$this, 'pinaka_hide_child_orders_from_admin'] );
        add_filter( 'woocommerce_orders_table_query_clauses', [$this, 'pinaka_exclude_child_orders_from_wc_admin'],10 );
        add_filter( 'woocommerce_orders_table_status_counts_query_clauses', [$this, 'pinaka_exclude_child_orders_from_wc_admin_class'],20 );
        // add_action( 'restrict_manage_posts', [$this, 'add_kot_order_filter'] );
        // add_filter('woocommerce_orders_table_filters', [$this, 'add_kot_order_filter']);
        // add_filter('woocommerce_order_table_query_args', [$this, 'filter_kot_orders'], 10, 2);
        
        // add_action( 'pre_get_posts', [$this, 'filter_kot_orders'] );
        // add_action('woocommerce_shop_order_query', [$this, 'filter_kot_orders'], 10, 2);
        // add_filter('woocommerce_order_list_table_query_args', [$this, 'filter_kot_orders']);
        // add_action( 'woocommerce_order_status_yet-to-prepare', [$this, 'reduce_stock_on_kot_order'] );
        // add_action( 'woocommerce_order_status_in-progress', [$this, 'reduce_stock_on_kot_order'] );

        // add_filter('woocommerce_order_get_total', [$this, 'override_parent_order_total'], 20, 2);
        // add_action('woocommerce_order_status_changed', [$this, 'maybe_complete_parent_order'], 20, 4);

        // Sync parent totals/status on all child order changes
        // add_action('woocommerce_order_status_changed', [$this, 'maybe_update_parent_order_on_child_change'], 21, 4);
        // add_action('save_post_shop_order', [$this, 'maybe_update_parent_order_on_child_save'], 99);
        // add_action('before_delete_post', [$this, 'maybe_update_parent_order_on_child_delete'], 9);
        // add_action('woocommerce_order_refunded', [$this, 'maybe_update_parent_order_on_child_refund'], 99, 2);

        // add_action('rest_api_init', [$this, 'register_kot_api']);
        // add_action('woocommerce_thankyou', [$this, 'maybe_create_initial_kot_order'], 10, 1);
        // add_filter('woocommerce_order_created_via_options', [$this, 'add_kot_order_created_via_option']);
    }
  
    // public function add_kot_order_created_via_option( $channels ) {
    //     // Add custom sales channels
    //     $channels['rest-api']  = __( 'Flutter App (REST API)', 'pinaka-pos' );
    //     $channels['takeaway']  = __( 'Takeaway', 'pinaka-pos' );
    //     $channels['online']    = __( 'Online Order', 'pinaka-pos' );
    //     $channels['pos-rest-api'] = __( 'Point of Sale', 'pinaka-pos' );

    //     return $channels;
    // }
    function reduce_stock_on_kot_processed( $order_id, $old_status, $new_status ) {

        if ( $new_status !== 'preparing' ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        
        if ( $order->get_meta('_kot_order') !== 'yes' ) {
            return;
        }
        if ( $order->get_meta('_stock_reduced') === 'yes' ) {
            return;
        }

        wc_reduce_stock_levels( $order_id );

        // foreach ( $order->get_items() as $order_item ) {

        //     $combo_product_id = $order_item->get_product_id();
        //     $kot_qty          = $order_item->get_quantity();

        //     $combo_product = wc_get_product( $combo_product_id );

        //     if ( ! $combo_product || $combo_product->get_type() !== 'combo_bundle' ) {
        //         continue;
        //     }

        //     $combo_items = json_decode(
        //         get_post_meta( $combo_product_id, '_combo_bundle_items', true ),
        //         true
        //     );

        //     if ( empty( $combo_items ) || ! is_array( $combo_items ) ) {
        //         continue;
        //     }

        //     foreach ( $combo_items as $combo_item ) {

        //         $child_id  = absint( $combo_item['id'] ?? 0 );
        //         $child_qty = absint( $combo_item['qty'] ?? 0 );

        //         if ( ! $child_id || ! $child_qty ) {
        //             continue;
        //         }

        //         $child_product = wc_get_product( $child_id );
        //         if ( ! $child_product || ! $child_product->managing_stock() ) {
        //             continue;
        //         }

        //         $reduce_qty = $child_qty * $kot_qty;

        //         wc_update_product_stock(
        //             $child_product,
        //             $reduce_qty,
        //             'decrease'
        //         );
        //     }
        // }

        $order->update_meta_data( '_stock_reduced', 'yes' );
        $order->save();
    }
    public function pinaka_exclude_child_orders_from_wc_admin( $args ) {
        global $wpdb;

        // Exclude child orders (only show parent orders where parent_id = 0)
        $args['where'] .= " AND {$wpdb->prefix}wc_orders.parent_order_id = 0 ";

        return $args;
    }
    public function pinaka_exclude_child_orders_from_wc_admin_class( $args )
    {
        global $wpdb;
        $args['where'] .= " AND {$wpdb->prefix}wc_orders.parent_order_id = 0 ";
        return $args;
    }
    public function pinaka_hide_child_orders_from_admin( $query ) {
        global $pagenow;

        // Only apply in admin orders screen
        if ( is_admin() 
            && $pagenow === 'edit.php' 
            && isset($_GET['post_type']) 
            && $_GET['post_type'] === 'shop_order' 
            && $query->is_main_query() 
        ) {
            // Exclude child orders (parent > 0)
            $query->set( 'post_parent', 0 );
        }
    }
    public function all_created_via_channels($post_type)
    {
        if ($post_type === 'shop_order') {
            global $wpdb;
            // Retrieve all unique _created_via values
            $created_vias = $wpdb->get_col("
                SELECT DISTINCT meta_value
                FROM {$wpdb->prefix}postmeta
                WHERE meta_key = '_created_via'
                AND meta_value != ''
            ");
            $selected = isset($_GET['created_via']) ? sanitize_text_field($_GET['created_via']) : '';
            ?>
            <select name="created_via" id="filter-by-created-via">
                <option value=""><?php esc_html_e('All Sales Channels', 'your-textdomain'); ?></option>
                <?php foreach ($created_vias as $via): ?>
                    <option value="<?php echo esc_attr($via); ?>" <?php selected($selected, $via); ?>>
                        <?php echo esc_html(ucwords(str_replace(['-', '_'], ' ', $via))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
    public function maybe_create_initial_kot_order($order_id) {

        $parent_order = wc_get_order($order_id);
        if (!$parent_order) {
            return;
        }
        if ($parent_order->get_parent_id() > 0) {
            return;
        }

        $is_kot = get_post_meta($parent_order->get_id(), '_parent_order', true);
        if ($is_kot === 'yes') {
            return;
        }

        $items = $parent_order->get_items();
        if (count($items) === 0) {
            return;
        }

        $kot_items = [];
        foreach ($items as $item) {
            $modifiers     = $item->get_meta('_product_modifiers');
            $modifier_cost = $item->get_meta('_modifier_extra_cost');
            // $unit_price       = (float) $item->get_total() / max(1, $quantity); // Avoid divide by zero
            $quantity = $item->get_quantity();
            $total    = $item->get_total();
            $unit_price = $quantity > 0 ? ($total / $quantity) : 0;
            // $unit_price       = (float) $item->get_total(); // Avoid divide by zero
            $line_subtotal    = (float) $item->get_total(); // Total excluding tax
            $line_total_with_tax = (float) $item->get_total() + $item->get_total_tax(); // Optional
            $kot_items[] = [
                'product_id' => $item->get_product_id(),
                'quantity'   => $item->get_quantity(),
                'unit_price'           => wc_format_decimal($unit_price, 2),
                'line_total'           => wc_format_decimal($line_subtotal, 2),
                'line_total_with_tax'  => wc_format_decimal($line_total_with_tax, 2),
                'product_modifiers'   => !empty($modifiers) ? explode(',', $modifiers) : [],
                'modifier_extra_cost' => floatval($modifier_cost),
                'parent_flag'         => 1,
                
            ];
        }
        $coupons = [];

        foreach ($parent_order->get_items('coupon') as $coupon_item) {
            $coupons[] = [
                'code'         => $coupon_item->get_code(),
                'discount'     => wc_format_decimal($coupon_item->get_discount(), 2),
                'discount_tax' => wc_format_decimal($coupon_item->get_discount_tax(), 2),
            ];
        }

        $params = [
            'parent_order_id' => $parent_order->get_id(),
            'status'          => 'yet-to-prepare',
            'items'           => $kot_items,
            'coupons'         => $coupons, // Optional
            'reserved_table_id' => 1786
        ];

        $request = new WP_REST_Request('POST', '/pinaka-restaurant-pos/v1/create');
        $request->set_body_params($params);

        $response = $this->handle_create_kot_api_request($request);

        if (is_wp_error($response)) {
        } elseif ($response instanceof WP_REST_Response) {
            $data = $response->get_data();
            if (!empty($data['success'])) {
                // update_post_meta($parent_order->get_id(), '_parent_order', 'yes');
            } else {
                // error_log('KOT creation failed: ' . print_r($data, true));
            }
        } else {
            // error_log('Unexpected KOT creation response: ' . print_r($response, true));
        }
    }

    public function add_custom_order_statuses_to_dropdown($order_statuses) {
        // Detect order ID
        $order_id = 0;

        // Try to get order ID from query string (both classic and HPOS supported)
        if (isset($_GET['post'])) {
            $order_id = absint($_GET['post']);
        } elseif (isset($_GET['id'])) {
            $order_id = absint($_GET['id']);
        }

        if (!$order_id) {
            return $order_statuses;
        }

        // Load the order object
        $order = wc_get_order($order_id);

        if (!$order) {
            return $order_statuses;
        }

        $is_kot_child = $order->get_meta('_kot_order', true);
 
        // Custom KOT statuses
        // $kot_statuses = [
        //     'wc-yet-to-prepare' => __('Yet To Prepare', 'pinaka-pos'),
        //     'wc-in-progress'    => __('In Progress', 'pinaka-pos'),
        //     'wc-prepared'       => __('Prepared', 'pinaka-pos'),
        //     'wc-ready-to-serve' => __('Ready To Serve', 'pinaka-pos')
        // ];

        // if ($is_kot_child) {
        //     return $kot_statuses;
        // }
        return $order_statuses;

    }

    public function add_kot_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'order_status') {
                $new_columns['kot_orders'] = 'KOT Orders';
                $new_columns['order_type'] = 'Order Type';
            }
        }
        return $new_columns;
    }

    public function render_kot_column($column, $order) {
        // if ($column !== 'kot_orders') return;
        if($column === 'kot_orders')
        {
            $parent_order_id = $order->get_id();
            $children = $this->get_kot_child_orders_by_parent_id($parent_order_id);

            $children = array_filter($children, function($child) {
                return $child->get_meta('_kot_number', true); 
            });

            if (empty($children)) {
                echo '<div style="color:#999;">No KOTs</div>';
                return;
            }

            echo '<div style="padding-left: 10px;">';
            foreach ($children as $kot) {
                $kot_number = $kot->get_meta('_kot_number', true) ?: 'KOT#' . $kot->get_id();
                $kot_status = $kot->get_status();
                $kot_total  = $kot->get_total();
                $restaurant_id = $kot->get_meta('_restaurant_id', true);
                $zone_id = $kot->get_meta('_zone_id', true);
                $status_label = wc_get_order_status_name($kot_status);
                $status_class = 'status-' . sanitize_html_class($kot_status);
                $edit_url = admin_url('post.php?post=' . $kot->get_id() . '&action=edit');

                echo '<div class="kot-entry">';
                echo '<p>kot id : '.$kot->get_id().'</p>';
                echo '<p>Restuarant id : '.$restaurant_id.'</p>';
                echo '<p>Zone id : '.$zone_id.'</p>';
                echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $kot_number ) . '</a>';
                echo ' , ' . wp_kses_post( wc_price( $kot_total ) );
                echo ' | <mark class="order-status ' . esc_attr( $status_class ) . '"><span>' . esc_html( $status_label ) . '</span></mark>';
                echo '</div>';
            }
            echo '</div>';
        }
        else if($column === 'order_type')
        {
            $order_type = $order->get_created_via();
            if($order_type === 'rest-api')
            {
                echo '<b>Dine-in</b>';
            }
            else if($order_type === 'takeaway')
            {
                echo '<b>Takeaways</b>';
            }
            else if($order_type === 'online')
            {
                echo '<b>Online Orders</b>';
            }
        }
    }

    private function get_kot_child_orders_by_parent_id($parent_id) {
        global $wpdb;

        $use_hpos = false;
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
            method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled')) {
            $use_hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }

        $orders_table  = $use_hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->prefix . 'posts';
        $id_field      = $use_hpos ? 'id' : 'ID';
        $parent_field  = $use_hpos ? 'parent_order_id' : 'post_parent';
        $status_field  = $use_hpos ? 'status' : 'post_status';

        $allowed_statuses = [ 
            'wc-pending',
            'wc-processing',
            'wc-on-hold',
            'wc-completed',
            'wc-cancelled',
            'wc-refunded',
            'wc-failed',
            'wc-checkout-draft',
            'yet-to-prepare',
            'preparing',
            'ready',
            'served'
        ];

        // $status_list = implode(',', array_map(function($status) {
        //     return "'" . esc_sql($status) . "'";
        // }, $allowed_statuses));

        // $sql = "
        //     SELECT $id_field FROM {$orders_table}
        //     WHERE $parent_field = %d
        //     AND $status_field IN ($status_list)
        // ";
        
        $sql = "
            SELECT $id_field FROM {$orders_table}
            WHERE $parent_field = %d
        ";

        $child_ids = $wpdb->get_col($wpdb->prepare($sql, $parent_id));
        $orders = array_filter(array_map('wc_get_order', $child_ids));

        return $orders;
    }

    public function add_kot_column_styles() {
        echo '<style>
            .column-kot_orders {
                width: 240px;
                font-size: 13px;
                white-space: normal !important;
            }
    
            .kot-entry {
                padding: 6px;
                margin-bottom: 8px;
                border: 1px solid #eee;
                border-radius: 4px;
                background: #fafafa;
            }
    
            .kot-entry a {
                font-weight: 600;
                color: #0073aa;
            }
    
            .kot-entry a:hover {
                text-decoration: underline;
            }
    
            .kot-entry mark.order-status {
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
            }
    
            /* Yet To Prepare */
            .kot-entry mark.status-yet-to-prepare {
                background: #e2e3e5;
                color: #383d41;
            }
    
            /* Preparing */
            .kot-entry mark.status-preparing {
                background: #cce5ff;
                color: #004085;
            }
    
            /* Ready */
            .kot-entry mark.status-ready {
                background: #fff3cd;
                color: #856404;
            }
    
            /* Served */
            .kot-entry mark.status-served {
                background: #d4edda;
                color: #155724;
            }
        </style>';
    }

    // public function override_parent_order_total($total, $order) {
    //     if ($order->get_parent_id() > 0) {
    //         return $total;
    //     }
    
    //     $parent_order_id = $order->get_id();
       
    //     $meta_value = $order->get_meta('_parent_order', true);
        
    //     if ($meta_value !== 'yes') {
    //         return $total;
    //     }
    //     $children = $this->get_kot_child_orders_by_parent_id($parent_order_id);

    //     $valid_children = array_filter($children, function($child) {
    //         return is_a($child, 'WC_Order') && $child->get_meta('_kot_number', true);
    //     });

    //     if (empty($valid_children)) {
    //         return 0;
    //     }

    //     $new_total = 0;
    //     foreach ($valid_children as $child) {
    //         if ($child->get_status() !== 'cancelled-kot') {
    //             $new_total += floatval($child->get_total());
    //         }
    //     }

    //     return $new_total;
    // }

    /** Sync parent totals/status after any child order status change */
    // public function maybe_update_parent_order_on_child_change($order_id, $old_status, $new_status, $order) {
    //     if ($order && $order->get_parent_id() > 0 && $order->get_meta('_kot_order', true) === 'yes') {
    //         $this->sync_parent_totals_and_status($order->get_parent_id());
    //     }
    // }

    /** Sync parent totals/status after child order saved */
    // public function maybe_update_parent_order_on_child_save($post_id) {
    //     $order = wc_get_order($post_id);
    //     if ($order && $order->get_parent_id() > 0 && $order->get_meta('_kot_order', true) === 'yes') {
    //         $this->sync_parent_totals_and_status($order->get_parent_id());
    //     }
    // }

    /** Sync parent totals/status after child order delete */
    // public function maybe_update_parent_order_on_child_delete($post_id) {
    //     $order = wc_get_order($post_id);
    //     if ($order && $order->get_parent_id() > 0 && $order->get_meta('_kot_order', true) === 'yes') {
    //         $this->sync_parent_totals_and_status($order->get_parent_id());
    //     }
    // }

    /** Sync parent totals/status after child order refund */
    // public function maybe_update_parent_order_on_child_refund($order_id, $refund_id) {
    //     $order = wc_get_order($order_id);
    //     if ($order && $order->get_parent_id() > 0 && $order->get_meta('_kot_order', true) === 'yes') {
    //         $this->sync_parent_totals_and_status($order->get_parent_id());
    //     }
    // }

    /** Core sync function */
    // public function sync_parent_totals_and_status($parent_id) {
    //     $parent = wc_get_order($parent_id);
    //     if (!$parent || $parent->get_meta('_parent_order', true) !== 'yes') {
    //         return;
    //     }

    //     $children = $this->get_kot_child_orders_by_parent_id($parent_id);
    //     if (empty($children)) {
    //         return;
    //     }

    //     $total = 0;
    //     $all_done = true;
    //     foreach ($children as $child) {
    //         $status = $child->get_status();

    //         if ($status !== 'cancelled-kot') {
    //             $total += floatval($child->get_total());
    //         }

    //         // If child not served or cancelled, parent can't complete
    //         if (!in_array($status, ['served', 'cancelled-kot'], true)) {
    //             $all_done = false;
    //         }
    //     }

    //     // Update parent total
    //     $parent->set_total($total);

    //     // Auto-complete parent if all children are done
    //     $parent_status = $parent->get_status();

    //     // Ensure allowed statuses and the $parent is a WC_Order
    //     if (
    //         $all_done &&
    //         $total > 0 &&
    //         in_array($parent_status, ['processing', 'pending'], true)
    //     ) {
    //         $parent->set_status('completed', __('Auto-completed after all KOTs done.', 'pinaka-pos'), true);
    //     }
    //     $parent->save();
    // }

    public function register_kot_api() {
        register_rest_route('pinaka-restaurant-pos/v1', '/create', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_create_kot_api_request'],
            'permission_callback' => function () {
                return current_user_can('edit_shop_orders');
            },
        ]);
        register_rest_route('pinaka-restaurant-pos/v1', '/orders/(?P<id>\d+)/status', [
            'methods'  => 'POST',
            'callback' => [$this, 'update_kot_order_status_via_api'],
            'permission_callback' => function () {
                return current_user_can('edit_shop_orders');
            },
            'args' => [
                'status' => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ]);
    }

    // public function handle_create_kot_api_request($request) 
    // {
    //     $params = $request->get_params();
    //     $parent_order_id = absint($params['parent_order_id'] ?? 0);
    //     $parent_order = wc_get_order($parent_order_id);

    //     if (!$parent_order_id || !$parent_order) {
    //         return new WP_REST_Response(['error' => 'Invalid parent order ID.'], 400);
    //     }

    //     if ($parent_order->get_status() === 'completed') {
    //         return new WP_REST_Response(['error' => 'Cannot create KOT. Parent order is already completed.'], 403);
    //     }

    //     $table_id = isset($params['reserved_table_id']) ? $params['reserved_table_id'] : 0;
    //     if ($table_id > 0) {
    //         $parent_order->update_meta_data('_reserved_table_id', $table_id);
    //         $parent_order->save();
    //     }

    //     $valid_statuses = [
    //         'wc-yet-to-prepare',
    //         'wc-in-progress',
    //         'wc-prepared',
    //         'wc-ready-to-serve',
    //         'wc-served',
    //     ];
    //     $requested_status = sanitize_text_field($params['status'] ?? '');
    //     $status_slug = in_array($requested_status, $valid_statuses) ? $requested_status : 'wc-yet-to-prepare';

    //     $kot_order = wc_create_order([
    //         'customer_id' => $parent_order->get_customer_id(),
    //     ]);

    //     if (is_wp_error($kot_order)) {
    //         return new WP_REST_Response(['error' => $kot_order->get_error_message()], 500);
    //     }

    //     $kot_order->set_parent_id($parent_order->get_id());
    //     $kot_order->set_status($status_slug, 'No email', true);

    //     $kot_number = 'KOT#' . $kot_order->get_id();
    //     update_post_meta($kot_order->get_id(), '_kot_number', $kot_number);
    //     update_post_meta($parent_order_id, '_parent_order', 'yes');

    //     $items = $params['items'] ?? [];
    //     $successful_items = [];
    //     $failed_items = [];
    //     $order_total = 0.0;

    //     foreach ($items as $item) {
    //         $product_id = absint($item['product_id'] ?? 0);
    //         $quantity = absint($item['quantity'] ?? 1);
    //         $variation_data = $item['variation'] ?? [];

    //         if (!$product_id || $quantity <= 0) continue;

    //         $product = wc_get_product($product_id);
    //         if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
    //             $failed_items[] = [
    //                 'product_id' => $product_id,
    //                 'reason' => 'Product not purchasable or out of stock.',
    //             ];
    //             continue;
    //         }

    //         $is_variable = $product->is_type('variable');
    //         $final_product = $product;
    //         $variation_id = 0;
    //         $variation_attributes = [];

    //         if ($is_variable) {
    //             foreach ($variation_data as $key => $value) {
    //                 $variation_attributes['attribute_' . sanitize_title($key)] = $value;
    //             }

    //             $variation_id = $this->find_matching_product_variation_id($product, $variation_attributes);
    //             if (!$variation_id) {
    //                 $failed_items[] = [
    //                     'product_id' => $product_id,
    //                     'reason' => 'No matching variation found.',
    //                 ];
    //                 continue;
    //             }

    //             $variation = wc_get_product($variation_id);
    //             if (!$variation || !$variation->is_in_stock()) {
    //                 $failed_items[] = [
    //                     'product_id' => $product_id,
    //                     'variation_id' => $variation_id,
    //                     'reason' => 'Variation out of stock.',
    //                 ];
    //                 continue;
    //             }

    //             if ($variation->managing_stock() && $variation->get_stock_quantity() < $quantity) {
    //                 $failed_items[] = [
    //                     'product_id' => $product_id,
    //                     'variation_id' => $variation_id,
    //                     'reason' => 'Insufficient variation stock.',
    //                     'requested' => $quantity,
    //                     'available' => $variation->get_stock_quantity(),
    //                 ];
    //                 continue;
    //             }

    //             $final_product = $variation;
    //         } else {
    //             if ($product->managing_stock() && $product->get_stock_quantity() < $quantity) {
    //                 $failed_items[] = [
    //                     'product_id' => $product_id,
    //                     'reason' => 'Insufficient stock.',
    //                     'requested' => $quantity,
    //                     'available' => $product->get_stock_quantity(),
    //                 ];
    //                 continue;
    //             }
    //         }

    //         $unit_price = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $final_product->get_price();
    //         $modifier_cost = isset($item['modifier_extra_cost']) ? (float) $item['modifier_extra_cost'] : 0.0;
    //         $final_price = $unit_price;

    //         $add_args = [
    //             'subtotal' => $final_price * $quantity,
    //             'total'    => $final_price * $quantity
    //         ];

    //         if ($is_variable) {
    //             $add_args['variation_id'] = $variation_id;
    //             $add_args['variation'] = $variation_attributes;
    //         }

    //         $item_id = $kot_order->add_product($final_product, $quantity, $add_args);
    //         $order_item = $kot_order->get_item($item_id);

    //         if (!empty($item['product_modifiers'])) {
    //             $order_item->add_meta_data('_product_modifiers', implode(',', $item['product_modifiers']), true);
    //         }

    //         if ($modifier_cost > 0) {
    //             $order_item->add_meta_data('_modifier_extra_cost', $modifier_cost, true);
    //         }

    //         $order_item->save();

    //         $successful_items[] = [
    //             'product_id'          => $product_id,
    //             'variation_id'        => $variation_id,
    //             'variation'           => $variation_attributes,
    //             'quantity'            => $quantity,
    //             'unit_price'          => $unit_price,
    //             'modifier_extra_cost' => $modifier_cost,
    //             'total_price'         => $final_price * $quantity,
    //         ];

    //         $order_total += $final_price * $quantity;
    //     }

    //     $applied_coupons = [];
    //     if (!empty($params['coupons']) && is_array($params['coupons'])) {
    //         foreach ($params['coupons'] as $coupon_code_raw) {
    //             $coupon_code = wc_format_coupon_code(sanitize_text_field($coupon_code_raw));
    //             $coupon = new WC_Coupon($coupon_code);

    //             if (!$coupon->get_id() || !$coupon->is_valid()) {
    //                 $failed_items[] = [
    //                     'coupon_code' => $coupon_code,
    //                     'reason'      => 'Invalid or expired coupon.',
    //                 ];
    //                 continue;
    //             }

    //             $kot_order->apply_coupon($coupon);
    //             $applied_coupons[] = $coupon_code;
    //         }
    //     }

    //     $kot_order->calculate_totals();
    //     $kot_order->save();

    //     return new WP_REST_Response([
    //         'success'           => true,
    //         'kot_order_id'      => $kot_order->get_id(),
    //         'kot_number'        => $kot_number,
    //         'order_status'      => $kot_order->get_status(),
    //         'total'             => $kot_order->get_total(),
    //         'applied_coupons'   => $applied_coupons,
    //         'failed_items'      => $failed_items,
    //         'successful_items'  => $successful_items,
    //     ]);
    // }

    // Helper: Match variation
    private function find_matching_product_variation_id($product, $attributes) 
    {
        if (!$product || !$product->is_type('variable')) return false;

        $available_variations = $product->get_available_variations();
        foreach ($available_variations as $variation) {
            $matched = true;
            foreach ($attributes as $key => $val) {
                if (!isset($variation['attributes'][$key]) || strtolower($variation['attributes'][$key]) !== strtolower($val)) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) return $variation['variation_id'];
        }
        return false;
    }

    public function update_kot_order_status_via_api($request) {
        $order_id = absint($request['id']);
        $new_status = sanitize_text_field($request['status']);
        // $new_status = 'wc-'.$new_status;
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('not_found', 'Order not found', ['status' => 404]);
        }

        $parent_id = $order->get_parent_id();
        if ($parent_id === 0) {
            return new WP_Error('not_kot', 'Not a KOT child order', ['status' => 400]);
        }

        $order->set_parent_id($parent_id);
  
        $order->set_status($new_status);
        $order->save();

        return rest_ensure_response([
            'success' => true,
            'message' => "KOT Order status updated to $new_status",
            'order_id' => $order_id,
        ]);
    }
}