<?php
/**
 * Dukkan self-updater for the TranslatePress fork.
 *
 * Injects the latest GitHub release into WordPress's native update UI so
 * the site admin sees "update now" on the Plugins screen. There is no
 * scheduled check and no automatic install; updates happen only when an
 * admin clicks "update now" (or enables WordPress auto-updates).
 *
 * Also suppresses the WordPress.org update check for this plugin so that
 * updates come exclusively from our own GitHub repository.
 *
 * @link       https://dukkanwoocommerce.com
 * @since      1.0.0
 * @package    Dukkan_TranslatePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dukkan_TranslatePress_Updater {

	/**
	 * URL of the version.json file in the GitHub repository.
	 *
	 * @var string
	 */
	const VERSION_URL = 'https://raw.githubusercontent.com/jodukkan-max/translatepress/main/version.json';

	/**
	 * Transient key and expiry for the cached version.json payload.
	 *
	 * @var string
	 */
	const CACHE_KEY    = 'dukkan_translatepress_latest_version';
	const CACHE_EXPIRY = 4 * HOUR_IN_SECONDS;

	/**
	 * The plugin slug (folder name).
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'translatepress-multilingual';

	/**
	 * The plugin basename (translatepress-multilingual/index.php).
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Full filesystem path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * The current plugin version.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Initialize the updater and register hooks.
	 *
	 * @param string $plugin_file  Full path to the main plugin file.
	 * @param string $version      Current plugin version.
	 */
	public function __construct( $plugin_file, $version ) {
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->plugin_file     = $plugin_file;
		$this->current_version = $version;

		// Inject into WP's native update UI (priority 20 so it runs after the
		// core WordPress.org check and can overwrite it).
		add_filter( 'site_transient_update_plugins', array( $this, 'check_for_update' ), 20, 1 );

		// Show the "Enable auto-updates" toggle for this GitHub-hosted plugin.
		add_filter( 'plugin_auto_update_setting_html', array( $this, 'auto_update_toggle_html' ), 10, 3 );

		// Remove this plugin from the WordPress.org update check so updates
		// come only from our GitHub repository.
		add_filter( 'http_request_args', array( $this, 'block_wordpress_org_check' ), 10, 2 );
	}

	// -----------------------------------------------------------------
	// WP native update UI injection.
	// -----------------------------------------------------------------

	/**
	 * Inject the latest release into WordPress's update transient.
	 *
	 * @param  object $transient  WordPress update transient.
	 * @return object  Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$data = $this->fetch_version_data();
		if ( ! $data ) {
			return $transient;
		}

		if ( version_compare( $data['version'], $this->current_version, '>' ) ) {
			$transient->response[ $this->plugin_basename ] = (object) array(
				'slug'        => self::PLUGIN_SLUG,
				'plugin'      => $this->plugin_basename,
				'new_version' => $data['version'],
				'package'     => $data['package'],
				'url'         => 'https://dukkanwoocommerce.com',
				'requires'    => isset( $data['requires'] ) ? $data['requires'] : '5.0',
				'tested'      => isset( $data['tested'] ) ? $data['tested'] : '',
			);
		} else {
			// Make sure no stale WordPress.org entry survives for our plugin.
			unset( $transient->response[ $this->plugin_basename ] );
		}

		return $transient;
	}

	// -----------------------------------------------------------------
	// Internal helpers.
	// -----------------------------------------------------------------

	/**
	 * Fetch the latest version.json from GitHub, cached for a few hours.
	 *
	 * @return array|null  Decoded version data, or null on failure.
	 */
	private function fetch_version_data() {
		static $data = null;

		// The update transient filter runs several times per page load; reuse
		// the result in-memory so we only touch storage/network once.
		if ( null !== $data ) {
			return $data;
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			$data = $cached;
			return $data;
		}

		$response = wp_remote_get( self::VERSION_URL, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['version'] ) || empty( $body['package'] ) ) {
			return null;
		}

		set_transient( self::CACHE_KEY, $body, self::CACHE_EXPIRY );
		$data = $body;

		return $data;
	}

	/**
	 * Remove this plugin from the WordPress.org update check request body.
	 *
	 * WordPress.org returns updates for any plugin whose slug matches a hosted
	 * listing. Since this fork keeps the original slug, we strip it from the
	 * check so the only update source is our own version.json.
	 *
	 * @param  array  $args  HTTP request arguments.
	 * @param  string $url   Request URL.
	 * @return array  Modified arguments.
	 */
	public function block_wordpress_org_check( $args, $url ) {
		if ( 0 !== strpos( $url, 'https://api.wordpress.org/plugins/update-check/' ) ) {
			return $args;
		}

		if ( empty( $args['body']['plugins'] ) ) {
			return $args;
		}

		$plugins = json_decode( $args['body']['plugins'], true );
		if ( ! is_array( $plugins ) || empty( $plugins['plugins'] ) ) {
			return $args;
		}

		if ( isset( $plugins['plugins'][ $this->plugin_basename ] ) ) {
			unset( $plugins['plugins'][ $this->plugin_basename ] );
			$args['body']['plugins'] = wp_json_encode( $plugins );
		}

		return $args;
	}

	/**
	 * Show the "Enable auto-updates" / "Disable auto-updates" toggle for this
	 * plugin in the Plugins list screen.
	 *
	 * WordPress only renders this toggle for wordpress.org-hosted plugins.
	 * Since this fork is self-hosted on GitHub, we inject the HTML manually.
	 *
	 * @param  string $html         Existing HTML (empty for self-hosted plugins).
	 * @param  string $plugin_file  Plugin basename.
	 * @param  array  $plugin_data  Plugin header data from get_plugins().
	 * @return string Modified HTML.
	 */
	public function auto_update_toggle_html( $html, $plugin_file, $plugin_data ) {
		// Only affect this plugin.
		if ( $plugin_file !== $this->plugin_basename ) {
			return $html;
		}

		// Bail if site-wide auto-updates are completely disabled.
		if ( wp_is_auto_update_forced_for_item( 'plugin', false, 'disabled' ) ) {
			return $html;
		}

		$auto_updates = (array) get_site_option( 'auto_update_plugins', array() );
		$enabled      = in_array( $plugin_file, $auto_updates, true );

		if ( $enabled ) {
			$action = 'disable-auto-update';
			$label  = __( 'Disable auto-updates' );
			$css    = 'auto-update-disabled';
		} else {
			$action = 'enable-auto-update';
			$label  = __( 'Enable auto-updates' );
			$css    = 'auto-update-enabled';
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => $action,
					'plugin' => $plugin_file,
				),
				'plugins.php'
			),
			'updates'
		);

		return sprintf(
			'<a href="%s" class="%s" data-wp-action="%s" aria-label="%s">%s</a>',
			esc_url( $url ),
			$css,
			$enabled ? 'disable' : 'enable',
			esc_attr( $label ),
			$label
		);
	}
}
