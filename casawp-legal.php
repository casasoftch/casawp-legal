<?php
/*
 *	Plugin Name: 	CASAWP-Legal
 *  Plugin URI: 	http://immobilien-plugin.ch
 *	Description:    Casasoft WordPress Plugin implementation for automating legal pages.
 *	Author:         Casasoft AG
 *	Author URI:     https://casasoft.ch
 *	Version: 	    1.0.8
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
$plugin_current_version = '1.0.8';
$plugin_slug = plugin_basename( __FILE__ );
$plugin_remote_path = 'http://wp.casasoft.com/casawp-legal/update.php';
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

// chdir(dirname(__DIR__));

// Setup autoloading
include 'vendor/autoload.php';
$configuration = array();
$casawpLegal = new casawpLegal\Plugin($configuration);

global $casawpLegal;

/*
curl -X "POST" "https://somedomain.ch/" \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "casawp_fetchdata_from_casaauth=" \
     --data-urlencode "force=0" \
     --data-urlencode "casawp_gateway_public_key=keyhere" \
     --data-urlencode "casawp_gateway_private_key=keyhere"
*/
if (isset($_POST['casawp_fetchdata_from_casaauth'])) {
	$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	$gateway_private_key = $_POST['casawp_gateway_private_key'];
	$gateway_public_key = $_POST['casawp_gateway_public_key'];
	$force = isset($_POST['force']) && $_POST['force'] ? true : false;
	$transcript = $casawpLegal->fetchCompanyDataFromGateway($gateway_private_key, $gateway_public_key, $force);
	echo json_encode([
		'action' => 'fetch data from casaauth',
		'result' => $transcript
	]);
	die();
}

/*
curl -X "POST" "https://somedomain.ch/" \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "casawp_generate_terms="
*/
if (isset($_POST['casawp_generate_terms'])) {
	$transcript = $casawpLegal->makeSureTermsExists();
	echo json_encode([
		'action' => 'generate terms',
		'result' => $transcript,
	]);
	die();
}

/*
curl -X "POST" "https://somedomain.ch/" \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "casawp_generate_imprint="
*/
if (isset($_POST['casawp_generate_imprint'])) {
	$transcript = $casawpLegal->makeSureImprintExists();
	echo json_encode([
		'action' => 'generate imprint',
		'result' => $transcript
	]);
	die();
}

/*
curl -X "POST" "https://somedomain.ch/" \
     -H 'Content-Type: application/x-www-form-urlencoded; charset=utf-8' \
     --data-urlencode "casawp_generate_footmenu_items="
*/
if (isset($_POST['casawp_generate_footmenu_items'])) {
	$transcript = $casawpLegal->addToFootmenu();
	echo json_encode([
		'action' => 'generate_footmenu_items',
		'result' => $transcript
	]);
	die();
}

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
