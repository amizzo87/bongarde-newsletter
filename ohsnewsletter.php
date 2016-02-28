<?php
/*
Plugin Name: OHS Newsletter
Version: 1.0
Author: Dejan Stosic
Author URI: https://www.upwork.com/o/profiles/users/_~01dca4c3772070997d/
Description: Custom newsletter with double opt in
GitHub Plugin URI: https://github.com/amizzo87/bongarde-newsletter
*/

require_once dirname( __FILE__ ) . '/Admin.php';
require_once dirname( __FILE__ ) . '/Widget.php';

define("OHS_NL_NONCE_STRING", "spfJkkVNb2SZoEgy");

/**
 * bootstrap the plugin
 */
$admin = new OHSNewsletterAdmin(__FILE__);
add_action( 'widgets_init', function(){
    register_widget( 'OHSNLWidget' );
});