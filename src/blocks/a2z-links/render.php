<?php

namespace NVWD\A2ZAAL;

/*
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$a2zaal_container_classes = [];

if ( $attributes['showCounts'] ) {
	$a2zaal_container_classes[] = 'counts';
}

if ( ! empty( $attributes['title'] ) ) {
	printf( '<h2>%s</h2>', \esc_html( $attributes['title'] ) );
}

printf(
	'<ul %s>%s</ul>',
	\wp_kses_data( \get_block_wrapper_attributes( [ 'class' => \implode( ' ', $a2zaal_container_classes ) ] ) ),
	\wp_kses_post( implode( '', get_a2zaal_display_links( $attributes['selectedPostType'], $attributes['showCounts'] ) ) )
);
