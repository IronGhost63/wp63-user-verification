<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action("admin_init", "wp63uv_register_option");
add_action("admin_menu", "wp63uv_register_menu");

function wp63uv_register_option(){
	// add_settings_section( id, title, callback, page )
	// add_settings_field( id, title, callback, page, section, args )
	// add_options_page( page_title, menu_title, capability, menu_slug, function )

	add_settings_section( "wp63uv_email_settings", "Email Settings", null, "wp63uv_settings" );
	add_settings_field( 
		"wp63uv_email_settings_template", 
		"Email Template", 
		"wp63uv_field_template", 
		"wp63uv_settings", 
		"wp63uv_email_settings" 
	);

	add_settings_section( "wp63uv_page_setting", "Page", null, "wp63uv_settings" );
	add_settings_field( 
		'wp63uv_page_setting_id', 
		'Verification Page', 
		'wp63uv_field_page', 
		'wp63uv_settings', 
		'wp63uv_page_setting' 
	);

	register_setting( 'wp63uv_settings', 'wp63uv_email_settings_template' );
	register_setting( 'wp63uv_settings', 'wp63uv_page_setting_id' );
}

function wp63uv_register_menu(){
	add_options_page( "User Verification", "User Verification", 'manage_options', 'wp63uv_settings' , 'wp63uv_settings_render');
}

function wp63uv_field_page(){
	$pages = new WP_Query(array(
		'post_type' => 'page',
		'nopaging' => true
	));

	echo '<select name="wp63uv_page_setting_id">' . PHP_EOL;
	if( $pages->have_posts() ){
		$selected = get_option('wp63uv_page_setting_id');

		while( $pages->have_posts() ){
			$pages->the_post();

			if( get_the_ID() == $selected){
				echo '<option value="'.get_the_ID().'" selected>' . get_the_title() . '</option>';
			}else{
				echo '<option value="'.get_the_ID().'">' . get_the_title() . '</option>';
			}
		}
	}else{
		echo "<option disabled>" . __("No page found", "wp64uv") . "</option>" . PHP_EOL;
	}
	echo "</select>" . PHP_EOL;
}

function wp63uv_field_template(){
	echo '<textarea name="wp63uv_email_settings_template" class="large-text" cols="30" rows="12">' . get_option('wp63uv_email_settings_template') . '</textarea>';
	echo "<p>Email template for verification email.</p>";
	echo "<p>Availeable tags: %NAME%, %VERIFICATIONCODE%, %VERIFICATION%, %USERNAME%, %RESETPASSWORD%.</p>";
}

function wp63uv_settings_render(){
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	include( WP63UV_PATH . "views/admin.php" );
}
?>