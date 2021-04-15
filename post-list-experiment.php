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

		// Enqueue admin theme colors
		global $_wp_admin_css_colors;
		$current_admin_theme = get_user_option( 'admin_color' );
		if ( empty( $current_admin_theme ) || ! isset( $_wp_admin_css_colors[ $current_admin_theme ] ) ) {
			$current_admin_theme = 'fresh';
		}
		$current_colors = $_wp_admin_css_colors[ $current_admin_theme ]->colors;
		$custom_css = ':root {
			--post-list-base-color: ' . $current_colors[0] . ';
			--post-list-highlight-color: ' . $current_colors[1] . ';
		}';
		wp_add_inline_style( 'post-list-experiment', $custom_css );

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
		$custom_columns              = array_merge( $custom_columns, $posts_columns );
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
		$title         = _draft_or_post_title();

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
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
		//if ( $count_users['total_users'] > 1 ) {
			printf( '<div class="post-author">%s</div>', get_the_author() );
		//}
		
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

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
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
		$actions               = array();
		$can_edit_post         = current_user_can( 'edit_post', $post->ID );
		$can_delete_post       = current_user_can( 'delete_post', $post->ID );
		$can_publish_posts     = current_user_can( 'publish_posts' );
		$can_moderate_comments = current_user_can( 'moderate_comments', $post->ID );
		$title                 = _draft_or_post_title();

		if ( $can_edit_post && 'trash' !== $post->post_status ) {
			$actions['edit'] = sprintf(
				'<a href="%s" data-action="edit" aria-label="%s">%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
				__( 'Edit' )
			);
		}

		if ( in_array( $post->post_status, array( 'pending', 'draft', 'future' ), true ) ) {
			if ( $can_edit_post ) {
				$preview_link    = get_preview_post_link( $post );
				$actions['view'] = sprintf(
					'<a href="%s" data-action="view" rel="bookmark" aria-label="%s">%s</a>',
					esc_url( $preview_link ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ),
					__( 'Preview' )
				);
			}
		} elseif ( 'trash' !== $post->post_status ) {
			$actions['view'] = sprintf(
				'<a href="%s" data-action="view" rel="bookmark" aria-label="%s">%s</a>',
				get_permalink( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'View &#8220;%s&#8221;' ), $title ) ),
				__( 'View' )
			);
		}

		if ( $can_publish_posts && in_array( $post->post_status, array( 'pending', 'draft' ), true ) ) {
			$actions['publish'] = sprintf(
				'<a href="%s" data-action="publish" aria-label="%s">%s</a>',
				'#',
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Publish &#8220;%s&#8221;' ), $title ) ),
				__( 'Publish' )
			);
		}

		if ( $can_edit_post && $can_moderate_comments ) {
			$actions['comments'] = sprintf(
				'<a href="%s" data-action="comments" aria-label="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'p'              => $post->ID,
							'comment_status' => 'approved',
						),
						admin_url( 'edit-comments.php' )
					)
				),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'View comments to &#8220;%s&#8221;' ), $title ) ),
				__( 'Comments' )
			);
		}

		if ( 'publish' === $post->post_status ) {
			$actions['copy-link'] = sprintf(
				'<a href="%s" data-action="copy-link" aria-label="%s">%s</a>',
				get_permalink( $post->ID ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Copy link to &#8220;%s&#8221;' ), $title ) ),
				__( 'Copy link' )
			);
		}

		if ( $can_delete_post ) {
			if ( 'trash' === $post->post_status ) {
				$actions['untrash'] = sprintf(
					'<a href="%s" data-action="untrash" aria-label="%s">%s</a>',
					wp_nonce_url( sprintf( get_edit_post_link( $post->ID ) . '&amp;action=untrash', $post->ID ), 'untrash-post_' . $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
					__( 'Restore' )
				);
			} else {
				$actions['trash'] = sprintf(
					'<a href="%s" data-action="trash" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID ),
					/* translators: %s: Post title. */
					esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ),
					_x( 'Trash', 'verb' )
				);
			}
		}

		if ( $can_delete_post && 'trash' === $post->post_status ) {
			$actions['delete'] = sprintf(
				'<a href="%s" data-action="delete" aria-label="%s">%s</a>',
				get_delete_post_link( $post->ID, '', true ),
				/* translators: %s: Post title. */
				esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
				__( 'Delete Permanently' )
			);
		}

		$actions = apply_filters( 'post_row_actions', $actions, $post );

		echo '<div class="more-menu-wrapper">';
		printf(
			'<button class="more-menu-toggle button-link" type="button"><span class="dashicons dashicons-ellipsis"><span class="screen-reader-text">%s</span></span></button>',
			__( 'Toggle menu' )
		);
		echo '<div class="more-menu-popover"><div class="more-menu-popover-arrow"></div>';

		foreach( $actions as $action ) {
			echo $action;
		}

		echo '</div></div>';
	}
}

Copons_Post_List_Experiment::init();
