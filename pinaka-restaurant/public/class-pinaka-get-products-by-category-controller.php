<?php

/**
 * REST API variations controller
 *
 * Handles requests to the /products/<product_id>/variations endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API variations controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Get_Products_By_Category_Controller 
{
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
    protected $rest_base = 'products-by-category';

    /**
     * Register the routes for sales reports.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<category_id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'get_products_by_category'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
            )
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/get-products',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => [$this, 'pinaka_search_products'],
                    'permission_callback' => [$this, 'check_user_role_permission'],
                ),
                'schema' => [$this, 'get_public_item_schema'],
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
    public function pinaka_search_products(WP_REST_Request $request) {

        $search = sanitize_text_field($request->get_param('search'));
        $ids    = $request->get_param('ids');
        $limit  = intval($request->get_param('limit')) ?: 20;
    
        $search_term_lower = strtolower($search);
    
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_sku',
                    'value'   => $search_term_lower,
                    'compare' => '=',
                ],
            ],
        ];
    
        $query = new WP_Query($args);
    
        if (empty($query->posts)) {
            $args = [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                's'              => $search_term_lower,
            ];
    
            $query = new WP_Query($args);
        }
    
        if (empty($query->posts)) {
            $args = [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                's'              => $search_term_lower,
            ];
    
            if (!empty($ids)) {
                $args['post__in'] = array_map('intval', explode(',', $ids));
            }
    
            $query = new WP_Query($args);
        }
    
        $results = [];
    
        foreach ($query->posts as $post) {
    
            $product = wc_get_product($post->ID);
    
            if (!$product) {
                continue;
            }
    
            /*
             * Get product category
             * For variable products, category is taken
             * from the parent product.
             */
            $categories = wp_get_post_terms(
                $product->get_id(),
                'product_cat'
            );
    
            $category_name = '';
    
            if (!empty($categories) && !is_wp_error($categories)) {
                $category_name = $categories[0]->name;
            }
    
            /*
             * SIMPLE PRODUCT
             */
            if ($product->is_type('simple')) {
    
                $in_stock = $product->is_in_stock();
    
                $results[] = [
                    'id'            => $product->get_id(),
                    'name'          => $product->get_name(),
                    'in_stock'      => $in_stock ? 'Yes' : 'No',
                    'sku'           => $product->get_sku(),
                    'type'          => 'simple',
                    'price'         => $product->get_price(),
                    'category_name' => $category_name,
                ];
            }
    
            /*
             * VARIABLE PRODUCT
             */
            if ($product->is_type('variable')) {
    
                $parent_name = $product->get_name();
                $variations  = $product->get_children();
    
                foreach ($variations as $variation_id) {
    
                    $variation = wc_get_product($variation_id);
    
                    if (!$variation) {
                        continue;
                    }
    
                    $attr_parts = [];
    
                    foreach ($variation->get_attributes() as $attr_key => $attr_value) {
    
                        if (!$attr_value) {
                            continue;
                        }
    
                        // Global attribute
                        if (taxonomy_exists($attr_key)) {
    
                            $term = get_term_by(
                                'slug',
                                $attr_value,
                                $attr_key
                            );
    
                            $value = $term
                                ? $term->name
                                : $attr_value;
    
                        } else {
    
                            // Custom attribute
                            $value = $attr_value;
                        }
    
                        $attr_parts[] = $value;
                    }
    
                    $attributes = implode(', ', $attr_parts);
    
                    $in_stock = $variation->is_in_stock();
    
                    $results[] = [
                        'id'            => $variation->get_id(),
                        'name'          => $parent_name . ' - ' . $attributes,
                        'in_stock'      => $in_stock ? 'Yes' : 'No',
                        'sku'           => $variation->get_sku(),
                        'type'          => 'simple',
                        'price'         => $variation->get_price(),
                        'parent_id'     => $product->get_id(),
                        'parent_name'   => $parent_name,
                        'category_name' => $category_name,
                    ];
                }
            }
        }
    
        return rest_ensure_response([
            'success' => true,
            'count'   => count($results),
            'data'    => $results,
        ]);
    }
    public function get_products_by_category(WP_REST_Request $request) {
        $category_id = $request->get_param('category_id');

        if (!$category_id || !is_numeric($category_id)) {
            return new WP_REST_Response(array('error' => 'Invalid category ID'), 400);
        }

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ),
            ),
        );

        $query = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());

                $products[] = $this->prepare_object_for_response($product);
            }
        }
        wp_reset_postdata();

        return new WP_REST_Response($products, 200);
    }
    
    
    /**
     * Prepare a single product output for response.
     */
    
    public function prepare_object_for_response($product) {
        $is_veg = true;
        $tags = wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']);
        $new_tag = array();
        if (!empty($tags)) {
            foreach ($tags as $tag_name) 
            {
                $tag_name_lower = strtolower($tag_name);
                if (strpos($tag_name_lower, 'non-vegetarian') !== false) {
                    $is_veg = false;
                    $new_tag[$product->get_id()] = $product->get_id();
                    break;
                }
                if (strpos($tag_name_lower, 'vegetarian') !== false) {
                    $new_tag[$product->get_id()] = $product->get_id();
                    $is_veg = true;
                    break;
                }
            }
        }
        $is_variation = $this->get_attributes($product);
        $is_combo = $product->is_type( 'combo_bundle' );
        $in_stock = $product->is_in_stock();
        $data = [
            'id'                    => $product->get_id(),
            'is_variant'          => $is_variation ? 'Yes' : 'No',
            'is_combo'            => $is_combo ? 'Yes' : 'No',
            'in_stock'            => $in_stock ? 'Yes' : 'No',
            'name'                  => $product->get_name(),
            'slug'                  => $product->get_slug(),
            'date_created'          => wc_rest_prepare_date_response($product->get_date_created(), false),
            'date_modified'         => wc_rest_prepare_date_response($product->get_date_modified(), false),
            'status'                => $product->get_status(),
            'description'           => wc_format_content($product->get_description()),
            'short_description'     => wc_format_content($product->get_short_description()),
            'sku'                   => $product->get_sku(),
            'price'                 => $product->get_price(),
            'regular_price'         => $product->get_regular_price(),
            'sale_price'            => $product->get_sale_price(),
            'date_on_sale_from'     => wc_rest_prepare_date_response($product->get_date_on_sale_from(), false),
            'date_on_sale_to'       => wc_rest_prepare_date_response($product->get_date_on_sale_to(), false),
            'on_sale'               => $product->is_on_sale(),
            'purchasable'           => $product->is_purchasable(),
            'stock_quantity'        => $product->get_stock_quantity(),
            'stock_status'          => $product->get_stock_status(),
            'backorders'            => $product->get_backorders(),
            'backorders_allowed'    => $product->backorders_allowed(),
            'low_stock_amount'      => $product->get_low_stock_amount(),
            'weight'                => $product->get_weight(),
            'dimensions'            => [
                'length' => $product->get_length(),
                'width'  => $product->get_width(),
                'height' => $product->get_height(),
            ],
            'categories'            => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']),
            'tags'                  => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']),
            'images'                => $this->get_images($product),
            'attributes'            => $this->get_attributes($product),
            'meta_data'             => $product->get_meta_data(),
            'permalink'             => $product->get_permalink(),
            'is_veg' => !empty($new_tag) ? $is_veg : null,
        ];

        return $data;
    }

    /**
     * Get product images
     */
    // private function get_images($product) {
    //     $images = [];
    //     $attachment_ids = $product->get_gallery_image_ids();

    //     // Add main product image
    //     if ($product->get_image_id()) {
    //         $images[] = wp_get_attachment_url($product->get_image_id());
    //     }

    //     // Add gallery images
    //     foreach ($attachment_ids as $attachment_id) {
    //         $images[] = wp_get_attachment_url($attachment_id);
    //     }

    //     return $images;
    // }
    private function get_images($product) {
        $images = [];

        // Add main product image (if exists)
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $main_image_url = wp_get_attachment_url($main_image_id);
            if ($main_image_url) {
                $images[] = $main_image_url;
            }
        }

        // Add gallery images (if any exist)
        $attachment_ids = $product->get_gallery_image_ids();
        if (!empty($attachment_ids)) {
            foreach ($attachment_ids as $attachment_id) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $images[] = $url;
                }
            }
        }

        // Return null if no images
        return !empty($images) ? $images : null;
    }

    /**
     * Get product attributes
     */
    private function get_attributes($product) {
        $attributes = [];

        foreach ($product->get_attributes() as $attribute) {
            $name  = wc_attribute_label($attribute->get_name());
            $values = $attribute->is_taxonomy()
                ? wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names'])
                : $attribute->get_options();

            $attributes[] = [
                'name'   => $name,
                'values' => $values,
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
            ];
        }

        return $attributes;
    }
}
