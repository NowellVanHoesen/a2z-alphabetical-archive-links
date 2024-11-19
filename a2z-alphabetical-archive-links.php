<?php
/*
	Plugin Name: A2Z Alphabetical Archive Links
	Plugin URI: https://github.com/NowellVanHoesen/a2z-alphabetical-archive-links/wiki
	Description: Get a list of characters, A to Z, from the initial character of a post or CPT title.
				The Initials will link to an archive page of posts/CPTs that begin with that character.
	Version: 2.1.0
	Author: Nowell VanHoesen
	Author URI: http://nvwebdev.com/
	Author Email: nowell@nvwebdev.com
	License: GPL-2.0+
	License URI: https://www.gnu.org/licenses/gpl.html
	Text Domain: nvwd-a2zaal
*/

namespace NVWD\A2ZAAL;

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'A2ZAAL_PLUGIN', __FILE__ );
define( 'A2ZAAL_BASENAME', plugin_basename( A2ZAAL_PLUGIN ) );
define( 'A2ZAAL_VERSION', '2.1.0' );
define( 'A2ZAAL_PLUGIN_ROOT_DIR', trailingslashit( __DIR__ ) );

$a2zaal_url = plugin_dir_url( A2ZAAL_PLUGIN );

if ( is_ssl() ) {
	$a2zaal_url = str_replace( 'http://', 'https://', $a2zaal_url );
}

define( 'A2ZAAL_ROOT_URL', $a2zaal_url );

define( 'A2ZAAL_PHP_MIN_VERSION', '7.4' );
define( 'A2ZAAL_WP_MIN_VERSION', '5.8.0' );

register_activation_hook( A2ZAAL_PLUGIN, 'a2zaal_activation_check' );
register_deactivation_hook( A2ZAAL_PLUGIN, 'a2zaal_deactivate' );
register_uninstall_hook( A2ZAAL_PLUGIN, 'a2zaal_uninstall' );

add_filter( 'a2zaal_do_version_checks', __NAMESPACE__ . '\a2zaal_check_php_version' );
add_filter( 'a2zaal_do_version_checks', __NAMESPACE__ . '\a2zaal_check_wp_version' );
add_action( 'admin_init', __NAMESPACE__ . '\a2zaal_verify_versions' );

/* do version checks before including the rest of the plugin code */
if ( is_wp_error( a2zaal_version_checks() ) ) {
	return;
}

/* version checks complete let's get the party started */
require A2ZAAL_PLUGIN_ROOT_DIR . 'src/plugin.php';

/**
 * Plugin activation script
 * initiate version checks, disable plugin if any fail
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_activation_check() {
	$a2zaal_activation_check = a2zaal_version_checks();

	if ( ! is_wp_error( $a2zaal_activation_check ) ) {
		return;
	}

	deactivate_plugins( A2ZAAL_BASENAME );
}

/**
 * Plugin deactivate script
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_deactivate() {
	/**
	 * Remove background processing
	 * remove any options
	 * remove any activated post type data
	 */
	do_action( 'a2zaal_deactivation' );
	a2zaal_clear_rewrite_rules();
}

/**
 * Plugin uninstall script
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_uninstall() {

	$a2zaal_active_post_types = get_a2zaal_active_post_types();

	foreach ( $a2zaal_active_post_types as $active_cpt ) {
		remove_disabled_cpts( $active_cpt );
	}

	delete_option( 'a2zaal_post_types' );
}

/**
 * Delete rewrite rules option wrapper
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_clear_rewrite_rules() {
	delete_option( 'rewrite_rules' );
}

/**
 * Main version check function
 * disable plugin if version check(s) fail
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_verify_versions() {
	$a2zaal_activation_check = a2zaal_version_checks();

	if ( ! is_wp_error( $a2zaal_activation_check ) || ! is_plugin_active( A2ZAAL_BASENAME ) ) {
		return;
	}

	deactivate_plugins( A2ZAAL_BASENAME );

	// phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.Security.NonceVerification.Recommended -- plugin activation failed, need to remove the param so nothing else is processed.
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
}

/**
 * Version check controller
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @return bool|\WP_Error
 */
function a2zaal_version_checks() {
	// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName -- Slevomat can't make up its mind with or without the '\', so I'm opting for with.
	$version_check_errors = new \WP_Error();

	$version_check_errors = apply_filters( 'a2zaal_do_version_checks', $version_check_errors );

	$error_codes = $version_check_errors->get_error_codes();

	if ( ! empty( $error_codes ) ) {
		return $version_check_errors;
	}

	return true;
}

/**
 * Check WP version
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param \WP_Error $version_check_errors version check errors.
 *
 * @return \WP_Error
 *
 * phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName -- Slevomat can't make up its mind with or without the '\', so I'm opting for with.
 */
function a2zaal_check_wp_version( \WP_Error $version_check_errors ) {
	global $wp_version;

	if ( version_compare( $wp_version, A2ZAAL_WP_MIN_VERSION, '>=' ) ) {
		return $version_check_errors;
	}

	add_action( 'admin_notices', 'a2zaal_wp_version_failure_message' );

	$version_check_errors->add(
		'wp_version',
		esc_html__(
			'WordPress version check for A2Z Alphabetical Archive Links failed. 
			A2Z Alphabetical Archive Links should not be active.',
			'nvwd-a2zaal'
		)
	);

	return $version_check_errors;
}
// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName

/**
 * Check PHP version
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param \WP_Error $version_check_errors version check errors.
 *
 * @return \WP_Error
 *
 * phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName -- Slevomat can't make up its mind with or without the '\', so I'm opting for with.
 */
function a2zaal_check_php_version( \WP_Error $version_check_errors ) {
	if ( version_compare( PHP_VERSION, A2ZAAL_PHP_MIN_VERSION, '>=' ) ) {
		return $version_check_errors;
	}

	add_action( 'admin_notices', 'a2zaal_php_version_failure_message' );

	$version_check_errors->add(
		'php_version',
		esc_html__(
			'PHP version check for A2Z Alphabetical Archive Links failed.
			A2Z Alphabetical Archive Links should not be active.',
			'nvwd-a2zaal'
		)
	);

	return $version_check_errors;
}
// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName

/**
 * Create admin notice of WP version check failure
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_wp_version_failure_message() {
	$css_class = 'notice notice-error';
	$message   = sprintf(
		/* translators: 1: Minimum WordPress version */
		__(
			'A2Z Alphabetical Archive Links requires WordPress %1$s to function properly. Please upgrade WordPress.
			A2Z Alphabetical Archive Links has been auto-deactivated.',
			'nvwd-a2zaal'
		),
		A2ZAAL_WP_MIN_VERSION
	);

	a2zaal_output_admin_notice( $css_class, $message );
}

/**
 * Create admin notice of PHP version check failure
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function a2zaal_php_version_failure_message() {
	$css_class = 'notice notice-error';
	$message   = sprintf(
		/* translators: 1: Minimum php version */
		__(
			'A2Z Alphabetical Archive Links requires PHP %1$s to function properly. Please upgrade PHP.
			A2Z Alphabetical Archive Links has been auto-deactivated.',
			'nvwd-a2zaal'
		),
		A2ZAAL_PHP_MIN_VERSION
	);

	a2zaal_output_admin_notice( $css_class, $message );
}

/**
 * Display admin notice
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $css_class css class for the message output.
 * @param string $message message to display.
 */
function a2zaal_output_admin_notice( string $css_class, string $message ) {
	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $css_class ), esc_html( $message ) );
}

if ( function_exists( 'register_block_type' ) ) {
	add_action(
		'init',
		static function () {
			register_block_type( __DIR__ . '/build/blocks/a2z-links' );
		}
	);
}
