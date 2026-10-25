<?php

    if (!defined('ABSPATH')) {
        exit;
    }

    $options = get_option('orderout_settings', []);
    $orderout_restuarant_id  = get_option('orderout_restuarant_id', '');
    $orderout_restuarant_api_created_at  = get_option('orderout_restuarant_api_created_at', '');
    $orderout_restuarant_accountId  = get_option('orderout_restuarant_accountId', '');
    $orderout_restuarant_pointOfSaleId  = get_option('orderout_restuarant_pointOfSaleId', '');
    $orderout_restuarant_billing  = get_option('orderout_restuarant_billing', '');


    $connected = !empty($orderout_restuarant_id);
?>

<div class="wrap">
    <h1>OrderOut Settings</h1>

    <form>

        <table class="form-table">

            <tr>
                <th>OrderOur Restuarant ID</th>
                <td><input type="text" readonly class="regular-text" value="<?php echo $orderout_restuarant_id;?>"></td>
            </tr>

            <tr>
                <th>Billing ID</th>
                <td><input type="text" readonly class="regular-text" value="<?php echo $orderout_restuarant_billing;?>"></td>
            </tr>

            <tr>
                <th>Point Of Sale ID</th>
                <td><input type="text" readonly class="regular-text" value="<?php echo $orderout_restuarant_pointOfSaleId;?>"></td>
            </tr>

            <tr>
                <th>Point Of Account ID</th>
                <td><input type="text" readonly class="regular-text" value="<?php echo $orderout_restuarant_accountId;?>"></td>
            </tr>

            <tr>
                <th>OrderOut Connected On</th>
                <td><input type="text" readonly class="regular-text" value="<?php echo date('d/M/yyyy', strtotime($orderout_restuarant_accountId));?>"></td>
            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

    <table class="form-table">

        <tr>
            <th>Webhook URL</th>
            <td>
                <input
                    type="text"
                    class="regular-text"
                    readonly
                    value="<?php echo esc_url( rest_url('wp-json/pinaka/v1/orderout-webhook') ); ?>"
                >
            </td>
        </tr>

        <tr>
            <th></th>
            <td>

                <button
                    class="button button-primary"
                    id="connect_webhook">
                    Connect Webhook
                </button>

            </td>
        </tr>

    </table>

    <div id="webhook_progress" style="display:none;margin-top:20px">

        <progress
            style="width:350px"
            id="progress_bar"
            max="100"
            value="0">
        </progress>

        <p id="progress_text">
            Connecting...
        </p>

    </div>

    <div id="webhook_message"></div>


</div>