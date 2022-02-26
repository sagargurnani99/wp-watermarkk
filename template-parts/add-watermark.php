<?php
$movieSlug = $_GET['p'] ?? null;

if( $movieSlug == null ) {
	die( _e('The movie slug is missing from the URL!') );
}

$movieSlug = esc_html($movieSlug);

$adminUrl  = $adminUrl . 'admin.php';
$page      = $_GET['page'];

$generatePosterUrl = $adminUrl . '?page=' . urlencode($page) . '&m=' . $movieSlug;

if( isset($_GET['action']) && $_GET['action'] == 'edit_movie' ) {
	$generatePosterUrl .= '&action=edit_movie';
}

if( $movieSlug == null ) {
	die( __('No movie found!') );
}

$pluginUrl       = $objTektonicCreatePosterThumb->plugin_url;
$pluginPath      = $objTektonicCreatePosterThumb->plugin_path;
$fontsFolderUrl  = $pluginUrl . '/fonts';
$fontsFolderPath = $pluginPath . 'fonts';

$arrFonts = $objTektonicCreatePosterThumb->directoryToArray( $fontsFolderPath );
?>
<style>
	<?php
		if( !empty($arrFonts) ) {
			foreach( $arrFonts as $font ) {
				$fontName = str_replace('.ttf', '', $font);
				?>
				@font-face {
	                font-family: <?php echo esc_html($fontName); ?>;
	                src: url('<?php echo esc_url($pluginUrl); ?>/fonts/<?php echo $font; ?>');
	            }
				<?php
			}
		}
	?>

	#watermark_font_family {
		width: 200px;
	    font-size: 20px;
	    height: 30px;
	    line-height: 20px;
	}
</style>
<div id="watermark_div" class="wrap">
	<h1 id="add-new-user"><?php _e('Add overlay text to the generated poster'); ?></h1>
	<p><?php _e('Select your preferences and then click on the movie poster below where you want the overlay to appear.'); ?></p>
	<?php
	if( !empty($movieDetails) ) {
		$movieId         = $movieDetails['id'] ?? 0;
		$movieId         = absint($movieId);

		$uploadFolderUrl = $baseUrl . '/movies/m' . $movieId . '/';
		$moviePosterName = $movieDetails['poster_filename'] ?? null;
		$moviePosterUrl  = $uploadFolderUrl . $moviePosterName . '?v=' . time();

		if( isset($_GET['action']) && $_GET['action'] == 'edit_movie'/* && isset($_GET['type']) && $_GET['type'] != 'new'*/) {
			$movieFolder    = CPTStorageRemoteFolderLocation . 'm' . $movieId . '/';
			$moviePosterUrl = CPTStorageURL . $movieFolder . $movieDetails['poster_filename'] . '?v=' . time();
		}

		$location = admin_url('admin.php/?page=' . urlencode($objTektonicCreatePosterThumb->plugin_path . '/movies.php'));
		?>
		<form name="add_watermark_text" id="add_watermark_text" method="POST" onsubmit="<?php echo esc_js('createMoviePoster.addWatermark( event, this )'); ?>">
			<?php wp_nonce_field( 'add_watermark', 'add_watermark_nonce' ); ?>
			<input type="hidden" name="action" value="add_watermark" />
			<input type="hidden" name="movie_slug" value="<?php echo esc_html($movieSlug); ?>" />
			<input type="hidden" name="watermark_coord" id="watermark_coord" />
			<table>
				<tr>
					<td class="first-col"><label for="watermark_text"><strong><?php _e('Title overlay text'); ?></strong></label></td>
					<td class="second-col">:&nbsp;<input type="text" name="watermark_text" id="watermark_text" placeholder="Overlay Text here" value="<?php echo esc_html(stripslashes($movieDetails['title'])); ?>" onkeyup="<?php echo esc_js('createMoviePoster.setDraggableWatermarkValue();'); ?>" /></td>
				</tr>
				<tr>
					<td class="first-col"><label for="watermark_color"><strong><?php _e('Choose a color for the overlay text'); ?></strong></label></td>
					<td class="second-col">:&nbsp;<input type="color" name="watermark_color" id="watermark_color" value="#ffffff" onchange="<?php echo esc_js('createMoviePoster.setDraggableWatermarkValue();'); ?>" /></td>
				</tr>
				<tr>
					<td class="first-col"><label for="watermark_font_size"><strong><?php _e('Select font-size for the overlay text'); ?></strong></label></td>
					<td class="second-col">:&nbsp;<input type="number" name="watermark_font_size" id="watermark_font_size" min="1" max="500" value="35" onkeyup="<?php echo esc_js('createMoviePoster.setDraggableWatermarkValue();'); ?>" onmouseup="<?php echo esc_js('createMoviePoster.setDraggableWatermarkValue();'); ?>" /></td>
				</tr>
				<tr>
					<td class="first-col"><label for="watermark_font_family"><strong><?php _e('Select font-family for the overlay text'); ?></strong></label></td>
					<td class="second-col">:&nbsp;<select name="watermark_font_family" id="watermark_font_family" onchange="<?php echo esc_js('createMoviePoster.setFontFace(this);'); ?>" >
							<?php
							if( !empty($arrFonts) ) {
								foreach( $arrFonts as $font ) {
									$fontName = str_replace('.ttf', '', $font);
									?>
									<option style="font-family: <?php echo $fontName; ?>;" value="<?php echo $fontName; ?>"><?php echo ucwords($fontName); ?></option>
									<?php
								}
							}
							?>
						</select>
						<label id="add_font_toggler"><a href="#" title="<?php _e('Click here to add a new font'); ?>"><?php _e('Add Font (only .ttf allowed)'); ?></a></label>
					</td>
				</tr>
				<tr>
					<td class="first-col"><label for="watermark_center_horizontally"><strong><?php _e('Centre text horizontally (defaults on)'); ?></strong></label></td>
					<td class="second-col">:&nbsp;<input type="checkbox" name="watermark_center_horizontally" id="watermark_center_horizontally" value="1" onchange="<?php echo esc_js('createMoviePoster.setDraggableWatermarkValue();'); ?>" checked /></td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td colspan="2" style="position: relative;"><div id="draggable_watermark" style="width: 1px; position: absolute; cursor: move; white-space: nowrap;"><span></span></div><img src="<?php echo esc_url($moviePosterUrl); ?>" alt="" id="movie_poster" data-pname="<?php echo esc_attr($moviePosterName); ?>" data-mid="<?php echo absint($movieId); ?>" id="movie_poster" /></td>
				</tr>
				<tr>
					<td colspan="2"><?php
					submit_button( __( 'Add Overlay Text' ), 'primary', 'add-watermark', false, array( 'id' => 'addwatermark_btn' ) );
					?><a href="<?php echo esc_url($generatePosterUrl); ?>"><button type="button" name="back_to_edit_movie" id="back_to_edit_movie" class="button" style="margin-left: 10px;"><?php _e('Back'); ?></button></a></td>
				</tr>
			</table>
		</form>
		<form name="upload_font_form" id="upload_font_form" method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" enctype="multipart/form-data" style="display: none;">
			<input type="hidden" name="action" value="upload_font">
			<table>
				<tr>
					<td class="first-col">
						<strong><?php _e('Upload Font'); ?></strong>
					</td>
					<td class="second-col">
						:&nbsp;<input type="file" name="upload_font" id="upload_font" />
					</td>
				</tr>
				<tr>
					<td colspan="2"><?php
					submit_button( __( 'Upload Font' ), 'primary', 'upload-font', false, array( 'id' => 'uploadfont_btn' ) );
					submit_button( __( 'Cancel' ), 'secondary', 'cancel-upload-font', false, array( 'id' => 'cancelfont_btn', 'style' => 'margin-left: 10px;', 'onclick' => 'history.go(-1)'));
					?></td>
				</tr>
			</table>
		</form>
		<div id="movie_poster_preview" style="border: 1px solid #000; width: 640px; height: 360px; margin-top: 50px; text-align: center;">
			<span><?php _e('This is where your poster preview will appear after you have added overlay.'); ?></span>
		</div>
		<div style="margin-top: 10px;"><?php
			submit_button( __( 'Save Poster' ), 'primary', 'save-poster', false, array( 'id' => 'saveNewPoster_btn', 'onclick' => esc_js('createMoviePoster.saveNewPoster()')) );
			submit_button( __( 'Discard Poster' ), 'secondary', 'discard-poster', false, array( 'id' => 'discardNewPoster_btn', 'style' => 'margin-left: 10px;', 'onclick' => esc_js('createMoviePoster.discardNewPoster()')) );
		?></div>
		<?php
	}
	?>
</div>
