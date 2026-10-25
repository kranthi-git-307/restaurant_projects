<?php 
/**
 * The admin-discounts functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Discounts {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $name        The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $loader;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = new Pinaka_POS_Loader();
        $this->define_admin_hooks();

        add_action('add_meta_boxes', [$this, 'add_discount_meta_boxes']);
        add_action('save_post_discounts', [$this, 'save_discount_meta']);
        add_filter('manage_discounts_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_discounts_posts_custom_column', [$this, 'custom_column_content'], 10, 2);      
        add_action('woocommerce_before_cart_totals', 'custom_discount_code_field');

    }

    public function pinaka_apply_mix_and_match_discounts($cart)
    {
        // Initial checks (no change)
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (did_action('woocommerce_before_calculate_totals') >= 2) return;

        $discounts = get_posts([
            'post_type'      => 'discounts',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        foreach ($discounts as $discount) {
            $required_ids     = (array) get_post_meta($discount->ID, '_required_product_ids', true);
            $target_ids       = (array) get_post_meta($discount->ID, '_product_ids', true);
            $type             = get_post_meta($discount->ID, '_discount_type', true);
            $amount           = floatval(get_post_meta($discount->ID, '_coupon_amount', true));

            $expiry_date      = get_post_meta($discount->ID, '_expiry_date', true);
            $min_spend        = floatval(get_post_meta($discount->ID, '_minimum_amount', true));
            $max_spend        = floatval(get_post_meta($discount->ID, '_maximum_amount', true));
            $individual_use   = get_post_meta($discount->ID, '_individual_use', true);
            $exclude_sale     = get_post_meta($discount->ID, '_exclude_sale_items', true);
            $min_qty          = intval(get_post_meta($discount->ID, '_min_qty', true)); // This will now apply per item
            $max_qty          = intval(get_post_meta($discount->ID, '_max_qty', true)); // This will now apply per item
            
            if (empty($required_ids) || empty($target_ids) || !$amount) continue;

            // 1. Expiry
            if (!empty($expiry_date) && strtotime($expiry_date) < time()) continue;

            // 2. Min/max cart subtotal
            $cart_subtotal = 0;
            foreach ($cart->get_cart() as $item) {
                $cart_subtotal += $item['data']->get_price() * $item['quantity'];
            }
            if ($cart_subtotal === 0) continue;
            if ($min_spend && $cart_subtotal < $min_spend) continue;
            if ($max_spend && $cart_subtotal > $max_spend) continue;
            
            // 3. Individual use only
            if ($individual_use && !empty($cart->get_applied_coupons())) continue;

            // 4. Global Exclude Sale Items Check: If ALL target products in the cart are on sale, then skip the entire discount.
            if ($exclude_sale) {
                $all_target_products_on_sale = true;
                $found_any_target_product = false;

                foreach ($cart->get_cart() as $item) {
                    if (in_array($item['product_id'], $target_ids)) {
                        $found_any_target_product = true;
                        if (!$item['data']->is_on_sale()) {
                            $all_target_products_on_sale = false; // Found a non-sale target product
                            break;
                        }
                    }
                }

                if ($found_any_target_product && $all_target_products_on_sale) {
                    continue; // Skip the entire discount
                }
            }
           
            // 5. Required qty (still applies to sum of required products)
            $required_qty = 0;
            foreach ($cart->get_cart() as $item) {
                if (in_array($item['product_id'], $required_ids)) {
                    $required_qty += $item['quantity'];
                }
            }
           
            if ($required_qty === 0) continue;

            // ============= IDENTIFY ELIGIBLE ITEMS & THEIR TOTAL QUANTITY/VALUE ==============
            // These checks now include min_qty and max_qty on a per-item basis.

            $eligible_for_discount_items = []; // This will hold cart items that actually get discounted
            $total_eligible_qty_for_discount = 0; // Sum of quantities of items that are eligible for discount
            $total_eligible_value_for_discount = 0; // Sum of values of items that are eligible for discount
            $eligible_ids = array_unique(array_merge($target_ids, $required_ids));
          
            foreach ($cart->get_cart() as $cart_item_key => $item) 
            {
                $product_id = $item['product_id'];
                $product_obj = $item['data'];
                $item_qty = $item['quantity']; // Current quantity of this specific item in cart
             
                // 1. Must be a target product for THIS discount
                if (!in_array($product_id, $eligible_ids)) {
                    continue;
                }
            
                // 2. If 'exclude_sale_items' is true AND the product is on sale, skip this product.
                if ($exclude_sale && $product_obj->is_on_sale()) {
                    continue;
                }
                
                // 3. Item-wise Min Quantity Check
                if ($min_qty && $item_qty < $min_qty) {
                    // This specific target item's quantity is less than the required min_qty. Skip it.
                    continue;
                }

                // 4. Item-wise Max Quantity Check
                if ($max_qty && $item_qty > $max_qty) {
                    // This specific target item's quantity is more than the allowed max_qty. Skip it.
                    continue;
                }

                // If it passes all checks, it's truly eligible to receive the discount
                if (in_array($product_id, $target_ids)) 
                {
                    $eligible_for_discount_items[$cart_item_key] = $item;
                    $total_eligible_qty_for_discount += $item_qty;
                    $total_eligible_value_for_discount += $product_obj->get_price() * $item_qty;
                }
            }

            // If after filtering, no items are left to apply the discount to, skip this discount
            if (empty($eligible_for_discount_items) || $total_eligible_qty_for_discount === 0) {
                continue; // No products qualified for the discount after all item-wise exclusions
            }

            // ============= Apply Discount to ELIGIBLE items ==============

            // --- FIXED CART DISCOUNT ---
            if ($type === 'fixed_cart') {
                // Fixed amount off the cart total
                $actual_discount = min($amount, $total_eligible_value_for_discount);
                if ($actual_discount > 0) {
                    $label = get_the_title($discount->ID);
                    $cart->add_fee($label, -1 * $actual_discount, false);
                }
            }

            // --- FIXED PRODUCT DISCOUNT ---
            elseif ($type === 'fixed_product') {
                if ($total_eligible_qty_for_discount > 0) {
                    $per_unit_discount = $amount / $total_eligible_qty_for_discount;

                    foreach ($eligible_for_discount_items as $cart_item_key => $item) {
                        $product = $item['data'];
                        $original_price = $product->get_price();
                        $discounted_price = max(0, $original_price - $per_unit_discount);

                        $product->set_price(wc_format_decimal($discounted_price, wc_get_price_decimals()));
                    }
                }
            }

            // --- PERCENT DISCOUNT ---
            elseif ($type === 'percent') {
                foreach ($eligible_for_discount_items as $cart_item_key => $item) {
                    $product = $item['data'];
                    $original_price = $product->get_price();
                    $discounted_price = max(0, $original_price * (1 - $amount / 100));

                    $product->set_price(wc_format_decimal($discounted_price, wc_get_price_decimals()));
                }
            }
            
        }
    }
    public function pinaka_apply_mix_and_match_discounts_to_order($order, $data) {
        $discounts = get_posts([
            'post_type'      => 'discounts',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        foreach ($discounts as $discount) {
            $required_ids    = (array) get_post_meta($discount->ID, '_required_product_ids', true);
            $target_ids      = (array) get_post_meta($discount->ID, '_product_ids', true);
            $type           = get_post_meta($discount->ID, '_discount_type', true);
            $amount         = floatval(get_post_meta($discount->ID, '_coupon_amount', true));
            $expiry_date    = get_post_meta($discount->ID, '_expiry_date', true);
            $min_spend      = floatval(get_post_meta($discount->ID, '_minimum_amount', true));
            $max_spend      = floatval(get_post_meta($discount->ID, '_maximum_amount', true));
            $individual_use = get_post_meta($discount->ID, '_individual_use', true);
            $exclude_sale   = get_post_meta($discount->ID, '_exclude_sale_items', true);
            $min_qty        = intval(get_post_meta($discount->ID, '_min_qty', true));
            $max_qty        = intval(get_post_meta($discount->ID, '_max_qty', true));

            if (empty($required_ids) || empty($target_ids) || !$amount) continue;
            if (!empty($expiry_date) && strtotime($expiry_date) < time()) continue;

            // Calculate order subtotal (sum of all line totals)
            $order_subtotal = 0;
            foreach ($order->get_items() as $item) {
                $order_subtotal += $item->get_total();
            }

            if ($order_subtotal === 0) continue;
            if ($min_spend && $order_subtotal < $min_spend) continue;
            if ($max_spend && $order_subtotal > $max_spend) continue;

            // Skip if individual use but order has coupons applied
            if ($individual_use && count($order->get_coupon_codes()) > 0) continue;

            // Check required qty across order items
            $required_qty = 0;
            foreach ($order->get_items() as $item) {
                if (in_array($item->get_product_id(), $required_ids)) {
                    $required_qty += $item->get_quantity();
                }
            }
            if ($required_qty === 0) continue;

            // Exclude if all target products on sale
            if ($exclude_sale) {
                $all_on_sale = true;
                $found_target = false;
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if (in_array($item->get_product_id(), $target_ids)) {
                        $found_target = true;
                        if (!$product->is_on_sale()) {
                            $all_on_sale = false;
                            break;
                        }
                    }
                }
                if ($found_target && $all_on_sale) continue;
            }

            // Identify eligible items & total qty/value
            $eligible_for_discount_items = [];
            $total_eligible_qty = 0;
            $total_eligible_value = 0;
            $eligible_ids = array_unique(array_merge($target_ids, $required_ids));

            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $product = $item->get_product();
                $qty = $item->get_quantity();

                if (!in_array($product_id, $eligible_ids)) continue;
                if ($exclude_sale && $product->is_on_sale()) continue;
                if ($min_qty && $qty < $min_qty) continue;
                if ($max_qty && $qty > $max_qty) continue;

                if (in_array($product_id, $target_ids)) {
                    $eligible_for_discount_items[$item_id] = $item;
                    $total_eligible_qty += $qty;
                    $total_eligible_value += $item->get_total();
                }
            }

            if (empty($eligible_for_discount_items) || $total_eligible_qty === 0) continue;

            // Apply discounts to eligible order items
            if ($type === 'fixed_cart') {
                // Fixed amount discount applied as a negative fee
                $actual_discount = min($amount, $total_eligible_value);
                if ($actual_discount > 0) {
                    // Add a negative fee to the order (works for WC 3.0+)
                    $order->add_order_fee(wc()->cart->add_fee(get_the_title($discount->ID), -$actual_discount));
                }
            } elseif ($type === 'fixed_product') {
                $per_unit_discount = $amount / $total_eligible_qty;
                foreach ($eligible_for_discount_items as $item_id => $item) {
                    $qty = $item->get_quantity();
                    $line_total = $item->get_total();

                    $discount_amount = $per_unit_discount * $qty;
                    $new_total = max(0, $line_total - $discount_amount);

                    $item->set_total($new_total);
                    $item->set_subtotal($new_total);
                }
            } elseif ($type === 'percent') {
                foreach ($eligible_for_discount_items as $item_id => $item) {
                    $line_total = $item->get_total();
                    $new_total = max(0, $line_total * (1 - $amount / 100));

                    $item->set_total($new_total);
                    $item->set_subtotal($new_total);
                }
            }
        }
    }

    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_discounts_post_type() {
        $labels = array(
            'name'                  => __('Discounts', 'pinaka-pos'),
            'singular_name'         => __('Discount', 'pinaka-pos'),
            'add_new'               => __('Add New Discount', 'pinaka-pos'),
            'add_new_item'          => __('Add New Discount', 'pinaka-pos'),
            'edit_item'             => __('Edit Discount', 'pinaka-pos'),
            'new_item'              => __('New Discount', 'pinaka-pos'),
            'view_item'             => __('View Discount', 'pinaka-pos'),
            'search_items'          => __('Search Discounts', 'pinaka-pos'),
            'not_found'             => __('No Discounts found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No Discounts found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Discounts', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Discounts custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'discounts'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('discounts', $args);
    }

    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns($columns) {
    $columns['discount_type'] = __('Discount Type', 'pinaka-pos');
    $columns['discount_amount'] = __('Discount Amount', 'pinaka-pos');
    $columns['discount_description'] = __('Description', 'pinaka-pos');
    $columns['discount_products'] = __('Product IDs', 'pinaka-pos');
    $columns['discount_usage_limit'] = __('Usage / Limit', 'pinaka-pos');
    $columns['discount_expiry'] = __('Expiry Date', 'pinaka-pos');
    return $columns;
}


    /**
     * Render the column values.
     */

    public function custom_column_content($column, $post_id) {
    switch ($column) {
        case 'discount_type':
            echo esc_html(get_post_meta($post_id, '_discount_type', true));
            break;
        case 'discount_amount':
            echo esc_html(get_post_meta($post_id, '_coupon_amount', true));
            break;
        case 'discount_description':
            echo esc_html(get_post_meta($post_id, '_discount_description', true));
            break;
        case 'discount_products':
           $product_ids = get_post_meta($post_id, '_product_ids', true);
           $min_qty = get_post_meta($post_id, '_min_qty', true);
           $max_qty = get_post_meta($post_id, '_max_qty', true);
           $required_ids = get_post_meta($post_id, '_required_product_ids', true);

           $output = [];

           if (!empty($product_ids) && is_array($product_ids)) {
                $names = array_map(function($pid) {
                    $p = get_post($pid);
                    return $p ? esc_html($p->post_title) : '';
                }, $product_ids);
                $output[] = '<strong>Products:</strong><br>' . implode('<br>', array_filter($names));
            }

            if (!empty($min_qty) || !empty($max_qty)) {
                $qty = '<strong>Qty:</strong> ';
                if ($min_qty) $qty .= 'Min: ' . esc_html($min_qty) . ' ';
                if ($max_qty) $qty .= 'Max: ' . esc_html($max_qty);
                $output[] = $qty;
            }

            if (!empty($required_ids) && is_array($required_ids)) {
                $req = array_map(function($pid) {
                    $p = get_post($pid);
                    return $p ? esc_html($p->post_title) : '';
                }, $required_ids);
                $output[] = '<strong>Required Products:</strong><br>' . implode('<br>', array_filter($req));
            }

            echo !empty($output) ? implode('<br><br>', $output) : '—';
            break;

        case 'discount_usage_limit':
            $min = get_post_meta($post_id, '_minimum_amount', true);
            $max = get_post_meta($post_id, '_maximum_amount', true);
            $individual = get_post_meta($post_id, '_individual_use', true);
            $exclude_sale = get_post_meta($post_id, '_exclude_sale_items', true);

            $data = [];

            if (!empty($min)) {
                $data[] = 'Min Spend: ₹' . esc_html($min);
            }

            if (!empty($max)) {
                $data[] = 'Max Spend: ₹' . esc_html($max);
            }

            if ($individual === 'yes') {
                $data[] = 'Individual Use Only';
            }

            if ($exclude_sale === 'yes') {
                $data[] = 'Exclude Sale Items';
            }

            echo !empty($data) ? implode('<br>', $data) : '—';
            break;
        case 'discount_expiry':
            echo esc_html(get_post_meta($post_id, '_expiry_date', true));
            break;
        }
    }



    /**
     * Define the admin hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // ✅ Ensure post type is registered before menu is built
        $this->loader->add_action('init', $this, 'register_shifts_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_shifts_submenu_page');
    }

    /**
     * Add the shifts submenu page.
     *
     * @since    1.0.0
     * @access   private
     */
    public function add_shifts_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage Vendors', 'pinaka-pos'),
            __('Manage Vendors', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-shiftss',
            [$this, 'shiftsRender']
        );
    }

    /**
     * Render the shifts submenu page.
     *
     * @since    1.0.0
     * @access   public
     */
    public function shiftsRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=shifts'));
        exit;
    }

    /**
     * Add meta boxes to Shift post type
     */
    public function add_discount_meta_boxes() {
        add_meta_box(
            'discount_tabs_box',
            __('Discount Settings', 'pinaka-pos'),
            [$this, 'render_discount_tabs_box'], // Only one render callback
            'discounts',
            'normal',
            'high'
        );
    }



    /**
     * Render Discount Details Meta Box
     */
    public function render_discount_tabs_box($post) {
        // Load existing meta values
        $description = get_post_meta($post->ID, '_discount_description', true);
        $min_spend = get_post_meta($post->ID, '_discount_min_spend', true);
        $max_usage = get_post_meta($post->ID, '_discount_max_usage', true);
        $discount_type = get_post_meta($post->ID, '_discount_type', true);
        $coupon_amount = get_post_meta($post->ID, '_coupon_amount', true);
        $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
        $minimum_amount = get_post_meta($post->ID, '_minimum_amount', true);
        $maximum_amount = get_post_meta($post->ID, '_maximum_amount', true);
        $individual_use = get_post_meta($post->ID, '_individual_use', true);
        $exclude_sale_items = get_post_meta($post->ID, '_exclude_sale_items', true);
        $min_quantity = get_post_meta($post->ID, '_min_qty', true);
        $max_quantity = get_post_meta($post->ID, '_max_qty', true);
        $product_ids = get_post_meta($post->ID, '_product_ids', true);

        ?>
    
        <div class="discount-tabs-wrap">
            <ul class="tabs">
                <li class="active" data-tab="tab-general">General</li>
                <li data-tab="tab-restrictions">Usage Restrictions</li>
                <!-- <li data-tab="tab-limits">Usage Limits</li> -->
            </ul>
    
            <div class="tab-content active panel" id="tab-general">
                <table class="form-table">
                    <tbody>
                        <!-- Discount Description -->
                        <tr class="form-field discount_description_field">
                            <th scope="row">
                                <label for="discount_description"><?php _e('Discount Description', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <textarea name="discount_description" id="discount_description" placeholder="Enter discount description..." class="widefat"><?php echo esc_textarea($description); ?></textarea>
                                <p class="description"><?php _e('Brief summary of what this discount does.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <!-- Discount Type -->
                        <tr class="form-field discount_type_field">
                            <th scope="row">
                                <label for="discount_type"><?php _e('Discount Type', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <select name="discount_type" id="discount_type" class="select short">
                                    <option value="percent" <?php selected($discount_type, 'percent'); ?>><?php _e('Percentage Discount', 'pinaka-pos'); ?></option>
                                    <option value="fixed_cart" <?php selected($discount_type, 'fixed_cart'); ?>><?php _e('Fixed Cart Discount', 'pinaka-pos'); ?></option>
                                    <option value="fixed_product" <?php selected($discount_type, 'fixed_product'); ?>><?php _e('Fixed Product Discount', 'pinaka-pos'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select whether the discount is a percentage, fixed cart, or fixed product.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <!-- Coupon Amount -->
                        <tr class="form-field coupon_amount_field">
                            <th scope="row">
                                <label for="coupon_amount"><?php _e('Coupon Amount', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input type="number" step="0.01" class="short wc_input_price" name="coupon_amount" id="coupon_amount" value="<?php echo esc_attr($coupon_amount); ?>" placeholder="0" />
                                <p class="description"><?php _e('Set the value of the discount. For percentage, use values like 10 for 10%.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                        <!-- Expiry Date -->
                        <tr class="form-field expiry_date_field">
                            <th scope="row">
                                <label for="expiry_date"><?php _e('Coupon Expiry Date', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input type="date" name="expiry_date" id="expiry_date" value="<?php echo esc_attr($expiry_date); ?>" placeholder="YYYY-MM-DD" />
                                <p class="description"><?php _e('Set the expiry date for this discount.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="tab-content" id="tab-restrictions">
                <table class="form-table">
                    <tbody>
                        <!-- Minimum Spend -->
                        <tr class="form-field minimum_amount_field">
                            <th scope="row">
                                <label for="minimum_amount"><?php _e('Minimum spend', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    class="short wc_input_price" 
                                    name="minimum_amount" 
                                    id="minimum_amount" 
                                    value="<?php echo esc_attr( $minimum_amount ); ?>" 
                                    placeholder="<?php esc_attr_e('No minimum', 'pinaka-pos'); ?>" 
                                />
                                <p class="description">
                                    <?php _e('This field allows you to set the minimum spend (subtotal) allowed to use the coupon.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Maximum Spend -->
                        <tr class="form-field maximum_amount_field">
                            <th scope="row">
                                <label for="maximum_amount"><?php _e('Maximum spend', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    class="short wc_input_price" 
                                    name="maximum_amount" 
                                    id="maximum_amount" 
                                    value="<?php echo esc_attr( $maximum_amount ); ?>" 
                                    placeholder="<?php esc_attr_e('No maximum', 'pinaka-pos'); ?>" 
                                />
                                <p class="description">
                                    <?php _e('This field allows you to set the maximum spend (subtotal) allowed when using the coupon.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Individual Use Only -->
                        <tr class="form-field individual_use_field">
                            <th scope="row">
                                <label for="individual_use"><?php _e('Individual use only', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="checkbox" 
                                    name="individual_use" 
                                    id="individual_use" 
                                    value="yes" 
                                    class="checkbox" 
                                    <?php checked( $individual_use, 'yes' ); ?> 
                                />
                                <span class="description">
                                    <?php _e('Check this box if the coupon cannot be used in conjunction with other coupons.', 'pinaka-pos'); ?>
                                </span>
                            </td>
                        </tr>

                        <!-- Exclude Sale Items -->
                        <tr class="form-field exclude_sale_items_field">
                            <th scope="row">
                                <label for="exclude_sale_items"><?php _e('Exclude sale items', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <input 
                                    type="checkbox" 
                                    name="exclude_sale_items" 
                                    id="exclude_sale_items" 
                                    value="yes" 
                                    class="checkbox" 
                                    <?php checked( $exclude_sale_items, 'yes' ); ?> 
                                />
                                <span class="description">
                                    <?php _e('Check this box if the coupon should not apply to items on sale.', 'pinaka-pos'); ?>
                                </span>
                            </td>
                        </tr>

                        <!-- Include Products -->
                        <tr class="form-field product_ids_field">
                            <th scope="row">
                                <label><?php _e('Products', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <select 
                                    class="wc-product-search select2" 
                                    name="product_ids[]" 
                                    id="product_ids" 
                                    multiple="multiple" 
                                    data-placeholder="<?php esc_attr_e('Search Products...', 'pinaka-pos'); ?>" 
                                    style="width: 100%;"
                                >
                                    <?php foreach ($product_ids as $product_id) :
                                            $product = wc_get_product($product_id);
                                            if ($product) : ?>
                                                <option value="<?php echo esc_attr($product_id); ?>" selected="selected">
                                                    <?php echo esc_html($product->get_name()); ?>
                                                </option>
                                            <?php endif;
                                        endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Products that the coupon will be applied to, or that need to be in the cart for a Fixed cart discount.', 'pinaka-pos'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr class="form-field product_ids_field">
                            <th scope="row">
                                <label><?php _e('Min-Max Quantity', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <table class="form-table widefat">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Min Qty', 'pinaka-pos'); ?></th>
                                            <th><?php _e('Max Qty', 'pinaka-pos'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <input type="number" name="min_qty" id="min_qty" value="<?php echo esc_attr($min_quantity); ?>" placeholder="Min Qty" />
                                            </td>
                                            <td>
                                                <input type="number" name="max_qty" id="max_qty" value="<?php echo esc_attr($max_quantity); ?>" placeholder="Max Qty" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                        <!-- Required Products for Mix & Match -->
                        <tr class="form-field required_product_ids_field">
                            <th scope="row">
                                <label><?php _e('Required Products for Discount', 'pinaka-pos'); ?></label>
                            </th>
                            <td>
                                <select 
                                    class="wc-product-search select2" 
                                    name="required_product_ids[]" 
                                    id="required_product_ids" 
                                    multiple="multiple" 
                                    data-placeholder="<?php esc_attr_e('Search Required Products...', 'pinaka-pos'); ?>" 
                                    style="width: 100%;">
                                    <?php 
                                    $required_product_ids = get_post_meta($post->ID, '_required_product_ids', true) ?: [];
                                    foreach ($required_product_ids as $product_id) :
                                        $product = wc_get_product($product_id);
                                        if ($product) : ?>
                                            <option value="<?php echo esc_attr($product_id); ?>" selected="selected">
                                                <?php echo esc_html($product->get_name()); ?>
                                            </option>
                                        <?php endif;
                                    endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Products that must be in the cart for the discount to apply.', 'pinaka-pos'); ?></p>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
    
        </div>
    
        <style>
            .discount-tabs-wrap .tabs {
                margin-bottom: 10px;
                list-style: none;
                padding: 0;
                display: flex;
                border-bottom: 1px solid #ccc;
            }
    
            .discount-tabs-wrap .tabs li {
                padding: 8px 14px;
                cursor: pointer;
                background: #f5f5f5;
                margin-right: 4px;
            }
    
            .discount-tabs-wrap .tabs li.active {
                background: #fff;
                border: 1px solid #ccc;
                border-bottom: none;
                border-radius: 4px 4px 0 0;
            }
    
            .discount-tabs-wrap .tab-content {
                display: none;
                background: #fff;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 0 4px 4px 4px;
            }
    
            .discount-tabs-wrap .tab-content.active {
                display: block;
            }

            #bulk-discount-container {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            #bulk-discount-container th,
            #bulk-discount-container td {
                border: 1px solid #ddd;
                padding: 10px;
                vertical-align: middle;
                text-align: center;
            }

            #bulk-discount-container thead th {
                background-color: #f9f9f9;
                font-weight: 600;
            }

            #bulk-discount-container input[type="number"] {
                width: 80%;
                padding: 4px;
                text-align: center;
            }

            #bulk-discount-container .wdr_desc_text {
                display: block;
                font-size: 11px;
                color: #666;
                margin-top: 4px;
            }

            #bulk-discount-container .dashicons-menu {
                cursor: move;
                font-size: 16px;
            }

            #bulk-discount-container .wdr_discount_remove {
                color: #a00;
                font-size: 18px;
                transition: color 0.2s ease;
            }

            #bulk-discount-container .wdr_discount_remove:hover {
                color: #dc3232;
            }

        </style>
    
        <script>
            jQuery(document).ready(function($) {
                $('.discount-tabs-wrap .tabs li').click(function() {
                    var tabId = $(this).data('tab');
                    $('.discount-tabs-wrap .tabs li').removeClass('active');
                    $(this).addClass('active');
                    $('.discount-tabs-wrap .tab-content').removeClass('active');
                    $('#' + tabId).addClass('active');
                });
            });

            jQuery(document).ready(function($) {
                // Tabs
                $('.discount-tabs-wrap .tabs li').on('click', function() {
                    var tab_id = $(this).data('tab');

                    $('.discount-tabs-wrap .tabs li').removeClass('active');
                    $('.discount-tabs-wrap .tab-content').removeClass('active');

                    $(this).addClass('active');
                    $('#' + tab_id).addClass('active');
                });

                // Bulk Discount Row Logic
                let bulkIndex = $('#bulk-discount-container .wdr-discount-group').length;

                $('#add-bulk-discount-row').on('click', function() {
                    let template = $('#bulk-discount-template').html().replace(/__INDEX__/g, bulkIndex);
                    $('#bulk-discount-container').append(template);
                    bulkIndex++;
                });

                $(document).on('click', '.wdr_discount_remove', function() {
                    $(this).closest('.wdr-discount-group').remove();
                });
            });

        </script>

        <?php
    }

    /**
     * Save Shift Meta Fields
     */
    public function save_discount_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        // echo    '<pre>';
        // print_r($_POST);
        // echo    '</pre>';
        // die;
        if (array_key_exists('discount_description', $_POST)) {
            update_post_meta($post_id, '_discount_description', sanitize_textarea_field($_POST['discount_description']));
        }

        if (array_key_exists('discount_type', $_POST)) {
            update_post_meta($post_id, '_discount_type', sanitize_text_field($_POST['discount_type']));
        }

        if (array_key_exists('coupon_amount', $_POST)) {
            update_post_meta($post_id, '_coupon_amount', sanitize_text_field($_POST['coupon_amount']));
        }

        if (array_key_exists('expiry_date', $_POST)) {
            update_post_meta($post_id, '_expiry_date', sanitize_text_field($_POST['expiry_date']));
        }

        if (array_key_exists('minimum_amount', $_POST)) {
            update_post_meta($post_id, '_minimum_amount', sanitize_text_field($_POST['minimum_amount']));
        }

        if (array_key_exists('maximum_amount', $_POST)) {
            update_post_meta($post_id, '_maximum_amount', sanitize_text_field($_POST['maximum_amount']));
        }

        if (array_key_exists('individual_use', $_POST)) {
            update_post_meta($post_id, '_individual_use', sanitize_text_field($_POST['individual_use']));
        } else {
            delete_post_meta($post_id, '_individual_use');
        }

        if (array_key_exists('exclude_sale_items', $_POST)) {
            update_post_meta($post_id, '_exclude_sale_items', sanitize_text_field($_POST['exclude_sale_items']));
        } else {
            delete_post_meta($post_id, '_exclude_sale_items');
        }   

        if (array_key_exists('product_ids', $_POST)) {
            update_post_meta($post_id, '_product_ids', array_map('sanitize_text_field', $_POST['product_ids']));
        } else {
            delete_post_meta($post_id, '_product_ids');
        }

        if (array_key_exists('min_qty', $_POST)) {
            update_post_meta($post_id, '_min_qty', sanitize_text_field($_POST['min_qty']));
        } else {
            delete_post_meta($post_id, '_min_qty');
        }

        if (array_key_exists('max_qty', $_POST)) {
            update_post_meta($post_id, '_max_qty', sanitize_text_field($_POST['max_qty']));
        } else {
            delete_post_meta($post_id, '_max_qty');
        }
        
        if (array_key_exists('required_product_ids', $_POST)) {
            update_post_meta($post_id, '_required_product_ids', array_map('sanitize_text_field', $_POST['required_product_ids']));
        } else {
            delete_post_meta($post_id, '_required_product_ids');
        }

        // if (array_key_exists('exclude_product_ids', $_POST)) {
        //     update_post_meta($post_id, '_exclude_product_ids', array_map('sanitize_text_field', $_POST['exclude_product_ids']));
        // } else {
        //     delete_post_meta($post_id, '_exclude_product_ids');
        // }

        // if (array_key_exists('product_categories', $_POST)) {
        //     update_post_meta($post_id, '_product_categories', array_map('sanitize_text_field', $_POST['product_categories']));
        // } else {
        //     delete_post_meta($post_id, '_product_categories');
        // }

        // if (array_key_exists('exclude_product_categories', $_POST)) {
        //     update_post_meta($post_id, '_exclude_product_categories', array_map('sanitize_text_field', $_POST['exclude_product_categories']));
        // } else {
        //     delete_post_meta($post_id, '_exclude_product_categories');
        // }

    }

    public function render_discount_description_box($post) {
        $value = get_post_meta($post->ID, '_discount_description', true);
        ?>
        <textarea 
            name="discount_description" 
            class="custom-discount-description" 
            placeholder="Enter discount description here..." 
            style="width:100%; min-height:120px;"><?php echo esc_textarea($value); ?></textarea>
        <?php
    }

    function custom_discount_code_field() {
        echo '<div class="custom-discount-field">
            <label for="custom_discount_code">Enter Discount Code:</label>
            <input type="text" name="custom_discount_code" id="custom_discount_code" value="">
        </div>';
    }
}