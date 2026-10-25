<?php
/**
 * The admin-zone functionality of the plugin.
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

class Pinaka_POS_Zones {

    private $plugin_name;
    private $version;
    private $loader;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = new Pinaka_POS_Loader();
        $this->define_admin_hooks();
        add_action('add_meta_boxes', [$this, 'add_zone_meta_boxes']);
        // add_action('save_post', [$this, 'save_zone_meta']);
        add_action('save_post', [$this, 'save_zone_meta'], 10, 3);

        add_filter('manage_zones_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_zones_posts_custom_column', [$this, 'custom_zone_column_content'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'prevent_duplicate_zone_title'], 10, 2);
        add_action('admin_notices', [$this, 'maybe_show_zone_duplicate_notice']);
        add_action('admin_enqueue_scripts',[$this, 'live_duplicate_zone_check']);
        add_action('rest_api_init',[$this, 'register_zone_rest_check']);
        
    }
    public function register_zone_rest_check()
    {
        register_rest_route('pinaka-restaurant-pos/v1', '/check-zone', [
            'methods' => 'GET',
            'callback' => function($request) {
                $name          = strtolower(trim(sanitize_text_field($request->get_param('name'))));
                $restaurant_id = intval($request->get_param('restaurant_id'));
                $post_id       = intval($request->get_param('post_id'));

                if (!$name || !$restaurant_id) {
                    return new WP_Error('missing_params', 'Missing name or restaurant_id', ['status' => 400]);
                }

                // Current zone details for edit check
                $current_zone = null;
                if ($post_id) {
                    $current_zone = [
                        'name' => strtolower(trim(get_the_title($post_id))),
                        'restaurant_id' => intval(get_post_meta($post_id, '_restaurant_id', true))
                    ];
                }

                // Query for duplicates in the same restaurant, excluding current
                $query = new WP_Query([
                    'post_type'      => 'zones',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => [
                        [
                            'key'     => '_restaurant_id',
                            'value'   => $restaurant_id,
                            'compare' => '='
                        ]
                    ],
                    'post__not_in' => $post_id ? [$post_id] : []
                ]);

                $found = ['name' => false];

                foreach ($query->posts as $zone_id) {
                    $existing_name = strtolower(trim(get_the_title($zone_id)));
                    if ($existing_name === $name) {
                        $found['name'] = true;
                        break;
                    }
                }

                // Check if same as existing (allow editing without false error)
                $same_as_existing = false;
                if ($current_zone) {
                    $same_as_existing = (
                        $current_zone['name'] === $name &&
                        $current_zone['restaurant_id'] === $restaurant_id
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

    public function live_duplicate_zone_check($hook) 
    {
        $screen = get_current_screen();
        if ($screen->post_type !== 'zones') return;
        global $post;
        $post_id = $post ? $post->ID : 0;
        wp_enqueue_script('zones-live-check', plugin_dir_url(dirname(dirname(__FILE__))) . 'assests/js/zones-live-check.js', ['jquery'], '1.0', true);
        wp_localize_script('zones-live-check', 'zoneCheckData', [
            'rest_url' => rest_url('pinaka-restaurant-pos/v1/check-zone'),
            'exclude_id' => $post_id,
        ]);
    }

    public function maybe_show_zone_duplicate_notice() 
    {
        if (get_option('pinaka_duplicate_zone_notice')) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>Notice:</strong> A zone with this name already exists for the selected restaurant. Please use a unique name.</p>
            </div>';
            delete_option('pinaka_duplicate_zone_notice');
        }
    }

    public function register_zone_post_type() {
        $labels = array(
            'name'               => __('Zones', 'pinaka-pos'),
            'singular_name'      => __('Zone', 'pinaka-pos'),
            'add_new'            => __('Add New Zone', 'pinaka-pos'),
            'add_new_item'       => __('Add New Zone', 'pinaka-pos'),
            'edit_item'          => __('Edit Zone', 'pinaka-pos'),
            'new_item'           => __('New Zone', 'pinaka-pos'),
            'view_item'          => __('View Zone', 'pinaka-pos'),
            'search_items'       => __('Search Zones', 'pinaka-pos'),
            'not_found'          => __('No zones found', 'pinaka-pos'),
            'not_found_in_trash' => __('No zones found in Trash', 'pinaka-pos'),
            'menu_name'          => __('Zones', 'pinaka-pos'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Restaurant zones like Garden, First Floor, etc.', 'pinaka-pos'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'zones'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
        );

        register_post_type('zones', $args);
    }
    public function prevent_duplicate_zone_title($data, $postarr) 
    {
        if ($data['post_type'] !== 'zones') return $data;

        $title = strtolower(trim($data['post_title']));
        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);

        if (!$title || !$restaurant_id) return $data;

        $existing_posts = get_posts([
            'post_type'      => 'zones',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'exclude'        => [$postarr['ID'] ?? 0],
            'meta_query'     => [
                [
                    'key'     => '_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '='
                ]
            ],
        ]);

        foreach ($existing_posts as $existing_id) {
            $existing_title = strtolower(trim(get_the_title($existing_id)));
            if ($existing_title === $title) {
                // Duplicate found in same restaurant
                $old_title = get_the_title($postarr['ID']);
                $data['post_title'] = $old_title ? $old_title : $data['post_title'];

                update_option('pinaka_duplicate_zone_notice', true);
                break;
            }
        }

        return $data;
    }

    private function define_admin_hooks() {
        $this->loader->add_action('init', $this, 'register_zone_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_zone_submenu_page');
    }

    public function add_zone_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage Zones', 'pinaka-pos'),
            __('Manage Zones', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-zones',
            [$this, 'zoneRender']
        );
    }

    public function zoneRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=zones'));
        exit;
    }
    public function set_custom_columns($columns) {
        // Rearranged and added custom fields
        unset($columns['date']);
        $columns['title'] = __('Zones', 'pinaka-pos');
        $columns['restaurant_id'] = __('Restaurant Name', 'pinaka-pos');
        $columns['status'] = __('Status', 'pinaka-pos');
        $columns['date'] = __('Date', 'pinaka-pos');

        return $columns;
    }
    public function custom_zone_column_content($column, $post_id) {
        switch ($column) {
            case 'restaurant_id':
            $restaurants = get_option('pinaka_pos_restaurants', []);
            $restaurant_map = array_column($restaurants, 'name', 'id');
            $restaurant_id = get_post_meta($post_id, '_restaurant_id', true);
            echo esc_html($restaurant_map[$restaurant_id] ?? 'N/A');
            break;
            case 'status':
            echo esc_html(get_post_meta($post_id, '_' . $column, true));
            break;
        }
    }
    public function add_zone_meta_boxes() {
        add_meta_box(
            'zone_details',
            __('Zone Details', 'pinaka-pos'),
            [$this, 'render_zone_meta_box'],
            'zones',
            'normal',
            'high'
        );
    }

    public function render_zone_meta_box($post) {
        $meta_fields = [
            'status' => '',
            'restaurant_id' => ''
        ];

        foreach ($meta_fields as $key => $default) {
            $meta_fields[$key] = get_post_meta($post->ID, '_' . $key, true);
        }

        wp_nonce_field('save_zone_meta_box', 'zone_meta_box_nonce');
        ?>
        <?php
        $restaurants = get_option('pinaka_pos_restaurants', []);
        $selected_restaurant_id = isset($meta_fields['restaurant_id']) ? $meta_fields['restaurant_id'] : '';
        ?>
        <p>
            <label for="restaurant_id">Restaurant Name:</label><br>
            <select name="restaurant_id" id="restaurant_id" style="width:100%;" autocomplete="off">
                <option value="">-- Select Restaurant --</option>
                <?php foreach ($restaurants as $restaurant): ?>
                    <option value="<?php echo esc_attr($restaurant['id']); ?>" <?php selected($selected_restaurant_id, $restaurant['id']); ?>>
                        <?php echo esc_html($restaurant['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="status">Status:</label><br>
            <select id="status" name="status" style="width:100%;">
                <option value="active" <?php selected($meta_fields['status'], 'active'); ?>>Active</option>
                <option value="in-active" <?php selected($meta_fields['status'], 'in-active'); ?>>In-Active</option>
            </select>
        </p>
        <?php
    }

    public function save_zone_meta($post_id, $post, $update) 
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['zone_meta_box_nonce']) || !wp_verify_nonce($_POST['zone_meta_box_nonce'], 'save_zone_meta_box')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (get_post_type($post_id) !== 'zones') return;

        $restaurant_id = isset($_POST['restaurant_id']) ? intval($_POST['restaurant_id']) : 0;
        $zone_name = strtolower(trim(get_the_title($post_id)));

        if ($restaurant_id && $zone_name) {
            $existing_zones = get_posts([
                'post_type' => 'zones',
                'post_status' => ['publish', 'draft', 'pending'],
                'posts_per_page' => -1,
                'fields' => 'ids',
                'exclude' => [$post_id],
                'meta_query' => [
                    [
                        'key' => '_restaurant_id',
                        'value' => $restaurant_id,
                        'compare' => '='
                    ]
                ]
            ]);

            foreach ($existing_zones as $existing_id) 
            {
                $existing_name = strtolower(trim(get_the_title($existing_id)));
                if ($existing_name === $zone_name) {
                    update_option('pinaka_duplicate_zone_notice', true);
                    remove_action('save_post', [$this, 'save_zone_meta'],10);
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
                    add_action('save_post', [$this, 'save_zone_meta'],10,3);
                    return;
                }
            }
        }
        $fields = [
            'restaurant_id' => 'intval',
            'status' => 'sanitize_text_field'
        ];
        
        foreach ($fields as $key => $sanitize_callback) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, '_' . $key, call_user_func($sanitize_callback, $_POST[$key]));
            }
        }
    }
}
