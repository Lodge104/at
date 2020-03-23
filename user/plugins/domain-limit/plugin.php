<?php
/*
Plugin Name: Domain Limiter
Plugin URI: https://bitbucket.org/quantumwebco/domain-limiter-yourls-plugin
Description: Only allow URLs from admin-specified domains, with an admin panel. Based on the Domain Limiter plugin by nicwaller.
Version: 1.1.0
Author: quantumweb.co
Author URI: http://quantumweb.co
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_filter( 'shunt_add_new_link', 'domainlimit_link_filter' );

function domainlimit_link_filter( $original_return, $url, $keyword = '', $title = '' ) {
	if ( domainlimit_environment_check() != true ) {
		$err = array();
		$err['status'] = 'fail';
		$err['code'] = 'error:configuration';
		$err['message'] = 'Problem with domain limit configuration. Check PHP error log.';
		$err['errorCode'] = '500';
		return $err;
	}

	// If the user is exempt, don't even bother checking.
	global $domainlimit_exempt_users;
    $domainlimit_exempt_users = array( 'nanderson' );
	if ( in_array( YOURLS_USER, $domainlimit_exempt_users ) ) {
		return $original_return;
	}

    $domainlimit_list = json_decode(yourls_get_option('domainlimit_list'), TRUE);

	// global $domainlimit_list;
	$domain_whitelist = $domainlimit_list;

	// The plugin hook gives us the raw URL input by the user, but
	// it needs some cleanup before it's suitable for parse_url().
	$url = yourls_encodeURI( $url );
	$url = yourls_escape( yourls_sanitize_url( $url) );
	if ( !$url || $url == 'http://' || $url == 'https://' ) {
		$return['status']    = 'fail';
		$return['code']      = 'error:nourl';
		$return['message']   = yourls__( 'Missing or malformed URL' );
		$return['errorCode'] = '400';
		return yourls_apply_filter( 'add_new_link_fail_nourl', $return, $url, $keyword, $title );
	}

	$allowed = false;
	$requested_domain = parse_url($url, PHP_URL_HOST);
	foreach ( $domain_whitelist as $domain_permitted ) {
		if ( domainlimit_is_subdomain( $requested_domain, $domain_permitted ) ) {
			$allowed = true;
			break;
		}
	}

	if ( $allowed == true ) {
		return $original_return;
	}

	$return = array();
	$return['status'] = 'fail';
	$return['code'] = 'error:disallowedhost';
	$return['message'] = 'URL must be in ' . implode(', ', $domain_whitelist);
	$return['errorCode'] = '400';
	return $return;
}

/*
 * Determine whether test_domain is controlled by $parent_domain
 */
function domainlimit_is_subdomain( $test_domain, $parent_domain ) {
	if ( $test_domain == $parent_domain ) {
		return true;
	}

	// note that "notunbc.ca" is NOT a subdomain of "unbc.ca"
	// We CANNOT just compare the rightmost characters
	// unless we add a period in there first
	if ( substr( $parent_domain, 1, 1) != '.' ) {
		$parent_domain = '.' . $parent_domain;
	}

	$chklen = strlen($parent_domain);
	return ( $parent_domain == substr( $test_domain, 0-$chklen ) );
}

// returns true if everything is configured right
function domainlimit_environment_check() {
        if (yourls_get_option('domainlimit_list') !== false) {
            $domainlimit_list = json_decode(yourls_get_option('domainlimit_list'), TRUE);
        } else {
            yourls_add_option('domainlimit_list');
        }

	if ( !isset( $domainlimit_list ) ) {
		error_log('Missing definition of $domainlimit_list in database');
		return false;
	} else if ( isset( $domainlimit_list ) && !is_array( $domainlimit_list ) ) {
		// be friendly and allow non-array definitions
		$domain = $domainlimit_list;
		$domainlimit_list = array( $domain );
		return true;
	}
	return true;
}


// Register your plugin admin page
yourls_add_action( 'plugins_loaded', 'domainlimit_init' );
function domainlimit_init() {
    yourls_register_plugin_page( 'domainlimit', 'Domain Limiter Settings', 'domainlimit_display_page' );
}

// The function that will draw the admin page
function domainlimit_display_page() {
    // Check if a form was submitted
    if( isset( $_POST['domainlimit_list'] ) )
            domainlimit_config_update_option();

	global $domainlimit_exempt_users;
    $domainlimit_list_option = yourls_get_option( 'domainlimit_list' );
    foreach (json_decode($domainlimit_list_option) as $domain) {
    	$domainlimit_list .= $domain.PHP_EOL;
    }
	$disabled = false;

	echo "<h3>Domain Limiter Settings</h3>";


	echo <<<HTML
	    <form method="post">
		<p>Please enter each URL on a new line</p>
		<textarea name="domainlimit_list" style="width:100%;min-height:7em;"{$disabled}>{$domainlimit_list}</textarea>
HTML;
		echo "<button type='submit'>Save</button>";
}

// Update option in database
function domainlimit_config_update_option() {
    $list_array = explode(PHP_EOL, $_POST['domainlimit_list']);
    foreach ($list_array as $domain) {
    	if(trim($domain)!="")
    	$list[] = filter_var(trim($domain), FILTER_SANITIZE_URL);
    }

    if($list) {

        $jsonlist = json_encode( $list );

        if (yourls_get_option('domainlimit_list') !== false) {
            yourls_update_option('domainlimit_list', $jsonlist);
        } else {
            yourls_add_option('domainlimit_list', $jsonlist);
        }
    }
}
