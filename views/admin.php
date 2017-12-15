<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_GET['settings-updated'] ) ) {
	add_settings_error( 'wp63uv_messages', 'wp63uv_message', __( 'Settings Saved', 'wp63uv' ), 'updated' );
}

settings_errors( 'wp63uv_messages' );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
	<?php
	// output security fields for the registered setting "wporg"
	settings_fields( 'wp63uv_settings' );
	// output setting sections and their fields
	// (sections are registered for "wporg", each field is registered to a specific section)
	do_settings_sections( 'wp63uv_settings' );
	// output save settings button
	submit_button( 'Save Settings' );
	?>
	</form>
 </div>