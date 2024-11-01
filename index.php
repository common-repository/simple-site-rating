<?php
/*
 * Plugin Name:       Simple Site Rating
 * Plugin URI:        
 * Description:       Let your website visitors rate your single posts and pages.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Author:            WSM – Walter Solbach Metallbau GmbH
 * Author URI:        https://www.wsm.eu
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       simple-site-rating
 * Domain Path:       /languages
 */

defined('ABSPATH') or die('No access allowed …');

define('WSMSSR_PLUGIN_FILE', __FILE__);

define('WSMSSR_PLUGIN_DIR', dirname(WSMSSR_PLUGIN_FILE));

define('WSMSSR_PLUGIN_BASENAME', plugin_basename(WSMSSR_PLUGIN_FILE));

define('WSMSSR_DETECT_PLUGIN_URI', plugin_dir_url(WSMSSR_PLUGIN_FILE));

/**
 * Enqueue Scripts
 */
add_action('wp_enqueue_scripts', 'wsmssr_load_scripts');
function wsmssr_load_scripts($hook) {
 
    $wsmssr_js_ver  = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/custom.js' ));
    $wsmssr_css_ver = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'style.css' ));
    
    wp_register_style( 'wsmssr_css',    plugins_url( 'style.css',    __FILE__ ), false,   $wsmssr_css_ver );
    wp_enqueue_style ( 'wsmssr_css' );  
    
    wp_enqueue_script( 'jquery' );

    if(get_option( 'wsmssr_fontawesome_toggle_html' ) != 'on'){
        wp_register_style( 'wsmssr-font-awesome-css', plugins_url( 'assets/css/all.min.css', __FILE__ ));
        wp_enqueue_script( 'wsmssr-font-awesome-js', plugins_url( 'assets/js/all.min.js', __FILE__ ));
    
    }
    
    wp_enqueue_script( 'wsmssr-custom-js', plugins_url( 'assets/js/custom.js', __FILE__ ), array(), $wsmssr_js_ver );
    
    wp_localize_script('wsmssr-custom-js', 'ajax_var', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wsmssr-ajax-nonce'),
        'post_id' => get_the_ID()
    ));
 
}

add_action('admin_enqueue_scripts', 'wsmssr_load_admin_script');
function wsmssr_load_admin_script() {

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wsmssr-admin-js', plugins_url( 'assets/js/admin-js.js', __FILE__ ), array('wp-color-picker'), '1.0.0' );
    wp_localize_script('wsmssr-admin-js', 'ajax_var', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wsmssr-admin-ajax-nonce')
    ));

    wp_register_style( 'wsmssr-backend-custom',    plugins_url( '/assets/css/custom.css',    __FILE__ ), false,  '' );
    wp_enqueue_style ( 'wsmssr-backend-custom' ); 
}

/**
 * create the custom table
 */
register_activation_hook( __FILE__, 'wsmssr_on_activation' );
function wsmssr_on_activation(){

	global $wpdb;
	$table_name = $wpdb->prefix . 'wsmssr_page_rank';
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        pageid varchar(50) NOT NULL,
        rate_date TIMESTAMP NOT NULL, 
        rate varchar(50) NOT NULL) $charset_collate;";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	dbDelta( $sql );
}

require_once( WSMSSR_PLUGIN_DIR . '/inc/core.php');

function wsmssr_plugin_init() {
    load_plugin_textdomain( 'simple-site-rating', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'wsmssr_plugin_init' );
