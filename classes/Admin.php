<?php
namespace casawpLegal;

class Admin {

	public function __construct(){ 
		add_action( 'admin_menu', array($this,'casawp_legal_menu') );
		add_action( 'admin_enqueue_scripts', array($this,'registerAdminScriptsAndStyles' ));
	}
	
	public function casawp_legal_install() {
		add_option('casawp_legal-casawp_legal_imprint', null);
		add_option('casawp_legal-casawp_legal_terms', null);

		$prefix = 'casawp_legal_';
		add_option($prefix.'company_legal_name', null);
		add_option($prefix.'company_phone', null);
		add_option($prefix.'company_fax', null);
		add_option($prefix.'company_email', null);
		add_option($prefix.'company_uid', null);
		add_option($prefix.'company_vat', null);
		add_option($prefix.'company_address_street', null);
		add_option($prefix.'company_address_street_number', null);
		add_option($prefix.'company_address_post_office_box_number', null);
		add_option($prefix.'company_address_postal_code', null);
		add_option($prefix.'company_address_locality', null);

	}

	public function casawp_legal_remove() {
		/* Delete the database field */
		//delete_option('my_first_data');
	}

	public function casawp_legal_menu() {
		add_menu_page(
			'casawp_legal options page',
			'<strong>CASA</strong><span style="font-weight:100">WP</span> Legal',
			'administrator',
			'casawp_legal',
			array($this,'casawp_legal_add_options_page')
		);
	}

	public function casawp_legal_add_options_page() {
		include(CASAWP_LEGAL_PLUGIN_DIR.'options.php');
	}

	public function registerAdminScriptsAndStyles() {
		wp_register_style( 'casawp_legal-admin-css', CASAWP_LEGAL_PLUGIN_URL . 'plugin-assets/global/css/casawp_legal-admin.css' );
        wp_enqueue_style( 'casawp_legal-admin-css' );
	}


}
