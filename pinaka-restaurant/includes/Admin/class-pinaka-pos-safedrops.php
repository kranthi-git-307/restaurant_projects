<?php
/**
 * The admin-safedrops functionality of the plugin.
 *
 * @package    SafeDrops
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Safe_Drops {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('init', [$this, 'register_safedrops_post_type']);
        add_action('add_meta_boxes', [$this, 'add_safedrops_meta_box']);
        add_action('save_post', [$this, 'save_safedrops_meta']);
        add_filter('manage_safedrops_posts_columns', [$this, 'set_custom_columns']);
        add_action('manage_safedrops_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
    }

    public function register_safedrops_post_type() {
        $args = [
            'labels' => [
                'name' => __('Safe Drops' , 'pinaka-pos'),
                'singular_name' => __('Safe Drops', 'pinaka-pos'),
                'add_new' => __('Add New Safe Drops', 'pinaka-pos'),
                'add_new_item' => __('Add New Safe Drops', 'pinaka-pos'),
                'edit_item' => __('Edit Safe Drops', 'pinaka-pos'),
                'new_item' => __('New Safe Drops', 'pinaka-pos'),
                'view_item' => __('View Safe Drops', 'pinaka-pos'),
                'search_items' => __('Search Safe Drops', 'pinaka-pos'),
                'not_found' => __('No SafeDrops Found', 'pinaka-pos'),
                'not_found_in_trash' => __('No SafeDrops Found in Trash', 'pinaka-pos'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-editor-ul',
            'supports' => ['title'],
            'has_archive' => true,
            'show_in_rest' => true,
        ];
        register_post_type('safedrops', $args);
    }

    public function set_custom_columns($columns) {
        $columns['safedrops_data'] = __('Denominations', 'pinaka-pos');
        return $columns;
    }

    public function custom_column_content($column, $post_id) {
        if ($column === 'safedrops_data') {
            $safedrops_json = get_post_meta($post_id, '_safedrops_data', true);

            $total = 0;

            if ($safedrops_json) {
                $safedrops_array = json_decode($safedrops_json, true);
                if ($safedrops_array) {
                    echo '<ul>';
                    foreach ($safedrops_array as $entry) {
                        $line_total = floatval($entry['denom_count'] ?? 0) * floatval($entry['denom'] ?? 0);
                        $total += $line_total;
                        echo '<li>' .
                            esc_html($entry['denom_count']) .'X'. esc_html($entry['denom']) .
                            ' = ' . number_format($line_total, 2) . '</li>';
                    }
                    echo '<li><strong>Total: ' . number_format($total, 2) . '</strong></li>';
                    echo '</ul>';
                } else {
                    echo __('Invalid JSON data', 'pinaka-pos');
                }
            } else {
                echo __('No Denominations', 'pinaka-pos');
            }
        }
    }

    public function add_safedrops_meta_box() {
        add_meta_box(
            'safedrops_details',
            __('Safe Drops Denominations', 'pinaka-pos'),
            [$this, 'render_safedrops_meta_box'],
            'safedrops',
            'normal',
            'high'
        );
    }

    public function render_safedrops_meta_box($post) {
        $safedrops_json = get_post_meta($post->ID, '_safedrops_data', true);
        $safedrops = json_decode($safedrops_json, true) ?: [];
        $safedrop_total = 0;
        ?>
        <table class="widefat" style="width:100%; border:1px solid #ddd;">
            <thead>
                <tr>
                    <th>Denomination</th>
                    <th>Denomination Count</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($safedrops as $entry): ?>
                <tr>
                    <td><input type="number" name="safedrops[denomination][]" value="<?php echo esc_attr($entry['denomination']); ?>" step="0.01"></td>
                    <td><input type="number" name="safedrops[denomination_count][]" value="<?php echo esc_attr($entry['denomination_count']); ?>"></td>

                    <td><?php echo number_format(floatval($entry['denomination'] * $entry['denomination_count'] ?? 0), 2); ?></td>
                </tr>
            <?php 
                $safedrop_total += floatval($entry['denomination'] * $entry['denomination_count'] ?? 0);
            endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <th><?php echo number_format($safedrop_total, 2); ?></th>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="button" onclick="addSafeRow()">Add Row</button>
        <script>
        function addSafeRow() {
            const row = `<tr>
                <td><input type="number" name="safedrops[denomination][]" step="0.01"></td>
                <td><input type="number" name="safedrops[denomination_count][]"></td>
            </tr>`;
            document.querySelector('#safedrops_details table tbody').insertAdjacentHTML('beforeend', row);
        }
        </script>
        <?php
    }

    public function save_safedrops_meta($post_id) {
        if (!isset($_POST['safedrops']) || !is_array($_POST['safedrops'])) {
            return;
        }

        $denominations = $_POST['safedrops']['denomination'] ?? [];
        $denomination_counts = $_POST['safedrops']['denomination_count'] ?? [];

        $cleaned = [];
        $total = 0;

        for ($i = 0; $i < count($denominations); $i++) {
            $denom = floatval($denominations[$i]);
            $count = intval($denomination_counts[$i]);

            if ($denom > 0 && $count > 0) {
                $line_total = $denom * $count;
                $cleaned[] = [
                    'denomination' => $denom,
                    'denomination_count' => $count,
                    'total' => $line_total
                ];
                $total += $line_total;
            }
        }

        update_post_meta($post_id, '_safedrops_data', wp_json_encode($cleaned));
        update_post_meta($post_id, '_safedrops_total', round($total, 2));
    }

}