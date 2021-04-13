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

		//add_filter( 'post_row_actions', [ __CLASS__, 'remove_post_row_actions' ] );
		add_filter( 'manage_posts_columns', [ __CLASS__, 'customize_posts_columns' ], 100 );
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
			plugins_url( 'post-list-experiment.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . 'post-list-experiment.css' )
		);
		wp_enqueue_style( 'post-list-experiment' );

		wp_register_script(
			'post-list-experiment',
			plugins_url( 'post-list-experiment.js', __FILE__ ),
			array( 'wp-dom-ready' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'post-list-experiment.js' ),
			true
		);
		wp_enqueue_script( 'post-list-experiment' );
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

		// Make sure checkbox and post item are the first two columns,
		// then append all other custom columns, and finally the more menu.
		$custom_columns = array();
		if ( array_key_exists( 'cb', $posts_columns ) ) {
			$custom_columns['cb'] = $posts_columns['cb'];
		}
		$custom_columns['post-item'] = __( 'Posts' );
		$custom_columns = array_merge( $custom_columns, $posts_columns );
		$custom_columns['more-menu'] = '';

		return $custom_columns;
	}

	public static function display_custom_columns( $column_name ) {
		if ( 'post-item' === $column_name ) {
			self::display_post_item();
			return;
		}

		if ( 'more-menu' === $column_name ) {
			self::display_more_menu();
		}
	}

	private static function display_post_item() {
		global $post;
		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$title = _draft_or_post_title();

		if ( $can_edit_post ) {
			printf(
				'<a class="post-item" href="%s" aria-label="%s">',
				get_edit_post_link(),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)' ), $title ) ),
			);
		} else {
			echo '<div class="post-item">';
		}

		echo '<div>';

		$count_users = count_users();
		if ( $count_users['total_users'] >= 1 ) {
			printf( '<div class="post-author">%s</div>', get_the_author() );
		}
		
		echo '<div class="post-title">';
		printf( '<strong>%s</strong>', $title );
		_post_states( $post );
		echo '</div>';

		$time_info = self::get_time_info();
		printf(
			'<time class="post-date" datetime="%s"><span class="dashicons dashicons-clock"></span>%s</time>',
			$time_info['timestamp'],
			$time_info['human']
		);

		echo '</div>';

		echo '<div class="post-thumbnail">';
		the_post_thumbnail( 'medium' );
		echo '</div>';

		get_inline_data( $post );

		if ( $can_edit_post ) {
			echo '</a>';
		} else {
			echo '</div>';
		}
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

	private static function display_more_menu() {
		global $post;

		echo '<div class="more-menu">';

		printf(
			'<a href="#" class="more-menu-toggle"><span class="dashicons dashicons-ellipsis"><span class="screen-reader-text">%s</span></span></a>',
			__( 'Toggle menu' )
		);

		$actions = array();
		$actions = apply_filters( 'post_row_actions', $actions, $post );

		printf( '<div class="more-menu-popover hidden">%s</div>', 'More Menu' );

		echo '</div>';
	}
}

Copons_Post_List_Experiment::init();
