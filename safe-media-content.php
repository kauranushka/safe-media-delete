<?php
/*
Plugin Name: Safe Media Content 
Description: Select the image for taxonomy and check the media before an delete it is used anywhere on the platform.
Author: test
Version: 1.0
Author URI: #
*/

if(!defined('ABSPATH')) exit;

define("SMCP_BUILD", 1.0 );

if(!defined("SMCP_PLUGIN_DIR_PATH"))
	
	define("SMCP_PLUGIN_DIR_PATH",plugin_dir_path(__FILE__));	
	
if(!defined("SMCP_PLUGIN_URL"))
	
	define("SMCP_PLUGIN_URL",plugins_url().'/'.basename(dirname(__FILE__)));	

// include the custom function file	
require( SMCP_PLUGIN_DIR_PATH . '/custom-functions.php' );

add_action('admin_enqueue_scripts', 'smcp_admin_styles' );

function smcp_admin_styles() {
	if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
	wp_enqueue_script( 'admin-custom-script', SMCP_PLUGIN_URL."/assets/js/admin-scripts.js", array( 'jquery' ), '1.0', true );	
	wp_localize_script( 'admin-custom-script', 'smcp_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}