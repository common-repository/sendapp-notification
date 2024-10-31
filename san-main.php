<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
define('SAN_FUNCTION', 'SAN_connection');



class san_main
{



	protected static $instance = NULL;
	public static function get_instance()
	{
		if (NULL === self::$instance)
			self::$instance = new self;

		return self::$instance;
	}

	public $ui;
	public function __construct()
	{
		$this->ui = new san_ui();
		$this->log = new SAN_Woosend_Logger();
		add_action('init', array($this, 'san_textdomain'));
		add_action('wp_ajax_get_base_url', 'get_base_url');
		add_action('wp_ajax_nopriv_get_base_url', 'get_base_url');

		function get_base_url()
		{
			echo plugin_dir_url(__FILE__);
			wp_die(); // Questo è richiesto per terminare immediatamente l'esecuzione dello script dopo l'invio della risposta
		}

		add_action('admin_init', array($this, 'san_register_settings'));
		add_action('admin_init', array($this, 'san_custom_order_status'));
		add_filter('manage_edit-shop_order_columns', array($this, 'san_wa_manual_new_columns'));
		add_action('manage_shop_order_posts_custom_column', array($this, 'san_wa_manual_manage_columns'), 10, 2);
		add_action('admin_menu', array($this, 'san_admin_menu'));
		add_action('admin_notices', array($this, 'san_admin_notices'));

		add_action('woocommerce_order_status_pending', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_failed', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_on-hold', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_completed', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_processing', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_refunded', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_order_status_cancelled', array($this, 'san_wa_process_states'), 10);
		add_action('woocommerce_thankyou', array($this, 'san_wa_order_receive'), 10, 1);
		add_action('woocommerce_new_customer_note', array($this, 'san_wa_process_note'), 10);
		add_action('woocommerce_before_checkout_form', array($this, 'woo_phone_intltel_input'));
		add_action('followup_cron_hook', array($this, 'followup_order'));
		add_action('followup_cron_hook_2', array($this, 'followup_order_2'));
		add_action('followup_cron_hook_3', array($this, 'followup_order_3'));
		add_action('aftersales_cron_hook', array($this, 'aftersales_order'));
		add_action('abandoned_cron_hook', array($this, 'abandoned_order'));
		add_filter('cron_schedules', array($this, 'followup_cron_schedule'));

		if (!wp_next_scheduled('followup_cron_hook')) {
			wp_schedule_event(time(), 'every_half_hours', 'followup_cron_hook');
		}
		if (!wp_next_scheduled('followup_cron_hook_2')) {
			wp_schedule_event(time(), 'every_half_hours', 'followup_cron_hook_2');
		}
		if (!wp_next_scheduled('followup_cron_hook_3')) {
			wp_schedule_event(time(), 'every_half_hours', 'followup_cron_hook_3');
		}
		if (!wp_next_scheduled('aftersales_cron_hook')) {
			wp_schedule_event(time(), 'every_half_hours', 'aftersales_cron_hook');
		}
		if (!wp_next_scheduled('abandoned_cron_hook')) {
			wp_schedule_event(time(), 'every_half_hours', 'abandoned_cron_hook');
		}

		add_action('admin_bar_menu', array($this, 'status_on_admin_bar'), 100);

		add_filter('san_editor_panels', array($this, 'san_editor_panels'));
		add_action('san_after_save', array($this, 'san_save_form'));
		add_action('san_before_send_mail', array($this, 'san_wa_handler'));

		add_action('wp_enqueue_scripts', array($this, 'cf_phone_intltel_input'));

		add_action('edd_purchase_form_user_info_fields', array($this, 'edd_buyer_phone_field'));
		add_action('edd_checkout_error_checks', array($this, 'edd_validate_checkout_field'), 10, 2);
		add_filter('edd_payment_meta', array($this, 'edd_save_phone_field'));
		add_action('edd_payment_personal_details_list', array($this, 'edd_show_phone_on_personal_details'), 10, 2);
		add_action('edd_payment_receipt_before', array($this, 'edd_send_wa_after_purchase'));
		add_action('edd_complete_purchase', array($this, 'edd_send_wa_on_complete'));
		add_action('edd_before_checkout_cart', array($this, 'edd_phone_intltel_input'));
		add_action('wp_ajax_yc_share_posts', array($this, 'yc_share_posts_ajax_callback'));
		add_action('wp_ajax_yc_share_products', array($this, 'yc_share_products_ajax_callback'));
		add_action('wp_ajax_yc_send_customer_msg', array($this, 'yc_send_customer_msg_ajax_callback'));
		add_action('wp_ajax_yc_get_wccust', array($this, 'yc_get_customers_ajax_callback'));
	}

	public function is_plugin_active($plugin)
	{
		return in_array($plugin, (array) get_option('active_plugins', array()));
	}

	public function san_textdomain()
	{
		load_plugin_textdomain('woo-send', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	public function san_register_settings()
	{
		register_setting('san_storage_notifications', 'san_notifications');
		register_setting('san_storage_instances', 'san_instances');
	}






	public function san_admin_menu()
	{
		$config = get_option('san_notifications');
		$my_page_1 = add_menu_page(
			__('SendApp Notification Settings', 'woo-send'),
			__('SendApp', 'woo-send'),
			'manage_options',
			'san',
			array(
				$this->ui,
				'admin_page'
			),
			plugin_dir_url(__FILE__) . 'assets/img/menu.png'
		);
		add_action('load-' . $my_page_1, array($this, 'san_load_admin_js'));
		add_submenu_page(
			'san',
			'SendApp Notification Settings',
			'Notification',
			'manage_options',
			'san'
		);
		$my_page_2 = add_submenu_page(
			'san',
			__('SendApp Message Logs New', 'woo-send'),
			__('Message Logs', 'woo-send'),
			'manage_options',
			'san-message-log',
			array(
				$this->ui,
				'logs_page'
			)
		);
		add_action('load-' . $my_page_2, array($this, 'san_load_admin_js'));
		if (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order') {
			if (isset($_GET['id'])) {
				$post_id = sanitize_text_field($_GET['id']);
				$result = $this->san_wa_process_states($post_id);
?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html(sprintf(__('Resend Message %s', 'woo-send'), esc_html($result))); ?></p>
				</div>
			<?php
			}
		}
	}

	public function san_load_admin_js()
	{
		add_action('admin_enqueue_scripts', array($this, 'san_admin_assets'));
	}

	public function san_admin_assets()
	{
		wp_enqueue_style('san-admin-style', plugins_url('assets/css/san-admin-style.css', __FILE__), array(), '1.1.4');
		wp_enqueue_style('san-admin-emojicss', plugins_url('assets/css/emojionearea.min.css', __FILE__));
		wp_enqueue_style('san-admin-telcss', plugins_url('assets/css/intlTelInput.css', __FILE__));
		wp_enqueue_style('san-admin-share-style', plugins_url('assets/css/san-admin-share.css', __FILE__));

		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-accordion');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('san-admin-teljs', plugins_url('assets/js/intlTelInput.js', __FILE__));
		wp_enqueue_script('san-admin-emojijs', plugins_url('assets/js/emojionearea.min.js', __FILE__));
		wp_enqueue_script('san-jquery-modal', plugins_url('assets/js/jquery.modal.min.js', __FILE__));
		wp_enqueue_script('san-admin-js', plugins_url('assets/js/san-admin-js.js', __FILE__), array(), '1.1.4');
		wp_enqueue_script('san-admin-share', plugins_url('assets/js/san-admin-share.js', __FILE__));
		wp_localize_script(
			'san-admin-share',
			'ycsc',
			array(
				'ajaxurl' => admin_url('admin-ajax.php'),
			)
		);

		wp_enqueue_style('ycselect2css', plugins_url('/assets/css/select2.css', __FILE__));
		wp_enqueue_script('ycselect2src', plugins_url('/assets/js/select2.js', __FILE__));

		wp_enqueue_media();

		remove_action('admin_print_styles', 'print_emoji_styles');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
	}

	public function san_admin_notices()
	{
		$screen = get_current_screen();
		$sid    = $screen->id;


		if ($sid == 'toplevel_page_san') {
			?>
			<style>
				#wpfooter {
					display: none;
				}
			</style>
		<?php
		}

		if (isset($_GET['settings-updated']) && $sid == 'toplevel_page_san') {
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e('All changes has been saved!', 'woo-send'); ?></p>
			</div>
			<?php
		}

		if ($sid == 'sendapp_page_san-message-log') {
			if (isset($_GET['clear'])) {
				$this->log->clear('woosend');
			?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e('Message logs has been cleared!', 'woo-send'); ?></p>
				</div>
			<?php
			}

			if (isset($_POST['san_resend_wa'])) {
				$phone = sanitize_text_field($_POST['san_resend_phone']);
				$message = wp_kses_post($_POST['san_resend_message']);
				$image = filter_var($_POST['san_resend_image'], FILTER_SANITIZE_URL);
				$result = $this->san_wa_send_msg('', $phone, $message, $image, '');
			?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html(sprintf(__('Resend Message %s', 'woo-send'), $result)); ?></p>
				</div>
			<?php
			}
		}

		if (isset($_POST['san_send_test'])) {
			if (!empty($_POST['san_test_number'])) {
				$number = sanitize_text_field($_POST['san_test_number']);
				$message = wp_kses_post($_POST['san_test_message']);
				$image = filter_var($_POST['san_test_image'], FILTER_SANITIZE_URL);
				$result = $this->san_wa_send_msg('', $number, $message, $image, '');
			?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html(sprintf(__('Send Message %s', 'sendapp-notification'), $result)); ?></p>
				</div>
			<?php
			}
		}
	}

	public function san_wa_manual_new_columns($columns)
	{
		$columns['notification'] = __('Notification');
		return $columns;
	}

	public function san_wa_manual_manage_columns($column_name, $id)
	{
		global $wpdb, $post;
		if ("notification" == $column_name) {
			echo '<a href="' . esc_url(admin_url('edit.php?post_type=shop_order&id=' . $post->ID)) . '" class="button wc-action-button">Resend WhatsApp</a>';
		}
	}

	public function san_custom_order_status()
	{
		if ($this->is_plugin_active('woocommerce/woocommerce.php')) {
			global $custom_status_list_temp;
			$custom_status_list = wc_get_order_statuses();
			$custom_status_list_temp = array();
			$original_status = array(
				'pending',
				'failed',
				'on-hold',
				'processing',
				'completed',
				'refunded',
				'cancelled',
			);
			foreach ($custom_status_list as $key => $status) {
				$status_name = str_replace("wc-", "", $key);
				if (!in_array($status_name, $original_status)) {
					$custom_status_list_temp[$status] = $status_name;
					add_action('woocommerce_order_status_' . $status_name, array($this, 'san_wa_process_states'), 10);
				}
			}
		}
	}

	public function san_wa_process_states($order)
	{
		global $woocommerce, $custom_status_list_temp;
		$order = new WC_Order($order);
		$status = $order->get_status();
		$status_list = array(
			'pending' => __('Pending', 'woo-send'),
			'failed' => __('Failed', 'woo-send'),
			'on-hold' => __('Receive', 'woo-send'),
			'processing' => __('Processing', 'woo-send'),
			'completed' => __('Completed', 'woo-send'),
			'refunded' => __('Refunded', 'woo-send'),
			'cancelled' => __('Cancelled', 'woo-send')
		);
		foreach ($status_list as $status_lists => $translations) if ($status == $status_lists) $status = $translations;
		$config = get_option('san_notifications');
		$phone = $order->get_billing_phone();
		if ($status == 'Receive') {
			$msg = $this->san_wa_process_variables($config['order_onhold'], $order, '');
			$img = $config['order_onhold_img'];
		} else if ($status == __('Pending', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_pending'], $order, '');
			$img = $config['order_pending_img'];
		} else if ($status == __('Failed', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_failed'], $order, '');
			$img = $config['order_failed_img'];
		} else if ($status == __('Processing', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_processing'], $order, '');
			$img = $config['order_processing_img'];
		} else if ($status == __('Completed', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_completed'], $order, '');
			$img = $config['order_completed_img'];
		} else if ($status == __('Refunded', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_refunded'], $order, '');
			$img = $config['order_refunded_img'];
		} else if ($status == __('Cancelled', 'woo-send')) {
			$msg = $this->san_wa_process_variables($config['order_cancelled'], $order, '');
			$img = $config['order_cancelled_img'];
		}
		$custom_status_list = $custom_status_list_temp;
		if (!empty($custom_status_list)) {
			foreach ($custom_status_list as $status_name => $custom_status) {
				if (strtolower($status) == $custom_status) {
					$msg = $this->san_wa_process_variables($config['order_' . $custom_status], $order, '');
					$img = $config['order_' . $custom_status . '_img'];
				}
			}
		}

		/* Admin Notification */
		if ($status == 'Receive' || $status == __('Cancelled', 'woo-send') || $status == __('Completed', 'woo-send')) {
			$msg_admin = $this->san_wa_process_variables($config['admin_onhold'], $order, '');
			$img_admin = $config['admin_onhold_img'];
			$phone_admin = preg_replace('/[^0-9]/', '', $config['admin_onhold_number']);
			if (!empty($msg_admin)) $this->san_wa_send_msg($config, $phone_admin, $msg_admin, $img_admin, '');
		}

		if (!empty($msg)) {
			$result = $this->san_wa_send_msg($config, $phone, $msg, $img, '');
			return $result;
		};
	}

	public function san_wa_default_country_code($phone)
	{
		$config = get_option('san_notifications');
		$country_code = preg_replace('/[^0-9]/', '', $config['default_country']);
		if (!$country_code) {
			return $phone;
		}
		if (strpos($phone, $country_code) === 0) {
			return $phone;
		} else {
			if (strpos($phone, '0') === 0) {
				return preg_replace('/^0/', $country_code, $phone);
			} else {
				return $country_code . $phone;
			}
		}
	}

	public function san_wa_order_receive($order_id)
	{
		if (!$order_id) {
			return;
		}
		global $woocommerce;
		$order = new WC_Order($order_id);
		$config = get_option('san_notifications');
		$phone = $order->get_billing_phone();
		$msg = $this->san_wa_process_variables($config['customer_neworder'], $order, '');
		$img = $config['customer_neworder_img'];
		if (!empty($msg)) $this->san_wa_send_msg($config, $phone, $msg, $img, '');
	}

	public function san_wa_process_note($data)
	{
		global $woocommerce;
		$order = new WC_Order($data['order_id']);
		$config = get_option('san_notifications');
		$phone = $order->get_billing_phone();
		$this->san_wa_send_msg($config, $phone, $this->san_wa_process_variables($config['order_note'], $order, '', wptexturize($data['customer_note'])), $config['order_note_img'], '');
	}

	public function remove_emoji($text)
	{
		return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F9B0})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FF}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FE}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FD}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FC}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{1F3FB}\x{200D}\x{2642}\x{FE0F})|[\x{1F46E}\x{1F9B8}\x{1F9B9}\x{1F482}\x{1F477}\x{1F473}\x{1F471}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{1F3FF}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FE}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FD}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FC}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{1F3FB}\x{200D}\x{2695}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FF})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FE})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FD})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FC})|[\x{1F476}\x{1F9D2}\x{1F466}\x{1F467}\x{1F9D1}\x{1F468}\x{1F469}\x{1F9D3}\x{1F474}\x{1F475}\x{1F46E}\x{1F575}\x{1F482}\x{1F477}\x{1F934}\x{1F478}\x{1F473}\x{1F472}\x{1F9D5}\x{1F9D4}\x{1F471}\x{1F935}\x{1F470}\x{1F930}\x{1F931}\x{1F47C}\x{1F385}\x{1F936}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F647}\x{1F926}\x{1F937}\x{1F486}\x{1F487}\x{1F6B6}\x{1F3C3}\x{1F483}\x{1F57A}\x{1F9D6}\x{1F9D7}\x{1F9D8}\x{1F6C0}\x{1F6CC}\x{1F574}\x{1F3C7}\x{1F3C2}\x{1F3CC}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{26F9}\x{1F3CB}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93D}\x{1F93E}\x{1F939}\x{1F933}\x{1F4AA}\x{1F9B5}\x{1F9B6}\x{1F448}\x{1F449}\x{261D}\x{1F446}\x{1F595}\x{1F447}\x{270C}\x{1F91E}\x{1F596}\x{1F918}\x{1F919}\x{1F590}\x{270B}\x{1F44C}\x{1F44D}\x{1F44E}\x{270A}\x{1F44A}\x{1F91B}\x{1F91C}\x{1F91A}\x{1F44B}\x{1F91F}\x{270D}\x{1F44F}\x{1F450}\x{1F64C}\x{1F932}\x{1F64F}\x{1F485}\x{1F442}\x{1F443}](?:\x{1F3FB})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6F9}\x{1F910}-\x{1F93A}\x{1F93C}-\x{1F93E}\x{1F940}-\x{1F945}\x{1F947}-\x{1F970}\x{1F973}-\x{1F976}\x{1F97A}\x{1F97C}-\x{1F9A2}\x{1F9B0}-\x{1F9B9}\x{1F9C0}-\x{1F9C2}\x{1F9D0}-\x{1F9FF}]/u', '', $text);
	}

	public function san_wa_send_msg($config, $phone, $msg, $img, $resend)
	{
		global $result;
		$config = get_option('san_notifications');
		$phone = preg_replace('/[^0-9]/', '', $phone);
		$phone = $this->san_wa_default_country_code($phone);
		if (substr($phone, 0, 2) === "52") {
			if (substr($phone, 0, 3) !== "521") {
				$phone = '521' . substr($phone, 2);
			}
		}
		$msg = $this->spintax($msg);
		$instances = get_option('san_instances');
		$dashboard_prefix = $instances['dashboard_prefix'];
		$access_token = $instances['access_token'];
		$instance_id = $instances['instance_id'];
		if (empty($img)) {
			$url = 'https://app.sendapp.cloud/api/send?number=' . $phone . '&type=text&message=' . urlencode($msg) . '&instance_id=' . $instance_id . '&access_token=' . $access_token;
			$rest_response = wp_remote_retrieve_body(wp_remote_get($url, array('sslverify' => false, 'timeout' => 60)));
		} else {
			$url = 'https://app.sendapp.cloud/api/send?number=' . $phone . '&type=media&message=' . urlencode($msg) . '&media_url=' . $img . '&instance_id=' . $instance_id . '&access_token=' . $access_token;
			$rest_response = wp_remote_retrieve_body(wp_remote_get($url, array('sslverify' => false, 'timeout' => 60)));
		}
		$current_datetime = date(get_option('date_format') . ' ' . get_option('time_format'));
		$result = json_decode($rest_response, true);
		$this->log->add('woosend', '<tr><td>' . $current_datetime . '</td><td class="log-phone">' . $phone . '</td><td class="log-msg"><div>' . $msg . '</div></td><td class="log-img">' . $img . '</td><td>' . $result["status"] . '</td>							<td>
		<form method="post" id="resend-form">
			<input type="hidden" name="san_resend_phone" value="' . $phone . '">
			<input type="hidden" name="san_resend_message" value="' . $msg . '">
			<input type="hidden" name="san_resend_image" value="' . $img . '">
			<input type="submit" name="san_resend_wa" class="button log-resend" value="Resend Message">
		</form>
	</td></tr>');
		if (empty($result["message"])) {
			$url = 'https://app.sendapp.cloud/api/reconnect?instance_id=' . $instance_id . '&access_token=' . $access_token;
			$rest_response = wp_remote_retrieve_body(wp_remote_get($url, array('sslverify' => false, 'timeout' => 60)));
		}
		return $result["status"];
	}

	public function san_wa_encoding($msg)
	{
		return htmlentities($msg, ENT_QUOTES, "UTF-8");
	}

	public function san_wa_process_variables($msg, $order, $variables, $note = '')
	{
		global $wpdb, $woocommerce;
		$san_wa = array("id", "order_key", "billing_first_name", "billing_last_name", "billing_company", "billing_address_1", "billing_address_2", "billing_city", "billing_postcode", "billing_country", "billing_state", "billing_email", "billing_phone", "shipping_first_name", "shipping_last_name", "shipping_company", "shipping_address_1", "shipping_address_2", "shipping_city", "shipping_postcode", "shipping_country", "shipping_state", "shipping_method", "shipping_method_title", "bacs_account", "payment_method", "payment_method_title", "order_subtotal", "order_discount", "cart_discount", "order_tax", "order_shipping", "order_shipping_tax", "order_total", "status", "shop_name", "currency", "cust_note", "note", "product", "product_name", "dpd", "unique_transfer_code", "order_date", "order_link");
		$variables = str_replace(array("\r\n", "\r"), "\n", $variables);
		$variables = explode("\n", $variables);
		preg_match_all("/%(.*?)%/", $msg, $search);
		$currency = get_woocommerce_currency_symbol();
		foreach ($search[1] as $variable) {
			$variable = strtolower($variable);
			// if (!in_array($variable, $san_wa) && !in_array($variable, $variables)) continue;
			if ($variable != "id" && $variable != "shop_name" && $variable != "currency" && $variable != "shipping_method" && $variable != "cust_note" && $variable != "note" && $variable != "bacs_account" && $variable != "order_subtotal" && $variable != "order_shipping" && $variable != "product" && $variable != "product_name" && $variable != "dpd" && $variable != "unique_transfer_code" && $variable != "order_date" && $variable != "order_link") {
				if (in_array($variable, $san_wa)) {
					$msg = str_replace("%" . $variable . "%", get_post_meta($order->get_id(), '_' . $variable, true), $msg);
				} else {
					if (strlen($order->order_custom_fields[$variable][0]) == 0) {
						$msg = str_replace("%" . $variable . "%", get_post_meta($order->get_id(), $variable, true), $msg);
					} else {
						$msg = str_replace("%" . $variable . "%", $order->order_custom_fields[$variable][0], $msg);
					}
				}
			} else if ($variable == "id") $msg = str_replace("%" . $variable . "%", $order->get_id(), $msg);
			else if ($variable == "shop_name") $msg = str_replace("%" . $variable . "%", get_bloginfo('name'), $msg);
			else if ($variable == "currency") $msg = str_replace("%" . $variable . "%", html_entity_decode($currency), $msg);
			else if ($variable == "cust_note") $msg = str_replace("%" . $variable . "%", $order->get_customer_note(), $msg);
			else if ($variable == "shipping_method") $msg = str_replace("%" . $variable . "%", $order->get_shipping_method(), $msg);
			else if ($variable == "note") $msg = str_replace("%" . $variable . "%", $note, $msg);
			else if ($variable == "order_subtotal") $msg = str_replace("%" . $variable . "%", number_format($order->get_subtotal(), wc_get_price_decimals()), $msg);
			else if ($variable == "order_shipping") $msg = str_replace("%" . $variable . "%", number_format(get_post_meta($order->get_id(), '_order_shipping', true), wc_get_price_decimals()), $msg);
			else if ($variable == "dpd") {
				$order_id = $order->get_id();
				$table_name = $wpdb->prefix . 'dpd_orders';
				$parcels = $wpdb->get_results("SELECT id, parcel_number, date FROM $table_name WHERE order_id = $order_id AND (order_type != 'amazon_prime' OR order_type IS NULL ) AND status !='trash'");
				if (count($parcels) > 0) {
					foreach ($parcels as $parcel) {
						$dpd = $parcel->parcel_number;
					}
				}
				$msg = str_replace("%" . $variable . "%", $dpd, $msg);
			} else if ($variable == "product") {
				$product_items = '';
				$order = wc_get_order($order->get_id());
				$i = 0;
				foreach ($order->get_items() as $item_id => $item_data) {
					$i++;
					$new_line = ($i > 1) ? '
' : '';
					$product = $item_data->get_product();
					$product_name = $product->get_name();
					$item_quantity = $item_data->get_quantity();
					$item_total = $item_data->get_total();
					$product_items .= $new_line . $i . '. ' . $product_name . ' x ' . $item_quantity . ' = ' . $currency . ' ' . number_format($item_total, wc_get_price_decimals());
				}
				$msg = str_replace("%" . $variable . "%", html_entity_decode($product_items), $msg);
			} else if ($variable == "product_name") {
				$product_items = '';
				$order = wc_get_order($order->get_id());
				$i = 0;
				foreach ($order->get_items() as $item_id => $item_data) {
					$i++;
					$new_line = ($i > 1) ? '
' : '';
					$product = $item_data->get_product();
					$product_name = $product->get_name();
					$product_items .= $new_line . $i . '. ' . $product_name;
				}
				$msg = str_replace("%" . $variable . "%", html_entity_decode($product_items), $msg);
			} else if ($variable == "unique_transfer_code") {
				$mtotal = get_post_meta($order->get_id(), '_order_total', true);
				$mongkir = get_post_meta($order->get_id(), '_order_shipping', true);
				$kode_unik = $mtotal - $mongkir;
				$msg = str_replace("%" . $variable . "%", $kode_unik, $msg);
			} else if ($variable == "order_date") {
				$order = wc_get_order($order->get_id());
				$date = $order->get_date_created();
				$date_format = get_option('date_format');
				$time_format = get_option('time_format');
				$msg = str_replace("%" . $variable . "%", date($date_format . ' ' . $time_format, strtotime($date)), $msg);
			} else if ($variable == "order_link") {
				$order_received_url = wc_get_endpoint_url('order-received', $order->get_id(), wc_get_checkout_url());
				$order_received_url = add_query_arg('key', $order->get_order_key(), $order_received_url);
				$msg = str_replace("%" . $variable . "%", $order_received_url, $msg);
			} else if ($variable == "bacs_account") {
				$gateway    = new WC_Gateway_BACS();
				$country    = WC()->countries->get_base_country();
				$locale     = $gateway->get_country_locale();
				$bacs_info  = get_option('woocommerce_bacs_accounts');
				$sort_code_label = isset($locale[$country]['sortcode']['label']) ? $locale[$country]['sortcode']['label'] : __('Sort code', 'woocommerce');
				$i = -1;
				$bacs_items = '';
				if ($bacs_info) {
					foreach ($bacs_info as $account) {
						$i++;
						$new_line = ($i > 0) ? '

' : '';
						$account_name   = esc_attr(wp_unslash($account['account_name']));
						$bank_name      = esc_attr(wp_unslash($account['bank_name']));
						$account_number = esc_attr($account['account_number']);
						$sort_code      = esc_attr($account['sort_code']);
						$iban_code      = esc_attr($account['iban']);
						$bic_code       = esc_attr($account['bic']);
						$bacs_items .=  $new_line . '🏦 ' . $bank_name . '
' . '🤵 ' . $account_name . '
' . '��?? ' . $account_number;
					}
				}
				$msg = str_replace("%" . $variable . "%", $bacs_items, $msg);
			}
		}
		return $msg;
	}

	public function spintax($str)
	{
		return preg_replace_callback("/{(.*?)}/", function ($match) {
			$words = explode("|", $match[1]);
			return $words[array_rand($words)];
		}, $str);
	}

	public function followup_order()
	{
		global $woocommerce;
		$config = get_option('san_notifications');
		$customer_orders = wc_get_orders(array(
			'limit'    => -1,
			'date_after' => date('Y-m-d', strtotime('-14 days')),
			'status'   => 'on-hold'
		));
		if (isset($customer_orders)) {
			$followup_send = [];
			foreach ($customer_orders as $order => $single_order) {
				$today = date_create(date('Y-m-d H:i:s'));
				$purchase_date = date_create($single_order->date_created->date('Y-m-d H:i:s'));
				$ts1 = strtotime($today->format('Y-m-d H:i:s'));
				$ts2 = strtotime($purchase_date->format('Y-m-d H:i:s'));
				$day_range = abs($ts1 - $ts2) / 3600;
				$followup_day = $config['followup_onhold_day'];

				if (empty($followup_day))
					$followup_day = 24;
				if ($day_range >= $followup_day) {
					$sent = get_post_meta($single_order->ID, 'followup', true);
					if (empty($sent) || $sent == null) {
						update_post_meta($single_order->ID, 'followup', '0');
					}
					if ($sent == '0') {
						echo esc_html($single_order->ID . ' = ' . $sent . '<br>');
						$followup_send[] = $single_order->ID;
					}
				}
			}
			if (count($followup_send) != 0) {
				foreach ($followup_send as $flw => $foll_id) {
					$order = new WC_Order($foll_id);
					$msg = $this->san_wa_process_variables($config['followup_onhold'], $order, '');
					$img = $config['followup_onhold_img'];
					$phone = $order->get_billing_phone();
					if (!empty($msg))
						$this->san_wa_send_msg($config, $phone, $msg, $img, '');
					update_post_meta($foll_id, 'followup', '1');
				}
			}
		}
	}

	public function followup_order_2()
	{
		global $woocommerce;
		$config = get_option('san_notifications');
		$customer_orders = wc_get_orders(array(
			'limit'    => -1,
			'date_after' => date('Y-m-d', strtotime('-14 days')),
			'status'   => 'on-hold'
		));
		if (isset($customer_orders)) {
			$followup_send_2 = [];
			foreach ($customer_orders as $order => $single_order) {
				$today = date_create(date('Y-m-d H:i:s'));
				$purchase_date = date_create($single_order->date_created->date('Y-m-d H:i:s'));
				$ts1 = strtotime($today->format('Y-m-d H:i:s'));
				$ts2 = strtotime($purchase_date->format('Y-m-d H:i:s'));
				$day_range = abs($ts1 - $ts2) / 3600;
				$followup_day = $config['followup_onhold_day_2'];

				if (empty($followup_day))
					$followup_day = 48;
				if ($day_range >= $followup_day) {
					$sent = get_post_meta($single_order->ID, 'followup_2', true);
					if (empty($sent) || $sent == null) {
						update_post_meta($single_order->ID, 'followup_2', '0');
					}
					if ($sent == '0') {
						echo esc_html($single_order->ID . ' = ' . $sent . '<br>');
						$followup_send_2[] = $single_order->ID;
					}
				}
			}
			if (count($followup_send_2) != 0) {
				foreach ($followup_send_2 as $flw => $foll_id) {
					$order = new WC_Order($foll_id);
					$msg = $this->san_wa_process_variables($config['followup_onhold_2'], $order, '');
					$img = $config['followup_onhold_img_2'];
					$phone = $order->get_billing_phone();
					if (!empty($msg))
						$this->san_wa_send_msg($config, $phone, $msg, $img, '');
					update_post_meta($foll_id, 'followup_2', '1');
				}
			}
		}
	}

	public function followup_order_3()
	{
		global $woocommerce;
		$config = get_option('san_notifications');
		$customer_orders = wc_get_orders(array(
			'limit'    => -1,
			'date_after' => date('Y-m-d', strtotime('-14 days')),
			'status'   => 'on-hold'
		));
		if (isset($customer_orders)) {
			$followup_send_3 = [];
			foreach ($customer_orders as $order => $single_order) {
				$today = date_create(date('Y-m-d H:i:s'));
				$purchase_date = date_create($single_order->date_created->date('Y-m-d H:i:s'));
				$ts1 = strtotime($today->format('Y-m-d H:i:s'));
				$ts2 = strtotime($purchase_date->format('Y-m-d H:i:s'));
				$day_range = abs($ts1 - $ts2) / 3600;
				$followup_day = $config['followup_onhold_day_3'];

				if (empty($followup_day))
					$followup_day = 72;
				if ($day_range >= $followup_day) {
					$sent = get_post_meta($single_order->ID, 'followup_3', true);
					if (empty($sent) || $sent == null) {
						update_post_meta($single_order->ID, 'followup_3', '0');
					}
					if ($sent == '0') {
						echo esc_html($single_order->ID . ' = ' . $sent . '<br>');
						$followup_send_3[] = $single_order->ID;
					}
				}
			}
			if (count($followup_send_3) != 0) {
				foreach ($followup_send_3 as $flw => $foll_id) {
					$order = new WC_Order($foll_id);
					$msg = $this->san_wa_process_variables($config['followup_onhold_3'], $order, '');
					$img = $config['followup_onhold_img_3'];
					$phone = $order->get_billing_phone();
					if (!empty($msg))
						$this->san_wa_send_msg($config, $phone, $msg, $img, '');
					update_post_meta($foll_id, 'followup_3', '1');
				}
			}
		}
	}

	public function aftersales_order()
	{
		global $woocommerce;
		$config = get_option('san_notifications');
		$customer_orders = wc_get_orders(array(
			'limit'    => -1,
			'date_after' => date('Y-m-d', strtotime('-14 days')),
			'status'   => 'completed'
		));
		if (isset($customer_orders)) {
			$aftersales_send = [];
			foreach ($customer_orders as $order => $single_order) {
				$today = date_create(date('Y-m-d H:i:s'));
				$purchase_date = date_create($single_order->date_created->date('Y-m-d H:i:s'));
				$paid_date_raw = date_format(date_create(get_post_meta($single_order->ID, '_completed_date', true)), "Y-m-d H:i:s");
				$paid_date_obj = new DateTime();
				$paid_date = $paid_date_obj->createFromFormat('Y-m-d H:i:s', $paid_date_raw);
				$ts1 = strtotime($today->format('Y-m-d H:i:s'));
				$ts2 = strtotime($paid_date->format('Y-m-d H:i:s'));
				$day_range = abs($ts1 - $ts2) / 3600;

				$aftersales_day = $config['followup_aftersales_day'];
				if (empty($aftersales_day))
					$aftersales_day = 72;
				if ($day_range >= $aftersales_day) {
					$sent = get_post_meta($single_order->ID, 'aftersales', true);
					if (empty($sent) || $sent == null) {
						update_post_meta($single_order->ID, 'aftersales', '0');
					}
					if ($sent == '0') {
						$aftersales_send[] = $single_order->ID;
					}
				}
			}
			if (count($aftersales_send) != 0) {
				foreach ($aftersales_send as $flw => $foll_id) {
					$order = new WC_Order($foll_id);
					$msg = $this->san_wa_process_variables($config['followup_aftersales'], $order, '');
					$img = $config['followup_aftersales_img'];
					$phone = $order->get_billing_phone();
					if (!empty($msg))
						$this->san_wa_send_msg($config, $phone, $msg, $img, '');
					update_post_meta($foll_id, 'aftersales', '1');
				}
			}
		}
	}

	public function abandoned_order()
	{
		if ($this->is_plugin_active('woo-save-abandoned-carts/cartbounty-abandoned-carts.php')) {
			global $wpdb;
			$config = get_option('san_notifications');
			$table_name = $wpdb->prefix . 'cartbounty';
			$ab_carts = $wpdb->get_results("SELECT * FROM $table_name WHERE other_fields != '1'");
			if (isset($ab_carts)) {
				foreach ($ab_carts as $ab_cart => $cart) {
					$id = $cart->id;
					$name = $cart->name;
					$surname = $cart->surname;
					$email = $cart->email;
					$phone = $cart->phone;
					$total = $cart->cart_total;
					$currency = $cart->currency;
					$today = date_create(date('Y-m-d H:i:s'));
					$abandoned_date_raw = date_format(date_create($cart->time), "Y-m-d H:i:s");
					$abandoned_date_obj = new DateTime();
					$abandoned_date = $abandoned_date_obj->createFromFormat('Y-m-d H:i:s', $abandoned_date_raw);
					$ts1 = strtotime($today->format('Y-m-d H:i:s'));
					$ts2 = strtotime($abandoned_date->format('Y-m-d H:i:s'));
					$day_range = round(abs($ts1 - $ts2) / 3600);
					$abandoned_day = $config['followup_abandoned_day'];
					$product_array = @unserialize($cart->cart_contents);
					if ($product_array) {
						$product_items = '';
						$i = 0;
						foreach ($product_array as $product) {
							$i++;
							$new_line = ($i > 1) ? '\n' : '';
							$product_name = $product['product_title'];
							$item_quantity =  $product['quantity'];
							$item_total = $product['product_variation_price'];
							$product_items .= $new_line . $i . '. ' . $product_name . ' x ' . $item_quantity . ' = ' . $currency . ' ' . $item_total;
						}
					}
					if (empty($abandoned_day))
						$abandoned_day = 24;
					if ($day_range >= $abandoned_day) {
						$replace_in_message = ["%billing_first_name%", "%billing_last_name%", "%billing_email%", "%billing_phone%", "%product%", "%order_total%", "%currency%"];
						$replace_with_message   = [$name, $surname, $email, $phone, $product_items, $total, $currency];
						$msg = str_replace($replace_in_message, $replace_with_message, $config['followup_abandoned']);
						$img = $config['followup_abandoned_img'];
						// Follow Up Abandoned Cart when status not shopping
						$type = $cart->type;
						$time = $cart->time;
						$status = $cart->status;
						$cart_time = strtotime($time);
						$date = date_create(current_time('mysql', false));
						$current_time = strtotime(date_format($date, 'Y-m-d H:i:s'));
						if ($cart_time > $current_time - 60 * 60 && $item['type'] != 1) {
							// Status is shopping
							// Do nothing
							// Source: woo-save-abandoned-carts/admin/class-cartbounty-admin-table.php:320
						} else {
							if (!empty($phone))
								$this->san_wa_send_msg($config, $phone, $msg, $img, '');
							$wpdb->update($table_name, array('other_fields' => '1'), array('id' => $id));
						}
					}
				}
			}
		}
	}

	public function followup_cron_schedule($schedules)
	{
		$schedules['every_six_hours'] = array(
			'interval' => 21600,
			'display'  => __('Every 6 hours'),
		);
		$schedules['every_half_hours'] = array(
			'interval' => 1800,
			'display'  => __('Every 30 minutes'),
		);
		return $schedules;
	}

	public function status_on_admin_bar($wp_admin_bar)
	{
		$args = array(
			'id' => 'san-admin-link',
			'title' => 'SendApp',
			'href' => admin_url() . 'admin.php?page=san-v2',
			'meta' => array(
				'class' => 'san-admin-link'
			)
		);

		$wp_admin_bar->add_node($args);

		$args = array(
			'id' => 'san-sub-link-2',
			'title' => 'Visit SendApp Dashboard',
			'href' => '//sendapp.live/whatsapp-api-sendapp-connect/',
			'parent' => 'san-admin-link',
			'meta' => array(
				'class' => 'san-sub-link',
				'title' => 'View Dashboard',
				'target' => '_blank'
			)
		);
		$wp_admin_bar->add_node($args);
	}

	public $san_data;
	public function san_editor_panels($panels)
	{
		$new_page = array(
			'woosend' => array(
				'title'    => __('SendApp', 'woo-send'),
				'callback' => array($this, 'san_setup_form')
			)
		);
		$panels = array_merge($panels, $new_page);
		return $panels;
	}

	public function san_setup_form($form)
	{
		$san_options = get_option('san_wa_' . (method_exists($form, 'id') ? $form->id() : $form->id));
		if (empty($san_options) || !is_array($san_options)) {
			$san_options = array('phone' => '', 'message' => '', 'image' => '');
		}
		$san_options['form'] = $form;
		include_once dirname(__FILE__) . '/san-san.php';
	}

	public function san_save_form($form)
	{
		$form_id = method_exists($form, 'id') ? $form->id() : $form->id;
		$form_data = sanitize_text_field($_POST['san-wa']);
		update_option('san_wa_' . $form_id, $form_data);
	}

	public function get_san_tagS_To_String($value, $form)
	{
		if (function_exists('san_mail_replace_tags')) {
			$return = san_mail_replace_tags($value);
		} elseif (method_exists($form, 'replace_mail_tags')) {
			$return = $form->replace_mail_tags($value);
		} else {
			return;
		}
		return $return;
	}

	public function san_wa_handler($form)
	{
		$san_options = get_option('san_wa_' . $form->id());
		$this->set_san_data();
		if ($san_options['message'] && $san_options['phone']) {
			$phone = $this->get_san_tagS_To_String($san_options['phone'], $form);
			$msg = $this->get_san_tagS_To_String($san_options['message'], $form);
			$img = $this->get_san_tagS_To_String($san_options['image'], $form);
			/*
			$img = preg_replace_callback('/%([a-zA-Z0-9._-]+)%/', function($matches) {
				foreach ($matches as $item) {
					return $this->san_data[$item];
				}
			}, $san_options['image']);
			*/
			$this->san_wa_send_msg('', $phone, $msg, $img, '');
		}
	}

	private function set_san_data()
	{
		foreach ($_POST as $index => $key) {
			if (is_array($key)) {
				$this->san_data[$index] = implode(', ', $key);
			} else {
				$this->san_data[$index] = $key;
			}
		}
	}

	public function woo_phone_intltel_input()
	{
		$config = get_option('san_notifications');
		if (!$config['default_country']) {
			wp_enqueue_style('san-admin-telcss', plugins_url('assets/css/intlTelInput.css', __FILE__));
			wp_enqueue_script('san-admin-teljs', plugins_url('assets/js/intlTelInput.js', __FILE__));
			wp_enqueue_script('san-admin-wootelinput', plugins_url('assets/js/woo-telinput.js', __FILE__));
			
			// Aggiungi questo dopo aver messo in coda woo-telinput.js
			wp_localize_script('san-admin-wootelinput', 'ycsc', array(
				'ajaxurl' => admin_url('admin-ajax.php')
			));
		}
	}
	

	public function cf_phone_intltel_input()
	{
		global $post;
		if (!is_admin()) {
			if (has_shortcode($post->post_content, 'caldera_form')) {
				wp_enqueue_style('san-admin-telcss', plugins_url('assets/css/intlTelInput.css', __FILE__));
				wp_enqueue_script('san-admin-teljs', plugins_url('assets/js/intlTelInput.js', __FILE__));
				wp_enqueue_script('san-admin-cftelinput', plugins_url('assets/js/cf-telinput.js', __FILE__));
			}
		}
	}

	public function edd_phone_intltel_input()
	{
		wp_enqueue_style('san-admin-telcss', plugins_url('assets/css/intlTelInput.css', __FILE__));
		wp_enqueue_script('san-admin-teljs', plugins_url('assets/js/intlTelInput.js', __FILE__));
		wp_enqueue_script('san-admin-eddtelinput', plugins_url('assets/js/edd-telinput.js', __FILE__));
	}

	public function yc_share_posts_ajax_callback()
	{

		$posts = array_map('sanitize_text_field', explode(',', $_POST['ycposts']));
		$this->yc_ajax_prepare_call($posts);
	}

	private function yc_ajax_prepare_call($posts)
	{

		$nums = sanitize_text_field($_POST['ycnums']);
		$msg = sanitize_textarea_field($_POST['ycmsg']);
		$inc_img = sanitize_text_field($_POST['ycimg']);

		$a_nums     = array();

		if ($nums) {
			$e_nums = explode("\r\n", $nums);
			foreach ($e_nums as $e_num) {

				$number_data    = explode(',', $e_num);
				$name           = isset($number_data[1]) ? $number_data[1] : '';
				$number         = reset($number_data);

				if ($number) {

					if ($name) {
						$namefl = explode(' ', $name);
						$fname = isset($namefl[0]) ? $namefl[0] : '';
						$lname = isset($namefl[1]) ? $namefl[1] : '';
					}

					$a_nums[] = array('number' => $number, 'fname' => $fname, 'lname' => $lname);
				}
			}
		}

		if (!empty($a_nums)) {
			$count = 0;

			foreach ($a_nums as $a_num) {

				$img = '';

				if (isset($a_num['number']) && ctype_digit($a_num['number']) && !empty($a_num['number'])) {

					$lmsg = $msg;

					$rf = !empty($a_num['fname']) ? $a_num['fname'] : '';
					$rl = !empty($a_num['lname']) ? $a_num['lname'] : '';
					$ftags  = array('%fname%', '%lname%');
					$rtags  = array($rf, $rl);
					$lmsg   = str_replace($ftags, $rtags, $msg);

					$status = $this->san_wa_send_msg('', $a_num['number'], $lmsg, '', '');

					if ($status == 'success') {
						$count++;

						foreach ($posts as $post_id) {

							$url = get_the_title($post_id) . "\n" . get_permalink($post_id);


							if ($inc_img) {
								$img = get_the_post_thumbnail_url($post_id);
							}


							$this->san_wa_send_msg('', $a_num['number'], $url, $img, '');
						}
					}
				}
			}

			$count = empty($count) ? '' : $count;
			wp_die($count);
		}

		wp_die();
	}

	public function yc_share_products_ajax_callback()
	{

		$products = array_map('sanitize_text_field', explode(',', $_POST['ycproducts']));
		$this->yc_ajax_prepare_call($products);
	}



	public function yc_send_customer_msg_ajax_callback()
	{

		$nums = sanitize_text_field($_POST['yccustomers']);
		$msg = sanitize_textarea_field($_POST['ycmsg']);
		$img = sanitize_text_field($_POST['yccustimg']);

		$a_nums     = array();

		if ($nums) {
			$e_nums = explode("\r\n", $nums);
			foreach ($e_nums as $e_num) {

				$number_data    = explode(',', $e_num);
				$name           = isset($number_data[1]) ? $number_data[1] : '';
				$number         = reset($number_data);

				if ($number) {

					if ($name) {
						$namefl = explode(' ', $name);
						$fname = isset($namefl[0]) ? $namefl[0] : '';
						$lname = isset($namefl[1]) ? $namefl[1] : '';
					}

					$a_nums[] = array('number' => $number, 'fname' => $fname, 'lname' => $lname);
				}
			}
		}

		if (!empty($a_nums)) {
			$count = 0;

			foreach ($a_nums as $a_num) {

				if (isset($a_num['number']) && ctype_digit($a_num['number']) && !empty($a_num['number'])) {

					$lmsg = $msg;

					$ftags  = array('%fname%', '%lname%');
					$rf = !empty($a_num['fname']) ? $a_num['fname'] : '';
					$rl = !empty($a_num['lname']) ? $a_num['lname'] : '';

					$rtags  = array($rf, $rl);
					$lmsg   = str_replace($ftags, $rtags, $msg);

					$status = $this->san_wa_send_msg('', $a_num['number'], $lmsg, $img, '');

					if ($status == 'success') {
						$count++;
					}
				}
			}

			$count = empty($count) ? '' : $count;
			wp_die($count);
		}

		wp_die();
	}

	public function yc_get_customers_ajax_callback()
	{

		$imp_cust = sanitize_text_field($_POST['san_imp_cust']);

		if ($imp_cust) {
			$ui = new san_ui;
			$customers = $ui->san_get_customers();

			if ($customers) {
				$get_cust = '';

				foreach ($customers as $customer) {

					$phone = $customer->billing_phone ? $customer->billing_phone : '';

					if ($phone) {

						$get_cust .= $phone . ',' . $customer->first_name . ' ' . $customer->last_name . "\n";
					}
				}
				wp_die($get_cust);
			}
		}
		wp_die();
	}

	public function edd_buyer_phone_field()
	{
		$config = get_option('san_notifications');
		if (!empty($config['edd_notification'])) {
			$fields = array(
				array('phone', 'Phone Number', 'Insert your phone number to get order notification via WhatsApp.')
			);
			foreach ($fields as $field) {
				$field_id = $field[0];
				$field_label = $field[1];
				$field_desc = $field[2];
			?>
				<p id="edd-<?php echo esc_attr($field_id); ?>-wrap">
					<label class="edd-label" for="edd-<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
					<span class="edd-description"><?php echo esc_html($field_desc); ?></span>
					<input class="edd-input" type="text" name="edd_<?php echo esc_attr($field_id); ?>" id="edd-<?php echo esc_attr($field_id); ?>" style="padding-right:6px;padding-left:52px;width:100%;" />
				</p>
<?php
			}
		}
	}

	public function edd_validate_checkout_field($valid_data, $data)
	{
		$additional_required_fields = array(
			array('phone', 'Please enter a valid phone number.'),
		);
		foreach ($additional_required_fields as $field) {
			$field_id = $field[0];
			$field_error = $field[1];
			if (empty($data['edd_' . $field_id . ''])) {
				edd_set_error('invalid_' . $field_id . '', $field_error);
			}
		}
	}

	public function edd_save_phone_field($payment_meta)
	{
		if (did_action('edd_purchase')) {
			$additional_required_fields = array('phone');
			foreach ($additional_required_fields as $field) {
				$payment_meta[$field] = isset($_POST['edd_' . $field]) ? sanitize_text_field($_POST['edd_' . $field]) : '';
			}
		}
		return $payment_meta;
	}

	public function edd_show_phone_on_personal_details($payment_meta, $user_info)
	{
		$fields = array(
			array('phone', 'Phone'),
		);
		foreach ($fields as $field) {
			$field_id = $field[0];
			$field_label = $field[1];
			echo '<div class="column-container">';
			if (!empty($payment_meta[$field_id])) {
				echo '<div class="column"><strong>' . esc_html($field_label) . '</strong>: ' . esc_html($payment_meta[$field_id]) . '</div>';
			}
			echo '</div>';
		}
	}

	public function edd_send_wa_after_purchase($payment)
	{
		$config = get_option('san_notifications');
		if (!empty($config['edd_notification'])) {
			$payment_meta = edd_get_payment_meta($payment->ID);
			$payment_ids = edd_get_payment_number($payment->ID);
			$payment_status = edd_get_payment_status($payment, true);
			$payment_method = edd_get_gateway_checkout_label(edd_get_payment_gateway($payment->ID));
			$date = date_i18n(get_option('date_format'), strtotime($payment_meta['date']));
			$user = edd_get_payment_meta_user_info($payment->ID);
			$email = edd_get_payment_user_email($payment->ID);
			$subtotal = edd_payment_subtotal($payment->ID);
			$total_price = edd_payment_amount($payment->ID);
			$cart = edd_get_payment_meta_cart_details($payment->ID, true);
			if ($cart) {
				$product_items = '';
				$i = 0;
				foreach ($cart as $key => $item) {
					$i++;
					$new_line = ($i > 1) ? '\n' : '';
					$product_items .= $new_line . $i . '. ' . $item['name'];
				}
			}
			$phone = $payment_meta['phone'];
			$buyer_wa  = $config['edd_notification'];
			$replace_in_wa_buyer = ["%payment_id%", "%payment_status%", "%payment_method%", "%date%", "%currency%", "%product%", "%subtotal_price%", "%total_price%", "%site_name%", "%first_name%", "%last_name%", "%email%"];
			$replace_with_buyer   = [$payment_ids, $payment_status, $payment_method, $date, $payment_meta['currency'], $product_items, $subtotal, $total_price, get_bloginfo('name'), $user['first_name'], $user['last_name'], $email];
			$buyer_wa = str_replace($replace_in_wa_buyer, $replace_with_buyer, $buyer_wa);
			$msg = $buyer_wa;
			$img = $config['edd_notification_img'];
			if (!empty($msg) && $payment_status == 'Pending')
				$this->san_wa_send_msg($config, $phone, $msg, $img, '');
		}
	}

	public function edd_send_wa_on_complete($payment_id)
	{
		$config = get_option('san_notifications');
		if (!empty($config['edd_notification_complete'])) {
			$payment_meta = edd_get_payment_meta($payment_id);
			$payment_ids = edd_get_payment_number($payment_id);
			$payment_status = edd_get_payment_status($payment_id, true);
			$payment_method = edd_get_gateway_checkout_label(edd_get_payment_gateway($payment_id));
			$date = date_i18n(get_option('date_format'), strtotime($payment_meta['date']));
			$user = edd_get_payment_meta_user_info($payment_id);
			$email = edd_get_payment_user_email($payment_id);
			$subtotal = edd_payment_subtotal($payment_id);
			$total_price = edd_payment_amount($payment_id);
			$cart = edd_get_payment_meta_cart_details($payment_id, true);
			if ($cart) {
				$product_items = '';
				$i = 0;
				foreach ($cart as $key => $item) {
					$i++;
					$new_line = ($i > 1) ? '\n' : '';
					$product_items .= $new_line . $i . '. ' . $item['name'];
				}
			}
			$phone = $payment_meta['phone'];
			$buyer_wa  = $config['edd_notification_complete'];
			$replace_in_wa_buyer = ["%payment_id%", "%payment_status%", "%payment_method%", "%date%", "%currency%", "%product%", "%subtotal_price%", "%total_price%", "%site_name%", "%first_name%", "%last_name%", "%email%"];
			$replace_with_buyer   = [$payment_ids, $payment_status, $payment_method, $date, $payment_meta['currency'], $product_items, $subtotal, $total_price, get_bloginfo('name'), $user['first_name'], $user['last_name'], $email];
			$buyer_wa = str_replace($replace_in_wa_buyer, $replace_with_buyer, $buyer_wa);
			$msg = $buyer_wa;
			$img = $config['edd_notification_complete_img'];
			if (!empty($msg))
				$this->san_wa_send_msg($config, $phone, $msg, $img, '');
		}
	}
}




add_action('elementor_pro/init', 'el_san_addon');
function el_san_addon()
{
	require(dirname(__FILE__) . '/san-elementor.php');
	$whats_action = new SAN_Whatsapp_Action_After_Submit;
	\ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($whats_action->get_name(), $whats_action);
}
