<?php
/**
 * The admin-combo functionality of the plugin.
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

class Pinaka_POS_Combos {

    private $plugin_name;
    private $version;
    private $loader;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = new Pinaka_POS_Loader();
        // $this->define_admin_hooks();
        //  $this->loader->register_combo_columns_hooks();

        // add_action('add_meta_boxes', [$this, 'add_combo_meta_boxes']);
        // add_action('save_post', [$this, 'save_combo_meta'], 10, 3);
        
        // add_action('woocommerce_before_calculate_totals', [$this, 'adjust_combo_cart_price']);
        // add_filter('manage_combos_posts_columns', [$this, 'set_combo_custom_columns']);
        // add_action('manage_combos_posts_custom_column', [$this, 'custom_combo_column_content'], 10, 2);
    }

    private function define_admin_hooks() {
        $this->loader->add_action('init', $this, 'register_combo_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_combo_submenu_page');
       
    }

    public function register_combo_columns_hooks() {
        add_filter('manage_combos_posts_columns', [$this, 'set_combo_custom_columns']);
        add_action('manage_combos_posts_custom_column', [$this, 'custom_combo_column_content'], 10, 2);
    }

    public function set_combo_custom_columns($columns) {
        unset($columns['date']);
        $columns['title'] = __('Combo Name', 'pinaka-pos');
        $columns['included_items'] = __('Included Products', 'pinaka-pos');
        $columns['combo_price'] = __('Combo Price (₹)', 'pinaka-pos');
        $columns['date'] = __('Date', 'pinaka-pos');
        return $columns;
    }

    public function custom_combo_column_content($column, $post_id) {
        switch ($column) {
            case 'included_items':
                $items = json_decode(get_post_meta($post_id, '_combo_items', true), true);
                if (!empty($items) && is_array($items)) {
                    $names = array_map(function($id) {
                        return get_the_title($id);
                    }, $items);
                    echo esc_html(implode(', ', $names));
                } else {
                    echo '—';
                }
                break;
            case 'combo_price':
                $price = get_post_meta($post_id, '_combo_price', true);
                echo esc_html($price ? '₹'.$price : '—');
                break;
        }
    }

    public function register_combo_post_type() {
        $labels = array(
            'name'               => __('Combos', 'pinaka-pos'),
            'singular_name'      => __('Combo', 'pinaka-pos'),
            'add_new'            => __('Add New Combo', 'pinaka-pos'),
            'add_new_item'       => __('Add New Combo', 'pinaka-pos'),
            'edit_item'          => __('Edit Combo', 'pinaka-pos'),
            'new_item'           => __('New Combo', 'pinaka-pos'),
            'view_item'          => __('View Combo', 'pinaka-pos'),
            'search_items'       => __('Search Combos', 'pinaka-pos'),
            'not_found'          => __('No combos found', 'pinaka-pos'),
            'not_found_in_trash' => __('No combos found in Trash', 'pinaka-pos'),
            'menu_name'          => __('Combos', 'pinaka-pos'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Combo items and their pricing.', 'pinaka-pos'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'combos'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
        );

        register_post_type('combos', $args);
    }

    public function add_combo_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage Combos', 'pinaka-pos'),
            __('Manage Combos', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-combos',
            [$this, 'comboRender']
        );
    }

    public function comboRender() {
        wp_safe_redirect(admin_url('edit.php?post_type=combos'));
        exit;
    }

    public function add_combo_meta_boxes() {
        add_meta_box(
            'combo_details',
            __('Combo Details', 'pinaka-pos'),
            [$this, 'render_combo_meta_box'],
            'combos',
            'normal',
            'high'
        );
    }

    public function render_combo_meta_box($post) {
        $combo_price = get_post_meta($post->ID, '_combo_price', true);
        $combo_items = json_decode(get_post_meta($post->ID, '_combo_items', true), true);
        if (!is_array($combo_items)) $combo_items = [];

        wp_nonce_field('save_combo_meta_box', 'combo_meta_box_nonce');

        // Select2
        wp_enqueue_script('combo-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
        wp_enqueue_style('combo-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

        ?>
        <p>
            <label>Combo Price (₹):</label><br>
            <input type="number" name="combo_price" value="<?php echo esc_attr($combo_price); ?>" style="width:100%;">
        </p>
        <p>
            <label>Included Products:</label><br>
            <select id="combo_products" name="combo_items[]" multiple="multiple" style="width:100%;">
                <?php
                $products = get_posts(['post_type'=>'product','numberposts'=>-1]);
                foreach ($products as $product) {
                    $selected = in_array($product->ID, $combo_items) ? 'selected' : '';
                    echo "<option value='{$product->ID}' {$selected}>{$product->post_title}</option>";
                }
                ?>
            </select>
        </p>
        <script>
        jQuery(function($){
            $('#combo_products').select2();
        });
        </script>
        <?php
    }

    public function save_combo_meta($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!isset($_POST['combo_meta_box_nonce']) || !wp_verify_nonce($_POST['combo_meta_box_nonce'], 'save_combo_meta_box')) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_type !== 'combos') return;

        if (isset($_POST['combo_price'])) {
            update_post_meta($post_id, '_combo_price', floatval($_POST['combo_price']));
        }
        if (isset($_POST['combo_items'])) {
            update_post_meta($post_id, '_combo_items', json_encode(array_map('intval', $_POST['combo_items'])));
        }
    }

    public function adjust_combo_cart_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['combo_id'])) {
                $combo_id = $cart_item['combo_id'];
                $combo_price = get_post_meta($combo_id, '_combo_price', true);
                $cart_item['data']->set_price($combo_price);
            }
        }
    }
}
