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

class Pinaka_POS_Modifiers {
    
    public function __construct() {

        add_action( 'init', [ $this, 'register_modifiers_post_type' ] );

        add_action( 'add_meta_boxes', [ $this, 'add_modifiers_meta_boxes' ] );

        add_action( 'save_post_modifiers', [ $this, 'save_modifiers_data' ] );

        add_filter(
            'manage_modifiers_posts_columns',
            [ $this, 'set_custom_columns' ]
        );

        add_action(
            'manage_modifiers_posts_custom_column',
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
    public function register_modifiers_post_type() {
        $labels = array(
            'name'                  => __('Modifiers', 'pinaka-pos'),
            'singular_name'         => __('Modifiers', 'pinaka-pos'),
            'add_new'               => __('Add New Modifier', 'pinaka-pos'),
            'add_new_item'          => __('Add New Modifier', 'pinaka-pos'),
            'edit_item'             => __('Edit Modifier', 'pinaka-pos'),
            'new_item'              => __('New Modifier', 'pinaka-pos'),
            'view_item'             => __('View Modifier', 'pinaka-pos'),
            'search_items'          => __('Search Modifier', 'pinaka-pos'),
            'not_found'             => __('No Modifier found', 'pinaka-pos'),
            'not_found_in_trash'    => __('No Modifier found in Trash', 'pinaka-pos'),
            'menu_name'             => __('Modifier', 'pinaka-pos'),
        );

        $args = array(
            'labels'        => $labels,
            'description'   => __('Modifier custom post type', 'pinaka-pos'),
            'public'        => true,
            'publicly_queryable' => true,
            'show_ui'       => true,
            'show_in_menu'  => false, // Hide from top menu, shown under Pinaka POS
            'query_var'     => true,
            'rewrite'       => array('slug' => 'modifiers'),
            'capability_type' => 'post',
            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => null,
            'supports'      => array('title'),
        );

        register_post_type('modifiers', $args);
    }

    /**
     * Define the admin Table columns.
     */

    public function set_custom_columns( $columns ) {

        $new_columns = [];

        // Keep checkbox first.
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }

        $new_columns['title'] = __( 'Modifier', 'pinaka-pos' );
        $new_columns['modifier_groups'] = __( 'Modifier Groups', 'pinaka-pos' );
        $new_columns['price'] = __( 'Price', 'pinaka-pos' );
        $new_columns['is_online']  = __( 'Is Available', 'pinaka-pos' );
        return $new_columns;
    }

    /**
     * Render the column values.
     */
    public function custom_column_content( $column, $post_id ) {

        switch ( $column ) {

            case 'modifier_groups':

                $group_ids = get_post_meta( $post_id, 'modifier_group', true );

                if ( ! empty( $group_ids ) && is_array( $group_ids ) ) {

                    $group_names = [];

                    foreach ( $group_ids as $group_id ) {

                        $group = get_post( $group_id );

                        if ( $group ) {
                            $group_names[] = esc_html( $group->post_title );
                        }
                    }

                    echo implode( '<br>', $group_names );

                } else {

                    echo '&mdash;';

                }

                break;

            case 'price':

                $price = get_post_meta( $post_id, 'modifier_price', true );

                if ( '' !== $price ) {

                    echo '₹' . number_format( (float) $price, 2 );

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
    public function add_modifiers_meta_boxes() {
        add_meta_box(
            'modifiers_box',
            __( 'Modifier Details', 'pinaka-pos' ),
            [ $this, 'render_modifiers_meta_box' ],
            'modifiers',
            'normal',
            'high'
        );
    }



    /**
     * Render Discount Details Meta Box
     */
    public function render_modifiers_meta_box( $post ) {

        $modifier_groups = get_posts(
            [
                'post_type'      => 'modifier_group',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );

        $selected_groups = get_post_meta( $post->ID, 'modifier_group', true );
        $selected_groups = is_array( $selected_groups ) ? $selected_groups : [];

        $price = get_post_meta( $post->ID, 'modifier_price', true );
        $is_online = get_post_meta( $post->ID, 'is_online', true );
        ?>
        <p>
            <label>
                <strong><?php esc_html_e( 'Modifier Groups', 'pinaka-pos' ); ?></strong>
            </label>

            <div class="pinaka-group-wrapper">

            <h4><?php esc_html_e( 'Modifier Groups', 'pinaka-pos' ); ?></h4>
        
            <div class="pinaka-group-list">
        
                <?php foreach ( $modifier_groups as $group ) : ?>
        
                    <label class="pinaka-group-item">
        
                        <input
                            type="checkbox"
                            name="modifier_group[]"
                            value="<?php echo esc_attr( $group->ID ); ?>"
                            <?php checked( in_array( $group->ID, $selected_groups, true ) ); ?>>
        
                        <span><?php echo esc_html( $group->post_title ); ?></span>
        
                    </label>
        
                <?php endforeach; ?>
        
            </div>
        
        </div>
        </p>

        <p>
            <label for="modifier_price">
                <strong><?php esc_html_e( 'Price', 'pinaka-pos' ); ?></strong>
            </label>

            <input
                type="number"
                id="modifier_price"
                name="modifier_price"
                class="widefat"
                min="0"
                step="0.01"
                value="<?php echo esc_attr( $price ); ?>">

        </p>
        <div class="pinaka-field pinaka-online-field">

            <label class="pinaka-checkbox">

                <input
                    type="checkbox"
                    name="is_online"
                    value="1"
                    <?php checked( $is_online, 1 ); ?>>

                <span>Is Available</span>

            </label>

        </div>
        <style>
        .pinaka-online-field{
            margin-bottom:20px;
            padding:12px 16px;
            background:#f6f7f7;
            border:1px solid #dcdcde;
            border-radius:8px;
        }

        .pinaka-checkbox{
            display:flex !important;
            align-items:center;
            gap:10px;
            margin:0 !important;
            cursor:pointer;
            font-weight:600;
        }

        .pinaka-checkbox input{
            margin:0;
        }
        .pinaka-group-wrapper{
            border:1px solid #dcdcde;
            border-radius:8px;
            background:#fff;
            margin-bottom:20px;
        }
        
        .pinaka-group-wrapper h4{
            margin:0;
            padding:12px 16px;
            background:#2271b1;
            color:#fff;
            border-radius:8px 8px 0 0;
            font-size:14px;
        }
        
        .pinaka-group-list{
            max-height:260px;
            overflow-y:auto;
            padding:12px;
        }
        
        .pinaka-group-item{
            display:flex;
            align-items:center;
            gap:10px;
            padding:8px 10px;
            margin-bottom:6px;
            border-radius:6px;
            cursor:pointer;
            transition:.2s;
        }
        
        .pinaka-group-item:hover{
            background:#f6f7f7;
        }
        
        .pinaka-group-item input{
            margin:0;
        }
        
        .pinaka-group-item span{
            flex:1;
        }
        
        .pinaka-group-list::-webkit-scrollbar{
            width:8px;
        }
        
        .pinaka-group-list::-webkit-scrollbar-thumb{
            background:#c3c4c7;
            border-radius:8px;
        }
        
        .pinaka-group-list::-webkit-scrollbar-thumb:hover{
            background:#8c8f94;
        }
        </style>
        <?php
    }

    /**
     * Save Shift Meta Fields
     */
    public function save_modifiers_data( $post_id ) {

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $modifier_groups = [];

        if ( ! empty( $_POST['modifier_group'] ) ) {

            foreach ( (array) $_POST['modifier_group'] as $group_id ) {
                $modifier_groups[] = absint( $group_id );
            }
        }

        update_post_meta( $post_id, 'modifier_group', $modifier_groups );

        update_post_meta(
            $post_id,
            'modifier_price',
            isset( $_POST['modifier_price'] ) ? (float) $_POST['modifier_price'] : 0
        );
        update_post_meta(
            $post_id,
            'is_online',
            isset( $_POST['is_online'] ) ? 1 : 0
        );
    }
}