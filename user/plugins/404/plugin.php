<?php
/*
	Plugin Name: 404 Not Found
	Plugin URI: https://github.com/DRGli/404-redirect-YOURLS
	Description: Display your own fancy 404 error page instead of redirecting to the index if the link doesn't exist.
	Version: 1.5-drg-git
	Author: DRG
	Author URI: https://drg.one/
*/
	
// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();
	
yourls_add_action('redirect_keyword_not_found', 'drg_404notfound');
yourls_add_action('infos_keyword_not_found', 'drg_404notfound'); // This sets up the 404 page for stats pages too. Unfortunately, this only works for logged in users on private instances.
include_once('user/extend_loader_failed.php'); // Place lines from other plugins that add an action to 'loader_failed' in user/extend_loader_failed.php to avoid breaking something.
yourls_add_action('loader_failed', 'drg_404notfound'); // This sets up the 404 page for any string that YOURLS is unable to process. This breaks some plugins that extend functionality like qr and link preview plugins.
function drg_404notfound() {
	$error404 = file_get_contents('user/404.html');
	yourls_status_header( 404 );
	echo $error404;
	die();
}
