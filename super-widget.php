<?php
/**
 * Plugin Name:       Super Widget
 * Description:       A modular widget pack for Elementor with its own dashboard. Includes a responsive Card Grid widget; add more widgets (icon box, slider, testimonial...) as separate modules and switch them on or off individually.
 * Version:           2.7.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       super-widget
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUPER_WIDGET_VERSION', '2.7.0' );
define( 'SUPER_WIDGET_FILE', __FILE__ );
define( 'SUPER_WIDGET_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUPER_WIDGET_URL', plugin_dir_url( __FILE__ ) );
define( 'SUPER_WIDGET_MIN_ELEMENTOR', '3.5.0' );
define( 'SUPER_WIDGET_MIN_PHP', '7.4' );

/**
 * Where sites look for new versions. Fill this in ONCE, before you build the zip you install on sites:
 *   'your-github-name/super-widget'                  -> latest GitHub release of that repository
 *   'https://example.com/updates/super-widget.json'  -> a small JSON file on your own server
 * Leave '' to switch update checks off. It can also be set on Super Widget > Settings > Updates.
 */
if ( ! defined( 'SUPER_WIDGET_UPDATE_SOURCE' ) ) {
	define( 'SUPER_WIDGET_UPDATE_SOURCE', '' );
}

/**
 * License server (optional). When this is the https address of api.php on your license server,
 * a License tab appears and updates are delivered only to sites with a valid key (the plugin itself
 * keeps working without one). '' = no licensing. Takes the place of SUPER_WIDGET_UPDATE_SOURCE.
 */
if ( ! defined( 'SUPER_WIDGET_LICENSE_SERVER' ) ) {
	define( 'SUPER_WIDGET_LICENSE_SERVER', '' );
}

if ( version_compare( PHP_VERSION, SUPER_WIDGET_MIN_PHP, '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: required PHP version */
						__( 'Super Widget requires PHP %s or newer.', 'super-widget' ),
						SUPER_WIDGET_MIN_PHP
					)
				)
			);
		}
	);
	return;
}

require_once SUPER_WIDGET_PATH . 'includes/class-manager.php';
require_once SUPER_WIDGET_PATH . 'includes/class-settings.php';
require_once SUPER_WIDGET_PATH . 'includes/class-nav.php';
require_once SUPER_WIDGET_PATH . 'includes/class-admin.php';
require_once SUPER_WIDGET_PATH . 'includes/class-license.php';
require_once SUPER_WIDGET_PATH . 'includes/class-updater.php';
require_once SUPER_WIDGET_PATH . 'includes/class-plugin.php';

Super_Widget_License::init();
Super_Widget_Updater::init();

Super_Widget_Plugin::instance();
