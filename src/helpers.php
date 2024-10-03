<?php

namespace NVWD\A2ZAAL;

use WP_Post;

/**
 * Return an array of a2zaal active post types or an empty array when none are active
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @return array
 */
function get_a2zaal_active_post_types() {
	$a2zaal_active_post_types = get_option( 'a2zaal_post_types', [] );

	if ( ! is_array( $a2zaal_active_post_types ) ) {
		return [];
	}

	return $a2zaal_active_post_types;
}

/**
 * Return an array of a2zaal post types that are currently processing or an empty array
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @return array
 */
function get_a2zaal_processing_post_types() {
	$a2zaal_processing_objects = get_option( 'a2zaal_processing_objects', [] );

	if ( empty( $a2zaal_processing_objects['post_types'] ) ) {
		return [];
	}

	return $a2zaal_processing_objects['post_types'];
}

/**
 * This is where the magic happens, return the first character of the provided title and the string used for sorting
 * if the title begins with one or more 'a', 'an', 'and', and/or 'the', ignore them and goto the next word
 * if the title then begins with a number return '#', otherwise return the first alpha character, capitalized.
 * return the adjusted title for proper sorting when the links are retireved
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param string $post_title Post title being processed for its grouping initial.
 *
 * @return bool|array
 */
function get_post_a2zaal_info( string $post_title ) {
	if ( empty( $post_title ) ) {
		return false;
	}

	// Regex explained: https://regex101.com/r/B8vbdX/1.
	preg_match( '/\A(?|a |an |and |the )*(([a-zA-Z]{1}|[\d]{1,}).*)\z/i', $post_title, $a2zaal_char );
	$post_a2zaal_info = [
		'sort_title' => $a2zaal_char[1],
		'initial'    => strtoupper( $a2zaal_char[2] ),
	];

	if ( is_numeric( $post_a2zaal_info['initial'] ) ) {
		$post_a2zaal_info['initial'] = '0';
	}

	return $post_a2zaal_info;
}

/**
 * Check if the query is an a2z query needing some extra stuff
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @return bool|string
 */
function is_a2zaal_query() {
	if ( ! \is_main_query() || ! \is_post_type_archive() ) {
		return false;
	}

	/* phpcs:ignore SlevomatCodingStandard.ControlStructures.UselessTernaryOperator.UselessTernaryOperator -- returning the results of get_query_var is not reliable due to one of the values being 0, making it false. */
	return false === \get_query_var( A2ZAAL_REWRITE_TAG, false ) ? false : true;
}

/**
 * Add sortable title and post ID to the structure used to order the posts alphabetically
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param \WP_Post $post post being saved.
 */
function add_post_a2zaal_info( WP_Post $post ) {
	$a2zaal_cpt_option = \get_option( $post->post_type . A2ZAAL_POSTS_SUFFIX, [] );
	$post_a2zaal_info  = get_post_a2zaal_info( $post->post_title );

	if (
		! is_array( $post_a2zaal_info )
		|| (
			array_key_exists( $post_a2zaal_info['initial'], $a2zaal_cpt_option )
			&& array_key_exists( $post->ID, $a2zaal_cpt_option[ $post_a2zaal_info['initial'] ] )
		)
	) {
		return;
	}

	// in the case of an update, the post may already exist in the a2z post info structure.
	// ensure that the post doesn't already exist in the structure.
	remove_prior_post_a2zaal_data( $a2zaal_cpt_option, $post->ID, $post_a2zaal_info['initial'] );

	$a2zaal_cpt_option[ $post_a2zaal_info['initial'] ][ $post->ID ] = $post_a2zaal_info['sort_title'];

	\update_option( $post->post_type . A2ZAAL_POSTS_SUFFIX, $a2zaal_cpt_option, true );
}

/**
 * In the case of a post update, check to see if the post already exists within the structure
 * remove it if necessary
 *
 * @author nvwd
 *
 * @since 2.0.0
 *
 * @param array  $a2zaal_cpt_option contains the post alphabetical detail for this post type.
 * @param int    $post_id current post ID to possibly remove a previous entry.
 * @param string $post_initial initial of the current post title.
 */
function remove_prior_post_a2zaal_data( array &$a2zaal_cpt_option, int $post_id, string $post_initial ) {
	foreach ( $a2zaal_cpt_option as $key => $initial_posts ) {
		if ( $key === $post_initial || ! array_key_exists( $post_id, $initial_posts ) ) {
			continue;
		}

		unset( $a2zaal_cpt_option[ $key ][ $post_id ] );
	}
}
