<?php
/*
Plugin Name:  Commons Booking Admin Booking
Plugin URI:   https://github.com/flotte-berlin/commons-booking-admin-booking
Description:  Ein Plugin in Ergänzung zu Commons Booking, das es erlaubt aus dem Admin-Bereich heraus Buchungen für andere NutzerInnen zu erstellen.
Version:      0.1.8
Author:       poilu
Author URI:   https://github.com/poilu
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'CB_ADMIN_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'CB_ADMIN_LANG_PATH', dirname( plugin_basename( __FILE__ )) . '/languages/' );

require_once( CB_ADMIN_BOOKING_PATH . 'functions/translate.php' );
require_once( CB_ADMIN_BOOKING_PATH . 'functions/is-plugin-active.php' );
require_once( CB_ADMIN_BOOKING_PATH . 'classes/class-cb-admin-booking-admin.php' );

$cb_admin_booking_admin = new CB_Admin_Booking_Admin();

add_action( 'toplevel_page_cb_bookings', array($cb_admin_booking_admin, 'load_bookings_creation'));

?>
