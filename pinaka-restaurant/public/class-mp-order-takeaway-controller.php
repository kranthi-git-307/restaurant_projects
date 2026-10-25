<?php
/**
 * REST API Takeaway Order API controller
 */

 if(!defined('ABSPATH')){
    exit;
 }

 /**
  * REST API Takeaway Order controller class.
  */

class Pinaka_Order_Takeaway_Api_Controller{
    protected $namespace = 'pinaka-restaurant-pos/v1';
    protected $rest_base = 'order';

    public function register_routes(){
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/create-takeaway',
            array(
                'methods'  => 'POST',
                'callback' =>  array($this, 'pinaka_create_takeaway_order_rest'),
                'permission_callback' => '__return_true',
            )
        );
    }
    public function pinaka_create_takeaway_order_rest($request){
        $params = $request->get_json_params();

        $customer_name = sanitize_text_field($params['customer_name'] ?? '');
        $order_total = floatval($params['order_total'] ?? 0);
        $status = sanitize_text_field($params['order_status'] ?? '');
        
        if(!$customer_name || !$order_total){
            return new WP_Error('missing_fields', 'Customer name and order total are required.', ['status' => 400]);
        }
        
        //Create WooCommerce order
        $order = wc_create_order([
            'status' => $status,
            'customer_note' => 'Takeaway Order for: '. $customer_name,
        ]);

        if(is_wp_error($order)){
            return new WP_Error('order_failed', 'Order creation failed.', ['status' => 500]);
        }

        //Update wp_wc_orders.type to takeaway_order
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}wc_orders",
            ['type' => 'takeaway_order'],
            ['id' => $order->get_id()]
        );

        return[
            'success' => true,
            'order_id' => $order->get_id(),
            'type' => 'takeaway_order'
        ];
    }
}
