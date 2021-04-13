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
			self::display_custom_title();
			return;
		}

		if ( 'featured-image' === $column_name ) {
			the_post_thumbnail( 'medium' );
			return;
		}

		if ( 'more-menu' === $column_name ) {
			printf(
				'<a href="#"><span class="dashicons dashicons-ellipsis"><span class="screen-reader-text">%s</span></span></a>',
				__( 'Toggle menu' )
			);
			return;
		}
	}

	private static function display_custom_title() {
		global $post;
		$can_edit_post = current_user_can( 'edit_post', $post->ID );

		$count_users = count_users();
		if ( $count_users['total_users'] > 1 ) {
			printf( '<div class="author">%s</div>', get_the_author() );
		}

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

		$time_info = self::get_time_info();
		printf(
			'<time class="relative-time" datetime="%s"><span class="dashicons dashicons-clock"></span>%s</time>',
			$time_info['timestamp'],
			$time_info['human']
		);

		get_inline_data( $post );
	}

	private static function get_time_info() {
		global $post;

		$time_info = array(
			'timestamp' => '',
			'human'     => '',
		);

		$time_info['timestamp'] = get_post_time();
		if ( in_array( $post->post_status, array( 'draft', 'trash', 'pending' ) ) ) {
			$time_info['timestamp'] = get_post_modified_time();
		}

		$time_info['human'] = sprintf(
			/* translators: 1: Post date, 2: Post time. */
			__( '%1$s at %2$s' ),
			wp_date( get_option('date_format'), $time_info['timestamp'] ),
			wp_date( get_option('time_format'), $time_info['timestamp'] )
		);

		if ( 'future' === $post->post_status ) {
			if ( DAY_IN_SECONDS >  time() - $time_info['timestamp'] ) {
				$time_info['human'] = sprintf(
					/* translators: Refers to time */
					__( 'Tomorrow at %s' ),
					wp_date( get_option('time_format'), $time_info['timestamp'] )
				);
			}
			if ( time() > $time_info['timestamp'] ) {
				$time_info['human'] .= sprintf( ' <strong>%s</strong>', __( '(Missed schedule)' ) );
			}
		} else if ( WEEK_IN_SECONDS > time() - $time_info['timestamp'] ) {
			$time_info['human'] = sprintf(
				/* translators: Refers to relative time */
				__( '%s ago' ),
				human_time_diff( $time_info['timestamp'] )
			);
		}

		return $time_info;
	}
}

Copons_Post_List_Experiment::init();
