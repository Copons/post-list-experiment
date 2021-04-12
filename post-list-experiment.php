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
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue_scripts' ] );

		add_filter( 'post_row_actions', [ __CLASS__, 'remove_post_row_actions' ] );
		add_filter( 'manage_posts_columns', [ __CLASS__, 'customize_posts_columns' ]);
		add_action( 'manage_posts_custom_column', [ __CLASS__, 'display_custom_columns' ] );
	}

	public static function admin_enqueue_scripts( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( 'post' !== $screen->post_type ) {
			return;
		}

		wp_register_style(
			'post-list-experiment',
			plugins_url( 'styles/post-list-experiment.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'styles/post-list-experiment.css' )
		);
		wp_enqueue_style( 'post-list-experiment' );
	}

	public static function remove_post_row_actions() {
		return array();
	}

	public static function customize_posts_columns( $posts_columns ) {
		unset( $posts_columns[ 'title'] );
		unset( $posts_columns[ 'author'] );
		unset( $posts_columns[ 'categories'] );
		unset( $posts_columns[ 'tags'] );
		unset( $posts_columns[ 'comments'] );
		unset( $posts_columns[ 'date'] );

		$posts_columns['custom-title'] = __( 'Posts' );
		$posts_columns['featured-image'] = '';
		$posts_columns['more-menu'] = '';

		return $posts_columns;
	}

	public static function display_custom_columns( $column_name ) {
		global $post;
		if ( 'custom-title' === $column_name ) {
			self::echo_custom_title();
			return;
		}

		if ( 'featured-image' === $column_name ) {
			the_post_thumbnail( 'medium' );
			return;
		}

		if ( 'more-menu' === $column_name ) {
			echo '<a href="#"><span class="dashicons dashicons-ellipsis"><span class="screen-reader-text">' . __( 'Toggle menu' ) . '</span></span></a>';
			return;
		}
	}

	private static function echo_custom_title() {
		global $post;
		$can_edit_post = current_user_can( 'edit_post', $post->ID );

		$title = _draft_or_post_title();

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
			printf(
				'<a class="custom-title" href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)' ), $title ) ),
				$title
			);
		} else {
			printf( '<span class="custom-title">%s</span>', $title );
		}

		_post_states( $post );

		get_inline_data( $post );
	}
}

Copons_Post_List_Experiment::init();
