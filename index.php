<?php
/*
 * Plugin Name: GOIC Gateway Netopia
 * Plugin URI: https://www.gorobic.com
 * Description: Adds Netopia (MobilPay) Gateway to GOIC e-commerce website
 * Version: 1.0.0
 * Author: <gorobic@gmail.com>
 * Author URI: https://www.gorobic.com
 */

 // Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

    
/**
 * Initiate plugin actions
 */
// if ( ... ) { // @todo: fac o verificare daca exista instalat pe site partea de ecommerce, de ex daca exista functia goicc_get_payment_method sau filtrul goicc_payment_method
    $plugin_dir = plugin_dir_path(__FILE__);
    $plugin_dir_url = plugin_dir_url(__FILE__);

    /* Language translations */
	// load_plugin_textdomain( 'goic-gateway-netopia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	
    $default_title = _x( 'Debit card', 'Payment method', 'goic-gateway-netopia' );
    $default_description = _x( 'Online secure payment with your debit card.', 'Payment method description', 'goic-gateway-netopia' );
    require_once $plugin_dir . 'goic-gateway-netopia.php';
    require_once $plugin_dir . 'admin/settings-page.php';
    require_once $plugin_dir . 'public/functions.php';

    function ggn_custom_enqueue_wp(){
        global $plugin_dir_url, $plugin_dir; 
        if(
            ( $account_page_id = get_theme_mod( 'goicc_account_page' ) && is_page($account_page_id) ) 
            || 
            ( $thankyou_page_id = get_theme_mod( 'goicc_thankyou_page' ) && is_page($thankyou_page_id) )
        ){
            wp_enqueue_script( 'ggn_scripts', $plugin_dir_url.'includes/index.js', array(), filemtime($plugin_dir.'includes/index.js'), true );
            wp_localize_script('ggn_scripts', 'AJAX_URL', admin_url('admin-ajax.php'));
        }
    }
    add_action( 'wp_enqueue_scripts', 'ggn_custom_enqueue_wp' );
// }