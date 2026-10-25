<?php

function pinaka_pos_register_settings() {
    // General Settings
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_name');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_email');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_phone');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_logo');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_gstin');
    register_setting('pinaka_pos_general_settings', 'store_registered_email_id');
    
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_address');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_city');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_state');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_postcode');
    register_setting('pinaka_pos_general_settings', 'pinaka_pos_business_country');
}
add_action('admin_init', 'pinaka_pos_register_settings');


function pinaka_pos_handle_file_upload($option) {
    if (!empty($_FILES['pinaka_pos_logo']['name'])) {
        $uploaded_file = wp_handle_upload($_FILES['pinaka_pos_logo'], ['test_form' => false]);
        if (!isset($uploaded_file['error'])) {
            update_option('pinaka_pos_logo', $uploaded_file['url']);
        }
    }
}
add_action('admin_post_update_pinaka_pos_settings', 'pinaka_pos_handle_file_upload');


// // Register the vendor post type
// function pinaka_pos_register_vendor_post_type() {
//     $labels = array(
//         'name' => __( 'Vendors', 'pinaka-pos' ),
//         'singular_name' => __( 'Vendor', 'pinaka-pos' ),
//         'add_new' => __( 'Add New Vendor', 'pinaka-pos' ),
//         'add_new_item' => __( 'Add New Vendor', 'pinaka-pos' ),
//         'edit_item' => __( 'Edit Vendor', 'pinaka-pos' ),
//         'new_item' => __( 'New Vendor', 'pinaka-pos' ),
//         'view_item' => __( 'View Vendor', 'pinaka-pos' ),
//         'search_items' => __( 'Search Vendors', 'pinaka-pos' ),
//         'not_found' => __( 'No vendors found', 'pinaka-pos' ),
//         'not_found_in_trash' => __( 'No vendors found in trash', 'pinaka-pos' ),
//         'parent_item_colon' => __( 'Parent Vendor:', 'pinaka-pos' ),
//         'menu_name' => __( 'Vendors', 'pinaka-pos' ),
//     );

//     $args = array(
//         'labels' => $labels,
//         'public' => true,
//         'publicly_queryable' => true,
//         'show_ui' => true,
//         'show_in_menu' => true,
//         'query_var' => true,
//         'rewrite' => array( 'slug' => 'vendor' ),
//         'capability_type' => 'post',
//         'has_archive' => true,
//         'hierarchical' => false,
//         'menu_position' => null,
//         'supports' => array( 'title', 'editor', 'thumbnail' ),
//     );

//     register_post_type( 'vendor', $args );
// }
// add_action( 'init', 'pinaka_pos_register_vendor_post_type' );