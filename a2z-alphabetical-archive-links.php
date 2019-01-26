<?php
	/*
		Plugin Name: A2Z Alphabetical Archive Links
		Plugin URI: https://github.com/NowellVanHoesen/a2z-alphabetical-archive-links/wiki
		Description: Get a list of characters, A to Z, from the initial character of a post or CPT title. The Initials will link to an archive page of posts/CPTs that begin with that character.
		Version: 2.0.2
		Author: Nowell VanHoesen
		Author URI: http://nvwebdev.com/
		Author Email: nowell@nvwebdev.com
		License: GPL-2.0+
		Licanse URI: https://www.gnu.org/licenses/gpl.html
		Text Domain: nvwd-a2zaal
	*/

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	define( 'A2ZAAL_PLUGIN', __FILE__ );
	define( 'A2ZAAL_BASENAME', plugin_basename( A2ZAAL_PLUGIN ) );
	define( 'A2ZAAL_VERSION', '2.0.2' );
	define( 'A2ZAAL_PLUGIN_ROOT_DIR', trailingslashit( __DIR__ ) );

	$a2zaal_url = plugin_dir_url( A2ZAAL_PLUGIN );
	if ( is_ssl() ) {
		$a2zaal_url = str_replace( 'http://', 'https://', $a2zaal_url );
	}

	define( 'A2ZAAL_ROOT_URL', $a2zaal_url );
	define( 'A2ZAAL_TEXT_DOMAIN', 'nvwd-a2zaal' );

	define( 'A2ZAAL_PHP_MIN_VERIONS', '5.6' );
	define( 'A2ZAAL_WP_MIN_VERSIONS', '4.6.0' );

	register_activation_hook( A2ZAAL_PLUGIN, 'a2zaal_activation_check' );
	register_deactivation_hook( A2ZAAL_PLUGIN, 'a2zaal_deactivate' );
	register_uninstall_hook( A2ZAAL_PLUGIN, 'a2zaal_uninstall' );

	add_action( 'admin_init', 'a2zaal_verify_versions' );

	/* do version checks before including the rest of the plugin code */
	if ( is_wp_error( a2zaal_version_checks() ) ) {
		return;
	}

	/* version checks complete let's get the party started */
	include( A2ZAAL_PLUGIN_ROOT_DIR . 'src/plugin.php' );

	/**
	 * plugin activation script
	 * initiate version checks, disable plugin if any fail
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_activation_check() {
		$a2zaal_activation_check = a2zaal_version_checks();

		if ( is_wp_error( $a2zaal_activation_check ) ) {
			deactivate_plugins( A2ZAAL_BASENAME );
			return;
		}

		return;
	}

	/**
	 * plugin deactivate script
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_deactivate() {
		/**
		 * remove background processing
		 * remove any options
		 * remove any activated post type data
		 */
		do_action( 'a2zaal_deactivation' );
		a2zaal_clear_rewrite_rules();
	}

	/**
	 * plugin uninstall script
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_uninstall() {

		$a2zaal_active_post_types = NVWD\A2ZAAL\get_a2zaal_active_post_types();

		foreach ( $a2zaal_active_post_types AS $active_cpt ) {
			NVWD\A2ZAAL\remove_disabled_taxonomy_terms( $active_cpt );
		}

		delete_option( 'a2zaal_post_types' );

	}

	/**
	 * delete rewrite rules option wrapper
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_clear_rewrite_rules() {
		delete_option( 'rewrite_rules' );
	}

	/**
	 * main version check function
	 * disable plugin if version check(s) fail
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_verify_versions() {
		$a2zaal_activation_check = a2zaal_version_checks();

		if ( ! is_wp_error( $a2zaal_activation_check ) ) {
			return;
		}

		if ( ! is_plugin_active( A2ZAAL_BASENAME ) ) {
			return;
		}

		deactivate_plugins( A2ZAAL_BASENAME );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	/**
	 * version check controller
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return bool|WP_Error
	 */
	function a2zaal_version_checks() {
		$version_check_errors = new WP_Error();

		$version_check_errors = apply_filters( 'a2zaal_do_version_checks', $version_check_errors );

		$error_codes = $version_check_errors->get_error_codes();
		if ( ! empty( $error_codes ) ) {
			return $version_check_errors;
		}

		return true;
	}

	add_filter( 'a2zaal_do_version_checks', 'a2zaal_check_wp_version' );
	/**
	 * Check WP version
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param $version_check_errors
	 *
	 * @return WP_Error
	 */
	function a2zaal_check_wp_version( $version_check_errors ) {
		global $wp_version;

		if ( version_compare( $wp_version, A2ZAAL_WP_MIN_VERSIONS, '>=' ) ) {
			return $version_check_errors;
		}

		add_action( 'admin_notices', 'a2zaal_wp_version_failure_message' );

		$version_check_errors->add(
			'wp_version',
			esc_html__('WordPress version check for A2Z Alphabetical Archive Links failed. A2Z Alphabetical Archive Links should not be active.', A2ZAAL_TEXT_DOMAIN )
		);

		return $version_check_errors;
	}

	add_filter( 'a2zaal_do_version_checks', 'a2zaal_check_php_version' );
	/**
	 * Check PHP version
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param $version_check_errors
	 *
	 * @return WP_Error
	 */
	function a2zaal_check_php_version( $version_check_errors ) {
		if ( version_compare( PHP_VERSION, A2ZAAL_PHP_MIN_VERIONS, '>=' ) ) {
			return $version_check_errors;
		}

		add_action( 'admin_notices', 'a2zaal_php_version_failure_message' );

		$version_check_errors->add(
			'php_version',
			esc_html__('PHP version check for A2Z Alphabetical Archive Links failed. A2Z Alphabetical Archive Links should not be active.', A2ZAAL_TEXT_DOMAIN )
		);

		return $version_check_errors;
	}

	/**
	 * Create admin notice of WP version check failure
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_wp_version_failure_message() {
		$class = 'notice notice-error';
		$message = __( 'A2Z Alphabetical Archive Links requires WordPress ' . A2ZAAL_WP_MIN_VERSIONS . ' to function properly. Please upgrade WordPress. A2Z Alphabetical Archive Links has been auto-deactivated.', A2ZAAL_TEXT_DOMAIN );

		a2zaal_output_admin_notice( $class, $message );
	}

	/**
	 * Create admin notice of PHP version check failure
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @return void
	 */
	function a2zaal_php_version_failure_message() {
		$class = 'notice notice-error';
		$message = __( 'A2Z Alphabetical Archive Links requires PHP ' . A2ZAAL_PHP_MIN_VERIONS . ' to function properly. Please upgrade PHP. A2Z Alphabetical Archive Links has been auto-deactivated.', A2ZAAL_TEXT_DOMAIN );

		a2zaal_output_admin_notice( $class, $message );
	}

	/**
	 * Display admin notice
	 *
	 * @author: nvwd
	 *
	 * @since: 2.0.0
	 *
	 * @param $class
	 * @param $message
	 *
	 * @return void
	 */
	function a2zaal_output_admin_notice( $class, $message ) {
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
