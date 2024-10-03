<?php

namespace NVWD\A2ZAAL;

use WP_Screen;

\add_filter( 'plugin_action_links_' . A2ZAAL_BASENAME, __NAMESPACE__ . '\add_settings_page_link' );

/**
 * Adds custom settings page to the admin settings menu
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param array $links admin menu links.
 *
 * @return array
 */
function add_settings_page_link( array $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		\admin_url( 'options-general.php?page=a2zaal-options' ),
		__( 'Settings', 'nvwd-a2zaal' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}

\add_action( 'admin_menu', __NAMESPACE__ . '\add_settings_menu_link' );

/**
 * Add admin settings page for our use
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function add_settings_menu_link() {
	\add_options_page(
		'A2Z Alphabetical Archive Links Options',
		'A2Z Alphabetical Archive Links',
		'manage_options',
		'a2zaal-options',
		__NAMESPACE__ . '\create_settings_page'
	);
	// enqueue javascript and styles needed for admin options page.
}

/**
 * Admin page creation script
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function create_settings_page() {
	if ( ! \current_user_can( 'manage_options' ) ) {
		die( 'Unauthorized User' );
	}

	require A2ZAAL_VIEW_DIR . 'settings-form.php';
}

add_action( 'admin_init', __NAMESPACE__ . '\register_plugin_settings' );
add_action( 'rest_admin_init', __NAMESPACE__ . '\register_plugin_settings' );

/**
 * Register block widget
 *
 * @author nvwd
 *
 * @since 2.1.0
 */
function register_plugin_settings() {
	$args = [
		'show_in_rest' => true,
		'default'      => [],
		'type'         => 'array',
	];

	register_setting( 'options', 'a2zaal_post_types', $args );
}

add_action( 'current_screen', __NAMESPACE__ . '\maybe_process_a2zaal_settings_save' );

/**
 * Handle the submission of our settings page
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param \WP_Screen $screen_obj current screen.
 */
function maybe_process_a2zaal_settings_save( WP_Screen $screen_obj ) {
	if ( 'settings_page_a2zaal-options' !== $screen_obj->id ) {
		return;
	}

	\wp_enqueue_script( 'a2zaal_settings_admin', A2ZAAL_ROOT_URL . 'js/a2zaal.js', [ 'jquery' ], A2ZAAL_VERSION, true );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable -- the nonce is checked in process_options_submission() if this _POST var is not empty.
	if ( empty( $_POST['sbmt_a2zaal_settings'] ) ) {
		return;
	}

	process_options_submission( get_a2zaal_active_post_types() );
}

/**
 * Control the a2zaal activation and deactivation of specified post types
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param array $a2zaal_original_cpts a2zaal active post types.
 */
function process_options_submission( array $a2zaal_original_cpts ) {
	\check_admin_referer( 'a2zaal-options' );

	// phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable -- checking if the _POST var exists before sanitizing it.
	$submitted_cpts = isset( $_POST['a2zaal_enabled_post_type'] )
		? array_map( 'sanitize_text_field', \wp_unslash( $_POST['a2zaal_enabled_post_type'] ) )
		: false;
	// phpcs:enable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable

	$enable_cpts  = [];
	$disable_cpts = [];

	if ( $submitted_cpts ) {
		$enable_cpts  = array_diff( $submitted_cpts, $a2zaal_original_cpts );
		$disable_cpts = array_diff( $a2zaal_original_cpts, $submitted_cpts );
	} else {
		$submitted_cpts = [];
		$disable_cpts   = $a2zaal_original_cpts;
	}

	maybe_remove_disabled_cpt_a2zaal_posts( $disable_cpts );

	maybe_setup_background_post_type_process( $enable_cpts );

	if ( ! empty( $disable_cpts ) || ! empty( $enable_cpts ) ) {
		a2zaal_clear_rewrite_rules();
	}

	\update_option( 'a2zaal_post_types', $submitted_cpts, true );
}

/**
 * Disable specified post types a2zaal and create the trigger to delete the associated terms
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param array $disable_cpts cpts being disabled.
 */
function maybe_remove_disabled_cpt_a2zaal_posts( array $disable_cpts ) {
	if ( empty( $disable_cpts ) ) {
		return;
	}

	foreach ( $disable_cpts as $disabled_post_type ) {
		remove_disabled_cpts( $disabled_post_type );
		maybe_remove_current_processing( $disabled_post_type );
	}

	\add_action( 'admin_notices', __NAMESPACE__ . '\disabled_a2zaal_cpts_admin_notice' );
}

/**
 * Remove a2zaal data for disabled post types
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $disabled_post_type post type to delete its option data.
 */
function remove_disabled_cpts( string $disabled_post_type ) {
	$a2zaal_option = $disabled_post_type . A2ZAAL_POSTS_SUFFIX;

	\delete_option( $a2zaal_option );
}

/**
 * Disable background processing for disabled post type.
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $disabled_post_type disabled post type to check for background processing.
 */
function maybe_remove_current_processing( string $disabled_post_type ) {
	$a2zaal_background_processing = \get_option( 'a2zaal_background_processing', [] );

	if ( ! isset( $a2zaal_background_processing['post-type'][ $disabled_post_type ] ) ) {
		return;
	}

	unset( $a2zaal_background_processing['post-type'][ $disabled_post_type ] );
	\update_option( 'a2zaal_background_processing', $a2zaal_background_processing, true );

	if ( ! empty( $a2zaal_background_processing['post-type'] ) ) {
		return;
	}

	clear_scheduled_processing( 'a2zaal_process_activation_cron' );
}

/**
 * Create the admin notice that the disable request is a success
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function disabled_a2zaal_cpts_admin_notice() {
	$class   = 'notice notice-success is-dismissible';
	$message = __( 'The specified Post Type(s) are now disabled for A2Z Alphabetical Archive Links.', 'nvwd-a2zaal' );

	a2zaal_output_admin_notice( $class, $message );
}

/**
 * Register a2zaal activated post types and create structure to hold the initials and sort titles for
 * existing post type items
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param array $enable_post_type cpts to enable.
 */
function maybe_setup_background_post_type_process( array $enable_post_type ) {
	if ( empty( $enable_post_type ) ) {
		return;
	}

	\do_action( 'a2zaal_setup_background_processes', $enable_post_type );

	\add_action( 'admin_notices', __NAMESPACE__ . '\enabled_a2zaal_cpts_admin_notice' );
}

/**
 * Create admin notice that the activate request is a success
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function enabled_a2zaal_cpts_admin_notice() {
	$class   = 'notice notice-success is-dismissible';
	$message = __(
		'The specified Post Type(s) are being processed in the background for A2Z Alphabetical Archive Links.
		Leaving this page does not stop the process.',
		'nvwd-a2zaal'
	);

	a2zaal_output_admin_notice( $class, $message );
}
