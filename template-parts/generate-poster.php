<?php
$movieSlug = $_GET['m'] ?? null;

if( $movieSlug == null ) {
	die( _e('The movie slug is missing from the URL!') );
}

$movieSlug = esc_html($movieSlug);

$adminUrl   = $adminUrl . 'admin.php';
$page       = $_GET['page'] ?? null;
$action     = $_GET['action'] ?? null;
$formAction = 'new';

$uploadMovieUrl  = $adminUrl . '?page=' . urlencode($page);
$editMovieUrl    = $adminUrl . '?page=' . urlencode($page) . '&action=edit_movie&id=' . absint($movieId);
$addWatermarkUrl = $adminUrl . '?page=' . urlencode($page) . '&p=' . $movieSlug;

$backUrl = $uploadMovieUrl;

if( $action == 'edit_movie' ) {
	$formAction = null;
	$backUrl = $editMovieUrl;
	$addWatermarkUrl .= '&action=edit_movie';
}

if( isset($_GET['file']) && absint($_GET['file']) == 1 ) {
	$formAction = 'new';
}
?>
<div class="wrap">
	<h1 id="add-new-user"><?php _e('Move the playhead to select the frame for the poster'); ?></h1>
	<div id="ajax-response"></div>
	<video width="640" height="360" controls id="movie" onseeked="<?php echo esc_js('createMoviePoster.getVideoSeekTime()'); ?>">
	  	<source src="<?php echo esc_url($movieUrl); ?>" type="video/mp4">
		<?php _e('Your browser does not support the video tag.'); ?>
	</video>
	<form method="post" name="generate_poster_form" id="generate_poster_form" class="validate" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="<?php echo esc_attr('generate_movie_poster'); ?>" />
		<input type="hidden" name="movie_id" value="<?php echo absint($movieId); ?>" />
		<input type="hidden" name="seek_time" id="<?php echo esc_attr('seek_time'); ?>" />
		<input type="hidden" name="movie_slug" id="movie_slug" value="<?php echo esc_attr($_GET['m']); ?>" />
		<input type="hidden" name="movie_url" id="movie_url" value="<?php echo esc_url($movieUrl); ?>" />
		<input type="hidden" name="form_action" id="form_action" value="<?php echo esc_attr($formAction); ?>" />
		<?php
		wp_nonce_field( 'generate-movie-poster', '_wpnonce_generate-movie-poster' );
		submit_button( __( 'Generate New Poster' ), 'primary', 'generate-movie-poster', false, array( 'id' => 'generatemovieposter_btn' ) );
		?>
		<a href="<?php echo esc_url($backUrl); ?>"><button type="button" name="back_to_edit_movie" id="back_to_edit_movie" class="button" style="margin-left: 10px;"><?php _e('Back'); ?></button></a>
		<!-- <a href="<?php echo esc_url($addWatermarkUrl); ?>"><button type="button" name="skip_generate_poster" id="skip_generate_poster" class="button" style="margin-left: 10px;"><?php _e('Edit an existing poster'); ?></button></a>-->
	</form>
</div>
