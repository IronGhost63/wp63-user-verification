<?php
/*
Plugin Name: User Verification
Plugin URI: https://github.com/IronGhost63/wp63-user-verification
Author: Jirayu Yingthawornsuk
Author URI: https://jirayu.in.th
Description: A WordPress Plugin forcing user to verify account with verification code from email
Version: 1.0
Text Domain: wp63uv
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP63UV_PATH', plugin_dir_path( __FILE__ ) );

require_once("inc/admin.php");

add_filter( 'authenticate', 'wp63_check_user_verification', 35, 3 );
add_filter( 'insert_user_meta', 'wp63_insert_verification_code', 35, 3);
add_filter( 'woocommerce_registration_auth_new_customer', 'wp63_prevent_wc_auto_login', 10, 2);
add_action( 'woocommerce_registration_redirect', 'wp63uv_redirect_after_registration');

add_shortcode( 'user_verification', 'wp63_sc_verification_box');

/*
* Use WooCommerce email function instead of built-in, if user has WooCommerce activated
*/
if ( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_locate_template', 'myplugin_woocommerce_locate_template', 10, 3 );
	add_filter( 'woocommerce_email_classes', 'wp63uv_intercept_wc_registration_email' );
}else{
	add_action( 'user_register', 'wp63_send_verification_email', 35, 1);
}

function wp63uv_intercept_wc_registration_email( $emails ) {
	$emails['WC_Email_Customer_New_Account'] = include( 'inc/class-wc-registration-email.php' );;
	return $emails;
}

function wp63_check_user_verification($user, $username, $password){
	if( is_wp_error($user) ){
		return $user;
	}

	if(user_can( $user, 'administrator')){
		return $user;
	}

	$verification = get_user_meta($user->ID, "fresh_verified", true);
	if($verification != "true"){
		$user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Account is not verified ' ) );		
	}

	return $user;
}

function wp63_verify_user($user_id, $verification_code){
	$confirm = get_user_meta($user_id, 'fresh_verification_code', true);
	if($verification_code === $confirm){
		add_user_meta( $user_id, "fresh_verified", "true", true);

		return true;
	}else{
		return new WP_Error("user_verfication", __("Invalid verification code - " . $confirm, "wp63uv"));
	}
}

function wp63_create_verification_code(){
	$seed = time();
	$seed = apply_filters("wp63_verification_code_generation", $seed);

	return sha1($seed);
}

function wp63_insert_verification_code($meta, $user, $update){
	$GLOBALS['verification_code'] = wp63_create_verification_code();

	$meta['fresh_verification_code'] = $GLOBALS['verification_code'];

	return $meta;
}

function wp63_sc_verification_box($atts){
	if( !empty( $_POST['verification_code'] ) ){
		$user_id = sanitize_text_field( $_POST['user_id'] );
		$verification_code = sanitize_text_field( $_POST['verification_code'] );

		$verify = wp63_verify_user($user_id, $verification_code);
		if( is_wp_error($verify) ){
			$return = '<div class="wp63-verification-failed">' . $verify->get_error_message() . '</div>';
		}else{
			$return = '<div class="wp63-verification-success">' . __("Your account is now verified!", "wp63uv") . ' <a href="' . wp63uv_login_url() .'">' . __("Sign in", "wp63uv") . '</a></div>';
		}

	}else{
		if( isset( $_GET['user_id'] ) ){
			$return = "";
			if( isset( $_GET['registered']) ){
				$user = get_userdata( $user_id );
				$email = $user->user_email;

				$return .= '<p class="wp63-user-registered-notify">' . sprintf(__("Thank you for registration. Your activation code has been sent to %1$s. Use activation code to activate your account in the form below"), $email) . '</p>';
			}

			$return .= '<form name="wp63-user-verification-form" method="post">' . PHP_EOL .
				'<input type="hidden" name="user_id" value="' . $_GET['user_id'] . '">' . PHP_EOL .
				'<input type="text" name="verification_code" placeholder="'. __('Verification Code', 'wp63uv') .'" class="wp63-verification-input">' . PHP_EOL .
				'<button type="submit">' . __('Verify', 'wp63uv') . '</button>' . PHP_EOL .
				'</form>';
		}else{
			$return = '<div class="wp63-verification-warning">' . __("No user specified", "wp63uv") . '</div>';
		}
	}

	return $return;
}

function wp63_send_verification_email( $user_id ){
	$user = get_userdata( $user_id );

	$username = $user->user_login;
	$name = $user->first_name;
	$email = $user->user_email;
	$code = $GLOBALS['verification_code'];
	$title = __("Account Verification on ", "wp63uv") . get_option("blogname");
	$verification_page = get_permalink(get_option('wp63uv_page_setting_id')) . "?user_id=" . $user_id;
	$lostpassword = wp_lostpassword_url();

	$tags = array('%NAME%', '%VERIFICATIONCODE%', '%VERIFICATION%', '%USERNAME%', '%RESETPASSWORD%');
	$replace = array($name, $code, $verification_page, $username, $lostpassword);

	$message = str_replace($tags, $replace, get_option('wp63uv_email_settings_template'));

	wp_mail($email, $title, $message);
}

/*
* myplugin_woocommerce_locate_template() - redirect template locating to this plugin before from theme 
* Copied from here: https://www.skyverge.com/blog/override-woocommerce-template-file-within-a-plugin/
*/

function myplugin_woocommerce_locate_template( $template, $template_name, $template_path ) {
	global $woocommerce;

	$_template = $template;

	if ( ! $template_path ) {
		$template_path = $woocommerce->template_url;
	}

	$plugin_path  = WP63UV_PATH . '/woocommerce/';

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			$template_path . $template_name,
			$template_name
		)
	);

	// Modification: Get the template from this plugin, if it exists
	if ( ! $template && file_exists( $plugin_path . $template_name ) ){
		$template = $plugin_path . $template_name;
	}

	// Use default template
	if ( ! $template ) {
		$template = $_template;
	}
	
	// Return what we found
	return $template;
}

function wp63_prevent_wc_auto_login($status, $user){
	$GLOBALS['wp63_user_id'] = $user;
	return false;
}

function wp63uv_redirect_after_registration(){
	$verification_page = get_permalink(get_option('wp63uv_page_setting_id')) . "?user_id=" . $GLOBALS['wp63_user_id'] . "&registered=true";

	return $verification_page;
}

function wp63uv_login_url(){
	if( class_exists("WooCommerce") ){
		return get_permalink( get_option('woocommerce_myaccount_page_id') );
	}else{
		return wp_login_url();
	}
}
?>