<?php
/**
 * Plugin Name: Last Modified by Mihdan
 */

namespace Mihdan\Last_Modified;

class Main {
	private $options = array();

	public function __construct() {
		$this->setup();
		$this->hooks();
	}

	public function setup() {
		$this->options[ 'last_modified_exclude' ] = '';
	}

	public function hooks() {
		add_action( 'template_redirect', array($this, 'set_last_modified_headers'), 999 );
	}

	/**
	 * Set last modified to all posts and archives
	 *
	 * @since    0.9.7
	 */
	public function set_last_modified_headers() {

		$last_modified_exclude = $this->options['last_modified_exclude'];

		$last_modified_exclude_exp = explode(PHP_EOL, $last_modified_exclude);

		$current_url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		foreach ($last_modified_exclude_exp as $expr) {
			if( ! empty($expr) && @preg_match( "~$expr~", $current_url ) ) {
				return;
			}
		}

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		     || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
		     || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		     || ( is_admin() ) ) {
			return;
		}

		/**
		 * if WooCommerce cart, checkout, account - just return
		 */
		if ( class_exists( 'woocommerce' ) && function_exists('is_cart') && function_exists('is_checkout') && function_exists('is_account_page')
		     && ( is_cart() || is_checkout() || is_account_page() ) ) return;

		/**
		 * if Search - just return
		 */
		if ( is_search() ) return;


		$last_modified = '';


		/**
		 * If posts, pages, custom post types
		 */
		if ( is_singular() ) {
			global $post;

			if ( ! isset($post->post_modified_gmt) ) {
				return;
			}

			$post_time = strtotime( $post->post_modified_gmt );
			$modified_time = $post_time;

			/**
			 * If we have comment set new modified date
			 */
			if ( (int) $post->comment_count > 0 ) {
				$comments = get_comments( array(
					'post_id' => $post->ID,
					'number' => '1',
					'status' => 'approve',
					'orderby' => 'comment_date_gmt',
				) );
				if ( ! empty($comments) && isset($comments[0]) ) {
					$comment_time = strtotime( $comments[0]->comment_date_gmt );
					if ( $comment_time > $post_time ) {
						$modified_time = $comment_time;
					}
				}
			}

			$last_modified = str_replace('+0000', 'GMT', gmdate('r', $modified_time));

		}

		/**
		 * If any archives: categories, tags, taxonomy terms, post type archives
		 */
		if ( is_archive() || is_home() ) {
			global $posts;

			if ( empty($posts) ) {
				return;
			}

			$post = $posts[0];

			if ( ! isset($post->post_modified_gmt) ) {
				return;
			}

			$post_time = strtotime( $post->post_modified_gmt );
			$modified_time = $post_time;

			$last_modified = str_replace('+0000', 'GMT', gmdate('r', $modified_time));
		}


		/**
		 * If headers already sent - do nothing
		 */
		if ( headers_sent() ) {
			return;
		}


		if ( ! empty($last_modified) ) {
			header( 'Last-Modified: ' . $last_modified );

			if ( $this->check_option('if_modified_since_headers') && ! is_user_logged_in() ) {
				if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $modified_time) {
					$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
					header($protocol . ' 304 Not Modified');
				}
			}
		}
	}

	private function check_option( $option_name ) {
		return true;
	}
}

new Main();
