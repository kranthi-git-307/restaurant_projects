<?php
/**
 * The admin-modifier functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) exit;
class Pinaka_POS_Modifier {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) 
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('init', [$this, 'register_modifier_post_type']);
        // add_action('admin_menu', [$this, 'add_modifier_submenu_page']);
        add_action('add_meta_boxes', [$this, 'add_modifier_meta_boxes']);
        // add_action('save_post', [$this, 'save_modifier_meta']);
        add_action('save_post', [$this, 'save_modifier_meta'], 10, 3);
        add_action('admin_enqueue_scripts', [$this, 'load_assets']);
        add_filter('manage_modifier_posts_columns', [$this, 'set_custom_columns']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_import_assets']);
        add_action('admin_footer-edit.php', [$this, 'render_inventory_import_modal']);
        add_action('manage_modifier_posts_custom_column', [$this, 'custom_zone_column_content'], 10, 2);
        add_filter('wp_insert_post_data', [$this, 'prevent_duplicate_modifier_title'], 10, 2);
        add_action('admin_notices', [$this, 'maybe_show_duplicate_notice']);
        add_action('admin_enqueue_scripts',[$this, 'live_duplicate_check']);
        add_action('rest_api_init',[$this, 'api_call_check']);
        add_action('wp_ajax_pinaka_get_restaurant_tax_setting', [$this, 'pinaka_get_restaurant_tax_settings']);
        add_action('wp_ajax_pinaka_update_tax', [$this, 'pinaka_update_tax_function']);
    
        $this->register_ajax_handlers();
    }
    public function pinaka_get_restaurant_tax_settings()
    {
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);

        if (!$restaurant_id) {
            wp_send_json_error('Invalid restaurant ID', 400);
        }

        $settings = get_option('pinaka_restaurant_tax_settings', []);
        $taxable = $settings[$restaurant_id] ?? 'no';

        wp_send_json_success([
            'taxable' => $taxable
        ]);
    }
    public function pinaka_update_tax_function()
    {
        $restaurant_id = absint($_POST['restaurant_id'] ?? 0);
        $taxable = sanitize_text_field($_POST['taxable'] ?? '');

        if (!$restaurant_id) {
            wp_send_json_error('Invalid restaurant ID', 400);
        }

        if (!in_array($taxable, ['yes', 'no'], true)) {
            wp_send_json_error('Invalid taxable value', 400);
        }

        $settings = get_option('pinaka_restaurant_tax_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings[$restaurant_id] = $taxable;
        update_option('pinaka_restaurant_tax_settings', $settings);

        wp_send_json_success([
            'restaurant_id' => $restaurant_id,
            'taxable' => $taxable
        ]);
    }

    public function enqueue_import_assets($hook) {
        if ($hook !== 'edit.php' || ($_GET['post_type'] ?? '') !== 'modifier') return;

        wp_enqueue_script('jquery');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }
    public function render_inventory_import_modal()
    {
        if (($_GET['post_type'] ?? '') !== 'modifier') return;

        $restaurants = get_option('pinaka_pos_restaurants', []);
        ?>
        <div id="pinaka-loading" style="display:none;">Updating...</div>

        <div id="pinaka-import-modal" style="display:none;">
            <h3>Restaurant Add-on Tax Setting</h3>

            <label><strong>Select Restaurant:</strong></label><br>
            <select id="import_restaurant_id">
                <option value="">-- Select Restaurant --</option>
                <?php foreach ($restaurants as $rest) : ?>
                    <option value="<?php echo esc_attr($rest['id']); ?>">
                        <?php echo esc_html($rest['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="pinaka-tax-choice" style="margin-top:15px;">
                <label style="display:block;">
                    <input type="radio" name="restaurant_taxable" value="yes">
                    <strong>YES — Add-ons are taxable</strong>
                </label>

                <label style="display:block;margin-top:5px;">
                    <input type="radio" name="restaurant_taxable" value="no">
                    <strong>NO — Add-ons are NOT taxable</strong>
                </label>
            </div>

            <hr>

            <button class="button button-primary" id="pinaka-import-start">
                Save Setting
            </button>
        </div>

        <script>
        jQuery(function($){

            // Add button
            $('.wrap > h1').append(
                '<a href="#TB_inline?width=500&height=300&inlineId=pinaka-import-modal" class="page-title-action thickbox">Tax Setting</a>'
            );

            // Load saved value
            $('#import_restaurant_id').on('change', function(){

                const restaurantId = $(this).val();
                if (!restaurantId) return;

                $.post(ajaxurl, {
                    action: 'pinaka_get_restaurant_tax_setting',
                    restaurant_id: restaurantId
                }, function(res){
                    if (res.success) {
                        $('input[name="restaurant_taxable"]').prop('checked', false);
                        $('input[name="restaurant_taxable"][value="' + res.data.taxable + '"]')
                            .prop('checked', true);
                    }
                });
            });

            // Save
            $('#pinaka-import-start').on('click', function(){

                const restaurantId = $('#import_restaurant_id').val();
                const taxable = $('input[name="restaurant_taxable"]:checked').val();

                if (!restaurantId) {
                    alert('Select a restaurant');
                    return;
                }

                if (!taxable) {
                    alert('Select YES or NO');
                    return;
                }

                $('#pinaka-loading').show();

                const formData = new FormData();
                formData.append('action', 'pinaka_update_tax');
                formData.append('restaurant_id', restaurantId);
                formData.append('taxable', taxable);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res){
                        $('#pinaka-loading').hide();
                        if (res.success) {
                            alert('Saved successfully');
                            location.reload();
                        } else {
                            alert('Save failed');
                        }
                    },
                    error: function(){
                        $('#pinaka-loading').hide();
                        alert('Request failed');
                    }
                });
            });

        });
        </script>
        <?php
    }

    public function api_call_check()
    {
        register_rest_route('pinaka-restaurant-pos/v1', '/check-modifier', [
            'methods'  => 'GET',
            'callback' => function($request) {
                $name    = strtolower(trim(sanitize_text_field($request->get_param('name'))));
                $type    = strtolower(trim(sanitize_text_field($request->get_param('type'))));
                $post_id = intval($request->get_param('post_id'));

                if (!$name || !$type) {
                    return new WP_Error('missing_params', 'Name or type missing', ['status' => 400]);
                }

                // Current post values for same check (edit mode)
                $current_modifier = null;
                if ($post_id) {
                    $current_modifier = [
                        'name' => strtolower(get_the_title($post_id)),
                        'type' => strtolower(get_post_meta($post_id, 'modifier_type', true))
                    ];
                }

                // Query for duplicates in same type, excluding current post
                $query_args = [
                    'post_type'      => 'modifier',
                    'posts_per_page' => -1,
                    'post_status'    => ['publish', 'draft', 'pending'],
                    'fields'         => 'ids',
                    'meta_query'     => [
                        [
                            'key'     => 'modifier_type',
                            'value'   => $type,
                            'compare' => '='
                        ]
                    ],
                    'post__not_in' => $post_id ? [$post_id] : []
                ];

                $query = new WP_Query($query_args);

                $found = ['name' => false];

                foreach ($query->posts as $other_id) {
                    if (strtolower(get_the_title($other_id)) === $name) {
                        $found['name'] = true;
                        break;
                    }
                }

                // Check if same as existing (skip blocking on update with unchanged values)
                $same_as_existing = false;
                if ($current_modifier) {
                    $same_as_existing = (
                        $current_modifier['name'] === $name &&
                        $current_modifier['type'] === $type
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

    public function live_duplicate_check($hook) 
    {
        $screen = get_current_screen();
        if ($screen->post_type !== 'modifier') return;
        global $post;
        $post_id = $post ? $post->ID : 0;
        wp_enqueue_script('modifier-live-check', plugin_dir_url(dirname(dirname(__FILE__))) . 'assests/js/modifier-live-check.js', ['jquery'], '1.0', true);
        wp_localize_script('modifier-live-check', 'modifierCheckData', [
            'rest_url' => rest_url('pinaka-restaurant-pos/v1/check-modifier'),
            'exclude_id' => $post_id,
        ]);
    }
    public function prevent_duplicate_modifier_title($data, $postarr) 
    {
        if ($data['post_type'] !== 'modifier') return $data;

        // Get the new title and type
        $title = strtolower(trim($data['post_title']));
        $type  = strtolower(trim(sanitize_text_field($_POST['modifier_type'] ?? '')));

        // Skip if missing
        if (!$title || !$type) return $data;

        // Check for duplicates
        $existing_posts = get_posts([
            'post_type'      => 'modifier',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'exclude'        => [$postarr['ID'] ?? 0],
        ]);

        foreach ($existing_posts as $existing_id) 
        {
            $existing_title = strtolower(trim(get_the_title($existing_id)));
            $existing_type  = strtolower(trim(get_post_meta($existing_id, 'modifier_type', true)));

            if ($existing_title === $title && $existing_type === $type) {
                // Found duplicate, revert title
                $old_title = get_the_title($postarr['ID']);
                $data['post_title'] = $old_title ? $old_title : $data['post_title'];
                update_option('pinaka_duplicate_modifier_notice', true);
                break;
            }
        }

        return $data;
    }

    public function set_custom_columns($columns) 
    {
        // Rearranged and added custom fields
        unset($columns['date']);
        $columns['title'] = __('Modifiers/Add-ons', 'pinaka-pos');
        $columns['restaurant_id'] = __('Restaurant Name', 'pinaka-pos');
        $columns['modifier_type'] = __('Modifier Type', 'pinaka-pos');
        $columns['modifier_price'] = __('Modifier Price', 'pinaka-pos');
        $columns['date'] = __('Date', 'pinaka-pos');

        return $columns;
    }
    public function custom_zone_column_content($column, $post_id) 
    {
        switch ($column) {
            case 'restaurant_id':
            $restaurants = get_option('pinaka_pos_restaurants', []);
            $restaurant_map = array_column($restaurants, 'name', 'id');
            $restaurant_id = get_post_meta($post_id, 'restaurant_id', true);
            echo esc_html($restaurant_map[$restaurant_id] ?? 'N/A');
            break;
            case 'modifier_type':
            case 'modifier_price':
            echo esc_html(get_post_meta($post_id, $column, true));
            break;
        }
    }
    public function register_modifier_post_type() 
    {
        $labels = array(
            'name'               => __('Modifier', 'pinaka-pos'),
            'singular_name'      => __('Modifier', 'pinaka-pos'),
            'add_new'            => __('Add New Modifier', 'pinaka-pos'),
            'add_new_item'       => __('Add New Modifier', 'pinaka-pos'),
            'edit_item'          => __('Edit Modifier', 'pinaka-pos'),
            'new_item'           => __('New Modifier', 'pinaka-pos'),
            'view_item'          => __('View Modifier', 'pinaka-pos'),
            'search_items'       => __('Search modifier', 'pinaka-pos'),
            'not_found'          => __('No Modifier found', 'pinaka-pos'),
            'not_found_in_trash' => __('No Modifier found in Trash', 'pinaka-pos'),
            'menu_name'          => __('Modifier', 'pinaka-pos'),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Restaurant Modifier like Extra Gravy, Extra Spicy, etc.', 'pinaka-pos'),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'modifier'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => ['title'],
            'show_in_rest'       => true
        );

        register_post_type('modifier', $args);
    }

    private function define_admin_hooks() {
        $this->loader->add_action('init', $this, 'register_modifier_post_type');
        $this->loader->add_action('admin_menu', $this, 'add_modifier_submenu_page');
    }

     public function add_modifier_submenu_page() {
        add_submenu_page(
            'pinaka-pos-dashboard',
            __('Manage modifier', 'pinaka-pos'),
            __('Manage modifier', 'pinaka-pos'),
            'manage_options',
            'pinaka-pos-modifier',
            [$this, 'modifierRender']
        );
    }
    public function modifierRender() 
    {
        wp_safe_redirect(admin_url('edit.php?post_type=modifier'));
        exit;
    }
    public function add_modifier_meta_boxes() {
        add_meta_box('modifier_details', 'Modifier Settings', [$this, 'render_modifier_meta_box'], 'modifier', 'normal', 'high');
    }

    private function normalize_meta_array($value) {
        if (empty($value)) return [];
        if (is_array($value)) return array_values(array_unique(array_map('intval', $value)));
        if (is_string($value)) {
            if (maybe_unserialize($value) !== $value) {
                $arr = maybe_unserialize($value);
                if (is_array($arr)) return array_values(array_unique(array_map('intval', $arr)));
            }
            if (strpos($value, ',') !== false) {
                return array_values(array_unique(array_map('intval', array_map('trim', explode(',', $value)))));
            }
            return [(int)$value];
        }
        return [(int)$value];
    }

    public function render_modifier_meta_box($post) {
        $post_id = $post->ID;
        $modifier_type = get_post_meta($post_id, 'modifier_type', true);
        $modifier_price = get_post_meta($post_id, 'modifier_price', true);
        $category_ids = $this->normalize_meta_array(get_post_meta($post_id, 'category_ids', true));
        $subcategory_ids = $this->normalize_meta_array(get_post_meta($post_id, 'subcategory_ids', true));
        $child_subcategory_ids = $this->normalize_meta_array(get_post_meta($post_id, 'child_subcategory_ids', true));
        $product_ids = $this->normalize_meta_array(get_post_meta($post_id, 'product_ids', true));
        $selected_restaurant_id = get_post_meta($post_id, 'restaurant_id', true);
        ?>
        <table class="form-table">
            <tr>
                <th>Restaurant Name</th>
                <td>
                    <?php
                    $restaurants = get_option('pinaka_pos_restaurants', []);
                    ?>
                    <select name="restaurant_id" id="restaurant_id" style="width:100%;" autocomplete="off">
                        <option value="">-- Select Restaurant --</option>
                        <?php foreach ($restaurants as $restaurant): ?>
                            <option value="<?php echo esc_attr($restaurant['id']); ?>" <?php selected($selected_restaurant_id, $restaurant['id']); ?>>
                                <?php echo esc_html($restaurant['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Modifier Type</th>
                <td>
                    <select name="modifier_type" id="modifier_type" onchange="show_modifier_price(this.value)" style="<?php echo !empty($modifier_type) ? 'pointer-events: none; background: #eee;' : ''; ?>">
                        <option value="modifier" <?php selected($modifier_type, 'modifier'); ?>>Modifier</option>
                        <option value="add-on" <?php selected($modifier_type, 'add-on'); ?>>Add-on</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Modifier Price</th>
                <td>
                    <input type="number" step="0.01" name="modifier_price" id="modifier_price"
                        value="<?php echo (isset($modifier_price) && !empty($modifier_price)) ? esc_attr($modifier_price) : '0'; ?>"
                        <?php echo (!empty($modifier_type) && $modifier_type === 'add-on') ? '' : 'readonly'; ?>>
                </td>
            </tr>
            <?php foreach([
                ['category_ids', 'Categories', $category_ids],
                ['subcategory_ids', 'Subcategories', $subcategory_ids],
                ['child_subcategory_ids', 'Child Subcategories', $child_subcategory_ids],
                ['product_ids', 'Products', $product_ids]
            ] as [$id, $label, $values]): ?>
                <tr><th><?php echo esc_html($label); ?></th>
                    <td>
                        <select name="<?php echo $id; ?>[]" id="<?php echo $id; ?>" class="select2-ajax" multiple style="width:100%;">
                            <?php foreach ($values as $val):
                                if ($id === 'product_ids') {
                                    $p = wc_get_product($val);
                                    if ($p) echo "<option value='$val' selected>".$p->get_name()."</option>";
                                } else {
                                    $t = get_term($val, 'product_cat');
                                    if ($t) echo "<option value='$val' selected>".$t->name."</option>";
                                }
                            endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <script>
        jQuery(function($){
            function initSelect2(selector, action, extra) {
                $(selector).select2({
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            var data = {term: params.term, action: action};
                            if(extra) $.extend(data, extra());
                            return data;
                        },
                        processResults: function(data) { return {results:data}; }
                    },
                    minimumInputLength:1
                });
            }
            initSelect2('#category_ids','search_categories');
            initSelect2('#subcategory_ids','search_subcategories', function(){ return {parent_ids:$('#category_ids').val()}; });
            initSelect2('#child_subcategory_ids','search_child_subcategories', function(){ return {parent_ids:$('#subcategory_ids').val()}; });
            initSelect2('#product_ids','search_products', function(){ return {category_ids:$('#child_subcategory_ids').val().length ? $('#child_subcategory_ids').val() : $('#subcategory_ids').val()}; });
        });
        function show_modifier_price(val) 
        {
            if(val !== 'add-on')
            {
                document.getElementById("modifier_price").readOnly = true;
            }
            else
            {
                document.getElementById("modifier_price").readOnly = false;
            }
        }
        </script>
        <?php
    }
    public function save_modifier_meta($post_id, $post, $update)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if ($post->post_type !== 'modifier') return;

        $title = strtolower(trim($post->post_title));
        $type = strtolower(trim(sanitize_text_field($_POST['modifier_type'] ?? '')));

        if ($title && $type) {
            $existing_posts = get_posts([
                'post_type' => 'modifier',
                'post_status' => ['publish', 'draft', 'pending'],
                'posts_per_page' => -1,
                'fields' => 'ids',
                'exclude' => [$post_id],
            ]);

            foreach ($existing_posts as $existing_id) {
                $existing_title = strtolower(trim(get_the_title($existing_id)));
                $existing_type  = strtolower(trim(get_post_meta($existing_id, 'modifier_type', true)));

                if ($existing_title === $title && $existing_type === $type) {
                    // Found duplicate
                    update_option('pinaka_duplicate_modifier_notice', true);

                    // Reset title to avoid duplicate slug
                    remove_action('save_post', [$this, 'save_modifier_meta'], 10);
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
                    add_action('save_post', [$this, 'save_modifier_meta'], 10, 3);
                    return;
                }
            }
        }   
        update_post_meta($post_id, 'restaurant_id', sanitize_text_field($_POST['restaurant_id']));
        update_post_meta($post_id, 'modifier_type', $type);
        update_post_meta($post_id, 'modifier_price', floatval($_POST['modifier_price'] ?? 0));
        update_post_meta($post_id, 'category_ids', $this->normalize_meta_array($_POST['category_ids'] ?? []));
        update_post_meta($post_id, 'subcategory_ids', $this->normalize_meta_array($_POST['subcategory_ids'] ?? []));
        update_post_meta($post_id, 'child_subcategory_ids', $this->normalize_meta_array($_POST['child_subcategory_ids'] ?? []));
        update_post_meta($post_id, 'product_ids', $this->normalize_meta_array($_POST['product_ids'] ?? []));
    }
    public function maybe_show_duplicate_notice() 
    {
        if (get_option('pinaka_duplicate_modifier_notice')) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>Notice:</strong> A Modifier/Add-on with this name and type already exists. The title was reverted to avoid duplicates.</p>
            </div>';
            delete_option('pinaka_duplicate_modifier_notice');
        }
    }
    public function load_assets() 
    {
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    }

    public function register_ajax_handlers() 
    {
        add_action('wp_ajax_search_categories', [$this, 'search_categories']);
        add_action('wp_ajax_search_subcategories', [$this, 'search_subcategories']);
        add_action('wp_ajax_search_child_subcategories', [$this, 'search_subcategories']);
        add_action('wp_ajax_search_products', [$this, 'search_products']);
    }

    public function search_categories() {
        $term = sanitize_text_field($_GET['term']);
        $results = [];
        foreach (get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>0,'name__like'=>$term]) as $cat)
        {
            if (strcasecmp($cat->name, 'Uncategorized') === 0) {
                continue;
            }
            $results[] = ['id'=>$cat->term_id,'text'=>$cat->name];
        }
        wp_send_json($results);
    }

    public function search_subcategories() {
        $term = sanitize_text_field($_GET['term']);
        $results = [];
        foreach (array_map('intval', $_GET['parent_ids'] ?? []) as $parent_id)
            foreach (get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>$parent_id,'name__like'=>$term]) as $cat)
                $results[] = ['id'=>$cat->term_id,'text'=>$cat->name];
        wp_send_json($results);
    }

    public function search_products() {
        $term = sanitize_text_field($_GET['term']);
        $category_ids = array_map('intval', $_GET['category_ids'] ?? []);
        $query = new WP_Query([
            'post_type'=>'product','s'=>$term,'posts_per_page'=>20,
            'tax_query'=>!empty($category_ids)?[['taxonomy'=>'product_cat','field'=>'term_id','terms'=>$category_ids,'include_children'=>true]]:[]
        ]);
        $results = [];
        foreach ($query->posts as $p)
            $results[] = ['id'=>$p->ID,'text'=>$p->post_title];
        wp_send_json($results);
    }
}
?>
