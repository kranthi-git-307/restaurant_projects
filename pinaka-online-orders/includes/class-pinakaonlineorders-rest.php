<?php

if (!defined('ABSPATH')) {
    exit;
}

class PinakaOnlineOrders_REST
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('pinaka/v1', '/save-orderout', [
            'methods'             => 'POST',
            'callback'            => [$this, 'save_orderout'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }

    public function save_orderout(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        echo json_encode($params);
        // $settings = get_option('orderout_settings', []);

        // $settings['connected']    = !empty($params['connected']);
        // $settings['connected_at'] = sanitize_text_field($params['connected_at'] ?? '');
        // $settings['api_response'] = $params['api_response'] ?? [];

        // update_option('orderout_settings', $settings);

        // return rest_ensure_response([
        //     'success' => true,
        //     'message' => 'Settings saved successfully.'
        // ]);
    }
}