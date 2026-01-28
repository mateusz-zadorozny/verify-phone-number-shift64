<?php
/**
 * GitHub-based auto-updater for the plugin.
 *
 * @package Shift64\SmartPhoneValidation\Admin
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Admin;

/**
 * Handles plugin updates from GitHub releases.
 *
 * Checks GitHub releases for new versions and integrates with
 * WordPress plugin update system to enable one-click updates.
 */
class GitHubUpdater {

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	const GITHUB_OWNER = 'mateusz-zadorozny';

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	const GITHUB_REPO = 'verify-phone-number-shift64';

	/**
	 * Plugin slug (directory name).
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'verify-phone-number-shift64';

	/**
	 * Transient key for caching release info.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'shift64_phone_validation_github_release';

	/**
	 * Cache duration in seconds (12 hours).
	 *
	 * @var int
	 */
	const CACHE_DURATION = 43200;

	/**
	 * Error cache duration in seconds (1 hour).
	 *
	 * @var int
	 */
	const ERROR_CACHE_DURATION = 3600;

	/**
	 * Initialize updater hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( self::class, 'check_for_update' ) );
		add_filter( 'plugins_api', array( self::class, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( self::class, 'fix_source_dir' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( self::class, 'clear_cache' ), 10, 0 );
	}

	/**
	 * Check for plugin updates from GitHub.
	 *
	 * Hooks into WordPress update check to inject our plugin update info.
	 *
	 * @param object $transient The update_plugins transient value.
	 * @return object Modified transient with our update info.
	 */
	public static function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release_info = self::get_release_info();
		if ( null === $release_info ) {
			return $transient;
		}

		$new_version = self::normalize_version( $release_info['tag_name'] );
		$plugin_file = self::PLUGIN_SLUG . '/verify-phone-number-shift64.php';

		if ( ! self::is_update_available( $new_version ) ) {
			return $transient;
		}

		$download_url = self::get_download_url( $release_info );
		if ( null === $download_url ) {
			return $transient;
		}

		$transient->response[ $plugin_file ] = (object) array(
			'slug'         => self::PLUGIN_SLUG,
			'plugin'       => $plugin_file,
			'new_version'  => $new_version,
			'url'          => $release_info['html_url'],
			'package'      => $download_url,
			'icons'        => array(),
			'banners'      => array(),
			'tested'       => '',
			'requires'     => '5.0',
			'requires_php' => '7.4',
		);

		return $transient;
	}

	/**
	 * Provide plugin information for the "View details" modal.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Plugin information or false if not our plugin.
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || self::PLUGIN_SLUG !== $args->slug ) {
			return $result;
		}

		$release_info = self::get_release_info();
		if ( null === $release_info ) {
			return $result;
		}

		$new_version  = self::normalize_version( $release_info['tag_name'] );
		$download_url = self::get_download_url( $release_info );

		return (object) array(
			'name'           => 'Verify Phone Number Shift64',
			'slug'           => self::PLUGIN_SLUG,
			'version'        => $new_version,
			'author'         => '<a href="https://shift64.com">Shift64</a>',
			'author_profile' => 'https://shift64.com',
			'homepage'       => 'https://shift64.com/plugins/verify-phone-number',
			'download_link'  => $download_url,
			'requires'       => '5.0',
			'tested'         => '',
			'requires_php'   => '7.4',
			'sections'       => array(
				'description' => 'Smart phone number validation and formatting for WordPress using Google\'s libphonenumber library.',
				'changelog'   => self::format_changelog( $release_info['body'] ?? '' ),
			),
			'banners'        => array(),
		);
	}

	/**
	 * Fix the extracted folder name from GitHub releases.
	 *
	 * GitHub extracts zips into folders named "repo-version", but WordPress
	 * expects the folder to match the plugin slug.
	 *
	 * @param string       $source        File source location.
	 * @param string       $remote_source Remote file source location.
	 * @param \WP_Upgrader $upgrader      WP_Upgrader instance.
	 * @param array        $hook_extra    Extra arguments passed to hooked filters.
	 * @return string|WP_Error Corrected source path or WP_Error on failure.
	 */
	public static function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		// Only process our plugin.
		if ( ! isset( $hook_extra['plugin'] ) ) {
			return $source;
		}

		$plugin_file = self::PLUGIN_SLUG . '/verify-phone-number-shift64.php';
		if ( $hook_extra['plugin'] !== $plugin_file ) {
			return $source;
		}

		// Check if source directory needs renaming.
		$source_base = basename( $source );
		if ( self::PLUGIN_SLUG === $source_base ) {
			return $source;
		}

		// Rename to expected plugin slug.
		$corrected_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
		if ( $wp_filesystem->move( $source, $corrected_source ) ) {
			return $corrected_source;
		}

		return new \WP_Error(
			'rename_failed',
			__( 'Failed to rename plugin directory.', 'verify-phone-number-shift64' )
		);
	}

	/**
	 * Get release info from GitHub (cached).
	 *
	 * @return array|null Release data or null on failure.
	 */
	public static function get_release_info(): ?array {
		$cached = get_transient( self::TRANSIENT_KEY );

		// Return cached data if available and not an error.
		if ( false !== $cached ) {
			if ( 'error' === $cached ) {
				return null;
			}
			return $cached;
		}

		$release_info = self::fetch_github_release();

		if ( null === $release_info ) {
			// Cache error state for shorter duration.
			set_transient( self::TRANSIENT_KEY, 'error', self::ERROR_CACHE_DURATION );
			return null;
		}

		// Cache successful response.
		set_transient( self::TRANSIENT_KEY, $release_info, self::CACHE_DURATION );

		return $release_info;
	}

	/**
	 * Fetch latest release data from GitHub API.
	 *
	 * @return array|null Release data or null on failure.
	 */
	private static function fetch_github_release(): ?array {
		$api_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			self::GITHUB_OWNER,
			self::GITHUB_REPO
		);

		$response = wp_safe_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Extract download URL from release assets.
	 *
	 * Looks for the distribution zip file in release assets.
	 *
	 * @param array $release_info GitHub release data.
	 * @return string|null Download URL or null if not found.
	 */
	private static function get_download_url( array $release_info ): ?string {
		$expected_asset = self::PLUGIN_SLUG . '.zip';

		if ( empty( $release_info['assets'] ) || ! is_array( $release_info['assets'] ) ) {
			return null;
		}

		foreach ( $release_info['assets'] as $asset ) {
			if ( isset( $asset['name'] ) && $expected_asset === $asset['name'] ) {
				return $asset['browser_download_url'] ?? null;
			}
		}

		return null;
	}

	/**
	 * Normalize version string by removing 'v' prefix.
	 *
	 * @param string $version Version string (e.g., "v1.0.2").
	 * @return string Normalized version (e.g., "1.0.2").
	 */
	private static function normalize_version( string $version ): string {
		return ltrim( $version, 'vV' );
	}

	/**
	 * Check if an update is available.
	 *
	 * @param string $new_version The new version from GitHub.
	 * @return bool True if update is available.
	 */
	private static function is_update_available( string $new_version ): bool {
		$current_version = SHIFT64_PHONE_VALIDATION_VERSION;
		return version_compare( $new_version, $current_version, '>' );
	}

	/**
	 * Format changelog text from GitHub release body.
	 *
	 * @param string $body GitHub release body (markdown).
	 * @return string Formatted changelog HTML.
	 */
	private static function format_changelog( string $body ): string {
		if ( empty( $body ) ) {
			return '<p>' . __( 'No changelog available.', 'verify-phone-number-shift64' ) . '</p>';
		}

		// Basic markdown to HTML conversion.
		$changelog = esc_html( $body );
		$changelog = nl2br( $changelog );

		// Convert markdown headers.
		$changelog = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $changelog );
		$changelog = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $changelog );

		// Convert markdown lists.
		$changelog = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $changelog );
		$changelog = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $changelog );

		return $changelog;
	}

	/**
	 * Clear the cached release info.
	 *
	 * Called on plugin deactivation and after successful update.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
