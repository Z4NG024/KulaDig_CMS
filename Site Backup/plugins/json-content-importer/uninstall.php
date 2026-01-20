<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

UNINSTALL_jci_plugin_options();

function UNINSTALL_jci_plugin_options() {
    global $wpdb;
    if (function_exists('is_multisite') && is_multisite()) {
		$blogIdCurrent = $wpdb->blogid;  // retrieve blogIds
	  	#$query = $wpdb->prepare(
		#	"SELECT blog_id FROM %s",
		#	$wpdb->blogs
		#);	  
		#	$sql = $wpdb->prepare($query, 'publish');
		#$blogIdArr = $wpdb->get_results($sql);
		$sites = get_sites();
        foreach ($sites as $site) {
			switch_to_blog($site->blog_id);
				UNINSTALL_jci_options();
        }
		switch_to_blog($blogIdCurrent);
		return;
    }
    UNINSTALL_jci_options();
	delete_option( "jci_uninstall_deleteall" );
}

function UNINSTALL_jci_options() {
  if (get_option('jci_uninstall_deleteall')==1) {
    delete_option( "jci_json_url" );
	delete_option( "jci_enable_cache" );
    delete_option( "jci_cache_time" );
    delete_option( "jci_cache_time_format" );
    delete_option( "jci_oauth_bearer_access_key" );
    delete_option( "jci_http_header_default_useragent" );
    delete_option( "jci_gutenberg_off" );
    delete_option( "jci_sslverify_off" );
    delete_option( "jci_api_errorhandling" );
  }
}

UNINSTALL_jci_plugin_cacher();

if ( ! function_exists( 'request_filesystem_credentials' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
function UNINSTALL_jci_plugin_cacher() {
	$cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/';
	return delete_plugin_cache_directory($cacheFolder);
}

function delete_plugin_cache_directory($dir) {
    $url = wp_nonce_url('index.php', 'my-nonce-del-jci');
    $credentials = request_filesystem_credentials($url);
    if (!WP_Filesystem($credentials)) {
        return false;
    }
    global $wp_filesystem;
    if ($wp_filesystem->rmdir($dir, true)) {
        return true;
    } else {
        return false;
    }
}
?>