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

add_filter( 'authenticate', 'wp63_check_user_verification', 35, 3 );
add_filter( 'insert_user_meta', 'wp63_insert_verification_code', 35, 3);
add_action( 'user_register', 'wp63_send_verification_email', 35, 1);
add_shortcode( 'user_verification', 'wp63_sc_verification_box');

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
		return new WP_Error("user_verfication", __("Invalid verification code", "wp63uv"));
	}
}

function wp63_create_verification_code(){
	$seed = time();
	$seed = apply_filters("wp63_verification_code_generation", $seed);

	return sha1($seed);
}

function wp63_insert_verification_code($meta, $user, $update){
	$meta['fresh_verification_code'] = wp63_create_verification_code();

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
			$return = '<div class="wp63-verification-success">' . __("Your account is now verified!", "wp63uv") . '</div>';
		}

	}else{
		if( isset( $_GET['user_id'] ) ){
			$return = '<form name="wp63-user-verification-form" method="post">' . PHP_EOL .
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

	$name = $user->first_name;
	$email = $user->user_email;
	$code = get_user_meta($user_id, "fresh_verification_code", true);
	$title = __("Account Verification on ", "wp63uv") . get_option("blogname");

	$message = "Hi, \n\n" . $name . PHP_EOL .
		"Please go to this page and enter this verification code to verify your account \n" . PHP_EOL .
		"Verification code: " . $code . "\n\n" . PHP_EOL .
		"Thank you";

	wp_mail($email, $title, $message);
}
?>