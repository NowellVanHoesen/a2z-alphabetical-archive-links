<?php
/**
 * A2Z Alphabetical Archive Links Settings Page Handler
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;

\add_filter( 'plugin_action_links_' . A2ZAAL_BASENAME, __NAMESPACE__ . '\add_settings_page_link' );

function add_settings_page_link( $links ) {
	$settings_link = '<a href="' . \admin_url( 'options-general.php?page=a2zaal-options' ) . '">' . __( 'Settings', A2ZAAL_TEXT_DOMAIN ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

\add_action( 'admin_menu', __NAMESPACE__ . '\add_settings_menu_link' );

/**
 * add admin settings page for our use
 *
 * @author: nvwd
 * @since: 2.0.0
 * @return void
 */
function add_settings_menu_link() {
	\add_options_page( 'A2Z Alphabetical Archive Links Options', 'A2Z Alphabetical Archive Links', 'manage_options', 'a2zaal-options', __NAMESPACE__ . '\create_settings_page' );
	//enqueue javascript and styles needed for admin options page
}

/**
 * admin page creation script
 *
 * @author: nvwd
 * @since: 2.0.0
 * @return void
 */
function create_settings_page() {

	if ( ! \current_user_can( 'manage_options' ) ) {
		die( 'Unauthorized User' );
	}

	$a2zaal_active_post_types = namespace\get_a2zaal_active_post_types();

	$a2zaal_processing_post_types = namespace\get_a2zaal_processing_post_types();

	$excluded_post_types = array(
		'attachment',
		'revision',
		'nav_menu_item'
	);

	$registered_post_types = \get_post_types( array( 'publicly_queryable' => true ), 'objects' );

	$a2zaal_processing_counts = \get_option( 'a2zaal_processing_counts', array() );

	include( A2ZAAL_VIEW_DIR . 'settings_form.php' );
}

add_action( 'current_screen', __NAMESPACE__ . '\maybe_process_a2zaal_settings_save' );

/**
 * handle the submission of our settings page
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $screen_obj
 * @return void
 */
function maybe_process_a2zaal_settings_save( $screen_obj ) {

	if ( 'settings_page_a2zaal-options' !== $screen_obj->id ) {
		return;
	}

	\wp_enqueue_script( 'a2zaal_settings_admin', A2ZAAL_ROOT_URL . 'js/a2zaal.js', array( 'jquery' ), false, true );

	if ( ! empty( $_POST['sbmt_a2zaal_settings'] ) ) {
		namespace\process_options_submission( namespace\get_a2zaal_active_post_types() );
	}
}

/**
 * control the a2zaal activation and deactivation of specified post types
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $a2zaal_original_cpts
 * @return void
 */
function process_options_submission( $a2zaal_original_cpts ) {

	\check_admin_referer( 'a2zaal-options' );

	$enable_cpts = array();
	$disable_cpts = array();

	if ( isset( $_POST['a2zaal_enabled_post_type'] ) ) {
		$submitted_cpts = $_POST['a2zaal_enabled_post_type'];
		$enable_cpts = array_diff( $_POST['a2zaal_enabled_post_type'], $a2zaal_original_cpts );
		$disable_cpts = array_diff( $a2zaal_original_cpts, $_POST['a2zaal_enabled_post_type'] );
	} else {
		$submitted_cpts = array();
		$disable_cpts = $a2zaal_original_cpts;
	}

	namespace\maybe_remove_disabled_cpt_a2zaal_posts( $disable_cpts );

	namespace\maybe_setup_background_post_type_process( $enable_cpts );

	if ( ! empty( $disable_cpts ) || ! empty( $enable_cpts ) ) {
		a2zaal_clear_rewrite_rules();
	}

	\update_option( 'a2zaal_post_types', $submitted_cpts, true );

}

/**
 * disable specified post types a2zaal and create the trigger to delete the associated terms
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $disable_cpts
 * @return void
 */
function maybe_remove_disabled_cpt_a2zaal_posts( $disable_cpts ) {
	if ( empty( $disable_cpts ) ) {
		return;
	}

	foreach ( $disable_cpts AS $disabled_post_type ) {
		namespace\remove_disabled_cpts( $disabled_post_type );
		namespace\maybe_remove_current_processing( $disabled_post_type );
	}

	\add_action( 'admin_notices', __NAMESPACE__ . '\disabled_a2zaal_cpts_admin_notice' );
}

/**
 * remove a2zaal data for disabled post types
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $disabled_post_type
 * @return void
 */
function remove_disabled_cpts( $disabled_post_type ) {
	$a2zaal_option = $disabled_post_type . A2ZAAL_POSTS_SUFFIX;

	\delete_option( $a2zaal_option );

}

function maybe_remove_current_processing( $disabled_post_type ) {
	$a2zaal_background_processing = \get_option( 'a2zaal_background_processing', array() );

	if ( ! isset( $a2zaal_background_processing['post-type'][$disabled_post_type] ) ) {
		return;
	}

	unset( $a2zaal_background_processing['post-type'][$disabled_post_type] );
	\update_option( 'a2zaal_background_processing', $a2zaal_background_processing, true );

	if ( empty( $a2zaal_background_processing['post-type'] ) ) {
		namespace\clear_scheduled_processing( 'a2zaal_process_activation_cron' );
	}

	return;
}

/**
 * create the admin notice that the disable request is a success
 *
 * @author: nvwd
 * @since: 2.0.0
 * @return void
 */
function disabled_a2zaal_cpts_admin_notice() {
	$class = 'notice notice-success is-dismissible';
	$message = __( 'The specified Post Type(s) are now disabled for A2Z Alphabetical Archive Links.', A2ZAAL_TEXT_DOMAIN );

	a2zaal_output_admin_notice( $class, $message );
}

/**
 * register a2zaal activated post types and create structure to hold the initials and sort titles for
 * existing post type items
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $enable_cpts
 * @return void
 */
function maybe_setup_background_post_type_process( $enable_post_type ) {
	if ( empty( $enable_post_type ) ) {
		return;
	}

	\do_action( 'a2zaal_setup_background_processes', $enable_post_type );

	\add_action( 'admin_notices', __NAMESPACE__ . '\enabled_a2zaal_cpts_admin_notice' );
}

/**
 * create admin notice that the activate request is a success
 *
 * @author: nvwd
 * @since: 2.0.0
 * @return void
 */
function enabled_a2zaal_cpts_admin_notice() {
	$class = 'notice notice-success is-dismissible';
	$message = __( 'The specified Post Type(s) are being processed in the background for A2Z Alphabetical Archive Links. Leaving this page does not stop the process.', A2ZAAL_TEXT_DOMAIN );

	a2zaal_output_admin_notice( $class, $message );
}
