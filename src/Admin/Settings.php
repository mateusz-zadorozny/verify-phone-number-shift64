<?php
/**
 * WooCommerce Settings integration for phone validation.
 *
 * @package Shift64\SmartPhoneValidation\Admin
 */

declare(strict_types=1);

namespace Shift64\SmartPhoneValidation\Admin;

/**
 * Manages plugin settings within WooCommerce settings pages.
 */
class Settings {

	/**
	 * Settings tab ID.
	 *
	 * @var string
	 */
	const TAB_ID = 'phone_validation';

	/**
	 * Option prefix for all settings.
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'shift64_phone_validation_';

	/**
	 * Initialize settings hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'woocommerce_settings_tabs_array', array( self::class, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_' . self::TAB_ID, array( self::class, 'render_settings_page' ) );
		add_action( 'woocommerce_update_options_' . self::TAB_ID, array( self::class, 'save_settings' ) );
	}

	/**
	 * Add settings tab to WooCommerce settings.
	 *
	 * @param array $tabs Existing WooCommerce settings tabs.
	 * @return array Modified tabs array.
	 */
	public static function add_settings_tab( array $tabs ): array {
		$tabs[ self::TAB_ID ] = __( 'Phone Validation', 'verify-phone-number-shift64' );
		return $tabs;
	}

	/**
	 * Render the settings page content.
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Save settings to wp_options.
	 *
	 * @return void
	 */
	public static function save_settings(): void {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all settings fields configuration.
	 *
	 * @return array Settings fields array.
	 */
	public static function get_settings(): array {
		$settings = array(
			array(
				'title' => __( 'Phone Validation Settings', 'verify-phone-number-shift64' ),
				'type'  => 'title',
				'desc'  => __( 'Configure phone number validation and formatting behavior.', 'verify-phone-number-shift64' ),
				'id'    => self::OPTION_PREFIX . 'section_title',
			),
			array(
				'title'    => __( 'Enable Validation', 'verify-phone-number-shift64' ),
				'desc'     => __( 'Enable phone number validation globally', 'verify-phone-number-shift64' ),
				'id'       => self::OPTION_PREFIX . 'enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc_tip' => __( 'When enabled, phone numbers will be validated during checkout.', 'verify-phone-number-shift64' ),
			),
			array(
				'title'    => __( 'Default Country', 'verify-phone-number-shift64' ),
				'desc'     => __( 'Select the default country code for phone validation.', 'verify-phone-number-shift64' ),
				'id'       => self::OPTION_PREFIX . 'default_country',
				'default'  => 'PL',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => self::get_country_options(),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Validation Mode', 'verify-phone-number-shift64' ),
				'desc'     => __( 'Choose how phone numbers should be validated.', 'verify-phone-number-shift64' ),
				'id'       => self::OPTION_PREFIX . 'validation_mode',
				'default'  => 'default_and_international',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => array(
					'default_and_international' => __( 'Default country + international', 'verify-phone-number-shift64' ),
					'international_only'        => __( 'International only', 'verify-phone-number-shift64' ),
				),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Output Format', 'verify-phone-number-shift64' ),
				'desc'     => __( 'Select the format for storing phone numbers.', 'verify-phone-number-shift64' ),
				'id'       => self::OPTION_PREFIX . 'output_format',
				'default'  => 'E164',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => array(
					'E164'          => __( 'E.164 (e.g., +48123456789)', 'verify-phone-number-shift64' ),
					'INTERNATIONAL' => __( 'International (e.g., +48 123 456 789)', 'verify-phone-number-shift64' ),
					'NATIONAL'      => __( 'National (e.g., 123 456 789)', 'verify-phone-number-shift64' ),
				),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Enable Formatting on Save', 'verify-phone-number-shift64' ),
				'desc'     => __( 'Automatically format phone numbers when saving', 'verify-phone-number-shift64' ),
				'id'       => self::OPTION_PREFIX . 'format_on_save',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc_tip' => __( 'When enabled, phone numbers will be reformatted to the selected output format before saving.', 'verify-phone-number-shift64' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => self::OPTION_PREFIX . 'section_end',
			),
		);

		return $settings;
	}

	/**
	 * Get list of countries with ISO-2 codes.
	 *
	 * @return array Associative array of country codes and names.
	 */
	public static function get_country_options(): array {
		$countries = array(
			'AF' => __( 'Afghanistan', 'verify-phone-number-shift64' ),
			'AL' => __( 'Albania', 'verify-phone-number-shift64' ),
			'DZ' => __( 'Algeria', 'verify-phone-number-shift64' ),
			'AS' => __( 'American Samoa', 'verify-phone-number-shift64' ),
			'AD' => __( 'Andorra', 'verify-phone-number-shift64' ),
			'AO' => __( 'Angola', 'verify-phone-number-shift64' ),
			'AI' => __( 'Anguilla', 'verify-phone-number-shift64' ),
			'AQ' => __( 'Antarctica', 'verify-phone-number-shift64' ),
			'AG' => __( 'Antigua and Barbuda', 'verify-phone-number-shift64' ),
			'AR' => __( 'Argentina', 'verify-phone-number-shift64' ),
			'AM' => __( 'Armenia', 'verify-phone-number-shift64' ),
			'AW' => __( 'Aruba', 'verify-phone-number-shift64' ),
			'AU' => __( 'Australia', 'verify-phone-number-shift64' ),
			'AT' => __( 'Austria', 'verify-phone-number-shift64' ),
			'AZ' => __( 'Azerbaijan', 'verify-phone-number-shift64' ),
			'BS' => __( 'Bahamas', 'verify-phone-number-shift64' ),
			'BH' => __( 'Bahrain', 'verify-phone-number-shift64' ),
			'BD' => __( 'Bangladesh', 'verify-phone-number-shift64' ),
			'BB' => __( 'Barbados', 'verify-phone-number-shift64' ),
			'BY' => __( 'Belarus', 'verify-phone-number-shift64' ),
			'BE' => __( 'Belgium', 'verify-phone-number-shift64' ),
			'BZ' => __( 'Belize', 'verify-phone-number-shift64' ),
			'BJ' => __( 'Benin', 'verify-phone-number-shift64' ),
			'BM' => __( 'Bermuda', 'verify-phone-number-shift64' ),
			'BT' => __( 'Bhutan', 'verify-phone-number-shift64' ),
			'BO' => __( 'Bolivia', 'verify-phone-number-shift64' ),
			'BA' => __( 'Bosnia and Herzegovina', 'verify-phone-number-shift64' ),
			'BW' => __( 'Botswana', 'verify-phone-number-shift64' ),
			'BR' => __( 'Brazil', 'verify-phone-number-shift64' ),
			'BN' => __( 'Brunei', 'verify-phone-number-shift64' ),
			'BG' => __( 'Bulgaria', 'verify-phone-number-shift64' ),
			'BF' => __( 'Burkina Faso', 'verify-phone-number-shift64' ),
			'BI' => __( 'Burundi', 'verify-phone-number-shift64' ),
			'KH' => __( 'Cambodia', 'verify-phone-number-shift64' ),
			'CM' => __( 'Cameroon', 'verify-phone-number-shift64' ),
			'CA' => __( 'Canada', 'verify-phone-number-shift64' ),
			'CV' => __( 'Cape Verde', 'verify-phone-number-shift64' ),
			'KY' => __( 'Cayman Islands', 'verify-phone-number-shift64' ),
			'CF' => __( 'Central African Republic', 'verify-phone-number-shift64' ),
			'TD' => __( 'Chad', 'verify-phone-number-shift64' ),
			'CL' => __( 'Chile', 'verify-phone-number-shift64' ),
			'CN' => __( 'China', 'verify-phone-number-shift64' ),
			'CO' => __( 'Colombia', 'verify-phone-number-shift64' ),
			'KM' => __( 'Comoros', 'verify-phone-number-shift64' ),
			'CG' => __( 'Congo', 'verify-phone-number-shift64' ),
			'CD' => __( 'Congo, Democratic Republic', 'verify-phone-number-shift64' ),
			'CR' => __( 'Costa Rica', 'verify-phone-number-shift64' ),
			'HR' => __( 'Croatia', 'verify-phone-number-shift64' ),
			'CU' => __( 'Cuba', 'verify-phone-number-shift64' ),
			'CY' => __( 'Cyprus', 'verify-phone-number-shift64' ),
			'CZ' => __( 'Czech Republic', 'verify-phone-number-shift64' ),
			'DK' => __( 'Denmark', 'verify-phone-number-shift64' ),
			'DJ' => __( 'Djibouti', 'verify-phone-number-shift64' ),
			'DM' => __( 'Dominica', 'verify-phone-number-shift64' ),
			'DO' => __( 'Dominican Republic', 'verify-phone-number-shift64' ),
			'EC' => __( 'Ecuador', 'verify-phone-number-shift64' ),
			'EG' => __( 'Egypt', 'verify-phone-number-shift64' ),
			'SV' => __( 'El Salvador', 'verify-phone-number-shift64' ),
			'GQ' => __( 'Equatorial Guinea', 'verify-phone-number-shift64' ),
			'ER' => __( 'Eritrea', 'verify-phone-number-shift64' ),
			'EE' => __( 'Estonia', 'verify-phone-number-shift64' ),
			'ET' => __( 'Ethiopia', 'verify-phone-number-shift64' ),
			'FJ' => __( 'Fiji', 'verify-phone-number-shift64' ),
			'FI' => __( 'Finland', 'verify-phone-number-shift64' ),
			'FR' => __( 'France', 'verify-phone-number-shift64' ),
			'GA' => __( 'Gabon', 'verify-phone-number-shift64' ),
			'GM' => __( 'Gambia', 'verify-phone-number-shift64' ),
			'GE' => __( 'Georgia', 'verify-phone-number-shift64' ),
			'DE' => __( 'Germany', 'verify-phone-number-shift64' ),
			'GH' => __( 'Ghana', 'verify-phone-number-shift64' ),
			'GR' => __( 'Greece', 'verify-phone-number-shift64' ),
			'GL' => __( 'Greenland', 'verify-phone-number-shift64' ),
			'GD' => __( 'Grenada', 'verify-phone-number-shift64' ),
			'GU' => __( 'Guam', 'verify-phone-number-shift64' ),
			'GT' => __( 'Guatemala', 'verify-phone-number-shift64' ),
			'GN' => __( 'Guinea', 'verify-phone-number-shift64' ),
			'GW' => __( 'Guinea-Bissau', 'verify-phone-number-shift64' ),
			'GY' => __( 'Guyana', 'verify-phone-number-shift64' ),
			'HT' => __( 'Haiti', 'verify-phone-number-shift64' ),
			'HN' => __( 'Honduras', 'verify-phone-number-shift64' ),
			'HK' => __( 'Hong Kong', 'verify-phone-number-shift64' ),
			'HU' => __( 'Hungary', 'verify-phone-number-shift64' ),
			'IS' => __( 'Iceland', 'verify-phone-number-shift64' ),
			'IN' => __( 'India', 'verify-phone-number-shift64' ),
			'ID' => __( 'Indonesia', 'verify-phone-number-shift64' ),
			'IR' => __( 'Iran', 'verify-phone-number-shift64' ),
			'IQ' => __( 'Iraq', 'verify-phone-number-shift64' ),
			'IE' => __( 'Ireland', 'verify-phone-number-shift64' ),
			'IL' => __( 'Israel', 'verify-phone-number-shift64' ),
			'IT' => __( 'Italy', 'verify-phone-number-shift64' ),
			'CI' => __( 'Ivory Coast', 'verify-phone-number-shift64' ),
			'JM' => __( 'Jamaica', 'verify-phone-number-shift64' ),
			'JP' => __( 'Japan', 'verify-phone-number-shift64' ),
			'JO' => __( 'Jordan', 'verify-phone-number-shift64' ),
			'KZ' => __( 'Kazakhstan', 'verify-phone-number-shift64' ),
			'KE' => __( 'Kenya', 'verify-phone-number-shift64' ),
			'KI' => __( 'Kiribati', 'verify-phone-number-shift64' ),
			'KP' => __( 'Korea, North', 'verify-phone-number-shift64' ),
			'KR' => __( 'Korea, South', 'verify-phone-number-shift64' ),
			'KW' => __( 'Kuwait', 'verify-phone-number-shift64' ),
			'KG' => __( 'Kyrgyzstan', 'verify-phone-number-shift64' ),
			'LA' => __( 'Laos', 'verify-phone-number-shift64' ),
			'LV' => __( 'Latvia', 'verify-phone-number-shift64' ),
			'LB' => __( 'Lebanon', 'verify-phone-number-shift64' ),
			'LS' => __( 'Lesotho', 'verify-phone-number-shift64' ),
			'LR' => __( 'Liberia', 'verify-phone-number-shift64' ),
			'LY' => __( 'Libya', 'verify-phone-number-shift64' ),
			'LI' => __( 'Liechtenstein', 'verify-phone-number-shift64' ),
			'LT' => __( 'Lithuania', 'verify-phone-number-shift64' ),
			'LU' => __( 'Luxembourg', 'verify-phone-number-shift64' ),
			'MO' => __( 'Macao', 'verify-phone-number-shift64' ),
			'MK' => __( 'North Macedonia', 'verify-phone-number-shift64' ),
			'MG' => __( 'Madagascar', 'verify-phone-number-shift64' ),
			'MW' => __( 'Malawi', 'verify-phone-number-shift64' ),
			'MY' => __( 'Malaysia', 'verify-phone-number-shift64' ),
			'MV' => __( 'Maldives', 'verify-phone-number-shift64' ),
			'ML' => __( 'Mali', 'verify-phone-number-shift64' ),
			'MT' => __( 'Malta', 'verify-phone-number-shift64' ),
			'MH' => __( 'Marshall Islands', 'verify-phone-number-shift64' ),
			'MR' => __( 'Mauritania', 'verify-phone-number-shift64' ),
			'MU' => __( 'Mauritius', 'verify-phone-number-shift64' ),
			'MX' => __( 'Mexico', 'verify-phone-number-shift64' ),
			'FM' => __( 'Micronesia', 'verify-phone-number-shift64' ),
			'MD' => __( 'Moldova', 'verify-phone-number-shift64' ),
			'MC' => __( 'Monaco', 'verify-phone-number-shift64' ),
			'MN' => __( 'Mongolia', 'verify-phone-number-shift64' ),
			'ME' => __( 'Montenegro', 'verify-phone-number-shift64' ),
			'MA' => __( 'Morocco', 'verify-phone-number-shift64' ),
			'MZ' => __( 'Mozambique', 'verify-phone-number-shift64' ),
			'MM' => __( 'Myanmar', 'verify-phone-number-shift64' ),
			'NA' => __( 'Namibia', 'verify-phone-number-shift64' ),
			'NR' => __( 'Nauru', 'verify-phone-number-shift64' ),
			'NP' => __( 'Nepal', 'verify-phone-number-shift64' ),
			'NL' => __( 'Netherlands', 'verify-phone-number-shift64' ),
			'NZ' => __( 'New Zealand', 'verify-phone-number-shift64' ),
			'NI' => __( 'Nicaragua', 'verify-phone-number-shift64' ),
			'NE' => __( 'Niger', 'verify-phone-number-shift64' ),
			'NG' => __( 'Nigeria', 'verify-phone-number-shift64' ),
			'NO' => __( 'Norway', 'verify-phone-number-shift64' ),
			'OM' => __( 'Oman', 'verify-phone-number-shift64' ),
			'PK' => __( 'Pakistan', 'verify-phone-number-shift64' ),
			'PW' => __( 'Palau', 'verify-phone-number-shift64' ),
			'PS' => __( 'Palestine', 'verify-phone-number-shift64' ),
			'PA' => __( 'Panama', 'verify-phone-number-shift64' ),
			'PG' => __( 'Papua New Guinea', 'verify-phone-number-shift64' ),
			'PY' => __( 'Paraguay', 'verify-phone-number-shift64' ),
			'PE' => __( 'Peru', 'verify-phone-number-shift64' ),
			'PH' => __( 'Philippines', 'verify-phone-number-shift64' ),
			'PL' => __( 'Poland', 'verify-phone-number-shift64' ),
			'PT' => __( 'Portugal', 'verify-phone-number-shift64' ),
			'PR' => __( 'Puerto Rico', 'verify-phone-number-shift64' ),
			'QA' => __( 'Qatar', 'verify-phone-number-shift64' ),
			'RO' => __( 'Romania', 'verify-phone-number-shift64' ),
			'RU' => __( 'Russia', 'verify-phone-number-shift64' ),
			'RW' => __( 'Rwanda', 'verify-phone-number-shift64' ),
			'WS' => __( 'Samoa', 'verify-phone-number-shift64' ),
			'SM' => __( 'San Marino', 'verify-phone-number-shift64' ),
			'ST' => __( 'Sao Tome and Principe', 'verify-phone-number-shift64' ),
			'SA' => __( 'Saudi Arabia', 'verify-phone-number-shift64' ),
			'SN' => __( 'Senegal', 'verify-phone-number-shift64' ),
			'RS' => __( 'Serbia', 'verify-phone-number-shift64' ),
			'SC' => __( 'Seychelles', 'verify-phone-number-shift64' ),
			'SL' => __( 'Sierra Leone', 'verify-phone-number-shift64' ),
			'SG' => __( 'Singapore', 'verify-phone-number-shift64' ),
			'SK' => __( 'Slovakia', 'verify-phone-number-shift64' ),
			'SI' => __( 'Slovenia', 'verify-phone-number-shift64' ),
			'SB' => __( 'Solomon Islands', 'verify-phone-number-shift64' ),
			'SO' => __( 'Somalia', 'verify-phone-number-shift64' ),
			'ZA' => __( 'South Africa', 'verify-phone-number-shift64' ),
			'SS' => __( 'South Sudan', 'verify-phone-number-shift64' ),
			'ES' => __( 'Spain', 'verify-phone-number-shift64' ),
			'LK' => __( 'Sri Lanka', 'verify-phone-number-shift64' ),
			'SD' => __( 'Sudan', 'verify-phone-number-shift64' ),
			'SR' => __( 'Suriname', 'verify-phone-number-shift64' ),
			'SZ' => __( 'Eswatini', 'verify-phone-number-shift64' ),
			'SE' => __( 'Sweden', 'verify-phone-number-shift64' ),
			'CH' => __( 'Switzerland', 'verify-phone-number-shift64' ),
			'SY' => __( 'Syria', 'verify-phone-number-shift64' ),
			'TW' => __( 'Taiwan', 'verify-phone-number-shift64' ),
			'TJ' => __( 'Tajikistan', 'verify-phone-number-shift64' ),
			'TZ' => __( 'Tanzania', 'verify-phone-number-shift64' ),
			'TH' => __( 'Thailand', 'verify-phone-number-shift64' ),
			'TL' => __( 'Timor-Leste', 'verify-phone-number-shift64' ),
			'TG' => __( 'Togo', 'verify-phone-number-shift64' ),
			'TO' => __( 'Tonga', 'verify-phone-number-shift64' ),
			'TT' => __( 'Trinidad and Tobago', 'verify-phone-number-shift64' ),
			'TN' => __( 'Tunisia', 'verify-phone-number-shift64' ),
			'TR' => __( 'Turkey', 'verify-phone-number-shift64' ),
			'TM' => __( 'Turkmenistan', 'verify-phone-number-shift64' ),
			'TV' => __( 'Tuvalu', 'verify-phone-number-shift64' ),
			'UG' => __( 'Uganda', 'verify-phone-number-shift64' ),
			'UA' => __( 'Ukraine', 'verify-phone-number-shift64' ),
			'AE' => __( 'United Arab Emirates', 'verify-phone-number-shift64' ),
			'GB' => __( 'United Kingdom', 'verify-phone-number-shift64' ),
			'US' => __( 'United States', 'verify-phone-number-shift64' ),
			'UY' => __( 'Uruguay', 'verify-phone-number-shift64' ),
			'UZ' => __( 'Uzbekistan', 'verify-phone-number-shift64' ),
			'VU' => __( 'Vanuatu', 'verify-phone-number-shift64' ),
			'VA' => __( 'Vatican City', 'verify-phone-number-shift64' ),
			'VE' => __( 'Venezuela', 'verify-phone-number-shift64' ),
			'VN' => __( 'Vietnam', 'verify-phone-number-shift64' ),
			'YE' => __( 'Yemen', 'verify-phone-number-shift64' ),
			'ZM' => __( 'Zambia', 'verify-phone-number-shift64' ),
			'ZW' => __( 'Zimbabwe', 'verify-phone-number-shift64' ),
		);

		return $countries;
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key     Setting key (without prefix).
	 * @param mixed  $default Default value if setting is not found.
	 * @return mixed Setting value or default.
	 */
	public static function get( string $key, $default = null ) {
		return get_option( self::OPTION_PREFIX . $key, $default );
	}

	/**
	 * Check if phone validation is enabled.
	 *
	 * @return bool True if validation is enabled.
	 */
	public static function is_validation_enabled(): bool {
		return 'yes' === self::get( 'enabled', 'yes' );
	}

	/**
	 * Check if format on save is enabled.
	 *
	 * @return bool True if format on save is enabled.
	 */
	public static function is_format_on_save_enabled(): bool {
		return 'yes' === self::get( 'format_on_save', 'yes' );
	}

	/**
	 * Get the default country code.
	 *
	 * @return string ISO-2 country code.
	 */
	public static function get_default_country(): string {
		return (string) self::get( 'default_country', 'PL' );
	}

	/**
	 * Get the validation mode.
	 *
	 * @return string Validation mode key.
	 */
	public static function get_validation_mode(): string {
		return (string) self::get( 'validation_mode', 'default_and_international' );
	}

	/**
	 * Get the output format.
	 *
	 * @return string Output format key (E164, INTERNATIONAL, NATIONAL).
	 */
	public static function get_output_format(): string {
		return (string) self::get( 'output_format', 'E164' );
	}
}
