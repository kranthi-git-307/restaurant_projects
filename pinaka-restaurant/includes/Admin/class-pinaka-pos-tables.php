<?php
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_Tables 
{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) 
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('init', [$this, 'register_table_post_type']);
        add_action('add_meta_boxes', [$this, 'add_table_meta_boxes']);
        // add_action('save_post', [$this, 'save_table_meta']);
        add_action('save_post', [$this, 'save_table_meta'], 10, 3);

        // Hooks for custom columns
        add_filter('manage_tables_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_tables_posts_custom_column', [$this, 'custom_table_column_content'], 10, 2);

        add_filter('wp_insert_post_data', [$this, 'prevent_duplicate_table_title'], 10, 2);
        add_action('admin_notices', [$this, 'maybe_show_duplicate_notice']);
        add_action('admin_enqueue_scripts',[$this, 'load_table_assets']);
        add_action('rest_api_init',[$this, 'register_table_rest_check']);
    }
    
    public function register_table_rest_check()
    {
        register_rest_route('pinaka-restaurant-pos/v1', '/check-table', [
            'methods'  => 'GET',
            'callback' => function($request) {
                $name          = strtolower(trim(sanitize_text_field($request->get_param('name'))));
                $zone_id       = intval($request->get_param('zone_id'));
                $restaurant_id = intval($request->get_param('restaurant_id'));
                $post_id       = intval($request->get_param('post_id'));

                if (!$name || !$zone_id || !$restaurant_id) {
                    return new WP_Error('missing_params', 'Missing name, zone, or restaurant', ['status' => 400]);
                }

                // Check current post for editing
                $current_table = null;
                if ($post_id) {
                    $current_table = [
                        'name'          => strtolower(trim(get_the_title($post_id))),
                        'zone_id'       => intval(get_post_meta($post_id, '_zone_id', true)),
                        'restaurant_id' => intval(get_post_meta($post_id, '_restaurant_id', true))
                    ];
                }

                // Query for other tables in same zone + restaurant, excluding current
                $existing_posts = get_posts([
                    'post_type'      => 'tables',
                    'post_status'    => ['publish', 'draft', 'pending'],
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => [
                        'relation' => 'AND',
                        [
                            'key'     => '_zone_id',
                            'value'   => $zone_id,
                            'compare' => '='
                        ],
                        [
                            'key'     => '_restaurant_id',
                            'value'   => $restaurant_id,
                            'compare' => '='
                        ]
                    ],
                    'post__not_in' => $post_id ? [$post_id] : []
                ]);

                $found = ['name' => false];

                foreach ($existing_posts as $existing_id) {
                    $existing_title = strtolower(trim(get_the_title($existing_id)));
                    if ($existing_title === $name) {
                        $found['name'] = true;
                        break;
                    }
                }

                // Determine if editing same
                $same_as_existing = false;
                if ($current_table) {
                    $same_as_existing = (
                        $current_table['name'] === $name &&
                        $current_table['zone_id'] === $zone_id &&
                        $current_table['restaurant_id'] === $restaurant_id
                    );
                }

                return [
                    'same_as_existing' => $same_as_existing,
                    'found_in_others'  => $found
                ];
            },
            'permission_callback' => '__return_true',
        ]);
    }
    public function load_table_assets($hook)
    {
        $screen = get_current_screen();
        if ($screen->post_type !== 'tables') return;
        global $post;
        $post_id = $post ? $post->ID : 0;
        wp_enqueue_script('table-live-check', plugin_dir_url(dirname(dirname(__FILE__))) . 'assests/js/table-live-check.js', ['jquery'], '1.0', true);
        wp_localize_script('table-live-check', 'tableCheckData', [
            'rest_url' => rest_url('pinaka-restaurant-pos/v1/check-table'),
            'exclude_id' => $post_id
        ]);
    }
    public function maybe_show_duplicate_notice() 
    {
        if (get_option('pinaka_duplicate_table_notice')) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>Notice:</strong> A Table with this name already exists in this Zone for this Restaurant. Title reverted to avoid duplicates.</p>
            </div>';
            delete_option('pinaka_duplicate_table_notice');
        }
    }
    public function register_table_post_type() {
        $labels = [
            'name'               => __('Tables', 'pinaka-pos'),
            'singular_name'      => __('Table', 'pinaka-pos'),
            'add_new'            => __('Add New Table', 'pinaka-pos'),
            'add_new_item'       => __('Add New Table', 'pinaka-pos'),
            'edit_item'          => __('Edit Table', 'pinaka-pos'),
            'new_item'           => __('New Table', 'pinaka-pos'),
            'view_item'          => __('View Table', 'pinaka-pos'),
            'search_items'       => __('Search Tables', 'pinaka-pos'),
            'not_found'          => __('No Tables found', 'pinaka-pos'),
            'not_found_in_trash' => __('No Tables found in Trash', 'pinaka-pos'),
            'menu_name'          => __('Tables', 'pinaka-pos'),
        ];

        $args = [
            'labels'             => $labels,
            'description'        => __('Restaurant Tables like Table - 1 , Table - 1 VIP, etc.', 'pinaka-pos'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false, // set to true so it's visible
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-grid-view',
            'query_var'          => true,
            'rewrite'            => ['slug' => 'tables'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => ['title'],
        ];

        register_post_type('tables', $args);
    }
    public function prevent_duplicate_table_title($data, $postarr) 
    {
        if ($data['post_type'] !== 'tables') return $data;

        $title = strtolower(trim($data['post_title']));
        $zone_id = intval($_POST['zone_id'] ?? 0);
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);

        if (!$title || !$zone_id || !$restaurant_id) return $data;

        $existing_posts = get_posts([
            'post_type'      => 'tables',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'exclude'        => [$postarr['ID'] ?? 0],
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_zone_id',
                    'value'   => $zone_id,
                    'compare' => '='
                ],
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ]
        ]);

        foreach ($existing_posts as $existing_id) {
            $existing_title = strtolower(trim(get_the_title($existing_id)));
            if ($existing_title === $title) {
                $old_title = get_the_title($postarr['ID']);
                $data['post_title'] = $old_title ? $old_title : $data['post_title'];
                update_option('pinaka_duplicate_table_notice', true);
                break;
            }
        }

        return $data;
    }
    public function set_custom_columns($columns) {
        // Rearranged and added custom fields
        unset($columns['date']);
        $columns['title'] = __('Table Name', 'pinaka-pos');
        $columns['restaurant_id'] = __('Restaurant Name', 'pinaka-pos');
        $columns['table_capacity'] = __('Capacity', 'pinaka-pos');
        $columns['table_shape'] = __('Shape', 'pinaka-pos');
        $columns['table_zone_id'] = __('Zone/Area Name', 'pinaka-pos');
        $columns['table_pos_x'] = __('posX', 'pinaka-pos');
        $columns['table_pos_y'] = __('posY', 'pinaka-pos');
        $columns['table_rotation'] = __('Rotation', 'pinaka-pos');
        $columns['table_status'] = __('Status', 'pinaka-pos');
        $columns['date'] = __('Date', 'pinaka-pos');

        return $columns;
    }

    public function custom_table_column_content($column, $post_id) 
    {
        $zones = get_posts([
            'post_type'      => 'zones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids', // more efficient if only ID is needed
        ]);

        $zone_map = [];
        foreach ($zones as $zone_id) {
            $zone_map[$zone_id] = get_the_title($zone_id);
        }
        $value = get_post_meta($post_id, '_' . $column, true);
        switch ($column) 
        {
            case 'table_pos_x':
            case 'table_pos_y':
            case 'table_rotation':
                echo esc_html(sprintf('%.2f', (float) $value)); // 2 decimal places
                break;
            case 'restaurant_id':
                $restaurants = get_option('pinaka_pos_restaurants', []);
                $restaurant_map = array_column($restaurants, 'name', 'id');
                $restaurant_id = get_post_meta($post_id, '_restaurant_id', true);
                echo esc_html($restaurant_map[$restaurant_id] ?? 'N/A');
                break;
            case 'table_zone_id':
                $zone_id = get_post_meta($post_id, '_table_zone_id', true);
                echo esc_html($zone_map[$zone_id] ?? 'N/A');
                break;
            case 'table_capacity':
            case 'table_shape':
            case 'table_status':
                echo esc_html($value); // Output as-is
                break;
        }
    }

    public function add_table_submenu_page() {
        add_submenu_page(
            'pin-pos-dashboard',
            __('Manage Tables', 'pin-pos'),
            __('Manage Tables', 'pin-pos'),
            'manage_options',
            'pin-pos-tables',
            [$this, 'tableRender']
        );
    }
    public function tableRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=tables'));
        exit;
    }
    public function add_table_meta_boxes() {
        add_meta_box(
            'table_details',
            __('Table Details', 'pinaka-pos'),
            [$this, 'render_table_meta_box'],
            'tables',
            'normal',
            'high'
        );
    }

    public function render_table_meta_box($post) 
    {
        $meta_fields = [
            'restaurant_id' => '',
            'table_capacity' => '',
            'table_shape'    => '',
            'table_zone_id'  => '',
            'table_pos_x'    => '',
            'table_pos_y'    => '',
            'table_rotation'    => '',
            'table_status' => ''
        ];

        foreach ($meta_fields as $key => $default) {
            $meta_fields[$key] = get_post_meta($post->ID, '_' . $key, true);
        }

        wp_nonce_field('save_table_meta_box', 'table_meta_box_nonce');
        ?>
        <?php
        $restaurants = get_option('pinaka_pos_restaurants', []);
        $selected_restaurant_id = isset($meta_fields['restaurant_id']) ? $meta_fields['restaurant_id'] : '';
        ?>
        <p>
            <label for="restaurant_id">Restaurant Name:</label><br>
            <select name="restaurant_id" id="restaurant_id" style="width:100%;" autocomplete="off" required>
                <option value="">-- Select Restaurant --</option>
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?php echo esc_attr($restaurant['id']); ?>" <?php selected($selected_restaurant_id, $restaurant['id']); ?>>
                        <?php echo esc_html($restaurant['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="table_capacity">Capacity:</label><br>
            <input type="number" id="table_capacity" name="table_capacity"
                value="<?php echo esc_attr($meta_fields['table_capacity']); ?>"
                style="width:100%;"
                min="1" autocomplete="off" required>
        </p>

        <p>
            <label for="table_shape">Shape:</label><br>
            <input type="text" id="table_shape" name="table_shape"
                value="<?php echo esc_attr($meta_fields['table_shape']); ?>" required
                style="width:100%;"
                autocomplete="off">
        </p>
        <?php
        $selected_zone_id = isset($meta_fields['table_zone_id']) ? $meta_fields['table_zone_id'] : '';

        // Get all published zones
        $zones = get_posts([
            'post_type'      => 'zones',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);
        ?>

        <p>
            <label for="table_zone_id">Zone:</label><br>
            <select name="table_zone_id" id="table_zone_id" style="width:100%;" autocomplete="off" required>
                <option value="">-- Select Zone --</option>
                <?php foreach ($zones as $zone): ?>
                    <option value="<?php echo esc_attr($zone->ID); ?>" <?php selected($selected_zone_id, $zone->ID); ?>>
                        <?php echo esc_html(get_the_title($zone->ID)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="table_pos_x">posX:</label><br>
            <input type="number" id="table_pos_x" name="table_pos_x"
                value="<?php echo esc_attr((float)$meta_fields['table_pos_x']); ?>"
                style="width:100%;"
                min="1" step="0.01" autocomplete="off" required>
        </p>

        <p>
            <label for="table_pos_y">posY:</label><br>
            <input type="number" id="table_pos_y" name="table_pos_y"
                value="<?php echo esc_attr((float)$meta_fields['table_pos_y']); ?>"
                style="width:100%;"
                min="1" step="0.01" autocomplete="off" required>
        </p>

        <p>
            <label for="table_rotation">Rotation:</label><br>
            <input type="number" id="table_rotation" name="table_rotation"
                value="<?php echo esc_attr((float)$meta_fields['table_rotation']); ?>"
                style="width:100%;" step="0.01" autocomplete="off">
        </p>
        <?php
        $statuses = ['Available', 'Dine in', 'Reserve', 'Ready to Pay'];
        $current_status = isset($meta_fields['table_status']) ? $meta_fields['table_status'] : '';
        ?>

        <p>
            <label for="table_status">Status:</label><br>
            <select id="table_status" name="table_status" style="width:100%;">
                <option value=""></option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo esc_attr($status); ?>" <?php selected($current_status, $status); ?>>
                        <?php echo esc_html($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function save_table_meta($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['table_meta_box_nonce']) || !wp_verify_nonce($_POST['table_meta_box_nonce'], 'save_table_meta_box')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (get_post_type($post_id) !== 'tables') return;
        
        $title = strtolower(trim(get_the_title($post_id)));
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);
        $zone_id = intval($_POST['table_zone_id'] ?? 0);

        if ($title && $restaurant_id && $zone_id) {
            $existing_posts = get_posts([
                'post_type'      => 'tables',
                'post_status'    => ['publish', 'draft', 'pending'],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'exclude'        => [$post_id],
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'   => '_restaurant_id',
                        'value' => $restaurant_id,
                        'compare' => '='
                    ],
                    [
                        'key'   => '_table_zone_id',
                        'value' => $zone_id,
                        'compare' => '='
                    ]
                ]
            ]);

            foreach ($existing_posts as $existing_id) {
                $existing_title = strtolower(trim(get_the_title($existing_id)));
                if ($existing_title === $title) {
                    update_option('pinaka_duplicate_table_notice', true);
                    // Found duplicate, forcibly delete this post
                    remove_action('save_post', [$this, 'save_table_meta'], 10);
                    if ($update) {
                        // Existing post, revert title
                        $previous_title = get_post_field('post_title', $post_id);
                        wp_update_post([
                            'ID' => $post_id,
                            'post_title' => $previous_title
                        ]);
                    } else {
                        // New post, set title to empty
                        wp_update_post([
                            'ID' => $post_id,
                            'post_title' => ''
                        ]);
                    }
                    add_action('save_post', [$this, 'save_table_meta'], 10, 3);
                    return;
                }
            }
        }
        $fields = [
            'restaurant_id' => 'intval',
            'table_capacity' => 'intval',
            'table_shape'    => 'sanitize_text_field',
            'table_zone_id'  => 'intval',
            'table_pos_x'    => 'floatval',
            'table_pos_y'    => 'floatval',
            'table_rotation'    => 'floatval',
            'table_status'   => 'sanitize_text_field'
        ];

        foreach ($fields as $key => $sanitize_callback) 
        {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, '_' . $key, call_user_func($sanitize_callback, $_POST[$key]));
            }
        }
    }
}
