<?php
if (!defined('ABSPATH')) {
    exit;
}
if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_REST_Product_Variations_Controller' ) ) {
    return;
}
class Custom_Variation_Controller extends WC_REST_Product_Variations_Controller {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        // Hook into BOTH single + collection variations
        add_filter('woocommerce_rest_prepare_product_variation', [$this, 'add_custom_fields_to_variation'], 10, 3);
        add_filter('woocommerce_rest_prepare_product_variation_object', [$this, 'add_custom_fields_to_variation'], 10, 3);
        add_filter('woocommerce_rest_prepare_shop_order_object', [$this, 'filter_parent_order_response'], 20, 3);
    }

    public function add_custom_fields_to_variation( $response, $object, $request ) {

        $route = $request->get_route();
    
        // Only apply for product variations list endpoint
        if ( ! preg_match( '#^/wc/v3/products/\d+/variations$#', $route ) ) {
            return $response;
        }
    
        // Parent product name
        $parent_name = get_the_title( $object->get_parent_id() );
    
        // Get all variation attributes
        $attributes = [];
    
        foreach ( $object->get_attributes() as $attribute_name => $attribute_value ) {
    
            $taxonomy = str_replace( 'attribute_', '', $attribute_name );
    
            $term = get_term_by(
                'slug',
                $attribute_value,
                $taxonomy
            );
    
            $attributes[] = $term ? $term->name : $attribute_value;
        }
    
        // Final variation name
        $variation_name = $parent_name . ' ' . implode( ' ', $attributes );
    
        $in_stock = $object->is_in_stock();
    
        $data = [
            'product_id'   => $object->get_parent_id(),
            'variation_id' => $object->get_id(),
            'name'         => $variation_name,
            'quantity'     => $object->get_stock_quantity(),
            'image'        => wp_get_attachment_url( $object->get_image_id() ),
            'price'        => $object->get_price(),
            'in_stock'     => $in_stock ? 'Yes' : 'No',
        ];
    
        $response->set_data( $data );
    
        return $response;
    }
    public function filter_parent_order_response( $response, $object, $request ) 
    {
        if ( ! ($response instanceof WP_REST_Response) ) {
            return $response;
        }
        // Check if takeaway order
        $is_takeaway_order = $object->get_meta('_takeaway_order');
        if ( $is_takeaway_order === 'yes' ) 
        {
            $data = [
                'restaurant_id' => intval($object->get_meta('_restaurant_id')),
                // 'zone_id'    => intval($object->get_meta('_zone_id')),
                'order_id' => $object->get_id(),
                'coupon_amount' => (float) $object->get_discount_total(),
                'order_datetime' => $object->get_meta('_order_datetime'),
                'status'     => $object->get_status(),
                'order_type' => 'Takeaways'
            ];
            $response->set_data( $data );
            return $response;
        }
        $is_kot_order = $object->get_meta('_kot_order');
        if ( $is_kot_order === 'yes' ) {
            $response_items = [];
            $kot_total = 0;

            foreach ( $object->get_items() as $item_id => $item ) {
                $product       = $item->get_product();
                $product_name  = $item->get_name();
                $qty           = $item->get_quantity();
                $total         = $item->get_total();

                if ( $product ) {
                    // WooCommerce automatically uses sale price if active, otherwise regular
                    $price = wc_get_price_to_display( $product );
                } else {
                    $price = 0;
                }
                // Variation attributes
                $attributes = [];
                if ($product && $product->is_type('variation')) {
                    $variation_data = wc_get_product_variation_attributes($product->get_id());
                    foreach ($variation_data as $attr_value) {
                        $attributes[] = $attr_value;
                    }
                }

                // Modifiers
                $all_modifiers    = [];
                $modifier_amount  = 0;
                $gst_amount       = 0;
                $meta_data = $item->get_meta_data();

                foreach ($meta_data as $meta) 
                {
                    $meta_key   = $meta->key;
                    $meta_value = $meta->value;

                    if (in_array($meta_key, ['_addons', '_modifiers']) && is_array($meta_value)) {
                        foreach ($meta_value as $mod) {
                            if (!empty($mod['id'])) {
                                $mod_post = get_post($mod['id']);
                                if ($mod_post && $mod_post->post_type === 'modifier') {
                                    $all_modifiers[] = $mod_post->post_title;
                                }
                            }
                            else
                            {
                                $all_modifiers[] = $mod['name'];
                            }
                        }
                    }

                    if ($meta_key === '_extra_modifier_amount') {
                        $modifier_amount += floatval($meta_value);
                    }
                }

                // Taxes
                $tax_data = $item->get_taxes();
                if (isset($tax_data['total'])) {
                    foreach ($tax_data['total'] as $tax_amount) {
                        $gst_amount += floatval($tax_amount);
                    }
                    $total += $gst_amount;
                }

                // Add modifiers
                $total += $modifier_amount;

                $response_items[] = [
                    'id'           => $item_id,
                    'product_id'   => $product ? $product->get_id() : 0,
                    'product_name' => $product_name,
                    'attributes'   => $attributes,
                    'quantity'     => $qty,
                    'price'        => floatval($price),
                    'amount'       => floatval($total),
                    'modifiers'    => $all_modifiers,
                ];

                $kot_total += floatval($total);
            }
            $parent_order = wc_get_order( $object->get_parent_id() );

            $table_id = null;
            if ( $parent_order && $parent_order->get_created_via() === 'rest-api' ) {
                $table_id = intval( $parent_order->get_meta('_table_id') );
            }

            $data = [
                'restaurant_id' => intval( $object->get_meta('_restaurant_id') ),
                'zone_id'       => intval( $object->get_meta('_zone_id') ),
                'order_datetime' => $object->get_meta('_order_datetime'),
                'order_id'      => $object->get_parent_id(),
                'kot_id'        => $object->get_id(),
                'kot_total'     => floatval( $kot_total ),
                'kot_number'    => $object->get_meta('_kot_number', true ),
                'items'         => $response_items,
            ];

            // Add table_id only if condition passes
            if ( $table_id !== null ) {
                $data['table_id'] = $table_id;
            }

            $response->set_data($data);
            return $response;
        }
        // Proceed for parent orders check
        $is_parent_order = $object->get_meta('_parent_order');
        if ( $is_parent_order === 'yes' ) 
        {
            $table_id = intval( $object->get_meta('_table_id') );
            $zone_id  = intval( $object->get_meta('_zone_id') );

            $table_post = get_post( $table_id );
            $zone_post  = get_post( $zone_id );

            $table_name = $table_post ? $table_post->post_title : '';
            $zone_name  = $zone_post ? $zone_post->post_title : '';
            $created_via = $object->get_created_via();
            if($created_via == "rest-api")
            {
                $created_via = "Dine In";
            }
            else if($created_via == "takeaway")
            {
                $created_via = "Takeaways";
            }
            else if($created_via == "online")
            {
                $created_via = "Online Orders";
            }
            $reservation_id = $object->get_meta('_reservation_id');
            $data = [
                'restaurant_id' => intval($object->get_meta('_restaurant_id')),
                'zone_id'    => intval($object->get_meta('_zone_id')),
                'order_id'   => $object->get_id(),
                'coupon_amount' => (float) $object->get_discount_total(),
                'status'     => $object->get_status(),
                'table_name' => $table_name,
                'zone_name'  => $zone_name,
                'table_status' => $object->get_meta('_table_status'),
                'order_type'   => $created_via,  
            ];
            if($reservation_id!=='')
            {
                $data['reservation_id'] = $reservation_id;
                update_post_meta($reservation_id, '_table_status', 'Dine in');
				update_post_meta($reservation_id, '_reservation_status', 'Seated');
            }
            $response->set_data( $data );
            return $response;
        }
        $order_id = $object->get_id();

        $merchant_discount = (float) $object->get_meta( '_order_discount' );
        
        if ( $merchant_discount > 0 ) {
        
            $coupon_amount = (float) $object->get_discount_total();
        
            // Previous order amount before merchant discount
            $product_total = (float) get_post_meta($order_id, '_order_pre_net_total',true );
        
            // Recalculate merchant discount
            $new_discount = max( 0, $merchant_discount - $coupon_amount );
        
            // Update order meta
            $object->update_meta_data( '_order_discount', $new_discount );
        
            $discount_product_id = absint( get_option( 'pinaka_discount_product_id', 0 ) );
        
            foreach ( $object->get_items() as $item ) {
        
                if ( $item->get_product_id() == $discount_product_id ) {
        
                    $item->set_subtotal( -$new_discount );
                    $item->set_total( -$new_discount );
        
                    $item->set_subtotal_tax( 0 );
                    $item->set_total_tax( 0 );
                    $item->set_taxes(
                        [
                            'subtotal' => [],
                            'total'    => [],
                        ]
                    );
        
                    $item->save();
                    break;
                }
            }
        
            $object->calculate_totals( false );
            $object->save();
        }
        return $response;
    }
}
