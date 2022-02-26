<?php
/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
$adminPath = ABSPATH . 'wp-admin/';
$adminUrl  = admin_url();

require_once( $adminPath . 'admin.php' );

if ( is_multisite() ) {
	if ( ! current_user_can( 'create_users' ) && ! current_user_can( 'promote_users' ) ) {
		wp_die(
			'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
			'<p>' . __( 'Sorry, you are not allowed to add users to this network.' ) . '</p>',
			403
		);
	}
} elseif ( ! current_user_can( 'create_users' ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to create users.' ) . '</p>',
		403
	);
}

require_once( $adminPath . 'admin-header.php' );

global $objTektonicCreatePosterThumb;

$pluginPath = $objTektonicCreatePosterThumb->plugin_path;

if( isset($_GET['p']) && $_GET['p'] != null ) {
	/**
	 * Add overlay to the poster file code
	 */

	global $objTektonicCreatePosterThumb;

	$moviePosterName = $moviePosterUrl = $moviePosterPath = $posterName = null;
	$movieDetails = $objTektonicCreatePosterThumb->getMovieDetailsBySlug( $_GET['p'] );

	$uploadDir       = wp_upload_dir();
	$baseUrl         = $uploadDir['baseurl'];
	$baseDir         = $uploadDir['basedir'];

	include( $pluginPath . '/template-parts/add-watermark.php' );
} else if( isset($_GET['m']) && $_GET['m'] != null ) {
	/**
	 * Generate jpg poster file code
	 */

	global $objTektonicCreatePosterThumb;

	$moveDetails = $objTektonicCreatePosterThumb->getMovieDetailsBySlug( esc_html($_GET['m']) );

	$movieId = $moveDetails['id'] ?? 0;
	$movieId = absint( $movieId );

	$uploadDir       = wp_upload_dir();
	$baseUrl         = $uploadDir['baseurl'];
	$baseDir         = $uploadDir['basedir'];
	$movieFolder     = 'm' . $movieId . '/';
	$uploadFolderUrl = $baseUrl . '/movies/'. $movieFolder;

	$movieUrl = $uploadFolderUrl . $moveDetails['movie_filename'];

	if( isset($_GET['action']) && $_GET['action'] == 'edit_movie' ) {
		$movieFolder = CPTStorageRemoteFolderLocation . 'm' . $movieId . '/';
		$movieUrl    = CPTStorageURL . $movieFolder . $moveDetails['movie_filename'];
	}

	if( isset($_GET['file']) && absint($_GET['file']) ) {
		$uploadDir       = wp_upload_dir();
		$baseUrl         = $uploadDir['baseurl'];
		$baseDir         = $uploadDir['basedir'];
		$movieFolder     = 'm' . $movieId . '/';
		$uploadFolderUrl = $baseUrl . '/movies/'. $movieFolder;

		$movieUrl = $uploadFolderUrl . $moveDetails['movie_filename'];
	}
	
	include( $pluginPath . '/template-parts/generate-poster.php' );
} else {
	/**
	 * Upload mp4 movie file code
	 */

	/** Initialize variables **/
	$arrMovieDetails = $arrMovieType = [];
	$smtC = $smtCF = $smtFF = $smtFFF = $sfgF = $sfgO = $sfgM = false;

	/** Manipulate the action variable **/
	$action = isset($_GET['action']) ? esc_html($_GET['action']) : 'upload_movie';

	/** Manipulate the title variable **/
	$title = str_replace('_', ' ', $action);
	$title = ucwords($title);

	/** Manipulate the description and show file fields flag variable **/
	$desc = __('Upload a movie here to add it to this site.');
	$showMovieFileFields = true;

	if($action == 'edit_movie') {
		$desc = __('Edit the movie here to make the changes.');
		// $showMovieFileFields = false;
	}

	/** Get movie detials by id **/
	$movieId = $_GET['id'] ?? 0;

	if(absint($movieId) > 0) {
		$arrMovieDetails = getMovieDetailsById($movieId);
	}

	/** Assign the values to the variables **/
	$movieTitle          = $arrMovieDetails['title'] ?? null;
	$uploadedBy          = $arrMovieDetails['uploaded_by'] ?? -1;
	$movieCategory       = $arrMovieDetails['category'] ?? -1;
	$movieType           = $arrMovieDetails['movie_type'] ?? null;
	$displayDate         = $arrMovieDetails['display_date'] ?? null;
	$directorName        = $arrMovieDetails['director_name'] ?? null;
	$crewDetails         = $arrMovieDetails['crew_details'] ?? null;
	$producerName        = $arrMovieDetails['producer_full_name'] ?? null;
	$writerName          = $arrMovieDetails['writer_full_name'] ?? null;
	$cast                = $arrMovieDetails['cast'] ?? null;
	$technicalNotes      = $arrMovieDetails['technical_notes'] ?? null;
	$synopsis            = $arrMovieDetails['synopsis'] ?? null;
	$editorName          = $arrMovieDetails['editor_full_name'] ?? null;
	$music               = $arrMovieDetails['music'] ?? null;
	$cinematographerName = $arrMovieDetails['cinematographer_full_name'] ?? null;
	$productionDesigner  = $arrMovieDetails['production_designer_full_name'] ?? null;
	$productionCompany   = $arrMovieDetails['production_company'] ?? null;
	$festivals           = $arrMovieDetails['festivals'] ?? null;
	$movieSortOrder      = $arrMovieDetails['sort_order'] ?? 0;
	$movieSlug 			 = $arrMovieDetails['slug'] ?? null;
	$arrFestivals        = explode(',', $festivals);

	if($movieType != null) {
		$arrMovieType = explode(',', $movieType);

		in_array('cardora', $arrMovieType) ? $smtC = true : $smtC = false ;

		in_array('cardora_featured', $arrMovieType) ? $smtCF = true : $smtCF = false ;

		in_array('film_festival', $arrMovieType) ? $smtFF = true : $smtFF = false ;

		in_array('film_festival_featured', $arrMovieType) ? $smtFFF = true : $smtFFF = false ;
	}
	
	include( $pluginPath . '/template-parts/upload-movie-form.php' );
}
