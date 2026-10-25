<?php

if (!defined('ABSPATH')) {
    exit;
}

class PinakaOnlineOrders_Activator
{

    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        /*
        |--------------------------------------------------------------------------
        | Logs Table
        |--------------------------------------------------------------------------
        */
        $logs_table = $wpdb->prefix . 'orderout_logs';

        $sql = "CREATE TABLE {$logs_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            type VARCHAR(50) NOT NULL,

            request LONGTEXT NULL,

            response LONGTEXT NULL,

            status VARCHAR(30) DEFAULT 'success',

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id)

        ) {$charset_collate};";

        dbDelta($sql);

        /*
        |--------------------------------------------------------------------------
        | Orders Table
        |--------------------------------------------------------------------------
        */

        $orders_table = $wpdb->prefix . 'orderout_orders';

        $sql = "CREATE TABLE {$orders_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            orderout_order_id VARCHAR(100) NOT NULL,

            wc_order_id BIGINT UNSIGNED NULL,

            status VARCHAR(50),

            payload LONGTEXT,

            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            KEY orderout_order_id (orderout_order_id)

        ) {$charset_collate};";

        dbDelta($sql);

        /*
        |--------------------------------------------------------------------------
        | Default Settings
        |--------------------------------------------------------------------------
        */

        if (!get_option('orderout_settings')) {

            add_option('orderout_settings', [

                'api_url'      => '',

                'api_key'      => '',

                'store_id'     => '',

                'mqtt_host'    => '',

                'mqtt_port'    => '1883',

                'debug'        => 0

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | Plugin Version
        |--------------------------------------------------------------------------
        */

        update_option('pinakaonlineorders_plugin_version', PINAKAONLINEORDERS_VERSION);

        /*
        |--------------------------------------------------------------------------
        | Cron Job
        |--------------------------------------------------------------------------
        */

        if (!wp_next_scheduled('pinakaonlineorders_sync_orders')) {

            wp_schedule_event(
                time(),
                'hourly',
                'pinakaonlineorders_sync_orders'
            );

        }

    }

}