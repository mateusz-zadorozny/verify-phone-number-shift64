<?php

namespace Shift64\SmartPhoneValidation\Tests\Unit;

use Shift64\SmartPhoneValidation\Admin\GitHubUpdater;

class GitHubUpdaterTest extends TestCase {

	const MASTER_DIR = 'verify-phone-number-shift64-master/verify-phone-number-shift64.php';

	private function cache_release( string $tag ): void {
		$GLOBALS['shift64_test_transients'][ GitHubUpdater::TRANSIENT_KEY ] = array(
			'tag_name' => $tag,
			'html_url' => 'https://example.test/release',
			'assets'   => array(
				array(
					'name'                 => 'verify-phone-number-shift64.zip',
					'browser_download_url' => 'https://example.test/plugin.zip',
				),
			),
		);
	}

	private function transient( string $plugin_file ): object {
		return (object) array(
			'checked'  => array( $plugin_file => '1.0.0' ),
			'response' => array(),
		);
	}

	public function test_update_is_offered_under_the_default_directory(): void {
		$this->cache_release( 'v9.9.9' );
		$file = 'verify-phone-number-shift64/verify-phone-number-shift64.php';

		$result = GitHubUpdater::check_for_update( $this->transient( $file ) );

		$this->assertSame( array( $file ), array_keys( $result->response ) );
		$this->assertSame( '9.9.9', $result->response[ $file ]->new_version );
		$this->assertSame( $file, $result->response[ $file ]->plugin );
		$this->assertSame( 'https://example.test/plugin.zip', $result->response[ $file ]->package );
	}

	/**
	 * Regression for issue #6: WordPress matches updates by plugin file, so the
	 * key must follow the directory the plugin is really installed in.
	 */
	public function test_update_is_offered_when_installed_in_another_directory(): void {
		$this->cache_release( 'v9.9.9' );
		$GLOBALS['shift64_test_plugin_basename'] = self::MASTER_DIR;

		$result = GitHubUpdater::check_for_update( $this->transient( self::MASTER_DIR ) );

		$this->assertSame( array( self::MASTER_DIR ), array_keys( $result->response ) );
		$this->assertSame( self::MASTER_DIR, $result->response[ self::MASTER_DIR ]->plugin );
	}

	public function test_no_update_for_same_or_older_release(): void {
		$this->cache_release( 'v1.0.0' );

		$result = GitHubUpdater::check_for_update( $this->transient( GitHubUpdater::get_plugin_file() ) );

		$this->assertSame( array(), $result->response );
	}

	public function test_unpacked_folder_is_renamed_to_the_installed_directory(): void {
		$GLOBALS['shift64_test_plugin_basename'] = self::MASTER_DIR;
		$GLOBALS['wp_filesystem']                = new class() {
			public $moves = array();
			public function move( $from, $to ) {
				$this->moves[] = array( $from, $to );
				return true;
			}
		};

		$result = GitHubUpdater::fix_source_dir(
			'/tmp/upgrade/verify-phone-number-shift64/',
			'/tmp/upgrade',
			null,
			array( 'plugin' => self::MASTER_DIR )
		);

		$this->assertSame( '/tmp/upgrade/verify-phone-number-shift64-master/', $result );
		$this->assertSame(
			array( array( '/tmp/upgrade/verify-phone-number-shift64/', '/tmp/upgrade/verify-phone-number-shift64-master/' ) ),
			$GLOBALS['wp_filesystem']->moves
		);
	}

	public function test_source_is_untouched_when_folder_already_matches_or_other_plugin(): void {
		$GLOBALS['wp_filesystem'] = null;
		$source                   = '/tmp/upgrade/verify-phone-number-shift64/';

		$this->assertSame(
			$source,
			GitHubUpdater::fix_source_dir( $source, '/tmp/upgrade', null, array( 'plugin' => GitHubUpdater::get_plugin_file() ) )
		);
		$this->assertSame(
			'/tmp/upgrade/other/',
			GitHubUpdater::fix_source_dir( '/tmp/upgrade/other/', '/tmp/upgrade', null, array( 'plugin' => 'other/other.php' ) )
		);
	}

	public function test_missing_filesystem_returns_error_instead_of_fatal(): void {
		$GLOBALS['shift64_test_plugin_basename'] = self::MASTER_DIR;
		$GLOBALS['wp_filesystem']                = null;

		$result = GitHubUpdater::fix_source_dir(
			'/tmp/upgrade/verify-phone-number-shift64/',
			'/tmp/upgrade',
			null,
			array( 'plugin' => self::MASTER_DIR )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	private function changelog( string $body ): string {
		$this->cache_release( 'v9.9.9' );
		$GLOBALS['shift64_test_transients'][ GitHubUpdater::TRANSIENT_KEY ]['body'] = $body;

		$info = GitHubUpdater::plugin_info( false, 'plugin_information', (object) array( 'slug' => GitHubUpdater::PLUGIN_SLUG ) );

		return $info->sections['changelog'];
	}

	public function test_release_notes_are_converted_to_clean_html(): void {
		$body = "## [1.2.0](https://example.test) (2026-09-21)\r\n\r\n### Features\r\n\r\n* add filters\r\n* add helper\r\n\r\n### Bug Fixes\n\n- one fix\nplain line";

		$this->assertSame(
			'<h3>[1.2.0](https://example.test) (2026-09-21)</h3>'
			. '<h4>Features</h4><ul><li>add filters</li><li>add helper</li></ul>'
			. '<h4>Bug Fixes</h4><ul><li>one fix</li></ul><p>plain line</p>',
			$this->changelog( $body )
		);
	}

	public function test_empty_release_notes(): void {
		$this->assertSame( '<p>No changelog available.</p>', $this->changelog( '' ) );
	}

	public function test_plugin_info_ignores_other_plugins(): void {
		$this->assertFalse( GitHubUpdater::plugin_info( false, 'plugin_information', (object) array( 'slug' => 'other' ) ) );
		$this->assertFalse( GitHubUpdater::plugin_info( false, 'query_plugins', (object) array() ) );
	}
}
