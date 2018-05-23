<?php
/*
 *	Plugin Name: 	CASAWP-Legal
 *  Plugin URI: 	http://immobilien-plugin.ch
 *	Description:    Casasoft WordPress Plugin implementation for automating legal pages.
 *	Author:         Casasoft AG
 *	Author URI:     https://casasoft.ch
 *	Version: 	    1.0.3
 *	Text Domain: 	casawp-legal
 *	Domain Path: 	languages/
 *	License: 		GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//load classes
require_once ( 'classes/AutoUpdate.php' );
require_once ( 'classes/Admin.php' );
require_once ( 'classes/Plugin.php' );


//update system
$plugin_current_version = '1.0.3';
$plugin_slug = plugin_basename( __FILE__ );
$plugin_remote_path = 'http://wp.casasoft.ch/casawp-legal/update.php';
$license_user = 'user';
$license_key = 'abcd';
new casawpLegal\AutoUpdate( $plugin_current_version, $plugin_remote_path, $plugin_slug, $license_user, $license_key );

function casawpLegalPostInstall( $true, $hook_extra, $result ) {
  // Remember if our plugin was previously activated
  $wasActivated = is_plugin_active( 'casawp-legal' );

  // Since we are hosted in GitHub, our plugin folder would have a dirname of
  // reponame-tagname change it to our original one:
  global $wp_filesystem;
  $pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname( 'casawp-legal' );
  $wp_filesystem->move( $result['destination'], $pluginFolder );
  $result['destination'] = $pluginFolder;

  // Re-activate plugin if needed
  if ( $wasActivated ) {
      $activate = activate_plugin( 'casawp-legal'  );
  }

  return $result;
}

add_filter( "upgrader_post_install", "casawpLegalPostInstall", 10, 3 );


/* Das WP Immobilien-Plugin für Ihre Website importiert Immobilien aus Ihrer Makler-Software! */
$dummy_desc = __( 'Casasoft WordPress Plugin implementation for automating legal pages.', 'casawp-legal' );

define('CASAWP_LEGAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CASAWP_LEGAL_PLUGIN_DIR', plugin_dir_path(__FILE__) . '');

$upload = wp_upload_dir();
define('CASAWP_LEGAL_CUR_UPLOAD_PATH', $upload['path'] );
define('CASAWP_LEGAL_CUR_UPLOAD_URL', $upload['url'] );
define('CASAWP_LEGAL_CUR_UPLOAD_BASEDIR', $upload['basedir'] );
define('CASAWP_LEGAL_CUR_UPLOAD_BASEURL', $upload['baseurl'] );

chdir(dirname(__DIR__));

// Setup autoloading
include 'vendor/autoload.php';
$configuration = array();
$casawpLegal = new casawpLegal\Plugin($configuration);

global $casawpLegal;

if (is_admin()) {
	$casawpLegalAdmin = new casawpLegal\Admin();
	register_activation_hook(__FILE__, array($casawpLegalAdmin,'casawp_legal_install'));
	register_deactivation_hook(__FILE__, array($casawpLegalAdmin, 'casawp_legal_remove'));
}


function casawp_legal_after_wpml() {
	// ensure path to this file is via main wp plugin path
	$wp_path_to_this_file = preg_replace('/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__);
	$casawp_legal = plugin_basename(trim($wp_path_to_this_file));
	$active_plugins = get_option('active_plugins');
	$casawp_legal_key = array_search($casawp_legal, $active_plugins);

	$dependency = 'sitepress-multilingual-cms/sitepress.php';
	unset($active_plugins[$casawp_legal_key]);
	$new_sort = array();
	foreach ($active_plugins as $active_plugin) {
		$new_sort[] = $active_plugin;
		if ($active_plugin == $dependency) {
			$new_sort[] = $casawp_legal;
		}
	}

	if (!in_array($casawp_legal, $new_sort)) {
		$new_sort[] = $casawp_legal;
	}

	$new_sort = array_values($new_sort);

	update_option('active_plugins', $new_sort);
}
add_action("activated_plugin", "casawp_legal_after_wpml");
