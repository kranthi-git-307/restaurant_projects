<?php
/**
 * MCP REST API Controller
 *
 * Handles MCP requests for Products, Categories and Coupons.
 *
 * @link       https://www.PinakaPOS.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MCP REST API Controller.
 */
class Pinaka_Mcp_Api_Controller {

	/**
	 * REST Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'pinaka-restaurant-pos/v1';

	/**
	 * REST Base.
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->rest_base = 'mcp';

	}

	/**
	 * Register MCP REST Routes.
	 *
	 * @return void
	 */
	public function register_routes() {

		/**
		 * Products
		 */
    		register_rest_route(
    			$this->namespace,
    			'/' . $this->rest_base . '/products',
    			array(
    				array(
    					'methods'             => WP_REST_Server::READABLE,
    					'callback'            => array( $this, 'get_products' ),
    					'permission_callback' => array( $this, 'permission_callback' ),
    				),
    				array(
    					'methods'             => WP_REST_Server::CREATABLE,
    					'callback'            => array( $this, 'create_product' ),
    					'permission_callback' => array( $this, 'permission_callback' ),
    				),
    			)
    		);
    
    		register_rest_route(
    	$this->namespace,
    	'/' . $this->rest_base . '/products/(?P<id>\d+)',
    	array(
    
    		array(
    			'methods'             => WP_REST_Server::EDITABLE,
    			'callback'            => array( $this, 'update_product' ),
    			'permission_callback' => array( $this, 'permission_callback' ),
    		),
    
    		array(
    			'methods'             => WP_REST_Server::DELETABLE,
    			'callback'            => array( $this, 'delete_product' ),
    			'permission_callback' => array( $this, 'permission_callback' ),
    		),
    	)
    );
        /**
         * Categories
         */
        register_rest_route(
        	$this->namespace,
        	'/' . $this->rest_base . '/categories',
        	array(
        
        		array(
        			'methods'             => WP_REST_Server::READABLE,
        			'callback'            => array( $this, 'get_categories' ),
        			'permission_callback' => array( $this, 'permission_callback' ),
        		),
        
        		array(
        			'methods'             => WP_REST_Server::CREATABLE,
        			'callback'            => array( $this, 'create_category' ),
        			'permission_callback' => array( $this, 'permission_callback' ),
        		),
        	)
        );
        
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/categories',
            array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_category' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
        array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_category' ),
            'permission_callback' => array( $this, 'permission_callback' ),
        ),
    )
);
        /**
         * Coupons
         */
        register_rest_route(
        	$this->namespace,
        	'/' . $this->rest_base . '/coupons',
        	array(
        
        		array(
        			'methods'             => WP_REST_Server::READABLE,
        			'callback'            => array( $this, 'get_coupons' ),
        			'permission_callback' => array( $this, 'permission_callback' ),
        		),
        
        		array(
        			'methods'             => WP_REST_Server::CREATABLE,
        			'callback'            => array( $this, 'create_coupon' ),
        			'permission_callback' => array( $this, 'permission_callback' ),
        		),
        	)
        );
        
        register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/coupons',
    array(
        array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => array( $this, 'update_coupon' ),
            'permission_callback' => array( $this, 'permission_callback' ),
        ),
        
        		array(
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => array( $this, 'delete_coupon' ),
            'permission_callback' => array( $this, 'permission_callback' ),
        ),
    )
);
        
/**
 * Top Sold Categories
 */
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/reports/top-sold-categories',
    array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_top_sold_categories' ),
            'permission_callback' => array( $this, 'permission_callback' ),
            'args'                => array(
                'period' => array(
                    'description' => 'today, this_week, last_week, this_month, custom',
                    'required'    => false,
                    'type'        => 'string',
                ),
                'start_date' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'end_date' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'limit' => array(
                    'required' => false,
                    'default'  => 10,
                    'type'     => 'integer',
                ),
                'page' => array(
                    'required' => false,
                    'default'  => 1,
                    'type'     => 'integer',
                ),
            ),
        ),
    )
);
/**
 * Top Sold Products
 */
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/reports/top-sold-products',
    array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_top_sold_products' ),
            'permission_callback' => array( $this, 'permission_callback' ),
            'args'                => array(
                'period' => array(
                    'description' => 'today, this_week, last_week, this_month, custom',
                    'required'    => false,
                    'type'        => 'string',
                ),
                'start_date' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'end_date' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'limit' => array(
                    'required' => false,
                    'default'  => 10,
                    'type'     => 'integer',
                ),
                'page' => array(
                    'required' => false,
                    'default'  => 1,
                    'type'     => 'integer',
                ),
            ),
        ),
    )
);
/**
 * Get Order Details by ID
 *
 * GET /wp-json/pinaka-pos/v1/mcp/orders/{id}
 */
register_rest_route(
    $this->namespace,
    '/' . $this->rest_base . '/orders/(?P<id>\d+)',
    array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_order_by_id' ),
            'permission_callback' => array( $this, 'permission_callback' ),
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                    'description'       => 'WooCommerce Order ID',
                ),
            ),
        ),
    )
);
}

	/**
	 * Permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function permission_callback() {

		if ( ! is_user_logged_in() ) {

			return new WP_Error(
				'unauthorized',
				'Unauthorized.',
				array(
					'status' => 401,
				)
			);

		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {

			return new WP_Error(
				'forbidden',
				'You do not have permission to access this endpoint.',
				array(
					'status' => 403,
				)
			);

		}

		return true;

	}

	/**
	 * Return success response.
	 *
	 * @param mixed  $data    Response data.
	 * @param string $message Response message.
	 * @param int    $status  HTTP status.
	 *
	 * @return WP_REST_Response
	 */
	private function success_response( $data = array(), $message = 'Success', $status = 200 ) {

		$response = rest_ensure_response(
			array(
				'success' => true,
				'message' => $message,
				'data'    => $data,
			)
		);

		$response->set_status( $status );

		return $response;

	}

	/**
	 * Return error response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status.
	 *
	 * @return WP_Error
	 */
	private function error_response( $code, $message, $status = 400 ) {

		return new WP_Error(
			$code,
			$message,
			array(
				'status' => $status,
			)
		);

	}

	/**
	 * Get product by ID.
	 */
	private function get_product( $product_id ) {}
	
   /**
 * Get products.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function get_products( WP_REST_Request $request ) {

	// Pagination.
	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

	$args = array(
		'status'   => 'publish',
		'paginate' => true,
		'page'     => $page,
		'limit'    => $per_page,
	);

	// Search by product name.
$search = sanitize_text_field( $request->get_param( 'search' ) );

if ( ! empty( $search ) ) {

    $product_ids = get_posts(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $search,
            'fields'         => 'ids',
            'posts_per_page' => -1,
        )
    );

    if ( empty( $product_ids ) ) {
        return $this->success_response(
            array(
                'products'     => array(),
                'page'         => $page,
                'per_page'     => $per_page,
                'total'        => 0,
                'total_pages'  => 0,
                'has_next'     => false,
                'has_previous' => false,
            ),
            'No products found.'
        );
    }

    $args['include'] = $product_ids;
}

	// Search by SKU.
	$sku = sanitize_text_field( $request->get_param( 'sku' ) );

	if ( ! empty( $sku ) ) {
		$args['sku'] = $sku;
	}

	// Filter by category name.
$category_name = sanitize_text_field( $request->get_param( 'category_name' ) );

if ( ! empty( $category_name ) ) {

	$term = get_term_by( 'name', $category_name, 'product_cat' );

	if ( $term && ! is_wp_error( $term ) ) {
		$args['category'] = array( $term->slug );
	}
}

	// Filter by stock status.
	$stock_status = sanitize_text_field( $request->get_param( 'stock_status' ) );

	if ( ! empty( $stock_status ) ) {
		$args['stock_status'] = $stock_status;
	}

	// Get products.
	$results = wc_get_products( $args );

	$products = array();

	foreach ( $results->products as $product ) {
		$products[] = $this->prepare_product_response( $product );
	}

	return $this->success_response(
		array(
			'products'    => $products,
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => (int) $results->total,
			'total_pages' => (int) $results->max_num_pages,
			'has_next'    => $page < $results->max_num_pages,
			'has_previous'=> $page > 1,
		),
		'Products fetched successfully.'
	);
}

	/**
	 * Get category by ID.
	 */
	private function get_category( $category_id ) {}

	/**
	 * Get coupon by ID.
	 */
	private function get_coupon( $coupon_id ) {}
	
	/**
 * Prepare product response.
 *
 * @param WC_Product $product Product object.
 *
 * @return array
 */
/**
 * Prepare product response.
 *
 * @param WC_Product $product Product object.
 *
 * @return array
 */
private function prepare_product_response( WC_Product $product ) {

	$image = '';

	if ( $product->get_image_id() ) {
		$image = wp_get_attachment_url( $product->get_image_id() );
	}

	// Get tax class.
	$tax_class = $product->get_tax_class();

	// Show 'standard' instead of empty string.
	if ( '' === $tax_class ) {
		$tax_class = 'standard';
	}

	// Get Categories.
	$categories = array();

	foreach ( $product->get_category_ids() as $category_id ) {

		$term = get_term( $category_id, 'product_cat' );

		if ( $term && ! is_wp_error( $term ) ) {

			$categories[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
	}

	// Get Tags.
	$tags = array();

	foreach ( $product->get_tag_ids() as $tag_id ) {

		$term = get_term( $tag_id, 'product_tag' );

		if ( $term && ! is_wp_error( $term ) ) {

			$tags[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}
	}

	return array(
		'id'             => $product->get_id(),
		'name'           => $product->get_name(),
		'slug'           => $product->get_slug(),
		'type'           => $product->get_type(),
		'sku'            => $product->get_sku(),
		'regular_price'  => $product->get_regular_price(),
		'sale_price'     => $product->get_sale_price(),
		'tax_status'     => $product->get_tax_status(),
		'tax_class'      => $tax_class,
		'stock_status'   => $product->get_stock_status(),
		'stock_quantity' => $product->get_stock_quantity(),
		'image'          => $image,
		'categories'     => $categories,
		'tags'           => $tags,
	);
}


/**
 * Create Product.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function create_product( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Validate required fields.
	if ( empty( $params['name'] ) ) {
		return $this->error_response( 'Product name is required.', 422 );
	}

	if ( ! isset( $params['regular_price'] ) || '' === $params['regular_price'] ) {
		return $this->error_response( 'Regular price is required.', 422 );
	}

	try {

		$product = new WC_Product_Simple();

		// Product Name.
		$product->set_name( sanitize_text_field( $params['name'] ) );

		// SKU.
		if ( ! empty( $params['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $params['sku'] ) );
		}

		// Prices.
		$product->set_regular_price(
			wc_format_decimal( $params['regular_price'] )
		);

		if ( ! empty( $params['sale_price'] ) ) {
			$product->set_sale_price(
				wc_format_decimal( $params['sale_price'] )
			);
		}

		// Description.
		if ( isset( $params['description'] ) ) {
			$product->set_description(
				wp_kses_post( $params['description'] )
			);
		}

		// Short Description.
		if ( isset( $params['short_description'] ) ) {
			$product->set_short_description(
				wp_kses_post( $params['short_description'] )
			);
		}

		// Stock Management.
		$product->set_manage_stock(
			isset( $params['manage_stock'] )
				? (bool) $params['manage_stock']
				: false
		);

		// Stock Quantity.
		if ( isset( $params['stock_quantity'] ) ) {
			$product->set_stock_quantity(
				absint( $params['stock_quantity'] )
			);
		}

		// Stock Status.
		if ( ! empty( $params['stock_status'] ) ) {
			$product->set_stock_status(
				sanitize_text_field( $params['stock_status'] )
			);
		}

		// Tax Status.
		if ( ! empty( $params['tax_status'] ) ) {
			$product->set_tax_status(
				sanitize_text_field( $params['tax_status'] )
			);
		}

		// Tax Class.
		if ( isset( $params['tax_class'] ) ) {

			$tax_class = sanitize_title( $params['tax_class'] );

			// WooCommerce uses empty string for Standard tax class.
			if ( 'standard' === $tax_class ) {
				$tax_class = '';
			}

			$product->set_tax_class( $tax_class );
		}

		// Product Status.
		$product->set_status(
			! empty( $params['status'] )
				? sanitize_text_field( $params['status'] )
				: 'publish'
		);

		/**
		 * Categories
		 * Supports:
		 * 1. category_name => "Vegetables"
		 * 2. categories => [ { id:1 } ]
		 * 3. categories => [ { name:"Vegetables" } ]
		 */
		$category_ids = array();

		// category_name.
		if ( ! empty( $params['category_name'] ) ) {

			$term = get_term_by(
				'name',
				sanitize_text_field( $params['category_name'] ),
				'product_cat'
			);

			if ( $term && ! is_wp_error( $term ) ) {
				$category_ids[] = $term->term_id;
			}
		}

		// categories array.
		elseif ( ! empty( $params['categories'] ) && is_array( $params['categories'] ) ) {

			foreach ( $params['categories'] as $category ) {

				// Category ID.
				if ( ! empty( $category['id'] ) ) {

					$category_ids[] = absint( $category['id'] );

				}
				// Category Name.
				elseif ( ! empty( $category['name'] ) ) {

					$term = get_term_by(
						'name',
						sanitize_text_field( $category['name'] ),
						'product_cat'
					);

					if ( $term && ! is_wp_error( $term ) ) {
						$category_ids[] = $term->term_id;
					}
				}
			}
		}

		if ( ! empty( $category_ids ) ) {
			$product->set_category_ids(
				array_unique( $category_ids )
			);
		}

		/**
		 * Tags
		 * Supports:
		 * 1. tag_name => "Organic"
		 * 2. tags => [ { id:1 } ]
		 * 3. tags => [ { name:"Organic" } ]
		 */
		$tag_ids = array();

		// tag_name.
		if ( ! empty( $params['tag_name'] ) ) {

			$term = get_term_by(
				'name',
				sanitize_text_field( $params['tag_name'] ),
				'product_tag'
			);

			if ( $term && ! is_wp_error( $term ) ) {
				$tag_ids[] = $term->term_id;
			}
		}

		// tags array.
		elseif ( ! empty( $params['tags'] ) && is_array( $params['tags'] ) ) {

			foreach ( $params['tags'] as $tag ) {

				// Tag ID.
				if ( ! empty( $tag['id'] ) ) {

					$tag_ids[] = absint( $tag['id'] );

				}
				// Tag Name.
				elseif ( ! empty( $tag['name'] ) ) {

					$term = get_term_by(
						'name',
						sanitize_text_field( $tag['name'] ),
						'product_tag'
					);

					if ( $term && ! is_wp_error( $term ) ) {
						$tag_ids[] = $term->term_id;
					}
				}
			}
		}

		if ( ! empty( $tag_ids ) ) {
			$product->set_tag_ids(
				array_unique( $tag_ids )
			);
		}

		// Save Product.
		$product_id = $product->save();

		// Reload product.
		$product = wc_get_product( $product_id );

		return $this->success_response(
			$this->prepare_product_response( $product ),
			'Product created successfully.'
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			$e->getMessage(),
			500
		);
	}
}

public function update_product( WP_REST_Request $request ) {

	$product_id = absint( $request->get_param( 'id' ) );

	if ( empty( $product_id ) ) {
		return $this->error_response(
			'Product ID is required.',
			422
		);
	}

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return $this->error_response(
			'Product not found.',
			404
		);
	}

	$params = $request->get_json_params();

	try {

		// Product Name.
		if ( isset( $params['name'] ) ) {
			$product->set_name(
				sanitize_text_field( $params['name'] )
			);
		}

		// SKU.
		if ( isset( $params['sku'] ) ) {
			$product->set_sku(
				sanitize_text_field( $params['sku'] )
			);
		}

		// Regular Price.
		if ( isset( $params['regular_price'] ) ) {
			$product->set_regular_price(
				wc_format_decimal( $params['regular_price'] )
			);
		}

		// Sale Price.
		if ( isset( $params['sale_price'] ) ) {
			$product->set_sale_price(
				wc_format_decimal( $params['sale_price'] )
			);
		}

		// Description.
		if ( isset( $params['description'] ) ) {
			$product->set_description(
				wp_kses_post( $params['description'] )
			);
		}

		// Short Description.
		if ( isset( $params['short_description'] ) ) {
			$product->set_short_description(
				wp_kses_post( $params['short_description'] )
			);
		}

		// Manage Stock.
		if ( isset( $params['manage_stock'] ) ) {
			$product->set_manage_stock(
				(bool) $params['manage_stock']
			);
		}

		// Stock Quantity.
		if ( isset( $params['stock_quantity'] ) ) {
			$product->set_stock_quantity(
				absint( $params['stock_quantity'] )
			);
		}

		// Stock Status.
		if ( isset( $params['stock_status'] ) ) {
			$product->set_stock_status(
				sanitize_text_field( $params['stock_status'] )
			);
		}

		// Tax Status.
		if ( isset( $params['tax_status'] ) ) {
			$product->set_tax_status(
				sanitize_text_field( $params['tax_status'] )
			);
		}

		// Tax Class.
		if ( isset( $params['tax_class'] ) ) {

			$tax_class = sanitize_title(
				$params['tax_class']
			);

			if ( 'standard' === $tax_class ) {
				$tax_class = '';
			}

			$product->set_tax_class( $tax_class );
		}

		// Status.
		if ( isset( $params['status'] ) ) {
			$product->set_status(
				sanitize_text_field( $params['status'] )
			);
		}

		/**
		 * Categories
		 */
		$category_ids = array();

		// category_name.
		if ( ! empty( $params['category_name'] ) ) {

			$category = sanitize_text_field(
				$params['category_name']
			);

			// Search by slug.
			$term = get_term_by(
				'slug',
				sanitize_title( $category ),
				'product_cat'
			);

			// Fallback to name.
			if ( ! $term ) {
				$term = get_term_by(
					'name',
					$category,
					'product_cat'
				);
			}

			if ( $term && ! is_wp_error( $term ) ) {
				$category_ids[] = $term->term_id;
			}

		} elseif ( ! empty( $params['categories'] ) && is_array( $params['categories'] ) ) {

			foreach ( $params['categories'] as $category ) {

				if ( ! empty( $category['id'] ) ) {

					$category_ids[] = absint(
						$category['id']
					);

				} elseif ( ! empty( $category['name'] ) ) {

					$category_name = sanitize_text_field(
						$category['name']
					);

					$term = get_term_by(
						'slug',
						sanitize_title( $category_name ),
						'product_cat'
					);

					if ( ! $term ) {
						$term = get_term_by(
							'name',
							$category_name,
							'product_cat'
						);
					}

					if ( $term && ! is_wp_error( $term ) ) {
						$category_ids[] = $term->term_id;
					}
				}
			}
		}

		if ( ! empty( $category_ids ) ) {
			$product->set_category_ids(
				array_unique( $category_ids )
			);
		}
				/**
		 * Tags
		 */
		$tag_ids = array();

		// Support tag_name.
		if ( ! empty( $params['tag_name'] ) ) {

			$tag_name = sanitize_text_field(
				$params['tag_name']
			);

			// Search by slug first.
			$term = get_term_by(
				'slug',
				sanitize_title( $tag_name ),
				'product_tag'
			);

			// Fallback to name.
			if ( ! $term ) {
				$term = get_term_by(
					'name',
					$tag_name,
					'product_tag'
				);
			}

			// Create tag only if it doesn't exist.
			if ( ! $term ) {

				$result = wp_insert_term(
					$tag_name,
					'product_tag'
				);

				if ( ! is_wp_error( $result ) ) {
					$tag_ids[] = (int) $result['term_id'];
				}

			} else {

				$tag_ids[] = (int) $term->term_id;

			}

		}
		// Support tags array.
		elseif ( ! empty( $params['tags'] ) && is_array( $params['tags'] ) ) {

			foreach ( $params['tags'] as $tag ) {

				// Tag ID.
				if ( ! empty( $tag['id'] ) ) {

					$tag_ids[] = absint(
						$tag['id']
					);

				}
				// Tag Name.
				elseif ( ! empty( $tag['name'] ) ) {

					$tag_name = sanitize_text_field(
						$tag['name']
					);

					// Search by slug.
					$term = get_term_by(
						'slug',
						sanitize_title( $tag_name ),
						'product_tag'
					);

					// Fallback to name.
					if ( ! $term ) {
						$term = get_term_by(
							'name',
							$tag_name,
							'product_tag'
						);
					}

					// Create tag if missing.
					if ( ! $term ) {

						$result = wp_insert_term(
							$tag_name,
							'product_tag'
						);

						if ( ! is_wp_error( $result ) ) {
							$tag_ids[] = (int) $result['term_id'];
						}

					} else {

						$tag_ids[] = (int) $term->term_id;

					}
				}
			}
		}

		if ( ! empty( $tag_ids ) ) {
			$product->set_tag_ids(
				array_unique( $tag_ids )
			);
		}

		// Save Product.
		$product->save();

		// Reload updated product.
		$product = wc_get_product(
			$product->get_id()
		);

		return $this->success_response(
			$this->prepare_product_response( $product ),
			'Product updated successfully.'
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			$e->getMessage(),
			500
		);
	}
}

/**
 * Delete product.
 */
public function delete_product( WP_REST_Request $request ) {

    $product_id = absint( $request->get_param( 'id' ) );

    if ( empty( $product_id ) ) {
        return $this->error_response(
            'product_id_missing',
            'Product ID is required.',
            422
        );
    }

    $product = wc_get_product( $product_id );

    if ( ! $product ) {
        return $this->error_response(
            'product_not_found',
            'Product not found.',
            404
        );
    }

    try {

        // Move product to Trash.
        $product->delete( false );

        return $this->success_response(
            array(
                'id'     => $product_id,
                'status' => 'trash',
            ),
            'Product moved to trash successfully.'
        );

    } catch ( Exception $e ) {

        return $this->error_response(
            'delete_product_error',
            $e->getMessage(),
            500
        );
    }
}

/**
 * Get Categories.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function get_categories( WP_REST_Request $request ) {

	// Pagination.
	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

	$args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => $per_page,
		'offset'     => ( $page - 1 ) * $per_page,
	);

	// Search by category name.
	$search = sanitize_text_field( $request->get_param( 'search' ) );

	if ( ! empty( $search ) ) {
		$args['search'] = $search;
	}

	// Filter by parent category.
	if ( null !== $request->get_param( 'parent' ) ) {
		$args['parent'] = absint( $request->get_param( 'parent' ) );
	}

	$categories = get_terms( $args );

	if ( is_wp_error( $categories ) ) {
		return $this->error_response(
			'get_categories_error',
			$categories->get_error_message(),
			500
		);
	}

	$response = array();

	foreach ( $categories as $category ) {
		$response[] = $this->prepare_category_response( $category );
	}

	// Get total count.
	$total = wp_count_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);

	return $this->success_response(
		array(
			'categories'   => $response,
			'page'         => $page,
			'per_page'     => $per_page,
			'total'        => (int) $total,
			'total_pages'  => (int) ceil( $total / $per_page ),
			'has_next'     => $page < ceil( $total / $per_page ),
			'has_previous' => $page > 1,
		),
		'Categories fetched successfully.'
	);
}

/**
 * Create Category.
 */
/**
 * Create Category.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
/**
 * Create category.
 */
public function create_category( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Validate Category Name.
	if ( empty( $params['name'] ) ) {

		return $this->error_response(
			'category_name_required',
			'Category name is required.',
			422
		);
	}

	// Resolve Parent Category.
	$parent = 0;

	// If Parent ID is provided.
	if ( ! empty( $params['parent'] ) ) {

		$parent = absint( $params['parent'] );

	// Otherwise find parent by category name.
	} elseif ( ! empty( $params['parent_category_name'] ) ) {

		$parent_term = get_term_by(
			'name',
			sanitize_text_field( $params['parent_category_name'] ),
			'product_cat'
		);

		if ( $parent_term && ! is_wp_error( $parent_term ) ) {
			$parent = (int) $parent_term->term_id;
		}
	}

	$args = array(
		'description' => ! empty( $params['description'] )
			? sanitize_textarea_field( $params['description'] )
			: '',

		'slug' => ! empty( $params['slug'] )
			? sanitize_title( $params['slug'] )
			: '',

		'parent' => $parent,
	);

	try {

		$category = wp_insert_term(
			sanitize_text_field( $params['name'] ),
			'product_cat',
			$args
		);

		if ( is_wp_error( $category ) ) {

			return $this->error_response(
				'create_category_error',
				$category->get_error_message(),
				400
			);
		}

		$term = get_term(
			$category['term_id'],
			'product_cat'
		);

		return $this->success_response(
			$this->prepare_category_response( $term ),
			'Category created successfully.',
			201
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			'create_category_error',
			$e->getMessage(),
			500
		);
	}
}

/**
 * Update Category.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
/**
 * Update category.
 */
public function update_category( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Category name is required.
	if ( empty( $params['category_name'] ) ) {

		return $this->error_response(
			'category_name_required',
			'Category name is required.',
			422
		);
	}

	// Find category by name.
	$term = get_term_by(
		'name',
		sanitize_text_field( $params['category_name'] ),
		'product_cat'
	);

	if ( ! $term || is_wp_error( $term ) ) {

		return $this->error_response(
			'category_not_found',
			'Category not found.',
			404
		);
	}

	$category_id = $term->term_id;

	$args = array();

	// Update category name.
	if ( ! empty( $params['new_name'] ) ) {
		$args['name'] = sanitize_text_field( $params['new_name'] );
	}

	// Update description.
	if ( isset( $params['description'] ) ) {
		$args['description'] = sanitize_textarea_field( $params['description'] );
	}

	// Update slug.
	if ( ! empty( $params['slug'] ) ) {
		$args['slug'] = sanitize_title( $params['slug'] );
	}

	// Update parent category.
	if ( ! empty( $params['parent_category_name'] ) ) {

		$parent_term = get_term_by(
			'name',
			sanitize_text_field( $params['parent_category_name'] ),
			'product_cat'
		);

		if ( $parent_term && ! is_wp_error( $parent_term ) ) {

			if ( (int) $parent_term->term_id === (int) $category_id ) {

				return $this->error_response(
					'invalid_parent',
					'A category cannot be its own parent.',
					400
				);
			}

			$args['parent'] = (int) $parent_term->term_id;
		}
	}

	try {

		$result = wp_update_term(
			$category_id,
			'product_cat',
			$args
		);

		if ( is_wp_error( $result ) ) {

			return $this->error_response(
				'update_category_error',
				$result->get_error_message(),
				400
			);
		}

		$updated_term = get_term(
			$category_id,
			'product_cat'
		);

		return $this->success_response(
			$this->prepare_category_response( $updated_term ),
			'Category updated successfully.'
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			'update_category_error',
			$e->getMessage(),
			500
		);
	}
}

/**
 * Delete Category.
 */
/**
 * Delete Category.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
/**
 * Delete category.
 */
public function delete_category( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Category name is required.
	if ( empty( $params['category_name'] ) ) {

		return $this->error_response(
			'category_name_required',
			'Category name is required.',
			422
		);
	}

	// Find category by name.
	$term = get_term_by(
		'name',
		sanitize_text_field( $params['category_name'] ),
		'product_cat'
	);

	if ( ! $term || is_wp_error( $term ) ) {

		return $this->error_response(
			'category_not_found',
			'Category not found.',
			404
		);
	}

	$category_id = (int) $term->term_id;

	// Prevent deleting the default category.
	$default_category = (int) get_option( 'default_product_cat', 0 );

	if ( $category_id === $default_category ) {

		return $this->error_response(
			'default_category',
			'The default product category cannot be deleted.',
			400
		);
	}

	// Reassign products (optional).
	$reassign = ! empty( $params['reassign'] )
		? absint( $params['reassign'] )
		: 0;

	$result = wp_delete_term(
		$category_id,
		'product_cat',
		array(
			'default' => $reassign,
		)
	);

	if ( is_wp_error( $result ) ) {

		return $this->error_response(
			'delete_category_error',
			$result->get_error_message(),
			400
		);
	}

	if ( ! $result ) {

		return $this->error_response(
			'delete_category_error',
			'Unable to delete category.',
			400
		);
	}

	return $this->success_response(
		array(
			'id'   => $category_id,
			'name' => $term->name,
		),
		'Category deleted successfully.'
	);
}

/**
 * Prepare Category Response.
 *
 * @param WP_Term $category Category object.
 *
 * @return array
 */
private function prepare_category_response( WP_Term $category ) {

	$parent_name = '';

	// Get Parent Category Name.
	if ( ! empty( $category->parent ) ) {

		$parent = get_term( $category->parent, 'product_cat' );

		if ( $parent && ! is_wp_error( $parent ) ) {
			$parent_name = $parent->name;
		}
	}

	return array(
		'id'          => (int) $category->term_id,
		'name'        => $category->name,
		'slug'        => $category->slug,
		'description' => $category->description,
		'parent'      => (int) $category->parent,
		'parent_name' => $parent_name,
		'count'       => (int) $category->count,
	);
}
/**
 * Get Coupons.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function get_coupons( WP_REST_Request $request ) {

	$page     = absint( $request->get_param( 'page' ) ) ?: 1;
	$per_page = absint( $request->get_param( 'per_page' ) ) ?: 20;

	$search = sanitize_text_field( $request->get_param( 'search' ) );

	$args = array(
		'post_type'      => 'shop_coupon',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'orderby'        => 'ID',
		'order'          => 'DESC',
	);

	// Search coupon code.
	if ( ! empty( $search ) ) {
		$args['s'] = $search;
	}

	$query = new WP_Query( $args );

	$coupons = array();

	if ( $query->have_posts() ) {

		foreach ( $query->posts as $post ) {

			$coupon = new WC_Coupon( $post->ID );

			$coupons[] = $this->prepare_coupon_response( $coupon );
		}
	}

	return $this->success_response(
		array(
			'coupons'      => $coupons,
			'page'         => $page,
			'per_page'     => $per_page,
			'total'        => (int) $query->found_posts,
			'total_pages'  => (int) $query->max_num_pages,
			'has_next'     => $page < $query->max_num_pages,
			'has_previous' => $page > 1,
		),
		'Coupons fetched successfully.'
	);
}


/**
 * Create Coupon.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function create_coupon( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Required fields.
	if ( empty( $params['code'] ) ) {
		return $this->error_response(
			'coupon_code_required',
			'Coupon code is required.',
			422
		);
	}

	if ( empty( $params['discount_type'] ) ) {
		return $this->error_response(
			'discount_type_required',
			'Discount type is required.',
			422
		);
	}

	if ( ! isset( $params['amount'] ) || $params['amount'] === '' ) {
		return $this->error_response(
			'amount_required',
			'Coupon amount is required.',
			422
		);
	}

	// Validate discount type.
	$allowed_types = array(
		'percent',
		'fixed_cart',
		'fixed_product',
	);

	if ( ! in_array( $params['discount_type'], $allowed_types, true ) ) {
		return $this->error_response(
			'invalid_discount_type',
			'Invalid discount type.',
			422
		);
	}

	// Duplicate coupon.
	$existing_coupon = new WC_Coupon( sanitize_text_field( $params['code'] ) );

	if ( $existing_coupon->get_id() ) {
		return $this->error_response(
			'coupon_exists',
			'Coupon code already exists.',
			409
		);
	}

	try {

		$coupon = new WC_Coupon();

		$coupon->set_code( sanitize_text_field( $params['code'] ) );
		$coupon->set_discount_type( sanitize_text_field( $params['discount_type'] ) );
		$coupon->set_amount( wc_format_decimal( $params['amount'] ) );

		// Optional fields.

		if ( isset( $params['description'] ) ) {
			$coupon->set_description( sanitize_textarea_field( $params['description'] ) );
		}

		if ( isset( $params['individual_use'] ) ) {
			$coupon->set_individual_use( wc_string_to_bool( $params['individual_use'] ) );
		}

		if ( isset( $params['free_shipping'] ) ) {
			$coupon->set_free_shipping( wc_string_to_bool( $params['free_shipping'] ) );
		}

		if ( isset( $params['usage_limit'] ) ) {
			$coupon->set_usage_limit( absint( $params['usage_limit'] ) );
		}

		if ( isset( $params['usage_limit_per_user'] ) ) {
			$coupon->set_usage_limit_per_user( absint( $params['usage_limit_per_user'] ) );
		}

		if ( isset( $params['limit_usage_to_x_items'] ) ) {
			$coupon->set_limit_usage_to_x_items( absint( $params['limit_usage_to_x_items'] ) );
		}

		if ( isset( $params['minimum_amount'] ) ) {
			$coupon->set_minimum_amount( wc_format_decimal( $params['minimum_amount'] ) );
		}

		if ( isset( $params['maximum_amount'] ) ) {
			$coupon->set_maximum_amount( wc_format_decimal( $params['maximum_amount'] ) );
		}

		if ( isset( $params['email_restrictions'] ) ) {
			$coupon->set_email_restrictions(
				array_map( 'sanitize_email', (array) $params['email_restrictions'] )
			);
		}

		if ( isset( $params['excluded_sale_items'] ) ) {
			$coupon->set_exclude_sale_items(
				wc_string_to_bool( $params['excluded_sale_items'] )
			);
		}

		if ( isset( $params['product_ids'] ) ) {
			$coupon->set_product_ids(
				array_map( 'absint', (array) $params['product_ids'] )
			);
		}

		if ( isset( $params['excluded_product_ids'] ) ) {
			$coupon->set_excluded_product_ids(
				array_map( 'absint', (array) $params['excluded_product_ids'] )
			);
		}

		if ( isset( $params['product_categories'] ) ) {
			$coupon->set_product_categories(
				array_map( 'absint', (array) $params['product_categories'] )
			);
		}

		if ( isset( $params['excluded_product_categories'] ) ) {
			$coupon->set_excluded_product_categories(
				array_map( 'absint', (array) $params['excluded_product_categories'] )
			);
		}

		if ( ! empty( $params['date_expires'] ) ) {
			$coupon->set_date_expires( strtotime( $params['date_expires'] ) );
		}

		$coupon->save();

		// Update coupon status if provided.
		if ( ! empty( $params['status'] ) ) {

			wp_update_post(
				array(
					'ID'          => $coupon->get_id(),
					'post_status' => sanitize_key( $params['status'] ),
				)
			);

			$coupon = new WC_Coupon( $coupon->get_id() );
		}

		return $this->success_response(
			$this->prepare_coupon_response( $coupon ),
			'Coupon created successfully.',
			201
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			'create_coupon_error',
			$e->getMessage(),
			500
		);
	}
}

/**
 * Update Coupon.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
/**
 * Update Coupon.
 */
public function update_coupon( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Coupon code is required.
	if ( empty( $params['code'] ) ) {
		return $this->error_response(
			'coupon_code_required',
			'Coupon code is required.',
			422
		);
	}

	// Find coupon by code.
	$coupon = new WC_Coupon(
		sanitize_text_field( $params['code'] )
	);

	if ( ! $coupon->get_id() ) {
		return $this->error_response(
			'coupon_not_found',
			'Coupon not found.',
			404
		);
	}

	$coupon_id = $coupon->get_id();

	try {

		// New Coupon Code.
		if ( ! empty( $params['new_code'] ) ) {

			$existing_coupon = new WC_Coupon(
				sanitize_text_field( $params['new_code'] )
			);

			if (
				$existing_coupon->get_id() &&
				$existing_coupon->get_id() !== $coupon_id
			) {
				return $this->error_response(
					'coupon_exists',
					'Coupon code already exists.',
					409
				);
			}

			$coupon->set_code(
				sanitize_text_field( $params['new_code'] )
			);
		}

		// Discount Type.
		if ( isset( $params['discount_type'] ) ) {

			$discount_type = strtolower( trim( $params['discount_type'] ) );

			$discount_type_map = array(
				'percentage discount'    => 'percent',
				'percent'                => 'percent',
				'fixed cart discount'    => 'fixed_cart',
				'fixed_cart'             => 'fixed_cart',
				'fixed product discount' => 'fixed_product',
				'fixed_product'          => 'fixed_product',
			);

			if ( ! isset( $discount_type_map[ $discount_type ] ) ) {
				return $this->error_response(
					'invalid_discount_type',
					'Invalid discount type.',
					422
				);
			}

			$coupon->set_discount_type(
				$discount_type_map[ $discount_type ]
			);
		}

		// Amount.
		if ( isset( $params['amount'] ) ) {
			$coupon->set_amount(
				wc_format_decimal( $params['amount'] )
			);
		}

		// Description.
		if ( isset( $params['description'] ) ) {
			$coupon->set_description(
				sanitize_textarea_field( $params['description'] )
			);
		}

		// Individual Use.
		if ( isset( $params['individual_use'] ) ) {
			$coupon->set_individual_use(
				wc_string_to_bool( $params['individual_use'] )
			);
		}

		// Free Shipping.
		if ( isset( $params['free_shipping'] ) ) {
			$coupon->set_free_shipping(
				wc_string_to_bool( $params['free_shipping'] )
			);
		}

		// Usage Limit.
		if ( isset( $params['usage_limit'] ) ) {
			$coupon->set_usage_limit(
				absint( $params['usage_limit'] )
			);
		}

		// Usage Limit Per User.
		if ( isset( $params['usage_limit_per_user'] ) ) {
			$coupon->set_usage_limit_per_user(
				absint( $params['usage_limit_per_user'] )
			);
		}

		// Limit Usage To X Items.
		if ( isset( $params['limit_usage_to_x_items'] ) ) {
			$coupon->set_limit_usage_to_x_items(
				absint( $params['limit_usage_to_x_items'] )
			);
		}

		// Minimum Amount.
		if ( isset( $params['minimum_amount'] ) ) {
			$coupon->set_minimum_amount(
				wc_format_decimal( $params['minimum_amount'] )
			);
		}

		// Maximum Amount.
		if ( isset( $params['maximum_amount'] ) ) {
			$coupon->set_maximum_amount(
				wc_format_decimal( $params['maximum_amount'] )
			);
		}

		// Email Restrictions.
		if ( isset( $params['email_restrictions'] ) ) {
			$coupon->set_email_restrictions(
				array_map(
					'sanitize_email',
					(array) $params['email_restrictions']
				)
			);
		}

		// Exclude Sale Items.
		if ( isset( $params['excluded_sale_items'] ) ) {
			$coupon->set_exclude_sale_items(
				wc_string_to_bool( $params['excluded_sale_items'] )
			);
		}

		// Product IDs.
		if ( isset( $params['product_ids'] ) ) {
			$coupon->set_product_ids(
				array_map(
					'absint',
					(array) $params['product_ids']
				)
			);
		}

		// Excluded Product IDs.
		if ( isset( $params['excluded_product_ids'] ) ) {
			$coupon->set_excluded_product_ids(
				array_map(
					'absint',
					(array) $params['excluded_product_ids']
				)
			);
		}

		// Product Categories.
		if ( isset( $params['product_categories'] ) ) {
			$coupon->set_product_categories(
				array_map(
					'absint',
					(array) $params['product_categories']
				)
			);
		}

		// Excluded Product Categories.
		if ( isset( $params['excluded_product_categories'] ) ) {
			$coupon->set_excluded_product_categories(
				array_map(
					'absint',
					(array) $params['excluded_product_categories']
				)
			);
		}

		// Expiry Date.
		if ( ! empty( $params['expiry_date'] ) ) {
			$coupon->set_date_expires(
				strtotime( $params['expiry_date'] )
			);
		}

		$coupon->save();

		// Status.
		if ( isset( $params['status'] ) ) {

			wp_update_post(
				array(
					'ID'          => $coupon->get_id(),
					'post_status' => sanitize_key( $params['status'] ),
				)
			);

			$coupon = new WC_Coupon( $coupon->get_id() );
		}

		return $this->success_response(
			$this->prepare_coupon_response( $coupon ),
			'Coupon updated successfully.'
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			'update_coupon_error',
			$e->getMessage(),
			500
		);
	}
}

/**
 * Delete Coupon.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
/**
 * Delete Coupon.
 */
public function delete_coupon( WP_REST_Request $request ) {

	$params = $request->get_json_params();

	// Coupon code is required.
	if ( empty( $params['code'] ) ) {
		return $this->error_response(
			'coupon_code_required',
			'Coupon code is required.',
			422
		);
	}

	// Find coupon by code.
	$coupon = new WC_Coupon(
		sanitize_text_field( $params['code'] )
	);

	if ( ! $coupon->get_id() ) {
		return $this->error_response(
			'coupon_not_found',
			'Coupon not found.',
			404
		);
	}

	$coupon_id = $coupon->get_id();

	try {

		// Keep response before deletion.
		$response = $this->prepare_coupon_response( $coupon );

		$result = wp_delete_post( $coupon_id, true );

		if ( ! $result ) {
			return $this->error_response(
				'delete_coupon_error',
				'Unable to delete coupon.',
				400
			);
		}

		return $this->success_response(
			$response,
			'Coupon deleted successfully.'
		);

	} catch ( Exception $e ) {

		return $this->error_response(
			'delete_coupon_error',
			$e->getMessage(),
			500
		);
	}
}
/**
 * Prepare Coupon Response.
 *
 * @param WC_Coupon $coupon Coupon object.
 *
 * @return array
 */
private function prepare_coupon_response( WC_Coupon $coupon ) {

	return array(
		'id'                   => $coupon->get_id(),
		'code'                 => $coupon->get_code(),
		'discount_type'        => $coupon->get_discount_type(),
		'amount'               => $coupon->get_amount(),
		'usage_limit'          => $coupon->get_usage_limit(),
		'usage_limit_per_user' => $coupon->get_usage_limit_per_user(),
		'usage_count'          => $coupon->get_usage_count(),
		'minimum_amount'       => $coupon->get_minimum_amount(),
		'maximum_amount'       => $coupon->get_maximum_amount(),
		'date_created'         => $coupon->get_date_created()
			? $coupon->get_date_created()->date( 'Y-m-d H:i:s' )
			: null,
		'date_expires'         => $coupon->get_date_expires()
			? $coupon->get_date_expires()->date( 'Y-m-d H:i:s' )
			: null,
		'status'               => $coupon->get_status(),
	);
}
/**
 * Get Top Sold Categories.
 *
 * @param WP_REST_Request $request REST Request.
 *
 * @return WP_REST_Response
 */
public function get_top_sold_categories( WP_REST_Request $request ) {

	global $wpdb;

	$period     = sanitize_text_field( $request->get_param( 'period' ) );
	$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
	$end_date   = sanitize_text_field( $request->get_param( 'end_date' ) );

	$page   = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
	$limit  = max( 1, absint( $request->get_param( 'limit' ) ?: 10 ) );
	$offset = ( $page - 1 ) * $limit;

	$where = " WHERE os.status = 'wc-completed' ";

	switch ( $period ) {

		case 'today':
			$where .= " AND DATE(os.date_created) = CURDATE() ";
			break;

		case 'this_week':
			$where .= " AND YEARWEEK(os.date_created,1) = YEARWEEK(CURDATE(),1) ";
			break;

		case 'last_week':
			$where .= " AND YEARWEEK(os.date_created,1) = YEARWEEK(CURDATE(),1)-1 ";
			break;

		case 'this_month':
			$where .= " AND MONTH(os.date_created) = MONTH(CURDATE())
						AND YEAR(os.date_created) = YEAR(CURDATE()) ";
			break;

		case 'custom':

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {

				$where .= $wpdb->prepare(
					" AND DATE(os.date_created) BETWEEN %s AND %s ",
					$start_date,
					$end_date
				);
			}

			break;
	}

	$count_sql = "

		SELECT COUNT(*) FROM (

			SELECT t.term_id

			FROM {$wpdb->prefix}wc_order_product_lookup op

			INNER JOIN {$wpdb->prefix}wc_order_stats os
				ON os.order_id = op.order_id

			INNER JOIN {$wpdb->term_relationships} tr
				ON tr.object_id = op.product_id

			INNER JOIN {$wpdb->term_taxonomy} tt
				ON tt.term_taxonomy_id = tr.term_taxonomy_id

			INNER JOIN {$wpdb->terms} t
				ON t.term_id = tt.term_id

			$where

			AND tt.taxonomy = 'product_cat'

			GROUP BY t.term_id

		) AS category_count

	";

	$total = (int) $wpdb->get_var( $count_sql );

	$sql = "

		SELECT

			t.term_id AS category_id,
			t.name AS category_name,

			COUNT(DISTINCT op.product_id) AS products_sold,

			SUM(op.product_qty) AS quantity_sold,

			ROUND(SUM(op.product_net_revenue),2) AS total_sales

		FROM {$wpdb->prefix}wc_order_product_lookup op

		INNER JOIN {$wpdb->prefix}wc_order_stats os
			ON os.order_id = op.order_id

		INNER JOIN {$wpdb->term_relationships} tr
			ON tr.object_id = op.product_id

		INNER JOIN {$wpdb->term_taxonomy} tt
			ON tt.term_taxonomy_id = tr.term_taxonomy_id

		INNER JOIN {$wpdb->terms} t
			ON t.term_id = tt.term_id

		$where

		AND tt.taxonomy = 'product_cat'

		GROUP BY t.term_id

		ORDER BY quantity_sold DESC

		LIMIT %d OFFSET %d

	";

	$categories = $wpdb->get_results(
		$wpdb->prepare(
			$sql,
			$limit,
			$offset
		),
		ARRAY_A
	);

	return $this->success_response(
		array(
			'categories'   => $categories,
			'total'        => $total,
			'page'         => $page,
			'limit'        => $limit,
			'total_pages'  => ceil( $total / $limit ),
			'has_next'     => ( $page * $limit ) < $total,
			'has_previous' => $page > 1,
		),
		'Top sold categories fetched successfully.'
	);
}
public function get_top_sold_products( WP_REST_Request $request ) {

    global $wpdb;

    $period     = sanitize_text_field( $request->get_param( 'period' ) );
    $start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
    $end_date   = sanitize_text_field( $request->get_param( 'end_date' ) );

    $page   = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
    $limit  = max( 1, absint( $request->get_param( 'limit' ) ?: 10 ) );
    $offset = ( $page - 1 ) * $limit;

    $where = " WHERE os.status = 'wc-completed' ";

    switch ( $period ) {

        case 'today':
            $where .= " AND DATE(os.date_created) = CURDATE() ";
            break;

        case 'this_week':
            $where .= " AND YEARWEEK(os.date_created,1) = YEARWEEK(CURDATE(),1) ";
            break;

        case 'last_week':
            $where .= " AND YEARWEEK(os.date_created,1) = YEARWEEK(CURDATE(),1)-1 ";
            break;

        case 'this_month':
            $where .= " AND MONTH(os.date_created)=MONTH(CURDATE())
                        AND YEAR(os.date_created)=YEAR(CURDATE()) ";
            break;

        case 'custom':

            if ( ! empty( $start_date ) && ! empty( $end_date ) ) {

                $where .= $wpdb->prepare(
                    " AND DATE(os.date_created) BETWEEN %s AND %s ",
                    $start_date,
                    $end_date
                );
            }

            break;
    }

    $count_sql = "
        SELECT COUNT(*) FROM (
            SELECT op.product_id
            FROM {$wpdb->prefix}wc_order_product_lookup op
            INNER JOIN {$wpdb->prefix}wc_order_stats os
                ON os.order_id = op.order_id
            $where
            GROUP BY op.product_id
        ) AS product_count
    ";

    $total = (int) $wpdb->get_var( $count_sql );

    $sql = "
        SELECT

            op.product_id,

            p.post_title AS product_name,

            sku.meta_value AS sku,

            SUM(op.product_qty) AS quantity_sold,

            ROUND(SUM(op.product_net_revenue),2) AS total_sales,

            COUNT(DISTINCT op.order_id) AS orders_count

        FROM {$wpdb->prefix}wc_order_product_lookup op

        INNER JOIN {$wpdb->prefix}wc_order_stats os
            ON os.order_id = op.order_id

        INNER JOIN {$wpdb->posts} p
            ON p.ID = op.product_id

        LEFT JOIN {$wpdb->postmeta} sku
            ON sku.post_id = op.product_id
            AND sku.meta_key = '_sku'

        $where

        GROUP BY op.product_id

        ORDER BY quantity_sold DESC

        LIMIT %d OFFSET %d
    ";

    $products = $wpdb->get_results(
        $wpdb->prepare(
            $sql,
            $limit,
            $offset
        ),
        ARRAY_A
    );

    return $this->success_response(
        array(
            'products'      => $products,
            'total'         => $total,
            'page'          => $page,
            'limit'         => $limit,
            'total_pages'   => ceil( $total / $limit ),
            'has_next'      => ( $page * $limit ) < $total,
            'has_previous'  => $page > 1,
        ),
        'Top sold products fetched successfully.'
    );
}
/**
 * Get Order Details by Order ID
 *
 * GET /wp-json/pinaka-pos/v1/mcp/orders/{id}
 */
public function get_order_by_id( WP_REST_Request $request ) {

    $order_id = absint( $request['id'] );

    if ( empty( $order_id ) ) {
        return new WP_Error(
            'missing_order_id',
            __( 'Order ID is required.', 'pinaka-pos' ),
            array( 'status' => 400 )
        );
    }

    // Get WooCommerce Order
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return new WP_Error(
            'invalid_order',
            __( 'Order not found.', 'pinaka-pos' ),
            array( 'status' => 404 )
        );
    }

    // WooCommerce REST Controllers
    $api_product = new WC_REST_Products_Controller();
    $api_var     = new WC_REST_Product_Variations_Controller();

    // Order IDs
    $pos_client_id = $order->get_meta( '_pos_client_order_id', true );
    $woo_order_id  = $order->get_id();

    // Refund Details
    $refunded_item_ids = [];

    foreach ( $order->get_refunds() as $refund ) {

        foreach ( $refund->get_items() as $refund_item ) {

            $line_item_id = $refund_item->get_meta(
                '_refunded_item_id',
                true
            );

            if ( ! $line_item_id ) {
                continue;
            }

            $refunded_item_ids[ $line_item_id ] = true;
        }
    }

    $refund_order_total      = (float) $order->get_total();
    $total_refunded          = (float) $order->get_total_refunded();
    $refund_remaining_amount = max(
        0,
        $refund_order_total - $total_refunded
    );

    // Arrays
    $line_items = [];
    $fee_lines  = [];
    $coupons    = [];
    /**
 * Build Line Items
 */
foreach ( $order->get_items() as $item ) {

    $product   = wc_get_product( $item->get_product_id() );
    $image_id  = $product ? $product->get_image_id() : null;
    $image_src = $image_id ? wp_get_attachment_url( $image_id ) : '';

    // Meta Data
    $item_meta_data = array();

    foreach ( $item->get_meta_data() as $meta ) {

        $item_meta_data[] = array(
            'id'            => $meta->id,
            'key'           => $meta->key,
            'value'         => maybe_unserialize( $meta->value ),
            'display_key'   => wc_attribute_label( $meta->key ),
            'display_value' => maybe_unserialize( $meta->value ),
        );
    }

    // Check Refund Status
    $is_refunded = isset( $refunded_item_ids[ $item->get_id() ] );

    if ( $order->has_status( 'refunded' ) ) {
        $is_refunded = false;
    }

    $line = array(
        'id'             => $item->get_id(),
        'name'           => $item->get_name(),
        'product_id'     => $item->get_product_id(),
        'variation_id'   => $item->get_variation_id(),
        'quantity'       => $item->get_quantity(),
        'tax_class'      => $item->get_tax_class(),
        'subtotal'       => $item->get_subtotal(),
        'subtotal_tax'   => $item->get_subtotal_tax(),
        'total'          => $item->get_total(),
        'total_tax'      => $item->get_total_tax(),
        'taxes'          => $item->get_taxes(),
        'meta_data'      => $item_meta_data,
        'is_refund_item' => $is_refunded,
        'image' => array(
            'id'  => $image_id,
            'src' => $image_src,
        ),
    );

    /**
     * Product Details
     */
    if ( ! empty( $line['product_id'] ) ) {

        $req_product = new WP_REST_Request( 'GET' );

        $req_product->set_query_params(
            array(
                'id' => $line['product_id'],
            )
        );

        $res_product = $api_product->get_item( $req_product );

        $line['product_data'] = is_wp_error( $res_product )
            ? null
            : $res_product->get_data();
    }

    /**
     * Variation Details
     */
    if ( ! empty( $line['variation_id'] ) ) {

        $req_var = new WP_REST_Request( 'GET' );

        $req_var->set_query_params(
            array(
                'id' => $line['variation_id'],
            )
        );

        $res_var = $api_var->get_item( $req_var );

        $line['product_variation_data'] = is_wp_error( $res_var )
            ? null
            : $res_var->get_data();
    }

    $line_items[] = $line;
}
/**
 * Build Fee Lines
 */
$fee_lines = array();

foreach ( $order->get_fees() as $fee ) {

    $taxes = array();

    foreach ( $fee->get_taxes()['total'] ?? array() as $tax_id => $total_tax ) {

        $taxes[] = array(
            'id'       => (int) $tax_id,
            'total'    => wc_format_decimal( $total_tax ),
            'subtotal' => '',
        );
    }

    $fee_meta_data = array();

    foreach ( $fee->get_meta_data() as $meta ) {

        $fee_meta_data[] = array(
            'id'            => $meta->id,
            'key'           => $meta->key,
            'value'         => $meta->value,
            'display_key'   => $meta->key,
            'display_value' => $meta->value,
        );
    }

    $fee_lines[] = array(
        'id'         => $fee->get_id(),
        'name'       => $fee->get_name(),
        'tax_class'  => $fee->get_tax_class(),
        'tax_status' => $fee->get_tax_status(),
        'amount'     => '',
        'total'      => wc_format_decimal( $fee->get_total() ),
        'total_tax'  => wc_format_decimal( $fee->get_total_tax() ),
        'taxes'      => $taxes,
        'meta_data'  => $fee_meta_data,
    );
}
/**
 * Build Coupon Lines
 */
$coupons = array_values(
    array_filter(
        array_map(
            function ( $item ) {

                $coupon_code = $item->get_code();

                // Get Coupon ID
                $coupon_id = wc_get_coupon_id_by_code( $coupon_code );

                // Skip Internal Coupons
                $is_internal = $coupon_id
                    ? get_post_meta( $coupon_id, '_is_internal_coupon', true )
                    : '';

                if ( $is_internal === 'yes' ) {
                    return null;
                }

                $coupon = new WC_Coupon( $coupon_code );

                $coupon_meta = array();

                foreach ( $item->get_meta_data() as $meta ) {

                    $coupon_meta[] = array(
                        'id'            => $meta->id,
                        'key'           => $meta->key,
                        'value'         => $meta->value,
                        'display_key'   => $meta->key,
                        'display_value' => $meta->value,
                    );
                }

                return array(
                    'id'            => $item->get_id(),
                    'code'          => $coupon_code,
                    'discount'      => wc_format_decimal( $item->get_discount(), 2 ),
                    'discount_tax'  => wc_format_decimal( $item->get_discount_tax(), 2 ),
                    'discount_type' => $coupon->get_discount_type(),
                    'meta_data'     => $coupon_meta,
                );

            },
            $order->get_items( 'coupon' )
        ),
        'is_array'
    )
);
/**
 * Payment Details
 */
$pos_payments = $order->get_meta( '_pos_payments' );

$date_completed = null;

if ( ! empty( $pos_payments ) && is_array( $pos_payments ) ) {

    // Get Last Payment Entry
    $last_payment = end( $pos_payments );

    if (
        isset( $last_payment['created_at'] ) &&
        ! empty( $last_payment['created_at'] )
    ) {
        $date_completed = $last_payment['created_at'];
    }
}

/**
 * Discounts
 */
$merchant_discount = (float) $order->get_meta(
    '_merchant_discount',
    true
);

$actual_discount = (float) $order->get_discount_total();

$calculated_discount = round(
    $actual_discount - $merchant_discount,
    2
);

/**
 * Refund Summary
 */
$refund_order_total = (float) $order->get_total();

$total_refunded = (float) $order->get_total_refunded();

$refund_remaining_amount = max(
    0,
    $refund_order_total - $total_refunded
);

/**
 * Currency
 */
$currency = esc_attr( get_woocommerce_currency() );

$currency_symbol = get_option( 'currency_symbol' );

/**
 * Optional Order Type
 */
$order_type = get_post_meta(
    $order->get_id(),
    '_order_type',
    true
);
/**
 * Final Response
 */
$formatted_order = array(
    'id'                => $pos_client_id ? (int) $pos_client_id : $woo_order_id,
    'woo_order_id'      => $woo_order_id,
    'status'            => ucfirst( $order->get_status() ),

    'date_created'      => $order->get_date_created()
        ? $order->get_date_created()->date( 'Y-m-d H:i:s' )
        : null,

    'date_modified'     => $order->get_date_modified()
        ? $order->get_date_modified()->date( 'Y-m-d H:i:s' )
        : null,

    'total'             => $order->get_total(),

    'discount_total'    => wc_format_decimal(
        $calculated_discount,
        2
    ),

    'discount_tax'      => $order->get_discount_tax(),

    'total_tax'         => $order->get_total_tax(),

    'created_via'       => $order->get_created_via(),

    'author'            => $order->get_meta( '_wc_order_created_by' ),

    'currency'          => $currency,

    'currency_symbol'   => $currency_symbol,

    'date_completed'    => $date_completed,

    'date_paid'         => $date_completed,

    'coupon_lines'      => $coupons,

    'line_items'        => $line_items,

    'fee_lines'         => $fee_lines,

    'refund_order_total'=> $refund_order_total,

    'refund_total'      => $total_refunded,

    'net_payment'       => round(
        $refund_remaining_amount,
        2
    ),
);

// Optional Order Type
if ( ! empty( $order_type ) ) {
    $formatted_order['order_type'] = $order_type;
}

/**
 * Success Response
 */
return rest_ensure_response(
    array(
        'success' => true,
        'message' => 'Order details fetched successfully.',
        'data'    => $formatted_order,
    )
);
}
}
