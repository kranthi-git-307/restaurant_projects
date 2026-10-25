<?php

class Pinaka_POS_Combo {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    
        // This is the first step, letting WooCommerce know about your new product type.
        add_filter('product_type_selector', [$this, 'register_product_type']);
        add_filter('woocommerce_product_class', [$this, 'register_product_class'], 10, 2);
        add_filter('woocommerce_product_type', [$this, 'woo_product_type'], 10, 2);
        add_action( 'woocommerce_add_to_cart', [$this, 'pk_add_combo_children_to_cart'], 10, 6 );
        // --- 2. Admin: Product Edit Page UI & Scripts ---
        // These hooks build the user interface for managing the bundle on the product edit screen.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('woocommerce_product_data_tabs', [$this, 'add_product_data_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_fields']);
        add_action('admin_footer', [$this, 'add_admin_footer_script']);

        // --- 3. Admin: AJAX & Data Processing ---
        // These hooks handle the backend logic for the admin UI, like searching for products and saving data.
        add_action('wp_ajax_combo_bundle_search_products', [$this, 'search_products']);
        add_action('woocommerce_process_product_meta_combo_bundle', [$this, 'save_product_data']);

        // --- 4. Frontend: Single Product Page Display ---
        // This hook controls how the bundle is displayed to customers on the product page.
        add_action('woocommerce_single_product_summary', [$this, 'display_combo_bundle_items'], 25);

        // --- 5. Frontend: Cart & Checkout Logic ---
        // These hooks manage the bundle once it's in the cart: validation and display.
        add_action('woocommerce_check_cart_items', [$this, 'check_cart_items']);
        add_filter('woocommerce_cart_item_name', [$this, 'show_combo_bundle_items_in_cart'], 10, 3);
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'show_combo_bundle_items_in_checkout'], 10, 3);

        // --- 6. Order Processing ---
        // This hook handles inventory management when a customer completes a purchase.
        add_action('woocommerce_reduce_order_stock', [$this, 'reduce_order_stock']);

        // --- 7. Order Display (Frontend & Admin) ---
        // This hook ensures the bundled items are correctly displayed in the final order details.
        add_filter('woocommerce_order_item_name', [$this, 'show_combo_bundle_items_in_order'], 10, 3);
    }

    // ----------------------
    // 1. Register product type
    // ----------------------
    public function register_product_type($types) 
    {
        $types['combo_bundle'] = __('Combo Bundle', 'wc-combo-bundle');
        return $types;
    }

    public function register_product_class($classname, $type) 
    {
        return $type === 'combo_bundle' ? 'WC_Product_combo_bundle' : $classname;
    }

    public function woo_product_type($type, $product) 
    {
        if ($product && $product->get_type() === 'combo_bundle') return 'simple';
        return $type;
    }
    public function pk_add_combo_children_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        $product = wc_get_product( $product_id );

        if ( $product && $product->get_type() === 'combo_bundle' ) {
            $items = json_decode( get_post_meta( $product_id, '_combo_bundle_items', true ), true );

            if ( ! empty( $items ) ) {
                foreach ( $items as $item ) {
                    $child = wc_get_product( $item['id'] );
                    if ( ! $child ) continue;

                    // Multiply child quantity by parent quantity
                    $child_qty = $item['qty'] * $quantity;

                    // Add child product as its own cart line
                    WC()->cart->add_to_cart(
                        $child->get_id(),
                        $child_qty,
                        0,
                        [],
                        [ 'combo_parent' => $product_id ]
                    );
                }
            }
        }
    }

    public function add_product_data_tab($tabs) {
        $tabs['combo_bundle'] = [
            'label' => __('Bundle', 'wc-combo-bundle'),
            'target' => 'combo_bundle_options',
            'class' => ['show_if_combo_bundle'],
            'priority' => 21
        ];
        return $tabs;
    }

    public function enqueue_admin_scripts($hook) {
        global $post;
        if (!in_array($hook, ['post.php', 'post-new.php']) || get_post_type($post) !== 'product') return;

        wp_enqueue_script('select2');
        wp_enqueue_style('select2');

        wp_enqueue_script('wc-combo-bundle-admin', plugin_dir_url(dirname(dirname(__FILE__))) . 'assests/js/combo-admin.js', ['jquery', 'select2'], null, true);
        wp_localize_script('wc-combo-bundle-admin', 'combo_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('combo_bundle_search'),
            'placeholder' => __('-- Search for Product --', 'wc-combo-bundle')
        ]);
    }

    public function search_products() {
        check_ajax_referer('combo_bundle_search', 'nonce');

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        $results = [];

        if (empty($search_term)) {
            wp_send_json(['results' => $results]);
        }

        // === Step 1: Search products by title
        $args = [
            'post_type'      => ['product', 'product_variation'],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            's'              => $search_term, // standard WP search (title + content)
        ];
        $query = new WP_Query($args);

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
             if ($product->is_type('variable')) {
                // It's a variable product, so get its children (the variations)
                $variation_ids = $product->get_children();
          
                foreach ($variation_ids as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    // Only add variations that are enabled and purchasable
                    if ($variation && $variation->exists() && $variation->is_purchasable()) {
                        $results[] = [
                            'id'   => $variation->get_id(),
                            'text' => wp_strip_all_tags($variation->get_formatted_name()),
                            'price' => $variation->get_price(),
                        ];
                    }
                }
            } else {
                // It's a simple product, so add it directly
                $results[] = [
                    'id'   => $product->get_id(),
                    'text' => wp_strip_all_tags($product->get_formatted_name()),
                    'price' => $product->get_price(),
                ];
            }
        }
        // Remove duplicates by product ID
        $results = array_values(array_unique($results, SORT_REGULAR));
        wp_send_json(['results' => $results]);
    }

    public function add_product_data_fields() {
        global $post;

        $items = json_decode(get_post_meta($post->ID, '_combo_bundle_items', true), true) ?: [];
        $custom_price = get_post_meta($post->ID, '_combo_bundle_custom_price', true);
        ?>
        <div id="combo_bundle_options" class="panel woocommerce_options_panel">
            <p><strong>Bundle Products:</strong></p>
            <table class="widefat">
                <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th></th></tr></thead>
                <tbody id="bundle-items">
                    <?php foreach ($items as $item): 
                        $product = wc_get_product($item['id']);
                        $price_html = $product ? $product->get_price_html() : '';
                    ?>
                    <tr>
                        <td>
                            <select class="combo-product-select" 
                                name="combo_bundle_items[id][]"
                                data-initial-id="<?php echo intval($item['id']); ?>"
                                data-initial-text="<?php echo esc_attr(get_the_title($item['id'])); ?>"
                                style="width:300px;" required>
                            </select>
                        </td>
                        <td><?php if ($price_html): ?>
                            <div style="margin-top:5px; font-size:0.9em; color:#555;">
                                <?php echo $price_html; ?>
                            </div>
                        <?php endif; ?></td>
                        <td><input type="number" name="combo_bundle_items[qty][]" value="<?php echo intval($item['qty']); ?>" min="1" required style="width:80px;"></td>
                        <td><button type="button" class="remove-item button">Remove</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" id="add-bundle-item" class="button" style="float:right; margin-top:10px; margin-right:5px;">+ Add Product</button>
            <div style="clear:both;"></div>
            <p style="margin-top:15px;">
                <label for="combo_bundle_custom_price"><strong><?php esc_html_e('Custom Price', 'wc-combo-bundle'); ?>:</strong></label>
                <input id="combo_bundle_custom_price" type="number" step="0.01" min="0" name="combo_bundle_custom_price"
                    value="<?php echo esc_attr($custom_price); ?>"
                    style="width:120px; margin-left:5px;" placeholder="<?php esc_attr_e('Custom Price', 'wc-combo-bundle'); ?>"/>
            </p>
        </div>
        <?php
    }

    public function save_product_data($post_id) {
        $items = [];
        if (!empty($_POST['combo_bundle_items']['id'])) {
            $ids = $_POST['combo_bundle_items']['id'];
            $qtys = $_POST['combo_bundle_items']['qty'];
            for ($i=0; $i<count($ids); $i++) {
                $id = intval($ids[$i]);
                $qty = max(1, intval($qtys[$i]));
                // if ($id) $items[] = ['id'=>$id, 'qty'=>$qty];
                if ($id) {
                    $product = wc_get_product($id);
                    if ($product) {
                        $items[] = [
                            'id'    => $id,
                            'name'  => $product->get_name(),
                            'price' => wc_get_price_to_display($product),
                            'qty'   => $qty,
                        ];
                    }
                }
            }
            // Sort once outside the loop
            usort($items, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
            global $wpdb;
            // Check for duplicates
            $existing_combos = $wpdb->get_results("
                SELECT post_id, meta_value 
                FROM {$wpdb->postmeta}
                WHERE meta_key = '_combo_bundle_items'
                AND post_id != {$post_id}
            ");

            foreach ($existing_combos as $combo) {
                $existing_items = json_decode($combo->meta_value, true) ?: [];
                usort($existing_items, function($a, $b) {
                    return $a['id'] <=> $b['id'];
                });

                $normalized_new = array_map(fn($it) => ['id' => $it['id'], 'qty' => $it['qty']], $items);
                $normalized_existing = array_map(fn($it) => ['id' => $it['id'], 'qty' => $it['qty']], $existing_items);

                if ($normalized_new === $normalized_existing) {
                    wp_die(
                        __('Error: A combo with these exact items and quantities already exists.', 'wc-combo-bundle'),
                        __('Duplicate Combo', 'wc-combo-bundle'),
                        ['back_link' => true]
                    );
                }
            }
        }
        update_post_meta($post_id, '_combo_bundle_items', wp_json_encode($items));
        update_post_meta($post_id, '_combo_bundle_custom_price', sanitize_text_field($_POST['combo_bundle_custom_price'] ?? ''));
    }

    // ----------------------
    // 4. Show bundle items everywhere (Cart, Order, Checkout)
    // ----------------------

    public function get_combo_bundle_items_html($bundle_product_id) {
        // Get the array of item data from the product's meta field
        $items = json_decode(get_post_meta($bundle_product_id, '_combo_bundle_items', true), true) ?: [];
        $html = '';

        if ($items) {
            $html .= '<ul class="bundle-items-list" style="margin:5px 0 0 15px;font-size:0.9em;color:#555;">';

            foreach ($items as $item) {
                // Get the full product object for the item
                $child_product = wc_get_product($item['id']);

                if ($child_product) {
                    $child_name = $child_product->get_formatted_name();
                    $single_item_price = wc_price($child_product->get_price());

                    // Use wp_kses_post to allow safe HTML tags like <span>
                    $html .= '<li>'
                        . wp_kses_post($child_name)
                        . ' (' . $single_item_price . ' x ' . intval($item['qty']) . ')'
                        . '</li>';
                }
            }
            $html .= '</ul>';
        }
        return $html;
    }
    public function show_combo_bundle_items_in_cart($name, $cart_item, $key) {
        $product = $cart_item['data'];
        if ($product->get_type() === 'combo_bundle') {
            // Append the detailed HTML for bundled items
            return $name . $this->get_combo_bundle_items_html($product->get_id());
        }
        return $name;
    }

    public function show_combo_bundle_items_in_order($name, $item, $is_visible) {
        $product = $item->get_product();
        if ($product && $product->get_type() === 'combo_bundle') {
            // Append the detailed HTML for bundled items
            return $name . $this->get_combo_bundle_items_html($product->get_id());
        }
        return $name;
    }

    public function show_combo_bundle_items_in_checkout($quantity_html, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        if ($product->get_type() === 'combo_bundle') {
            // Append the detailed HTML for bundled items after the quantity
            $details_html = $this->get_combo_bundle_items_html($product->get_id());
            return $quantity_html . $details_html;
        }
        return $quantity_html;
    }

    // ----------------------
    // 5. Check & reduce stock
    // ----------------------
    public function check_cart_items() {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            if ($product->get_type() === 'combo_bundle') {
                $items = json_decode(get_post_meta($product->get_id(), '_combo_bundle_items', true), true) ?: [];
                foreach ($items as $item) {
                    $child = wc_get_product($item['id']);
                    if ($child && !$child->has_enough_stock($item['qty'] * $cart_item['quantity'])) {
                        wc_add_notice(sprintf(__('Not enough stock for %s', 'wc-combo-bundle'), get_the_title($item['id'])), 'error');
                        return;
                    }
                }
            }
        }
    }

    public function reduce_order_stock($order) {
       if (!$order) return;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_type() === 'combo_bundle') {
                $bundle_items = json_decode(get_post_meta($product->get_id(), '_combo_bundle_items', true), true) ?: [];
                foreach ($bundle_items as $child_item) {
                    $child_product = wc_get_product($child_item['id']);
                    if ($child_product) {
                        wc_update_product_stock( 
                            $child_product, 
                            $child_item['qty'] * $item->get_quantity(), 
                            'decrease'
                        );
                    }
                }
            }
        }
    }

    // ----------------------
    // 6. Single product display
    // ----------------------
    public function display_combo_bundle_items() 
    {
        global $product;
        if ($product->get_type() !== 'combo_bundle') {
            return;
        }

        $items = json_decode(get_post_meta($product->get_id(), '_combo_bundle_items', true), true) ?: [];

        if ($items) {
            echo '<div class="bundle-included" style="margin-top:10px;">
                <h4>Included Items:</h4>
                <ul>';

            foreach ($items as $item) {
                $child_product = wc_get_product($item['id']);

                if ($child_product) {
                    $child_name = $child_product->get_formatted_name();
                    $single_price = wc_price($child_product->get_price());

                    // Use wp_kses_post to allow safe HTML tags like <span>
                    echo '<li>'
                        . wp_kses_post($child_name)
                        . ' (' . $single_price . ' x ' . intval($item['qty']) . ')'
                        . '</li>';
                }
            }
            echo '</ul></div><br>';
        }

        // Load the add-to-cart template
        wc_get_template('single-product/add-to-cart/simple.php');
    }

    // ----------------------
    // 7. Default tab auto select
    // ----------------------
    public function add_admin_footer_script() {
        global $post;
        if ('product' !== get_post_type() || !$post) return;
        ?>
        <script>
        jQuery(function($){
            function toggleComboPriceFields() {
                var type = $('#product-type').val();
                if (type === 'combo_bundle') {
                    $('.pricing, .sale_schedule').closest('.options_group').hide();
                } else {
                    $('.pricing, .sale_schedule').closest('.options_group').show();
                }
            }
            toggleComboPriceFields();
            $('#product-type').on('change', toggleComboPriceFields);
        });
        </script>
        <?php
    }
}

// -----------------------------
// Separated product class file
// -----------------------------
if (class_exists('WC_Product_Simple')) {
    class WC_Product_combo_bundle extends WC_Product_Simple {

        public function get_type() { return 'combo_bundle'; }

        public function get_price( $context = 'view' ) {
            $custom = get_post_meta( $this->get_id(), '_combo_bundle_custom_price', true );
            if ( $custom !== '' ) return floatval( $custom );

            $items = json_decode(get_post_meta( $this->get_id(), '_combo_bundle_items', true), true) ?: [];
            $total = 0;
            foreach ( $items as $item ) {
                $child = wc_get_product( $item['id'] );
                if ( $child ) $total += $child->get_price() * $item['qty'];
            }
            return $total;
        }
        public function get_regular_price( $context = 'view' ) { return $this->get_price( $context ); }
        public function get_sale_price( $context = 'view' ) { return $this->get_price( $context ); }
        public function get_price_html( $context = 'view' ) { return wc_price( $this->get_price( $context ) ); }

        public function is_purchasable() { return true; }
        public function is_in_stock() { return true; }
        public function get_stock_status( $context = 'view' ) { return 'instock'; }
        public function is_virtual() { return false; }
        public function needs_shipping() { return false; }

        public function supports( $feature ) {
            if (in_array($feature, ['ajax_add_to_cart', 'simple', 'purchasable', 'sold_individually'])) {
                return true;
            }
            return parent::supports($feature);
        }
    }
}
