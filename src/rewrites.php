<?php
/**
 * A2Z Alphabetical Archive Rewrites Handler
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;

\add_filter( 'rewrite_rules_array', __NAMESPACE__ . '\maybe_modify_rewrite_rules_array' );

/**
 * add rewrite rules for each a2z active post type
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param   array   $rewrite_rules  the rewrite structure passed in
 * @return  array                   the possibly updated rewrite structure
 */
function maybe_modify_rewrite_rules_array( $rewrite_rules ) {
	$a2zaal_active_post_types = namespace\get_a2zaal_active_post_types();

	if ( empty( $a2zaal_active_post_types ) ) {
		return $rewrite_rules;
	}

	$new_rules = array();

	foreach ( $a2zaal_active_post_types AS $active_post_type ) {
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/?$']                              = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=all';
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/page/?([0-9]{1,})/?$']            = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=all&paged=$matches[2]';
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/([a-zA-Z])/?$']                   = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=$matches[1]';
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/num/?$']                          = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=0';
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/([a-zA-Z])/page/?([0-9]{1,})/?$'] = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=$matches[1]&paged=$matches[2]';
		$new_rules[$active_post_type . '/' . A2ZAAL_REWRITE_TAG . '/num/page/?([0-9]{1,})/?$']        = 'index.php?post_type=' . $active_post_type . '&' . A2ZAAL_REWRITE_TAG . '=0&paged=$matches[1]';
	}

	return array_merge( $new_rules, $rewrite_rules );
}

\add_action( 'init', __NAMESPACE__ . '\add_a2zaal_rewrite_tag' );

/**
 * add the query tag being used for holding the specific initial
 *
 * @author: nvwd
 * @since: 2.0.0
 * @return void
 */
function add_a2zaal_rewrite_tag() {
	\add_rewrite_tag( '%' . A2ZAAL_REWRITE_TAG . '%', '([^/]*)' );
}

\add_action( 'parse_request', __NAMESPACE__ . '\maybe_alter_query_for_a2zaal' );

/**
 * add hooks to modify the query if the a2z rewrite tag exists
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param   WP_Query    $query  main query
 * @return void
 */
function maybe_alter_query_for_a2zaal( $query ) {
	if ( ! isset( $query->query_vars[ A2ZAAL_REWRITE_TAG ] ) ) {
		return;
	}

	\add_filter( 'posts_where', __NAMESPACE__ . '\filter_a2zaal_where_clause' );
	\add_filter( 'posts_orderby', __NAMESPACE__ . '\filter_a2zaal_orderby_clause' );
	\add_action( 'pre_get_posts', __NAMESPACE__ . '\kill_pagination' );
	\add_filter( 'get_the_archive_title', __NAMESPACE__ . '\filter_archive_title' );
	\add_filter( 'get_the_archive_description', __NAMESPACE__ . '\filter_archive_description' );
}

/**
 * pull all posts until I can build in pagination, then make pagination optional
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param   WP_Query    $query  main query
 * @return void
 */
function kill_pagination( $query ) {
	$query->set( 'posts_per_page', -1 );
	$query->set( 'no_found_rows', true );
}

/**
 * add IN clause providing a list of post IDs to be retrieved by the query
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param   string  $posts_where    query where clause
 * @return  string                  maybe modified where clause
 */
function filter_a2zaal_where_clause( $posts_where ) {
	global $wpdb;

	$post_type_a2zaal_struct = \get_option( \get_query_var( 'post_type' ) . A2ZAAL_POSTS_SUFFIX, array() );

	if ( empty( $post_type_a2zaal_struct ) ) {
		return $posts_where;
	}

	$a2zaal_query_var = \get_query_var( A2ZAAL_REWRITE_TAG );

	$specific_post_ids = implode( ',', array_keys( $post_type_a2zaal_struct[ $a2zaal_query_var ] ) );

	$posts_where .= " AND ( $wpdb->posts.ID IN (" . $specific_post_ids . ") )";

	return $posts_where;
}

/**
 * set the sort order using the naturally sorted object titles
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param   string  $posts_orderby  orderby clause
 * @return  string                  maybe new orderby clause
 */
function filter_a2zaal_orderby_clause( $posts_orderby ) {
	global $wpdb;

	$post_type_a2zaal_struct = \get_option( \get_query_var( 'post_type' ) . A2ZAAL_POSTS_SUFFIX, array() );

	if ( empty( $post_type_a2zaal_struct ) ) {
		return $posts_orderby;
	}

	$a2zaal_query_var = \get_query_var( A2ZAAL_REWRITE_TAG );

	natsort( $post_type_a2zaal_struct[ $a2zaal_query_var ] );

	$specific_post_ids = implode( ',', array_keys( $post_type_a2zaal_struct[ $a2zaal_query_var ] ) );

	$posts_orderby = "field( $wpdb->posts.ID, $specific_post_ids )";

	return $posts_orderby;
}

\add_action( 'parse_query', __NAMESPACE__ . '\check_a2zaal_404' );

/**
 * check to see if posts/objects for the specified a2z initial exists in the post type structure
 *
 * @author: nvwd
 * @since: 2.0.0
 * @param $query
 * @return void
 */
function check_a2zaal_404( &$query ) {

	if ( ! namespace\is_a2zaal_query() || \is_admin() ) {
		return;
	}

	$post_type_a2zaal_struct = \get_option( $query->query_vars['post_type'] . A2ZAAL_POSTS_SUFFIX, array() );

	if ( empty( $post_type_a2zaal_struct ) ) {
		return;
	}

	if ( array_key_exists( $query->query_vars[ A2ZAAL_REWRITE_TAG ], $post_type_a2zaal_struct ) || 'all' == $query->query_vars[ A2ZAAL_REWRITE_TAG ] ) {
		return;
	}

	$query->set_404();
}

