<?php
/**
 * Admin dashboard: Dashboard / Widgets / Settings / Tools / Help tabs.
 *
 * @package Super_Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Super_Widget_Admin {

	const SLUG = 'super-widget';

	/**
	 * @var Super_Widget_Admin|null
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $hook = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( 'Super_Widget_Settings', 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'admin_post_super_widget_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_super_widget_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_super_widget_reset', array( $this, 'handle_reset' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( SUPER_WIDGET_FILE ), array( $this, 'action_links' ) );
	}

	/* ------------------------------------------------------------------
	 * Menu and assets
	 * ------------------------------------------------------------------ */

	public function add_menu() {
		$this->hook = add_menu_page(
			esc_html__( 'Super Widget', 'super-widget' ),
			esc_html__( 'Super Widget', 'super-widget' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' ),
			'dashicons-screenoptions',
			58.5
		);
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook ) {
			return;
		}

		wp_enqueue_style( 'super-widget-admin', SUPER_WIDGET_URL . 'assets/admin/admin.css', array(), SUPER_WIDGET_VERSION );
		wp_enqueue_script( 'super-widget-admin', SUPER_WIDGET_URL . 'assets/admin/admin.js', array(), SUPER_WIDGET_VERSION, true );
	}

	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $this->tab_url( 'dashboard' ) ), esc_html__( 'Dashboard', 'super-widget' ) )
		);
		return $links;
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	private function tabs() {
		$tabs = array(
			'dashboard' => __( 'Dashboard', 'super-widget' ),
			'widgets'   => __( 'Widgets', 'super-widget' ),
			'settings'  => __( 'Settings', 'super-widget' ),
			'tools'     => __( 'Tools', 'super-widget' ),
		);
		if ( Super_Widget_License::enabled() ) {
			$tabs['license'] = __( 'License', 'super-widget' );
		}
		$tabs['help'] = __( 'Help', 'super-widget' );
		return $tabs;
	}

	private function tab_icons() {
		return array(
			'dashboard' => 'dashicons-dashboard',
			'widgets'   => 'dashicons-screenoptions',
			'settings'  => 'dashicons-admin-generic',
			'tools'     => 'dashicons-admin-tools',
			'license'   => 'dashicons-admin-network',
			'help'      => 'dashicons-sos',
		);
	}

	/**
	 * Widget categories shown in the dashboard. A module picks one with 'category' in its meta.php.
	 * An unknown slug still works: it gets its own group with a neutral look.
	 *
	 * @return array<string, array{0:string,1:string,2:string,3:string}> slug => label, icon, colour, one-line help
	 */
	private function categories() {
		return array(
			'cards'        => array( __( 'Cards', 'super-widget' ), 'dashicons-index-card', '#7c5cff', __( 'Blog, service and feature cards', 'super-widget' ) ),
			'products'     => array( __( 'Products', 'super-widget' ), 'dashicons-cart', '#f2790d', __( 'WooCommerce product grids', 'super-widget' ) ),
			'testimonials' => array( __( 'Testimonials', 'super-widget' ), 'dashicons-format-quote', '#14a37f', __( 'Reviews and client quotes', 'super-widget' ) ),
			'timelines'    => array( __( 'Timelines', 'super-widget' ), 'dashicons-clock', '#e0457b', __( 'Steps, history and process', 'super-widget' ) ),
			'images'       => array( __( 'Images & galleries', 'super-widget' ), 'dashicons-format-gallery', '#2a7de1', __( 'Shapes, collages and image grids', 'super-widget' ) ),
			'other'        => array( __( 'Other', 'super-widget' ), 'dashicons-screenoptions', '#64748b', __( 'More widgets', 'super-widget' ) ),
		);
	}

	/**
	 * Group the discovered widgets by category, in the order of categories().
	 *
	 * @param array $discovered Output of Super_Widget_Manager::discover().
	 * @return array<string, array{label:string,icon:string,color:string,help:string,items:array}>
	 */
	private function grouped( $discovered ) {
		$defs   = $this->categories();
		$groups = array();
		foreach ( $discovered as $slug => $data ) {
			$cat = isset( $data['category'] ) && '' !== $data['category'] ? $data['category'] : 'other';
			if ( ! isset( $groups[ $cat ] ) ) {
				$d              = isset( $defs[ $cat ] ) ? $defs[ $cat ] : array( ucwords( str_replace( array( '-', '_' ), ' ', $cat ) ), 'dashicons-screenoptions', '#64748b', '' );
				$groups[ $cat ] = array( 'label' => $d[0], 'icon' => $d[1], 'color' => $d[2], 'help' => $d[3], 'items' => array() );
			}
			$groups[ $cat ]['items'][ $slug ] = $data;
		}
		$ordered = array();
		foreach ( array_keys( $defs ) as $k ) {
			if ( isset( $groups[ $k ] ) ) {
				$ordered[ $k ] = $groups[ $k ];
				unset( $groups[ $k ] );
			}
		}
		return $ordered + $groups;
	}

	private function tab_url( $tab ) {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
	}

	private function back( $tab, $notice ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::SLUG,
					'tab'       => $tab,
					'sw_notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private function guard( $nonce_action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'super-widget' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $nonce_action );
	}

	private function elementor_status() {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return array( false, __( 'Not active', 'super-widget' ) );
		}
		$ok = version_compare( ELEMENTOR_VERSION, SUPER_WIDGET_MIN_ELEMENTOR, '>=' );
		return array( $ok, ELEMENTOR_VERSION );
	}

	/* ------------------------------------------------------------------
	 * Tools: export / import / reset
	 * ------------------------------------------------------------------ */

	public function handle_export() {
		$this->guard( 'super_widget_export' );

		$payload = array(
			'plugin'   => 'super-widget',
			'version'  => SUPER_WIDGET_VERSION,
			'settings' => Super_Widget_Settings::get_all(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="super-widget-settings.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	public function handle_import() {
		$this->guard( 'super_widget_import' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in guard(); parsed and normalized below.
		$raw = isset( $_POST['super_widget_import'] ) ? wp_unslash( $_POST['super_widget_import'] ) : '';

		if ( ! is_string( $raw ) || '' === trim( $raw ) || strlen( $raw ) > 20000 ) {
			$this->back( 'tools', 'import_failed' );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$this->back( 'tools', 'import_failed' );
		}

		$settings = ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) ? $data['settings'] : $data;

		update_option( Super_Widget_Settings::OPTION, Super_Widget_Settings::normalize( $settings ) );

		$this->back( 'tools', 'imported' );
	}

	public function handle_reset() {
		$this->guard( 'super_widget_reset' );

		delete_option( Super_Widget_Settings::OPTION );

		$this->back( 'tools', 'reset' );
	}

	/* ------------------------------------------------------------------
	 * Page
	 * ------------------------------------------------------------------ */

	private function print_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, value is whitelisted.
		$key = isset( $_GET['sw_notice'] ) ? sanitize_key( wp_unslash( $_GET['sw_notice'] ) ) : '';

		$messages = array(
			'imported'      => array( 'success', __( 'Settings imported.', 'super-widget' ) ),
			'import_failed' => array( 'error', __( 'Import failed. Paste valid JSON exported from Super Widget.', 'super-widget' ) ),
			'reset'         => array( 'success', __( 'All settings were reset to defaults.', 'super-widget' ) ),
			'license_ok'          => array( 'success', __( 'License is active. Updates are switched on.', 'super-widget' ) ),
			'license_deactivated' => array( 'success', __( 'License removed from this site. You can use the key on another site now.', 'super-widget' ) ),
			'license_bad_key'     => array( 'error', __( 'That does not look like a license key. Copy it again from your purchase email.', 'super-widget' ) ),
			'license_unreachable' => array( 'warning', __( 'Could not reach the license server. Try again in a few minutes.', 'super-widget' ) ),
			'license_rejected'    => array( 'error', __( 'The license server did not accept this key. See the message in the status box below.', 'super-widget' ) ),
			'update_found'  => array( 'success', __( 'A new version is available. Go to Dashboard > Updates or the Plugins screen to install it.', 'super-widget' ) ),
			'update_current' => array( 'success', __( 'You have the latest version.', 'super-widget' ) ),
			'update_none'   => array( 'warning', __( 'Could not check for updates. Is the update source filled in (Settings > Updates) and reachable?', 'super-widget' ) ),
		);

		if ( isset( $messages[ $key ] ) ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $messages[ $key ][0] ),
				esc_html( $messages[ $key ][1] )
			);
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = $this->tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, value is whitelisted.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'dashboard';
		}
		?>
		<?php
		$active_n = count( Super_Widget_Settings::active_widgets() );
		$total_n  = count( Super_Widget_Manager::discover() );
		$icons    = $this->tab_icons();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only, value is whitelisted below.
		$cat_get = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : 'all';
		?>
		<div class="wrap spw-admin" data-spw-cat="<?php echo esc_attr( $cat_get ); ?>">
			<header class="spw-hero">
				<div class="spw-hero__main">
					<span class="spw-hero__logo dashicons dashicons-screenoptions" aria-hidden="true"></span>
					<div>
						<h1>
							<?php esc_html_e( 'Super Widget', 'super-widget' ); ?>
							<span class="spw-admin__version">v<?php echo esc_html( SUPER_WIDGET_VERSION ); ?></span>
						</h1>
						<p><?php esc_html_e( 'Premium widgets for Elementor: fast, responsive and fully editable.', 'super-widget' ); ?></p>
					</div>
				</div>
				<div class="spw-hero__pills">
					<span class="spw-pill"><strong><?php echo esc_html( $active_n ); ?></strong> <?php esc_html_e( 'active', 'super-widget' ); ?></span>
					<span class="spw-pill"><strong><?php echo esc_html( $total_n ); ?></strong> <?php esc_html_e( 'widgets', 'super-widget' ); ?></span>
				</div>
			</header>

			<nav class="spw-tabs" aria-label="<?php esc_attr_e( 'Super Widget sections', 'super-widget' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( $this->tab_url( $slug ) ); ?>" class="spw-tab<?php echo $slug === $tab ? ' is-active' : ''; ?>"<?php echo $slug === $tab ? ' aria-current="page"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $icons[ $slug ] ); ?>" aria-hidden="true"></span><?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php
			$this->print_notice();
			settings_errors();

			echo '<div class="spw-body spw-body--' . esc_attr( $tab ) . '">';
			switch ( $tab ) {
				case 'widgets':
					$this->tab_widgets();
					break;
				case 'settings':
					$this->tab_settings();
					break;
				case 'tools':
					$this->tab_tools();
					break;
				case 'license':
					$this->tab_license();
					break;
				case 'help':
					$this->tab_help();
					break;
				default:
					$this->tab_dashboard();
			}
			echo '</div>';
			?>
		</div>
		<?php
	}

	/* ------------------------------ Dashboard ------------------------------ */

	private function tab_dashboard() {
		$discovered = Super_Widget_Manager::discover();
		$active     = Super_Widget_Settings::active_widgets();
		$settings   = Super_Widget_Settings::get_all();
		$groups     = $this->grouped( $discovered );

		list( $el_ok, $el_version ) = $this->elementor_status();
		$php_ok                     = version_compare( PHP_VERSION, SUPER_WIDGET_MIN_PHP, '>=' );

		$checks = array(
			array( $el_ok, __( 'Elementor is active and up to date', 'super-widget' ), $el_version ),
			array( $php_ok, __( 'PHP version is supported', 'super-widget' ), PHP_VERSION ),
			array( count( $active ) > 0, __( 'At least one widget is enabled', 'super-widget' ), count( $active ) . ' / ' . count( $discovered ) ),
			Super_Widget_License::enabled() ? array( Super_Widget_License::is_valid(), __( 'License is active (updates on)', 'super-widget' ), Super_Widget_License::is_valid() ? __( 'Active', 'super-widget' ) : __( 'Not active', 'super-widget' ) ) : null,
			array( empty( $settings['disable_animations'] ), __( 'Animations are enabled', 'super-widget' ), empty( $settings['disable_animations'] ) ? __( 'On', 'super-widget' ) : __( 'Off', 'super-widget' ) ),
		);
		?>
		<div class="spw-stats">
			<div class="spw-stat">
				<span class="spw-stat__icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<span class="spw-stat__num"><?php echo esc_html( count( $active ) ); ?><small> / <?php echo esc_html( count( $discovered ) ); ?></small></span>
				<span class="spw-stat__label"><?php esc_html_e( 'Active widgets', 'super-widget' ); ?></span>
			</div>
			<div class="spw-stat">
				<span class="spw-stat__icon dashicons dashicons-category" aria-hidden="true"></span>
				<span class="spw-stat__num"><?php echo esc_html( count( $groups ) ); ?></span>
				<span class="spw-stat__label"><?php esc_html_e( 'Categories', 'super-widget' ); ?></span>
			</div>
			<div class="spw-stat">
				<span class="spw-stat__icon dashicons dashicons-admin-plugins" aria-hidden="true"></span>
				<span class="spw-stat__num"><?php echo esc_html( $el_version ); ?></span>
				<span class="spw-stat__label"><?php esc_html_e( 'Elementor', 'super-widget' ); ?></span>
			</div>
			<div class="spw-stat">
				<span class="spw-stat__icon dashicons dashicons-editor-code" aria-hidden="true"></span>
				<span class="spw-stat__num"><?php echo esc_html( PHP_VERSION ); ?></span>
				<span class="spw-stat__label"><?php esc_html_e( 'PHP', 'super-widget' ); ?></span>
			</div>
		</div>

		<section class="spw-section">
			<div class="spw-section__head">
				<h2><?php esc_html_e( 'Browse by category', 'super-widget' ); ?></h2>
				<a class="spw-link" href="<?php echo esc_url( $this->tab_url( 'widgets' ) ); ?>"><?php esc_html_e( 'All widgets', 'super-widget' ); ?> &rarr;</a>
			</div>
			<div class="spw-cats">
				<?php foreach ( $groups as $key => $g ) : ?>
					<?php
					$on = 0;
					foreach ( array_keys( $g['items'] ) as $slug ) {
						if ( in_array( $slug, $active, true ) ) {
							++$on;
						}
					}
					$url = add_query_arg( 'cat', $key, $this->tab_url( 'widgets' ) );
					?>
					<a class="spw-cat" href="<?php echo esc_url( $url ); ?>" style="--c: <?php echo esc_attr( $g['color'] ); ?>">
						<span class="spw-cat__icon dashicons <?php echo esc_attr( $g['icon'] ); ?>" aria-hidden="true"></span>
						<span class="spw-cat__body">
							<span class="spw-cat__title"><?php echo esc_html( $g['label'] ); ?></span>
							<?php if ( '' !== $g['help'] ) : ?>
								<span class="spw-cat__help"><?php echo esc_html( $g['help'] ); ?></span>
							<?php endif; ?>
							<span class="spw-cat__names">
								<?php foreach ( $g['items'] as $slug => $data ) : ?>
									<span class="spw-tag<?php echo in_array( $slug, $active, true ) ? '' : ' is-off'; ?>"><?php echo esc_html( $data['title'] ); ?></span>
								<?php endforeach; ?>
							</span>
						</span>
						<span class="spw-cat__count"><strong><?php echo esc_html( $on ); ?></strong>/<?php echo esc_html( count( $g['items'] ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<div class="spw-columns">
			<div class="spw-panel">
				<h2><?php esc_html_e( 'System status', 'super-widget' ); ?></h2>
				<ul class="spw-checks">
					<?php foreach ( array_filter( $checks ) as $check ) : ?>
						<li class="<?php echo $check[0] ? 'is-ok' : 'is-bad'; ?>">
							<span class="dashicons <?php echo $check[0] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
							<span class="spw-checks__label"><?php echo esc_html( $check[1] ); ?></span>
							<span class="spw-checks__value"><?php echo esc_html( $check[2] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="spw-panel">
				<h2><?php esc_html_e( 'Quick actions', 'super-widget' ); ?></h2>
				<div class="spw-actions">
					<a class="button button-primary" href="<?php echo esc_url( $this->tab_url( 'widgets' ) ); ?>"><?php esc_html_e( 'Manage widgets', 'super-widget' ); ?></a>
					<a class="button" href="<?php echo esc_url( $this->tab_url( 'settings' ) ); ?>"><?php esc_html_e( 'Settings', 'super-widget' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Edit a page with Elementor', 'super-widget' ); ?></a>
				</div>
				<p class="description"><?php esc_html_e( 'In Elementor, open the widget panel and look for the "Super Widget" category.', 'super-widget' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* ------------------------------- Widgets ------------------------------- */

	private function tab_widgets() {
		$discovered   = Super_Widget_Manager::discover();
		$active       = Super_Widget_Settings::active_widgets();
		$name         = Super_Widget_Settings::OPTION;
		$settings_all = Super_Widget_Settings::get_all();
		$names        = $settings_all['names'];
		$groups       = $this->grouped( $discovered );

		$planned = array(
			array( __( 'Icon Box', 'super-widget' ), 'dashicons-star-filled' ),
			array( __( 'Gallery Slider', 'super-widget' ), 'dashicons-images-alt2' ),
		);
		?>
		<form action="options.php" method="post" id="spw-widgets-form">
			<?php settings_fields( Super_Widget_Settings::GROUP ); ?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[_tab]" value="widgets" />

			<div class="spw-toolbar">
				<div class="spw-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<input type="search" id="spw-widget-search" placeholder="<?php esc_attr_e( 'Search widgets...', 'super-widget' ); ?>" />
				</div>
				<div class="spw-toolbar__actions">
					<button type="button" class="button" data-spw-toggle-all="on"><?php esc_html_e( 'Enable all', 'super-widget' ); ?></button>
					<button type="button" class="button" data-spw-toggle-all="off"><?php esc_html_e( 'Disable all', 'super-widget' ); ?></button>
				</div>
			</div>

			<div class="spw-chips" role="tablist" aria-label="<?php esc_attr_e( 'Widget categories', 'super-widget' ); ?>">
				<button type="button" class="spw-chip is-active" data-spw-cat-filter="all" role="tab">
					<span class="dashicons dashicons-screenoptions" aria-hidden="true"></span><?php esc_html_e( 'All', 'super-widget' ); ?>
					<span class="spw-chip__n"><?php echo esc_html( count( $discovered ) ); ?></span>
				</button>
				<?php foreach ( $groups as $key => $g ) : ?>
					<button type="button" class="spw-chip" data-spw-cat-filter="<?php echo esc_attr( $key ); ?>" role="tab" style="--c: <?php echo esc_attr( $g['color'] ); ?>">
						<span class="dashicons <?php echo esc_attr( $g['icon'] ); ?>" aria-hidden="true"></span><?php echo esc_html( $g['label'] ); ?>
						<span class="spw-chip__n"><?php echo esc_html( count( $g['items'] ) ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>

			<div id="spw-widgets">
				<?php foreach ( $groups as $key => $g ) : ?>
					<section class="spw-group" data-spw-group="<?php echo esc_attr( $key ); ?>" style="--c: <?php echo esc_attr( $g['color'] ); ?>">
						<div class="spw-group__head">
							<span class="spw-group__icon dashicons <?php echo esc_attr( $g['icon'] ); ?>" aria-hidden="true"></span>
							<h2><?php echo esc_html( $g['label'] ); ?></h2>
							<span class="spw-group__count"><?php echo esc_html( count( $g['items'] ) ); ?></span>
							<span class="spw-group__tools">
								<button type="button" class="spw-linkbtn" data-spw-group-toggle="on"><?php esc_html_e( 'Enable', 'super-widget' ); ?></button>
								<button type="button" class="spw-linkbtn" data-spw-group-toggle="off"><?php esc_html_e( 'Disable', 'super-widget' ); ?></button>
							</span>
						</div>
						<div class="spw-widgets">
							<?php foreach ( $g['items'] as $slug => $data ) : ?>
								<?php $custom_name = isset( $names[ $slug ] ) ? $names[ $slug ] : ''; ?>
								<div class="spw-wcard" data-search="<?php echo esc_attr( strtolower( $data['title'] . ' ' . $custom_name . ' ' . $data['description'] . ' ' . $g['label'] ) ); ?>">
									<span class="spw-wcard__icon dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
									<span class="spw-wcard__body">
										<span class="spw-wcard__title"><?php echo esc_html( $data['title'] ); ?> <em>v<?php echo esc_html( $data['version'] ); ?></em></span>
										<span class="spw-wcard__desc"><?php echo esc_html( $data['description'] ); ?></span>
										<details class="spw-wcard__more">
											<summary><?php esc_html_e( 'Rename in Elementor', 'super-widget' ); ?></summary>
											<span class="spw-wcard__rename">
												<label class="screen-reader-text" for="spw-name-<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Name in Elementor', 'super-widget' ); ?></label>
												<input type="text" id="spw-name-<?php echo esc_attr( $slug ); ?>" class="spw-wname" name="<?php echo esc_attr( $name ); ?>[names][<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $custom_name ); ?>" placeholder="<?php echo esc_attr( $data['title'] ); ?>" maxlength="60" />
											</span>
										</details>
									</span>
									<label class="spw-switch">
										<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[widgets][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active, true ) ); ?> />
										<span class="spw-switch__track" aria-hidden="true"></span>
										<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: widget name */ __( 'Enable %s', 'super-widget' ), $data['title'] ) ); ?></span>
									</label>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
				<p class="spw-empty" hidden><?php esc_html_e( 'No widget matches. Try another word or category.', 'super-widget' ); ?></p>
			</div>

			<div class="spw-panel spw-panel--flat">
				<h2><?php esc_html_e( 'Elementor panel category', 'super-widget' ); ?></h2>
				<p>
					<label for="spw-category-name"><?php esc_html_e( 'Category name', 'super-widget' ); ?></label><br />
					<input type="text" id="spw-category-name" class="regular-text" name="<?php echo esc_attr( $name ); ?>[category_name]" value="<?php echo esc_attr( $settings_all['category_name'] ); ?>" placeholder="<?php esc_attr_e( 'Super Widget', 'super-widget' ); ?>" maxlength="40" />
				</p>
				<p class="description"><?php esc_html_e( 'Leave a name empty to use the default. Reload the Elementor editor to see changes.', 'super-widget' ); ?></p>
			</div>

			<div class="spw-savebar">
				<?php submit_button( __( 'Save widgets', 'super-widget' ), 'primary large', 'submit', false ); ?>
			</div>
		</form>

		<section class="spw-section">
			<div class="spw-section__head"><h2><?php esc_html_e( 'Planned', 'super-widget' ); ?></h2></div>
			<p class="description"><?php esc_html_e( 'These are not built yet. Each one will be added as its own module and show up here automatically.', 'super-widget' ); ?></p>
			<div class="spw-widgets spw-widgets--planned">
				<?php foreach ( $planned as $item ) : ?>
					<div class="spw-wcard is-disabled">
						<span class="spw-wcard__icon dashicons <?php echo esc_attr( $item[1] ); ?>" aria-hidden="true"></span>
						<span class="spw-wcard__body">
							<span class="spw-wcard__title"><?php echo esc_html( $item[0] ); ?></span>
							<span class="spw-wcard__desc"><?php esc_html_e( 'Coming soon', 'super-widget' ); ?></span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	/* ------------------------------- License ------------------------------- */

	private function tab_license() {
		$l      = Super_Widget_License::get();
		$valid  = Super_Widget_License::is_valid();
		$masked = '' !== $l['key'] ? substr( $l['key'], 0, 5 ) . str_repeat( '•', 8 ) . substr( $l['key'], -4 ) : '';
		$labels = array(
			'valid'    => __( 'Active', 'super-widget' ),
			'expired'  => __( 'Expired', 'super-widget' ),
			'revoked'  => __( 'Cancelled', 'super-widget' ),
			'limit'    => __( 'Site limit reached', 'super-widget' ),
			'invalid'  => __( 'Not valid', 'super-widget' ),
			'inactive' => __( 'Not activated', 'super-widget' ),
		);
		$label  = isset( $labels[ $l['status'] ] ) ? $labels[ $l['status'] ] : $labels['inactive'];
		?>
		<div class="spw-columns">
			<div class="spw-panel">
				<h2><?php esc_html_e( 'License key', 'super-widget' ); ?></h2>
				<ul class="spw-checks">
					<li class="<?php echo $valid ? 'is-ok' : 'is-bad'; ?>">
						<span class="dashicons <?php echo $valid ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
						<span class="spw-checks__label"><?php esc_html_e( 'Status', 'super-widget' ); ?></span>
						<span class="spw-checks__value"><?php echo esc_html( $label ); ?></span>
					</li>
					<?php if ( '' !== $l['key'] ) : ?>
						<li><span class="spw-checks__label"><?php esc_html_e( 'Key', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo esc_html( $masked ); ?></span></li>
					<?php endif; ?>
					<?php if ( $valid && $l['expires'] > 0 ) : ?>
						<li><span class="spw-checks__label"><?php esc_html_e( 'Updates until', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo esc_html( wp_date( get_option( 'date_format' ), $l['expires'] ) ); ?></span></li>
					<?php elseif ( $valid ) : ?>
						<li><span class="spw-checks__label"><?php esc_html_e( 'Updates until', 'super-widget' ); ?></span><span class="spw-checks__value"><?php esc_html_e( 'Lifetime', 'super-widget' ); ?></span></li>
					<?php endif; ?>
					<?php if ( $valid && $l['sites_max'] > 0 ) : ?>
						<li><span class="spw-checks__label"><?php esc_html_e( 'Sites using this key', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo esc_html( $l['sites_used'] . ' / ' . $l['sites_max'] ); ?></span></li>
					<?php endif; ?>
				</ul>
				<?php if ( ! $valid && '' !== $l['message'] ) : ?>
					<p class="description"><?php echo esc_html( $l['message'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! $valid ) : ?>
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="super_widget_license_activate" />
						<?php wp_nonce_field( 'super_widget_license_activate' ); ?>
						<p>
							<label for="spw-license-key" class="screen-reader-text"><?php esc_html_e( 'License key', 'super-widget' ); ?></label>
							<input type="text" id="spw-license-key" name="license_key" class="regular-text code" autocomplete="off" spellcheck="false" placeholder="SW-XXXX-XXXX-XXXX-XXXX" maxlength="64" />
						</p>
						<?php submit_button( __( 'Activate license', 'super-widget' ), 'primary', 'submit', false ); ?>
					</form>
				<?php else : ?>
					<div class="spw-actions">
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="super_widget_license_check" />
							<?php wp_nonce_field( 'super_widget_license_check' ); ?>
							<?php submit_button( __( 'Check status', 'super-widget' ), 'secondary', 'submit', false ); ?>
						</form>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-spw-confirm="<?php esc_attr_e( 'Remove the license from this site?', 'super-widget' ); ?>">
							<input type="hidden" name="action" value="super_widget_license_deactivate" />
							<?php wp_nonce_field( 'super_widget_license_deactivate' ); ?>
							<?php submit_button( __( 'Deactivate on this site', 'super-widget' ), 'delete', 'submit', false ); ?>
						</form>
					</div>
				<?php endif; ?>
			</div>

			<div class="spw-panel">
				<h2><?php esc_html_e( 'What the license does', 'super-widget' ); ?></h2>
				<p><?php esc_html_e( 'The widgets work with or without a license. A valid license gives you automatic updates and support.', 'super-widget' ); ?></p>
				<p class="description"><?php esc_html_e( 'Activating sends your key, this site\'s address and the plugin version to the license server. Nothing else is sent. Moving to another site? Deactivate here first, then activate there.', 'super-widget' ); ?></p>
			</div>
		</div>
		<?php
	}

	/* ------------------------------- Settings ------------------------------- */

	private function tab_settings() {
		$s    = Super_Widget_Settings::get_all();
		$name = Super_Widget_Settings::OPTION;
		?>
		<form action="options.php" method="post">
			<?php settings_fields( Super_Widget_Settings::GROUP ); ?>
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[_tab]" value="settings" />

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Animations', 'super-widget' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[disable_animations]" value="1" <?php checked( ! empty( $s['disable_animations'] ) ); ?> />
								<?php esc_html_e( 'Disable all entrance and hover animations on the whole site', 'super-widget' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Visitors who set "reduce motion" in their system always get no animation, regardless of this setting.', 'super-widget' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spw-accent"><?php esc_html_e( 'Accent color', 'super-widget' ); ?></label></th>
						<td>
							<input type="color" class="spw-color-picker" value="<?php echo esc_attr( $s['accent_color'] ? $s['accent_color'] : '#f2790d' ); ?>" aria-label="<?php esc_attr_e( 'Pick a color', 'super-widget' ); ?>" />
							<input type="text" id="spw-accent" class="spw-color-text" name="<?php echo esc_attr( $name ); ?>[accent_color]" value="<?php echo esc_attr( $s['accent_color'] ); ?>" placeholder="#f2790d" maxlength="7" size="10" />
							<button type="button" class="button button-small" data-spw-clear-color><?php esc_html_e( 'Use default', 'super-widget' ); ?></button>
							<p class="description"><?php esc_html_e( 'Default color for buttons and focus rings in all widgets. A color set inside an Elementor widget still wins.', 'super-widget' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spw-update-source"><?php esc_html_e( 'Updates', 'super-widget' ); ?></label></th>
						<td>
							<input type="text" id="spw-update-source" class="regular-text code" name="<?php echo esc_attr( $name ); ?>[update_source]" value="<?php echo esc_attr( $s['update_source'] ); ?>" placeholder="<?php esc_attr_e( 'your-name/super-widget  or  https://example.com/super-widget.json', 'super-widget' ); ?>" />
							<p class="description"><?php esc_html_e( 'Where this site looks for new versions: a GitHub "owner/repository" (uses its latest release) or an https link to a JSON file. Leave empty to use the address built into the plugin.', 'super-widget' ); ?></p>
							<p>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[auto_update]" value="1" <?php checked( ! empty( $s['auto_update'] ) ); ?> />
									<?php esc_html_e( 'Install new versions automatically', 'super-widget' ); ?>
								</label>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Uninstall', 'super-widget' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[delete_data]" value="1" <?php checked( ! empty( $s['delete_data'] ) ); ?> />
								<?php esc_html_e( 'Delete Super Widget settings when the plugin is deleted', 'super-widget' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Content you built in Elementor is never touched.', 'super-widget' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( __( 'Save settings', 'super-widget' ) ); ?>
		</form>
		<?php
	}

	/* -------------------------------- Tools -------------------------------- */

	private function tab_tools() {
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=super_widget_export' ), 'super_widget_export' );
		$check_url  = wp_nonce_url( admin_url( 'admin-post.php?action=super_widget_check_update' ), 'super_widget_check_update' );
		list( $up_type, $up_value ) = Super_Widget_Updater::source();
		$remote                     = Super_Widget_Updater::remote();
		?>
		<div class="spw-panel">
			<h2><?php esc_html_e( 'Updates', 'super-widget' ); ?></h2>
			<ul class="spw-checks">
				<li class="is-ok"><span class="spw-checks__label"><?php esc_html_e( 'Installed version', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo esc_html( SUPER_WIDGET_VERSION ); ?></span></li>
				<li class="<?php echo '' !== $up_type ? 'is-ok' : 'is-bad'; ?>"><span class="spw-checks__label"><?php esc_html_e( 'Update source', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo '' !== $up_type ? esc_html( $up_value ) : esc_html__( 'Not set', 'super-widget' ); ?></span></li>
				<li class="is-ok"><span class="spw-checks__label"><?php esc_html_e( 'Latest version found', 'super-widget' ); ?></span><span class="spw-checks__value"><?php echo $remote ? esc_html( $remote['version'] ) : '-'; ?></span></li>
			</ul>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $check_url ); ?>"><?php esc_html_e( 'Check for updates now', 'super-widget' ); ?></a>
			</p>
			<p class="description"><?php esc_html_e( 'The check runs only in the admin (about twice a day), sends no site data, and downloads only from GitHub or your own update address over https.', 'super-widget' ); ?></p>
		</div>

		<div class="spw-panel">
			<h2><?php esc_html_e( 'Export settings', 'super-widget' ); ?></h2>
			<p><?php esc_html_e( 'Download your settings as a JSON file to move them to another site.', 'super-widget' ); ?></p>
			<a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Download JSON', 'super-widget' ); ?></a>
		</div>

		<div class="spw-panel">
			<h2><?php esc_html_e( 'Import settings', 'super-widget' ); ?></h2>
			<p><?php esc_html_e( 'Paste the contents of an exported JSON file. Unknown or invalid values are ignored.', 'super-widget' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="super_widget_import" />
				<?php wp_nonce_field( 'super_widget_import' ); ?>
				<textarea name="super_widget_import" rows="8" class="large-text code" spellcheck="false" placeholder='{"settings": {"widgets": ["card-grid"]}}'></textarea>
				<?php submit_button( __( 'Import', 'super-widget' ), 'secondary', 'submit', true ); ?>
			</form>
		</div>

		<div class="spw-panel">
			<h2><?php esc_html_e( 'Reset', 'super-widget' ); ?></h2>
			<p><?php esc_html_e( 'Restore every setting to its default (all widgets on, default colors, animations on).', 'super-widget' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-spw-confirm="<?php esc_attr_e( 'Reset all Super Widget settings?', 'super-widget' ); ?>">
				<input type="hidden" name="action" value="super_widget_reset" />
				<?php wp_nonce_field( 'super_widget_reset' ); ?>
				<?php submit_button( __( 'Reset to defaults', 'super-widget' ), 'delete', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/* --------------------------------- Help --------------------------------- */

	private function tab_help() {
		$theme = wp_get_theme();
		list( , $el_version ) = $this->elementor_status();

		$info = array(
			'Super Widget'  => SUPER_WIDGET_VERSION,
			'WordPress'     => get_bloginfo( 'version' ),
			'PHP'           => PHP_VERSION,
			'Elementor'     => $el_version,
			'Theme'         => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
			'Memory limit'  => WP_MEMORY_LIMIT,
			'Active widgets' => implode( ', ', Super_Widget_Settings::active_widgets() ),
		);

		$text = '';
		foreach ( $info as $label => $value ) {
			$text .= $label . ': ' . $value . "\n";
		}
		?>
		<div class="spw-columns">
			<div class="spw-panel">
				<h2><?php esc_html_e( 'Getting started', 'super-widget' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Edit a page with Elementor.', 'super-widget' ); ?></li>
					<li><?php esc_html_e( 'Search for "Card Grid" or open the "Super Widget" category and drag the widget in.', 'super-widget' ); ?></li>
					<li><?php esc_html_e( 'Use the Cards tab to add cards, the Grid Layout tab for columns, and the Style tab for colors, shadow and animation.', 'super-widget' ); ?></li>
				</ol>

				<h2><?php esc_html_e( 'Adding a new widget module', 'super-widget' ); ?></h2>
				<p><?php esc_html_e( 'Create a folder in widgets/ (for example icon-box) with a PHP file of the same name that defines class SPW_Icon_Box_Widget. Optional files: meta.php, assets/css/icon-box.css and assets/js/icon-box.js. The widget appears in Elementor and on the Widgets tab automatically.', 'super-widget' ); ?></p>
			</div>

			<div class="spw-panel">
				<h2><?php esc_html_e( 'System info', 'super-widget' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Copy this when you ask for support.', 'super-widget' ); ?></p>
				<textarea id="spw-sysinfo" class="large-text code" rows="8" readonly><?php echo esc_textarea( $text ); ?></textarea>
				<p><button type="button" class="button" data-spw-copy="#spw-sysinfo"><?php esc_html_e( 'Copy', 'super-widget' ); ?></button></p>
			</div>
		</div>
		<?php
	}
}
