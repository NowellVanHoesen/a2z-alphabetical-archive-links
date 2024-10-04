<?php

namespace NVWD\A2ZAAL;

/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$container_classes = [];

if ( $attributes['showCounts'] ) {
	$container_classes[] = 'counts';
}

if ( ! empty( $attributes['title'] ) ) {
?>
<h2><?php \esc_html_e( $attributes['title'], 'nvwd-a2zaal' ); ?></h2>
<?php } ?>
<ul <?php print \get_block_wrapper_attributes( [ 'class' => \implode( ' ', $container_classes ) ] ); ?>>
	<?php
		print \wp_kses_post(
			implode( '', get_a2zaal_display_links( $attributes['selectedPostType'], $attributes['showCounts'] ) )
		);
		?>
</ul>
