<?php
/**
 * A2Z Alphabetical Archive Links Widget
 *
 * the widget will be registered once there are a2zaal active post types
 *
 * @package     NVWD\A2ZAAL
 * @since       2.0.0
 * @author      nvwd
 * @link        http://nvwebdev.com/
 * @license     GPL-2.0+
 */

namespace NVWD\A2ZAAL;

\add_action( 'widgets_init', __NAMESPACE__ . '\maybe_register_archive_link_widget' );
/**
 * register the a2zaal widget only if there are active post types
 *
 * @author: nvwd
 *
 * @since: 2.0.0
 *
 * @return void
 */
function maybe_register_archive_link_widget() {
	$a2z_active_post_types = namespace\get_a2zaal_active_post_types();
	if ( empty( $a2z_active_post_types ) ) {
		return;
	}

	\register_widget( __NAMESPACE__ . '\a2zaal_widget' );
}

class a2zaal_widget extends \WP_Widget {

	function __construct() {
		$opts = array(
			'classname' => 'a2zaal_widget',
			'description' => __( 'Display a list of post/cpt title initials that link to a list of posts beginning with that initial.', A2ZAAL_TEXT_DOMAIN ),
		);
		parent::__construct( 'a2zaal_widget', __( 'A2Z Alphabetical Archive Links', A2ZAAL_TEXT_DOMAIN ), $opts );
	}

	function form( $instance ) {
		$defaults = array(
			'selected_post_type' => '',
			'title' => '',
			'show_counts' => '',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		extract( $instance );

		$available_posts_types = get_post_types( array( 'publicly_queryable' => true ), 'objects' );

		$a2zaal_active_post_types = namespace\get_a2zaal_active_post_types();

		echo '<p>Title: <input class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';
		echo '<p>Post Type: <select name="' . $this->get_field_name( 'selected_post_type' ) . '">';
		foreach ( $available_posts_types AS $post_type_obj ) {
			if ( in_array( $post_type_obj->name, $a2zaal_active_post_types ) ) {
				echo '<option value="' . esc_attr( $post_type_obj->name ) . '" ' . selected( $selected_post_type, $post_type_obj->name, false ) . '>' . esc_html( $post_type_obj->label ) . '</option>';
			}
		}
		echo '</select></p>';
		echo '<p>Show Counts: <input name="' . $this->get_field_name( 'show_counts' ) . '" type="checkbox" ' . checked( $show_counts, 'on', false ) . ' /></p>';
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['selected_post_type'] = strip_tags( $new_instance['selected_post_type'] );
		$instance['show_counts'] = empty( $new_instance['show_counts'] ) ? '' : 'on';
		return $instance;
	}

	function widget( $args, $instance ) {
		// TODO: make this dynamic to be able to have custom styles enqueued
		wp_enqueue_style( 'default_a2zaal_style', A2ZAAL_ROOT_URL . '/css/display.css', array(), A2ZAAL_VERSION );

		$post_type_titles_struct = get_option( $instance['selected_post_type'] . A2ZAAL_POSTS_SUFFIX, array() );
		$display_links = array();

		ksort( $post_type_titles_struct, SORT_NATURAL );

		foreach ( $post_type_titles_struct AS $title_initial => $grouped_titles ) {
			$group_link = '/' . $instance['selected_post_type'] . '/' . A2ZAAL_REWRITE_TAG . '/' . $title_initial;
			$group_count_display = ! empty( $instance['show_counts'] ) ? '<span>' . number_format_i18n( count( $grouped_titles ) ) . '</span>' : '';
			$link_classes = ! empty( $instance['show_counts'] ) ? array( 'count' ) : array();
			$link_classes = implode( ' ', apply_filters( 'a2zaal_link_css_class', $link_classes, $instance, $args ) );
			$link_title = esc_attr__( trim( apply_filters( 'a2zaal_link_title', '', $instance, $args ) ), A2ZAAL_TEXT_DOMAIN );
			$link_text_display = esc_html__( $title_initial, A2ZAAL_TEXT_DOMAIN );

			if ( '0' == $title_initial ) {
				$group_link = '/' . $instance['selected_post_type'] . '/' . A2ZAAL_REWRITE_TAG . '/num';
				$link_text_display = '#';
			}
			// TODO: Make sure link is accessable
			$display_links[] = sprintf( '<li><a href="%s" class="%s" title="%s">%s%s</a></li>', $group_link, $link_classes, $link_title, $link_text_display, $group_count_display );
		}

		$container_classes = array();

		if ( $instance['show_counts'] ) {
			$container_classes[] = 'counts';
		}

		$container_classes = apply_filters( 'a2zaal_container_classes', $container_classes, $instance, $args );

		$container_class_output = empty( $container_classes ) ? '': ' class="' . implode( ' ', $container_classes ) . '"';

		if ( empty( $display_links ) ) {
			$display_links[] = '<p>' . esc_html__( 'No links to display.', A2ZAAL_TEXT_DOMAIN ) . '</p>';
		}

		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
		echo '<ul' . $container_class_output . '>';
		echo implode( '', $display_links );
		echo '</ul>';
		echo $args['after_widget'];

	}

}
