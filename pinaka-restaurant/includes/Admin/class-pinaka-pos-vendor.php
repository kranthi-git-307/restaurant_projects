<?php
/**
 * The admin-vendor functionality of the plugin.
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

class Pinaka_POS_Vendor
{

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

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->loader = new Pinaka_POS_Loader();
		$this->define_admin_hooks();

		add_action('add_meta_boxes', [$this, 'add_vendor_meta_boxes']);
		add_action('save_post', [$this, 'save_vendor_meta']);
		add_filter('manage_vendor_posts_columns', [$this, 'vendor_columns']);
		add_action('manage_vendor_posts_custom_column', [$this, 'vendor_column_content'], 10, 2);
		add_filter('manage_edit-vendor_sortable_columns', [$this, 'vendor_sortable_columns']);
	}

	/**
	 * Register the vendor custom post type.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function register_vendor_post_type()
	{
		$labels = array(
			'name' => __('Vendors', 'pinaka-pos'),
			'singular_name' => __('Vendor', 'pinaka-pos'),
			'add_new' => __('Add New Vendor', 'pinaka-pos'),
			'add_new_item' => __('Add New Vendor', 'pinaka-pos'),
			'edit_item' => __('Edit Vendor', 'pinaka-pos'),
			'new_item' => __('New Vendor', 'pinaka-pos'),
			'view_item' => __('View Vendor', 'pinaka-pos'),
			'search_items' => __('Search Vendors', 'pinaka-pos'),
			'not_found' => __('No vendors found', 'pinaka-pos'),
			'not_found_in_trash' => __('No vendors found in Trash', 'pinaka-pos'),
			'menu_name' => __('Vendors', 'pinaka-pos'),
		);

		$args = array(
			'labels' => $labels,
			'description' => __('Vendor custom post type', 'pinaka-pos'),
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false, // Hide from top menu, shown under Pinaka POS
			'query_var' => true,
			'rewrite' => array('slug' => 'vendor'),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('title', 'thumbnail'),
		);

		register_post_type('vendor', $args);
	}

	/**
	 * Define the admin hooks.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		// ✅ Ensure post type is registered before menu is built
		$this->loader->add_action('init', $this, 'register_vendor_post_type');
		$this->loader->add_action('admin_menu', $this, 'add_vendor_submenu_page');
	}

	/**
	 * Add the vendor submenu page.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function add_vendor_submenu_page()
	{
		add_submenu_page(
			'pinaka-pos-dashboard',
			__('Manage Vendors', 'pinaka-pos'),
			__('Manage Vendors', 'pinaka-pos'),
			'manage_options',
			'pinaka-pos-vendors',
			[$this, 'vendorRender']
		);
	}
	public function vendor_columns($columns)
	{

		$new_columns = [];

		foreach ($columns as $key => $value) {

			$new_columns[$key] = $value;

			if ($key === 'title') {
				$new_columns['vendor_phone'] = __('Phone Number', 'pinaka-pos');
				$new_columns['vendor_email'] = __('Email', 'pinaka-pos');
			}
		}

		return $new_columns;
	}
	/**
	 * Render the vendor submenu page.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function vendorRender()
	{
		wp_safe_redirect(admin_url('edit.php?post_type=vendor'));
		exit;
	}

	/**
	 * Add meta boxes to Vendor post type
	 */
	public function add_vendor_meta_boxes()
	{
		add_meta_box(
			'vendor_contact_info',
			'Vendor Contact Information',
			[$this, 'render_vendor_contact_meta_box'],
			'vendor',
			'normal',
			'high'
		);

		add_meta_box(
			'vendor_linked_payments',
			'Linked Payments',
			[$this, 'render_vendor_payments_meta_box'],
			'vendor',
			'normal',
			'high'
		);
	}
	public function vendor_sortable_columns($columns)
	{
		$columns['vendor_phone'] = 'vendor_phone';
		$columns['vendor_email'] = 'vendor_email';
		return $columns;
	}


	/**
	 * Render Vendor Contact Information Meta Box
	 */
	public function render_vendor_contact_meta_box($post)
	{
		$phone = get_post_meta($post->ID, '_vendor_phone', true);
		$email = get_post_meta($post->ID, '_vendor_email', true);
		$address = get_post_meta($post->ID, '_vendor_address', true);
		?>

		<div style="margin-bottom:15px;">
			<label for="vendor_phone"><strong>Phone Number</strong></label><br>
			<input type="text" id="vendor_phone" name="vendor_phone" value="<?php echo esc_attr($phone); ?>" maxlength="10"
				pattern="[0-9]{10}" style="width:100%;" placeholder="Enter 10 digit mobile number" required>
			<small>Only 10 digit numbers allowed.</small>
		</div>

		<div style="margin-bottom:15px;">
			<label for="vendor_email"><strong>Email Address</strong></label><br>
			<input type="email" id="vendor_email" name="vendor_email" value="<?php echo esc_attr($email); ?>"
				pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" style="width:100%;" placeholder="example@gmail.com" required>
			<small>Only Gmail addresses are allowed.</small>
		</div>

		<div>
			<label for="vendor_address"><strong>Address</strong></label><br>
			<textarea id="vendor_address" name="vendor_address" rows="4"
				style="width:100%;"><?php echo esc_textarea($address); ?></textarea>
		</div>

		<script>
			jQuery(document).ready(function ($) {

				// Phone number: only digits
				$('#vendor_phone').on('input', function () {
					this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
				});

				$('form#post').on('submit', function () {

					let phone = $('#vendor_phone').val().trim();
					let email = $('#vendor_email').val().trim();

					if (!/^[0-9]{10}$/.test(phone)) {
						alert('Phone number must be exactly 10 digits.');
						return false;
					}

					if (!/^[a-zA-Z0-9._%+-]+@gmail\.com$/.test(email)) {
						alert('Please enter a valid Gmail address.');
						return false;
					}

					return true;
				});
			});
		</script>

		<?php
	}
	public function vendor_column_content($column, $post_id)
	{

		switch ($column) {

			case 'vendor_phone':
				echo esc_html(get_post_meta($post_id, '_vendor_phone', true));
				break;

			case 'vendor_email':
				echo esc_html(get_post_meta($post_id, '_vendor_email', true));
				break;
		}
	}

	/**
	 * Render Vendor Linked Payments Meta Box
	 */
	public function render_vendor_payments_meta_box($post)
	{
		$linked_payments = get_post_meta($post->ID, '_vendor_linked_payments', true);
		?>
		<p>
			<label for="vendor_linked_payments">Linked Payments (IDs, comma-separated):</label><br>
			<input type="text" id="vendor_linked_payments" name="vendor_linked_payments"
				value="<?php echo esc_attr($linked_payments); ?>" style="width:100%;">
		</p>
		<?php
	}

	/**
	 * Save Vendor Meta Fields
	 */
	public function save_vendor_meta($post_id)
	{

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Phone Validation
		if (isset($_POST['vendor_phone'])) {

			$phone = preg_replace('/[^0-9]/', '', $_POST['vendor_phone']);

			if (!preg_match('/^[0-9]{10}$/', $phone)) {
				wp_die('Vendor phone number must be exactly 10 digits.');
			}

			update_post_meta($post_id, '_vendor_phone', $phone);
		}

		// Gmail Validation
		if (isset($_POST['vendor_email'])) {

			$email = sanitize_email($_POST['vendor_email']);

			if (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
				wp_die('Only Gmail addresses are allowed.');
			}

			update_post_meta($post_id, '_vendor_email', $email);
		}

		if (isset($_POST['vendor_address'])) {
			update_post_meta(
				$post_id,
				'_vendor_address',
				sanitize_textarea_field($_POST['vendor_address'])
			);
		}

		if (isset($_POST['vendor_linked_payments'])) {
			update_post_meta(
				$post_id,
				'_vendor_linked_payments',
				sanitize_text_field($_POST['vendor_linked_payments'])
			);
		}
	}
}