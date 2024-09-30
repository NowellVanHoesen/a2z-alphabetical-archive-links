<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! empty( $attributes['title'] ) ) {
?>
<h2><?php esc_html_e( $attributes['title'], 'nvwd-a2zaal' ); ?></h2>
<?php } ?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php esc_html_e( 'A2z Links – hello from a dynamic block!', 'nvwd-a2zaal' ); ?>
</p>
