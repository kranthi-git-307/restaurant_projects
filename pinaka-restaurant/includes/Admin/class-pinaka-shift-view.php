<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Pinaka_Shift_View {

    public function __construct() {

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        add_filter( 'post_row_actions', [ $this, 'add_view_link' ], 10, 2 );

        add_action( 'wp_ajax_pinaka_shift_view', [ $this, 'ajax_shift_view' ] );
    }

    public function enqueue_scripts( $hook ) {

        // enqueue code
        if ( 'edit.php' !== $hook ) {
            return;
        }

        global $typenow;

        if ( 'shifts' !== $typenow ) {
            return;
        }

        wp_enqueue_script( 'thickbox' );
        wp_enqueue_style( 'thickbox' );

        wp_enqueue_script(
            'pinaka-shift-view',
            PINAKA_POS_PLUGIN_URL . 'assests/js/shift-view.js',
            [ 'jquery', 'thickbox' ],
            '1.0',
            true
        );

        wp_localize_script(
            'pinaka-shift-view',
            'pinakaShift',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'pinaka_shift_view' ),
            ]
        );
    }

    public function add_view_link( $actions, $post ) {

        // post_row_actions code
        if ( 'shifts' !== $post->post_type ) {
            return $actions;
        }

        $actions['view'] =
            '<a href="#" class="pinaka-view-shift" data-id="' . esc_attr( $post->ID ) . '">View</a>';

        return $actions;
    }

    public function ajax_shift_view() {

        // your complete ajax code
        check_ajax_referer( 'pinaka_shift_view', 'nonce' );

        $shift_id = absint( $_POST['id'] );

        $post = get_post( $shift_id );

        if ( ! $post ) {
            wp_die( 'Shift not found.' );
        }

        $meta_fields = [
            'shift_date'       => 'Shift Date',
            'shift_start_time' => 'Start Time',
            'shift_end_time'   => 'End Time',
        ];

        foreach ($meta_fields as $field => $label) {
            $value = get_post_meta($post->ID, '_' . $field, true);
            ?>
            <p>
                <strong><?php echo esc_html($label); ?>:</strong><br>
                <?php echo esc_html($value ?: '-'); ?>
            </p>
            <?php
        }
        $assigned_staff = get_post_meta($post->ID, '_shift_start_employee', true);
        $user = get_userdata($assigned_staff);
        ?>

        <p>
            <strong>Employee:</strong><br>
            <?php echo esc_html($user ? $user->display_name : '-'); ?>
        </p>
        <?php
        $shift_status = get_post_meta($post->ID, '_shift_status', true);
        $start_notes = get_post_meta($post->ID, '_shift_start_notes', true);
            $closing_notes = get_post_meta($post->ID, '_shift_closing_notes', true);
            $total_sales = get_post_meta($post->ID, '_shift_total_sales', true);
            $safe_drop_total = get_post_meta($post->ID, '_shift_safe_drop_total', true);
            $opening_balance = get_post_meta($post->ID, '_shift_drawer_total', true);
            $closing_balance = get_post_meta($post->ID, '_shift_closing_balance', true);
            $shift_notes = get_post_meta($post->ID, '_shift_notes', true);
            $cash_count_details = get_post_meta($post->ID, '_shift_cash_count_details', true);
        
            $drawer_opening_denominations = get_post_meta($post->ID, '_shift_drawer_denominations', true);
            $drawer = json_decode($drawer_opening_denominations, true) ?: [];
            $tube_opening_denominations = get_post_meta($post->ID, '_shift_tube_denominations', true);

            $tubes = json_decode($tube_opening_denominations, true) ?: [];
            $vendor_payments = $this->get_shift_vendor_payouts_total($post->ID);
        ?>

        <p>
            <strong>Status:</strong><br>
            <?php echo esc_html(ucfirst($shift_status ?: 'Open')); ?>
        </p>
        <p>
            <strong>Total Sales:</strong><br>
            <?php echo wc_price($total_sales ?: 0); ?>
        </p>
        
        <?php
        $saved_managers = get_post_meta($post->ID, '_shift_managers', true);
            if (!is_array($saved_managers)) $saved_managers = [];
            $managers = get_users([
                'role__in' => ['captain', 'manager', 'chef', 'employee'],
                'fields'   => ['ID', 'display_name']
            ]);
        echo '<p><strong>Present Employees:</strong></p>';
            echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;" width="100%">';
            echo '<thead><tr><th width="10%">S.no</th><th width="90%">Name</th></tr></thead>';
            echo '<tbody>';
                    $ssno=0;
        foreach ($saved_managers as $user_id) {
            $user = get_userdata($user_id);

            if ($user) {
                $ssno++;
                echo '<tr>';
                echo '<td>' . esc_html($ssno) . '</td>';
                echo '<td>' . esc_html($user->display_name) . '</td>';
                echo '</tr>';
            }
        }
        echo '</tbody></table>';
        echo '<p><strong>Absent Employees:</strong></p>';
            echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;" width="100%">';
            echo '<thead><tr><th width="10%">S.No</th><th width="90%">Name</th></tr></thead>';
            echo '<tbody>';

        $absent_employees = get_post_meta($post->ID, '_shift_absent_emp', true);
            if (!is_array($absent_employees)) $absent_employees = [];

            $all_users = get_users([
                'role__in' => ['captain', 'manager', 'chef', 'employee'],
                'fields' => ['ID', 'display_name']
            ]);
            $sno=0;
        foreach ($absent_employees as $user_id) {
            $user = get_userdata($user_id);

            if ($user) {
                $sno++;
                echo '<tr>';
                echo '<td>' . esc_html($sno) . '</td>';
                echo '<td>' . esc_html($user->display_name) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        wp_die();
    }

    private function get_shift_vendor_payouts_total( $shift_id ) {

        $payouts = get_posts([
            'post_type'   => 'vendor_payments',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => [
                [
                    'key'   => '_vendor_payment_shift_id',
                    'value' => $shift_id,
                ]
            ],
        ]);

        $total_vendor_payouts = 0;
        $results = [];
        foreach ( $payouts as $payout ) {
            $total_vendor_payouts += floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true));
            $vendor_info = get_post_meta($payout->ID, '_vendor_id', true);
            $results['vendor_payments'][] = [
                'id'        => $payout->ID,
                'amount'    => floatval(get_post_meta($payout->ID, '_vendor_payment_amount', true)),
                'note'      => get_post_meta($payout->ID, '_vendor_payment_note', true),
                'payment_method' => get_post_meta($payout->ID, '_vendor_payment_method', true),
                'time'      => get_post($payout->ID, 'post_date', true)->post_date,
                'vendor_name' => get_post($vendor_info, '_vendor_id', true)->post_title,
                'service_type'	 => get_post_meta($payout->ID, '_vendor_payment_service_type', true),
                'vendor_id' => get_post_meta($payout->ID, '_vendor_id', true),
            ];
            $results['total_vendor_payments'] = $total_vendor_payouts;
        }
        return $results;

    }

}
