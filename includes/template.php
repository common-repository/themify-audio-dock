<?php
/**
 * Template to render the player
 * Created by themify
 * @since 1.0.0
 */

$playlist = $this->get_playlist();
if( empty( $playlist ) )
	return;
?>

<div id="themify-audio-dock" class="jukebox<?php echo get_option( 'themify_audio_dock_collapsed', 'no' ) === 'yes' ? ' collapsed' : ''; ?>">
	<div class="themify-audio-dock-inner tf_clearfix">

		<div class="tracklist">
			<?php
			$html = '';
			foreach( $playlist as $track ) {
				if( empty( $track['file'] ) ) continue;

				$list= '[themify_trac src="' . esc_url( trim($track['file']) ) . '"';
				if( isset( $track['name'] ) ) {
					$list.=' title="'.$this->https_esc( $track['name'] ).'"';
				}
				if( isset( $track['image'] ) ){
					$list.=' thumb_src="'.$this->https_esc( $track['image'] ).'"';
				}
				$list.=']';
		   
				$html.=$list;
			}

			if( $html ) {
				echo do_shortcode( '[themify_playlist type="audio" tracklist="no" tracknumbers="no" images="no" artist="no" style="themify" ' . ( get_option( 'themify_audio_dock_autoplay', 'no' ) === 'yes' ? 'preload="auto" autoplay="yes"' : '' ) . ' ]' . $html . '[/themify_playlist]' );
			}
			?>
		</div>

		<div class="buttons-console-wrap">
			<a href="#" class="button-switch-player"></a>
		</div>

	</div>
</div>