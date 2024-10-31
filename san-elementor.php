<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class SAN_Whatsapp_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {
	    
	public function get_name() {
		return 'woosend';
	}
	public function get_label() {
		return __( 'WooSend', 'woo-send' );
	}
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_whatsapp-redirect',
			[
				'label' => __( 'SendApp', 'woo-send' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);
		$widget->add_control(
			'whatsapp_to',
			[
				'label' => __( 'WhatsApp Phone', 'woo-send' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( '62899999999', 'woo-send' ),
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-whats-phone-direction-ltr',
				'description' => __( 'Type WhatsApp Number with country code (e.g: 62XXXXXXXX) or use number field shortcode.', 'woo-send' ),
			]
		);
		$widget->add_control(
			'whatsapp_message',
			[
				'label' => __( 'WhatsApp Message', 'woo-send' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Type your own text or use available fields shortcode.', 'woo-send' ),
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-whats-direction-ltr',
				'description' => __( 'Insert your own message with available fields shortcode.', 'woo-send' ),
			]
		);
		$widget->add_control(
			'whatsapp_img',
			[
				'label' => __( 'WhatsApp Image (Optional)', 'woo-send' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( '.jpg/.png', 'woo-send' ),
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-whats-direction-ltr',
				'description' => __( 'Insert image url or image field shortcode if needed (optional).', 'woo-send' ),
			]
		);
		$widget->end_controls_section();
	}
	public function on_export( $element ) {
		unset(
			$element['settings']['whatsapp_to'],
			$element['settings']['whatsapp_message'],
			$element['settings']['whatsapp_img']
		);
		return $element;
	}
	public function run( $record, $ajax_handler ) {
		$whatsapp_to = $record->get_form_settings( 'whatsapp_to' );
		$whatsapp_to = $record->replace_setting_shortcodes( $whatsapp_to, true );
		$whatsapp_message = $record->get_form_settings( 'whatsapp_message' );
		$whatsapp_message = urldecode($record->replace_setting_shortcodes( $whatsapp_message, true ));
		$whatsapp_img = $record->get_form_settings( 'whatsapp_img' );
		$whatsapp_img = $record->replace_setting_shortcodes( $whatsapp_img, true );
		if ( ! empty( $whatsapp_message ) ) {
			wnt_main::get_instance()->wnt_wa_send_msg( '', $whatsapp_to, $whatsapp_message, $whatsapp_img, '');
		}
	}
}
?>