<?php
/**
 * Custom WooCommerce Order Statuses for Pinaka POS Plugin
 */

if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Order_Statuses {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name = '', $version ='') {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

	    add_action( 'init', array( $this, 'register_custom_statuses' ) );
	    add_filter( 'wc_order_statuses', array( $this, 'add_custom_statuses_to_wc' ) );
        add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_custom_statuses_to_reports' ) );
        add_filter( 'woocommerce_order_list_status_filters', array( $this, 'add_custom_statuses_to_order_list' ) );
        add_filter( 'woocommerce_order_list_table_get_views', array( $this, 'add_custom_order_statuses_to_views' ) ,20  );
    }
    public function register_custom_statuses() {
        $statuses = [
            'wc-yet-to-prepare' => __('Yet To Prepare', 'pinaka-pos'),
            'wc-preparing'     => __('Preparing', 'pinaka-pos'),
            'wc-ready'         => __('Ready', 'pinaka-pos'),
            'wc-served'        => __('Served', 'pinaka-pos'),
            'wc-recalled'      => __('Recalled', 'pinaka-pos'),
        ];

        foreach ($statuses as $key => $label) {
            register_post_status($key, [
                'label'                     => _x($label, 'Order status', 'pinaka-pos'),
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    "$label <span class='count'>(%s)</span>",
                    "$label <span class='count'>(%s)</span>",
                    'pinaka-pos'
                ),
            ]);
        }
    }

    public function add_custom_statuses_to_wc($order_statuses) {
        $new_statuses = [];

        foreach ($order_statuses as $key => $label) {
            $new_statuses[$key] = $label;

            if ($key === 'wc-processing') {

                $new_statuses['wc-yet-to-prepare'] = _x('Yet To Prepare', 'Order status', 'pinaka-pos');
                $new_statuses['wc-preparing']     = _x('Preparing', 'Order status', 'pinaka-pos');
                $new_statuses['wc-ready']         = _x('Ready', 'Order status', 'pinaka-pos');
                $new_statuses['wc-served']        = _x('Served', 'Order status', 'pinaka-pos');
                $new_statuses['wc-recalled']      = _x('Recalled', 'Order status', 'pinaka-pos');
            }
        }

        return $new_statuses;
    }
    public function add_custom_statuses_to_reports($statuses) {
        $custom_statuses = [
            'wc-yet-to-prepare',
            'wc-preparing',
            'wc-ready',
            'wc-served',
            'wc-recalled',
        ];

        return array_merge($statuses, $custom_statuses);
    }

    public function add_custom_statuses_to_order_list($statuses) {

        $statuses['yet-to-prepare'] = __('Yet To Prepare', 'pinaka-pos');
        $statuses['preparing']     = __('Preparing', 'pinaka-pos');
        $statuses['ready']         = __('Ready', 'pinaka-pos');
        $statuses['served']        = __('Served', 'pinaka-pos');
        $statuses['recalled']      = __('Recalled', 'pinaka-pos');
    
        return $statuses;
    }
    
    public function add_custom_order_statuses_to_views($views)
    {
        $custom = [
            'yet-to-prepare' => __('Yet To Prepare', 'pinaka-pos'),
            'preparing'     => __('Preparing', 'pinaka-pos'),
            'ready'         => __('Ready', 'pinaka-pos'),
            'served'        => __('Served', 'pinaka-pos'),
            'recalled'      => __('Recalled', 'pinaka-pos'),
        ];
        
        foreach ( $custom as $slug => $label ) {
            // HPOS-safe count
            $count = function_exists( 'wc_orders_count' ) ? (int) wc_orders_count( $slug ) : 0;
            if ( $count <= 0 ) {
                continue; // Woo shows only statuses with > 0
            }

            $is_current = ( isset( $_GET['status'] ) && $_GET['status'] === $slug );
            $url        = add_query_arg( [ 'page' => 'wc-orders', 'status' => $slug ], admin_url( 'admin.php' ) );

            $views[ 'wc-' . $slug ] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url( $url ),
                $is_current ? ' class="current"' : '',
                esc_html( $label ),
                $count
            );
        }

        return $views;
    }
}