<?php
/*
Plugin Name: Simple taxonomy image
Version: 1.0
Plugin URI: http://www.beapi.fr
Description: Add possibility to add image on term taxonomy
Author: Be API
Author URI: http://www.beapi.fr
Dependencies:meta-for-taxonomies/meta-for-taxonomies.php

Copyright 2010 - BeAPI Team (technique@beapi.fr)
*/

// Setup tables for multisite
global $wpdb;
$wpdb->tables[] 		= 'term_taxo_meta';
$wpdb->term_taxometa 	= $wpdb->prefix . 'term_taxo_meta';

define( 'STI_VERSION', '1.0' );
define( 'STI_FOLDER', 'simple-taxonomy-image' );
define( 'STI_OPTIONS_NAME', 'simple-taxonomy-image' ); // Option name for save settings
define( 'STI_URL', plugins_url('', __FILE__) );
define( 'STI_DIR', dirname(__FILE__) );
define( 'STI_LIB_DIR', STI_DIR . '/inc/lib' );

require( STI_DIR . '/inc/functions.plugin.php');
require( STI_DIR . '/inc/functions.tpl.php');
require( STI_DIR . '/inc/class.client.php');

if( !function_exists( 'st_get_term_meta' ) ){
	// 2. Library
	require( STI_LIB_DIR.'/functions.meta.php' );
	require( STI_LIB_DIR.'/functions.meta.ext.php' );
	require( STI_LIB_DIR.'/functions.meta.terms.php' );
	
	// 3. Functions
	require( STI_LIB_DIR.'/functions.hook.php' );
	require( STI_LIB_DIR.'/functions.inc.php' );
	require( STI_LIB_DIR.'/functions.tpl.php' );

	add_action ( 'delete_term', 'remove_meta_during_delete', 10, 3 );
}


// Activation, uninstall
register_activation_hook( __FILE__, 'STI_Install' );
register_uninstall_hook ( __FILE__, 'STI_Uninstall' );

// Init POSTSMETAS
function STI_Init() {
	global $sti;

	// Load translations
	load_plugin_textdomain ( 'sti', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );

	// Load client
	$sti['client'] = new STI_Client();

	// Admin
	if ( is_admin() ) {
		require( STI_DIR . '/inc/class.admin.php' );
		require( STI_DIR . '/inc/class.admin.page.php' );
		
		$sti['admin'] = new STI_Admin();
		$sti['admin_page'] = new STI_Admin_Page();
	}
}
add_action( 'plugins_loaded', 'STI_Init' );
?>