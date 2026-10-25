<?php 
/**
 * The admin-discounts functionality of the plugin.
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

class Pinaka_POS_Modifier_Groups {
    public function __construct() {

        add_action( 'init', [ $this, 'register_modifier_groups_post_type' ] );

        add_action( 'add_meta_boxes', [ $this, 'add_modifier_group_meta_boxes' ] );

        add_action( 'save_post_modifier_group', [ $this, 'save_modifier_groups_data' ] );

        add_filter(
            'manage_modifier_group_posts_columns',
            [ $this, 'set_custom_columns' ]
        );

        add_action(
            'manage_modifier_group_posts_custom_column',
            [ $this, 'custom_column_content' ],
            10,
            2
        );
    }

    /**
     * Register the vendor custom post type.
     *
     * @since    1.0.0
     * @access   private
     */
    public function register_modifier_groups_post_type() {
        $labels = array(
            'name'                  => __('Modifier Groups', 'pinaka-pos'),
            'singular_name'         => __('Modifier Group', 'pinaka-pos'),
            'add_new'               => __('Add New Modifier Group', 'pinaka-pos'),
            'add_new_item'          => __('Add New Modifier Group', 'pinaka-pos'),
            'edit_item'             => __('Edit Modifier Group', 'pinaka-pos'),
            'new_item'              => __('New Modifier Group', 'pinaka-pos'),
            'view_item'             => __('View Modifier Group', 'pinaka-pos'),
            'search_items'          => __('Search Modifier Groups', 'pinaka-pos'),
            'not_found'             => __('No Modifier Groups found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No Modifier Groups found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Modifier Groups', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Modifier custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'modifier_group'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('modifier_group', $args);
    }

    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns($columns) {

        $new_columns = [];

        // keep checkbox first
        if ( isset($columns['cb']) ) {
            $new_columns['cb'] = $columns['cb'];
        }

        // title
        $new_columns['title'] = __('Group Name', 'pinaka-pos');
        $new_columns['product_categories'] = __( 'Product Categories', 'pinaka-pos' );
        $new_columns['is_online']  = __( 'Is Available', 'pinaka-pos' );

        // move date to LAST
        if ( isset($columns['date']) ) {
            $new_columns['date'] = $columns['date'];
        }

        return $new_columns;
    }


    /**
     * Render the column values.
     */

    public function custom_column_content( $column, $post_id ) {

        switch ( $column ) {
    
            case 'product_categories':
    
                $category_ids = get_post_meta( $post_id, 'product_categories', true );
    
                if ( ! empty( $category_ids ) && is_array( $category_ids ) ) {
    
                    $category_names = [];
    
                    foreach ( $category_ids as $category_id ) {
    
                        $term = get_term( $category_id, 'product_cat' );
    
                        if ( $term && ! is_wp_error( $term ) ) {
                            $category_names[] = $term->name;
                        }
                    }
    
                    echo esc_html( implode( ', ', $category_names ) );
    
                } else {
    
                    echo '&mdash;';
                }
    
                break;
    
            case 'is_online':
    
                $is_online = get_post_meta( $post_id, 'is_online', true );
    
                echo $is_online ? 'Yes' : 'No';
    
                break;
        }
    }

    /**
     * Add meta boxes to Shift post type
     */
    public function add_modifier_group_meta_boxes() {
        add_meta_box(
            'modifier_group_box',
            __( 'Modifier Group', 'pinaka-pos' ),
            [ $this, 'render_modifier_group_meta_box' ],
            'modifier_group',
            'normal',
            'high'
        );
    }

    /**
     * Render Discount Details Meta Box
     */
    public function render_modifier_group_meta_box( $post ) {

        $is_online = get_post_meta( $post->ID, 'is_online', true );

        $selected_categories = get_post_meta( $post->ID, 'product_categories', true );
        $selected_categories = is_array( $selected_categories ) ? $selected_categories : [];

        $category_products = [];

        $product_ids = get_posts(
            [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]
        );
        
        foreach ( $product_ids as $product_id ) {

            $product_categories = wp_get_post_terms(
                $product_id,
                'product_cat',
                [
                    'fields' => 'all',
                ]
            );

            foreach ( $product_categories as $category ) {

                // Skip unwanted categories
                if ( in_array( $category->name, [ 'Uncategorized', 'Default' ], true ) ) {
                    continue;
                }

                // Prevent duplicates
                if ( ! isset( $category_products[ $category->term_id ] ) ) {
                    $category_products[ $category->term_id ] = $category;
                }
            }
        }

        // Sort categories alphabetically
        uasort(
            $category_products,
            function ( $a, $b ) {
                return strcmp( $a->name, $b->name );
            }
        );

        ?>

        <div class="pinaka-meta-wrapper">

            <div class="pinaka-online-field">

                <label class="pinaka-checkbox">
                    <input
                        type="checkbox"
                        name="is_online"
                        value="1"
                        <?php checked( $is_online, 1 ); ?>>

                    <span>Is Available</span>
                </label>

            </div>

            <div class="pinaka-category-wrapper">

                <h4>Available Categories</h4>

                <div class="pinaka-category-list">

                    <?php foreach ( $category_products as $category ) : ?>

                        <?php
                        $category_name = $category->name;

                        if ( $category->parent ) {
                            $parent = get_term( $category->parent, 'product_cat' );

                            if ( $parent && ! is_wp_error( $parent ) ) {
                                $category_name = $parent->name . ' → ' . $category->name;
                            }
                        }
                        ?>

                        <label class="pinaka-category-item">

                            <input
                                type="checkbox"
                                name="product_categories[]"
                                value="<?php echo esc_attr( $category->term_id ); ?>"
                                <?php checked( in_array( $category->term_id, $selected_categories, true ) ); ?>>

                            <span><?php echo esc_html( $category_name ); ?></span>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

        <style>

        .pinaka-meta-wrapper{
            display:flex;
            flex-direction:column;
            gap:20px;
        }

        .pinaka-online-field{
            padding:14px 18px;
            background:#f6f7f7;
            border:1px solid #dcdcde;
            border-radius:8px;
        }

        .pinaka-checkbox{
            display:flex;
            align-items:center;
            gap:10px;
            font-weight:600;
            cursor:pointer;
        }

        .pinaka-checkbox input{
            margin:0;
        }

        .pinaka-category-wrapper{
            border:1px solid #dcdcde;
            border-radius:8px;
            background:#fff;
        }

        .pinaka-category-wrapper h4{
            margin:0;
            padding:12px 16px;
            background:#2271b1;
            color:#fff;
            border-radius:8px 8px 0 0;
            font-size:14px;
        }

        .pinaka-category-list{
            max-height:260px;
            overflow-y:auto;
            padding:12px;
        }

        .pinaka-category-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:8px 10px;
            margin-bottom:6px;
            border-radius:6px;
            cursor:pointer;
            transition:.2s;
        }

        .pinaka-category-item:hover{
            background:#f6f7f7;
        }

        .pinaka-category-item input{
            margin:0;
        }

        .pinaka-category-item span{
            flex:1;
        }

        .pinaka-category-list::-webkit-scrollbar{
            width:8px;
        }

        .pinaka-category-list::-webkit-scrollbar-thumb{
            background:#c3c4c7;
            border-radius:8px;
        }

        .pinaka-category-list::-webkit-scrollbar-thumb:hover{
            background:#8c8f94;
        }

        </style>

        <?php
    }

    /**
     * Save Shift Meta Fields
     */
    public function save_modifier_groups_data( $post_id ) {

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        update_post_meta(
            $post_id,
            'product_categories',
            ! empty( $_POST['product_categories'] )
                ? array_map( 'absint', $_POST['product_categories'] )
                : []
        );
        update_post_meta(
            $post_id,
            'is_online',
            isset( $_POST['is_online'] ) ? 1 : 0
        );
    }
}