<?php
	/*
		Plugin Name: A2Z Post Titles
		Plugin URI: https://github.com/NowellVanHoesen/A2Z-Post-Titles/wiki
		Description: This WordPress plugin will get a list of characters, A to Z, from the initial character of a post or CPT title. The Initials will link to a page/archive of posts/CPTs that begin with that character.
		Version: 1.0
		Author: Nowell VanHoesen
		Author URI: http://nvwebdev.com/
		Author Email: nowell@nvwebdev.com
		License:
		
		Copyright 2013 Nowell VanHoesen (nowell@nvwebdev.com)
		
		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
		
	*/
	add_filter( 'query_vars', 'a2zpt_query_vars' );
	add_action( 'pre_get_posts', 'a2z_check_qv' );
	add_action( 'widgets_init', 'a2zpt_register_widgets' );
	add_action( 'wp_print_styles', 'register_a2zpt_styles' );

	function a2zpt_query_vars( $query_vars ) {
		array_push( $query_vars, 'a2zpt' );
		return $query_vars;
	}
	
	function a2z_check_qv( $query ) {
		global $wp_query;
		if( $query->is_main_query() && isset( $wp_query->query_vars['a2zpt'] ) ) {
			// modify the where/orderby similar to above examples
			echo "<!-- made it into modify the query -->";
			add_filter( 'posts_where', 'a2zpt_modify_query_where' );
			add_filter( 'posts_orderby', 'a2zpt_modify_query_orderby' );
		}
	}
	
	function a2zpt_modify_query_where( $where ) {
		global $wp_query, $wpdb; //$wpdb->posts.
		$where .= " AND substring( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ), 1, 1) = '" . $wp_query->query_vars['a2zpt'] . "'";
		return $where;
	}
	
	function a2zpt_modify_query_orderby( $orderby ) {
		global $wp_query, $wpdb;
		$orderby = "( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ) )";
		return $orderby;
	}
	
	function a2zpt_register_widgets() {
		register_widget( 'a2zpt_widget' );
	}
	
	class a2zpt_widget extends WP_Widget {
		
		function a2zpt_widget() {
			$opts = array(
				'classname' => 'a2zpt_widget',
				'description' => 'Display a list of post/cpt title initials that link to a list of posts beginning with that initial.',
			);
			$this->WP_Widget( 'a2zpt_widget', 'A2Z Post Titles', $opts );
		}
		
		function form( $instance ) {
			$available_posts_types = get_post_types( array( 'public' => true ) );
			$exclude_pts = array( 'attachment' );
			$defaults = array(
				'post_type' => 'post',
				'title' => '',
				/*'show_counts' => 'false',*/
			);
			$instance = wp_parse_args( (array) $instance, $defaults );
			$post_type = $instance['post_type'];
			$title = $instance['title'];
			echo '<p>Title: <input class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';
			echo '<p>Post Type: <select name="' . $this->get_field_name( 'post_type' ) . '">';
			foreach ( $available_posts_types AS $post_type_name ) {
				if ( array_search( $post_type_name, $exclude_pts ) === false ) {
					echo '<option value="' . esc_attr( $post_type_name ) . '" ' . selected( $post_type, $post_type_name, false ) . '>' . esc_attr( $post_type_name ) . '</option>';
				}
			}
			echo '</select></p>';
		}
		
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['post_type'] = strip_tags( $new_instance['post_type'] );
			return $instance;
		}
		
		function widget( $args, $instance ) {
			extract( $args );
			// run query to pull the initials for the specified post_type
			global $wpdb;
			$querystr = "
				SELECT DISTINCT substring( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ), 1, 1) as initial 
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_status = 'publish' 
				AND $wpdb->posts.post_type = '" . $instance['post_type'] . "'
				ORDER BY TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) );
			";
			$pt_initials = $wpdb->get_results( $querystr, ARRAY_A );
			$initial_arr = array();
			foreach( $pt_initials AS $pt_rec ) {
				$link = add_query_arg( 'a2zpt', $pt_rec['initial'], get_post_type_archive_link( $instance['post_type'] ) );
				$initial_arr[] = '<li><a href="' . $link . '">' . $pt_rec['initial'] . '</a></li>';
			}
			$initial_list = '<ul>' . implode( '', $initial_arr ) . '</ul>';
			
			// check widget title - get post_type name if it is empty
			if ( strlen( $instance['title'] ) == 0 ) {
				$pt_obj = get_post_type_object( $instance['post_type'] );
				$instance['title'] = $pt_obj->labels->name;
			}
			$title = apply_filters( 'widget_title', $instance['title'] );
			
			echo $before_widget;
			echo $before_title . $title . $after_title;
			echo $initial_list;
			echo $after_widget;
			
		}
		
	}
	
	function register_a2zpt_styles() {
		wp_register_style( 'myWidgetStylesheet', plugins_url( 'css/display.css', __FILE__ ) );
		wp_enqueue_style( 'myWidgetStylesheet' );
	}
	