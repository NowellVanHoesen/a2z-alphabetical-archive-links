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
		\wp_enqueue_style( 'default_a2zaal_style', A2ZAAL_ROOT_URL . '/css/display.css', [], A2ZAAL_VERSION );

		$post_type_titles_struct = get_option( $instance['selected_post_type'] . A2ZAAL_POSTS_SUFFIX, [] );
		$display_links           = [];

		ksort( $post_type_titles_struct, SORT_NATURAL );

		foreach ( $post_type_titles_struct as $title_initial => $grouped_titles ) {
			$group_link          = '/' . $instance['selected_post_type'] . '/' . A2ZAAL_REWRITE_TAG . '/' . $title_initial;
			$group_count_display = ! empty( $instance['show_counts'] )
				? '<span>' . number_format_i18n( count( $grouped_titles ) ) . '</span>'
				: '';
			$link_classes        = ! empty( $instance['show_counts'] ) ? [ 'count' ] : [];
			$link_classes        = implode( ' ', apply_filters( 'a2zaal_link_css_class', $link_classes, $instance, $args ) );
			$link_title          = trim( apply_filters( 'a2zaal_link_title', '', $instance, $args ) );
			$link_text_display   = $title_initial;

			if ( 0 === $title_initial ) {
				$group_link        = '/' . $instance['selected_post_type'] . '/' . A2ZAAL_REWRITE_TAG . '/num';
				$link_text_display = '#';
			}

			// TODO: Make sure link is accessible.
			$display_links[] = sprintf(
				'<li><a href="%s" class="%s" title="%s">%s%s</a></li>',
				$group_link,
				$link_classes,
				$link_title,
				$link_text_display,
				$group_count_display
			);
		}

		$container_classes = [];

		if ( $instance['show_counts'] ) {
			$container_classes[] = 'counts';
		}

		$container_classes = apply_filters( 'a2zaal_container_classes', $container_classes, $instance, $args );

		$container_class_output = empty( $container_classes ) ? '' : ' class="' . implode( ' ', $container_classes ) . '"';

		if ( empty( $display_links ) ) {
			$display_links[] = '<p>' . esc_html__( 'No links to display.', 'nvwd-a2zaal' ) . '</p>';
		}

		print \wp_kses_post( $args['before_widget'] );
		print \wp_kses_post( $args['before_title'] . $instance['title'] . $args['after_title'] );
		print \wp_kses_post( '<ul' . $container_class_output . '>' );
		print \wp_kses_post( implode( '', $display_links ) );
		print '</ul>';
		print \wp_kses_post( $args['after_widget'] );
	}
	// phpcs:enable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
}
