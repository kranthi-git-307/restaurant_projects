<?php

if (!defined('ABSPATH')) exit;

class PinakaOnlineOrder_API
{
    public function connect()
    {

    }

    public function send_order($order)
    {

    }

    public function get_order($id)
    {

    }

    public function check_connection()
    {

        $settings = get_option('orderout_settings');

        $response = wp_remote_get(
            $settings['api_url'] . '/health',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $settings['api_key']
                )
            )
        );

        return $response;

    }
}