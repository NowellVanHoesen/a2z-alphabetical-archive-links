<?php

namespace NVWD\A2ZAAL;

use WP_Widget;

/**
 * A2zaal custom widget class.
 */
class A2zaal_Widget extends WP_Widget {
	// phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint -- need to match the signatures of parent methods
	/**
	 * Widget class construct method
	 */
	public function __construct() {
		$opts = [
			'classname'   => 'a2zaal_widget',
			'description' => __(
				'Display a list of post/cpt title initials that link to a list of posts beginning with that initial.',
				'nvwd-a2zaal'
			),
		];

		parent::__construct( 'a2zaal_widget', __( 'A2Z Alphabetical Archive Links', 'nvwd-a2zaal' ), $opts );
	}

	/**
	 * Widget instance settings form
	 *
	 * @author nvwd
	 *
	 * @param array $instance {
	 *     Setting values for specific instance of the widget.
	 *
	 *     @type string $title Widget title.
	 *     @type string $selected_post_type Selected post type for the widget.
	 *     @type string $show_counts Whether to show index group counts with the links.
	 * }
	 */
	public function form( $instance ) {
		$defaults = [
			'selected_post_type' => '',
			'title'              => '',
			'show_counts'        => '',
		];

		$instance = \wp_parse_args( (array) $instance, $defaults );

		$available_posts_types = \get_post_types( [ 'publicly_queryable' => true ], 'objects' );

		$a2zaal_active_post_types = get_a2zaal_active_post_types();

		printf(
			'<p>Title: <input class="widefat" name="%s" type="text" value="%s" /></p>',
			\esc_attr( $this->get_field_name( 'title' ) ),
			\esc_attr( $instance['title'] )
		);
		printf( '<p>Post Type: <select name="%s">', \esc_attr( $this->get_field_name( 'selected_post_type' ) ) );

		foreach ( $available_posts_types as $post_type_obj ) {
			if ( in_array( $post_type_obj->name, $a2zaal_active_post_types, true ) ) {
				printf(
					'<option value="%s" %s>%s</option>',
					\esc_attr( $post_type_obj->name ),
					\selected( $instance['selected_post_type'], $post_type_obj->name, false ),
					\esc_html( $post_type_obj->label )
				);
			}
		}

		print '</select></p>';
		printf(
			'<p>Show Counts: <input name="%s" type="checkbox" %s/></p>',
			\esc_attr( $this->get_field_name( 'show_counts' ) ),
			\checked( $instance['show_counts'], 'on', false )
		);
	}

	/**
	 * Widget instance update.
	 *
	 * @author nvwd
	 *
	 * @param array $new_instance {
	 *     Updated setting values for specific instance of the widget.
	 *
	 *     @type string $title Widget title.
	 *     @type string $selected_post_type Selected post type for the widget.
	 *     @type string $show_counts Whether to show index group counts with the links.
	 * }
	 *
	 * @param array $old_instance {
	 *     Original setting values for specific instance of the widget.
	 *
	 *     @type string $title Widget title.
	 *     @type string $selected_post_type Selected post type for the widget.
	 *     @type string $show_counts Whether to show index group counts with the links.
	 * }
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                       = $old_instance;
		$instance['title']              = \wp_strip_all_tags( $new_instance['title'] );
		$instance['selected_post_type'] = \wp_strip_all_tags( $new_instance['selected_post_type'] );
		$instance['show_counts']        = empty( $new_instance['show_counts'] ) ? '' : 'on';

		return $instance;
	}

	/**
	 * Widget output
	 *
	 * @author nvwd
	 *
	 * @param array $args {
	 *     Arguments for displaying the widget.
	 *
	 *     @type string $before_title Markup output before widget title.
	 *     @type string $after_title Markup output after widget title.
	 *     @type string $before_widget Markup/content output before widget.
	 *     @type string $after_widget Markup/content output after widget.
	 * }
	 *
	 * @param array $instance {
	 *     Setting values for specific instance of the widget.
	 *
	 *     @type string $title Widget title.
	 *     @type string $selected_post_type Selected post type for the widget.
	 *     @type string $show_counts Whether to show index group counts with the links.
	 * }
	 */
	public function widget( $args, $instance ) {
		// TODO: make this dynamic to be able to have custom styles enqueued.
		if ( ! wp_style_is( 'default_a2zaal_style', 'enqueued' ) ) {
			\wp_enqueue_style( 'default_a2zaal_style', A2ZAAL_ROOT_URL . '/css/display.css', [], A2ZAAL_VERSION );
		}

		$show_counts = ! empty( $instance['show_counts'] );

		$display_links = get_a2zaal_display_links( $instance['selected_post_type'], $show_counts );

		$container_classes = [];

		if ( $show_counts ) {
			$container_classes[] = 'counts';
		}

		$container_classes = \apply_filters( 'a2zaal_container_classes', $container_classes, $instance, $args );

		$container_class_output = empty( $container_classes ) ? '' : ' class="' . implode( ' ', $container_classes ) . '"';

		print \wp_kses_post( $args['before_widget'] );
		print \wp_kses_post( $args['before_title'] . $instance['title'] . $args['after_title'] );
		print \wp_kses_post( '<ul' . $container_class_output . '>' );
		print \wp_kses_post( implode( '', $display_links ) );
		print '</ul>';
		print \wp_kses_post( $args['after_widget'] );
	}
	// phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
}
