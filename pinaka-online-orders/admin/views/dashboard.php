<?php

    if (!defined('ABSPATH')) {
        exit;
    }

    update_option('orderout_restuarant_id', '');
    update_option('orderout_restuarant_api_created_at', '');
    update_option('orderout_restuarant_accountId', '');
    update_option('orderout_restuarant_pointOfSaleId', '');
    update_option('orderout_restuarant_billing', '');

    $options = get_option('orderout_settings', []);
    $orderout_restuarant_id  = get_option('orderout_restuarant_id', '');
    $orderout_restuarant_api_created_at  = get_option('orderout_restuarant_api_created_at', '');
    $orderout_restuarant_accountId  = get_option('orderout_restuarant_accountId', '');
    $orderout_restuarant_pointOfSaleId  = get_option('orderout_restuarant_pointOfSaleId', '');
    $orderout_restuarant_billing  = get_option('orderout_restuarant_billing', '');
    $connected = !empty($orderout_restuarant_id);
?>

<div class="wrap orderout-dashboard">

    <div class="orderout-card">

        <div class="orderout-logo">
            <span class="dashicons dashicons-store"></span>
        </div>

        <h1>OrderOut Integration</h1>

        <p class="description">
            Connect your OrderOut Merchant account to synchronize orders with WooCommerce.
        </p>

        <div class="connection-status">

            <?php if ($connected) : ?>

                <span class="status connected">
                    ● Connected
                </span>

            <?php else : ?>

                <span class="status disconnected">
                    ● Not Connected
                </span>

            <?php endif; ?>

        </div>

        <div class="orderout-actions">

            <?php if ($connected) : ?>

                <button class="button button-secondary" id="check-connection">
                    Check Connection
                </button>

                <button class="button button-link-delete" id="disconnect-orderout">
                    Disconnect
                </button>

            <?php else : ?>

                <button class="button button-primary button-hero" id="connect-orderout">
                    Connect OrderOut
                </button>

            <?php endif; ?>

        </div>

        <div id="orderout-message"></div>

    </div>

</div>