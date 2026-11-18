<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 * Settings are removed only if "Delete data on uninstall" is switched on.
 *
 * @package Super_Widget
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$super_widget_settings = get_option( 'super_widget_settings', array() );

if ( is_array( $super_widget_settings ) && ! empty( $super_widget_settings['delete_data'] ) ) {
	delete_option( 'super_widget_settings' );
	delete_option( 'super_widget_license' );
	delete_site_transient( 'super_widget_update_info' );
}
