<?php

namespace NVWD\A2ZAAL;

$a2zaal_active_post_types = get_a2zaal_active_post_types();

$a2zaal_excluded_post_types = [
	'attachment',
	'revision',
	'nav_menu_item',
];

$a2zaal_registered_post_types = \get_post_types( [ 'publicly_queryable' => true ], 'objects' );

$a2zaal_processing_counts = \get_option( 'a2zaal_processing_counts', [] );

?>

<h1>A2Z Alphabetical Archive Links Options</h1>
<noscript><h3>This page requires JavaScript to run properly.</h3></noscript>
<p>Select which post types you would like to enable Alphabetical Archive Links.</p>
<form name="frm_a2zaal_settings" method="post" action="">
<?php
\wp_nonce_field( 'a2zaal-options' );

foreach ( $a2zaal_registered_post_types as $a2zaal_post_type ) {
	if ( in_array( $a2zaal_post_type->name, $a2zaal_excluded_post_types, true ) ) {
		continue;
	}

	$a2zaal_post_type_total_count = \wp_count_posts( $a2zaal_post_type->name )->publish;

	$a2zaal_post_type_processing = empty( $a2zaal_processing_counts )
		? false
		: array_key_exists( $a2zaal_post_type->name, $a2zaal_processing_counts['post_type'] );

	$a2zaal_processing_output  = '';
	$a2zaal_display_input_type = 'checkbox';

	if ( $a2zaal_post_type_processing ) {
		$a2zaal_processing_output  = sprintf(
			'<span class="a2zaal_processing">%s&nbsp;</span>',
			\esc_html__( 'Processing', 'nvwd-a2zaal' )
		);
		$a2zaal_display_input_type = 'hidden';
	}

	$a2zaal_post_type_active = in_array( $a2zaal_post_type->name, $a2zaal_active_post_types, true ) ? ' checked="checked"' : '';

	printf(
		'<p data-post_type="%s">%s<input type="%s" name="a2zaal_enabled_post_type[]" value="%s"%s>%s (%d)</p>',
		\esc_attr( $a2zaal_post_type->name ),
		\wp_kses_post( $a2zaal_processing_output ),
		\esc_attr( $a2zaal_display_input_type ),
		\esc_attr( $a2zaal_post_type->name ),
		\esc_attr( $a2zaal_post_type_active ),
		\esc_html( $a2zaal_post_type->label ),
		\esc_html( $a2zaal_post_type_total_count )
	);
}

?>
		<input type="submit" name="sbmt_a2zaal_settings" class="button button-primary" value="Submit">
</form>
<?php
