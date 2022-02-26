<?php
/**
 * Plugin Name:   Create Posters & thumb
 * Description:   This plugin generates the posters and thumb for the video uploaded as a custom post
 * Author:        Tektonic Solutions
 * Author URI:    http://tektonic.solutions/
 * Developer:     Tektonic Solutions
 * Developer URI: http://tektonic.solutions/
 * Text Domain:   tektonic-create-poster-thumb
 * Domain Path:   /languages/
 * Network:       false
 * Slug:          tektonic-create-poster-thumb
 * Version:       1.0.0
 *
 * Copyright (C) 2019  Tektonic Solutions (http://tektonic.solutions/)
 *
 * @package    tektonic-create-poster-thumb
 * @subpackage Main
 * @author     Sagar Gurnani
 * @version    1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) or die;

require plugin_dir_path( __FILE__ ) . '/../speakez-video-recorder/vendor/autoload.php';
/*use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;*/
use phpseclib\Net\SSH2;
use phpseclib\Net\SFTP;

define( 'CPTStoragePluginPath', plugin_dir_path( __FILE__ ) );
define( 'CPTStoragePluginUrl', plugins_url( null, __FILE__ ) );

/**
 * Juno E3 - related contants
 */
define( 'CPTStorageURL', 'https://e3.extrabrain.net/E3/D001-1TB-20200710/E3-CARDORA-TEST/' );
define( 'CPTStoragePath', '/E3/D001-1TB-20200710/E3-CARDORA-TEST/' );
define( 'CPTStorageHostname', 'e3.extrabrain.net' );
define( 'CPTStorageUsername', 'e3-juno' );
define( 'CPTStoragePassword', 'e3' );
define( 'CPTStoragePort', 21022 );
define( 'CPTStorageRemoteFolderLocation', 'videos/films/' );

/*
if( defined('AWS_KEY') ) {
	define( 'AWS_KEY', 'AKIAJQ47BXOQOFSP7VGA' );
}

if( defined('AWS_SECRET') ) {
	define( 'AWS_SECRET', '/sDGv2/lxgWW72qSMbR1171Zec0Dc1wvYd8MKE5c' );
}

if( defined('AWS_API_VERSION') ) {
	define( 'AWS_API_VERSION', 'latest' );
}

if( defined('AWS_REGION') ) {
	define( 'AWS_REGION', 'eu-west-2' );
}

if( defined('AWS_BUCKET_NAME') ) {
	define( 'AWS_BUCKET_NAME', 'cardora-staging-server' );
}

if( defined('AWS_MICROMOVIE_URL') ) {
	define( 'AWS_MICROMOVIE_URL', 'https://' . AWS_BUCKET_NAME . '.s3-' . AWS_REGION . '.amazonaws.com/movies/m' );
}

if( defined('AWS_SELFIEPHOTO_URL') ) {
	define( 'AWS_SELFIEPHOTO_URL', 'https://' . AWS_BUCKET_NAME . '.s3-' . AWS_REGION . '.amazonaws.com/photos/' );
}

require plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
*/

if( !class_exists('TektonicCreatePosterThumb') ) {
	class TektonicCreatePosterThumb {
		/**
		 * Class variable to get the site url
		 *
		 * @var  String
		 */
		public $site_url;

		/**
		 * Class variable to get the plugin path
		 *
		 * @var  String
		 */
		public $plugin_path;

		/**
		 * Class variable to get the plugin url
		 *
		 * @var  String
		 */
		public $plugin_url;

		/**
		 * Class variable to get the upload URL
		 *
		 * @var  String
		 */
		public $upload_url;

		/**
		 * Class constructor
		 *
		 * @method  __construct
		 *
		 * @since   1.0.0
		 */
		public function __construct() {
			$upload_dir = wp_upload_dir();

			$this->site_url    = get_site_url();
			$this->plugin_path = CPTStoragePluginPath;
			$this->plugin_url  = CPTStoragePluginUrl;
			$this->upload_url  = $upload_dir['baseurl'];
			$this->upload_Path = $upload_dir['basedir'];

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_post_upload_movie', array( $this, 'upload_movie' ) );
			add_action( 'admin_post_edit_movie', array( $this, 'edit_movie' ) );
			add_action( 'admin_post_generate_movie_poster', array( $this, 'generate_movie_poster') );
			add_action( 'wp_ajax_add_watermark', array($this, 'add_watermark') );
			add_action( 'wp_ajax_discard_poster', array($this, 'discard_poster') );
			add_action( 'wp_ajax_save_poster', array($this, 'save_poster') );
			add_action( 'admin_enqueue_scripts', array($this, 'plugin_enqueue_admin') );
			add_action( 'admin_post_upload_font', array($this, 'uploadFont') );
			add_action( 'admin_notices', array($this, 'admin_notice_error') );
			add_action( 'admin_notices', array($this, 'admin_notice_success') );
			add_action( 'admin_post_delete_movie', array($this, 'delete_movie') );
			add_action( 'admin_post_nopriv_delete_movie', array($this, 'delete_movie') );
			add_action( 'admin_post_activate_movie', array($this, 'activate_movie') );
			add_action( 'admin_post_nopriv_activate_movie', array($this, 'activate_movie') );
			add_action( 'admin_post_deactivate_movie', array($this, 'deactivate_movie') );
			add_action( 'admin_post_nopriv_deactivate_movie', array($this, 'deactivate_movie') );
			add_action( 'admin_post_make_featured', array($this, 'make_featured') );
			add_action( 'admin_post_nopriv_make_featured', array($this, 'make_featured') );
			add_action( 'wp_ajax_sort_movies', array($this, 'sort_movies') );
			add_action( 'wp_ajax_nopriv_sort_movies', array($this, 'sort_movies') );
			add_action( 'wp_ajax_btt_movie', array($this, 'boost_movie_to_top') );
			add_action( 'wp_ajax_nopriv_btt_movie', array($this, 'boost_movie_to_top') );
			add_action( 'admin_post_make_unfeatured', array($this, 'make_unfeatured') );
			add_action( 'admin_post_nopriv_make_unfeatured', array($this, 'make_unfeatured') );
			add_action( 'wp_ajax_reset_movies_sort_order', array($this, 'reset_movies_sort_order') );
			add_action( 'wp_ajax_nopriv_reset_movies_sort_order', array($this, 'reset_movies_sort_order') );
			add_action( 'wp_loaded', array($this, 'remoteServerSSH') );

			add_filter( 'upload_mimes', array($this, 'add_file_types'), 1, 1);
		}

		public function plugin_enqueue_admin( $hook ) {
			$pluginUrl = $this->plugin_url;

			wp_enqueue_script( 'jquery' );
		    wp_enqueue_script( 'jquery-ui-core' );
		    wp_enqueue_script( 'jquery-ui-sortable' );
		    wp_enqueue_script( 'jquery-ui-draggable' );
		    wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'ts-watermark', $pluginUrl . '/js/ts-watermark.js', array('jquery'), time(), true );
			wp_enqueue_script( 'cardora-admin-script', $pluginUrl . '/js/cardora-admin-script.js', array('jquery', 'jquery-ui-core', 'jquery-ui-sortable') );

		    wp_localize_script( 'ts-watermark', 'tsWatermark', array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'site_url'     => get_site_url(),
				't'            => get_current_user_id(),
				'tsfu'         => wp_create_nonce( 'tektonic-create-poster-thumb' ),
				'purl'         => $pluginUrl,
				'current_page' => $_SERVER['REQUEST_URI']
			));
		}
 
 		/**
 		 * Registers the movie menu
 		 *
 		 * @method  register_menu
 		 *
 		 * @since   1.0.0
 		 */
		public function register_menu() {
			$pluginPath = $this->plugin_path;

			add_menu_page( 
		        'Movies',
		        'Movies',
		        'manage_options',
		        $pluginPath . 'movies.php',
		        '',
		        'dashicons-video-alt',
		        70
		    );

		    add_submenu_page(
		        $pluginPath . 'movies.php',	
		        'Upload Movie',
		        'Upload Movie',
		        'manage_options',
		        $pluginPath . 'upload-movie.php',
		        ''
		    );
		}

		/**
		 * Upload the movie to the uplods folder so
		 * that it can be uploaded to cloud later
		 *
		 * @method  upload_movie
		 *
		 * @since   1.0.0
		 */
		public function upload_movie() {
			global $wpdb;

			$movieFilePath = $posterFilePath = $thumbFilePath = null;

			$currentUnixTime        = time();
			$movieId                = absint($_POST['movie_id']);
			$movieTitle             = isset($_REQUEST['movie_title']) ? sanitize_text_field( $_REQUEST['movie_title'] ) : null;
			$movieCategory          = isset($_REQUEST['movie_category']) ? absint( $_REQUEST['movie_category'] ) : 0;
			$uploadedBy             = isset($_REQUEST['uploaded_by']) ? absint($_REQUEST['uploaded_by']) : 0;
			$movieType              = (!empty($_REQUEST['movie_type']) > 0) ? sanitize_text_field(implode(',', $_REQUEST['movie_type'])) : 'cardora' ;
			$arrFestivals           = $_REQUEST['festivals'] ?? [];
			$displayDate            = $_REQUEST['display_date'] ?? date('Y-m-d');		
			$producerName           = isset($_REQUEST['producer_full_name']) ? sanitize_text_field($_REQUEST['producer_full_name']) : null;
			$directorName           = isset($_REQUEST['director_full_name']) ? sanitize_text_field($_REQUEST['director_full_name']) : null;
			$writerName             = isset($_REQUEST['writer_full_name']) ? sanitize_text_field($_REQUEST['writer_full_name']) : null;
			$cinematographerName    = isset($_REQUEST['cinematographer_full_name']) ? sanitize_text_field($_REQUEST['cinematographer_full_name']) : null;
			$productionDesignerName = isset($_REQUEST['production_designer_full_name']) ? sanitize_text_field($_REQUEST['production_designer_full_name']) : null;
			$productionCompany      = isset($_REQUEST['production_company']) ? sanitize_text_field($_REQUEST['production_company']) : null;
			$editorName             = isset($_REQUEST['editor_full_name']) ? sanitize_text_field($_REQUEST['editor_full_name']) : null;
			$music                  = isset($_REQUEST['music']) ? sanitize_text_field($_REQUEST['music']) : null;
			$cast                   = isset($_REQUEST['cast']) ? sanitize_textarea_field($_REQUEST['cast']) : null;
			$crewDetails            = isset($_REQUEST['crew_details']) ? sanitize_textarea_field($_REQUEST['crew_details']) : null;
			$technicalNotes         = isset($_REQUEST['technical_notes']) ? sanitize_textarea_field($_REQUEST['technical_notes']) : null;
			$sortOrder 				= isset($_REQUEST['movie_sort_order']) ? absint($_REQUEST['movie_sort_order']) : 0 ;
			$filmmakerSlug 			= $this->getUserSlugById($uploadedBy);
			$movieSlug              = sanitize_title_with_dashes($movieTitle);
			$synopsis = null;
			
			if($filmmakerSlug != null) {
				$movieSlug = $movieSlug . '-by-' . $filmmakerSlug;
			} else {
				$movieSlug = $movieSlug;
			}

			$movieSlug = $movieSlugRaw = $this->parseMovieSlug($movieSlug);

			if(isset($_FILES['upload_movie']['name'])) {
				$posterFileExt  = $thumbFileExt = 'jpg';
				$filePathInfo   = pathinfo($_FILES['upload_movie']['name']);
				$videoFileExt   = strtolower($filePathInfo['extension']) ?? null;
				$movieFilePath  = $movieSlugRaw . '.' . $videoFileExt;
				$posterFilePath = $movieSlugRaw . '_poster.' . $posterFileExt;
				$thumbFilePath  = $movieSlugRaw . '_thumb.' . $thumbFileExt;
			}

			if($movieTitle == null or $movieCategory == 0 or $_FILES['upload_movie']['error'] != 0) {
				update_option('error_notice', 2);
			} else {
				if((in_array('cardora_featured', $_REQUEST['movie_type']) === true
					&& in_array('cardora', $_REQUEST['movie_type']) === false)
					|| (in_array('film_festival_featured', $_REQUEST['movie_type']) === true
					&& in_array('film_festival', $_REQUEST['movie_type']) === false)
				) {
					update_option('error_notice', 2);
				} else {
					try {
						$wpdb->insert(
							$wpdb->prefix . 'movies',
							array(
								'title'              => $movieTitle,
								'category'           => $movieCategory,
								'movie_filename'     => $movieFilePath,
								'poster_filename'    => $posterFilePath,
								'thumbnail_filename' => $thumbFilePath,
								'status'             => 1,
								'uploaded_by'        => $uploadedBy,
								'created_on'         => date('Y-m-d H:i:s', $currentUnixTime),
								'modified_on'        => date('Y-m-d H:i:s', $currentUnixTime),
								'movie_type'         => $movieType,
								'display_date'       => date('Y-m-d H:i:s', strtotime($displayDate)),
								'sort_order'         => $sortOrder,
								'slug'               => $movieSlug
							)
						);
					} catch (Exception $e) {
						echo $e->getMessage(); 
						die;
					} 
					
					$lastInsertId = $wpdb->insert_id;

					if($lastInsertId > 0) {
						$uploadDir = wp_upload_dir();
						$baseDir   = $uploadDir['basedir'];
						$parentDir = $baseDir . '/movies/m' . $lastInsertId . '/';

						if( !file_exists($parentDir) ) {
							mkdir($parentDir, 0777);
						}

						if($_FILES['upload_movie']['error'] == 0) {
							move_uploaded_file($_FILES['upload_movie']['tmp_name'], $parentDir . $movieFilePath);
						}

						$wpdb->insert(
							$wpdb->prefix . 'movie_details',
							array(
								'movie_id'						=> $lastInsertId,
								'producer_full_name'            => $producerName,
								'director_full_name'            => $directorName,
								'writer_full_name'              => $writerName,
								'cinematographer_full_name'     => $cinematographerName,
								'production_designer_full_name' => $productionDesignerName,
								'production_company'            => $productionCompany,
								'editor_full_name'              => $editorName,
								'music'                         => $music,
								'cast'                          => $cast,
								'crew_details'                  => $crewDetails,
								'technical_notes'               => $technicalNotes,
								'synopsis'                      => $synopsis
							)
						);

						if(!empty($arrFestivals)) {
							foreach($arrFestivals as $festivalId) {
								$wpdb->insert(
									$wpdb->prefix . 'mf_mapping',
									array(
										'movie_id'    => $lastInsertId,
										'festival_id' => $festivalId
									)
								);
							}
						}

						update_option('error_notice', 1);
					} else {
						update_option('error_notice', 2);
					}
				}
			}

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/upload-movie.php') . '&m=' . $movieSlug;

			wp_redirect($redirectUrl);
			die;
		}

		public function edit_movie() {
			if(isset($_REQUEST['movie_id']) && intval($_REQUEST['movie_id']) > 0) {
				global $wpdb;

				$currentUnixTime        = time();
				$movieId                = absint($_REQUEST['movie_id']);
				$movieTitle             = isset($_REQUEST['movie_title']) ? sanitize_text_field( $_REQUEST['movie_title'] ) : null;
				$movieCategory          = isset($_REQUEST['movie_category']) ? absint( $_REQUEST['movie_category'] ) : 0;
				$uploadedBy             = isset($_REQUEST['uploaded_by']) ? absint($_REQUEST['uploaded_by']) : null;
				$movieType              = (!empty($_REQUEST['movie_type']) > 0) ? sanitize_text_field(implode(',', $_REQUEST['movie_type'])) : 'cardora' ;
				$arrFestivals           = $_REQUEST['festivals'] ?? [];
				$displayDate            = $_REQUEST['display_date'] ?? date('Y-m-d');		
				$producerName           = isset($_REQUEST['producer_full_name']) ? sanitize_text_field($_REQUEST['producer_full_name']) : null;
				$directorName           = isset($_REQUEST['director_full_name']) ? sanitize_text_field($_REQUEST['director_full_name']) : null;
				$writerName             = isset($_REQUEST['writer_full_name']) ? sanitize_text_field($_REQUEST['writer_full_name']) : null;
				$cinematographerName    = isset($_REQUEST['cinematographer_full_name']) ? sanitize_text_field($_REQUEST['cinematographer_full_name']) : null;
				$productionDesignerName = isset($_REQUEST['production_designer_full_name']) ? sanitize_text_field($_REQUEST['production_designer_full_name']) : null;
				$productionCompany      = isset($_REQUEST['production_company']) ? sanitize_text_field($_REQUEST['production_company']) : null;
				$editorName             = isset($_REQUEST['editor_full_name']) ? sanitize_text_field($_REQUEST['editor_full_name']) : null;
				$music                  = isset($_REQUEST['music']) ? sanitize_text_field($_REQUEST['music']) : null;
				$cast                   = isset($_REQUEST['cast']) ? sanitize_textarea_field($_REQUEST['cast']) : null;
				$crewDetails            = isset($_REQUEST['crew_details']) ? sanitize_textarea_field($_REQUEST['crew_details']) : null;
				$technicalNotes         = isset($_REQUEST['technical_notes']) ? sanitize_textarea_field($_REQUEST['technical_notes']) : null;
				$synopsis               = isset($_REQUEST['synopsis']) ? sanitize_textarea_field($_REQUEST['synopsis']) : null ;
				$sortOrder 				= isset($_REQUEST['movie_sort_order']) ? absint($_REQUEST['movie_sort_order']) : 0 ;
				$movieSlug              = $movieSlugRaw = isset($_REQUEST['movie_slug']) ? $_REQUEST['movie_slug'] : null; # Old slug
				$filmmakerSlug 			= getUserSlugById($uploadedBy);

				if($filmmakerSlug != null) {
					$newMovieSlug = sanitize_title_with_dashes($movieTitle) . '-by-' . $filmmakerSlug;
				} else {
					$newMovieSlug = sanitize_title_with_dashes($movieTitle);
				}

				if($newMovieSlug != $movieSlug) {
					$movieSlug = $movieSlugRaw = parseMovieSlug($newMovieSlug, $movieId);
				}

				if(isset($_FILES['upload_movie']['name'])) {
					$posterFileExt  = $thumbFileExt = 'jpg';
					$filePathInfo   = pathinfo($_FILES['upload_movie']['name']);
					$videoFileExt   = isset($filePathInfo['extension']) ? strtolower($filePathInfo['extension']) : 'mp4' ;
					$movieFilePath  = $movieSlugRaw . '.' . $videoFileExt;
					$posterFilePath = $movieSlugRaw . '_poster.' . $posterFileExt;
					$thumbFilePath  = $movieSlugRaw . '_thumb.' . $thumbFileExt;
				}

				$fileUrlSegment = null;

				if($movieTitle == null or $movieCategory == 0) {
					update_option('error_notice', 2);
				} else {
					if((in_array('cardora_featured', $_REQUEST['movie_type']) === true
						&& in_array('cardora', $_REQUEST['movie_type']) === false)
						|| (in_array('film_festival_featured', $_REQUEST['movie_type']) === true
						&& in_array('film_festival', $_REQUEST['movie_type']) === false)
					) {
						update_option('error_notice', 2);
					} else {
						// $arrMovieDetailsBySlug = $this->getMovieDetailsBySlug( $movieSlug );
						$arrMovieDetailsById = $this->getMovieDetailsById( $movieId );

						/**
						 * Rename the assoicated files on the remote server
						 */
						if( !empty($arrMovieDetailsById) ) {
							$movieFileOldName   = $arrMovieDetailsById['movie_filename'];
							$moviePosterOldName = $arrMovieDetailsById['poster_filename'];

							$movieFileNewName   = $movieSlug . '.mp4';
							$moviePosterNewName = $movieSlug . '.jpg';

							/**
							 * Rename remote files
							 *
							 * @var  Boolean
							 */
							$this->rename_remote_files(
								$movieId,
								array(
									$movieFileOldName   => $movieFileNewName,
									$moviePosterOldName => $moviePosterNewName
								)
							);
						}					

						/**
						 * Check is there are no errors in file upload
						 */
						if($_FILES['upload_movie']['error'] == 0) {
							$uploadDir = wp_upload_dir();
							$baseDir   = $uploadDir['basedir'];
							$parentDir = $baseDir . '/movies/m' . $movieId . '/';
							$movieFileOldName = $moviePosterOldName = $movieThumbOldName = $movieFileOldPath = $moviePosterOldPath = $movieFileNewPath = $moviePosterNewPath = null;

							/**
							 * Create the parent folder if it does not exist
							 * which might have been deleted when the files have
							 * been uploaded to the remote server
							 */
							if( !file_exists($parentDir) ) {
								mkdir($parentDir, 0777);
							}

							/**
							 * Rename the associated files on the local server
							 * if a new file is uploaded
							 */
							if( !empty($arrMovieDetailsById) ) {
								$movieFileOldName   = $arrMovieDetailsById['movie_filename'];
								$moviePosterOldName = $arrMovieDetailsById['poster_filename'];
								$movieThumbOldName  = $arrMovieDetailsById['thumbnail_filename'];

								$movieFileOldPath   = $parentDir . $movieFileOldName;
								$moviePosterOldPath = $parentDir . $moviePosterOldName;
								$movieFileNewPath   = $parentDir . $movieFileNewName;
								$moviePosterNewPath = $parentDir . $moviePosterNewName;

								if( file_exists($movieFileOldPath) ) {
									$filePathMovie = (int) rename($movieFileOldPath, $movieFileNewPath);
								}

								if( file_exists($moviePosterOldPath) ) {
									$filePathPoster = (int) rename($moviePosterOldPath, $moviePosterNewPath);
								}
							}

							move_uploaded_file($_FILES['upload_movie']['tmp_name'], $movieFileNewPath);

							$fileUrlSegment = '&file=1';
						}

						$updateMovie = $wpdb->update(
							$wpdb->prefix . 'movies',
							array(
								'title'              => $movieTitle,
								'category'           => $movieCategory,
								'uploaded_by'        => $uploadedBy,
								'modified_on'        => date('Y-m-d H:i:s', $currentUnixTime),
								'movie_type'         => $movieType,
								'display_date'       => date('Y-m-d H:i:s', strtotime($_REQUEST['display_date'])),
								'sort_order'         => $sortOrder,
								'slug'               => $movieSlug,
								'movie_filename'     => $movieFileNewName,
								'poster_filename'    => $moviePosterNewName,
								'thumbnail_filename' => $movieThumbNewName
							),
							array(
								'id' => $movieId
							),
							array(
								'%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
							),
							array(
								'%d'
							)
						);

						if(false === $updateMovie) {
							update_option('error_notice', 2);
						} else {
							$updateMovieDetails = $wpdb->update(
								$wpdb->prefix . 'movie_details',
								array(
									'producer_full_name'            => $producerName,
									'director_full_name'            => $directorName,
									'writer_full_name'              => $writerName,
									'cinematographer_full_name'     => $cinematographerName,
									'production_designer_full_name' => $productionDesignerName,
									'production_company'            => $productionCompany,
									'editor_full_name'              => $editorName,
									'music'                         => $music,
									'cast'                          => $cast,
									'crew_details'                  => $crewDetails,
									'technical_notes'               => $technicalNotes,
									'synopsis'                      => $synopsis
								),
								array(
									'movie_id' => $movieId
								),
								array(
									'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
								),
								array(
									'%d'
								)
							);

							if(!empty($arrFestivals)) {
								$deleteFestivalId = $wpdb->delete(
									$wpdb->prefix . 'mf_mapping',
									array( 'movie_id' => $movieId ),
									array( '%d' )
								);

								foreach($arrFestivals as $festivalId) {
									$wpdb->insert(
										$wpdb->prefix . 'mf_mapping',
										array(
											'movie_id'    => $movieId,
											'festival_id' => $festivalId
										),
										array(
											'%d', '%d'
										)
									);
								}
							}

							update_option('error_notice', 1);
						}
					}
				}
			} else {
				update_option('error_notice', 2);
			}

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/upload-movie.php') . '&m=' . $movieSlug . '&action=edit_movie' . $fileUrlSegment;

			if( isset($_POST['uploadmovieandfinish']) ) {
				$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php') . '&orderby=movie_id&order=desc';
			}

			wp_redirect($redirectUrl);
			die;
		}

		/**
		 * Rename the files on the remote server
		 *
		 * @method  rename_remote_files
		 *
		 * @param   Integer               $movieId
		 * @param   String                $files
		 * @param   String                $remoteFolder
		 *
		 * @return  Boolean
		 *
		 * @since   1.0.0
		 */
		public function rename_remote_files( $movieId, $files, $remoteFolder = CPTStorageRemoteFolderLocation ) {
			/**
			 * Initialize the output
			 *
			 * @var  boolean
			 */
			$output = false;

			/**
			 * Connect to the remote server
			 */
			$sftp = $this->remoteServerSFTP();

		    /**
		     * Set the new folder name and the remote
		     * file page
		     *
		     * @var  string
		     */
			$newFolderName    = 'm' . $movieId;
			$remoteFolderPath = CPTStoragePath . $remoteFolder . $newFolderName;

			/**
			 * Don't do anything if the files array is empty 
			 */
			if( !empty($files) ) {
				/**
				 * Loop through all the files in the folder
				 * on the remote server
				 */
				foreach( $files as $fileOldName => $fileNewName ) {
					/**
					 * Set the Old and New file paths
					 *
					 * @var  String
					 */
					$remoteFilePathOld = $remoteFolderPath . '/' . $fileOldName;
					$remoteFilePathNew = $remoteFolderPath . '/' . $fileNewName;

					/**
					 * Get the size of the files
					 *
					 * @var  Integer
					 */
					$remoteFileSize = (int) $sftp->size( $remoteFilePathOld );

					/**
					 * Check whether the files on the remote server exists
					 */
					if( $remoteFileSize > 0 ) {
						/**
						 * Rename the file according to the new movie slug
						 */
				    	$sftp->rename( $remoteFilePathOld, $remoteFilePathNew );

				    	/**
				    	 * Set the output as true when the file is renamed successfully
				    	 *
				    	 * @var  boolean
				    	 */
				    	$output = true;
				    }
				}
			}

		    return $output;
		}

		/*public function move_to_cloud() {
			if( isset($_POST['movie_slug']) && $_POST['movie_slug'] != null ) {
				$movieDetails = getMovieDetailsBySlug( $_POST['movie_slug'] );
				$movieId      = $movieDetails['id'];

				$uploadDir       = wp_upload_dir();
				$baseUrl         = $uploadDir['baseurl'];
				$baseDir         = $uploadDir['basedir'];
				$uploadFolderDir = $baseDir . '/movies/m' . $movieId . '/';

				if( !empty( $movieDetails['movie_filename'] ) ) {
					move_uploaded_file_to_awss3( $movieDetails['movie_filename'], $uploadFolderDir, $movieId );
				}

				if( !empty( $movieDetails['poster_filename'] ) ) {
					move_uploaded_file_to_awss3( $movieDetails['poster_filename'], $uploadFolderDir, $movieId );
				}
			}
		}*/

		/**
		 * Callback for generating the movie poster action hook
		 *
		 * @method  generate_movie_poster
		 *
		 * @since   [build_version]
		 */
		public function generate_movie_poster() {
			/**
			 * Get the plugin directory path
			 */
			$pluginPath  = $this->plugin_path;

			$movieId = $_POST['movie_id'] ?? 0;
			$movieId = absint( $movieId );

			$movieUrl = $_POST['movie_url'] ?? null;
			$movieUrl = $movieUrl;

			$movieSlug = $_POST['movie_slug'] ?? null;
			$movieSlug = sanitize_title_with_dashes($movieSlug);

			$seekTime = $_POST['seek_time'] ?? null;

			$formAction = isset($_POST['form_action']) && $_POST['form_action'] != null ? '&action=' . sanitize_text_field( $_POST['form_action'] ) . '&type=new' : null;

			if( $seekTime == null ) {
				update_option('error_notice', 4);

				$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/upload-movie.php') . '&m=' . $movieSlug . $formAction;
				wp_redirect($redirectUrl);
				die;
			}			

			/**
			 * Generate the poster out of the uploaded video
			 */
			$posterName = $this->generatePoster( $movieId, $movieUrl, $seekTime, $formAction );

			if( false === $posterName ) {
				$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/upload-movie.php') . '&m=' . $movieSlug . $formAction;
			} else {
				$this->update_movie_data(
					array('poster_filename' => $posterName),
					array('id' => $movieId)
				);

				$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/upload-movie.php') . '&p=' . $movieSlug . $formAction;
			}

			wp_safe_redirect($redirectUrl);
			die;
		}

		/**
		 * Generate poster for the uploaded movie using FFMPEG
		 *
		 * @method  generatePoster
		 *
		 * @param   Integer          $movieId
		 * @param   String           $movieUrl
		 * @param   String           $seekTime
		 * @param   String|Null      $formAction
		 *
		 * @return  Mixed
		 *
		 * @since   [build_version]
		 */
		public function generatePoster( Int $movieId, String $movieUrl, String $seekTime, $formAction = null ) {
			$uploadDir       = wp_upload_dir();
			$baseUrl         = $uploadDir['baseurl'];
			$baseDir         = $uploadDir['basedir'];
			$uploadFolderPath = $baseDir . '/movies/m' . $movieId . '/';

			if( !file_exists($uploadFolderPath) ) {
				shell_exec( 'mkdir ' . $uploadFolderPath );
			}

			/**
			 * For windows machine replace the backslash in the path
			 * with forward slash
			 *
			 * @var  String
			 */
			$uploadFolderPath = str_replace('\\', '/', $uploadFolderPath);

			$splitMovieUrl  = explode('/', $movieUrl);
			$movieName      = array_pop($splitMovieUrl);
			$posterName     = str_replace('.mp4', '.jpg', $movieName);
			$posterFilePath = $uploadFolderPath . $posterName;

			if( $formAction == null ) {
				$movieFolder      = CPTStorageRemoteFolderLocation . 'm' . $movieId . '/';
				$uploadFolderPath = CPTStorageURL . $movieFolder;
			}

			$movieFilePath  = $uploadFolderPath . $movieName;

			try {
				$cmd = shell_exec( 'ffmpeg -y -ss ' . $seekTime . ' -i ' . $movieFilePath . ' -s 640x360 -vframes 1 -q:v 2 ' . $posterFilePath );

				return $posterName;
			} catch( Exception $e ) {
				echo $e->getMessage(); die;
			}
		}

		/**
		 * Update the movie data in the movie table
		 *
		 * @method  update_movie_data
		 *
		 * @param   Array              $data
		 * @param   Array              $where
		 *
		 * @return  Mixed
		 *
		 * @since   [build_version]
		 */
		public function update_movie_data( $data, $where ) {
			global $wpdb;

			$movieTableName = $wpdb->prefix . 'movies';

			return $wpdb->update(
				$movieTableName,
				$data,
				$where
			);
		}

		/**
		 * Callback for add watermark action hook
		 *
		 * @method  add_watermark
		 *
		 * @since   [build_version]
		 */
		public function add_watermark() {
			$formData  = $_POST;
			$movieSlug = $formData['movie_slug'];

			$movieData = $this->getMovieDetailsBySlug( $movieSlug );
			$movieId   = absint( $movieData['id'] );

			$uploadDir        = wp_upload_dir();
			$baseUrl          = $uploadDir['baseurl'];
			$baseDir          = $uploadDir['basedir'];
			$uploadFolderPath = $baseDir . '/movies/m' . $movieId . '/';
			$uploadFolderUrl  = $baseUrl . '/movies/m' . $movieId . '/';
			$moviePosterName  = $movieData['poster_filename'];
			$moviePosterPath  = $uploadFolderPath . $moviePosterName;
			$moviePosterUrl   = $uploadFolderUrl . $moviePosterName;

			$watermarkText       = sanitize_text_field($formData['watermark_text']);
			$watermarkColor      = sanitize_text_field($formData['watermark_color']);
			$watermarkFontSize   = absint( $formData['watermark_font_size'] );
			$watermarkFontFamily = sanitize_text_field( $formData['watermark_font_family'] );
			$watermarkTextCoords = sanitize_text_field( $formData['watermark_coord'] );

			$arrWatermarkTextCoords = explode('-', $watermarkTextCoords);

			list($r, $g, $b) = sscanf($watermarkColor, "#%02x%02x%02x");
			$watermarkColor = array('r' => $r, 'g' => $g, 'b' => $b);

			$x = absint($arrWatermarkTextCoords[0]);
			$y = absint($arrWatermarkTextCoords[1]);

			$this->generate_watermark( $x, $y, $moviePosterPath, $watermarkText, $watermarkColor, $watermarkFontSize, $watermarkFontFamily, $movieSlug, $uploadFolderPath, $uploadFolderUrl );
		}

		/**
		 * Generate the watermark and add it to the
		 * movie poster with the text specified
		 *
		 * @method  generate_watermark
		 *
		 * @param   Int                 $x
		 * @param   Int                 $y
		 * @param   String              $moviePosterPath
		 * @param   String              $watermarkText
		 * @param   Array               $watermarkColor
		 * @param   Int                 $watermarkFontSize
		 * @param   String              $watermarkFontFamily
		 * @param   String 				$movieSlug
		 * @param   String 				$uploadFolderPath
		 * @param   String 				$uploadFolderUrl
		 *
		 * @since   [build_version]
		 */
		public function generate_watermark( Int $x, Int $y, String $moviePosterPath, String $watermarkText, Array $watermarkColor, Int $watermarkFontSize, String $watermarkFontFamily, String $movieSlug, String $uploadFolderPath, $uploadFolderUrl ) {
			/**
			 * Get the plugin directory path
			 */
			$pluginPath = $this->plugin_path;

			/**
			 * Set the headers to declare the
			 * type of file to send to the browser
			 */
			$outputFilePath = $uploadFolderPath . $movieSlug . '____.jpg';
			$outputFileUrl  = $uploadFolderUrl . $movieSlug . '____.jpg';
			$imageURL       = $moviePosterPath;
			$imageMimeType  = mime_content_type($imageURL);

			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=" . $outputFilePath);

			if( $imageMimeType == 'image/png' ) {
				header('Content-type: image/png');
			} else if( $imageMimeType == 'image/jpeg' || $imageMimeType == 'image/jpg' ) {
				header('Content-type: image/jpeg');
			}

			/**
			 * Get the RGB color values
			 */
			$red   = $watermarkColor['r'];
			$green = $watermarkColor['g'];
			$blue  = $watermarkColor['b'];

			$watermarkText = stripslashes($watermarkText);
			
			list($width,$height) = getimagesize($imageURL);
			$imageProperties     = imagecreatetruecolor($width, $height);

			if( $imageMimeType == 'image/png' ) {
				$targetLayer = imagecreatefrompng($imageURL);
			} else if( $imageMimeType == 'image/jpeg' || $imageMimeType == 'image/jpg' ) {
				$targetLayer = imagecreatefromjpeg($imageURL);
			}

			$watermarkColor = imagecolorallocate($imageProperties, $red, $green, $blue);

			/**
			 * Copy the image to resample with
			 * the text
			 */
			imagecopyresampled($imageProperties, $targetLayer, 0, 0, 0, 0, $width, $height, $width, $height);

			/**
			 * Load the fonts for the watermark text
			 */
			$font = $pluginPath . 'fonts/' . $watermarkFontFamily . '.ttf';

			/**
			 * Write the text over the image
			 * to create the watermark
			 */
			imagettftext($imageProperties, $watermarkFontSize, 0, $x, $y, $watermarkColor, $font, $watermarkText);

			if( $imageMimeType == 'image/png' ) {
				/**
				 * Outputs the created image as png
				 * to the broser
				 */
				imagepng($imageProperties, $outputFilePath);
			} else if( $imageMimeType == 'image/jpeg' || $imageMimeType == 'image/jpg' ) {
				/**
				 * Outputs the created image as jpg
				 * to the broser
				 */
				imagejpeg($imageProperties, $outputFilePath);
			}

			/**
			 * Destroys or frees the memory
			 * associated with the image
			 */
			imagedestroy($targetLayer);
			imagedestroy($imageProperties);

			echo '<img src="'.esc_url($outputFileUrl).'?v='.time().'" data-movie-slug="'.esc_attr($movieSlug).'" data-fpath="'.esc_attr($outputFilePath).'" alt="" />';
			die;
		}

		public function discard_poster() {
			if( !empty($_POST['file_path']) ) {
				echo (int) unlink( $_POST['file_path'] );
				die;
			}

			echo 0;
			die;
		}

		public function save_poster() {
			$output = array('message' => __('Error! please try again.'), 'redirect_url' => '');

			$movieSlug = isset($_POST['movie_slug']) ? $_POST['movie_slug'] : null ;

			$movieSlug = sanitize_title( $_POST['movie_slug'] );

			if( !empty($_POST['file_path']) ) {
				$pluginPath = $this->plugin_path;

				$newFileName = str_replace('____', '', $_POST['file_path']);
				$filePath    = (int) rename($_POST['file_path'], $newFileName);

				/*if( file_exists($_POST['file_path']) ) {
					unlink( $_POST['file_path'] );
				}*/

				$remoteFileUrl = $this->preMovieFilmToRemoteStorage( $movieSlug );

				$redirectUrl = admin_url( 'admin.php?page=' . urlencode($pluginPath) . 'movies.php' . '&orderby=movie_id&order=desc' );

				$output = array('message' => __('Poster for the uploaded movie saved successfully!'), 'redirect_url' => $redirectUrl );
			}

			echo json_encode($output);
			die;
		}

		/**
		 * Generate thumb for the uploaded movie
		 *
		 * @method  generateThumb
		 *
		 * @param   String           $parentDir
		 * @param   String           $movieFilePath
		 * @param   String           $thumbFilePath
		 *
		 * @return  Mixed
		 *
		 * @since   [build_version]
		 */
		public function generateThumb( $parentDir, $movieFilePath, $thumbFilePath ) {
			try {
				$cmd = shell_exec( 'ffmpeg -i ' . $parentDir.$movieFilePath . ' -vf scale=352x198,setdar=16:9 ' . $parentDir.$thumbFilePath );

				return $cmd;
			} catch( Exception $e ) {
				echo $e->getMessage(); die;
			}
		}

		/**
		 * Upload the true type font (.ttf) file
		 *
		 * @method  uploadFont
		 *
		 * @since   [build_version]
		 */
		public function uploadFont() {
			$ext = null;
			$redirectUrl = wp_get_referer();
			$pluginPath  = $this->plugin_path;

			if( isset($_FILES['upload_font']['name']) && $_FILES['upload_font']['name'] != null ) {
				$arrFileExt = wp_check_filetype($_FILES['upload_font']['name']);

				$ext = $arrFileExt['ext'];
			}

			/**
			 * Check whether the upload font file has errors
			 */
			if( isset($_FILES['upload_font']['error']) && $_FILES['upload_font']['error'] > 0 ) {
				update_option('error_notice', 2);
			} else if( $ext != 'ttf' ) {
				update_option('error_notice', 3);
			} else {
				$fileName    = sanitize_file_name( $_FILES['upload_font']['name'] );
				$fileTmpName = $_FILES['upload_font']['tmp_name'];
				$fileSize    = $_FILES['upload_font']['size'];

				$fileUploadPath = $pluginPath . 'fonts/' . $fileName;

				/**
				 * Upload the font file to the "fonts"
				 * folder in the plugin
				 */
				$uploadFile = (int) move_uploaded_file( $fileTmpName, $fileUploadPath );

				/**
				 * If the file uploaded successfully then show a
				 * success message else an error message
				 */
				if( $uploadFile === 0 ) {
					update_option('error_notice', 2);
				} else {
					update_option('error_notice', 1);
				}
			}

			/**
			 * Redirect back to the referrer URL
			 */
			wp_safe_redirect($redirectUrl);
			die;
		}

		/**
		 * Move uploaded movie files to DO Spaces Cloud
		 *
		 * @method  move_uploaded_file_to_awss3
		 *
		 * @param   String                       $fileName
		 * @param   String                       $filePath
		 * @param   Integer                       $movieId
		 *
		 * @return  String
		 *
		 * @since   1.0.0
		 */
		/*public function move_uploaded_file_to_awss3($fileName, $filePath, $movieId) {
			$bucket  = AWS_BUCKET_NAME;
			$keyname = 'movies/m' . $movieId . '/' . $fileName;

			$s3 = new S3Client([
			    'version' => AWS_API_VERSION,
			    'region'  => AWS_REGION,
			    'credentials' => [
			        'key'    => AWS_KEY,
			        'secret' => AWS_SECRET
			    ]
			]);

			try {
			    // Upload data.
			    $result = $s3->putObject([
			        'Bucket'     => $bucket,
			        'Key'        => $keyname,
			        'SourceFile' => $filePath
			    ]);

			    // Print the URL to the object.
			    $result = $result['ObjectURL'];

			    unlink($filePath);
			} catch (S3Exception $e) {
			    $result = $e->getMessage();
			}

			return $result;
		}*/

		/**
		 * Parse the movie slug to increment it in case it is not unique
		 *
		 * @method  parseMovieSlug
		 *
		 * @param   String           $movieSlug
		 * @param   integer          $movieId
		 *
		 * @return  String
		 *
		 * @since   1.0.0
		 */
		public function parseMovieSlug($movieSlug, $movieId = 0) {
			$checkMovieSlug = $this->isMovieSlugUnique($movieSlug, $movieId);

			if($checkMovieSlug != null) {
				$splitMovieSlug       = explode('-', $checkMovieSlug);
				$sizeOfMovieSlug      = count($splitMovieSlug);
				$lastIndexOfMovieSlug = $splitMovieSlug[$sizeOfMovieSlug-1];

				if(absint($lastIndexOfMovieSlug) == 0) {
					$movieSlug .= '-2';
				} else if($lastIndexOfMovieSlug > 0) {
					array_pop($splitMovieSlug);
					$lastIndexOfMovieSlug = $lastIndexOfMovieSlug + 1;
					$splitMovieSlug[]     = $lastIndexOfMovieSlug;
					$movieSlug            = implode('-', $splitMovieSlug);
				}
			}

			return $movieSlug;
		}

		/**
		 * Get the user slug by user id
		 *
		 * @method  getUserSlugById
		 *
		 * @param   Int              $userId
		 *
		 * @return  String
		 *
		 * @since   1.0.0
		 */
		public function getUserSlugById(Int $userId) {
			$userSlug = null;
			$userId   = absint($userId);

			if($userId > 0) {
				$userSlug = get_user_meta($userId, 'user_slug', true);
			}

			return $userSlug;
		}

		/**
		 * Check whether movie slug is unique
		 *
		 * @method  isMovieSlugUnique
		 *
		 * @param   String             $movieSlug
		 * @param   Integer            $movieId
		 *
		 * @return  String
		 *
		 * @since   1.0.0
		 */
		public function isMovieSlugUnique($movieSlug, $movieId) {
			global $wpdb;

			if($movieId > 0) {
				$movieMetaValue = $wpdb->get_var('SELECT slug FROM ' . $wpdb->prefix . 'movies WHERE slug LIKE "'.$movieSlug.'%" AND id <> '.$movieId.' ORDER BY id DESC LIMIT 1');
			} else {
				$movieMetaValue = $wpdb->get_var('SELECT slug FROM ' . $wpdb->prefix . 'movies WHERE slug LIKE "'.$movieSlug.'%" ORDER BY id DESC LIMIT 1');
			}

			return $movieMetaValue;
		}

		/**
		 * Delete the movie directory recursively
		 *
		 * @method  deleteDir
		 *
		 * @param   String           $dirPath
		 *
		 * @return  Boolean
		 *
		 * @since   1.0.0
		 */
		public function deleteDir($dirPath) {
		    if (! is_dir($dirPath)) {
		        throw new InvalidArgumentException("$dirPath must be a directory");
		    }

		    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
		        $dirPath .= '/';
		    }

		    $files = glob($dirPath . '*', GLOB_MARK);

		    if( !empty($files) ) {
			    foreach ($files as $file) {
			        if (is_dir($file)) {
			            $this->deleteDir($file);
			        } else {
			        	if( file_exists($file) ) {
			            	unlink($file);
			            }
			        }
			    }
			}

		    rmdir($dirPath);
		}

		public function delete_movie() {
			global $wpdb;

			$arrMovieId          = $_REQUEST['movies'];
			$uploadDirectoryPath = wp_upload_dir();

			if(!empty($arrMovieId)) {
				/*$bucket  = AWS_BUCKET_NAME;

				$s3 = new S3Client([
				    'version' => AWS_API_VERSION,
				    'region'  => AWS_REGION,
				    'credentials' => [
				        'key'    => AWS_KEY,
				        'secret' => AWS_SECRET
				    ]
				]);*/

				foreach($arrMovieId as $movieId) {
					$arrMovieDetailsById = $this->getMovieDetailsById($movieId);

					/* $keyname = 'movies/m' . $movieId . '/' . $arrMovieDetailsById['movie_filename'];

					try {
						$s3->deleteObject([
						    'Bucket' => $bucket,
						    'Key'    => $keyname
						]);
					} catch (Exception $e) {
						echo 'Error in deleting objects from aws s3: ' . $e->getMessage();
					}

					$keyname = 'movies/m' . $movieId . '/' . $arrMovieDetailsById['poster_filename'];

					$s3->deleteObject([
					    'Bucket' => $bucket,
					    'Key'    => $keyname
					]);

					$keyname = 'movies/m' . $movieId . '/' . $arrMovieDetailsById['thumbnail_filename'];

					$s3->deleteObject([
					    'Bucket' => $bucket,
					    'Key'    => $keyname
					]);*/

					$deleteMovieId = $wpdb->delete( $wpdb->prefix . 'movies', array( 'id' => $movieId ), array( '%d' ) );

					$this->deleteMovieFromRemoteServer( $movieId );
				}
			}
			
			update_option('error_notice', 1, 'no');

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php') . '&orderby=movie_id&order=desc';

			wp_redirect($redirectUrl);
		}

		public function getMovieDetailsById( $movieId ) {
			global $wpdb;

			$arrMovies = [];

			if(absint($movieId) > 0) {
				$arrMovies = $wpdb->get_row( $wpdb->prepare('SELECT m.id, m.title, m.category, m.movie_filename, m.poster_filename, m.thumbnail_filename, m.uploaded_by, m.movie_type, m.display_date, GROUP_CONCAT(mfm.festival_id) AS festivals, md.producer_full_name, md.director_full_name AS director_name, md.writer_full_name, md.cast, md.crew_details, md.technical_notes, md.editor_full_name, md.music, md.cinematographer_full_name, md.production_designer_full_name, md.production_company, md.synopsis, m.sort_order, m.slug, m.length, m.size, m.bitrate FROM ' . $wpdb->prefix . 'movies AS m LEFT JOIN ' . $wpdb->prefix . 'movie_details AS md ON m.id=md.movie_id LEFT JOIN ' . $wpdb->prefix . 'mf_mapping AS mfm ON mfm.movie_id=m.id WHERE m.id=%d GROUP BY mfm.movie_id', [$movieId]), 'ARRAY_A');
			}

			return $arrMovies;
		}

		public function activate_movie() {
			global $wpdb;

			$arrMovieId = $_REQUEST['movies'];

			if(!empty($arrMovieId)) {
				foreach($arrMovieId as $movieId) {
					$wpdb->update( 
						$wpdb->prefix . 'movies', 
						array( 'status' => 1 ), 
						array( 'id' => $movieId ), 
						array( '%d' ), 
						array( '%d' ) 
					);
				}
			}

			update_option('error_notice', 1, 'no');

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php') . '&orderby=movie_id&order=desc';

			wp_redirect($redirectUrl);
			die;
		}

		public function deactivate_movie() {
			global $wpdb;

			$arrMovieId = $_REQUEST['movies'];

			if(!empty($arrMovieId)) {
				foreach($arrMovieId as $movieId) {
					$wpdb->update( 
						$wpdb->prefix . 'movies', 
						array( 'status' => 0 ), 
						array( 'id' => $movieId ), 
						array( '%d' ), 
						array( '%d' ) 
					);
				}
			}

			update_option('error_notice', 1, 'no');

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php') . '&orderby=movie_id&order=desc';

			wp_redirect($redirectUrl);
			die;
		}

		public function make_featured() {
			global $wpdb;

			$arrMovieId = $_REQUEST['movies'];

			if(!empty($arrMovieId)) {
				foreach($arrMovieId as $movieId) {
					$arrMovies  = getMovieDetailsById( $movieId );
					$arrMovieTypes = explode(',', $arrMovies['movie_type']);

					if( isset($arrMovies['movie_type']) && !in_array('film_festival_featured', $arrMovieTypes) ){
						$arrMovieTypes[10] = 'film_festival_featured';

						$arrMovieTypes = array_filter($arrMovieTypes);

						$wpdb->update( 
							$wpdb->prefix . 'movies', 
							array( 'movie_type' => implode(',', $arrMovieTypes) ), 
							array( 'id' => $movieId ), 
							array( '%s' ),
							array( '%d' ) 
						);
					}
				}
			}

			update_option('error_notice', 1, 'no');

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php') . '&orderby=movie_id&order=desc';

			wp_redirect($redirectUrl);
			die;
		}

		public function make_unfeatured() {
			global $wpdb;

			$arrMovieId = $_REQUEST['movies'];

			if(!empty($arrMovieId)) {
				foreach($arrMovieId as $movieId) {
					$arrMovies  = getMovieDetailsById( $movieId );
					$arrMovieTypes = explode(',', $arrMovies['movie_type']);

					if( isset($arrMovies['movie_type']) && in_array('film_festival_featured', $arrMovieTypes) ){
						$movieArrKey = array_search('film_festival_featured', $arrMovieTypes);
						unset( $arrMovieTypes[$movieArrKey] );

						$arrMovieTypes = array_filter($arrMovieTypes);

						$wpdb->update( 
							$wpdb->prefix . 'movies', 
							array( 'movie_type' => implode(',', $arrMovieTypes) ), 
							array( 'id' => $movieId ), 
							array( '%s' ), 
							array( '%d' ) 
						);
					}
				}
			}

			update_option('error_notice', 1, 'no');

			$pluginPath  = $this->plugin_path;
			$redirectUrl = admin_url('admin.php') . '/?page=' . urlencode($pluginPath . '/movies.php' . '&orderby=movie_id&order=desc');

			wp_redirect($redirectUrl);
			die;
		}

		public function sort_movies() {
			global $wpdb;

			for( $i=0; $i < count($_REQUEST['data']); $i++ ) {
				$wpdb->update( 
					$wpdb->prefix . 'movies', 
					array( 'sort_order' => $i ), 
					array( 'id' => absint($_REQUEST['data'][$i]) ), 
					array( '%d'	), 
					array( '%d' ) 
				);
			}

			die('1');
		}

		public function boost_movie_to_top() {
			global $wpdb;

			$wpdb->update( 
				$wpdb->prefix . 'movies', 
				array( 'sort_order' => 0 ), 
				array( 'id' => absint($_REQUEST['mid']) ), 
				array( '%d'	), 
				array( '%d' ) 
			);

			$wpdb->query(
				$wpdb->prepare(
					'UPDATE '.$wpdb->prefix.'movies SET sort_order = sort_order+1 WHERE sort_order <= %d AND id != %d',
					absint($_REQUEST['so']),
					absint($_REQUEST['mid'])
				)
			);

			die('1');
		}

		/**
		 * Get the movie details by slug
		 *
		 * @method  getMovieDetailsBySlug
		 *
		 * @param   String                 $movieSlug
		 *
		 * @return  Array
		 *
		 * @since   [build_version]
		 */
		public function getMovieDetailsBySlug(String $movieSlug) {
			global $wpdb;

			$arrMovies = [];
			$movieSlug = esc_html($movieSlug);

			if($movieSlug != null) {
				$arrMovies = $wpdb->get_row( $wpdb->prepare('SELECT m.id, m.title, m.category, m.movie_filename, m.poster_filename, m.thumbnail_filename, m.uploaded_by, m.movie_type, m.display_date, GROUP_CONCAT(mfm.festival_id) AS festivals, md.producer_full_name, md.director_full_name AS director_name, md.writer_full_name, md.cast, md.crew_details, md.technical_notes, md.editor_full_name, md.music, md.cinematographer_full_name, md.production_designer_full_name, md.production_company, md.synopsis, m.sort_order FROM ' . $wpdb->prefix . 'movies AS m LEFT JOIN ' . $wpdb->prefix . 'movie_details AS md ON m.id=md.movie_id LEFT JOIN ' . $wpdb->prefix . 'mf_mapping AS mfm ON mfm.movie_id=m.id WHERE m.slug=%s GROUP BY mfm.movie_id', [$movieSlug]), 'ARRAY_A');
			}

			return $arrMovies;
		}

		/**
		 * Get the directory structure to an array recursively
		 *
		 * @method  directoryToArray
		 *
		 * @param   String            $directory
		 * @param   boolean           $recursive
		 *
		 * @return  Array
		 *
		 * @since   [build_version]
		 */
		public function directoryToArray( $directory, $recursive = true ) {
			$array_items = array();

			if ($handle = opendir($directory)) {
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						if (is_dir($directory. "/" . $file)) {
							if($recursive) {
								$array_items = array_merge($array_items, directoryToArray($directory. "/" . $file, $recursive));
							}

							$array_items[] = preg_replace("/\/\//si", "/", $file);
						} else {
							$array_items[] = preg_replace("/\/\//si", "/", $file);
						}
					}
				}

				closedir($handle);
			}

			return $array_items;
		}

		public function admin_notice_error() {
			$getOptionValue = get_option('error_notice') ?? 0;
			$errorMessage   = null;

		    if( $getOptionValue == 2 ) {
		    	$errorMessage = __( 'Something went wrong! Please try again.' );
			}

			if( $getOptionValue == 3 ) {
		    	$errorMessage = __( 'Invalid File Type! Only True Type Fonts (.ttf) are allowed.' );
			}

			if( $getOptionValue == 4 ) {
		    	$errorMessage = __( 'Please choose a frame by moving the playhead in the video below.' );
			}

			if( $getOptionValue > 0 && $errorMessage != null) {
				?>
				<div class="error notice is-dismissible">
			        <p><?php echo $errorMessage; ?></p>
			    </div>
			    <?php
			}

			delete_option('error_notice');
		}

		public function admin_notice_success() {
			if(get_option('error_notice') == 1) {
		    ?>
			    <div class="updated notice is-dismissible">
			        <p><?php _e( 'Operation performed successfully.' ); ?></p>
			    </div>
		    <?php
		    	delete_option('error_notice');
			}
		}
		
		public function add_file_types($mime_types){
		    $mime_types['ttf'] = 'application/octet-stream';

		    return $mime_types;
		}

		public function cardora_check_url($url) {
		    $ch = curl_init();

		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_HEADER, 1);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$data    = curl_exec($ch);
			$headers = curl_getinfo($ch);

		    curl_close($ch);

		    return $headers['http_code'];
		}

		public function reset_movies_sort_order() {
			global $wpdb;

			$arrMovies = $wpdb->get_results('SELECT m.id AS movie_id, m.title, m.category, m.sort_order, m.slug FROM ' . $wpdb->prefix . 'movies AS m ORDER BY id DESC', 'ARRAY_A');

			if( !empty($arrMovies) ) {
				$i=0;
				foreach( $arrMovies as $movie ) {
					$wpdb->update( 
						$wpdb->prefix . 'movies', 
						array( 'sort_order' => $i ), 
						array( 'id' => absint($movie['movie_id']) ), 
						array( '%d'	), 
						array( '%d' ) 
					);

					$i++;
				}
			}

			die('1');
		}

		public function preMovieFilmToRemoteStorage( String $movieSlug ) {
			$arrMovieData  = $this->getMovieDetailsBySlug( $movieSlug );
			$remoteFileUrl = null;

			if( !empty($arrMovieData) ) {
				$movieId = absint( $arrMovieData['id'] );
				$movieUploadPath = $this->upload_Path;
				$movieFolderName = 'm' . $movieId;
				$movieFolderPath = $movieUploadPath . '/movies/' . $movieFolderName;

				$arrMoviefolderContents = $this->directoryToArray( $movieFolderPath );

				if( !empty($arrMoviefolderContents) ) {
					foreach( $arrMoviefolderContents as $fileName ) {
						$localFilePath = $movieFolderPath . '/' . $fileName;
 
						$remoteFileUrl = $this->moveFilmToRemoteStorage( $localFilePath, $fileName, $movieFolderName );
					}

				}

				shell_exec('rm -rf ' . $movieFolderPath);
			}

			return $remoteFileUrl;
		}

		public function moveFilmToRemoteStorage( String $filePath, String $fileName, String $newFolderName = '', String $remoteFolder = CPTStorageRemoteFolderLocation ) {
			$localFilePath    = $filePath;
			$remoteFolderPath = CPTStoragePath . $remoteFolder . $newFolderName;
			$remoteFilePath   = $remoteFolderPath . '/' . $fileName;
			$remoteFileUrl    = CPTStorageURL . $remoteFolder . $newFolderName . '/' . $fileName;

			$ssh  = $this->remoteServerSSH();
			$sftp = $this->remoteServerSFTP();

			$remoteFileSize = (int) $sftp->size($remoteFilePath);

			if( $remoteFileSize > 0 ) {
		    	$sftp->delete( $remoteFilePath );
		    }

		    if( !file_exists($remoteFolderPath) ) {
		    	$ssh->exec('mkdir ' . $remoteFolderPath);
		    }

		    $sftp->put( $remoteFilePath, $localFilePath, SFTP::SOURCE_LOCAL_FILE );

		    return $remoteFileUrl;
		}

		public function deleteMovieFromRemoteServer( Int $movieId, String $remoteFolder = CPTStorageRemoteFolderLocation ) {
			if( absint($movieId) == 0 ) {
				return false;
			}

			$newFolderName    = 'm' . $movieId;
			$remoteFolderPath = CPTStoragePath . $remoteFolder . $newFolderName;

		    $ssh = $this->remoteServerSSH();

		    if( $newFolderName != null ) {
		    	return $ssh->exec('rm -rf ' . $remoteFolderPath);
		    }

		    return false;
		}

		public function remoteServerSSH() {
			if( defined('CPTStoragePort') ) {
		    	$ssh = new SSH2( CPTStorageHostname, CPTStoragePort );
		    } else {
		    	$ssh = new SSH2( CPTStorageHostname );
		    }
 
		    if(!$ssh->login( CPTStorageUsername, CPTStoragePassword )) {
		    	update_option( 'remote_server_status', 'disconnected', true );

		        return false;
		    }

		    update_option( 'remote_server_status', 'connected', true );
		    delete_option( 'remote_server_disconnection_notice_admin' );

		    return $ssh;
		}

		public function remoteServerSFTP() {
			if( defined('CPTStoragePort') ) {
		        $sftp = new SFTP( CPTStorageHostname, CPTStoragePort );
		    } else {
		        $sftp = new SFTP( CPTStorageHostname );
		    }

		    if (!$sftp->login( CPTStorageUsername, CPTStoragePassword )) {
		    	update_option( 'remote_server_status', 'disconnected', true );

		        return false;
		    }

		    update_option( 'remote_server_status', 'connected', true );
		    delete_option( 'remote_server_disconnection_notice_admin' );

		    return $sftp;
		}
	}
}

$objTektonicCreatePosterThumb = new TektonicCreatePosterThumb;
