<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if (!isset($_POST['action'])) {
    echo '0';
    exit;
}

define('SAN_SHORTINIT', true);
define('SAN_WP_USE_THEMES', false);

// Verify nonce
if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'sendapp_nonce')) {
    echo '0';
    exit;
}

$connection = json_decode(get_option('wnt_connection'), true);
$obj = new stdClass();
$obj->slug = sanitize_text_field('sendapp/sendapp.php');
$obj->name = sanitize_text_field('SendApp');
$obj->plugin_name = sanitize_text_field('sendapp.php');
$obj->new_version = sanitize_text_field(substr($connection['data']['downloadable']['name'], 1));
$obj->url = sanitize_text_field('https://sendapp.live');
$obj->package = sanitize_text_field($connection['data']['downloadable']['url']);

switch ($_POST['action']) {
    case 'version':
        echo esc_html(serialize($obj));
        break;
    case 'info':
        $obj->requires = sanitize_text_field('4.7');
        $obj->tested = sanitize_text_field('5.2');
        $obj->downloaded = 10000;
        $obj->last_updated = sanitize_text_field('2022-01-01');
        $obj->sections = array(
            'description' => 'WhatsApp Order Notification for WooCommerce',
            'changelog' => 'View SendApp site (https://sendapp.live) for changelogs'
        );
        $obj->download_link = $obj->package;
        echo esc_html(serialize($obj));
        break;
    case 'license':
        echo esc_html(serialize($obj));
        break;
}

