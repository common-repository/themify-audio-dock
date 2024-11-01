<?php
/**
 * Themify Audio Dock Admin Pages
 *
 * Admin page setting
 *
 * @author    Themify
 */

class Themify_Player_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 100 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function admin_menu() {
		$hook = add_options_page(
			__( 'Themify Audio Dock', 'themify-audio-dock' ),
			__( 'Themify Audio Dock', 'themify-audio-dock' ),
			'manage_options',
			'themify-audio-dock',
			array( $this, 'page_callback' )
		);
		add_action( "admin_print_styles-{$hook}", array( $this, 'enqueue' ) );
	}

    public function page_callback() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Themify Audio Dock', 'themify-audio-dock' ); ?></h2>           
			<form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields( 'themify_audio_dock_option_group' );   
				do_settings_sections( 'themify-audio-dock' );
				submit_button();
				?>
			</form>
		</div>
		<?php
    }

	/**
	 * Register and add settings
	 */
	public function admin_init() {        
		register_setting( 'themify_audio_dock_option_group', 'themify_audio_dock_playlist' );
		register_setting( 'themify_audio_dock_option_group', 'themify_audio_dock_collapsed' );
		register_setting( 'themify_audio_dock_option_group', 'themify_audio_dock_bar_color' );
		register_setting( 'themify_audio_dock_option_group', 'themify_audio_dock_track_color' );

		add_settings_section(
			'themify_audio_dock_playlist_settings',
			null,
			null,
			'themify-audio-dock'
		);

		add_settings_section(
			'themify_audio_dock_styling_settings',
			__( 'Styling', 'themify-audio-dock' ),
			null,
			'themify-audio-dock'
		);

		add_settings_field(
			'themify_audio_dock_playlist', // ID
			__( 'Playlist', 'themify-audio-dock' ),
			array( $this, 'playlist_callback' ), // Callback
			'themify-audio-dock', // Page
			'themify_audio_dock_playlist_settings' // Section           
		);

		add_settings_field(
			'themify_audio_dock_collapsed', // ID
			__( 'Collapsed By Default', 'themify-audio-dock' ),
			array( $this, 'collapsed_callback' ), // Callback
			'themify-audio-dock', // Page
			'themify_audio_dock_playlist_settings' // Section           
		);

		add_settings_field(
			'themify_audio_dock_bar_color', // ID
			__( 'Bar Color', 'themify-audio-dock' ),
			array( $this, 'bar_color' ), // Callback
			'themify-audio-dock', // Page
			'themify_audio_dock_styling_settings' // Section           
		);

		add_settings_field(
			'themify_audio_dock_track_color', // ID
			__( 'Track Color', 'themify-audio-dock' ),
			array( $this, 'track_color' ), // Callback
			'themify-audio-dock', // Page
			'themify_audio_dock_styling_settings' // Section           
		);
    }

	public function bar_color() {
		$color = get_option( 'themify_audio_dock_bar_color' );
		printf( '<input class="themify-audio-dock-color-picker" type="text" value="%s" name="themify_audio_dock_bar_color" />', esc_attr( $color ) );
	}

	public function track_color() {
		$color = get_option( 'themify_audio_dock_track_color' );
		printf( '<input class="themify-audio-dock-color-picker" type="text" value="%s" name="themify_audio_dock_track_color" />', esc_attr( $color ) );
	}

	public function playlist_callback() {
		$playlist = get_option( 'themify_audio_dock_playlist', array() );
		if( empty( $playlist ) ) {
			$playlist = array(
				1 => array( 'name' => '', 'file' => '' )
			);
		}
		echo '<div class="themify-playlist">';

		$playlist = array_values( $playlist ); /* reset keys */

		foreach( $playlist as $key => $track ) {
			echo '<div class="themify-track">';
			printf( '<label>%s <input class="widefat" type="text" name="themify_audio_dock_playlist[%s][name]" value="%s"></label>', __( 'Name', 'themify-audio-dock' ), $key, $track['name'] );
			printf( '<label>%s <input class="widefat song-file-field" type="text" name="themify_audio_dock_playlist[%s][file]" value="%s"></label>', __( 'Song File', 'themify-audio-dock' ), $key, $track['file'] );
			echo '<a href="#" class="themify-audio-dock-media-browse" data-uploader-title="' . __( 'Browse Audio', 'themify-audio-dock' ) . '" data-uploader-button-text="' . __( 'Insert Audio', 'themify-audio-dock' ) . '" data-type="audio">' . __( 'Browse Library', 'themify-audio-dock' ) . '</a>';
			echo '<a href="#" class="themify-audio-dock-delete-track">X</a>';
			echo '</div>';
		}
		echo '</div>';
		echo '<a class="button button-secondary add-new-track">' . __( 'Add New Track', 'themify-audio-dock' ) . '</a>';
	}

	function collapsed_callback() {
		$val = get_option( 'themify_audio_dock_collapsed', 'no' );
		?>
		<select name="themify_audio_dock_collapsed">
			<option value="no" <?php selected( $val, 'no' ); ?>><?php _e( 'No', 'themify-audio-dock' ); ?></option>
			<option value="yes" <?php selected( $val, 'yes' ); ?>><?php _e( 'Yes', 'themify-audio-dock' ); ?></option>
		</select>
		<?php
	}

	function enqueue() {
		$url=Themify_Player::get_url();
		$v=Themify_Player::get_version();
		wp_enqueue_media();
		wp_enqueue_script( 'audiodock-colorpicker', $url . 'assets/jquery.minicolors.min.js', array( 'jquery' ),$v,true );
		wp_enqueue_style( 'audiodock-colorpicker', $url . 'assets/jquery.minicolors.css',null,$v );
		wp_enqueue_style( 'themify-player-admin', $url . 'assets/admin.css', array( 'audiodock-colorpicker' ),$v);
		wp_enqueue_script( 'themify-player-admin', $url . 'assets/admin.js', array( 'jquery', 'audiodock-colorpicker' ),$v,true );
	}
}
new Themify_Player_Admin;