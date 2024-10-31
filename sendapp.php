<?php
/*
Plugin Name: SendApp Notification
Version: 1.3.3
Plugin URI: https://sendapp.live/plugin-wordpress-woocommerce-whatsapp-notification/
Description: WhatsApp, Recover Abandoned Carts, Send Order Notifications, Share Post on WhatsApp, Share Store Products on WhatsApp.
Author: SendApp
Author URI: https://sendapp.live
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


require_once 'san-main.php';
require_once 'san-ui.php';
require_once 'san-logger.php';

$nno = new san_main;