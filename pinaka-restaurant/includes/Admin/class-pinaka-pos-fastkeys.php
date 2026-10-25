<?php
/**
 * The admin-fast-keys functionality of the plugin.
 *
 * @package    Fast_Keys
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Fast_Keys {
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
        add_action('init', [$this, 'register_fast_keys_post_type']);
        add_action('add_meta_boxes', [$this, 'add_fast_keys_meta_box']);
        add_action('save_post', [$this, 'save_fast_keys_meta']);
        add_filter('manage_fast_keys_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_fast_keys_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }

    public function register_fast_keys_post_type() {
        $args = [
            'labels' => [
                'name'          => __('Fast Keys', 'pinaka-pos'),
                'singular_name' => __('Fast Key', 'pinaka-pos'),
                'add_new'       => __('Add New Fast Key', 'pinaka-pos'),
                'add_new_item'  => __('Add New Fast Key', 'pinaka-pos'),
                'edit_item'     => __('Edit Fast Key', 'pinaka-pos'),
                'new_item'      => __('New Fast Key', 'pinaka-pos'),
                'view_item'     => __('View Fast Key', 'pinaka-pos'),
                'search_items'  => __('Search Fast Keys', 'pinaka-pos'),
                'not_found'     => __('No Fast Keys Found', 'pinaka-pos'),
                'not_found_in_trash' => __('No Fast Keys Found in Trash', 'pinaka-pos'),
            ],
            'public'        => true, // Ensure it's public
            'show_ui'       => true, // Enable UI in admin
            'show_in_menu'  => true, // Show in admin menu
            'menu_position' => 20, // Adjust position in admin menu
            'menu_icon'     => 'dashicons-editor-ul', // Set a WordPress icon
            'supports'      => ['title'], // Define supported features
            'has_archive'   => true, // Enable archive page
            'show_in_rest'  => true, // Enable for REST API
        ];
        register_post_type('fast_keys', $args);
    }

    public function set_custom_columns($columns) {
        $columns['fast_keys_data'] = __('Fast Keys', 'pinaka-pos');
        return $columns;
    }

    public function custom_column_content($column, $post_id) {
        if ($column === 'fast_keys_data') {
            $fast_keys = get_post_meta($post_id, '_fast_keys_data', true);
            if ($fast_keys) {
                $fast_keys_array = json_decode($fast_keys, true);
                if ($fast_keys_array) {
                    echo '<ul>';
                    foreach ($fast_keys_array as $key) {
                        echo '<li>' . esc_html($key['product_id']) . ' - Order: ' . esc_html($key['sl_number']) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo __('Invalid JSON data', 'pinaka-pos');
                }
            } else {
                echo __('No Fast Keys', 'pinaka-pos');
            }
        }
    }

    public function add_fast_keys_meta_box() {
        add_meta_box(
            'fast_keys_details',
            __('Fast Keys Details', 'pinaka-pos'),
            [$this, 'render_fast_keys_meta_box'],
            'fast_keys',
            'normal',
            'high'
        );
    }
    
    public function render_fast_keys_meta_box($post) {
        $fast_keys_json = get_post_meta($post->ID, '_fast_keys_data', true);
        $fast_keys = json_decode($fast_keys_json, true) ?: [];
        ?>
        <table style="width:100%; border:1px solid #ddd;">
            <tr><th>Product ID</th><th>Sort Order</th></tr>
            <?php foreach ($fast_keys as $key): ?>
            <tr>
                <td><input type="text" name="fast_keys[product_id][]" value="<?php echo esc_attr($key['product_id']); ?>"></td>
                <td><input type="number" name="fast_keys[sl_number][]" value="<?php echo esc_attr($key['sl_number']); ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function save_fast_keys_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    
        if (isset($_POST['fast_keys'])) {
            $fast_keys_data = [];
            $count = count($_POST['fast_keys']['product_id']);
            for ($i = 0; $i < $count; $i++) {
                $fast_keys_data[] = [
                    'product_id'  => sanitize_text_field($_POST['fast_keys']['product_id'][$i]),
                    'sl_number'  => intval($_POST['fast_keys']['sl_number'][$i]),
                ];
            }
            update_post_meta($post_id, '_fast_keys_data', json_encode($fast_keys_data));
        }
    }

}
