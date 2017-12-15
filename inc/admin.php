<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action("admin_init", "wp63uv_register_option");
add_action("admin_menu", "wp63uv_register_menu");

function wp63uv_register_option(){
	add_settings_section( "wp63uv_email_settings", "Email Settings", null, "wp63uv_settings" );

	// add_settings_section( id, title, callback, page )
	// add_settings_field( id, title, callback, page, section, args )
	// add_options_page( page_title, menu_title, capability, menu_slug, function )

	add_settings_field( 
		"wp63uv_email_settings_template", 
		"Email Template", 
		"wp63uv_field_template", 
		"wp63uv_settings", 
		"wp63uv_email_settings" 
	);

	register_setting( 'wp63uv_settings_email', 'wp63uv_email_settings_template' );
}

function wp63uv_register_menu(){
	add_options_page( "User Verification", "User Verification", 'manage_options', 'wp63uv_settings' , 'wp63uv_settings_render');
}

function wp63uv_email_description(){
	
}

function wp63uv_field_template(){
	echo '<textarea name="wp63uv_email_settings_template" class="large-text" cols="30" rows="12">' . get_option('wp63uv_email_settings_template') . '</textarea>';
	echo "<p>Email template for verification email</p>";
}

function wp63uv_settings_render(){
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	include( WP63UV_PATH . "views/admin.php" );
}
?>