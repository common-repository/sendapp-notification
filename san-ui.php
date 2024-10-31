<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
class san_ui
{

	public function __construct()
	{
		$this->notif  = get_option('san_notifications');
		$this->instances  = get_option('san_instances');
	}

	public function is_plugin_active($plugin)
	{
		return in_array($plugin, (array) get_option('active_plugins', array()));
	}

	public function admin_page()
	{
?>
		<div class="wrap" id="san-wrap">
			<h1>
				<?php echo esc_html(get_admin_page_title()); ?>
			</h1>
			<div class="form-wrapper">
				<div class="san-tab-wrapper">
					<ul class="nav-tab-wrapper woo-nav-tab-wrapper">
						<li class="nav-tab nav-tab-active"><a href="#notification">Notification Message</a></li>
						<li class="nav-tab"><a href="#followup">Follow-Up Message</a></li>
						<li class="nav-tab"><a href="#share">Share Post</a></li>
						<li class="nav-tab"><a href="#wccustomers">Marketing</a></li>
						<li class="nav-tab"><a href="#other">Other Integration</a></li>
						<li class="nav-tab"><a href="#help">Help</a></li>
					</ul>
					<form method="post" action="options.php">
						<div class="wp-tab-panels" id="notification">
							<?php
							$this->notification_settings();
							?>
						</div>
						<div class="wp-tab-panels" id="followup" style="display: none;">
							<?php
							$this->followup_settings();
							?>
						</div>

						<div class="wp-tab-panels" id="share" style="display: none;">
							<?php
							$this->wp_posts_products();
							?>
						</div>
						<div class="wp-tab-panels" id="wccustomers" style="display: none;">
							<?php
							$this->san_wc_customers();
							?>
						</div>
						<div class="wp-tab-panels" id="other" style="display: none;">
							<?php
							$this->other_settings();
							?>
						</div>
					</form>
					<div class="wp-tab-panels" id="help" style="display: none;">
						<?php
						$this->help_info();
						?>
					</div>
				</div>
				<div class="info">
					<?php
					$this->setup_info();
					?>
				</div>
			</div>
		</div>
	<?php
	}

	public function notification_settings()
	{
		if ($this->is_plugin_active('woocommerce/woocommerce.php')) {
			$status_list = wc_get_order_statuses();
			$status_list_temp = array();
			$original_status = array(
				'pending',
				'failed',
				'on-hold',
				'processing',
				'completed',
				'refunded',
				'cancelled',
			);
			foreach ($status_list as $key => $status) {
				$status_name = str_replace("wc-", "", $key);
				if (!in_array($status_name, $original_status)) {
					$status_list_temp[$status] = $status_name;
				}
			}
			$status_list = $status_list_temp;
		}
	?>
		<?php settings_fields('san_storage_notifications'); ?>
		<table class="form-table san-table">
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[default_country]">
						<?php _e('Default Country Code:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<input type="text" name="san_notifications[default_country]" placeholder="Your country code" class="regular-text" value="<?php echo esc_attr(stripcslashes(isset($this->notif['default_country']) ? $this->notif['default_country'] : '')); ?>">
					<p><em><?php _e('Insert country code only if your customer from a single country. This also will remove country detection library on checkout page. Leave blank if your customer from many countries.', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[admin_onhold]">
						<?php _e('Admin Notification (Order):', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<p class="san-admin-number">
						<input type="text" name="san_notifications[admin_onhold_number]" placeholder="Admin Number with country code" class="admin_number regular-text admin_number" value="<?php echo esc_attr(stripcslashes(isset($this->notif['admin_onhold_number']) ? $this->notif['admin_onhold_number'] : '')); ?>">
					</p>
					<textarea class="san-emoji" id="san_notifications[admin_onhold]" name="san_notifications[admin_onhold]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['admin_onhold']) ? $this->notif['admin_onhold'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="admin_onhold_img" value="Upload Image">
						<input type="text" name="san_notifications[admin_onhold_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text admin_onhold_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['admin_onhold_img']) ? $this->notif['admin_onhold_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[customer_neworder]">
						<?php _e('Order - New (Thankyou Page):', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[customer_neworder]" name="san_notifications[customer_neworder]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['customer_neworder']) ? $this->notif['customer_neworder'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="customer_neworder_img" value="Upload Image">
						<input type="text" name="san_notifications[customer_neworder_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text customer_neworder_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['customer_neworder_img']) ? $this->notif['customer_neworder_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_onhold]">
						<?php _e('Order - On Hold:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_onhold]" name="san_notifications[order_onhold]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_onhold']) ? $this->notif['order_onhold'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_onhold_img" value="Upload Image">
						<input type="text" name="san_notifications[order_onhold_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_onhold_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_onhold_img']) ? $this->notif['order_onhold_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_processing]">
						<?php _e('Order - Processing:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_processing]" name="san_notifications[order_processing]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_processing']) ? $this->notif['order_processing'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_processing_img" value="Upload Image">
						<input type="text" name="san_notifications[order_processing_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_processing_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_processing_img']) ? $this->notif['order_processing_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_completed]">
						<?php _e('Order - Completed:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_completed]" name="san_notifications[order_completed]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_completed']) ? $this->notif['order_completed'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_completed_img" value="Upload Image">
						<input type="text" name="san_notifications[order_completed_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_completed_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_completed_img']) ? $this->notif['order_completed_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_pending]">
						<?php _e('Order - Pending:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_pending]" name="san_notifications[order_pending]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_pending']) ? $this->notif['order_pending'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_pending_img" value="Upload Image">
						<input type="text" name="san_notifications[order_pending_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_pending_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_pending_img']) ? $this->notif['order_pending_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_failed]">
						<?php _e('Order - Failed:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_failed]" name="san_notifications[order_failed]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_failed']) ? $this->notif['order_failed'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_failed_img" value="Upload Image">
						<input type="text" name="san_notifications[order_failed_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_failed_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_failed_img']) ? $this->notif['order_failed_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_refunded]">
						<?php _e('Order - Refunded:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_refunded]" name="san_notifications[order_refunded]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_refunded']) ? $this->notif['order_refunded'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_refunded_img" value="Upload Image">
						<input type="text" name="san_notifications[order_refunded_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_refunded_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_refunded_img']) ? $this->notif['order_refunded_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_cancelled]">
						<?php _e('Order - Cancelled:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_cancelled]" name="san_notifications[order_cancelled]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_cancelled']) ? $this->notif['order_cancelled'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_cancelled_img" value="Upload Image">
						<input type="text" name="san_notifications[order_cancelled_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_cancelled_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_cancelled_img']) ? $this->notif['order_cancelled_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[order_note]">
						<?php _e('Order - Notes:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[order_note]" name="san_notifications[order_note]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_note']) ? $this->notif['order_note'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_note_img" value="Upload Image">
						<input type="text" name="san_notifications[order_note_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_note_img" value="<?php echo esc_url(stripcslashes(isset($this->notif['order_note_img']) ? $this->notif['order_note_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<?php if (!empty($status_list)) : ?>
				<?php foreach ($status_list as $status_name => $custom_status) : ?>
					<tr valign="top">
						<th scope="row">
							<label for="san_notifications[order_<?php echo esc_attr($custom_status); ?>]">
								<?php echo esc_html(sprintf(__('Order - %s:', 'woo-send'), $status_name)); ?>
							</label>
						</th>
						<td><textarea class="san-emoji" id="san_notifications[order_<?php echo esc_attr($custom_status); ?>]" name="san_notifications[order_<?php echo esc_attr($custom_status); ?>]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['order_' . $custom_status]) ? $this->notif['order_' . $custom_status] : '')); ?></textarea>
							<p class="san-upload-img">
								<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="order_<?php echo esc_attr($custom_status); ?>_img" value="Upload Image">
								<input type="text" name="san_notifications[order_<?php echo esc_attr($custom_status); ?>_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text order_<?php echo esc_attr($custom_status); ?>_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['order_' . $custom_status . '_img']) ? $this->notif['order_' . $custom_status . '_img'] : '')); ?>">
							</p>
							<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
		<footer class="san-panel-footer">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>">
		</footer>
	<?php
	}

	public function wp_posts_products()
	{

		settings_fields('san_storage_notifications');

		$san    = get_option('san_notifications');
		$nums   = isset($san['san-wa-nums']) ? $san['san-wa-nums'] : '';
		$msg    = isset($san['san-wa-msg']) ? $san['san-wa-msg'] : '';
		$pi = isset($san['san_inc_post_image']) ? 'checked="checked"' : '';
		$sposts = isset($san['wp_posts_list']) ? $san['wp_posts_list'] : '';
		$spr    = isset($san['wp_products_list']) ? $san['wp_products_list'] : '';

		$args = array(
			'post_type'     => 'post',
			'orderby'       => 'name',
			'post_status'   => 'publish',
			'order'         => 'ASC',
			'posts_per_page' => -1
		);

		$argsp = array(
			'post_type'     => 'product',
			'orderby'       => 'name',
			'post_status'   => 'publish',
			'order'         => 'ASC',
			'posts_per_page' => -1
		);

		$posts = new WP_Query($args);
		wp_reset_postdata();

		$products = new WP_Query($argsp);
		wp_reset_postdata();
	?>

		<table class="form-table san-table">

			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san-wa-nums]">
						<?php _e('Numbers:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-wa-ta" id="san_wa_nums" name="san_notifications[san-wa-nums]" rows="5" spellcheck="false"><?php echo esc_textarea($nums); ?></textarea>
					<p>Format: 4423454875, Alex Anderson (one contact per line)</p>
				</td>
				<td> <button class="button button-secondary wsm-import-cust"><?php _e('Import', 'woo-send'); ?></button></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san-wa-msg]">
						<?php _e('Message:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji san-wa-ta" id="san_wa_msg" name="san_notifications[san-wa-msg]" rows="5"><?php echo esc_textarea($msg); ?></textarea>
					<p><em>Use tags: <b>%fname%</b> (for first name), <b>%lname%</b> (for last name)</em></p>
					<p>&nbsp;</p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san_inc_post_image]">
						<?php _e('Include post/product image:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="san_notifications[san_inc_post_image]" id="san_inc_post_image" value="1" <?php echo $pi; ?> />
				</td>

			</tr>
			<tr class="san-wrps" valign="top">
				<th></th>
				<td class="san-warnings"></td>
			</tr>
			<tr class="san-post-tr" valign="top">
				<th scope="row">
					<label for="san_notifications[san_posts_list]">
						<?php _e('Posts:', 'woo-send'); ?>
					</label>
				</th>
				<td class="san-post-list">

					<?php
					echo '<select multiple class="wp-posts-list san-wa-ta" id="wp_posts_list" name="san_notifications[wp_posts_list][]">';

					foreach ($posts->posts as $value) {
						$sl = '';

						if ($sposts && in_array($value->ID, $sposts)) {
							$sl = 'selected';
						}

						echo '<option value="' . esc_attr($value->ID) . '" ' . $sl . '>' . esc_html($value->post_title) . '</option>';
					}

					echo '</select>';

					?>

				</td>
				<td>
					<input type="hidden" id="plugin-base-url" value="<?php echo esc_attr(plugin_dir_url(__FILE__)); ?>">


					<button class="button button-secondary wsm-share-posts"><?php _e('Share posts', 'woo-send'); ?></button>
				</td>
			</tr>
			<tr class="san-wrpr" valign="top">
				<th></th>
				<td class="san-warnings"></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san-products]">
						<?php _e('Products:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<?php
					echo '<select multiple class="wp-products-list san-wa-ta" id="wp_products_list" name="san_notifications[wp_products_list][]">';

					foreach ($products->posts as $value) {

						$sl = '';

						if ($spr && in_array($value->ID, $spr)) {
							$sl = 'selected';
						}

						echo '<option value="' . esc_attr($value->ID) . '" ' . $sl . '>' . esc_html($value->post_title) . '</option>';
					}

					echo '</select>';

					?>

				</td>
				<td>
					<button class="button button-secondary wsm-share-products"><?php _e('Share products', 'woo-send'); ?></button>
				</td>
			</tr>
		</table>

		<footer class="san-panel-footer">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>">
		</footer>

	<?php

	}

	public function san_get_customers()
	{

		$customer_q = new WP_User_Query(
			array(
				'role' => 'customer',
				'meta_query' => array(
					array(
						'key' => 'billing_phone',
						'compare' => '!=',
						'value' => ' '
					)
				)
			)
		);

		$customers = $customer_q->get_results();
		wp_reset_postdata();

		return $customers;
	}

	public function san_wc_customers()
	{

		settings_fields('san_storage_notifications');

		$san                = get_option('san_notifications');
		$saved_customers    = isset($san['wp_customer_listn']) ? $san['wp_customer_listn'] : '';
		$msg                = isset($san['san-cu-wa-msg']) ? $san['san-cu-wa-msg'] : '';
		$customers = $this->san_get_customers();

	?>
		<table class="form-table san-table">
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san-wa-nums]">
						<?php _e('Customers:', 'woo-send'); ?>
					</label>
				</th>
				<td style="width:450px;max-width:450px;">
					<textarea class="san-wa-ta" id="wp_customer_listn" name="san_notifications[wp_customer_listn]" rows="5" spellcheck="false"><?php echo esc_textarea($saved_customers); ?></textarea>
					<p>Format: 4423454875, Alex Anderson (one contact per line)</p>
				</td>
				<td> <button class="button button-secondary wsm-import-custn"><?php _e('Import', 'woo-send'); ?></button></td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[san-wc-msg]">
						<?php _e('Message:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji san-wa-ta" id="san-cu-wa-msg" name="san_notifications[san-cu-wa-msg]" rows="5"><?php echo esc_textarea($msg); ?></textarea>
					<p><em>Use tags: <b>%fname%</b> (for first name), <b>%lname%</b> (for last name)</em></p>
					<p>&nbsp;</p>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="san_cu_wa_msg" value="Upload Image">
						<input type="text" name="san_notifications[customer_msg_img]" id="customer_msg_img" placeholder="Image URL (Max 1 MB)" class="image_url regular-text san_cu_wa_msg" value="<?php echo esc_attr(stripcslashes(isset($this->notif['customer_msg_img']) ? $this->notif['customer_msg_img'] : '')); ?>">
					</p>
				</td>
			</tr>
			<tr class="san-wrcu" valign="top" style="display:none;">
				<th></th>
				<td class="san-warnings"></td>
			</tr>
			<tr valign="top">
				<th scope="row"></th>
				<td>
					<button class="button button-secondary wsm-send-wa"><?php _e('Send', 'woo-send'); ?></button>
				</td>
			</tr>
		</table>
		<footer class="san-panel-footer">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>">
		</footer>
	<?php
	}

	public function followup_settings()
	{
	?>
		<?php settings_fields('san_storage_notifications'); ?>
		<!-- san -->

		<div class="tabs">

			<input type="radio" name="tabs" id="tabone" checked="checked">
			<label for="tabone">Follow Up On-Hold Order #1</label>
			<div class="tab">
				<table class="form-table san-table">
					<tr valign="top">
						<th scope="row"> <label for="san_notifications[followup_onhold]">
								<?php _e('Follow Up On-Hold Order #1:', 'woo-send'); ?>
							</label>
						</th>
						<td>
							<textarea class="san-emoji" id="san_notifications[followup_onhold]" name="san_notifications[followup_onhold]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['followup_onhold']) ? $this->notif['followup_onhold'] : '')); ?></textarea>
							<p class="san-upload-img">
								<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="followup_onhold_img" value="Upload Image">
								<input type="text" name="san_notifications[followup_onhold_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text followup_onhold_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_onhold_img']) ? $this->notif['followup_onhold_img'] : '')); ?>">
							</p>
							<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
						</td>
					</tr>
					<tr valign="top" style="border-bottom: 1px solid #ccc;">
						<th scope="row"> <label for="san_notifications[followup_onhold_day]">
								<?php _e('Follow Up On-Hold Order After:', 'woo-send'); ?>
							</label>
						</th>
						<td><input id="san_notifications[followup_onhold_day]" name="san_notifications[followup_onhold_day]" type="number" cols="50" rows="5" placeholder="24" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_onhold_day']) ? $this->notif['followup_onhold_day'] : '')); ?>"> Hour(s)</td>
					</tr>
				</table>
			</div>

			<input type="radio" name="tabs" id="tabtwo">
			<label for="tabtwo">Follow Up On-Hold Order #2</label>
			<div class="tab">
				<table class="form-table san-table">
					<tr valign="top">
						<th scope="row"> <label for="san_notifications[followup_onhold_2]">
								<?php _e('Follow Up On-Hold Order #2:', 'woo-send'); ?>
							</label>
						</th>
						<td>
							<textarea class="san-emoji" id="san_notifications[followup_onhold_2]" name="san_notifications[followup_onhold_2]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['followup_onhold_2']) ? $this->notif['followup_onhold_2'] : '')); ?></textarea>
							<p class="san-upload-img">
								<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="followup_onhold_img_2" value="Upload Image">
								<input type="text" name="san_notifications[followup_onhold_img_2]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text followup_onhold_img_2" value="<?php echo esc_url(stripcslashes(isset($this->notif['followup_onhold_img_2']) ? $this->notif['followup_onhold_img_2'] : '')); ?>">
							</p>
							<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
						</td>
					</tr>
					<tr valign="top" style="border-bottom: 1px solid #ccc;">
						<th scope="row"> <label for="san_notifications[followup_onhold_day_2]">
								<?php _e('Follow Up On-Hold Order #2 After:', 'woo-send'); ?>
							</label>
						</th>
						<td><input id="san_notifications[followup_onhold_day_2]" name="san_notifications[followup_onhold_day_2]" type="number" placeholder="48" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_onhold_day_2']) ? $this->notif['followup_onhold_day_2'] : '')); ?>"> Hour(s)</td>
					</tr>
				</table>
			</div>

			<input type="radio" name="tabs" id="tabthree">
			<label for="tabthree">Follow Up On-Hold Order #3</label>
			<div class="tab">
				<table class="form-table san-table">
					<tr valign="top">
						<th scope="row"> <label for="san_notifications[followup_onhold_3]">
								<?php _e('Follow Up On-Hold Order #3:', 'woo-send'); ?>
							</label>
						</th>
						<td>
							<textarea class="san-emoji" id="san_notifications[followup_onhold_3]" name="san_notifications[followup_onhold_3]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['followup_onhold_3']) ? $this->notif['followup_onhold_3'] : '')); ?></textarea>
							<p class="san-upload-img">
								<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="followup_onhold_img_3" value="Upload Image">
								<input type="text" name="san_notifications[followup_onhold_img_3]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text followup_onhold_img_3" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_onhold_img_3']) ? $this->notif['followup_onhold_img_3'] : '')); ?>">
							</p>
							<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
						</td>
					</tr>
					<tr valign="top" style="border-bottom: 1px solid #ccc;">
						<th scope="row"> <label for="san_notifications[followup_onhold_day_3]">
								<?php _e('Follow Up On-Hold Order #3 After:', 'woo-send'); ?>
							</label>
						</th>
						<td><input id="san_notifications[followup_onhold_day_3]" name="san_notifications[followup_onhold_day_3]" type="number" placeholder="72" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_onhold_day_3']) ? $this->notif['followup_onhold_day_3'] : '')); ?>"> Hour(s)</td>
					</tr>
				</table>
			</div>

		</div>

		<table class="form-table san-table">
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[followup_aftersales]">
						<?php _e('Follow Up Completed Order:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[followup_aftersales]" name="san_notifications[followup_aftersales]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['followup_aftersales']) ? $this->notif['followup_aftersales'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="followup_aftersales_img" value="Upload Image">
						<input type="text" name="san_notifications[followup_aftersales_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text followup_aftersales_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_aftersales_img']) ? $this->notif['followup_aftersales_img'] : '')); ?>">
					</p>
					<p><em><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"> <label for="san_notifications[followup_aftersales_day]">
						<?php _e('Follow Up Completed Order After:', 'woo-send'); ?>
					</label>
				</th>
				<td><input id="san_notifications[followup_aftersales_day]" name="san_notifications[followup_aftersales_day]" type="number" placeholder="72" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_aftersales_day']) ? $this->notif['followup_aftersales_day'] : '')); ?>"> Hour(s)</td>
			</tr>
			<tr valign="top" style="border-top: 1px solid #ccc;">
				<th colspan="2">
					<?php echo sprintf(__('Enable abandoned cart notification by installing "<a href="%s">Cartbounty Abandoned Carts</a>" plugin', 'woo-send'), esc_url(admin_url('plugin-install.php?s=Cartbounty%20Abandoned%20Cart&tab=search&type=term'))); ?>
				</th>
			</tr>
			<?php
			if (is_plugin_active('woo-save-abandoned-carts/cartbounty-abandoned-carts.php')) {
			?>
				<tr valign="top">
					<th scope="row"> <label for="san_notifications[followup_abandoned]">
							<?php _e('Follow Up Abandoned Cart:', 'woo-send'); ?>
						</label>
					</th>
					<td>
						<textarea class="san-emoji" id="san_notifications[followup_abandoned]" name="san_notifications[followup_abandoned]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['followup_abandoned']) ? $this->notif['followup_abandoned'] : '')); ?></textarea>
						<p class="san-upload-img">
							<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="followup_abandoned_img" value="Upload Image">
							<input type="text" name="san_notifications[followup_abandoned_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text followup_abandoned_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_abandoned_img']) ? $this->notif['followup_abandoned_img'] : '')); ?>">
						</p>
						<p><em><b>Available tags:</b> %billing_first_name% %billing_last_name% %billing_email% %billing_phone% %product% %order_total% %currency%<br><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"> <label for="san_notifications[followup_abandoned_day]">
							<?php _e('Follow Up Abandoned Cart After:', 'woo-send'); ?>
						</label>
					</th>
					<td><input id="san_notifications[followup_abandoned_day]" name="san_notifications[followup_abandoned_day]" type="number" placeholder="24" value="<?php echo esc_attr(stripcslashes(isset($this->notif['followup_abandoned_day']) ? $this->notif['followup_abandoned_day'] : '')); ?>"> Hour(s)</td>
				</tr>
			<?php
			}
			?>
		</table>
		<footer class="san-panel-footer">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>">
		</footer>
	<?php
	}

	public function other_settings()
	{
	?>
		<?php settings_fields('san_storage_notifications'); ?>
		<table class="form-table san-table">
			<!-- san -->
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[edd_notification]">
						<?php _e('Easy Digital Downloads - New Order Notification:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[edd_notification]" name="san_notifications[edd_notification]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['edd_notification']) ? $this->notif['edd_notification'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="edd_notification_img" value="Upload Image">
						<input type="text" name="san_notifications[edd_notification_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text edd_notification_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['edd_notification_img']) ? $this->notif['edd_notification_img'] : '')); ?>">
					</p>
					<p><em><b>Available tags:</b> %site_name% %product% %currency% %subtotal_price% %total_price% %payment_id% %payment_status% %payment_method% %date% %first_name% %last_name% %email%<br><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="san_notifications[edd_notification_complete]">
						<?php _e('Easy Digital Downloads - Complete Order Notification:', 'woo-send'); ?>
					</label>
				</th>
				<td>
					<textarea class="san-emoji" id="san_notifications[edd_notification_complete]" name="san_notifications[edd_notification_complete]" cols="50" rows="5"><?php echo esc_textarea(stripcslashes(isset($this->notif['edd_notification_complete']) ? $this->notif['edd_notification_complete'] : '')); ?></textarea>
					<p class="san-upload-img">
						<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="edd_notification_complete_img" value="Upload Image">
						<input type="text" name="san_notifications[edd_notification_complete_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text edd_notification_complete_img" value="<?php echo esc_attr(stripcslashes(isset($this->notif['edd_notification_complete_img']) ? $this->notif['edd_notification_complete_img'] : '')); ?>">
					</p>
					<p><em><b>Available tags:</b> %site_name% %product% %currency% %subtotal_price% %total_price% %payment_id% %payment_status% %payment_method% %date% %first_name% %last_name% %email%<br><?php _e('Leave blank to deactivate. Spintax format example: {hi|hello|hola}', 'woo-send'); ?></em></p>
				</td>
			</tr>
		</table>
		<footer class="san-panel-footer">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>">
		</footer>
	<?php
	}

	public function help_info()
	{
	?>
		<div class="san-panel">
			<div class="san-panel-body">
				<p style="margin-bottom: 10px;"><strong>Below is list of available fields you can use on notification &amp; follow-up message:</strong></p>
				<div class="san-item-body">
					<div class="san-body-left">
						<strong>ORDER</strong><br />
						<strong>Order ID:</strong> %id%<br />
						<strong>Order Key:</strong> %order_key%<br />
						<strong>Order Date:</strong> %order_date%<br />
						<strong>Order Summary Link:</strong> %order_link%<br />

						<strong>Product List:</strong> %product%<br />
						<strong>Product Name:</strong> %product_name%<br />
						<strong>Order Discount:</strong> %order_discount%<br />
						<strong>Cart Discount:</strong> %cart_discount%<br />
						<strong>Tax:</strong> %order_tax%<br />
						<strong>Currency Symbol:</strong> %currency%<br />
						<strong>Subtotal Amount:</strong> %order_subtotal%<br />
						<strong>Total Amount:</strong> %order_total%<br />
						<strong>Unique Transfer Code:</strong> %unique_transfer_code%<br />
						<br />
						<strong>BILLING DETAILS</strong><br />
						<strong>First Name:</strong> %billing_first_name%<br />
						<strong>Last Name:</strong> %billing_last_name%<br />
						<strong>Company:</strong> %billing_company%<br />
						<strong>Address 1:</strong> %billing_address_1%<br />
						<strong>Address 2:</strong> %billing_address_2%<br />
						<strong>City:</strong> %billing_city%<br />
						<strong>Postcode:</strong> %billing_postcode%<br />
						<strong>Country:</strong> %billing_country%<br />
						<strong>Province:</strong> %billing_state%<br />
						<strong>Email:</strong> %billing_email%<br />
						<strong>Phone:</strong> %billing_phone%<br />
						<strong>Customer Note:</strong> %cust_note%<br />
					</div>
					<div class="san-body-right">
						<strong>SHIP TO DIFFERENT ADDRESS</strong><br />
						<strong>First Name:</strong> %shipping_first_name%<br />
						<strong>Last Name:</strong> %shipping_last_name%<br />
						<strong>Company:</strong> %shipping_company%<br />
						<strong>Address 1:</strong> %shipping_address_1%<br />
						<strong>Address 2:</strong> %shipping_address_2%<br />
						<strong>City:</strong> %shipping_city%<br />
						<strong>Postcode:</strong> %shipping_postcode%<br />
						<strong>Country:</strong> %shipping_country%<br />
						<strong>Province:</strong> %shipping_state%<br />
						<br />
						<strong>PAYMENT &amp; SHIPPING</strong><br />
						<strong>Shipping Method:</strong> %shipping_method%<br />
						<strong>Shipping Cost:</strong> %order_shipping%<br />
						<strong>Shipping Tax:</strong> %order_shipping_tax%<br />
						<strong>Payment Method:</strong> %payment_method_title%<br />
						<strong>Bank Account Info:</strong> %bacs_account%<br />
						<br />
						<strong>INFO</strong><br />
						<strong>Shop Name:</strong> %shop_name%<br />
						<strong>Order Note:</strong> %note%<br />
						<strong>Meta Key (custom):</strong> %meta_key_field%<br />
					</div>
				</div>
				<p style="margin-top: 15px; margin-bottom: 10px;"><strong>You can test the service by sending WhatsApp message here:</strong></p>
				<form method="post">
					<table class="form-table san-table">
						<tr valign="top">
							<th scope="row"> <label for="san_test_number">
									<?php _e('To:', 'woo-send'); ?>
								</label>
							</th>
							<td><input id="san_test_number" class="regular-text" name="san_test_number" type="text"></td>
						</tr>
						<tr valign="top">
							<th scope="row"> <label for="san_test_message">
									<?php _e('Message:', 'woo-send'); ?>
								</label>
							</th>
							<td>
								<textarea class="san-emoji" id="san_test_message" name="san_test_message" cols="50" rows="5"></textarea>
								<p class="san-upload-img">
									<input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="san-test-image" value="Upload Image">
									<input type="text" name="san_test_image" placeholder="Image URL (Max 1 MB)" class="image_url regular-text san-test-image">
								</p>
							</td>
						</tr>
					</table>
					<p class="submit san-submit" style="padding-bottom:0;">
						<input type="submit" name="san_send_test" class="button-primary" value="<?php _e('Send Message', 'woo-send'); ?>">
					</p>
				</form>
			</div>
		</div>
	<?php
	}

	public function setup_info()
	{
	?>
		<div class="info-body">
			<p class="head"><a href="https://sendapp.live/plugin-wordpress-woocommerce-whatsapp-notification/" title="SendApp" target="_blank"><img style="width:90%;" src="<?php echo esc_url(plugins_url('/assets/img/logo.png', __FILE__)); ?>"></a></p>
			<div>
				<form method="post" action="options.php" style="margin-bottom:15px;">
					<?php settings_fields('san_storage_instances'); ?>


					<label for="san_instances[access_token]">
						<?php _e('Access Token:', 'woo-send'); ?>
					</label>
					<input type="text" id="access_token" name="san_instances[access_token]" placeholder="Your Access Token" class="regular-text" value="<?php echo esc_attr(stripcslashes(isset($this->instances['access_token']) ? $this->instances['access_token'] : '')); ?>">
					<label for="san_instances[instance_id]">
						<?php _e('Instance ID:', 'woo-send'); ?>
					</label>
					<input type="text" id="instance_id" name="san_instances[instance_id]" placeholder="Your Instance ID" class="regular-text" value="<?php echo esc_attr(stripcslashes(isset($this->instances['instance_id']) ? $this->instances['instance_id'] : '')); ?>">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'woo-send'); ?>" style="margin-top:10px;">
					<a href="https://app.sendapp.cloud/signup" class="button-primary" target="_blank">Registration</a>
					<a href="https://app.sendapp.cloud/api/send?number=447868042053&type=text&message=test+message&instance_id=<?php echo $this->instances['instance_id']; ?>&access_token=<?php echo $this->instances['access_token']; ?>" class="button-primary" target="_blank">Test Connection</a>

				</form>
				<?php if (isset($this->instances['access_token']) && isset($this->instances['instance_id'])) : ?>

			</div>
			<div id="control-modal" class="modal"></div>
		<?php endif; ?>
		</div>
		</div>
	<?php
	}
	public function logs_page()
	{
		$logger = new SAN_Woosend_Logger();
		$customer_logs = $logger->get_log_file("woosend");
	?>
		<div class="wrap" id="san-wrap">
			<h1>
				<?php echo get_admin_page_title(); ?>
				<a href="<?php echo esc_url(admin_url('admin.php?page=san-message-log&clear=1')); ?>" class="button log-clear">Clear Logs</a>
			</h1>
			<div class="form-wrapper">
				<div class="san-tab-wrapper">
					<table class="wp-list-table widefat fixed striped table-view-list posts table-message-logs" style="margin:10px 0;">
						<thead>
							<tr>
								<th>Date</th>
								<th>WhatsApp Number</th>
								<th>Message</th>
								<th>Image Attachment</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php echo $customer_logs; ?>
						</tbody>
						<form method="post" id="resend-form">
									<input type="hidden" name="san_resend_phone">
									<input type="hidden" name="san_resend_message">
									<input type="hidden" name="san_resend_image">
									<input type="submit" name="san_resend_wa" class="button log-resend" style="display:none;" value="Resend Message">
						</form>
					</table>
				</div>
				<div class="info">
					<?php
					$this->setup_info();
					?>
				</div>
			</div>
		</div>
<?php
	}
}
