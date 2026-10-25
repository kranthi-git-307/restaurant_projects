<?php
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Reservation_List {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        add_action('init', [$this, 'register_reservation_list_post_type']);
        add_action('add_meta_boxes', [$this, 'add_reservation_list_meta_boxes']);
        add_action('save_post', [$this, 'save_reservation_list_meta']);

        // Hooks for custom columns
        add_filter('manage_edit-reservation_list_columns', [$this, 'set_custom_columns']);
        add_action('manage_reservation_list_posts_custom_column', [$this, 'custom_reservation_column_content'], 10, 2);
    }

    public function register_reservation_list_post_type() {
        $labels = [
            'name'               => __('Reservation List', 'pinaka-pos'),
            'singular_name'      => __('Reservation List', 'pinaka-pos'),
            'add_new_item'       => __('Add New Reservation', 'pinaka-pos'),
            'edit_item'          => __('Edit Reservation', 'pinaka-pos'),
            'new_item'           => __('New Reservation', 'pinaka-pos'),
            'view_item'          => __('View Reservation', 'pinaka-pos'),
            'search_items'       => __('Search Reservation', 'pinaka-pos'),
            'not_found_in_trash' => __('No Reservation found in Trash', 'pinaka-pos'),
            'menu_name'          => __('Reservation Lists', 'pinaka-pos'),
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __('Restaurant Reservation Lists like Reservation - 1, Reservation - 2, etc.', 'pinaka-pos'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-grid-view',
            'query_var'          => true,
            'rewrite'            => ['slug' => 'reservation_list'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => ['title'],
        ];

        register_post_type('reservation_list', $args);
    }

    public function set_custom_columns($columns) {
        unset($columns['date']);
        $columns['customer_name']     = __('Customer Name', 'pinaka-pos');
        $columns['customer_phone']   = __('Customer Phone', 'pinaka-pos');
        $columns['people_count']      = __('No. of People', 'pinaka-pos');
        $columns['table_no']          = __('Table No', 'pinaka-pos');
        $columns['area']              = __('Area', 'pinaka-pos');
        $columns['status'] =            __('Status', 'pinaka-pos');
        $columns['reservation_date'] = __('Date', 'pinaka-pos');
        $columns['reservation_time'] = __('Time', 'pinaka-pos');
        return $columns;
    }

    public function custom_reservation_column_content($column, $post_id) {
        switch ($column) {
            case 'reservation_id':
            case 'reservation_date':
            case 'reservation_time':
            case 'customer_name':
            case 'customer_phone':
            case 'people_count':
            case 'table_no':
            case 'area':
                echo esc_html(get_post_meta($post_id, '_' . $column, true));
                break;
            case 'status':
            $status = get_post_meta($post_id, '_reservation_status', true);
            echo esc_html($status ?: '—');
            break;
        }
    }

    public function add_reservation_submenu_page() {
        add_submenu_page(
            'pin-pos-dashboard',
            __('Manage Reservation List', 'pin-pos'),
            __('Manage Reservation List', 'pin-pos'),
            'manage_options',
            'pin-pos-reservation_list',
            [$this, 'reservationRender']
        );
    }

    public function reservationRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=reservation_list'));
        exit;
    }

    public function add_reservation_list_meta_boxes() {
        add_meta_box(
            'reservation_list_details',
            __('Reservation List Details', 'pinaka-pos'),
            [$this, 'render_reservation_list_meta_box'],
            'reservation_list',
            'normal',
            'high'
        );
    }

    public function render_reservation_list_meta_box($post) {
        wp_nonce_field('save_reservation_list_meta_box', 'reservation_list_meta_box_nonce');

        // Track current tab
        $current_tab = isset($_POST['pinaka_active_tab']) ? sanitize_text_field($_POST['pinaka_active_tab']) : 'booking';

        // Booking Details
        $customer_name     = get_post_meta($post->ID, '_customer_name', true);
        $people_count      = get_post_meta($post->ID, '_people_count', true);
        $customer_phone    = get_post_meta($post->ID, '_customer_phone', true);
        $priority_category = get_post_meta($post->ID, '_priority_category', true);

        // Slot Availability
        $reservation_date  = get_post_meta($post->ID, '_reservation_date', true);
        $slot_type         = get_post_meta($post->ID, '_slot_type', true);
        $reservation_time  = get_post_meta($post->ID, '_reservation_time', true);

        // Zones
        $selected_zone     = get_post_meta($post->ID, '_zone_name', true);
        $selected_table    = get_post_meta($post->ID, '_table_name', true);

        // Fetch CPTs
        $zones  = get_posts(['post_type' => 'zones', 'posts_per_page' => -1, 'post_status' => 'publish']);
        $tables = get_posts(['post_type' => 'tables', 'posts_per_page' => -1, 'post_status' => 'publish']);

        // Group tables by zone name
        $tables_by_zone = [];
        foreach ($tables as $table) {
            $zone_name = get_post_meta($table->ID, 'zone_name', true);
            if (!isset($tables_by_zone[$zone_name])) {
                $tables_by_zone[$zone_name] = [];
            }
            $tables_by_zone[$zone_name][] = [
                'id'    => $table->ID,
                'title' => $table->post_title,
            ];
        }
        ?>

        <!-- Tabs -->
        <style>
            .pinaka-tab-buttons {
                display: flex;
                margin-bottom: 10px;
            }
            .pinaka-tab-buttons input[type="submit"] {
                margin-right: 10px;
                padding: 10px 16px;
                background-color: #f1f1f1;
                border: 1px solid #ccc;
                cursor: pointer;
            }
            .pinaka-tab-buttons input[type="submit"].active {
                background-color: #fff;
                font-weight: bold;
            }
            .pinaka-tab-content {
                border: 1px solid #ccc;
                background: #fff;
                padding: 15px;
                margin-top: -1px;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .form-group input,
            .form-group select {
                width: 100%;
                padding: 6px 10px;
                font-size: 14px;
            }
        </style>

        <div class="pinaka-tab-buttons">
            <form method="post">
                <?php wp_nonce_field('save_reservation_list_meta_box', 'reservation_list_meta_box_nonce'); ?>
                <input type="hidden" name="pinaka_active_tab" value="booking" />
                <input type="submit" value="Booking Details" class="<?php echo ($current_tab === 'booking') ? 'active' : ''; ?>">
            </form>
            <form method="post">
                <?php wp_nonce_field('save_reservation_list_meta_box', 'reservation_list_meta_box_nonce'); ?>
                <input type="hidden" name="pinaka_active_tab" value="slot" />
                <input type="submit" value="Slot Availability" class="<?php echo ($current_tab === 'slot') ? 'active' : ''; ?>">
            </form>
            <form method="post">
                <?php wp_nonce_field('save_reservation_list_meta_box', 'reservation_list_meta_box_nonce'); ?>
                <input type="hidden" name="pinaka_active_tab" value="zones" />
                <input type="submit" value="Zones" class="<?php echo ($current_tab === 'zones') ? 'active' : ''; ?>">
            </form>
        </div>

        <div class="pinaka-tab-content">
            <?php if ($current_tab === 'booking') : ?>
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" name="customer_name" id="customer_name" value="<?php echo esc_attr($customer_name); ?>" />
                </div>
                <div class="form-group">
                    <label for="people_count">No. of People</label>
                    <input type="number" name="people_count" id="people_count" value="<?php echo esc_attr($people_count); ?>" />
                </div>
                <div class="form-group">
                    <label for="customer_phone">Contact Details</label>
                    <input type="text" name="customer_phone" id="customer_phone" value="<?php echo esc_attr($customer_phone); ?>" />
                </div>
                <div class="form-group">
                    <label for="priority_category">Priority / Category</label>
                    <select name="priority_category" id="priority_category">
                        <option value="">Select</option>
                        <option value="regular" <?php selected($priority_category, 'regular'); ?>>Regular</option>
                        <option value="vip" <?php selected($priority_category, 'vip'); ?>>VIP</option>
                        <option value="celebrity" <?php selected($priority_category, 'celebrity'); ?>>Celebrity</option>
                    </select>
                </div>

                <!-- Save button here -->
                <div class="form-actions" style="margin-top:20px;">
                    <input type="submit" class="button button-primary" value="Save Booking">
                </div>

                <?php elseif ($current_tab === 'slot') : ?>
                <div class="form-group">
                    <label for="reservation_date">Reservation Date</label>
                    <input type="date" name="reservation_date" id="reservation_date" value="<?php echo esc_attr($reservation_date); ?>" />
                </div>

                <?php
                $slots = get_option('pinaka_pos_slots', []);
                $slot_times_by_type = [];
                foreach ($slots as $slot) {
                    $type = strtolower($slot['type']);
                    $time = $slot['time'];
                    if (!isset($slot_times_by_type[$type])) {
                        $slot_times_by_type[$type] = [];
                    }
                    if (!in_array($time, $slot_times_by_type[$type])) {
                        $slot_times_by_type[$type][] = $time;
                    }
                }
                ?>

                <div class="slot-row">
                    <div>
                        <label><strong>Slot Type</strong></label>
                        <select name="slot_type" id="slot_type" required>
                            <option value="">Select Type</option>
                            <?php foreach (array_keys($slot_times_by_type) as $type): ?>
                                <option value="<?= esc_attr($type) ?>" <?php selected($slot_type, $type); ?>>
                                    <?= ucfirst(esc_html($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-top: 15px;">
                        <label><strong>Reservation Time</strong></label>
                        <select name="reservation_time" id="reservation_time" required>
                            <option value="">Select Slot Type First</option>
                        </select>
                    </div>
                </div>

                <script>
                    const slotTimes = <?php echo json_encode($slot_times_by_type); ?>;
                    const slotTypeSelect = document.getElementById("slot_type");
                    const timeSelect = document.getElementById("reservation_time");
                    const selectedTime = "<?php echo esc_js($reservation_time); ?>";

                    slotTypeSelect.addEventListener("change", function () {
                        const selectedType = this.value;
                        timeSelect.innerHTML = '';
                        if (slotTimes[selectedType]) {
                            slotTimes[selectedType].forEach(time => {
                                const option = document.createElement("option");
                                option.value = time;
                                option.textContent = time;
                                if (time === selectedTime) option.selected = true;
                                timeSelect.appendChild(option);
                            });
                        } else {
                            const opt = document.createElement("option");
                            opt.textContent = "No times available";
                            timeSelect.appendChild(opt);
                        }
                    });

                    window.addEventListener('DOMContentLoaded', function () {
                        slotTypeSelect.dispatchEvent(new Event('change'));
                   });
                </script>

                <!-- Save button here -->
                <div class="form-actions" style="margin-top:20px;">
                    <input type="submit" class="button button-primary" value="Save Slots">
                </div>

                <?php elseif ($current_tab === 'zones') : ?>

                <?php
                // Fetch all zones
                $zones = get_posts([
                    'post_type' => 'zones',
                    'numberposts' => -1,
                    'post_status' => 'publish',
                ]);

                // Fetch all tables
                $tables = get_posts([
                    'post_type' => 'tables',
                    'numberposts' => -1,
                    'post_status' => 'publish',
              ]);

                // Group tables by zone_name
                $tables_by_zone = [];
                foreach ($tables as $table) {
                    $zone_id = get_post_meta($table->ID, '_table_zone_id', true);
                    if (!$zone_id) {
                        continue;
                   }

                   if (!isset($tables_by_zone[$zone_id])) {
                        $tables_by_zone[$zone_id] = [];
                    }

                    $tables_by_zone[$zone_id][] = [
                        'id'    => $table->ID,
                        'title' => $table->post_title,
                    ];
                }
    
                ?>

                <div class="form-group">
                    <label for="zone_select"><strong>Zone</strong></label>
                    <select name="zone_name" id="zone_select">
                        <option value="">Select Zone</option>
                        <?php foreach ($zones as $zone): ?>
                        <option value="<?php echo esc_attr($zone->ID); ?>" <?php selected($selected_zone, $zone->ID); ?>>
                            <?php echo esc_html($zone->post_title); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    </div>

                    <div class="form-group">
                        <label for="table_select"><strong>Table</strong></label>
                        <select name="table_name" id="table_select">
                            <option value="">Select Zone First</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="reservation_status"><strong>Reservation Status</strong></label><br>
                        <?php
                            $reservation_status = get_post_meta($post->ID, '_reservation_status', true);
                            $status_options = ['Cancelled', 'Expired', 'Seated'];
                        ?>
                        <select name="reservation_status" id="reservation_status">
                            <option value="">Select status</option>
                            <?php foreach ($status_options as $status_option) : ?>
                            <option value="<?php echo esc_attr($status_option); ?>" <?php selected($reservation_status, $status_option); ?>>
                                <?php echo esc_html($status_option); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                        const tablesByZone = <?php echo json_encode($tables_by_zone); ?>;
                        const zoneSelect = document.getElementById('zone_select');
                        const tableSelect = document.getElementById('table_select');
                        const preSelectedTable = "<?php echo esc_js($selected_table); ?>";

                        function populateTables(zoneId) {
                            tableSelect.innerHTML = '';

                            if (zoneId && tablesByZone.hasOwnProperty(zoneId)) {
                                tablesByZone[zoneId].forEach(table => {
                                    const option = document.createElement('option');
                                    option.value = table.id;
                                    option.textContent = table.title;
                                    if (String(table.id) === String(preSelectedTable)) {
                                        option.selected = true;
                                    }
                                    tableSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.textContent = "No tables available";
                            tableSelect.appendChild(option);
                        }
                    }   

                   // Initial load if zone is pre-selected
                   if (zoneSelect.value) {
                        populateTables(zoneSelect.value);
                    }

                    // When zone changes
                    zoneSelect.addEventListener('change', function () {
                        populateTables(this.value);
                    });
                });
            </script>

            <!--  Save button here -->
            <div class="form-actions" style="margin-top:20px;">
                <input type="submit" class="button button-primary" value="Save Zones">
            </div>

            <?php endif; ?>

        </div>

    <?php
    }

    public function save_reservation_list_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['reservation_list_meta_box_nonce']) || !wp_verify_nonce($_POST['reservation_list_meta_box_nonce'], 'save_reservation_list_meta_box')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (get_post_type($post_id) !== 'reservation_list') return;

        $fields = [
            'reservation_date'   => 'sanitize_text_field',
            'slot_type'          => 'sanitize_text_field',
            'reservation_time'   => 'sanitize_text_field',
            'customer_name'      => 'sanitize_text_field',
            'customer_phone'     => 'sanitize_text_field',
            'priority_category'  => 'sanitize_text_field',
            'reservation_status' => 'sanitize_text_field',
            'people_count'       => 'absint',
        ];

        foreach ($fields as $key => $sanitize_callback) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, '_' . $key, call_user_func($sanitize_callback, $_POST[$key]));
            }
        }

        // Save Zone
        if (!empty($_POST['zone_name'])) {
            $zone_id    = intval($_POST['zone_name']);
            $zone_title = get_the_title($zone_id);

            update_post_meta($post_id, '_zone_name', $zone_id);   // store ID
            update_post_meta($post_id, '_area', $zone_title);     // optional: store title too
        }

        // Save Table
        if (!empty($_POST['table_name'])) {
            $table_id    = intval($_POST['table_name']);
            $table_title = get_the_title($table_id);

            update_post_meta($post_id, '_table_name', $table_id); // store ID
            update_post_meta($post_id, '_table_no', $table_title);// optional: store title too
        }
    }
}
