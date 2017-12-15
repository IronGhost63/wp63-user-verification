<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP63_Email_Customer_New_Account', false ) ) :

class WP63_Email_Customer_New_Account extends WC_Email {
	public $user_id;
	public $verification_code;
	public $verification_page;

	public $user_login;
	public $user_email;
	public $user_pass;
	public $password_generated;
	
	public function __construct() {
		$this->id             = 'customer_new_account';
		$this->customer_email = true;
		$this->title          = __( 'New account', 'woocommerce' );
		$this->description    = __( 'Customer "new account" emails are sent to the customer when a customer signs up via checkout or account pages.', 'woocommerce' );
		$this->template_html  = 'emails/customer-new-account.php';
		$this->template_plain = 'emails/plain/customer-new-account.php';

		// Call parent constructor
		parent::__construct();
	}

	public function get_default_subject() {
		return __( 'Your account on {site_title}', 'woocommerce' );
	}

	public function get_default_heading() {
		return __( 'Welcome to {site_title}', 'woocommerce' );
	}

	function trigger( $user_id, $user_pass = '', $password_generated = false ) {
		$this->setup_locale();

		if ( $user_id ) {
			$this->object             = new WP_User( $user_id );

			$this->verification_code		= get_user_meta($user_id, "fresh_verification_code", true);
			$this->verification_page 		= get_permalink(get_option('wp63uv_page_setting_id')) . "?user_id=" . $user_id;

			$this->user_pass				= $user_pass;
			$this->user_login				= stripslashes( $this->object->user_login );
			$this->user_email				= stripslashes( $this->object->user_email );
			$this->recipient				= $this->user_email;
			$this->password_generated	= $password_generated;
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

		/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'email_heading'		=> $this->get_heading(),
			'user_login'			=> $this->user_login,
			'user_pass'				=> $this->user_pass,
			'blogname'				=> $this->get_blogname(),
			'password_generated'	=> $this->password_generated,
			'sent_to_admin'		=> false,
			'plain_text'			=> false,
			'email'				 	=> $this,
			'verification_code'	=> $this->verification_code,
			'verification_page'	=> $this->verification_page
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'email_heading'      => $this->get_heading(),
			'user_login'         => $this->user_login,
			'user_pass'          => $this->user_pass,
			'blogname'           => $this->get_blogname(),
			'password_generated' => $this->password_generated,
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'			     => $this,
			'verification_code'	=> $this->verification_code,
			'verification_page'	=> $this->verification_page
		) );
	}
}

endif;

return new WP63_Email_Customer_New_Account();
?>