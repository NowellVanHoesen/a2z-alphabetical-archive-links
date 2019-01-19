<?php
/**
 * A2Z Alphabetical Archive Links Handler
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;

define( 'A2ZAAL_POSTS_SUFFIX', '_a2zaal' );
define( 'A2ZAAL_REWRITE_TAG', \apply_filters( 'nv_a2zaal_rewrite_tag', 'a2z' ) );
define( 'A2ZAAL_SOURCE_DIR', A2ZAAL_PLUGIN_ROOT_DIR . 'src/' );
define( 'A2ZAAL_VIEW_DIR', A2ZAAL_SOURCE_DIR . 'views/' );

include A2ZAAL_SOURCE_DIR . 'vender/wp-background-processing/wp-background-processing.php';
include A2ZAAL_SOURCE_DIR . 'classes/A2ZAAL_Post_Type_Background_Process.php';

if ( is_admin() ) {
	include A2ZAAL_SOURCE_DIR . 'settings.php';
}
include A2ZAAL_SOURCE_DIR . 'rewrites.php';
include A2ZAAL_SOURCE_DIR . 'helpers.php';
include A2ZAAL_SOURCE_DIR . 'widgets/a2zaal_widget.php';

add_action( 'save_post', __NAMESPACE__ . '\maybe_add_a2zaal_post' );
/**
 * add the a2zaal data to the specific structure when an active post type item is saved
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $post_id
 * @return void
 */
function maybe_add_a2zaal_post( $post_id ) {

	if ( \wp_is_post_revision( $post_id ) || \wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$a2zaal_post = \get_post( $post_id );
	$a2zaal_active_post_types = namespace\get_a2zaal_active_post_types();

	if ( ! in_array( $a2zaal_post->post_type, $a2zaal_active_post_types ) ) {
		return;
	}

	namespace\add_post_a2zaal_info( $a2zaal_post );

}

/**
 * make the a2zaal list page titles more readable
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $archive_title
 * @return string
 */
function filter_archive_title( $archive_title ) {
	if ( ! namespace\is_a2zaal_query() ) {
		return $archive_title;
	}

	$post_type_labels = get_post_type_object( get_query_var( 'post_type' ) );
	$group_title_initial = get_query_var( A2ZAAL_REWRITE_TAG );

	if ( '0' == $group_title_initial ) {
		$group_title_initial = '#';
	}

	return sprintf( __( '%1$s: %2$s' ), $post_type_labels->label, $group_title_initial );
}

/**
 * customize the a2zaal list page description
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $archive_description
 * @return string
 */
function filter_archive_description( $archive_description ) {
	if ( ! namespace\is_a2zaal_query() ) {
		return $archive_description;
	}

	$post_type_labels = get_post_type_object( get_query_var( 'post_type' ) );

	$group_title_initial = get_query_var( A2ZAAL_REWRITE_TAG );

	if ( '0' == $group_title_initial ) {
		$group_title_initial = __( 'a number', A2ZAAL_TEXT_DOMAIN );
	}

	return sprintf( __( '%1$s beginning with %2$s.' ), $post_type_labels->label, $group_title_initial );
}
