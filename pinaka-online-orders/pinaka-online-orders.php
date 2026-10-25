<?php
/*
Plugin Name: Pinaka Online Orders
Description: OrderOut Integration Plugin
Version: 1.0.0
Author: Pinaka
*/

if (!defined('ABSPATH')) {
    exit;
}

define('PINAKAONLINEORDERS_VERSION', '1.0.0');
define('PINAKAONLINEORDERS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PINAKAONLINEORDERS_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-activator.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-deactivator.php';

register_activation_hook(__FILE__, ['PinakaOnlineOrders_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['PinakaOnlineOrders_Deactivator', 'deactivate']);

require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-api.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-webhook.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-mqtt.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-orders.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-logger.php';
// require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'includes/class-pinakaonlineorders-rest.php';


require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/class-pinakaonlineorders-admin.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/class-pinakaonlineorders-modifier-groups.php';
require_once PINAKAONLINEORDERS_PLUGIN_PATH . 'admin/class-pinakaonlineorders-modifiers.php';
new OrderOut_Admin();
new Pinaka_POS_Modifier_Groups();
new Pinaka_POS_Modifiers();