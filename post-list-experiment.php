<?php
/**
 * Plugin Name: Post List Experiment
 * Requires at least: 5.6
 * Requires PHP: 5.6
 * Version: 0.0.1
 * Author: Copons
 * Text Domain: copons
 */

class Copons_Post_List_Experiment {
	public static function init() {
		add_filter( 'post_row_actions', [ __CLASS__, 'remove_post_row_actions' ], 10, 2);
		add_filter( 'manage_posts_columns', [ __CLASS__, 'customize_posts_columns' ], 10, 1 );
	}

	public static function remove_post_row_actions( $actions, $post ) {
		return array();
	}

	public static function customize_posts_columns( $posts_columns ) {
		unset( $posts_columns[ 'author'] );
		unset( $posts_columns[ 'categories'] );
		unset( $posts_columns[ 'tags'] );
		unset( $posts_columns[ 'comments'] );
		unset( $posts_columns[ 'date'] );

		$posts_columns['featured-image'] = __( 'Featured Image' );
		$posts_columns['more-menu'] = '';

		return $posts_columns;
	}
}

Copons_Post_List_Experiment::init();
