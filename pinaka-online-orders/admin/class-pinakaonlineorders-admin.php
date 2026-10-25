<?php

if (!defined('ABSPATH')) exit;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;


class OrderOut_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_post_pinakaonlineorders_publish_menu', [$this, 'publish_menu']);
    }

    public function menu()
    {
        add_menu_page(
            'Pinaka Online Orders',
            'Pinaka Online Orders',
            'manage_options',
            'pinakaonlineorders-dashboard',
            [$this, 'dashboard'],
            'dashicons-store'
        );

        add_submenu_page(
            'pinakaonlineorders-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'pinakaonlineorders-dashboard',
            [$this, 'dashboard']
        );

        add_submenu_page(
            'pinakaonlineorders-dashboard',
            'Orders',
            'Orders',
            'manage_options',
            'pinakaonlineorders-orders',
            [$this, 'orders']
        );
        add_submenu_page('pinakaonlineorders-dashboard', 'Modifier Groups', 'Modifier Groups', 'manage_woocommerce', 'edit.php?post_type=modifier_group', '');

		add_submenu_page('pinakaonlineorders-dashboard', 'Modifiers', 'Modifiers', 'manage_woocommerce', 'edit.php?post_type=modifiers', '');
		
        add_submenu_page(
            'pinakaonlineorders-dashboard', 
            'Create Menu', 
            'Create Menu', 
            'manage_options', 
            'pinakaonlineorders-menus-add-edit', 
            [$this, 'render_products_by_category']
        );

        add_submenu_page(
            'pinakaonlineorders-dashboard',
            'Menus',
            'Menus',
            'manage_options',
            'pinakaonlineorders-menus',
            [$this, 'render_list_menu']
        );

        

        add_submenu_page(
            'pinakaonlineorders-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'pinakaonlineorders-settings',
            [$this, 'settings']
        );

        add_submenu_page(
            'pinakaonlineorders-dashboard',
            'Logs',
            'Logs',
            'manage_options',
            'pinakaonlineorders-logs',
            [$this, 'logs']
        );
        
    }

    public function assets()
    {
        wp_enqueue_style(
            'orderout-admin',
            PINAKAONLINEORDERS_PLUGIN_URL . 'admin/assets/admin.css',
            [],
            PINAKAONLINEORDERS_VERSION
        );

        wp_enqueue_script(
            'orderout-admin',
            PINAKAONLINEORDERS_PLUGIN_URL . 'admin/assets/admin.js',
            ['jquery'],
            PINAKAONLINEORDERS_VERSION,
            true
        );

        wp_localize_script(
            'orderout-admin',
            'orderout_admin',
            [
                'email'       => get_option('store_registered_email_id') ?? get_option('pinaka_pos_email'),
                'connect_url' => 'https://test.alekyatechsolutions.com/wp-json/custom/v1/connect-orderout',
                'save_url'    => rest_url('pinaka/v1/save-orderout'),
                'orders_url'  => rest_url('pinaka/v1/orders'),
                'cancel_order_url' => rest_url('pinaka/v1/orders/'),
                'nonce'       => wp_create_nonce('wp_rest'),
            ]
        );

        wp_localize_script(
            'orderout-admin',
            'orderout_webhook',
            [
                'nonce'             => wp_create_nonce('wp_rest'),
                'connect_webhook'   => rest_url('pinaka/v1/connect-webhook'),
            ]
        );
    }

    public function dashboard()
    {
        include PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/views/dashboard.php';
    }

    public function settings()
    {
        include PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/views/settings.php';
    }

    public function logs()
    {
        include PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/views/logs.php';
    }

    public function orders()
    {
        include PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/views/dashboard-orders.php';
    }

    public function register_rest_routes()
    {
        register_rest_route('pinaka/v1', '/save-orderout', [
            'methods' => 'POST',
            'callback' => [$this, 'save_orderout'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);

        register_rest_route('pinaka/v1', '/orders', [
            'methods' => 'GET',
            'callback' => [$this, 'orders_api'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
        register_rest_route('pinaka/v1', '/orders/(?P<order_id>\d+)/cancel', [
            'methods' => 'POST',
            'callback' => [$this, 'cancel_order_api'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'args' => [
                'order_id' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($value) {
                        return is_string($value) && preg_match('/^\d+$/', $value);
                    },
                ],
            ],
        ]);
        // Connect webhook (called from admin AJAX)
        register_rest_route(
            'pinaka/v1',
            '/connect-webhook',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'connect_webhook'],
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                }
            ]
        );

        // OrderOut webhook (called by OrderOut)
        register_rest_route(
            'pinaka-restaurant-pos/v1/uber-eats',
            '/webhook',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'orderout_webhook'],
                'permission_callback' => '__return_true'
            ]
        );
    }

    public function save_orderout(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        $settings = get_option('orderout_settings', []);

        // $settings['connected']    = !empty($params['connected']);
        // $settings['connected_at'] = sanitize_text_field($params['connected_at'] ?? '');
        $api_response = $params['api_response'] ?? [];
        $res = json_decode($api_response, true);
        $orderout_restuarant_id  = $res['id'];
        $orderout_restuarant_api_created_at  = $res['api_created_at'];
        $orderout_restuarant_accountId  = $res['accountId'];
        $orderout_restuarant_pointOfSaleId  = $res['pointOfSaleId'];
        $orderout_restuarant_billing  = $res['billing'];

        // echo json_encode($settings);
        update_option('orderout_restuarant_id', $orderout_restuarant_id);
        update_option('orderout_restuarant_api_created_at', $orderout_restuarant_api_created_at);
        update_option('orderout_restuarant_accountId', $orderout_restuarant_accountId);
        update_option('orderout_restuarant_pointOfSaleId', $orderout_restuarant_pointOfSaleId);
        update_option('orderout_restuarant_billing', $orderout_restuarant_billing);

        return rest_ensure_response([
            'success' => true,
            'message' => 'Saved successfully.'
        ]);
    }


    public function get_orderout_orders($filters = [])
    {
        $api_key = "s11pfICCvVLnNsQujYu60TuAo6OLqyhF";
        $service_merchant_id = "0e841c9c-1991-4bd0-ad7f-b1c3ce640cfa";

        if (empty($api_key) || empty($service_merchant_id)) {

            return new WP_Error(
                'not_connected',
                'OrderOut is not connected.'
            );

        }

        $query = [
            'page' => $filters['page'] ?? 1,
            'items_per_page' => $filters['items_per_page'] ?? 10,
            'service_merchant_id' => $service_merchant_id
        ];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        if (!empty($filters['from_date'])) {
            $query['from_date'] = $filters['from_date'];
        }

        if (!empty($filters['to_date'])) {
            $query['to_date'] = $filters['to_date'];
        }
        $url = add_query_arg(
            $query,
            'https://api.orderout.co/api/pos/order'
        );

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [  
                'accept' => 'application/json',
                'api-key' => $api_key
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(
            wp_remote_retrieve_body($response),
            true
        );
    }

    public function orders_api(WP_REST_Request $request)
    {
        $filters = [
            'page' => $request->get_param('page'),
            'items_per_page' => $request->get_param('items_per_page'),
            'status' => $request->get_param('status'),
            'from_date' => $request->get_param('from_date'),
            'to_date' => $request->get_param('to_date'),
        ];

        $orders = $this->get_orderout_orders($filters);

        if (is_wp_error($orders)) {

            return rest_ensure_response([
                'success' => false,
                'message' => $orders->get_error_message()
            ]);

        }

        return rest_ensure_response($orders);
    }
    public function cancel_order_api(WP_REST_Request $request)
    {
        $order_id = $request->get_param('order_id');
        $reason = sanitize_text_field($request->get_param('reason') ?: 'ITEM_UNAVAILABLE');
        $allowed_reasons = ['ITEM_UNAVAILABLE'];

        if (!in_array($reason, $allowed_reasons, true)) {
            return new WP_Error('invalid_cancel_reason', 'Invalid cancellation reason.', ['status' => 400]);
        }

        $api_key = get_option('orderout_api_key', 's11pfICCvVLnNsQujYu60TuAo6OLqyhF');
        $url = sprintf(
            'https://api.orderout.co/api/channel/order/%s/cancel',
            rawurlencode($order_id)
        );
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => [
                'accept' => 'application/json',
                'api-key' => $api_key,
                'content-type' => 'application/json',
            ],
            'body' => wp_json_encode(['reason' => $reason]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('orderout_cancel_failed', $response->get_error_message(), ['status' => 502]);
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status < 200 || $status >= 300) {
            $message = is_array($body) && !empty($body['message'])
                ? sanitize_text_field($body['message'])
                : 'OrderOut could not cancel this order.';
            return new WP_Error('orderout_cancel_rejected', $message, ['status' => $status ?: 502]);
        }

        return rest_ensure_response([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => $body,
        ]);
    }
    public function orderout_webhook(WP_REST_Request $request)
    {
        $headers = $request->get_headers();

        $payload = $request->get_json_params();

        error_log("========= ORDEROUT WEBHOOK =========");
        error_log(print_r($headers,true));
        error_log(print_r($payload,true));

        update_option(
            'last_orderout_webhook',
            wp_json_encode($payload)
        );

        $this->publish_mqtt($payload);

        return new WP_REST_Response([
            'success' => true
        ],200);
    }

    private function publish_mqtt($payload)
    {
        $host = 'broker.emqx.io';
        $port = 1883;
        $client_id = 'pinaka-online-orders-' . uniqid();
        $topic = 'orderout/orders';

        $mqtt = new \PhpMqtt\Client\MqttClient($host, $port, $client_id);

        try {
            $mqtt->connect();
            $mqtt->publish($topic, json_encode($payload), 0);
            $mqtt->disconnect();
        } catch (\PhpMqtt\Client\Exceptions\MqttClientException $e) {
            error_log('MQTT Publish Error: ' . $e->getMessage());
        }
    }
    

    public function connect_webhook()
    {  
        $api_key = "s11pfICCvVLnNsQujYu60TuAo6OLqyhF";

        $endpoint = rest_url('pinaka/v1/orderout-webhook');

        $response = wp_remote_post(
            'https://api.orderout.co/api/webhooks/push_order',
            [
                'method'  => 'POST',
                'timeout' => 30,
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                    'api-key'      => $api_key
                ],
                'body' => wp_json_encode([
                    'endpoint' => $endpoint,
                    'method'   => 'POST',
                    "authorization_header" => [
                        "Authorization" => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczpcL1wvbWVyY2hhbnRyZXRhaWwuYWxla3Rhc29sdXRpb25zLmNvbSIsImlhdCI6MTc4NDkxODE2MSwibmJmIjoxNzg0OTE4MTYxLCJleHAiOjE3ODc1MTAxNjEsImRhdGEiOnsidXNlciI6eyJpZCI6MTA4fX19.PVyt8J1ytAY5UF94AQi6Q6-C9mVte4IoutkZ-QUAntY"
                    ]
                ])
            ]
        );

        if (is_wp_error($response)) {

            return new WP_REST_Response([
                'success' => false,
                'message' => $response->get_error_message()
            ],400);

        }

        $status = wp_remote_retrieve_response_code($response);

        $body = wp_remote_retrieve_body($response);

        error_log("OrderOut Status : ".$status);
        error_log("OrderOut Response : ".$body);

        $result = json_decode($body,true);

        update_option(
            'orderout_webhook_response',
            $body
        );

        update_option(
            'orderout_webhook_connected_at',
            current_time('mysql')
        );

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Webhook registered successfully.',
            'data'    => $result
        ]);
    }

    public function render_list_menu()
    {
        $restaurant_id = get_option('orderout_restuarant_id') ?: '5189587907510272';
        $api_key = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';

        if ( empty( $restaurant_id ) ) {
            return new WP_Error( 'invalid_restaurant', 'Restaurant ID is required.' );
        }
        
        $url = sprintf(
            'https://api.orderout.co/api/pos/restaurant/%s/menu',
            rawurlencode( $restaurant_id )
        );
    
        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'api-key'      => $api_key,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'timeout' => 60,
            ]
        );
    
        if ( is_wp_error( $response ) ) {
            return $response;
        }
    
        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
    
        if ( $status_code !== 200 ) {
            return new WP_Error(
                'orderout_error',
                $body,
                [ 'status' => $status_code ]
            );
        }
    
        $response = json_decode( $body, true );
        ?>
        
        <div class="wrap">
            <h1 class="wp-heading-inline">Restaurant Menus</h1>
            <?php
            $create_url = admin_url( 'admin.php?page=pinakaonlineorders-menus-add-edit' );
            ?>
        
            <a href="<?php echo esc_url( $create_url ); ?>" class="page-title-action">
                Create New Menu
            </a>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th width="20%">Menu Name</th>
                        <th>Description</th>
                        <th width="18%">Created At</th>
                        <th width="10%">Published</th>
                        <th width="10%">Archived</th>
                        <th width="10%">Action</th>
                        <th width="10%">Publish</th>
                    </tr>
                </thead>
        
                <tbody>
        
                <?php if ( ! empty( $response ) ) : ?>
        
                    <?php foreach ( $response as $menu ) : ?>
        
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $menu['name'] ); ?></strong>
                            </td>
        
                            <td>
                                <?php echo esc_html( $menu['description'] ); ?>
                            </td>
        
                            <td>
                                <?php echo esc_html( date( 'd M Y H:i', strtotime( $menu['api_created_at'] ) ) ); ?>
                            </td>
        
                            <td>
                                <?php echo ! empty( $menu['published'] ) ? '✅ Yes' : '❌ No'; ?>
                            </td>
        
                            <td>
                                <?php echo ! empty( $menu['archived'] ) ? '✅ Yes' : '❌ No'; ?>
                            </td>
        
                            <td>
        
                                <?php
                                $edit_url = admin_url(
                                    'admin.php?page=pinakaonlineorders-menus-add-edit'
                                    . '&restaurant_id=' . urlencode( $restaurant_id )
                                    . '&menu_id=' . urlencode( $menu['id'] )
                                );
                                ?>
        
                                <a href="<?php echo esc_url( $edit_url ); ?>"
                                   class="button button-primary">
                                    Edit
                                </a>
                                
                            </td>
                            <td>
                                <?php
                                $publish_url = wp_nonce_url(
                                    add_query_arg(
                                        [
                                            'action'        => 'pinakaonlineorders_publish_menu',
                                            'restaurant_id' => $restaurant_id,
                                            'menu_id'       => $menu['id'],
                                        ],
                                        admin_url( 'admin-post.php' )
                                    ),
                                    'pinakaonlineorders_publish_menu_' . $menu['id']
                                );
                                ?>
                                <a href="<?php echo esc_url( $publish_url ); ?>"
                                   class="button button-primary">
                                    Publish
                                </a>
                            </td>
                        </tr>
        
                    <?php endforeach; ?>
        
                <?php else : ?>
        
                    <tr>
                        <td colspan="6">
                            No menus found.
                        </td>
                    </tr>
        
                <?php endif; ?>
        
                </tbody>
            </table>
        </div>
        <?php
    }
    public function publish_menu()
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to publish menus.', 'pinaka-online-orders' ) );
        }

        $menu_id = isset( $_GET['menu_id'] )
            ? sanitize_text_field( wp_unslash( $_GET['menu_id'] ) )
            : '';
        $restaurant_id = isset( $_GET['restaurant_id'] )
            ? sanitize_text_field( wp_unslash( $_GET['restaurant_id'] ) )
            : '';

        check_admin_referer( 'pinakaonlineorders_publish_menu_' . $menu_id );

        $redirect_url = admin_url( 'admin.php?page=pinakaonlineorders-menus' );
        if ( '' === $restaurant_id || '' === $menu_id ) {
            wp_safe_redirect( add_query_arg( 'publish_error', 'Restaurant ID and menu ID are required.', $redirect_url ) );
            exit;
        }

        $endpoint = sprintf(
            'https://api.orderout.co/api/pos/restaurant/%s/push_menu/%s',
            rawurlencode( $restaurant_id ),
            rawurlencode( $menu_id )
        );
        $api_key = get_option( 'orderout_api_key', 's11pfICCvVLnNsQujYu60TuAo6OLqyhF' );
        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => 60,
                'headers' => [
                    'api-key'      => $api_key,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body' => wp_json_encode( new stdClass() ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( add_query_arg( 'publish_error', $response->get_error_message(), $redirect_url ) );
            exit;
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status < 200 || $status >= 300 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $message = is_array( $body ) && ! empty( $body['message'] )
                ? sanitize_text_field( $body['message'] )
                : 'OrderOut could not publish the menu (HTTP ' . absint( $status ) . ').';
            wp_safe_redirect( add_query_arg( 'publish_error', $message, $redirect_url ) );
            exit;
        }

        wp_safe_redirect( add_query_arg( 'menu_published', '1', $redirect_url ) );
        exit;
    }
    public function render_products_by_category() {
        $category_checked = false;

        $menu_id       =  @($_GET['menu_id']) ? sanitize_text_field( $_GET['menu_id'] ) : NULL;
        $restaurant_id = get_option('orderout_restuarant_id') ?: '5189587907510272';
        $response="";
        if ( isset( $_POST['pinaka_update_products'] ) ) {
            
            check_admin_referer( 'pinaka_update_products' );
           
            if(!empty($_POST['products']))
            {
                
                $menu_name = sanitize_text_field( $_POST['menu_name'] ?? '' );
                $menu_description = sanitize_textarea_field( $_POST['menu_description'] ?? '' );
                $menu_id_from = sanitize_text_field( $_POST['menu_id_from'] ?? '' );
                
                $menu = $this->build_orderout_menu($_POST['products'] ?? [],$_POST['modifier_groups'] ?? [],$_POST['modifiers'] ?? [],$menu_name,$menu_description);
 
                $response = $this->send_to_orderout(
                    $restaurant_id,
                    $menu_id_from,
                    $menu
                );
                
            }
        }
        
        $api_key       = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';
        $selected_items = [];
        if ( $response ) {
            echo "<h3>Menu Updated Successfully</h3>";
        }
        $selected_items           = [];
        $selected_modifier_groups = [];
        $selected_modifiers       = [];
        if($menu_id!='' && $restaurant_id!='')
        {
            $endpoint = "https://api.orderout.co/api/pos/restaurant/{$restaurant_id}/menu/{$menu_id}";
            
            $response = wp_remote_get(
                $endpoint,
                [
                    'timeout' => 60,
                    'headers' => [
                        'accept'  => 'application/json',
                        'api-key' => $api_key,
                    ],
                ]
            );
            
            if ( is_wp_error( $response ) ) {
                wp_die( $response->get_error_message() );
            }
            
            $status_code = wp_remote_retrieve_response_code( $response );
            $body        = wp_remote_retrieve_body( $response );
    
            $menu = json_decode( $body, true );
            /**
             * Build lookup for modifier groups
             */
            $modifier_group_map = [];

            if ( ! empty( $menu['modifier_groups'] ) ) {

                foreach ( $menu['modifier_groups'] as $group ) {

                    $modifier_group_map[ $group['id'] ] = [
                        'external_data' => $group['external_data'],
                        'modifier_options' => $group['modifier_options'],
                    ];
                }
            }
            foreach ( $menu['items'] as $item ) {
                $selected_items[(string) $item['external_data']] = (string) $item['external_data'];
                if ( strpos( $item['id'], 'modifier-' ) !== 0 ) {

                    if ( empty( $item['modifier_group_ids']['ids'] ) ) {
                        continue;
                    }
                    $product_id = (string) $item['external_data'];
                    $selected_items[$product_id] = $product_id;
                    foreach ( $item['modifier_group_ids']['ids'] as $group_api_id ) {

                        if ( empty( $modifier_group_map[ $group_api_id ] ) ) {
                            continue;
                        }

                        $group = $modifier_group_map[ $group_api_id ];

                        $group_id = (string) $group['external_data'];

                        $selected_modifier_groups[ $product_id ][] = $group_id;

                        if ( empty( $group['modifier_options'] ) ) {
                            continue;
                        }

                        foreach ( $group['modifier_options'] as $option ) {

                            $modifier_item_id = $option['id'];

                            foreach ( $menu['items'] as $modifier_item ) {

                                if ( $modifier_item['id'] !== $modifier_item_id ) {
                                    continue;
                                }

                                $selected_modifiers[ $product_id ][ $group_id ][] =
                                    (string) $modifier_item['external_data'];

                                break;
                            }
                        }
                    }
                }
            }
        }

        $category_products = [];

        $products = get_posts([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'return' => 'objects',
        ]);
        
        foreach ( $products as $product ) {
        
            $product_categories = wp_get_post_terms(
                $product->ID,
                'product_cat',
                [
                    'fields' => 'all',
                ]
            );
        
            foreach ( $product_categories as $category ) {
        
                // Skip unwanted categories
                if ( in_array( $category->name, [ 'Uncategorized', 'Default' ], true ) ) {
                    continue;
                }
        
                // Create category if it doesn't exist
                if ( ! isset( $category_products[ $category->term_id ] ) ) {
        
                    $category_products[ $category->term_id ] = [
                        'category' => $category,
                        'products' => [],
                    ];
                }
        
                $category_products[ $category->term_id ]['products'][] = $product;
            }
        }
        
        // Sort categories by name
        uasort( $category_products, function( $a, $b ) {
            return strcmp( $a['category']->name, $b['category']->name );
        });
        $modifier_groups = get_posts(
            [
                'post_type'      => 'modifier_group',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => 'is_online',
                'meta_value'     => 1,
                'meta_type'      => 'NUMERIC',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );
        
        $category_modifier_map = [];
        
        foreach ( $modifier_groups as $group ) {
        
            $group_categories = get_post_meta( $group->ID, 'product_categories', true );
        
            if ( empty( $group_categories ) || ! is_array( $group_categories ) ) {
                continue;
            }
        
            foreach ( $group_categories as $category_id ) {
                $category_modifier_map[ (int) $category_id ][] = $group;
            }
        }

        ?>
        <style>
            .orderout-category-grid{
                display:grid;
                grid-template-columns:repeat(auto-fill,minmax(320px,1fr));
                gap:24px;
                margin-top:25px;
            }
            
            .category-card{
                background:#fff;
                border:1px solid #dcdcde;
                border-radius:12px;
                overflow:hidden;
                box-shadow:0 4px 12px rgba(0,0,0,.08);
            }
            
            .category-header{
                display:flex;
                align-items:center;
                gap:12px;
                padding:14px 18px;
                background:#2271b1;
                color:#fff;
            }
            
            .category-header h3{
                margin:0;
                flex:1;
                font-size:17px;
                color:#fff;
            }
            
            .product-count{
                font-size:12px;
                background:rgba(255,255,255,.2);
                padding:3px 8px;
                border-radius:20px;
            }
            
            .product-list{
                max-height:380px;
                overflow-y:auto;
            }
            
            .menu-item-row{
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:10px;
                padding:12px 16px;
                border-bottom:1px solid #f1f1f1;
                transition:.2s;
            }
            
            .menu-item-row:hover{
                background:#f8fbff;
            }
            
            .menu-item-row:last-child{
                border-bottom:none;
            }
            
            .item-left{
                display:flex;
                align-items:center;
                gap:10px;
                flex:1;
            }
            
            .item-name{
                font-size:14px;
                font-weight:500;
            }
            
            .item-price{
                color:#2271b1;
                font-weight:600;
            }
            
            .submit-wrap{
                margin-top:30px;
                text-align:right;
            }
            .orderout-toolbar{
                position:sticky;
                top:32px; /* WordPress admin bar */
                z-index:999;
                background:#fff;
                border:1px solid #ccd0d4;
                border-radius:10px;
                padding:20px;
                margin:20px 0 30px;
                display:flex;
                justify-content:space-between;
                align-items:flex-end;
                gap:25px;
                box-shadow:0 3px 10px rgba(0,0,0,.08);
            }
            
            .toolbar-fields{
                display:flex;
                flex:1;
                gap:20px;
            }
            
            .toolbar-field{
                flex:1;
            }
            
            .toolbar-field label{
                display:block;
                margin-bottom:6px;
                font-weight:600;
            }
            
            .toolbar-field input,
            .toolbar-field textarea{
                width:100%;
            }
            
            .toolbar-description{
                flex:2;
            }
            
            .toolbar-actions{
                display:flex;
                align-items:flex-end;
            }
            
            .toolbar-actions .button{
                min-width:180px;
                height:42px;
                font-size:15px;
            }
            .item-name {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }

            .item-price {
                color: #666;
                font-size: 13px;
                font-weight: 600;
                margin-left: 10px;
                white-space: nowrap;
            }
            .menu-item-row{
                display:flex;
                align-items:flex-start;
                gap:12px;
                padding:12px 16px;
                border-bottom:1px solid #eee;
            }

            .item-left{
                display:flex;
                align-items:flex-start;
                gap:12px;
                width:100%;
            }

            .item-left > input[type="checkbox"]{
                margin-top:4px;
            }

            .item-name{
                flex:1;
                display:flex;
                flex-direction:column;
                gap:6px;
            }

            .item-header{
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:10px;
            }

            .item-title{
                font-weight:600;
                color:#1d2327;
            }

            .item-price{
                color:#2271b1;
                font-weight:600;
                white-space:nowrap;
            }

            .modifier-groups{
                display:flex;
                flex-wrap:wrap;
                gap:8px 15px;
                margin-top:4px;
                padding-left:0;
            }

            .modifier-group-item{
                display:flex;
                align-items:center;
                gap:5px;
                font-size:12px;
                color:#444;
                white-space:nowrap;
            }

            .modifier-group-item input{
                margin:0;
            }
            .modifier-group-block{
                margin-top:8px;
            }

            .modifier-group-item{
                display:block;
                font-weight:600;
            }

            .modifier-list{
                margin:8px 0 0 22px;
                padding-left:12px;
                border-left:2px solid #dcdcde;
            }

            .modifier-item{
                display:block;
                margin:5px 0;
                font-size:13px;
            }

            .modifier-price{
                color:#666;
                margin-left:6px;
                font-size:12px;
            }
            .modifier-list {
                display: none;
                margin-left: 25px;
                margin-top: 8px;
            }

            .modifier-group-block.active .modifier-list {
                display: block;
            }

        </style>
        <div class="wrap">
            <form method="post" id="orderout-menu-form">
                <div class="orderout-toolbar">
    
                    <div class="toolbar-fields">
                
                        <div class="toolbar-field">
                            <label for="menu_name"><strong>Menu Name</strong></label>
                            <input
                            type="text"
                            id="menu_name"
                            name="menu_name"
                            class="regular-text"
                            required
                            value="<?php echo esc_attr( $menu['name'] ?? '' ); ?>"
                            placeholder="Enter menu name" autocomplete="off">
                        </div>
                
                        <div class="toolbar-field toolbar-description">
                            <label for="menu_description"><strong>Menu Description</strong></label>
                            <textarea
                            id="menu_description"
                            name="menu_description"
                            rows="2"
                            autocomplete="off"
                            required
                            placeholder="Enter menu description"><?php echo esc_textarea( $menu['description'] ?? '' ); ?></textarea>
                        </div>
                        <input type="hidden" name="menu_id_from" value="<?php echo $menu_id; ?>">
                
                    </div>
                
                    <div class="toolbar-actions">
                
                        <input
                            type="submit"
                            class="button button-primary button-large"
                            name="pinaka_update_products"
                            value="<?php echo empty( $menu_id ) ? 'Create Menu' : 'Update Menu'; ?>">
                
                    </div>
                
                </div>
        
                <?php wp_nonce_field( 'pinaka_update_products' ); ?>
        
                <div class="orderout-category-grid">

                    <?php foreach ( $category_products as $data ) :
                    
                        $category = $data['category'];
                        $products = $data['products'];
                    
                        $category_name = $category->name;
                    
                        if ( $category->parent ) {
                    
                            $parent = get_term( $category->parent, 'product_cat' );
                    
                            if ( $parent && ! is_wp_error( $parent ) ) {
                                $category_name = $parent->name . ' → ' . $category->name;
                            }
                        }
                        $assigned_groups = [];
                        $added = [];
                        if(isset($category_modifier_map[ $category->term_id ]))
                        {
                            foreach ( $category_modifier_map[ $category->term_id ] as $group ) {

                                if ( isset( $added[ $group->ID ] ) ) {
                                    continue;
                                }

                                $assigned_groups[] = $group;
                                $added[ $group->ID ] = true;
                            }
                        }
                        
                    ?>
        
                        <div class="category-card">
        
                            <div class="category-header">
                        
                                <input
                                    type="checkbox"
                                    class="category-checkbox">
                        
                                <h3><?php echo esc_html( $category_name ); ?></h3>
                            </div>
                        
                            <div class="product-list">
                                
                                <?php foreach ( $products as $product ) :

                                        $wc_product = wc_get_product( $product->ID );
                                    
                                        if ( ! $wc_product ) {
                                            continue;
                                        }
                                    
                                        // Show variations instead of parent product
                                        if ( $wc_product->is_type( 'variable' ) ) :
                                    
                                            foreach ( $wc_product->get_children() as $variation_id ) :
                                    
                                                $variation = wc_get_product( $variation_id );
                                    
                                                if ( ! $variation ) {
                                                    continue;
                                                }
                                                
                                                ?>
                                    
                                                <label class="menu-item-row">
                                    
                                                    <div class="item-left">
                                    
                                                        <input
                                                            type="checkbox"
                                                            class="product-checkbox"
                                                            name="products[]"
                                                            value="<?php echo esc_attr( $variation_id ); ?>"
                                                            <?php checked( in_array( (string) $variation_id, $selected_items, true ) ); ?>>
                                    
                                                        <span class="item-name">
                                                            <?php echo esc_html( $variation->get_name() ); ?>
                                                            <span class="item-price">
                                                                (<?php echo wp_kses_post( wc_price( $variation->get_price() ) ); ?>)
                                                            </span>
                                                            <?php if ( ! empty( $assigned_groups ) ) : ?>

                                                                <div class="modifier-groups">

                                                                    <?php foreach ( $assigned_groups as $group ) : ?>

                                                                        <?php
                                                                        
                                                                        $modifiers = get_posts(
                                                                            [
                                                                                'post_type'      => 'modifiers',
                                                                                'post_status'    => 'publish',
                                                                                'posts_per_page' => -1,
                                                                                'meta_query'     => [
                                                                                    'relation' => 'AND',

                                                                                    [
                                                                                        'key'     => 'modifier_group',
                                                                                        'value'   => 'i:' . (int) $group->ID . ';',
                                                                                        'compare' => 'LIKE',
                                                                                    ],

                                                                                    [
                                                                                        'key'     => 'is_online',
                                                                                        'value'   => 1,
                                                                                        'compare' => '=',
                                                                                    ],
                                                                                ],
                                                                            ]
                                                                        );
                                                                        $groups = $selected_modifier_groups[$variation_id] ?? [];
                                                                        $checked = in_array(
                                                                            (string) $group->ID,
                                                                            $groups,
                                                                            true
                                                                        );
                                                                        if($modifiers):
                                                                        ?>

                                                                        <div class="modifier-group-block">

                                                                            <label class="modifier-group-item">

                                                                                <input
                                                                                    type="checkbox"
                                                                                    class="modifier-group-checkbox"
                                                                                    name="modifier_groups[<?php echo esc_attr( $variation_id ); ?>][]"
                                                                                    value="<?php echo esc_attr( $group->ID ); ?>"
                                                                                    <?php checked($checked); ?>>

                                                                                <strong><?php echo esc_html( $group->post_title ); ?></strong>

                                                                            </label>

                                                                            <div
                                                                                class="modifier-list"
                                                                            >

                                                                                <?php foreach ( $modifiers as $modifier ) : ?>

                                                                                    <?php
                                                                                    $price = (float) get_post_meta(
                                                                                        $modifier->ID,
                                                                                        'modifier_price',
                                                                                        true
                                                                                    );
                                                                                    $selected = $selected_modifiers[$variation_id][$group->ID] ?? [];
                                                                                    ?>

                                                                                    <label class="modifier-item">

                                                                                        <input
                                                                                            type="checkbox"
                                                                                            name="modifiers[<?php echo esc_attr( $variation_id ); ?>][<?php echo esc_attr( $group->ID ); ?>][]"
                                                                                            value="<?php echo esc_attr( $modifier->ID ); ?>"
                                                                                            <?php checked(
                                                                                                in_array((string) $modifier->ID, $selected, true)
                                                                                            ); ?>>

                                                                                        <?php echo esc_html( $modifier->post_title ); ?>

                                                                                        <span class="modifier-price">
                                                                                            (₹<?php echo number_format( $price, 2 ); ?>)
                                                                                        </span>

                                                                                    </label>

                                                                                <?php endforeach; ?>

                                                                            </div>

                                                                        </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>

                                                                </div>

                                                            <?php endif; ?>
                                                        </span>
                                                        

                                                    </div>
                                    
                                                </label>
                                    
                                    <?php
                                            endforeach;
                                    
                                        else :
                                     
                                    ?>
                                    
                                            <label class="menu-item-row">
                                    
                                                <div class="item-left">
                                    
                                                    <input
                                                        type="checkbox"
                                                        class="product-checkbox"
                                                        name="products[]"
                                                        value="<?php echo esc_attr( $product->ID ); ?>"
                                                        <?php checked( in_array( (string) $product->ID, $selected_items, true ) ); ?>>
                                    
                                                    <span class="item-name">
                                                        <?php echo esc_html( $wc_product->get_name() ); ?>
                                                        <span class="item-price">
                                                            (<?php echo wp_kses_post( wc_price( $wc_product->get_price() ) ); ?>)
                                                        </span>
                                                        <?php if ( ! empty( $assigned_groups ) ) : ?>

                                                                <div class="modifier-groups">

                                                                    <?php foreach ( $assigned_groups as $group ) : ?>

                                                                        <?php
                                                                        $modifiers = get_posts(
                                                                            [
                                                                                'post_type'      => 'modifiers',
                                                                                'post_status'    => 'publish',
                                                                                'posts_per_page' => -1,
                                                                                'meta_query'     => [
                                                                                    'relation' => 'AND',

                                                                                    [
                                                                                        'key'     => 'modifier_group',
                                                                                        'value'   => 'i:' . (int) $group->ID . ';',
                                                                                        'compare' => 'LIKE',
                                                                                    ],

                                                                                    [
                                                                                        'key'     => 'is_online',
                                                                                        'value'   => 1,
                                                                                        'compare' => '=',
                                                                                    ],
                                                                                ],
                                                                            ]
                                                                        );
                                                                        $groups = $selected_modifier_groups[$product->ID] ?? [];
                                                                        $checked = in_array(
                                                                            (string) $group->ID,
                                                                            $groups,
                                                                            true
                                                                        );
                                                                        if($modifiers):
                                                                        ?>

                                                                        <div class="modifier-group-block">

                                                                            <label class="modifier-group-item">

                                                                                <input
                                                                                    type="checkbox"
                                                                                    class="modifier-group-checkbox"
                                                                                    name="modifier_groups[<?php echo esc_attr( $product->ID ); ?>][]"
                                                                                    value="<?php echo esc_attr( $group->ID ); ?>"
                                                                                    <?php checked(
                                                                                        $checked
                                                                                    ); ?>>

                                                                                <strong><?php echo esc_html( $group->post_title ); ?></strong>

                                                                            </label>

                                                                            <div
                                                                                class="modifier-list"
                                                                                >

                                                                                <?php foreach ( $modifiers as $modifier ) : ?>

                                                                                    <?php
                                                                                    $price = (float) get_post_meta(
                                                                                        $modifier->ID,
                                                                                        'modifier_price',
                                                                                        true
                                                                                    );
                                                                                    $selected = $selected_modifiers[$product->ID][$group->ID] ?? [];
                                                                                    ?>

                                                                                    <label class="modifier-item">

                                                                                        <input
                                                                                            type="checkbox"
                                                                                            name="modifiers[<?php echo esc_attr( $product->ID ); ?>][<?php echo esc_attr( $group->ID ); ?>][]"
                                                                                            value="<?php echo esc_attr( $modifier->ID ); ?>"
                                                                                            <?php checked(
                                                                                                in_array((string) $modifier->ID, $selected, true)
                                                                                            ); ?>>

                                                                                        <?php echo esc_html( $modifier->post_title ); ?>

                                                                                        <span class="modifier-price">
                                                                                            (₹<?php echo number_format( $price, 2 ); ?>)
                                                                                        </span>

                                                                                    </label>

                                                                                <?php endforeach; ?>

                                                                            </div>

                                                                        </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>

                                                                </div>

                                                            <?php endif; ?>
                                                    </span>

                                                </div>
                                    
                                            </label>
                                    
                                    <?php
                                        endif;
                                    
                                    endforeach;
                                    ?>
                        
                            </div>
                        
                        </div>
        
                    <?php endforeach; ?>
        
                </div>
        
            </form>
        
        </div>
        <script>
        document.getElementById('orderout-menu-form').addEventListener('submit', function(e){
            
            const menuName = document.getElementById('menu_name').value.trim();
            const menuDescription = document.getElementById('menu_description').value.trim();
        
            if(menuName === ''){
                alert('Please enter Menu Name.');
                document.getElementById('menu_name').focus();
                e.preventDefault();
                return;
            }
        
            if(menuDescription === ''){
                alert('Please enter Menu Description.');
                document.getElementById('menu_description').focus();
                e.preventDefault();
                return;
            }
        
            const checkedProducts = document.querySelectorAll('.product-checkbox:checked');
        
            if(checkedProducts.length === 0){
                alert('Please select at least one product.');
                e.preventDefault();
                return;
            }
        
        });
        document.querySelectorAll('.category-checkbox').forEach(function(category){

            category.addEventListener('change', function(){
        
                const card = this.closest('.category-card');
        
                card.querySelectorAll('.product-checkbox').forEach(function(product){
                    product.checked = category.checked;
                });
        
            });
        
        });
        document.querySelectorAll('.category-card').forEach(function(card) {

            const category = card.querySelector('.category-checkbox');
            const total = card.querySelectorAll('.product-checkbox').length;
            const checked = card.querySelectorAll('.product-checkbox:checked').length;
        
            category.checked = (total > 0 && checked === total);
            category.indeterminate = (checked > 0 && checked < total);
        
        });
        document.querySelectorAll('.product-checkbox').forEach(function(product){
        
            product.addEventListener('change', function(){
        
                const card = this.closest('.category-card');
                const category = card.querySelector('.category-checkbox');
        
                const total = card.querySelectorAll('.product-checkbox').length;
                const checked = card.querySelectorAll('.product-checkbox:checked').length;
        
                category.checked = checked === total;
                category.indeterminate = checked > 0 && checked < total;
        
            });
        
        });
        jQuery(function ($) {

            function toggleModifierList(checkbox) {
                var block = $(checkbox).closest('.modifier-group-block');

                if ($(checkbox).is(':checked')) {
                    block.addClass('active');
                } else {
                    block.removeClass('active');

                    // Optional: Uncheck all modifiers when group is unchecked
                    block.find('.modifier-list input[type="checkbox"]')
                        .prop('checked', false);
                }
            }

            // Initial page load
            $('.modifier-group-checkbox').each(function () {
                toggleModifierList(this);
            });

            // On click
            $(document).on('change', '.modifier-group-checkbox', function () {
                toggleModifierList(this);
            });
            $(document).on('change', '.modifier-group-checkbox', function () {
                $(this)
                    .closest('.modifier-group-block')
                    .find('.modifier-list input[type="checkbox"]')
                    .prop('checked', $(this).is(':checked'));
            });
            // $(document).on('change', '.modifier-list input[type="checkbox"]', function () {

            //     var block = $(this).closest('.modifier-group-block');
            //     var checked = $(this).is(':checked');
            
            //     block.find('.modifier-list input[type="checkbox"]').prop('checked', checked);
            //     block.find('.modifier-group-checkbox').prop('checked', checked);
            // });
            $(document).on('click', '.modifier-list input[type="checkbox"]', function (e) {
                e.preventDefault();
                return false;
            });
        });
        </script>
        <?php
    }
    

    public function build_orderout_menu($products,$modifier_groups,$modifiers,$menu_name,$menu_description) {
        // echo '<pre>';
        // print_r([$modifier_groups,$modifiers]);
        // die;
        $menu = [
            'menus'      => [],
            'categories' => [],
            'items'      => [],
            'modifier_groups'  => [],
        ];
        $menu['name'] = $menu_name;

        $menu['description'] = $menu_description;
        /**
         * Categories
         */
        // $categories = get_terms(
        //     [
        //         'taxonomy'   => 'product_cat',
        //         'hide_empty' => true,
        //         // 'include'    => array_map('intval', $categories),
        //     ]
        // );
    
        $category_map = [];
    
        $product_ids = array_map('intval', $products);
        
        $processed_groups    = [];
        $processed_modifiers = [];

        foreach ( $modifier_groups as $product_id => $group_ids ) {

            foreach ( $group_ids as $group_id ) {

                if ( isset( $processed_groups[ $group_id ] ) ) {
                    continue;
                }

                $processed_groups[ $group_id ] = true;

                $group = get_post( $group_id );

                if ( ! $group ) {
                    continue;
                }

                $modifier_options = [];

                $group_modifiers = $modifiers[ $product_id ][ $group_id ] ?? [];

                foreach ( $group_modifiers as $modifier_id ) {

                    $modifier = get_post( $modifier_id );

                    if ( ! $modifier ) {
                        continue;
                    }

                    $price = (float) get_post_meta(
                        $modifier_id,
                        'modifier_price',
                        true
                    );

                    if ( ! isset( $processed_modifiers[ $modifier_id ] ) ) {

                        $processed_modifiers[ $modifier_id ] = true;

                        $menu['items'][] = [
                            'id' => 'modifier-' . $modifier_id,

                            'title' => [
                                'translations' => [
                                    'en_us' => $modifier->post_title,
                                ],
                            ],

                            'description' => [
                                'translations' => [
                                    'en_us' => '',
                                ],
                            ],

                            'quantity_info' => new stdClass(),

                            'external_data' => (string) $modifier_id,

                            'price_info' => [
                                'price' => (int) ( $price * 100 ),
                            ],
                        ];
                    }

                    $modifier_options[] = [
                        'type' => 'ITEM',
                        'id'   => 'modifier-' . $modifier_id,
                    ];
                }

                $menu['modifier_groups'][] = [
                    'id' => 'group-' . $group_id,

                    'title' => [
                        'translations' => [
                            'en_us' => $group->post_title,
                        ],
                    ],

                    'external_data' => (string) $group_id,

                    'quantity_info' => [
                        'minimum_quantity' => 0,
                        'maximum_quantity' => count( $modifier_options ),
                    ],

                    'modifier_options' => $modifier_options,
                ];
            }
        }
        foreach ( $product_ids as $product_id ) {
    
            $product = wc_get_product( $product_id );
    
            if ( ! $product ) {
                continue;
            }
            $category_product_id = $product->is_type( 'variation' )
            ? $product->get_parent_id()
            : $product->get_id();

            $image_url = '';

            if ( $product->get_image_id() ) {
                $image_url = wp_get_attachment_image_url(
                    $product->get_image_id(),
                    'full'
                );
            }
            $tax_class = $product->get_tax_class();
            $tax_rates = WC_Tax::get_rates($tax_class);
            
            $total_tax_rate = 0;
            
            foreach ($tax_rates as $tax_rate_data) {
                $total_tax_rate += (float) $tax_rate_data['rate'];
            }
            $product_modifier_groups = [];

            if ( ! empty( $modifier_groups[ $product->get_id() ] ) ) {

                foreach ( $modifier_groups[ $product->get_id() ] as $group_id ) {
                    $product_modifier_groups[] = 'group-' . $group_id;
                }
            }

            $menu['items'][] = [
    
                'id' => (string) $product->get_id(),

                'title' => [
                    'translations' => [
                        'en_us' => $product->get_name(),
                    ],
                ],
    
                'description' => [
                    'translations' => [
                        'en_us' => wp_strip_all_tags(
                            $product->get_description()
                        ),
                    ],
                ],
                'image_url' => $image_url,

                'quantity_info' => new stdClass(),
    
                'external_data' => (string)$product->get_id(),
    
                'price_info' => [
                    'price' => (int) (
                        floatval( $product->get_price() ) * 100
                    ),
                ],
                'modifier_group_ids' => [
                    'ids' => $product_modifier_groups,
                ],
                'tax_info' => [
                    'tax_rate' => $total_tax_rate,
                    'vat_rate_percentage' => 0,
                ],
            ];
    
            $product_categories = wp_get_post_terms(
                $category_product_id,
                'product_cat',
                [
                    'fields' => 'all',
                ]
            );

            foreach ( $product_categories as $product_category ) {

                if ( ! isset( $category_map[ $product_category->term_id ] ) ) {
            
                    $category_map[ $product_category->term_id ] = [
                        'id' => (string) $product_category->term_id,
            
                        'title' => [
                            'translations' => [
                                'en_us' => $product_category->name,
                            ],
                        ],
            
                        'entities' => [],
                    ];
                }
            
                $category_map[ $product_category->term_id ]['entities'][] = [
                    'type' => 'ITEM',
                    'id'   => (string) $product->get_id(),
                ];
            }
                
        }
        
        /**
         * Categories
         */
        $menu['categories'] = array_values(
            $category_map
        );
    
        /**
         * Menu
         */

        $menu['menus'][] = [
    
            'id' => 'main-menu',

            'title' => [
                'translations' => [
                    'en_us' => $menu_name,
                ],
            ],
        
            'service_availability' => [
    
                [
                    'day_of_week' => 'monday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'tuesday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'wednesday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'thursday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'friday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'saturday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
                [
                    'day_of_week' => 'sunday',
                    'time_periods' => [
                        [
                            'start_time' => '00:00',
                            'end_time'   => '23:59',
                        ],
                    ],
                ],
            ],
    
            'category_ids' => array_map(
                'strval',
                array_keys( $category_map )
            ),
        ];

        return $menu;
    }

    public function send_to_orderout( $restaurant_id, $menu_id_from, $menu) {
        
        $api_key = 's11pfICCvVLnNsQujYu60TuAo6OLqyhF';
        if($menu_id_from!='')
        {
            $endpoint = "https://api.orderout.co/api/pos/restaurant/{$restaurant_id}/menu/{$menu_id_from}";
    
            $response = wp_remote_request(
                $endpoint,
                [
                    'method'  => 'PUT',
                    'timeout' => 60,
    
                    'headers' => [
                        'accept'       => 'application/json',
                        'api-key'      => $api_key,
                        'content-type' => 'application/json',
                    ],
    
                    'body' => wp_json_encode( $menu ),
                ]
            );
        }
        else
        {

            $endpoint =
                "https://api.orderout.co/api/pos/restaurant/{$restaurant_id}/menu";
            
            $response = wp_remote_post(
                $endpoint,
                [
                    'timeout' => 60,
        
                    'headers' => [
        
                        'accept'       => 'application/json',
        
                        'api-key'      => $api_key,
        
                        'content-type' => 'application/json',
                    ],
        
                    'body' => wp_json_encode(
                        $menu
                    ),
                ]
            );
        }
        if ( is_wp_error( $response ) ) {
    
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }
    
        $body =
            json_decode(
                wp_remote_retrieve_body(
                    $response
                ),
                true
            );

        if (
            isset( $body['menu']['items'] ) &&
            is_array( $body['menu']['items'] )
        ) {
    
            foreach ( $body['menu']['items'] as $item ) {
    
                if (
                    empty( $item['external_data'] ) ||
                    empty( $item['id'] )
                ) {
                    continue;
                }
                $item_id = sanitize_text_field( $item['external_data'] );
                $orderout_item_id = sanitize_text_field( $item['id'] );
                update_post_meta(
                    $item_id,
                    '_orderout_item_id',
                    $orderout_item_id
                );
                
            }
        
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        return [
            'response'    => $status_code
        ];
    }

    
}