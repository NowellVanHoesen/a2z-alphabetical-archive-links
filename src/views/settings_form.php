<?php
/**
 * A2Z Alphabetical Archive Links Options/Settings form
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;

?>

<h1>A2Z Alphabetical Archive Links Options</h1>
<noscript><h3>This page requires JavaScript to run properly.</h3></noscript>
<p>Select which post types you would like to enable Alphabetical Archive Links.</p>
<form name="frm_a2zaal_settings" method="post" action="">
<?php
\wp_nonce_field( 'a2zaal-options' );

foreach ( $registered_post_types AS $post_type ) {

	if ( in_array( $post_type->name, $excluded_post_types ) ) {
		continue;
	}

	$post_type_attr = \esc_attr( $post_type->name );
	$post_type_total_count = \wp_count_posts( $post_type->name )->publish;

	if ( empty( $a2zaal_processing_counts ) ) {
		$post_type_processing = false;
	} else {
		$post_type_processing = array_key_exists( $post_type->name, $a2zaal_processing_counts['post_type'] );
	}

	$processing_output = '';
	$display_input_type = 'checkbox';
	if ( $post_type_processing ) {
		$processing_output = '<span class="a2zaal_processing">' . \esc_html__( 'Processing', A2ZAAL_TEXT_DOMAIN ) . '&nbsp;</span>';
		$display_input_type = 'hidden';
	}

	$post_type_active = in_array( $post_type->name, $a2zaal_active_post_types ) ? ' checked="checked"' : '';

	echo '<p data-post_type="' . $post_type_attr . '">';
	echo sprintf( '%s<input type="%s" name="a2zaal_enabled_post_type[]" value="%s"%s>', $processing_output, $display_input_type, $post_type_attr, $post_type_active );
	echo \esc_html__( $post_type->label, A2ZAAL_TEXT_DOMAIN ) . ' (' . $post_type_total_count . ')';
	echo "</p>";
}
?>
		<input type="submit" name="sbmt_a2zaal_settings" class="button button-primary" value="Submit">
</form>
<?php
