<?php
/*
Plugin Name:  Commons Booking Admin Booking
Plugin URI:   https://github.com/flotte-berlin/commons-booking-admin-booking
Description:  Ein Plugin in Ergänzung zu Commons Booking, das es erlaubt aus dem Admin-Bereich heraus Buchungen für andere NutzerInnen zu erstellen.
Version:      0.5.9
Author:       poilu
Author URI:   https://github.com/poilu
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'CB_ADMIN_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'CB_ADMIN_BOOKING_ASSETS_URL', plugins_url( 'assets/', __FILE__ ));
define( 'CB_ADMIN_LANG_PATH', dirname( plugin_basename( __FILE__ )) . '/languages/' );

require_once( CB_ADMIN_BOOKING_PATH . 'functions/translate.php' );
require_once( CB_ADMIN_BOOKING_PATH . 'functions/get-active-plugin-directory.php' );
require_once( CB_ADMIN_BOOKING_PATH . 'functions/is-plugin-active.php' );
require_once( CB_ADMIN_BOOKING_PATH . 'classes/class-cb-admin-booking-admin.php' );

$cb_admin_booking_admin = new CB_Admin_Booking_Admin();

register_activation_hook( __FILE__, array( $cb_admin_booking_admin, 'activate' ) );

add_action( 'wp_ajax_cb_admin_booking_serial', [$cb_admin_booking_admin, 'handle_serial_booking_check'] );
add_action( 'wp_ajax_cb_admin_booking_user_search', [$cb_admin_booking_admin, 'handle_user_search'] );
add_action( 'wp_ajax_cb_admin_booking_edit', [$cb_admin_booking_admin, 'handle_booking_edit'] );
add_action( 'wp_ajax_get_booking_comment', [$cb_admin_booking_admin, 'get_booking_comment'] );
add_action( 'wp_ajax_get_booking_special_fields', [$cb_admin_booking_admin, 'get_booking_special_fields'] );

add_action( 'admin_init', [$cb_admin_booking_admin, 'check_nonce'], 10, 2);

add_action( 'toplevel_page_cb_bookings', array($cb_admin_booking_admin, 'load_bookings_creation'));

?>
