<?php
/**
 * Dependency Checker for plugin requirements.
 *
 * @package Shift64\SmartPhoneValidation\Admin
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Admin;

/**
 * Checks if required plugin dependencies are active.
 */
class DependencyChecker {

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Display admin notice when WooCommerce is not active.
	 *
	 * @return void
	 */
	public static function display_woocommerce_missing_notice(): void {
		add_action( 'admin_notices', array( self::class, 'render_woocommerce_notice' ) );
	}

	/**
	 * Render the WooCommerce missing admin notice.
	 *
	 * @return void
	 */
	public static function render_woocommerce_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo esc_html__(
					'Verify Phone Number Shift64 requires WooCommerce to be installed and activated.',
					'verify-phone-number-shift64'
				);
				?>
			</p>
		</div>
		<?php
	}
}
