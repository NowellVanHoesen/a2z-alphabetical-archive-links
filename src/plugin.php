<?php

namespace NVWD\A2ZAAL;

define( 'A2ZAAL_POSTS_SUFFIX', '_a2zaal' );
define( 'A2ZAAL_REWRITE_TAG', \apply_filters( 'nv_a2zaal_rewrite_tag', 'a2z' ) );
define( 'A2ZAAL_SOURCE_DIR', A2ZAAL_PLUGIN_ROOT_DIR . 'src/' );
define( 'A2ZAAL_VIEW_DIR', A2ZAAL_SOURCE_DIR . 'views/' );

require A2ZAAL_SOURCE_DIR . 'vender/wp-background-processing/wp-background-processing.php';
require A2ZAAL_SOURCE_DIR . 'classes/class-a2zaal-post-type-background-process.php';

if ( is_admin() ) {
	require A2ZAAL_SOURCE_DIR . 'settings.php';
}

require A2ZAAL_SOURCE_DIR . 'rewrites.php';
require A2ZAAL_SOURCE_DIR . 'helpers.php';
require A2ZAAL_SOURCE_DIR . 'widgets/class-a2zaal-widget.php';

\add_action( 'widgets_init', __NAMESPACE__ . '\maybe_register_archive_link_widget' );

/**
 * Register the a2zaal widget only if there are active post types
 *
 * @author nvwd
 *
 * @since 2.0.0
 */
function maybe_register_archive_link_widget() {
	$a2z_active_post_types = get_a2zaal_active_post_types();

	if ( empty( $a2z_active_post_types ) ) {
		return;
	}

	\register_widget( __NAMESPACE__ . '\a2zaal_widget' );
}

add_action( 'save_post', __NAMESPACE__ . '\maybe_add_a2zaal_post' );
/**
 * Add the a2zaal data to the specific structure when an active post type item is saved
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param int $post_id ID of the post being saved.
 */
function maybe_add_a2zaal_post( int $post_id ) {
	if ( \wp_is_post_revision( $post_id ) || \wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$a2zaal_post              = \get_post( $post_id );
	$a2zaal_active_post_types = get_a2zaal_active_post_types();

	if ( ! in_array( $a2zaal_post->post_type, $a2zaal_active_post_types, true ) ) {
		return;
	}

	add_post_a2zaal_info( $a2zaal_post );
}

/**
 * Make the a2zaal list page titles more readable
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $archive_title archive page title.
 *
 * @return string
 */
function filter_archive_title( string $archive_title ) {
	if ( ! is_a2zaal_query() ) {
		return $archive_title;
	}

	$post_type_labels    = \get_post_type_object( \get_query_var( 'post_type' ) );
	$group_title_initial = \get_query_var( A2ZAAL_REWRITE_TAG );

	if ( '0' === $group_title_initial ) {
		$group_title_initial = '#';
	}

	/* translators: 1: post type title 2: initial of the archive grouping */
	return sprintf( __( '%1$s: %2$s', 'nvwd-a2zaal' ), $post_type_labels->label, $group_title_initial );
}

/**
 * Customize the a2zaal list page description
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $archive_description archive page description.
 *
 * @return string
 */
function filter_archive_description( string $archive_description ) {
	if ( ! is_a2zaal_query() ) {
		return $archive_description;
	}

	$post_type_labels = \get_post_type_object( \get_query_var( 'post_type' ) );

	$group_title_initial = \get_query_var( A2ZAAL_REWRITE_TAG );

	if ( '0' === $group_title_initial ) {
		$group_title_initial = 'a number';
	}

	/* translators: 1: post type title 2: initial of the archive grouping */
	return sprintf( __( '%1$s beginning with %2$s.', 'nvwd-a2zaal' ), $post_type_labels->label, $group_title_initial );
}
