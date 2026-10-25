<?php 
/**
 * The admin-shifts functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Shifts {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $name        The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $loader;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = new Pinaka_POS_Loader();
        $this->define_admin_hooks();

        add_action('add_meta_boxes', [$this, 'add_shift_meta_boxes']);
        add_action('save_post', [$this, 'save_shift_meta'], 10, 3);
        add_filter('manage_shifts_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_shifts_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'prevent_duplicate_shift_title'], 10, 2);
        add_action('admin_notices', [$this, 'maybe_show_shift_duplicate_notice']);
        add_action('admin_enqueue_scripts',[$this, 'live_duplicate_shift_check']);
        add_action('rest_api_init',[$this, 'register_check_shift_rest']);
    }
 
    public function register_check_shift_rest() 
    {
        register_rest_route('pinaka-restaurant-pos/v1', '/check-shift', [
            'methods' => 'GET',
            'callback' => function($request) {
                $name = strtolower(trim(sanitize_text_field($request->get_param('name'))));
                $start = trim(sanitize_text_field($request->get_param('start')));
                $end = trim(sanitize_text_field($request->get_param('end')));
                $shift_date = trim(sanitize_text_field($request->get_param('date')));
                $post_id = intval($request->get_param('post_id'));

                if (empty($shift_date)) {
                    $shift_date = current_time('Y-m-d');
                }

                if (!$name && !$start && !$end && !$shift_date) {
                    return new WP_Error('missing_params', 'Missing parameters', ['status' => 400]);
                }

                // Current post values for same check
                $current_shift = null;
                if ($post_id) {
                    $current_shift = [
                        'name'  => strtolower(get_the_title($post_id)),
                        'start' => get_post_meta($post_id, '_shift_start_time', true),
                        'end'   => get_post_meta($post_id, '_shift_end_time', true),
                        'date'  => get_post_meta($post_id, '_shift_date', true),
                    ];
                }

                // Query for potential duplicates
                $query = new WP_Query([
                    'post_type'      => 'shifts',
                    'posts_per_page' => -1,
                    'post_status'    => ['publish', 'draft', 'pending'],
                    'fields'         => 'ids',
                    'meta_query'     => [
                        ['key' => '_shift_date', 'value' => $shift_date, 'compare' => '=']
                    ],
                    'post__not_in'   => $post_id ? [$post_id] : []
                ]);

                $found = ['name' => false, 'start' => false, 'end' => false];
                foreach ($query->posts as $shift_id) {
                    if ($name && strtolower(get_the_title($shift_id)) === $name) {
                        $found['name'] = true;
                    }
                    if ($start && get_post_meta($shift_id, '_shift_start_time', true) === $start) {
                        $found['start'] = true;
                    }
                    if (!empty($end) && get_post_meta($shift_id, '_shift_end_time', true) === $end) {
                        $found['end'] = true;
                    }
                }

                // Same check for edit
                if ($current_shift) {
                    $same = (
                        $current_shift['date'] === $shift_date &&
                        $current_shift['name'] === $name &&
                        $current_shift['start'] === $start &&
                        $current_shift['end'] === $end
                    );
                    return [
                        'same_as_existing' => $same,
                        'found_in_others'  => $found
                    ];
                }

                return ['found_in_others' => $found, 'same_as_existing' => false];
            },
            'permission_callback' => '__return_true',
        ]);
    }

    public function live_duplicate_shift_check($hook) {
        $screen = get_current_screen();
        if ($screen->post_type !== 'shifts') return;

        wp_enqueue_script('shifts-live-check', plugin_dir_url(dirname(dirname(__FILE__))) . 'assests/js/shifts-live-check.js', ['jquery'], '1.0', true);
        wp_localize_script('shifts-live-check', 'shiftCheckData', [
            'rest_url' => rest_url('pinaka-restaurant-pos/v1/check-shift'),
            'post_id'  => get_the_ID() ?: 0,
        ]);
    }

    public function maybe_show_shift_duplicate_notice() {
        if (get_option('pinaka_duplicate_shift_notice')) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>Notice:</strong> Duplicate shift detected. Please check name, start or end times.</p>
            </div>';
            delete_option('pinaka_duplicate_shift_notice');
        }
    }

    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_shifts_post_type() {
        $labels = array(
            'name'                  => __('Shifts', 'pinaka-pos'),
            'singular_name'         => __('Shift', 'pinaka-pos'),
            'add_new'               => __('Add New Shift', 'pinaka-pos'),
            'add_new_item'          => __('Add New Shift', 'pinaka-pos'),
            'edit_item'             => __('Edit Shift', 'pinaka-pos'),
            'new_item'              => __('New Shift', 'pinaka-pos'),
            'view_item'             => __('View Shift', 'pinaka-pos'),
            'search_items'          => __('Search Shifts', 'pinaka-pos'),
            'not_found'             => __('No shifts found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No shifts found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Shifts', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Shifts custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'shifts'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('shifts', $args);
    }
    public function prevent_duplicate_shift_title($data, $postarr) {
        if ($data['post_type'] !== 'shifts') return $data;

        $title = strtolower(trim($data['post_title']));
        $start = sanitize_text_field($_POST['shift_start_time'] ?? '');
        $end = sanitize_text_field($_POST['shift_end_time'] ?? '');
        $shift_date = sanitize_text_field($_POST['shift_date'] ?? '');
        if (empty($shift_date)) {
            $shift_date = current_time('Y-m-d'); // fallback
        }

        $existing = get_posts([
            'post_type' => 'shifts',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'draft', 'pending'],
            'exclude' => [$postarr['ID'] ?? 0],
            'fields' => 'ids',
            'meta_query' => [
                ['key' => '_shift_date', 'value' => $shift_date, 'compare' => '=']
            ]
        ]);

        foreach ($existing as $id) 
        {
            if ($title && strtolower(get_the_title($id)) === $title) {
                update_option('pinaka_duplicate_shift_notice', true);
                $data['post_title'] = get_the_title($postarr['ID']) ?: '';
                break;
            }
            if ($start && get_post_meta($id, '_shift_start_time', true) === $start) {
                update_option('pinaka_duplicate_shift_notice', true);
                break;
            }
            if (!empty($end) && get_post_meta($id, '_shift_end_time', true) === $end) {
                update_option('pinaka_duplicate_shift_notice', true);
                break;
            }
        }

        return $data;
    }


    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns($columns) {
        unset($columns['date']);
        $columns['title'] = __('Shifts', 'pinaka-pos');
        $columns['shift_date'] = __('Shift Date', 'pinaka-pos');
        $columns['shift_start_time'] = __('Start Time', 'pinaka-pos');
        $columns['shift_total_sales'] = __('Total Sale Amount', 'pinaka-pos');
        // $columns['shift_safe_drop_total'] = __('Safe Drop Total', 'pinaka-pos');
        $columns['shift_start_employee'] = __('Assigned Staff', 'pinaka-pos');
        // $columns['shift_opening_balance'] = __('Opening Bal', 'pinaka-pos');
        // $columns['shift_closing_balance'] = __('Closing Bal ', 'pinaka-pos');
        // $columns['shift_over_short'] = __('Over/Short ', 'pinaka-pos');
        $columns['shift_end_time'] = __('End Time', 'pinaka-pos');
        $columns['shift_start_employee'] = __('Employee', 'pinaka-pos');
        $columns['date'] = __('Date', 'pinaka-pos');
        return $columns;
    }

    /**
     * Render the column values.
     */

    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'shift_start_employee':
            $shift_user_id = get_post_meta($post_id, '_'.$column , true);
            $shift_user = get_userdata($shift_user_id);
            echo $shift_user ? esc_html($shift_user->display_name) : __('N/A', 'pinaka-pos');
            break;
            case 'shift_total_sales':
                echo esc_html(get_post_meta($post_id, '_shift_total_sales', true));
                break;
            case 'shift_safe_drop_total':
                    echo esc_html(get_post_meta($post_id, '_shift_safe_drop_total', true));
                    break;
            case 'shift_opening_balance':
                echo esc_html(get_post_meta($post_id, '_shift_opening_balance', true));
                break;
            case 'shift_closing_balance':
                echo esc_html(get_post_meta($post_id, '_shift_closing_balance', true));
                break;
            case 'shift_over_short':
                echo esc_html(get_post_meta($post_id, '_shift_over_short', true));
                break;
            case 'shift_date':
            case 'shift_start_time':
            case 'shift_end_time':
            echo esc_html(get_post_meta($post_id, '_'.$column , true));
            break;
        }
    }

    /**
     * Define the admin hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $this->loader->add_action('init', $this, 'register_shifts_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_shifts_submenu_page');
    }

    /**
     * Add the shifts submenu page.
     *
     * @since    1.0.0
     * @access   private
     */
    public function add_shifts_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage Shifts', 'pinaka-pos'),
            __('Manage Shifts', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-shiftss',
            [$this, 'shiftsRender']
        );
    }

    /**
     * Render the shifts submenu page.
     *
     * @since    1.0.0
     * @access   public
     */
    public function shiftsRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=shifts'));
        exit;
    }

    /**
     * Add meta boxes to Shift post type
     */
    public function add_shift_meta_boxes() {
        add_meta_box(
            'shift_details',
            'Shift Details',
            [$this, 'render_shift_details_meta_box'],
            'shifts',
            'normal',
            'high'
        );
    }

    public function render_shift_details_meta_box($post)
{
    $meta_fields = [
        'shift_date'       => 'Shift Date',
        'shift_start_time' => 'Start Time',
        'shift_end_time'   => 'End Time',
    ];

    foreach ($meta_fields as $field => $label) {

        $value = get_post_meta($post->ID, '_' . $field, true);
        $type  = 'text';

        if ($field === 'shift_date') {
            $type = 'date';
        }

        if (in_array($field, ['shift_start_time', 'shift_end_time'])) {
            $type = 'time';
        }
        ?>
        <p>
            <label for="<?php echo esc_attr($field); ?>">
                <?php echo esc_html($label); ?>:
            </label><br>

            <input
                type="<?php echo esc_attr($type); ?>"
                id="<?php echo esc_attr($field); ?>"
                name="<?php echo esc_attr($field); ?>"
                value="<?php echo esc_attr($value); ?>"
                style="width:100%;">
        </p>
        <?php
    }

    $assigned_staff = get_post_meta($post->ID, '_shift_start_employee', true);
    $users = get_users([
        'fields' => ['ID', 'display_name']
    ]);
    ?>

    <p>
        <label for="shift_start_employee">Employee:</label><br>

        <select id="shift_start_employee"
                name="shift_start_employee"
                style="width:100%;"
                required>

            <option value="">Select Employee</option>

            <?php foreach ($users as $user) : ?>

                <option value="<?php echo esc_attr($user->ID); ?>"
                    <?php selected($assigned_staff, $user->ID); ?>>

                    <?php echo esc_html($user->display_name); ?>

                </option>

            <?php endforeach; ?>

        </select>
    </p>

    <?php

    $shift_status = get_post_meta($post->ID, '_shift_status', true) ?: 'open';
    ?>

    <p>

        <label for="shift_status">Status:</label><br>

        <select
            name="shift_status"
            id="shift_status"
            style="width:100%;">

            <option value="open" <?php selected($shift_status, 'open'); ?>>
                Open
            </option>

            <option value="closed" <?php selected($shift_status, 'closed'); ?>>
                Closed
            </option>

        </select>

    </p>

    <?php

    $total_sales = get_post_meta($post->ID, '_shift_total_sales', true);
    ?>

    <p>

        <label for="shift_total_sales">Total Sales:</label><br>

        <input
            type="number"
            id="shift_total_sales"
            name="shift_total_sales"
            value="<?php echo esc_attr($total_sales); ?>"
            style="width:100%;">

    </p>

    <?php

    // Shift Employees
    $saved_managers = get_post_meta($post->ID, '_shift_managers', true);

    if (!is_array($saved_managers)) {
        $saved_managers = [];
    }

    $managers = get_users([
        'role__in' => ['captain', 'manager', 'chef', 'employee'],
        'fields'   => ['ID', 'display_name']
    ]);

    echo '<p><strong>Shift Employees:</strong></p>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;" width="100%">';
    echo '<thead><tr><th width="10%">Select</th><th width="90%">Name</th></tr></thead>';
    echo '<tbody>';

    foreach ($managers as $manager) {

        echo '<tr>';

        echo '<td align="center">
                <input type="checkbox"
                       name="shift_managers[]"
                       value="' . esc_attr($manager->ID) . '" ';

        checked(in_array($manager->ID, $saved_managers));

        echo '></td>';

        echo '<td>' . esc_html($manager->display_name) . '</td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Absent Employees

    $absent_employees = get_post_meta($post->ID, '_shift_absent_emp', true);

    if (!is_array($absent_employees)) {
        $absent_employees = [];
    }

    $all_users = get_users([
        'role__in' => ['captain', 'manager', 'chef', 'employee'],
        'fields'   => ['ID', 'display_name']
    ]);

    echo '<p><strong>Absent Employees:</strong></p>';
    echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse:collapse;" width="100%">';
    echo '<thead><tr><th width="10%">Select</th><th width="90%">Name</th></tr></thead>';
    echo '<tbody>';

    foreach ($all_users as $user) {

        echo '<tr>';

        echo '<td align="center">
                <input type="checkbox"
                       name="shift_absent_emp[]"
                       value="' . esc_attr($user->ID) . '" ';

        checked(in_array($user->ID, $absent_employees));

        echo '></td>';

        echo '<td>' . esc_html($user->display_name) . '</td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
    public function save_shift_meta($post_id, $post, $update)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (get_post_type($post_id) !== 'shifts') {
        return;
    }

    $shift_name  = strtolower(trim(get_the_title($post_id)));
    $shift_start = sanitize_text_field($_POST['shift_start_time'] ?? '');
    $shift_end   = sanitize_text_field($_POST['shift_end_time'] ?? '');
    $shift_date  = sanitize_text_field($_POST['shift_date'] ?? '');

    if (empty($shift_date)) {
        $shift_date = current_time('Y-m-d');
    }

    // Check duplicate shifts
    $existing_shifts = get_posts([
        'post_type'      => 'shifts',
        'post_status'    => ['publish', 'draft', 'pending'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'exclude'        => [$post_id],
        'meta_query'     => [
            [
                'key'     => '_shift_date',
                'value'   => $shift_date,
                'compare' => '='
            ]
        ]
    ]);

    foreach ($existing_shifts as $existing_id) {

        if ($shift_name && strtolower(trim(get_the_title($existing_id))) === $shift_name) {
            update_option('pinaka_duplicate_shift_notice', 'Duplicate shift name for today.');
            $this->rollback_shift_title($post_id, $update);
            return;
        }

        if ($shift_start && get_post_meta($existing_id, '_shift_start_time', true) === $shift_start) {
            update_option('pinaka_duplicate_shift_notice', 'Duplicate shift start time for today.');
            $this->rollback_shift_title($post_id, $update);
            return;
        }

        if (!empty($shift_end) && get_post_meta($existing_id, '_shift_end_time', true) === $shift_end) {
            update_option('pinaka_duplicate_shift_notice', 'Duplicate shift end time for today.');
            $this->rollback_shift_title($post_id, $update);
            return;
        }
    }

    // Save basic fields
    $fields = [
        'shift_date'           => 'sanitize_text_field',
        'shift_start_time'     => 'sanitize_text_field',
        'shift_end_time'       => 'sanitize_text_field',
        'shift_start_employee' => 'intval',
        'shift_status'         => 'sanitize_text_field',
    ];

    foreach ($fields as $key => $sanitize_callback) {
        if (isset($_POST[$key])) {
            update_post_meta(
                $post_id,
                '_' . $key,
                call_user_func($sanitize_callback, $_POST[$key])
            );
        }
    }

    // Save Shift Employees
    if (isset($_POST['shift_managers']) && is_array($_POST['shift_managers'])) {
        update_post_meta(
            $post_id,
            '_shift_managers',
            array_map('intval', $_POST['shift_managers'])
        );
    } else {
        delete_post_meta($post_id, '_shift_managers');
    }

    // Save Absent Employees
    if (isset($_POST['shift_absent_emp']) && is_array($_POST['shift_absent_emp'])) {
        update_post_meta(
            $post_id,
            '_shift_absent_emp',
            array_map('intval', $_POST['shift_absent_emp'])
        );
    } else {
        delete_post_meta($post_id, '_shift_absent_emp');
    }

    // Save Total Sales
    if (isset($_POST['shift_total_sales'])) {
        update_post_meta(
            $post_id,
            '_shift_total_sales',
            floatval($_POST['shift_total_sales'])
        );
    }

    // Save Shift Status
    // if (isset($_POST['shift_status'])) {
    //     update_post_meta(
    //         $post_id,
    //         '_shift_status',
    //         sanitize_text_field($_POST['shift_status'])
    //     );
    // }
}

    private function rollback_shift_title($post_id, $update) {
        remove_action('save_post', [$this, 'save_shift_meta'], 10);
        if ($update) {
            $previous_title = get_post_field('post_title', $post_id);
            wp_update_post(['ID' => $post_id, 'post_title' => $previous_title]);
        } else {
            wp_update_post(['ID' => $post_id, 'post_title' => '']);
        }
        add_action('save_post', [$this, 'save_shift_meta'], 10, 3);
    }
    public function get_shift_vendor_payouts_total( $shift_id ) {
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