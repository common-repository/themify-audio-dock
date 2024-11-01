<?php
/*
Plugin Name:  Themify Audio Dock
Plugin URI:   https://themify.me/
Version:      2.0.4 
Author:       Themify
Author URI:   https://themify.me
Description:  An slick and simple sticky music player.
Text Domain:  themify-audio-dock
Domain Path:  /languages
Requires PHP: 7.2
Compatibility: 5.0.0
*/

if ( !defined( 'ABSPATH' ) ) exit;

class Themify_Player {

	private static $url;
	private static $dir;

	/* Themify_Playlist members */
	private $type     = '';
	private $types    = array( 'audio', 'video' );
	private $instance = 0;

	function __construct() {
		self::$url = trailingslashit( plugin_dir_url( __FILE__ ) );
		self::$dir = trailingslashit( plugin_dir_path( __FILE__ ) );

		add_action( 'plugins_loaded', array( $this, 'i18n' ), 5 );
		add_action( 'template_redirect', array( $this, 'frontend_display' ) );
		add_filter( 'plugin_row_meta', array( $this, 'themify_plugin_meta'), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'action_links') );
		add_filter('script_loader_tag',array( $this, 'defer_js'), 11, 3);
		add_shortcode( 'themify_playlist', array( $this, 'playlist_shortcode' ) );
		add_shortcode( 'themify_trac',     array( $this, 'trac_shortcode'     ) );

		if( is_admin() ) {
			include self::$dir . 'includes/admin.php';
		}
	}

	
	public static function get_version(){
		return '2.0.4';
	}

	public function i18n() {
		load_plugin_textdomain( 'themify-audio-dock', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	public function themify_plugin_meta( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$row_meta = array(
			  'changelogs'    => '<a href="' . esc_url( 'https://themify.org/changelogs/' ) . basename( dirname( $file ) ) .'.txt" target="_blank" aria-label="' . esc_attr__( 'Plugin Changelogs', 'themify-audio-dock' ) . '">' . esc_html__( 'View Changelogs', 'themify-audio-dock' ) . '</a>'
			);
	 
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	public function action_links( $links ) {
		if ( is_plugin_active( 'themify-updater/themify-updater.php' ) ) {
			$tlinks = array(
			 '<a href="' . admin_url( 'index.php?page=themify-license' ) . '">'.__('Themify License', 'themify-audio-dock') .'</a>',
			 );
		} else {
			$tlinks = array(
			 '<a href="' . esc_url('https://themify.me/docs/themify-updater-documentation') . '">'. __('Themify Updater', 'themify-audio-dock') .'</a>',
			 );
		}
		return array_merge( $links, $tlinks );
	}

	function frontend_display() {
		/* disable the plugin on Themify Builder's frontend editor */
		if ( method_exists( 'Themify_Builder_Model', 'is_front_builder_activate' ) && Themify_Builder_Model::is_front_builder_activate() ) {
			return;
		}

		$playlist = $this->get_playlist();
		if ( ! empty( $playlist ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_footer', array( $this, 'render' ) );
		}
	}

	function enqueue() {
	    $v=self::get_version();
	    wp_playlist_scripts( 'audio' );
	    wp_enqueue_style( 'themify-audio-dock', self::$url . 'assets/styles.css',null,$v);
	    $bar_color = get_option( 'themify_audio_dock_bar_color' );
	    $track_color = get_option( 'themify_audio_dock_track_color' );
	    if( $bar_color ) {
		    wp_add_inline_style( 'themify-audio-dock', sprintf( '
		    #themify-audio-dock,
		    #themify-audio-dock.collapsed .themify-audio-dock-inner .button-switch-player {
			    background-color: %s;
		    }
		    ', $bar_color ) );
	    }
	    if( $track_color ) {
		    wp_add_inline_style( 'themify-audio-dock', sprintf( '
		    #themify-audio-dock .tracklist .wp-playlist-themify .mejs-controls .mejs-horizontal-volume-slider .mejs-horizontal-volume-current,
		    #themify-audio-dock .mejs-container .mejs-controls .mejs-time-rail .mejs-time-current {
			    background-color: %s;
		    }
		    ', $track_color ) );
	    }
	    wp_enqueue_script( 'themify-audio-dock', self::$url . 'assets/scripts.js', array( 'jquery' ), $v, true );
	}

	function render() {
		include( self::$dir . 'includes/template.php' );
	}

	public function get_playlist() {
		return get_option( 'themify_audio_dock_playlist', array() );
	}

	/**
	 * Callback for the [themify_playlist] shortcode
	 */
	public function playlist_shortcode( $atts = array(), $content = '' ) {
		$this->instance++;
		$atts = shortcode_atts(
			array(
				'type'          => 'audio',
				'style'         => 'light',
				'tracklist'     => 'true',
				'tracknumbers'  => 'true',
				'images'        => 'true',
				'artists'       => 'true',
				'current'       => 'true',
				'loop'          => 'false',
				'preload'       => 'metadata', // none, auto
				'id'            => '',
				'width'         => '',
				'height'        => '',
			), $atts, 'themify_playlist_shortcode' );

		//----------
		// Input
	    //----------
		$atts['id']           = esc_attr( $atts['id'] );
		$atts['type']         = esc_attr( $atts['type'] );
		$atts['style']        = esc_attr( $atts['style'] );
		$atts['tracklist']    = filter_var( $atts['tracklist'], FILTER_VALIDATE_BOOLEAN );
		$atts['tracknumbers'] = filter_var( $atts['tracknumbers'], FILTER_VALIDATE_BOOLEAN );
		$atts['images']       = filter_var( $atts['images'], FILTER_VALIDATE_BOOLEAN );

		// Audio specific:
		$atts['artists']      = filter_var( $atts['artists'], FILTER_VALIDATE_BOOLEAN );
		$atts['current']      = filter_var( $atts['current'], FILTER_VALIDATE_BOOLEAN );

		// Video specific:
		$atts['loop']         = filter_var( $atts['loop'], FILTER_VALIDATE_BOOLEAN );

		// Nested shortcode support:
		$this->type           = ( in_array( $atts['type'], $this->types, TRUE ) ) ? $atts['type'] : 'audio';

		// Get tracs:
		$content              = strip_tags( nl2br( do_shortcode( $content ) ) );

		// Replace last comma:
	    if( false !== ( $pos = strrpos( $content, ',' ) ) ) {
			$content = substr_replace( $content, '', $pos, 1 );
		}

		// Enqueue default scripts and styles for the playlist.
		if ( 1 === $this->instance ) {
			do_action( 'wp_playlist_scripts', $atts['type'], $atts['style'] );
		}
	    //----------
		// Output
	    //----------
		$html = sprintf( '<div class="wp-playlist wp-%s-playlist wp-playlist-%s">', $this->type, $atts['style'] );

		// Current audio item:
		if( $atts['current'] && 'audio' === $this->type ) {
			$html .= '<div class="wp-playlist-current-item"></div>';
		}

		// Video player:
		if( 'video' === $this->type ):
			$html .= sprintf( '<video controls="controls" preload="none" width="%s" height="%s"></video>',
				$atts['style'],
				$atts['width'],
				$atts['height']
			);
		// Audio player:
		else:
			$html .= sprintf(
	            '<audio controls="controls" preload="%s"></audio>',
	            $atts['preload']
			);
		endif;

	   // Next/Previous:
	    $html .= '<div class="wp-playlist-next"></div><div class="wp-playlist-prev"></div>';

		// JSON
		$html .= sprintf( '
			<script class="wp-playlist-script" type="application/json">{
				"type":"%s",
				"tracklist":%b,
				"tracknumbers":%b,
				"images":%b,
				"artists":%b,
				"tracks":[%s]
			}</script></div>',
			$atts['type'],
			$atts['tracklist'],
			$atts['tracknumbers'],
			$atts['images'],
			$atts['artists'],
			$content
		);
		return $html;
	}

	/**
	 * Callback for the [themify_trac] shortcode
	 */
	public function trac_shortcode( $atts = array(), $content = '' ) {
		$atts = shortcode_atts(
			array(
				 'src'                   => '',
				 'type'                  => ( 'video' === $this->type ) ? 'video/mp4' : 'audio/mpeg',
				 'title'                 => '',
				 'caption'               => '',
				 'description'           => '',
				 'image_src'             => sprintf( '%s/wp-includes/images/media/%s.png', get_site_url(), $this->type ),
				 'image_width'           => '48',
				 'image_height'          => '64',
				 'thumb_src'             => sprintf( '%s/wp-includes/images/media/%s.png', get_site_url(), $this->type ),
				 'thumb_width'           => '48',
				 'thumb_height'          => '64',
				 'meta_artist'           => '',
				 'meta_album'            => '',
				 'meta_genre'            => '',
				 'meta_length_formatted' => '',

				 'dimensions_original_width'  => '300',
				 'dimensions_original_height' => '200',
				 'dimensions_resized_width'   => '600',
				 'dimensions_resized_height'  => '400',
			), $atts, 'themify_trac_shortcode' );

		//----------
		// Input
		//----------
		$data['src']                      = esc_url( $atts['src'] );
		$data['title']                    = sanitize_text_field( $atts['title'] );
		$data['type']                     = sanitize_text_field( $atts['type'] );
		$data['caption']                  = sanitize_text_field( $atts['caption'] );
		$data['description']              = sanitize_text_field( $atts['description'] );
		$data['image']['src']             = esc_url( $atts['image_src'] );
		$data['image']['width']           = intval( $atts['image_width'] );
		$data['image']['height']          = intval( $atts['image_height'] );
		$data['thumb']['src']             = esc_url( $atts['thumb_src'] );
		$data['thumb']['width']           = intval( $atts['thumb_width'] );
		$data['thumb']['height']          = intval( $atts['thumb_height'] );
		$data['meta']['length_formatted'] = sanitize_text_field( $atts['meta_length_formatted'] );

		// Video related:
		if( 'video' === $this->type ) {
			$data['dimensions']['original']['width']  = sanitize_text_field( $atts['dimensions_original_width'] );
			$data['dimensions']['original']['height'] = sanitize_text_field( $atts['dimensions_original_height'] );
			$data['dimensions']['resized']['width']   = sanitize_text_field( $atts['dimensions_resized_width'] );
			$data['dimensions']['resized']['height']  = sanitize_text_field( $atts['dimensions_resized_height'] );

		// Audio related:
		} else {
			$data['meta']['artist'] = sanitize_text_field( $atts['meta_artist'] );
			$data['meta']['album']  = sanitize_text_field( $atts['meta_album'] );
			$data['meta']['genre']  = sanitize_text_field( $atts['meta_genre'] );
		}

		//----------
		// Output:
		//----------
		return json_encode( $data ) . ',';
	}

	/**
	 * Check if the site is using an HTTPS scheme and returns the proper url
	 * @param String $url requested url
	 * @return String
	 * @since 1.0.0
	 */
	function https_esc( $url ) {
		if ( is_ssl() ) {
			$url = preg_replace( '/^(http:)/i', 'https:', $url, 1 );
		}
		return $url;
	}

	function hex2rgb( $hex ) {
		$hex = str_replace('#','', $hex);

		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
		$rgb = array($r, $g, $b);
		return implode(",", $rgb); // returns the rgb values separated by commas
	}
	
	public function defer_js($tag,$handle,$src){
		if(strpos($tag,' defer',5)==false && strpos($src,self::$url)!==false){
			$tag = str_replace(' src', ' defer="defer" src', $tag);
		}
		return $tag;
	}

	public static function get_url(){
		return self::$url;
	}
	
}
new Themify_Player();
