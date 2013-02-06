<?php

if ( is_admin() ) {
	add_filter( 'pre_set_site_transient_update_themes', 'gb_check_for_premium_theme_update' );
}
function gb_check_for_premium_theme_update( $trans ) {
	$theme_slug = GBS_THEME_SLUG;
	$basename = basename( get_template_directory() );
	$current_version = GBS_THEME_VERSION;
	
	if ( method_exists( 'Group_Buying_Addons', 'get_addon_data' ) ) { // GBS 4.0+
		$theme_data = Group_Buying_Addons::get_addon_data( $theme_slug );
		if ( $theme_data ) {
			// Add addon upgrade data
			if ( version_compare( $current_version, $theme_data['new_version'], '<' ) ) {
				$trans->response[$basename]['url'] = $theme_data['url'];
				$trans->response[$basename]['slug'] = $basename;
				$trans->response[$basename]['package'] = $theme_data['download_url'];
				$trans->response[$basename]['new_version'] = $theme_data['new_version'];
			}
		}
	} 
	else {
		$api_url = 'http://groupbuyingsite.com/check-key/';
		// Get API response
		$theme_data_api = wp_remote_post( $api_url, array(
				'body' => array(
					'key' => Group_Buying_Update_Check::get_api_key(),
					'plugin' => $theme_slug,
					'url' => site_url(),
					'wp_version' => get_bloginfo( 'version' ),
					'plugin_version' => $current_version
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url('/')
			) );
		$theme_data = json_decode( wp_remote_retrieve_body( $theme_data_api ) );
		if ( !is_wp_error( $theme_data_api ) && 200 == $theme_data_api['response']['code'] ) {
			if ( version_compare( $current_version, $theme_data->version, '<' ) ) {
				$update_info = array(
					'slug' => $basename,
					'new_version' => $theme_data->version,
					'url' => $theme_data->plugin_url,
					'package' => $theme_data->download_url
				);
				$trans->response[$basename] = $update_info;
			}
		}
	}
	return $trans;
}
// set_site_transient('update_themes', null);