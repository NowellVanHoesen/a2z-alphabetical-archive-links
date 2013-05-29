<?php
	/*
		Plugin Name: A2Z Alphabetical Archive Links
		Plugin URI: https://github.com/NowellVanHoesen/A2Z-Alphabetical-Archive-Links/wiki
		Description: This WordPress plugin will get a list of characters, A to Z, from the initial character of a post or CPT title. The Initials will link to an archive page of posts/CPTs that begin with that character.
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
	
	/* add actions and filters */
	add_filter( 'query_vars', 'a2zaal_query_vars' );
	add_action( 'pre_get_posts', 'a2z_check_qv' );
	add_action( 'wp_print_styles', 'register_a2zaal_styles' );
	add_action( 'widgets_init', 'a2zaal_register_widgets' );
	
	function register_a2zaal_styles() {
		wp_register_style( 'myWidgetStylesheet', plugins_url( 'css/display.css', __FILE__ ) );
		wp_enqueue_style( 'myWidgetStylesheet' );
	}
	
	function a2zaal_query_vars( $query_vars ) {
		array_push( $query_vars, 'a2zaal' );
		return $query_vars;
	}
	
	function a2z_check_qv( $query ) {
		global $wp_query;
		if( $query->is_main_query() && isset( $wp_query->query_vars['a2zaal'] ) ) {
			// if we are on the main query and the query var 'a2zaal' exists, modify the where/orderby statements
			add_filter( 'posts_where', 'a2zaal_modify_query_where' );
			add_filter( 'posts_orderby', 'a2zaal_modify_query_orderby' );
		}
	}
	
	function a2zaal_modify_query_where( $where ) {
		global $wp_query, $wpdb;
		$where .= " AND substring( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ), 1, 1) = '" . $wp_query->query_vars['a2zaal'] . "'";
		return $where;
	}
	
	function a2zaal_modify_query_orderby( $orderby ) {
		global $wp_query, $wpdb;
		$orderby = "( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ) )";
		return $orderby;
	}
	
	function a2zaal_register_widgets() {
		register_widget( 'a2zaal_widget' );
	}
	
	class a2zaal_widget extends WP_Widget {
		
		function a2zaal_widget() {
			$opts = array(
				'classname' => 'a2zaal_widget',
				'description' => 'Display a list of post/cpt title initials that link to a list of posts beginning with that initial.',
			);
			$this->WP_Widget( 'a2zaal_widget', 'A2Z Alphabetical Archive Links', $opts );
		}
		
		function form( $instance ) {
			$available_posts_types = get_post_types( array( 'public' => true ) );
			$exclude_pts = array( 'attachment' );
			$defaults = array(
				'post_type' => 'post',
				'title' => '',
				'show_counts' => 0,
			);
			$instance = wp_parse_args( (array) $instance, $defaults );
			$post_type = $instance['post_type'];
			$title = $instance['title'];
			$show_counts = $instance['show_counts'];
			echo '<p>Title: <input class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" /></p>';
			echo '<p>Post Type: <select name="' . $this->get_field_name( 'post_type' ) . '">';
			foreach ( $available_posts_types AS $post_type_name ) {
				if ( array_search( $post_type_name, $exclude_pts ) === false ) {
					echo '<option value="' . esc_attr( $post_type_name ) . '" ' . selected( $post_type, $post_type_name, false ) . '>' . esc_attr( $post_type_name ) . '</option>';
				}
			}
			echo '</select></p>';
			echo '<p>Show Counts: <input name="' . $this->get_field_name( 'show_counts' ) . '" type="checkbox" ' . checked( $show_counts, 'on', false ) . ' /></p>';
		}
		
		function update( $new_instance, $old_instance ) {
			$instance = $old_instance;
			$instance['title'] = strip_tags( $new_instance['title'] );
			$instance['post_type'] = strip_tags( $new_instance['post_type'] );
			$instance['show_counts'] = strip_tags( $new_instance['show_counts'] );
			return $instance;
		}
		
		function widget( $args, $instance ) {
			extract( $args );
			// run query to pull the initials for the specified post_type
			global $wpdb;
			$count_col = '';
			if ( (bool) $instance['show_counts'] ) {
				$count_col = ", count( substring( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ), 1, 1) ) as counts";
			}
			$querystr = "
				SELECT DISTINCT substring( TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) ), 1, 1) as initial" . $count_col . "
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_status = 'publish' 
				AND $wpdb->posts.post_type = '" . $instance['post_type'] . "'
				GROUP BY initial
				ORDER BY TRIM( LEADING 'A ' FROM TRIM( LEADING 'AN ' FROM TRIM( LEADING 'THE ' FROM UPPER( $wpdb->posts.post_title ) ) ) );
			";
			$pt_initials = $wpdb->get_results( $querystr, ARRAY_A );
			$initial_arr = array();
			foreach( $pt_initials AS $pt_rec ) {
				$link = add_query_arg( 'a2zaal', $pt_rec['initial'], get_post_type_archive_link( $instance['post_type'] ) );
				if ( (bool) $instance['show_counts'] ) {
					$item = '<li class="count"><a href="' . $link . '">' . $pt_rec['initial'] . '<span>' . $pt_rec['counts'] . '</span>' . '</a></li>';
				} else {
					$item = '<li><a href="' . $link . '">' . $pt_rec['initial'] . '</a></li>';
				}
				$initial_arr[] = $item;
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
