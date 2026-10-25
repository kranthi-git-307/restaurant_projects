<?php

if (!defined('ABSPATH')) {
    exit;
}

class PinakaOnlineOrders_Deactivator
{

    public static function deactivate()
    {

        /*
        |--------------------------------------------------------------------------
        | Remove Scheduled Cron
        |--------------------------------------------------------------------------
        */

        $timestamp = wp_next_scheduled('pinakaonlineorders_sync_orders');

        if ($timestamp) {

            wp_unschedule_event(
                $timestamp,
                'pinakaonlineorders_sync_orders'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Flush Rewrite Rules
        |--------------------------------------------------------------------------
        */

        flush_rewrite_rules();

    }

}