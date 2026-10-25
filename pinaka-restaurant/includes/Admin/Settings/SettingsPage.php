<?php
namespace PinakaPos\Admin\Settings;
use PinakaPos\Admin\Admin_Helper;

use EverAccounting\Utilities\I18nUtil;

// Exit if accessed directly.
defined('ABSPATH') || exit();

/**
 * Class SettingsPage
 */
class SettingsPage {
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

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

    public function render() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'tab1';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Settings', 'pinaka-pos-wp'); ?></h1>
            <p><?php echo esc_html__('This page will display and manage settings.', 'pinaka-pos-wp'); ?></p>

            <!-- Tab Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=pinaka-pos-settings&tab=tab1" 
                   class="nav-tab <?php echo $current_tab === 'tab1' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General Settings', 'pinaka-pos-wp'); ?>
                </a>
                <a href="?page=pinaka-pos-settings&tab=tab2" 
                   class="nav-tab <?php echo $current_tab === 'tab2' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Admin Theme Settings ', 'pinaka-pos-wp'); ?>
                </a>
                <a href="?page=pinaka-pos-settings&tab=tab3" 
                   class="nav-tab <?php echo $current_tab === 'tab3' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Roles - Menu Settings', 'pinaka-pos-wp'); ?>
                </a>
				<a href="?page=pinaka-pos-settings&tab=tab4" 
                   class="nav-tab <?php echo $current_tab === 'tab4' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Opening Denominatoins', 'pinaka-pos-wp'); ?>
                </a>
				<a href="?page=pinaka-pos-settings&tab=tab5" 
                   class="nav-tab <?php echo $current_tab === 'tab5' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Manage Restaurants', 'pinaka-pos-wp'); ?>
                </a>
				<a href="?page=pinaka-pos-settings&tab=tab6" 
                   class="nav-tab <?php echo $current_tab === 'tab6' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Manage Shifts', 'pinaka-pos-wp'); ?>
                </a>
				<a href="?page=pinaka-pos-settings&tab=tab7" 
                   class="nav-tab <?php echo $current_tab === 'tab7' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Token Expiry', 'pinaka-pos-wp'); ?>
                </a>
				<a href="?page=pinaka-pos-settings&tab=tab8" 
                   class="nav-tab <?php echo $current_tab === 'tab8' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Slot Availability', 'pinaka-pos-wp'); ?>
                </a>
            </h2>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php
                if ($current_tab === 'tab1') {
                    $this->render_general_settings_tab();
                } elseif ($current_tab === 'tab2') {
                    $this->render_theme_settings_tab();
                } elseif ($current_tab === 'tab3') {
                    $this->render_financial_settings_tab();
                }elseif ($current_tab === 'tab4') {
                    $this->render_denominations_tab();
                }
				elseif ($current_tab === 'tab5') {
                    $this->render_restaurants_tab();
                }
				elseif ($current_tab === 'tab6') {
                    $this->render_shifts_tab();
                }
				elseif ($current_tab === 'tab7') {
                    $this->render_token_expiry_tab();
                }
				elseif ($current_tab === 'tab8') {
                    $this->render_slot_availability_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_general_settings_tab() {
    ?>

    <div class="wrap">
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php
                // Output nonce, action, and option_page for settings
                settings_fields('pinaka_pos_general_settings');
            ?>

	</form>

        <!-- Secondary Child Tabs -->
        <?php
        $child_tab = isset($_GET['general_tab']) ? sanitize_text_field($_GET['general_tab']) : 'general1';
        ?>
        <h3 class="nav-tab-wrapper1" style="margin-top:20px;">
            <a href="?page=pinaka-pos-settings&tab=tab1&general_tab=general1" 
               class="nav-tab1 <?php echo $child_tab === 'general1' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('General', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab1&general_tab=general2" 
               class="nav-tab1 <?php echo $child_tab === 'general2' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Payment', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab1&general_tab=general3" 
               class="nav-tab1 <?php echo $child_tab === 'general3' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Device', 'pinaka-pos-wp'); ?>
            </a>
            <a href="?page=pinaka-pos-settings&tab=tab1&general_tab=general4" 
               class="nav-tab1 <?php echo $child_tab === 'general4' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Advanced', 'pinaka-pos-wp'); ?>
            </a>
        </h3>
    </div>


	<style> 
		.nav-tab-wrapper1
		{ 
			margin-top: 20px; 
		} 
		.nav-tab1 
		{ 
			color : #2271b1; 
			margin-right : 15px; 
			font-size : 14px; 
			border-right: 2px solid #2271b1; 
			padding-right:10px; font-weight:400;
			border-bottom: none;
		} 
		.nav-tab-active1 
			{ 
				border-bottom: 2px solid #fff; 
				font-weight: 400; 
				color:black; 
			} 
			
	</style>
    <!-- Child Tab Content -->

	
    <?php
	if ($child_tab === 'general1') {

		$current_user_id = get_current_user_id();

		if (!$current_user_id) {
			echo '<div class="error"><p>You must be logged in to view this page.</p></div>';
			return;
		}
		$current_user = get_user_by('id', $current_user_id);

		if ( ! $current_user ) {
			echo '<div class="error"><p>User not found.</p></div>';
			return;
		}

		$first_name      = $current_user->first_name ?? '';
		$last_name       = $current_user->last_name ?? '';
		$email           = $current_user->user_email ?? '';
		$phone_number    = get_user_meta($current_user_id, 'phone_number', true);
		if (empty($phone_number)) {
			$phone_number = get_user_meta($current_user_id, 'billing_phone', true);
		}

		$user_device_id  = get_user_meta($current_user_id, 'user_device_id', true);
		$gstin           = get_option('pinaka_pos_gstin');

		$dew_image_id    = get_user_meta($current_user_id, 'profilepicture', true);
		$company_name = get_option("pinaka_pos_name");
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e('General Settings', 'pinaka-pos-wp'); ?></h1>
			<hr class="wp-header-end">

			<form method="post" enctype="multipart/form-data" style="max-width: 1000px;">
				<style>
					.pinaka-settings-columns {
						display: flex;
						gap: 50px;
						margin-top: 30px;
						align-items: flex-start;
					}
					.pinaka-settings-column {
						flex: 1;
						min-width: 400px;
					}
					.section-title {
						font-size: 18px;
						font-weight: 600;
						margin-bottom: 20px;
						color: #333;
					}
					.upload-preview img {
						border-radius: 10px;
						border: 1px solid #ddd;
						width: 100px;
						height: 100px;
						object-fit: cover;
					}
					.upload-preview.empty {
						border: 2px dashed #ccc;
						width: 100px;
						height: 100px;
						display: flex;
						align-items: center;
						justify-content: center;
						border-radius: 10px;
						color: #999;
						font-size: 12px;
						background: #fafafa;
					}
					.upload-btn {
						display: inline-block;
						padding: 10px 16px;
						border-radius: 6px;
						border: 1px solid #ccc;
						background: #f3f3f3;
						cursor: pointer;
						font-size: 13px;
						color: #333;
						transition: all 0.2s;
					}
					.upload-btn:hover {
						background: #0073aa;
						color: #fff;
						border-color: #0073aa;
					}
					input[type="text"], input[type="email"], select {
						width: 100%;
						padding: 8px 10px;
						border: 1px solid #ccc;
						border-radius: 6px;
						font-size: 14px;
					}
					label {
						font-weight: 500;
						color: #222;
						margin-bottom: 6px;
						display: block;
					}
					.form-field {
						margin-bottom: 20px;
					}
					.section-box {
						background: #fff;
						border-radius: 10px;
						padding: 20px;
						box-shadow: 0 2px 6px rgba(0,0,0,0.05);
					}
				</style>

				<?php if (isset($_GET['settings-updated'])): ?>
					<div class="updated notice is-dismissible"><p><?php esc_html_e('Settings saved successfully.', 'pinaka-pos-wp'); ?></p></div>
				<?php endif; ?>

				<div class="pinaka-settings-columns">

					<!-- LEFT COLUMN - USER INFO -->
					<div class="pinaka-settings-column section-box">
						<h2 class="section-title">Personal Information</h2>

						<!-- Profile Photo -->
						<div class="form-field">
							<label><?php esc_html_e('Profile Photo', 'pinaka-pos-wp'); ?></label>
							<div>
								<?php if ($dew_image_id): ?>
									<div class="upload-preview">
										<img src="<?php echo $dew_image_id; ?>" alt="Profile Photo" />
									</div>
								<?php else: ?>
									<div class="upload-preview empty">No Image</div>
								<?php endif; ?>
							</div>
						</div>

						<div class="form-field">
							<label>Full Name</label>
							<input type="text" value="<?php echo esc_attr($first_name.' '.$last_name); ?>">
						</div>

						<div class="form-field">
							<label>Contact Number</label>
							<input type="text" value="<?php echo esc_attr($phone_number); ?>">
						</div>

						<div class="form-field">
							<label>Email Address</label>
							<input type="email" value="<?php echo esc_attr($email); ?>">
						</div>
                        
                        <div class="form-field">
							<label>Registered Email Address</label>
							<input type="text" name="store_registered_email_id" value="<?php echo esc_attr(get_option('store_registered_email_id', '')); ?>">
						</div>
						
						<div class="form-field">
							<label>Device ID</label>
							<input type="text" name="pinaka_deviceid" value="<?php echo esc_attr($user_device_id); ?>">
						</div>

						<div class="form-field">
							<label>GSTIN</label>
							<input type="text" name="pinaka_gstin"
								value="<?php echo esc_attr($gstin); ?>">
						</div>
					</div>

					<!-- RIGHT COLUMN - RECEIPT SETTINGS -->
					<div class="pinaka-settings-column section-box">
						<h2 class="section-title">Receipt Settings</h2>

						<!-- Logo Upload -->
						<div class="form-field">
							<label><?php esc_html_e('Receipt Logo', 'pinaka-pos-wp'); ?></label>
							<div id="receipt-logo-wrapper">
								<?php $logo = get_option('pinaka_receipt_logo'); ?>

								<div class="upload-container">
									<?php if ($logo): ?>
										<div class="upload-preview" id="receipt-logo-preview">
											<img src="<?php echo esc_url($logo); ?>" alt="Receipt Logo" />
											<div class="upload-actions">
												<button type="button" class="button edit-logo" onclick="document.getElementById('receipt_logo').click();">Edit</button>
												<button type="button" class="button delete-logo" onclick="deleteReceiptLogo()">Delete</button>
											</div>
										</div>
									<?php else: ?>
										<div class="upload-preview empty" id="receipt-logo-preview">
											<span>No Logo</span>
										</div>
									<?php endif; ?>

									<label class="upload-btn button" for="receipt_logo">Upload</label>
									<input type="file" id="receipt_logo" name="receipt_logo" accept="image/*" style="display:none;" onchange="previewReceiptLogo(event)">
								</div>
							</div>
							<style>
						#receipt-logo-wrapper {
							display: flex;
							justify-content: center;
							align-items: center;
							flex-direction: column;
							gap: 12px;
							text-align: center;
							margin-top: 20px;
							}

							.upload-container {
							display: flex;
							flex-direction: column;
							align-items: center;
							gap: 12px;
							}

							.upload-preview {
							position: relative;
							width: 150px;
							height: 150px;
							border: 2px dashed #ccc;
							border-radius: 10px;
							display: flex;
							justify-content: center;
							align-items: center;
							overflow: hidden;
							background-color: #f9f9f9;
							}

							.upload-preview img {
							width: 100%;
							height: 100%;
							object-fit: cover;
							}

							.upload-preview.empty {
							color: #888;
							font-size: 14px;
							background-color: #f1f1f1;
							}

							.upload-actions {
							position: absolute;
							bottom: 5px;
							display: flex;
							gap: 6px;
							justify-content: center;
							width: 100%;
							}

							.upload-actions .button {
							background: #2271b1;
							color: #fff;
							border: none;
							padding: 4px 10px;
							border-radius: 4px;
							font-size: 12px;
							cursor: pointer;
							}

							.upload-actions .button.delete-logo {
							background: #d63638;
							}

							.upload-actions .button:hover {
							opacity: 0.9;
							}

							.upload-btn.button {
							background: #0073aa;
							color: #fff;
							border: none;
							padding: 6px 16px;
							border-radius: 4px;
							cursor: pointer;
							transition: all 0.2s ease;
							}

							.upload-btn.button:hover {
							background: #005177;
							}

							</style>

							<script>
								function previewReceiptLogo(event) {
									const file = event.target.files[0];
									if (!file) return;

									const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
									if (!allowedTypes.includes(file.type)) {
										alert('Invalid file type. Only JPG and PNG are allowed.');
										event.target.value = '';
										return;
									}

									if (file.size > 5 * 1024 * 1024) {
										alert('File too large! Maximum 5MB allowed.');
										event.target.value = '';
										return;
									}

									const preview = document.getElementById('receipt-logo-preview');
									const reader = new FileReader();
									reader.onload = function(e) {
										preview.innerHTML = `<img src="${e.target.result}" alt="Receipt Logo" />`;
									};
									reader.readAsDataURL(file);
								}

								function deleteReceiptLogo() {
									if (!confirm('Are you sure you want to delete this logo?')) return;

									fetch(ajaxurl, {
										method: 'POST',
										headers: {'Content-Type': 'application/x-www-form-urlencoded'},
										body: new URLSearchParams({
											action: 'delete_receipt_logo',
											_wpnonce: '<?php echo wp_create_nonce("delete_receipt_logo_nonce"); ?>'
										})
									})
									.then(res => res.json())
									.then(data => {
										if (data.success) {
											alert('Receipt logo deleted successfully.');
											const preview = document.getElementById('receipt-logo-preview');
											preview.innerHTML = 'No Logo';
											preview.classList.add('empty');
										} else {
											alert('Error deleting logo: ' + (data.message || 'Unknown error.'));
										}
									})
									.catch(() => alert('Error deleting logo.'));
								}
							</script>
						</div>

						<div class="form-field">
							<label>Company Name</label>
							<input type="text" name="company_name" value="<?php echo esc_attr($company_name); ?>">
						</div>

						<div class="form-field">
							<label>Header (on receipt)</label>
							<input type="text" name="pinaka_header_text" value="<?php echo esc_attr(get_option('pinaka_header_text', '')); ?>">
						</div>

						<div class="form-field">
							<label>Footer (on receipt)</label>
							<input type="text" name="pinaka_footer_text" value="<?php echo esc_attr(get_option('pinaka_footer_text', '')); ?>">
						</div>

						<?php
                        $print_settings = get_option( 'pinaka_print_settings', array() );
                        
                        if ( ! is_array( $print_settings ) ) {
                            $print_settings = array();
                        }
                        
                        // Show at least one input if nothing is saved.
                        if ( empty( $print_settings ) ) {
                            $print_settings = array( '' );
                        }
                        ?>
                        
                        <div class="form-field">
                            <label>Printer Settings</label>
                        
                            <?php foreach ( $print_settings as $setting ) : ?>
                                <input
                                    type="text"
                                    name="pinaka_print_settings[]"
                                    value="<?php echo esc_attr( $setting ); ?>"
                                >
                            <?php endforeach; ?>
                        </div>
					</div>
				</div>

				<p style="margin-top: 30px;">
					<button type="submit" name="save_general_settings" class="button button-primary button-large">Save Changes</button>
				</p>

				<?php
				if (isset($_POST['save_general_settings'])) {

					if (
                        isset( $_POST['company_name'] )
                    ) {
                        update_option(
                            'pinaka_pos_name',
                            sanitize_text_field( wp_unslash( $_POST['company_name'] ) )
                        );
                    }
                    if (
                        isset( $_POST['pinaka_gstin'] )
                    ) {
                        update_option(
                            'pinaka_pos_gstin',
                            sanitize_text_field( wp_unslash( $_POST['pinaka_gstin'] ) )
                        );
                    }
                    if (
                        isset( $_POST['pinaka_deviceid'] )
                    ) {
                        update_user_meta($current_user_id,
                            'user_device_id',
                            sanitize_text_field( $_POST['pinaka_deviceid'] )
                        );
                    }
					update_option('pinaka_header_text', sanitize_text_field($_POST['pinaka_header_text'] ?? ''));
					update_option('pinaka_footer_text', sanitize_text_field($_POST['pinaka_footer_text'] ?? ''));
				// 	update_option('pinaka_print_settings', sanitize_text_field($_POST['pinaka_print_settings'] ?? ''));
				    $print_settings = isset( $_POST['pinaka_print_settings'] )
                        ? (array) wp_unslash( $_POST['pinaka_print_settings'] )
                        : array();
                    
                    $print_settings = array_map(
                        'sanitize_text_field',
                        $print_settings
                    );
                    
                    update_option( 'pinaka_print_settings', $print_settings );
                    update_option('store_registered_email_id', sanitize_text_field($_POST['store_registered_email_id'] ?? ''));
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/media.php');
					require_once(ABSPATH . 'wp-admin/includes/image.php');

					if (!empty($_FILES['receipt_logo']['name'])) {
						$uploaded = media_handle_upload('receipt_logo', 0);
						if (!is_wp_error($uploaded)) {
							update_option('pinaka_receipt_logo', wp_get_attachment_url($uploaded));
						}
					}

					wp_redirect(add_query_arg('settings-updated', 'true'));
					exit;
				}
				?>
			</form>
		</div>
	<?php 
	}elseif ($child_tab === 'general2') {
		?>
    <style>
.payment-panel {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}
.payment-panel h2 {
    margin-bottom: 20px;
    font-size: 20px;
}
.flex-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.payment-field {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.payment-field label {
    font-weight: bold;
    margin-bottom: 5px;
}
.payment-field input,
.payment-field select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
.chips { 
    display: flex; 
    gap: 10px; 
    flex-wrap: wrap; 
    margin-top:5px; 
}
.chip { 
    border: 1px solid #ccc; 
    padding: 5px 10px; 
    border-radius: 15px; 
}
.btn-save {
    margin-top: 20px;
    padding: 10px 20px;
    background: #398ae6ff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>

<?php
// ✅ Handle form save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_settings_nonce'])) {
    if (wp_verify_nonce($_POST['payment_settings_nonce'], 'save_payment_settings')) {

        $payment_settings = [
            'default_payment' => sanitize_text_field($_POST['default_payment'] ?? ''),
            'tax_config'      => sanitize_text_field($_POST['tax_config'] ?? ''),
            'enable_payment'  => isset($_POST['enable_payment']) ? '1' : '0',
            'custom_payment'  => sanitize_text_field($_POST['custom_payment'] ?? ''),
            'business_name'   => sanitize_text_field($_POST['business_name'] ?? ''),
            'secret_key'      => sanitize_text_field($_POST['secret_key'] ?? ''),
            'api_key'         => sanitize_text_field($_POST['api_key'] ?? ''),
        ];

        // ✅ Handle new custom payment
        $extra_payments = get_option('pinaka_payment_settings', [])['extra_payments'] ?? [];
        if (!empty($_POST['new_custom_payment'])) {
            $new_payment = sanitize_text_field($_POST['new_custom_payment']);
            if (!in_array($new_payment, $extra_payments)) {
                $extra_payments[] = $new_payment;
            }
        }
        $payment_settings['extra_payments'] = $extra_payments;

        update_option('pinaka_payment_settings', $payment_settings);

        echo '<div class="updated"><p>✅ Payment settings saved.</p></div>';
    }
}

// ✅ Load saved settings
$payment_settings = get_option('pinaka_payment_settings', []);
$default_payment  = $payment_settings['default_payment'] ?? '';
$tax_config       = $payment_settings['tax_config'] ?? '';
$enable_payment   = $payment_settings['enable_payment'] ?? '';
$custom_payment   = $payment_settings['custom_payment'] ?? '';
$business_name = get_option('pinaka_business_name', '');
$secret_key       = $payment_settings['secret_key'] ?? '';
$api_key          = $payment_settings['api_key'] ?? '';
$extra_payments   = $payment_settings['extra_payments'] ?? [];
?>

<div class="payment-panel">
    <h2>Payment settings</h2>
    <form method="post">
        <?php wp_nonce_field('save_payment_settings', 'payment_settings_nonce'); ?>

        <div class="flex-row">
            <div class="payment-field">
                <label>Default Payment Method</label>
                <select name="default_payment">
                    <option value="">Select Payment Method</option>
                    <option value="cash" <?php selected($default_payment, 'cash'); ?>>Cash</option>
                    <option value="upi" <?php selected($default_payment, 'upi'); ?>>UPI</option>
                    <option value="card" <?php selected($default_payment, 'card'); ?>>Card</option>
                    <option value="wallet" <?php selected($default_payment, 'wallet'); ?>>Wallet</option>
                </select>
            </div>

            <div class="payment-field">
                <label>Tax Configuration</label>
                <input type="text" name="tax_config" value="<?php echo esc_attr($tax_config); ?>" placeholder="Please enter your info">
            </div>
        </div>

        <div class="payment-field">
            <label>Enable Payment</label>
            <input type="checkbox" name="enable_payment" value="1" <?php checked($enable_payment, '1'); ?>>
        </div>

        <div class="payment-field" style="margin-top:20px;">
            <label>Custom Payment</label>
            <div class="chips">
                <label class="chip"><input type="radio" name="custom_payment" value="wallet" <?php checked($custom_payment, 'wallet'); ?>> Wallet</label>
                <label class="chip"><input type="radio" name="custom_payment" value="pay_later" <?php checked($custom_payment, 'pay_later'); ?>> Pay Later</label>
                <label class="chip"><input type="radio" name="custom_payment" value="membership" <?php checked($custom_payment, 'membership'); ?>> Membership</label>
                <label class="chip"><input type="radio" name="custom_payment" value="card_on_file" <?php checked($custom_payment, 'card_on_file'); ?>> Card on file</label>

                <?php if (!empty($extra_payments)) : ?>
                    <?php foreach ($extra_payments as $extra) : ?>
                        <label class="chip">
                            <input type="radio" name="custom_payment" value="<?php echo esc_attr($extra); ?>" <?php checked($custom_payment, $extra); ?>>
                            <?php echo esc_html($extra); ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <input type="text" name="new_custom_payment" placeholder="+ Add custom" style="margin-top:10px;width:50%">
        </div>

        <h3 style="margin-top:20px;">Payment Gateway Integration Setup</h3>
        <div class="flex-row">
            <div class="payment-field">
                <label>Business Name :</label>
                <input type="text" name="business_name" value="<?php echo esc_attr($business_name); ?>" placeholder="Enter your business name">
            </div>
            <div class="payment-field">
                <label>Secret Key :</label>
                <input type="password" name="secret_key" value="<?php echo esc_attr($secret_key); ?>" placeholder="Enter your secret key">
            </div>
        </div>
        <div class="payment-field">
            <label>API Key :</label>
            <input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="Enter API Key">
        </div>

        <button class="btn-save" type="submit">Save Payments</button>
    </form>
</div>



<?php 


	} elseif ($child_tab === 'general3') {
	?>
	<?php

    // ✅ Handle form save
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pinaka_device_settings_nonce'])) {
        if (wp_verify_nonce($_POST['pinaka_device_settings_nonce'], 'save_device_settings')) {
        
            // Collect & sanitize
            $settings = [
                'kot_printer'       => sanitize_text_field($_POST['kot_printer'] ?? ''),
                'receipt_printer'   => sanitize_text_field($_POST['receipt_printer'] ?? ''),
                'paper_size'        => sanitize_text_field($_POST['paper_size'] ?? ''),
                'connection_type'   => sanitize_text_field($_POST['connection_type'] ?? ''),
                'kitchen_printer'   => sanitize_text_field($_POST['kitchen_printer'] ?? ''),
                'bar_printer'       => sanitize_text_field($_POST['bar_printer'] ?? ''),
            ];

            // Save to wp_options
            update_option('pinaka_device_settings', $settings);

            echo '<div class="updated"><p><strong>Device settings saved successfully!</strong></p></div>';
        }
    }

    // ✅ Load saved values
    $saved = get_option('pinaka_device_settings', []);
    ?>

    <style>
        .device-panel {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            width: 100%;
        }
        .device-panel h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .flex-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .device-field {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .device-field label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .device-field input,
        .device-field select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
	    .device-field select {
		    max-width: 100%;
	    }
        .btn-save {
            margin-top: 20px;
            padding: 10px 20px;
            background: #398ae6ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-test {
            margin-top: 28px;
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .radio-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .device-field-row {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
		    max-width:40%;
        }
        .btn-test {
            padding: 6px 15px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>

    <div class="device-panel">
        <h2>Device Settings</h2>
        <form method="post">
            <?php wp_nonce_field('save_device_settings', 'pinaka_device_settings_nonce'); ?>

            <h3>POS / KOT / Receipt Printer Setup :</h3>
            <div class="flex-row">
                <div class="device-field">
                    <label>KOT Printer :</label>
                    <select name="kot_printer">
                        <option value="">Please select your printer</option>
                        <option value="Printer 1" <?php selected($saved['kot_printer'] ?? '', 'Printer 1'); ?>>Printer 1</option>
                        <option value="Printer 2" <?php selected($saved['kot_printer'] ?? '', 'Printer 2'); ?>>Printer 2</option>
                    </select>
                </div>
                <div class="device-field">
                    <label>Receipt Printer :</label>
                    <select name="receipt_printer">
                        <option value="">Please select your printer</option>
                        <option value="Printer 1" <?php selected($saved['receipt_printer'] ?? '', 'Printer 1'); ?>>Printer 1</option>
                        <option value="Printer 2" <?php selected($saved['receipt_printer'] ?? '', 'Printer 2'); ?>>Printer 2</option>
                    </select>
                </div>
            </div>

            <div class="flex-row">
                <div class="device-field">
                    <label>Paper Size Selection :</label>
                    <select name="paper_size">
                        <option value="">Please select your paper size</option>
                        <option value="58mm" <?php selected($saved['paper_size'] ?? '', '58mm'); ?>>58mm</option>
                        <option value="80mm" <?php selected($saved['paper_size'] ?? '', '80mm'); ?>>80mm</option>
                    </select>
                </div>
                <div class="device-field">
                    <label>Connection Types :</label>
                    <div class="radio-options">
                        <label><input type="radio" name="connection_type" value="USB" <?php checked($saved['connection_type'] ?? '', 'USB'); ?>> USB</label>
                        <label><input type="radio" name="connection_type" value="Bluetooth" <?php checked($saved['connection_type'] ?? '', 'Bluetooth'); ?>> Bluetooth</label>
                        <label><input type="radio" name="connection_type" value="Network" <?php checked($saved['connection_type'] ?? '', 'Network'); ?>> Network</label>
                    </div>
                </div>
            </div>

            <h3>Add Multiple Printers</h3>
            <div class="flex-row">
                <div class="device-field">
                    <label>Kitchen :</label>
                    <select name="kitchen_printer">
                        <option value="">Please select your printer</option>
                        <option value="Printer 1" <?php selected($saved['kitchen_printer'] ?? '', 'Printer 1'); ?>>Printer 1</option>
                        <option value="Printer 2" <?php selected($saved['kitchen_printer'] ?? '', 'Printer 2'); ?>>Printer 2</option>
                    </select>
                </div>
                <div class="device-field">
                    <label>Bar :</label>
                    <select name="bar_printer">
                        <option value="">Please select your printer</option>
                        <option value="Printer 1" <?php selected($saved['bar_printer'] ?? '', 'Printer 1'); ?>>Printer 1</option>
                        <option value="Printer 2" <?php selected($saved['bar_printer'] ?? '', 'Printer 2'); ?>>Printer 2</option>
                    </select>
                </div>
            </div>

            <div class="flex-row">
                <div class="device-field-row">
                    <label>Test Print :</label>
                    <button class="btn-test" type="button">Test</button>
                </div>
            </div>

            <button class="btn-save" type="submit">Save Devices</button>
        </form>
    </div>
    <?php
    } elseif ($child_tab === 'general4') {
    ?>  
    <style>
        .advanced-panel {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            width: 100%;
        }
        .advanced-panel h2 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .flex-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            align-items: center;
        }
        .advanced-field {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .advanced-field label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .advanced-field input,
        .advanced-field select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn-primary {
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-save {
            margin-top: 20px;
            padding: 10px 20px;
            background: #398ae6ff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .toggle-switch input[type="checkbox"] {
            width: 40px;
            height: 20px;
            appearance: none;
            background: #ccc;
            border-radius: 20px;
            position: relative;
            outline: none;
            cursor: pointer;
        }
        .toggle-switch input[type="checkbox"]:checked {
            background: #007bff;
        }
        .toggle-switch input[type="checkbox"]::before {
            content: "";
            width: 18px;
            height: 18px;
            background: #fff;
            border-radius: 50%;
            position: absolute;
            top: 1px;
            left: 1px;
            transition: 0.3s;
        }
        .toggle-switch input[type="checkbox"]:checked::before {
            transform: translateX(20px);
        }
		.flex-row.space-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .restore-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .restore-left {
            display: flex;
            align-items: center;
            gap: 10px; /* spacing between label and input */
        }

        .restore-left label {
            min-width: 60px; /* keeps alignment neat */
        }

        .btn-restore {
            background-color: #007bff; /* same blue as Backup/Download */
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

    </style>

    <div class="advanced-panel">
        <h2>Advanced Settings</h2>

        <div class="flex-row">
            <h3 style="flex:1;">Preview KOT Format</h3>
            <button class="btn-primary" type="button">Preview</button>
        </div>

        <h3>Printer Related :</h3>
        <div class="flex-row">
            <div class="advanced-field">
                <label>Header</label>
                <input type="text" placeholder="Please enter your details">
            </div>
            <div class="advanced-field">
                <label>Footer</label>
                <input type="text" placeholder="Please enter your details">
            </div>
        </div>

        <h3>Backup & Restore :</h3>
        <div class="flex-row">
            <div class="advanced-field">
                <label>Auto Backup Scheduler</label>
                <select>
                    <option>Please select scheduler</option>
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                </select>
            </div>
            <div class="toggle-switch">
                <label>Enable</label>
                <input type="checkbox">
            </div>
        </div>

        <!-- Backup Now row -->
        <div class="flex-row space-between">
            <label>Backup Now</label>
            <button class="btn-primary" type="button">Backup</button>
        </div>

        <!-- Download Logs row -->
        <div class="flex-row space-between">
            <label>Download Logs</label>
            <button class="btn-primary" type="button">Download</button>
        </div>

		<div class="restore-row">
            <div class="restore-left">
                <label for="restoreFile">Restore</label>
                <input type="file" id="restoreFile">
            </div>
            <button class="btn-restore" type="button">Restore</button>
        </div>


        <button class="btn-save" type="button">Save Changes</button>
    </div>
    
	<?php
    
    }
    
	}

    private function render_theme_settings_tab() {
        
        global $_wp_admin_css_colors;
	
		$user_id = get_current_user_id();
		$current_color = get_user_option( 'admin_color', $user_id );
	
		if ( empty( $current_color ) || ! isset( $_wp_admin_css_colors[ $current_color ] ) ) {
			$current_color = 'fresh'; // Default color scheme
		}
		?>
	
		<form id="color-scheme-form" method="post">
			<?php wp_nonce_field( 'update_admin_color_scheme', 'color_scheme_nonce' ); ?>
	
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php _e( 'Select Admin Color Scheme' ); ?></th>
					<td>
						<fieldset id="color-picker" class="scheme-list">
							<legend class="screen-reader-text"><span>
								<?php
								/* translators: Hidden accessibility text. */
								_e( 'Admin Color Scheme' );
								?>
							</span></legend>
							<?php
							wp_nonce_field( 'save-color-scheme', 'color-nonce', false );
							foreach ( $_wp_admin_css_colors as $color => $color_info ) :

								?>
								<div class="color-option <?php echo ( $color === $current_color ) ? 'selected' : ''; ?>">
									<input name="admin_color" id="admin_color_<?php echo esc_attr( $color ); ?>" type="radio" value="<?php echo esc_attr( $color ); ?>" class="tog" <?php checked( $color, $current_color ); ?> />
									<input type="hidden" class="css_url" value="<?php echo esc_url( $color_info->url ); ?>" />
									<input type="hidden" class="icon_colors" value="<?php echo esc_attr( wp_json_encode( array( 'icons' => $color_info->icon_colors ) ) ); ?>" />
									<label for="admin_color_<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $color_info->name ); ?></label>
									<div class="color-palette">
									<?php
										foreach ( $color_info->colors as $html_color ) {
											?>
											<div class="color-palette-shade" style="background-color: <?php echo esc_attr( $html_color ); ?>">&nbsp;</div>
											<?php
										}
									?>
									</div>
								</div>
								<?php

							endforeach;
							?>
						</fieldset>
					</td>
				</tr>
			</table>
	
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
			<button type="submit" class="button button-primary"><?php _e( 'Save Color Scheme' ); ?></button>
		</form>
	
		<div id="color-scheme-message"></div>
	
		<script>
			jQuery(document).ready(function ($) {
				function submitColorSchemeForm() {
					var formData = $('#color-scheme-form').serialize();

					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: formData + '&action=update_admin_color_scheme',
						beforeSend: function () {
							$('#color-scheme-message').html('<p>Saving...</p>');
						},
						success: function (response) {
							$('#color-scheme-message').html('<p style="color:green;">' + response.data.message + '</p>');
							location.reload();
						},
						error: function (response) {
							let errorMsg = response.responseJSON && response.responseJSON.data && response.responseJSON.data.message
								? response.responseJSON.data.message
								: 'Error updating color scheme';
							$('#color-scheme-message').html('<p style="color:red;">' + errorMsg + '</p>');
						}
					});
				}

				$('#color-scheme-form').on('submit', function (e) {
					e.preventDefault();
					submitColorSchemeForm();
				});

				$('input[name="admin_color"]').on('change', function () {
					submitColorSchemeForm();
				});
			});

		</script>
		<?php
	
	}
    private function render_financial_settings_tab() {
		$all_roles = wp_roles()->roles;

		// Filter out default roles
		$custom_roles = array_filter($all_roles, function($key) {
			return !in_array($key, ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager']);
		}, ARRAY_FILTER_USE_KEY);

		// Selected role from URL or default to first
		$selected_role = isset($_GET['selected_role']) ? sanitize_text_field($_GET['selected_role']) : key($custom_roles);
		$all_settings = get_option('pinaka_pos_menu_settings', []);
		$menu_settings = isset($all_settings[$selected_role]) ? $all_settings[$selected_role] : [];
		$menu_items = [
			'vendors' => 'Manage Vendors',
			'vendor_payments' => 'Manage Vendor Payments',
			'shifts' => 'Manage Shifts',
			'payments' => 'Manage Payments',
			'settings' => 'Settings'
		];

		$is_edit = !empty($_GET['edit_role']);
		$edit_role_key = $is_edit ? sanitize_text_field($_GET['edit_role']) : '';
		$edit_role_name = $is_edit && isset($all_roles[$edit_role_key]['name']) 
			? $all_roles[$edit_role_key]['name'] 
			: '';

			if ($is_edit && isset($_GET['updated'])) {
				$is_edit = false;
			}

			
		?>

		<div class="wrap">
			<!-- Create new custom role -->
			<h3>Create New Role</h3>
			
			<form id="create-role-form">

				<input type="hidden" name="edit_role" value="<?php echo esc_attr($edit_role_key); ?>">

				<input type="text" name="new_role_key"
				value="<?php echo esc_attr($edit_role_key); ?>"
				placeholder="Role Key (e.g. manager)" required>

				<input type="text" name="new_role_name"
					value="<?php echo esc_attr($edit_role_name); ?>"
					placeholder="Role Name (e.g. Manager)" required>

				<?php wp_nonce_field('create_custom_role_action', 'create_custom_role_nonce'); ?>

				<button type="submit" class="button">
					<?php echo $is_edit ? 'Update Role' : 'Create Role'; ?>
				</button>

				<span id="role-create-message"></span>
			</form>

			<hr>

			<!-- Select existing role -->


			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th>Role Name</th>
						<th>Role Key</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($custom_roles as $role_key => $role_details): ?>
						<tr>
							<td><?php echo esc_html($role_details['name']); ?></td>
							<td><?php echo esc_html($role_key); ?></td>
							<td>
								<!-- Edit -->
								<a href="<?php echo admin_url('admin.php?page=' . $_GET['page'] . '&tab=tab3&edit_role=' . $role_key); ?>" class="button">
									Edit
								</a>

								<!-- Delete -->
								<button class="button delete-role-btn" data-role="<?php echo esc_attr($role_key); ?>">
									Delete
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<hr>
			<?php 

			?>
			<!-- Privilege Form -->
			<!--<form id="pinaka-pos-menu-settings-form">-->
			<!--	<input type="hidden" name="role" value="<?php echo esc_attr($selected_role); ?>">-->
			<!--	<?php wp_nonce_field('pinaka_pos_menu_settings_action', 'pinaka_pos_menu_nonce'); ?>-->
			<!--	<table class="form-table">-->
			<!--		<?php foreach ($menu_items as $key => $label) : ?>-->
			<!--			<tr>-->
			<!--				<th scope="row"><?php echo esc_html($label); ?></th>-->
			<!--				<td>-->
			<!--					<input type="checkbox" name="menu_items[]" value="<?php echo esc_attr($key); ?>" -->
			<!--						<?php checked(in_array($key, $menu_settings)); ?>>-->
			<!--				</td>-->
			<!--			</tr>-->
			<!--		<?php endforeach; ?>-->
			<!--	</table>-->
			<!--	<p>-->
			<!--		<button type="submit" class="button button-primary">Save Menu Settings</button>-->
			<!--	</p>-->
			<!--	<div id="menu-settings-message"></div>-->
			<!--</form>-->
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Save privileges
// 			$('#pinaka-pos-menu-settings-form').on('submit', function(e) {
// 				e.preventDefault();
// 				var formData = $(this).serialize();

// 				$.post(ajaxurl, formData + '&action=save_pinaka_pos_menu_settings', function(response) {
// 					if (response.success) {
// 						$('#menu-settings-message').html('<p style="color:green;">' + response.data.message + '</p>');
// 						location.reload();
// 					} else {
// 						$('#menu-settings-message').html('<p style="color:red;">' + (response.data?.message || 'Error') + '</p>');
// 					}
// 				});
// 			});

			// Create new role
			$('#create-role-form').on('submit', function(e) {
				e.preventDefault();
				let formData = $(this).serialize();

				let actionType = $('input[name="edit_role"]').val() ? 'update_custom_role' : 'create_custom_role';

				$.post(ajaxurl, formData + '&action=' + actionType, function(response) {
					if (response.success) {
						$('#role-create-message').text(response.data.message).css('color', 'green');
						window.location.href = '<?php echo admin_url("admin.php?page=pinaka-pos-settings&tab=tab3"); ?>';
					} else {
						$('#role-create-message').text(response.data.message).css('color', 'red');
					}
				});
			});

			// Delete role
			$('.delete-role-btn').on('click', function() {
				if (!confirm('Are you sure you want to delete this role?')) return;

				let role = $(this).data('role');

				$.post(ajaxurl, {
					action: 'delete_custom_role',
					role: role,
					_ajax_nonce: '<?php echo wp_create_nonce("delete_custom_role_nonce"); ?>'
				}, function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message || 'Error deleting role');
					}
				});
			});
		});
		</script>
		<?php
	}

	private function render_denominations_tab() {
		$denominations = get_option('pinaka_pos_denominations', []);
		$safedrop_denominations = get_option('pinaka_pos_safedrop_denominations', []);
		$coins_denominations = get_option('pinaka_pos_coins_denominations', []);
		$safe_denominations = get_option('pinaka_pos_safe_denominations', []);
		?>

		<h3>Tube Size and Safe Drop and Cash Drawer Amount</h3>

		<form id="tube-safe-cash-form" method="post">
			<table class="form-table">
				<tr>
					<th><label for="tube_size">Safe Drop Tube Size (e.g. 11):</label></th>
					<td><input type="text" name="tube_size" id="tube_size" value="<?php echo esc_attr(get_option('tube_size')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="safe_drop_amount">Safe Drop Amount:</label></th>
					<td><input type="text" name="safe_drop_amount" id="safe_drop_amount" value="<?php echo esc_attr(get_option('safe_drop_amount')); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="cash_drawer_amount">Cash Drawer Amount:</label></th>
					<td><input type="text" name="cash_drawer_amount" id="cash_drawer_amount" value="<?php echo esc_attr(get_option('cash_drawer_amount')); ?>" class="regular-text" required></td>
				</tr>
			</table>

			<?php wp_nonce_field('save_tube_safe_cash_settings', 'tube_safe_cash_nonce'); ?>
			<p><input type="submit" name="save_tube_safe_cash" class="button button-primary" value="Save Settings"></p>
		</form>


		<div class="wrap">
			<h2>Manage Denominations</h2>

			<form id="denominations-form">
				<?php wp_nonce_field('save_denominations_action', 'save_denominations_nonce'); ?>

				<div id="denomination-list">
					<?php if (!empty($denominations)): ?>
						<?php foreach ($denominations as $denom): ?>
							<div class="denomination-row">
								<input type="text" name="denominations[denom][]" value="<?php echo esc_attr($denom['denom']); ?>" placeholder="Denomination (e.g., ₹100 Note)" required>
								<input type="url" name="denominations[image][]" value="<?php echo esc_attr($denom['image']); ?>" placeholder="Image URL (paste from Media)">
								<button type="button" class="remove-denomination button">Remove</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<button type="button" id="add-denomination" class="button">Add Denomination</button>

				<p><button type="submit" class="button button-primary">Save Denominations</button></p>

				<div id="denomination-message"></div>
			</form>
		</div>

		<div class="wrap">
			<h2>Manage Safe Drop Denominations</h2>

			<form id="safedrop-denominations-form">
				<?php wp_nonce_field('save_safedrop_denominations_action', 'save_safedrop_denominations_nonce'); ?>

				<div id="safedrop-denomination-list">
					<?php
						$safedrop_denominations = get_option('pinaka_pos_safedrop_denominations', []);
						if (!is_array($safedrop_denominations)) {
							$safedrop_denominations = [];
						}

						foreach ($safedrop_denominations as $item): ?>
							<div class="safedrop-denomination-row">
								<input type="text" name="safedrop_denominations[]" value="<?php echo esc_attr($item['denom'] ?? ''); ?>" required>
								<input type="text" name="safedrop_denominations_limit[]" value="<?php echo esc_attr($item['tube_limit'] ?? ''); ?>" step="1" required>
								<input type="text" name="safedrop_denominations_symbol[]" value="<?php echo esc_attr($item['symbol'] ?? ''); ?>" required>
								<button type="button" class="remove-safedrop-denomination button">Remove</button>
							</div>
					<?php endforeach; ?>
				</div>

				<button type="button" id="add-safedrop-denomination" class="button">Add Denomination</button>

				<p><button type="submit" class="button button-primary">Save Denominations</button></p>

				<div id="safedrop-denomination-message"></div>
			</form>
		</div>


		<div class="wrap">
			<h2>Manage Coin Denominations</h2>

			<form id="coins-denominations-form">
				<?php wp_nonce_field('save_coins_denominations_action', 'save_coins_denominations_nonce'); ?>

				<div id="coins-denomination-list">
					<?php if (!empty($coins_denominations)): ?>
						<?php foreach ($coins_denominations as $value): ?>
							<div class="coins-denomination-row">
								<input type="text" name="coins_denominations[denom][]" value="<?php echo esc_attr($value['denom']); ?>" required>
								<input type="text" name="coins_denominations[image][]" value="<?php echo esc_attr($value['image']); ?>" required>
								<button type="button" class="remove-coins-denomination button">Remove</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<button type="button" id="add-coins-denomination" class="button">Add Denomination</button>

				<p><button type="submit" class="button button-primary">Save Denominations</button></p>

				<div id="coins-denomination-message"></div>
			</form>
		</div>

		<div class="wrap">
			<h2>Manage Safe Denominations</h2>

			<form id="safe-denominations-form">
				<?php wp_nonce_field('save_safe_denominations_action', 'save_safe_denominations_nonce'); ?>

				<div id="safe-denomination-list">
					<?php if (!empty($safe_denominations)): ?>
						<?php foreach ($safe_denominations as $value): ?>
							<div class="safe-denomination-row">
								<input type="text" name="safe_denominations[denom][]" value="<?php echo esc_attr($value['denom']); ?>" required>
								<input type="text" name="safe_denominations[image][]" value="<?php echo esc_attr($value['image']); ?>" required>
								<button type="button" class="remove-safe-denomination button">Remove</button>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<button type="button" id="add-safe-denomination" class="button">Add Denomination</button>

				<p><button type="submit" class="button button-primary">Save Denominations</button></p>

				<div id="safe-denomination-message"></div>
			</form>
		</div>

		<style>
			.denomination-row {
				margin-bottom: 8px;
			}
			.denomination-row input {
				width: 100px;
			}

			.safedrop-denomination-row {
				margin-bottom: 8px;
			}
			.coins-denomination-row {
				margin-bottom: 8px;
			}
			.coins-denomination-row input {
				width: 235px;
			}

			.denomination-row input {
				width: 235px;
			}

			.denomination-row input {
				width: 235px;
			}

			.safe-denomination-row {
				margin-bottom: 8px;
			}
			.safe-denomination-row input {
				width: 235px;
			}

			.safedrop-denomination-row input {
				width: 235px !important;
			}
			
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('#add-denomination').on('click', function () {
				const row = `
					<div class="denomination-row">
						<input type="text" name="denominations[denom][]" placeholder="Denomination (e.g., 50)" required>
						<input type="url" name="denominations[image][]" placeholder="Image URL (paste from Media)">
						<button type="button" class="remove-denomination button">Remove</button>
					</div>
				`;
				$('#denomination-list').append(row);
			});

			$('#denomination-list').on('click', '.remove-denomination', function () {
				$(this).closest('.denomination-row').remove();
			});

			$('#denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_denominations', function(response) {
					if (response.success) {
						$('#denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});

			// Safedrop denominations
			$('#add-safedrop-denomination').on('click', function () {
				$('#safedrop-denomination-list').append(`
					<div class="safedrop-denomination-row">
						<input type="text" name="safedrop_denominations[]" placeholder="Denomination" step="0.01" required />
						<input type="text" name="safedrop_denominations_limit[]" placeholder="Denomination Limit" step="1" required />
						<input type="text" name="safedrop_denominations_symbol[]" placeholder="Symbol" required />
						<button type="button" class="remove-safedrop-denomination button">Remove</button>
					</div>
				`);
			});

			$('#safedrop-denomination-list').on('click', '.remove-safedrop-denomination', function () {
				$(this).closest('.safedrop-denomination-row').remove();
			});

			$('#safedrop-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_safedrop_denominations', function(response) {
					if (response.success) {
						$('#safedrop-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#safedrop-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});

			// Coins denominations
			$('#add-coins-denomination').on('click', function () {
				$('#coins-denomination-list').append(`
					<div class="coins-denomination-row">
						<input type="text" placeholder="Denomination (e.g., 50)" name="coins_denominations[denom][]" required />
						<input type="text" placeholder="Image URL (paste from Media)"  name="coins_denominations[image][]" required>

						<button type="button" class="remove-coins-denomination button">Remove</button>
					</div>
				`);
			});

			$('#coins-denomination-list').on('click', '.remove-coins-denomination', function () {
				$(this).closest('.coins-denomination-row').remove();
			});

			$('#coins-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_coins_denominations', function(response) {
					if (response.success) {
						$('#coins-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#coins-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});


			// Safe denominations
			$('#add-safe-denomination').on('click', function () {
				$('#safe-denomination-list').append(`
					<div class="safe-denomination-row">
						<input type="text" placeholder="Denomination (e.g., 50)" name="safe_denominations[denom][]" required />
						<input type="text" placeholder="Image URL (paste from Media)"  name="safe_denominations[image][]" required>

						<button type="button" class="remove-safe-denomination button">Remove</button>
					</div>
				`);
			});

			$('#safe-denomination-list').on('click', '.remove-safe-denomination', function () {
				$(this).closest('.safe-denomination-row').remove();
			});

			$('#safe-denominations-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();

				$.post(ajaxurl, formData + '&action=save_safe_denominations', function(response) {
					if (response.success) {
						$('#safe-denomination-message').html('<p style="color:green;">' + response.data.message + '</p>');
						setTimeout(() => location.reload(), 300);
					} else {
						$('#safe-denomination-message').html('<p style="color:red;">' + (response.data?.message || 'Error saving.') + '</p>');
					}
				});
			});
		});
		</script>
		<?php
	}
	private function render_restaurants_tab()
	{
		?>
		<style>
			.restaurant-section {
				max-width: 900px;
				margin-top: 20px;
			}
			.restaurant-row,
			.restaurant-existing-row {
				display: grid;
				grid-template-columns: 60px 1fr 1fr 2fr auto;
				gap: 10px;
				align-items: center;
				padding: 10px;
				border: 1px solid #ccc;
				border-radius: 6px;
				margin-bottom: 10px;
				background: #f9f9f9;
			}
			.restaurant-row textarea,
			.restaurant-existing-row textarea {
				resize: vertical;
			}
			.restaurant-row input,
			.restaurant-row textarea {
				width: 100%;
			}
			.restaurant-existing-row input,
			.restaurant-existing-row textarea {
				background-color: #f1f1f1;
				border: 1px solid #ddd;
			}
		</style>

		<div class="restaurant-section">
			<h2>Manage Restaurants</h2>
			<form id="restaurant-form">

				<div id="existing-restaurants">
					<h4>Existing Restaurants</h4>
					<?php
					$restaurants = get_option('pinaka_pos_restaurants', []);

					if (!empty($restaurants)) 
					{
						foreach ($restaurants as $res) 
						{
							?>
							<div class="restaurant-existing-row" data-restaurant-id="<?php echo esc_attr($res['id']); ?>">
								<input type="number" class="restaurant-id" value="<?php echo esc_attr($res['id']); ?>" readonly>
								<input type="text" class="restaurant-slug" value="<?php echo esc_attr($res['slug']); ?>" readonly placeholder="Slug">
								<input type="text" class="restaurant-name" value="<?php echo esc_attr($res['name']); ?>" readonly placeholder="Name">
								<textarea readonly class="restaurant-address" placeholder="Address" rows="2"><?php echo esc_textarea($res['address'] ?? ''); ?></textarea>
								<button type="button" class="edit-restaurant button">Edit</button>
                				<button type="button" class="save-restaurant button" style="display:none;">Save</button>
								<button type="button" class="delete-existing-restaurant button">Delete</button>
							</div>
							<?php
						}
					} 
					else 
					{
						echo "<p>No restaurants added yet.</p>";
					}
					?>
				</div>
				<h4>Add New Restaurants</h4>
				<div id="restaurant-list">
					<!-- JavaScript will populate this -->
				</div>
				<p>
					<button type="button" class="button" id="add-restaurant-row">Add Row</button>
					<button type="submit" class="button button-primary">Save New Restaurants</button>
				</p>

				<div id="restaurant-save-message"></div>
			</form>
		</div>
		<?php
	}
	private function render_shifts_tab() 
	{
		$shift_settings = get_option('pinaka_pos_shift_settings', [
			'shift_count' => 1,
			'total_hours' => 24,
			'shifts' => [['shift_id'=>1, 'name'=>'', 'start'=>'', 'end'=>'']]
		]);
		$shift_count = $shift_settings['shift_count'];
		$shifts = array_values($shift_settings['shifts']); // ensure indexed 0,1,2
		?>
		<div class="wrap">
			<h1>Shift Settings</h1>
			<form id="shift-settings-form">
				<?php wp_nonce_field('save_shift_settings', 'shift_settings_nonce'); ?>
				<table class="form-table">
					<tr>
						<th>Number of Shifts</th>
						<td>
							<select name="shift_count" id="shift_count">
								<?php for ($i=1; $i<=24; $i++): ?>
									<option value="<?= $i ?>" <?= selected($shift_count, $i, false) ?>><?= $i ?></option>
								<?php endfor; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Total Shift Hours</th>
						<td>
							<input type="number" name="total_shift_hours" min="1" max="24" step="0.01" value="<?= esc_attr($shift_settings['total_hours']) ?>">
						</td>
					</tr>
				</table>
				<table class="form-table">
					<tbody id="shifts-container">
						<?php for ($i=0; $i<$shift_count; $i++):
							$shift = $shifts[$i] ?? ['shift_id'=>$i+1,'name'=>'','start'=>'','end'=>''];
						?>
						<tr>
							<th>Shift <?= $i+1 ?></th>
							<td>
								Name: <input type="text" name="shift_name_<?= $i ?>" value="<?= esc_attr($shift['name']) ?>" style="width:20%">
								From <input type="time" name="shift_start_<?= $i ?>" value="<?= esc_attr($shift['start']) ?>">
								to <input type="time" name="shift_end_<?= $i ?>" value="<?= esc_attr($shift['end']) ?>">
							</td>
						</tr>
						<?php endfor; ?>
					</tbody>
				</table>

				<p><button type="submit" class="button button-primary">Save Shifts</button></p>
				<div id="shift-save-message"></div>
			</form>
		</div>
		<script>
		jQuery(document).ready(function($) {
			let existingShifts = <?= json_encode($shifts) ?>;
			function renderShifts(count) {
				let html = '';
				for (let i = 0; i < count; i++) {
					let shift = existingShifts[i] || {shift_id: i+1, name: '', start: '', end: ''};
					html += `<tr>
						<th>Shift ${i+1}</th>
						<td>
							Name: <input type="text" name="shift_name_${i}" value="${shift.name}" style="width:20%">
							From <input type="time" name="shift_start_${i}" value="${shift.start}">
							to <input type="time" name="shift_end_${i}" value="${shift.end}">
						</td>
					</tr>`;
				}
				$('#shifts-container').html(html);
			}
			renderShifts($('#shift_count').val());

			$('#shift_count').on('change', function() {
				renderShifts($(this).val());
			});

			$('#shift-settings-form').on('submit', function(e) {
				e.preventDefault();
				let formData = $(this).serialize();
				$.post(ajaxurl, formData + '&action=save_shift_settings', function(response) {
					if (response.success) {
						$('#shift-save-message').html('<p style="color:green;">'+ response.data.message +'</p>');
						existingShifts = response.data.shifts;
					} else {
						$('#shift-save-message').html('<p style="color:red;">'+ response.data.message +'</p>');
					}
				});
			});
		});
		</script>
		<?php
	}
	private function render_token_expiry_tab() 
	{
		// Save on POST
		if ( isset( $_POST['submit_token_expiry_settings'] ) && isset( $_POST['pinaka_token_expiry_days'] ) ) {
			update_option( 'pinaka_token_expiry_days', intval( $_POST['pinaka_token_expiry_days'] ) );
			echo '<div class="notice notice-success"><p>Token expiry time saved successfully.</p></div>';
		}

		// Get current value
		$token_expiry_days = get_option( 'pinaka_token_expiry_days', 0 );
		?>
		<div class="wrap">
			<h2>Token Expiry Settings</h2>
			<form method="post">
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="pinaka_token_expiry_days">Token Expiry Time (in days)</label>
						</th>
						<td>
							<input name="pinaka_token_expiry_days" type="number" id="pinaka_token_expiry_days" value="<?php echo esc_attr( $token_expiry_days ); ?>" class="small-text" min="0" />
							<p class="description">Enter 0 for lifetime token (no expiry).</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Token Expiry', 'primary', 'submit_token_expiry_settings' ); ?>
			</form>
		</div>
		<?php
	}

	private function render_slot_availability_tab() 
	{
		$restaurants = get_option('pinaka_pos_restaurants', []);
		$slots = get_option('pinaka_pos_slots', []);

		// Handle Delete Slot action
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot_id'])) {
			$delete_id = intval($_POST['delete_slot_id']);

			if (isset($_POST['delete_slot_nonce']) &&
				wp_verify_nonce($_POST['delete_slot_nonce'], 'delete_slot_action_' . $delete_id)
			) {
				$slots = get_option('pinaka_pos_slots', []);
				$updated_slots = array_filter($slots, function($slot) use ($delete_id) {
					return $slot['id'] !== $delete_id;
				});

				update_option('pinaka_pos_slots', array_values($updated_slots));
				wp_safe_redirect(admin_url('admin.php?page=pinaka-pos-settings&tab=tab8&deleted=1'));
				exit;
			}
		}
		?>
		<style>
			.slot-section {
				max-width: 800px;
				background: #fff;
				padding: 20px;
				border: 1px solid #ccc;
				border-radius: 6px;
				margin-top: 20px;
			}
			.slot-row {
				display: grid;
				grid-template-columns: 1fr 1fr 1fr;
				gap: 15px;
				margin-bottom: 15px;
			}
			.slot-row select {
				width: 100%;
				padding: 5px;
			}
			.existing-slot-row {
				display: flex;
				gap: 10px;
				align-items: center;
				margin-bottom: 10px;
			}
			.existing-slot-row input {
				padding: 6px;
				width: 200px;
				background: #f1f1f1;
				border: 1px solid #ccc;
			}
		</style>
		<div class="slot-section">
			<h2>Slot Availability</h2>

			<?php if (!empty($slots)) : ?>
			<h4>Existing Slots</h4>
			<?php foreach ($slots as $slot) : ?>
				<div class="existing-slot-row">
					<input type="text" value="ID: <?= esc_attr($slot['id']) ?>" readonly>
					<input type="text" value="Restaurant ID: <?= esc_attr($slot['restaurant_id']) ?>" readonly>
					<input type="text" value="Type: <?= esc_attr(ucfirst($slot['type'])) ?>" readonly>
					<input type="text" value="Time: <?= esc_attr($slot['time']) ?>" readonly>
					<form method="post" style="display:inline;">
						<?php wp_nonce_field('delete_slot_action_' . $slot['id'], 'delete_slot_nonce'); ?>
						<input type="hidden" name="delete_slot_id" value="<?= esc_attr($slot['id']) ?>">
						<button type="submit" class="button button-secondary"
							onclick="return confirm('Are you sure you want to delete this slot?')">
						Delete
						</button>
					</form>
				</div>
			<?php endforeach; ?>
			<?php else : ?>
			<p>No slots added yet.</p>
			<?php endif; ?>
				<hr>
				<h4>Add New Slot</h4>
				<form method="post">
					<div class="slot-row">
						<div>
							<label><strong>Restaurant</strong></label>
							<select name="restaurant_id" required>
								<option value="">Select Restaurant</option>
								<?php foreach ($restaurants as $restaurant): ?>
									<option value="<?= esc_attr($restaurant['id']) ?>">
										<?= esc_html($restaurant['name']) ?> (ID: <?= esc_html($restaurant['id']) ?>)
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div>
							<label><strong>Slot Type</strong></label>
							<select name="slot_type" id="slot_type" required>
								<option value="">Select Type</option>
								<option value="breakfast">Breakfast</option>
								<option value="lunch">Lunch</option>
								<option value="dinner">Dinner</option>
							</select>
						</div>

						<div>
							<label><strong>Reservation Time</strong></label>
							<select name="reservation_time" id="reservation_time" required>
								<option value="">Select Slot Type First</option>
							</select>
						</div>
					</div>

					<p>
						<button type="submit" class="button button-primary">Save Slot</button>
					</p>
				</form>
			</div>
			<script>
				const timeOptions = {
					breakfast: generateTimes("09:00", "11:45"),
					lunch: generateTimes("12:00", "17:45"),
					dinner: generateTimes("18:00", "21:00")
				};

				function generateTimes(start, end) {
					const result = [];
					const startTime = new Date("1970-01-01T" + start + ":00");
					const endTime = new Date("1970-01-01T" + end + ":00");

					while (startTime <= endTime) {
						const hours = startTime.getHours() % 12 || 12;
						const minutes = startTime.getMinutes().toString().padStart(2, '0');
						const ampm = startTime.getHours() >= 12 ? "PM" : "AM";
						const value = `${hours.toString().padStart(2, '0')}:${minutes} ${ampm}`;
						const label = `${hours}:${minutes} ${ampm}`;
						result.push({ value, label });
						startTime.setMinutes(startTime.getMinutes() + 15);
					}

					return result;
				}

				document.getElementById("slot_type").addEventListener("change", function() {
					const selectedType = this.value;
					const timeSelect = document.getElementById("reservation_time");
					timeSelect.innerHTML = '<option value="">Select Time</option>';

					if (timeOptions[selectedType]) {
						timeOptions[selectedType].forEach(time => {
							const opt = document.createElement("option");
							opt.value = time.value;
							opt.textContent = time.label;
							timeSelect.appendChild(opt);
						});
					} else {
						const opt = document.createElement("option");
						opt.textContent = "Select Slot Type First";
						timeSelect.appendChild(opt);
					}
				});
			</script>
		<?php
		// Save logic
		if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
			!empty($_POST['slot_type']) &&
			!empty($_POST['reservation_time']) &&
			!empty($_POST['restaurant_id'])
		) {
			// $slots = get_option('pinaka_pos_slots', []);
			$slots_option = get_option('pinaka_pos_slots', []);
			$slots = is_string($slots_option) ? maybe_unserialize($slots_option) : $slots_option;

			// Ensure we always work with an array
			if (!is_array($slots)) {
				$slots = [];
			}
			$last_id = get_option('pinaka_pos_slot_last_id', 0) + 1;
		
			$slots[] = [
				'id'            => $last_id,
				'restaurant_id' => intval($_POST['restaurant_id']),
				'type'          => sanitize_text_field($_POST['slot_type']),
				'time'          => sanitize_text_field($_POST['reservation_time']), // saved in 12-hr format
			];
			
			update_option('pinaka_pos_slots', $slots);
			update_option('pinaka_pos_slot_last_id', $last_id);

			echo '<div class="notice notice-success is-dismissible"><p>Slot saved successfully.</p></div>';

			// Redirect after saving to reload the page with updated slots
			wp_safe_redirect(admin_url('admin.php?page=pinaka-pos-settings&tab=tab8'));
			exit;
		}
	}

}

add_action('wp_ajax_delete_custom_role', function() {
	check_ajax_referer('delete_custom_role_nonce');

	if (!current_user_can('manage_options')) {
		wp_send_json_error(['message' => 'Permission denied']);
	}

	$role = sanitize_text_field($_POST['role']);

	if (!$role) {
		wp_send_json_error(['message' => 'Invalid role']);
	}

	remove_role($role);

	// Remove saved settings also
	$all_settings = get_option('pinaka_pos_menu_settings', []);
	unset($all_settings[$role]);
	update_option('pinaka_pos_menu_settings', $all_settings);

	wp_send_json_success(['message' => 'Role deleted successfully']);
});

add_action('wp_ajax_update_custom_role', function() {

    check_ajax_referer('create_custom_role_action', 'create_custom_role_nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // ✅ Get & sanitize inputs
    $old_role_key = sanitize_text_field($_POST['edit_role'] ?? '');
    $new_role_key = sanitize_text_field($_POST['new_role_key'] ?? '');
    $new_role_name = sanitize_text_field($_POST['new_role_name'] ?? '');

    // ✅ Validate
    if (empty($old_role_key) || empty($new_role_key) || empty($new_role_name)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $wp_roles = wp_roles();

    // ✅ Check old role exists
    if (!isset($wp_roles->roles[$old_role_key])) {
        wp_send_json_error(['message' => 'Old role not found']);
    }

    $old_role = get_role($old_role_key);

    // ==============================
    // ✅ CASE 1: ONLY NAME CHANGE
    // ==============================
    if ($old_role_key === $new_role_key) {

        $wp_roles->roles[$old_role_key]['name'] = $new_role_name;
        $wp_roles->role_names[$old_role_key] = $new_role_name;

        update_option($wp_roles->role_key, $wp_roles->roles);

        wp_send_json_success(['message' => 'Role updated successfully']);
    }

    // ==============================
    // ✅ CASE 2: ROLE KEY CHANGE
    // ==============================

    // ❗ Check new role already exists
    if (get_role($new_role_key)) {
        wp_send_json_error(['message' => 'New role key already exists']);
    }

    // ✅ Step 1: Create new role with same capabilities
    add_role($new_role_key, $new_role_name, $old_role->capabilities);

    // ✅ Step 2: Move users to new role
    $users = get_users(['role' => $old_role_key]);

    foreach ($users as $user) {
        $user->remove_role($old_role_key);
        $user->add_role($new_role_key);
    }

    // ✅ Step 3: Remove old role
    remove_role($old_role_key);

    // ✅ Step 4: Update your custom settings
    $all_settings = get_option('pinaka_pos_menu_settings', []);

    if (isset($all_settings[$old_role_key])) {
        $all_settings[$new_role_key] = $all_settings[$old_role_key];
        unset($all_settings[$old_role_key]);
        update_option('pinaka_pos_menu_settings', $all_settings);
    }

    wp_send_json_success(['message' => 'Role key updated successfully']);

});