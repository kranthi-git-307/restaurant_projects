<?php

/**
 * REST API Categories controller
 *
 * Handles requests to the / endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   1.0.0
 */

defined('ABSPATH') || exit;
/**
 * REST API Categories controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Variations_V2_Controller
 */
class Pinaka_Get_Categories_Controller 
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
    protected $rest_base = 'categories';

    /**
	 * Register the routes for sales reports.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_product_categories'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base."/get-main-courses",
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_product_main_categories'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

        register_rest_route(
			$this->namespace,
			'/' . $this->rest_base."/get-categories-products",
			array(
				array(
					'methods'             => 'GET',
                    'callback' => [$this, 'get_product_categories_with_products'],
					'permission_callback' => array( $this, 'check_user_role_permission' )
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/mini-subcategories',
            array(
                array(
                    'methods'             => 'GET', // GET
                    'callback'            => [$this, 'get_mini_subcategories_with_products'],
                    'permission_callback' => [$this, 'check_user_role_permission']
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
    
    public function get_product_categories($request) {
        $search = $request->get_param('search') ?? ''; // Get search parameter
        $parent = $request->get_param('parent') ?? ''; // Get parent category ID

        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false, // Show empty categories
            'parent'     => $parent,
        ];

        // If search query is present, filter by name
        if (!empty($search)) {
            $args['name__like'] = $search;
        }

        $categories = get_terms($args);
        $data = [];

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                if ($category->slug === 'uncategorized') {
            continue;
            }
            $data[] = $this->prepare_category_for_response($category);
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare a single category for API response
     */
    private function prepare_category_for_response($category) {
        // Check if this category has any children
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $category->term_id,
            'number'     => 1, // We only need to check if at least one exists
        ]);

        return [
            'id'          => $category->term_id,
            'name'        => $category->name,
            // 'slug'        => $category->slug,
            // 'parent'      => $category->parent,
            // 'description' => $category->description,
            "sl_no"       => get_term_meta( $category->term_id, 'product_cat_sequence', true ),
            'count'       => $category->count,
            'imagepath'       => $this->get_category_image($category->term_id),
            'isFolder' => (!empty($children) && !is_wp_error($children))
        ];
    }

    /**
     * Get category image (if available)
     */
    private function get_category_image($category_id) {
        $thumbnail_id = get_term_meta($category_id, 'thumbnail_id', true);
        return $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;
    }
    // private function get_category_image($category_id) {
    //     $thumbnail_id = get_term_meta($category_id, 'thumbnail_id', true);
    //     if (empty($thumbnail_id)) {
    //         return null;
    //     }
    //     $image_url = wp_get_attachment_url($thumbnail_id);
    //     // If attachment doesn't exist or was deleted, return null
    //     if (!$image_url || !file_exists(get_attached_file($thumbnail_id))) {
    //         return null;
    //     }
    //     return $image_url;
    // }
    public function get_product_main_categories($request) 
       {
        $uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');

        $args = [
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0,
        ];

        if ($uncategorized && !is_wp_error($uncategorized)) {
            $args['exclude'] = [$uncategorized->term_id];
        }

        $categories = get_terms($args);
        $data = [];

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $prepared = $this->prepare_category_for_response($category);

                // 🔹 Level 1: Subcategories
                $child_args = [
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => false,
                    'parent'     => $category->term_id,
                ];

                $child_terms = get_terms($child_args);

                if (!empty($child_terms) && !is_wp_error($child_terms)) {
                    $children = [];
                    foreach ($child_terms as $child) {
                        $child_prepared = $this->prepare_category_for_response($child);

                        // 🔹 Level 2: Mini Subcategories
                        $mini_child_args = [
                            'taxonomy'   => 'product_cat',
                            'hide_empty' => false,
                            'parent'     => $child->term_id,
                        ];
                        $mini_child_terms = get_terms($mini_child_args);

                        if (!empty($mini_child_terms) && !is_wp_error($mini_child_terms)) {
                            $mini_children = [];
                            foreach ($mini_child_terms as $mini) {
                                $mini_children[] = $this->prepare_category_for_response($mini);
                                $mini_children['products'] = $this->get_products_by_category($mini->term_id);
                            }
                            $child_prepared['mini_subcategory'] = $mini_children;

                        } else {
                            $child_prepared['mini_subcategory'] = [];
                        }

                        $children[] = $child_prepared;
                    }
                    $prepared['subcategory'] = $children;
                } else {
                    $prepared['subcategory'] = [];
                }

                $data[] = $prepared;
            }
        }

        return rest_ensure_response(['status' => "success", 'category' => $data]);
    }

    public function get_product_categories_with_products($request) {
        $args = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
        ];

        $categories = get_terms($args);
        $data = [];

        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $data[] = $this->build_category_tree($category);
            }
        }

        return rest_ensure_response(['status' => 'success', 'category' => $data]);
    }


    private function build_category_tree($category) {
        $category_data = $this->prepare_category_for_response($category);

        // Get products for this category
        $category_data['products'] = $this->get_products_by_category($category->term_id);

        // Get child categories
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $category->term_id,
        ]);

        $category_data['children'] = [];

        if (!empty($children) && !is_wp_error($children)) {
            foreach ($children as $child) {
                $category_data['children'][] = $this->build_category_tree($child); // 🔁 recursive call
            }
        }

        return $category_data;
    }

    private function get_products_by_category($cat_id) {
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => [$cat_id],
                    'include_children' => false, // Only for this specific category
                ],
            ],
        ];

        $query = new WP_Query($args);
        $products = [];

        foreach ($query->posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) continue;
            $is_veg = true;
            $tags = wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']);
            $new_tag = array();
            if (!empty($tags)) 
            {
                foreach ($tags as $tag_name) 
                {
                    $tag_name_lower = strtolower($tag_name);
                    if (strpos($tag_name_lower, 'non-vegetarian') !== false) {
                        $new_tag[$product->get_id()] = $product->get_id();
                        $is_veg = false;
                        break;
                    }
                    if (strpos($tag_name_lower, 'vegetarian') !== false) {
                        $new_tag[$product->get_id()] = $product->get_id();
                        $is_veg = true;
                        break;
                    }
                }
            }
            $image_url = wp_get_attachment_url($product->get_image_id());
            $is_variation = $this->get_attributes($product);
            $is_combo = $product->is_type( 'combo_bundle' );
            $in_stock = $product->is_in_stock();
            $products[] = [
                'id'    => $product->get_id(),
                'is_variant'          => $is_variation ? 'Yes' : 'No',
                'is_combo'            => $is_combo ? 'Yes' : 'No',
                'in_stock'            => $in_stock ? 'Yes' : 'No',
                'name'  => $product->get_name(),
                'price' => $product->get_price(),
                // 'image' => wp_get_attachment_url($product->get_image_id()),
                'image'   => $image_url ? $image_url : null,
                'is_veg' => !empty($new_tag) ? $is_veg : null,
            ];
        }

        return $products;
    }


    public function get_mini_subcategories_with_products($request) {
        $subcategory_id = intval($request->get_param('subcategory_id'));

        if (!$subcategory_id) {
            return new WP_Error(
                'missing_param',
                __('subcategory_id is required', 'pinaka-pos'),
                ['status' => 400]
            );
        }

        // Get mini subcategories (children of the given subcategory)
        $mini_subcategories = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $subcategory_id,
        ]);

        $data = [];

        if (!empty($mini_subcategories) && !is_wp_error($mini_subcategories)) {
            foreach ($mini_subcategories as $mini) {
                $mini_data = $this->prepare_category_for_response($mini);

                // Attach products for this mini subcategory
                $mini_data['products'] = $this->get_products_by_category($mini->term_id);
                $mini_data['isFolder'] = true;
                $data[] = $mini_data;
            }
        }

        return rest_ensure_response([
            'status' => 'success',
            'subcategory_id' => $subcategory_id,
            'mini_subcategories' => $data
        ]);
    }
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
