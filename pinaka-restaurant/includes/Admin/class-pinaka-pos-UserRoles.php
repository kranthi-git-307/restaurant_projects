<?php
if (!defined('WPINC')) {
    die;
}

class Pinaka_POS_UserRoles {

    private $plugin_name;
    private $version;
    private $plugin_url;
    private $plugin_dir;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_dir = plugin_dir_path(__FILE__);

        // Multi-role
        add_action('show_user_profile', [$this, 'render_multi_role_field']);
        add_action('edit_user_profile', [$this, 'render_multi_role_field']);
        add_action('personal_options_update', [$this, 'save_multi_roles']);
        add_action('edit_user_profile_update', [$this, 'save_multi_roles']);

        // PIN columns
        add_filter('manage_users_columns', [$this, 'add_pin_column']);
        add_filter('manage_users_custom_column', [$this, 'show_pin_column_content'], 10, 3);
        add_filter('manage_users_columns', [$this, 'add_user_pin_column']);
        add_filter('manage_users_custom_column', [$this, 'show_user_pin_column_content'], 10, 3);

        // Profile picture upload
        add_action('show_user_profile', [$this, 'insert_upload_form']);
        add_action('edit_user_profile', [$this, 'insert_upload_form']);
        add_action('user_new_form', [$this, 'insert_upload_form']);

        // Add enctype to forms for file upload
        add_action('user_edit_form_tag', function() { echo ' enctype="multipart/form-data"'; });
        add_action('user_new_form_tag', function() { echo ' enctype="multipart/form-data"'; });

        // Save profile picture
        add_action('edit_user_profile_update', [$this, 'save_user_profile']);
        add_action('personal_options_update', [$this, 'save_user_profile']);
        add_action('user_register', [$this, 'save_user_profile']);

        // Avatar override
        add_filter('get_avatar', [$this, 'avatar_override'], 10, 6);

        // Phone number
        add_filter('user_contactmethods', [$this, 'add_phone_to_contact_methods']);
        add_action('personal_options_update', [$this, 'save_phone_number_field']);
        add_action('user_new_form', [$this, 'render_phone_number_new_user']);
        add_action('edit_user_profile_update', [$this, 'save_phone_number_field']);
        add_action('user_register', [$this, 'save_phone_number_field']);
    }

    // --- Phone number ---
    public function add_phone_to_contact_methods($user_contact) {
        $user_contact['phone_number'] = __('Phone Number', 'pinaka');
        return $user_contact;
    }

    public function render_phone_number_new_user($operation) {
        if ($operation !== 'add-new-user') return;
        ?>
        <h3><?php esc_html_e('Contact Info', 'pinaka'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone_number"><?php esc_html_e('Phone Number', 'pinaka'); ?></label></th>
                <td>
                    <input type="text" name="phone_number" id="phone_number" value="" class="regular-text" />
                    <p class="description"><?php esc_html_e('Enter user phone number', 'pinaka'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_phone_number_field($user_id) {
        if (!current_user_can('edit_users', $user_id)) return;
        if (isset($_POST['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        }
    }

    // --- Multi-role ---
    public function render_multi_role_field($user) {
        if (!current_user_can('edit_users')) return;
        $all_roles  = wp_roles()->roles;
        $user_roles = $user->roles;
        echo '<h3>PinakaPOS: Assign Multiple Roles</h3>';
        echo '<table class="form-table"><tr><th><label>User Roles</label></th><td>';
        echo '<div style="border: 1px solid #ccc; padding: 10px; max-width: 400px;">';
        foreach ($all_roles as $role_slug => $details) {
            $checked = in_array($role_slug, $user_roles) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="pinaka_user_roles[]" value="' . esc_attr($role_slug) . '" ' . $checked . '> ';
            echo esc_html($details['name']);
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description">Tick all roles this user should have.</p>';
        echo '</td></tr></table>';
    }

    public function save_multi_roles($user_id) {
        if (!current_user_can('edit_users')) return;
        if (isset($_POST['pinaka_user_roles']) && is_array($_POST['pinaka_user_roles'])) {
            $user = new WP_User($user_id);
            unset($_POST['role']);
            foreach ($user->roles as $role) $user->remove_role($role);
            foreach ($_POST['pinaka_user_roles'] as $role) $user->add_role(sanitize_text_field($role));
        }
    }

    // --- PIN columns ---
    public function add_pin_column($columns) {
        $columns['emp_login_pin'] = 'Login PIN';
        return $columns;
    }

    public function show_pin_column_content($value, $column_name, $user_id) {
        if ($column_name === 'emp_login_pin') {
            $pin = get_user_meta($user_id, 'emp_login_pin', true);
            return $pin ? esc_html($pin) : '<em>Not set</em>';
        }
        return $value;
    }

    public function add_user_pin_column($columns) {
        $columns['user_login_pin'] = 'User Login PIN';
        return $columns;
    }

    public function show_user_pin_column_content($value, $column_name, $user_id) {
        if ($column_name === 'user_login_pin') {
            $pin = get_user_meta($user_id, 'user_login_pin', true);
            return $pin ? esc_html($pin) : '<em>Not set</em>';
        }
        return $value;
    }

    // --- Profile picture upload with delete ---
public function insert_upload_form($user) {
    $user_id = isset($user->ID) ? $user->ID : 0;
    $profile_pic = get_user_meta($user_id, 'profilepicture', true);
    ?>
    <h3><?php esc_html_e('Profile Picture', 'pinaka'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="profilepicture_upload"><?php _e('Upload Profile Picture', 'pinaka'); ?></label></th>
            <td>
                <input type="file" name="profilepicture_upload" id="profilepicture_upload" accept="image/*" />
                <p class="description"><?php esc_html_e('Choose a picture from your computer and click "Update Profile".', 'pinaka'); ?></p>
                <?php if (!empty($profile_pic)) : ?>
                    <br>
                    <img src="<?php echo esc_url($profile_pic); ?>" style="max-width:100px; max-height:100px; margin-top:10px; border:1px solid #ccc; padding:4px;">
                    <br>
                    <label>
                        <input type="checkbox" name="delete_profilepicture" value="1"> <?php esc_html_e('Delete current profile picture', 'pinaka'); ?>
                    </label>
                <?php else: ?>
                    <p><em><?php esc_html_e('No profile picture uploaded.', 'pinaka'); ?></em></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

public function save_user_profile($user_id) {
    if (!current_user_can('edit_users', $user_id)) return;

    // Handle file upload
    if (!empty($_FILES['profilepicture_upload']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('profilepicture_upload', 0);
        if (!is_wp_error($attachment_id)) {
            $image_url = wp_get_attachment_url($attachment_id);
            update_user_meta($user_id, 'profilepicture', esc_url($image_url));
        }
    }

    // Handle delete request
    if (!empty($_POST['delete_profilepicture']) && $_POST['delete_profilepicture'] == '1') {
        delete_user_meta($user_id, 'profilepicture');
    }
}


    // --- Avatar override ---
    public function avatar_override($avatar, $id_or_email, $size, $default, $alt, $args = array()) {
        global $pagenow;
        if ($pagenow === 'options-discussion.php') return $avatar;

        if (is_numeric($id_or_email)) {
            $user = get_user_by('id', (int)$id_or_email);
        } elseif (is_object($id_or_email)) {
            $comment = $id_or_email;
            $user = !empty($comment->user_id) ? get_user_by('id', $comment->user_id) : get_user_by('email', $comment->comment_author_email);
        } elseif (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
        } else {
            return $avatar;
        }
        if (!$user) return $avatar;

        $user_id = $user->ID;
        $custom_avatar = dew_profile_img($user_id, [
            'size' => [$size, $size],
            'attr' => ['alt' => $alt],
            'echo' => false
        ]);
        return $custom_avatar ? $custom_avatar : $avatar;
    }

    public function get_plugin_url($path = '') {
        $url = $this->plugin_url;
        if (!empty($path)) $url .= ltrim($path, '/');
        return $url;
    }
}

// --- Instantiate the class ---
global $pinaka_user_roles;
if (class_exists('Pinaka_POS_UserRoles')) {
    add_action('plugins_loaded', function() {
        global $pinaka_user_roles;
        $pinaka_user_roles = new Pinaka_POS_UserRoles('pinaka-pos', '1.0.0');
    });
}

// --- Helper to return profile img ---
function dew_profile_img($user_id, $args = []) {
    $defaults = [
        'size' => 'thumbnail',
        'attr' => '',
        'echo' => true
    ];
    $args = wp_parse_args($args, $defaults);
    $profile_pic = get_user_meta($user_id, 'profilepicture', true);
    $size = is_array($args['size']) ? $args['size'][0] : 96;
    $profile = $profile_pic ? '<img src="' . esc_url($profile_pic) . '" class="avatar avatar-' . $size . ' photo" width="' . $size . '" height="' . $size . '">' : '';
    if ($args['echo']) echo $profile; else return $profile;
}
