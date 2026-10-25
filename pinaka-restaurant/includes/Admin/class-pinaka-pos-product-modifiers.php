<?php

/**
 * Product_Modifiers Class
 *
 * Manages product modifiers (add-ons) within WooCommerce, including:
 * - Displaying modifiers on the single product page.
 * - Adding selected modifiers to cart item data.
 * - Merging cart items of the same product ID and combining their modifiers.
 * - Adjusting cart item prices based on selected modifiers.
 * - Displaying modifiers in cart and checkout.
 * - Saving modifier data to order item meta.
 * - Displaying modifiers on order details pages (admin and customer).
 * - Hiding raw modifier meta keys in the admin order details.
 */
class Product_Modifiers {

    private $plugin_name;
    private $version;

    /**
     * Constructor for the Product_Modifiers class.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of the plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        // Hook for displaying modifiers on the product page before the add-to-cart button.
        add_action('woocommerce_before_add_to_cart_button', [$this, 'display_modifiers']);

        // Hook for adding modifier data to the cart item.
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);

        // Hook for merging similar items in the cart (after session is loaded).
        // This runs before price calculations.
        add_action('woocommerce_cart_loaded_from_session', [$this, 'maybe_merge_same_items']);

        // Hook for adjusting the cart item price based on modifiers.
        // This runs after cart items are loaded and potentially merged.
        add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_price'], 10);

        // Hook for displaying modifiers in cart and checkout.
        add_filter('woocommerce_get_item_data', [$this, 'display_in_cart_checkout'], 10, 2);

        // Hook for saving modifiers to the order item meta when an order is created.
        add_action('woocommerce_add_order_item_meta', [$this, 'save_to_order_item'], 10, 3);

        // Hook for displaying modifiers on order details page (admin and customer).
        add_filter('woocommerce_order_item_name', [$this, 'display_on_order_details'], 10, 2);

        // Hook for hiding the raw modifier meta key in admin order details to keep it clean.
        add_filter('woocommerce_hidden_order_itemmeta', [$this, 'hide_order_itemmeta']);
    }

    /**
     * Display product modifiers checkboxes on the single product page.
     * Modifiers are fetched based on their association with the current product.
     */
    public function display_modifiers() {
        global $product;

        if (!is_a($product, 'WC_Product')) {
            return; // Ensure $product is valid
        }

        $product_id = $product->get_id();
        // Fetch modifiers where product ID is stored in meta key 'product_ids' as multiple entries (not serialized)
        $modifiers = get_posts([
            'post_type'      => 'modifier',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'product_ids',
                    'value'   => 'i:' . intval($product_id) . ';',
                    'compare' => 'REGEXP',
                ],
            ],
        ]);

        if (empty($modifiers)) {
            return;
        }

        echo '<div class="product-modifiers" style="margin-bottom:15px;padding:10px;border:1px dashed #aaa;border-radius:8px;">';
        echo '<h4 style="margin-top:0;margin-bottom:10px;">Select Modifiers</h4>';

        foreach ($modifiers as $modifier) {
            $modifier_id = $modifier->ID;
            $type        = get_post_meta($modifier_id, 'modifier_type', true);
            $label       = get_the_title($modifier_id);
            $price       = ($type === 'add-on') ? floatval(get_post_meta($modifier_id, 'modifier_price', true)) : 0;

            echo '<label style="display:flex;align-items:center;margin-bottom:8px;cursor:pointer;">';
            echo '<input type="checkbox" name="product_modifiers[]" value="' . esc_attr($modifier_id) . '" style="margin-right:8px;transform:scale(1.2);">';
            echo '<span style="font-size:16px;">' . esc_html($label) . '</span>';
            if ($price > 0 || $type === 'add-on') {
                echo ' <span style="font-weight:bold;color:#4CAF50;">(₹' . number_format($price, 2) . ')</span>';
            }
            echo '</label>';
        }

        echo '</div>';
    }
    /**
     * Adds selected modifiers to the cart item data.
     * This data will be used for price adjustments and display in cart/checkout.
     *
     * @param array $cart_item_data Existing extra cart item data.
     * @param int   $product_id     The ID of the product being added.
     * @param int   $variation_id   The ID of the product variation (0 if not a variation).
     * @return array Modified cart item data.
     */
    // public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
    //     if (!empty($_POST['product_modifiers']) && is_array($_POST['product_modifiers'])) {
    //         // Sanitize and convert modifier IDs to integers.
    //         $modifiers = array_map('intval', $_POST['product_modifiers']);
    //         // Sort modifiers for consistent comparison, which aids in merging.
    //         sort($modifiers);
    //         $cart_item_data['product_modifiers'] = $modifiers;
    //     } else {
    //         // Ensure 'product_modifiers' is an empty array if none are selected,
    //         // to prevent issues with merging or display logic expecting an array.
    //         $cart_item_data['product_modifiers'] = [];
    //     }
    //     return $cart_item_data;
    // }
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $extra_cost = 0;
        $modifiers  = [];

        if (!empty($_POST['product_modifiers']) && is_array($_POST['product_modifiers'])) {
            $modifiers = array_map('intval', $_POST['product_modifiers']);
            sort($modifiers);

            // Calculate the cost of modifiers for THIS specific item being added.
            foreach ($modifiers as $modifier_id) {
                if (get_post_meta($modifier_id, 'modifier_type', true) === 'add-on') {
                    $modifier_price = get_post_meta($modifier_id, 'modifier_price', true);
                    if (is_numeric($modifier_price)) {
                        $extra_cost += floatval($modifier_price);
                    }
                }
            }
        }
        
        $cart_item_data['product_modifiers'] = $modifiers;
        // NEW: Store the calculated extra cost. This will be summed up during merge.
        $cart_item_data['modifier_extra_cost'] = $extra_cost;

        return $cart_item_data;
    }
    /**
     * Merges cart items that correspond to the same product ID,
     * combining their quantities and unique modifiers.
     * This ensures that a single line item represents a product,
     * regardless of how many times it was added with different modifier selections.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */
    public function maybe_merge_same_items($cart) {
        // Prevent merging in the admin area unless it's an AJAX request (e.g., cart widget updates).
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $current_cart_contents = $cart->get_cart();
        $merged_cart_contents = []; // Stores the consolidated cart items, keyed by product_id

        foreach ($current_cart_contents as $cart_item_key => $item) {
            $product_id = $item['product_id'];
            // For this merging strategy, we only care about the base product ID.
            // If you needed to keep variations separate, you'd include variation_id in the merge key.
            $merge_key = $product_id;

            // Ensure 'product_modifiers' key exists and is an array for reliable merging.
            if (!isset($item['product_modifiers']) || !is_array($item['product_modifiers'])) {
                $item['product_modifiers'] = [];
            }
            $item['modifier_extra_cost'] = isset($item['modifier_extra_cost']) ? floatval($item['modifier_extra_cost']) : 0;
            // Check if this product_id has already been added to our merged cart.
            if (isset($merged_cart_contents[$merge_key])) {
                // If it exists, merge the current item's data into the existing merged item.
                $existing_item = &$merged_cart_contents[$merge_key];

                // 1. Merge Quantities: Add the current item's quantity to the existing one.
                $existing_item['quantity'] += $item['quantity'];

                // 2. Combine Modifiers: Merge and de-duplicate modifier IDs.
                $combined_modifiers = array_merge(
                    $existing_item['product_modifiers'],
                    $item['product_modifiers']
                );
                // Ensure modifier IDs are unique and sorted for consistency and clean display.
                $existing_item['product_modifiers'] = array_values(array_unique($combined_modifiers));
                sort($existing_item['product_modifiers']);
                $existing_item['modifier_extra_cost'] += $item['modifier_extra_cost'];


            } else {
                // If this is the first time encountering this product_id, add it to the merged cart.
                $merged_cart_contents[$merge_key] = $item;
                // Store the original cart_item_key of the first instance.
                // This is crucial for WooCommerce to correctly reference the item later.
                $merged_cart_contents[$merge_key]['original_cart_item_key'] = $cart_item_key;
            }
        }

        // Reconstruct the final cart array using the original cart_item_keys.
        $final_cart_contents = [];
        foreach ($merged_cart_contents as $merged_item) {
            $original_key = $merged_item['original_cart_item_key'];
            unset($merged_item['original_cart_item_key']); // Remove the temporary key
            $final_cart_contents[$original_key] = $merged_item;
        }

        // Update the WooCommerce cart with the new, merged contents.
        $cart->set_cart_contents($final_cart_contents);
    }

    /**
     * Adjusts the price of cart items based on selected modifiers.
     * This function will correctly sum prices for all combined modifiers for merged items.
     *
     * @param WC_Cart $cart The WooCommerce cart object.
     */

    // public function adjust_cart_item_price($cart) {
    //     // This hook doesn't need to run in the admin context unless it's an AJAX call (e.g., for recalculating totals).
    //     if (is_admin() && !defined('DOING_AJAX')) {
    //         return;
    //     }

    //     // Ensure we have a valid cart object to work with.
    //     if (!is_object($cart)) {
    //         return;
    //     }

    //     // Iterate through each item in the cart.
    //     foreach ($cart->get_cart() as $cart_item_key => &$cart_item) {
    //         // --- THE FIX: Get a fresh product object to ensure we start with the original price. ---
    //         // We use the product ID from the cart item to fetch an unmodified product instance.
    //         // This avoids using the price from `$cart_item['data']`, which this function may have already modified.
    //         $product = wc_get_product($cart_item['product_id']);

    //         // Skip to the next item if the product doesn't exist for some reason.
    //         if (!$product) {
    //             continue;
    //         }

    //         // Get the product's original base price (it will correctly account for sale prices).
    //         $base_price = floatval($product->get_price());
            
    //         $extra_price = 0; // Initialize the total for modifiers.

    //         // Check if our custom modifier data exists and is an array.
    //         if (!empty($cart_item['product_modifiers']) && is_array($cart_item['product_modifiers'])) {
                
    //             // Loop through each selected modifier ID.
    //             foreach ($cart_item['product_modifiers'] as $modifier_id) {
    //                 // Check if the modifier is an 'add-on' type.
    //                 $type = get_post_meta($modifier_id, 'modifier_type', true);
    //                 if ($type === 'add-on') {
    //                     // Get the modifier's price from post meta.
    //                     $modifier_price = get_post_meta($modifier_id, 'modifier_price', true);
                        
    //                     // Add its price to the total, ensuring it's a numeric value.
    //                     if (is_numeric($modifier_price)) {
    //                         $extra_price += floatval($modifier_price);
    //                     }
    //                 }
    //             }
    //         }
            
    //         // Calculate the final price.
    //         $new_price = $base_price + $extra_price;
            
    //         // Set the new price on the product object within the cart.
    //         // This is the object WooCommerce will use for the rest of its calculations.
    //         $cart_item['data']->set_price($new_price);
    //     }
    // }
    public function adjust_cart_item_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        if (!is_object($cart)) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => &$cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if (!$product) {
                continue;
            }

            $base_price = floatval($product->get_price());
            $quantity   = $cart_item['quantity'];

            // Get the TOTAL extra cost for the entire line item (summed during merge).
            $total_extra_cost = isset($cart_item['modifier_extra_cost']) ? floatval($cart_item['modifier_extra_cost']) : 0;

            // Calculate the final average price per unit.
            // Formula: ((Base Price * Quantity) + Total Add-on Cost) / Quantity
            if ($quantity > 0) {
                $new_price = (($base_price * $quantity) + $total_extra_cost) / $quantity;
            } else {
                $new_price = $base_price; // Fallback to prevent division by zero.
            }

            // Set the new calculated average price on the cart item.
            $cart_item['data']->set_price($new_price);
        }
    }
    /**
     * Displays modifier data in cart and checkout pages.
     * This will correctly display all combined modifiers for merged items.
     *
     * @param array $item_data Existing item data (e.g., product name, quantity).
     * @param array $cart_item The full cart item data array.
     * @return array Modified item data with modifier details.
     */
    public function display_in_cart_checkout($item_data, $cart_item) {
        $product = $cart_item['data']; // Get the WC_Product object for the current cart item.

        // Get the base price of the product (either regular or current price).
        $original_price = $product->get_price();

        // Add the original/base price to the item data for display.
        // $item_data[] = [
        //     'name'    => 'Base Price', // Label for the product's base price.
        //     'value'   => '₹' . number_format($original_price, 2),
        //     'display' => '', // Empty display means WooCommerce will format name/value.
        // ];

        // If modifiers are associated with the cart item.
        if (!empty($cart_item['product_modifiers']) && is_array($cart_item['product_modifiers'])) {
            $lines = []; // Array to store formatted modifier strings.
            foreach ($cart_item['product_modifiers'] as $modifier_id) {
                // Fetch modifier details (type, label, price).
                $type  = get_post_meta($modifier_id, 'modifier_type', true);
                $label = get_the_title($modifier_id);
                $price = ($type === 'add-on') ? floatval(get_post_meta($modifier_id, 'modifier_price', true)) : 0;
                // Format the modifier string.
                $lines[] = esc_html($label) . ' (₹' . number_format($price, 2) . ')';
            }
            // Add the combined modifiers as a single item data entry.
            $item_data[] = [
                'name'    => 'Modifiers',
                'value'   => implode(', ', $lines), // Join all modifier strings.
                'display' => '',
            ];
        }
        return $item_data;
    }

    /**
     * Saves modifier data to order item meta when an order is created.
     * This allows modifier information to persist with the order.
     *
     * @param int    $item_id     The ID of the new order item.
     * @param array  $values      The cart item data being saved.
     * @param string $cart_item_key The unique key for the cart item.
     */
    public function save_to_order_item($item_id, $values, $cart_item_key) {
        if (!empty($values['product_modifiers']) && is_array($values['product_modifiers'])) {
            // Store modifier IDs as a comma-separated string in order item meta.
            wc_add_order_item_meta($item_id, '_product_modifiers', implode(',', $values['product_modifiers']));
        }
        // NEW: Save the total modifier cost for this line item.
        if (isset($values['modifier_extra_cost']) && $values['modifier_extra_cost'] > 0) {
            wc_add_order_item_meta($item_id, '_modifier_extra_cost', $values['modifier_extra_cost']);
        }
    }

    /**
     * Displays modifiers on order details pages (both admin and customer views).
     * Appends modifier details to the product name.
     *
     * @param string      $name The original item name.
     * @param WC_Order_Item $item The order item object.
     * @return string Modified item name with modifier details.
     */
    public function display_on_order_details($name, $item) {
        // Retrieve the stored modifier IDs from order item meta.
        $modifiers_string = wc_get_order_item_meta($item->get_id(), '_product_modifiers', true);

        if ($modifiers_string) {
            // Convert the comma-separated string back to an array of IDs.
            $modifier_ids = explode(',', $modifiers_string);
            $lines = []; // Array to store formatted modifier strings.

            foreach ($modifier_ids as $id_str) {
                $id = intval(trim($id_str)); // Sanitize and convert to integer.

                // Validate that the ID corresponds to a 'modifier' post type.
                if (get_post_type($id) !== 'modifier') {
                    continue; // Skip if it's not a valid modifier.
                }

                // Fetch modifier details.
                $type  = get_post_meta($id, 'modifier_type', true);
                $label = get_the_title($id);
                $price = ($type === 'add-on') ? floatval(get_post_meta($id, 'modifier_price', true)) : 0;
                // Format the modifier string for display.
                $lines[] = esc_html($label) . ' (₹' . number_format($price, 2) . ')';
            }

            // If there are valid modifiers, append them to the item name.
            if (!empty($lines)) {
                $name .= '<br><small style="font-size:0.85em;color:#555;">Modifiers: ' . implode(', ', $lines) . '</small>';
            }
        }
        return $name;
    }

    /**
     * Hides the custom order item meta key from the WooCommerce admin interface.
     * This prevents the raw '_product_modifiers' key from being visible in the order meta box.
     *
     * @param array $hidden Array of meta keys that are currently hidden.
     * @return array Modified array with '_product_modifiers' added.
     */
    public function hide_order_itemmeta($hidden) {
        $hidden[] = '_product_modifiers';
        $hidden[] = '_modifier_extra_cost';
        return $hidden;
    }
}
