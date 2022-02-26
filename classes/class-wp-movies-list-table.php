<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WP_Movies_List_Table extends WP_List_Table
{
	public function get_columns() {
		$columns = array(
			'options'         => '',
			'cb'              => '<input type="checkbox" />',
			'title'           => __('Title'),
			'slug'            => __('Slug'),
			'poster_filename' => __('Poster'),
			'category_name'   => __('Category'),
			'user_name'       => __('Filmmaker'),
			'movie_type'      => __('Movie Type'),
			'festivals'       => __('Festivals'),
			'display_date'    => __('Display Date'),
			'movie_status'    => __('Status'),
			'sort_order'      => __('Order'),
			'length'          => __('Length'),
			'size'            => __('Size'),
			'bitrate'         => __('Bitrate'),
			'movie_id'        => __('Id')
		);

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'movie_id'   => array('movie_id', true),
			'title'      => array('title', true),
			'sort_order' => array('sort_order', true),
			'length'     => array('length', true),
			'size'       => array('size', true),
			'bitrate'    => array('bitrate', true)
		);

		return $sortable_columns;
	}

	public function get_bulk_actions() {
		$actions = array(
			'delete_movie'     => __('Delete'),
			'activate_movie'   => __('Activate'),
			'deactivate_movie' => __('Deactivate'),
			'make_featured'    => __('Show on Front Page'),
			'make_unfeatured'  => __('Remove from Front Page')
		);

		return $actions;
	}

	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$movies                = $this->get_movies();

		$this->items = $movies;
	}

	public function column_default( $item, $columnName ) {
		$arrMovieData = array();

		if( $columnName == 'length' || $columnName == 'bitrate' || $columnName == 'size' ) {
			$movieId       = absint($item['movie_id']);
			$movieFileName = $item['movie_filename'];

			global $objTektonicCreatePosterThumb;
			$arrMovieData = $objTektonicCreatePosterThumb->getMovieDetailsById( $movieId );
		}

		switch( $columnName ) { 
			case 'title':
			case 'movie_id':
			case 'category_name':
			case 'movie_status':
			case 'user_name':
			case 'sort_order':
				return stripcslashes($item[$columnName]);
			case 'slug':
				$slug = stripcslashes($item[$columnName]);

				if( in_array('film_festival_featured', explode(',', $item['movie_type'])) ) {
					return '<strong style="color: #ff0000;">' . stripcslashes($item[$columnName]) . '</strong>';
				} else {
					return stripcslashes($item[$columnName]);
				}

			case 'length':
				return (isset($arrMovieData[$columnName]) && $arrMovieData[$columnName] != null) ? $arrMovieData[$columnName] . ' s' : 0;
			case 'size':
				return (isset($arrMovieData[$columnName]) && $arrMovieData[$columnName] > 0) ? $arrMovieData[$columnName] . ' MB' : 0;
			case 'bitrate':
				return (isset($arrMovieData[$columnName]) && $arrMovieData[$columnName] > 0) ? number_format($arrMovieData[$columnName], 2) . ' Mbps' : 0;
			case 'movie_type':
				$arrMovieType = explode(',', $item[$columnName]);

				if(!empty($arrMovieType)) {
					foreach($arrMovieType as &$movieType) {
						if( $movieType == 'cardora' ) {
							$movieType = 'SaM';
						} else if( $movieType == 'film_festival_featured' ) {
							$movieType = 'FP';
						}

						$movieType = str_replace('_', '&nbsp;', $movieType);
						$movieType = ucwords(esc_html($movieType));
					}
				}

				return implode(', ', stripslashes_deep($arrMovieType));
			case 'display_date':
				if( $item[$columnName] != '0000-00-00' ) {
					return date('d-M-Y', strtotime($item[$columnName]));
				} else {
					return esc_html($item[$columnName]);
				}
			case 'festivals':
				$arrFestivals = explode(',', $item[$columnName]);
				$arrFestivals = array_unique($arrFestivals);

				if(!empty($arrFestivals)) {
					foreach($arrFestivals as &$festivals) {
						$festivals = str_replace('_', ' ', $festivals);
						$festivals = ucwords(esc_html($festivals));
					}
				}

				return implode('<br>', stripslashes_deep($arrFestivals));
			case 'poster_filename':
				global $objTektonicCreatePosterThumb;

				$movieId         = absint($item['movie_id']);
				$moviePosterName = $item[$columnName];

				$baseMovieUrlUploadDir = CPTStorageURL . CPTStorageRemoteFolderLocation;
				$moviePosterUrl        = $baseMovieUrlUploadDir . 'm' . $movieId . '/' . $moviePosterName;

				$arrUploadDir         = wp_get_upload_dir();
				$baseUrlUploadDir     = $arrUploadDir['baseurl'];
				$basePathUploadDir    = $arrUploadDir['basedir'];

				$checkAwsS3UrlWorking = $objTektonicCreatePosterThumb->cardora_check_url($moviePosterUrl);

				if( $checkAwsS3UrlWorking != 200 ) {
				    $moviePosterUrl = $baseUrlUploadDir . '/movies/m' . $movieId . '/' . $moviePosterName;
				}

				if( @getimagesize($moviePosterUrl) ) {
					return '<img src="' . esc_url($moviePosterUrl) . '?t=' . time(). '" alt="' . esc_html($moviePosterName) . '" style="width: 100px;" />';
				}

				return __('No poster found!');
			default:
				return '<img class="drag-movies" title="Drag Movie" src="'.esc_url( plugins_url( '/../img/drag.png', __FILE__ ) ).'" alt="" style="height: 20px;width: 11px;cursor: grabbing;margin: 0px 5px;" /><img class="btt-movie" src="'.esc_url( plugins_url( '/../img/double-arrow-up.png', __FILE__ ) ).'" alt="" title="Boost Up" style="height: 20px;width: 11px;cursor: pointer;" data-mid="'.absint($item['movie_id']).'" data-so="'.absint($item['sort_order']).'" />';
		}
	}

	/**
	 * Output 'no user roles' message.
	 *
	 * @since 3.1.0
	 */
	public function no_items() {
		_e( 'No movies found.' );
	}

	public function has_items() {
		return true;
	}

	public function get_movies() {
		global $wpdb;

		$orderBy    = $_GET['orderby'] ?? 'sort_order';
		$order      = $_GET['order'] ?? 'asc';
		$searchTerm = $_GET['s'] ?? null;

		if($searchTerm != null) {
			$searchTerm = urldecode($searchTerm);

			$arrMovies = $wpdb->get_results('SELECT m.id AS movie_id, m.title, m.category, CASE WHEN m.status > 0 THEN "Active" ELSE "Inactive" END AS movie_status, created_on, CASE WHEN u.display_name != "" THEN u.display_name ELSE u.user_nicename END AS user_name, t.name as category_name, movie_type, display_date, md.director_full_name AS director_name, m.sort_order, m.slug, m.movie_filename, m.poster_filename, m.length, m.size, m.bitrate FROM ' . $wpdb->prefix . 'movies AS m LEFT JOIN ' . $wpdb->prefix . 'movie_details AS md ON m.id=md.movie_id INNER JOIN ' . $wpdb->prefix . 'users AS u ON m.uploaded_by = u.ID INNER JOIN ' . $wpdb->prefix . 'terms AS t ON m.category = t.term_id AND (m.title LIKE "%'.$searchTerm.'%" OR md.director_full_name LIKE "%'.$searchTerm.'%") ORDER BY ' . $orderBy . ' ' . $order, 'ARRAY_A');
		} else {
			$arrMovies = $wpdb->get_results('SELECT m.id AS movie_id, m.title, m.category, CASE WHEN m.status > 0 THEN "Active" ELSE "Inactive" END AS movie_status, created_on, CASE WHEN u.display_name != "" THEN u.display_name ELSE u.user_nicename END AS user_name, t.name as category_name, movie_type, display_date, md.director_full_name AS director_name, m.sort_order, m.slug, m.movie_filename, m.poster_filename, m.length, m.size, m.bitrate FROM ' . $wpdb->prefix . 'movies AS m LEFT JOIN ' . $wpdb->prefix . 'movie_details AS md ON m.id=md.movie_id INNER JOIN ' . $wpdb->prefix . 'users AS u ON m.uploaded_by = u.ID INNER JOIN ' . $wpdb->prefix . 'terms AS t ON m.category = t.term_id ORDER BY ' . $orderBy . ' ' . $order, 'ARRAY_A');
		}

		if(!empty($arrMovies)) {
			foreach($arrMovies as &$movie) {
				$arrFestivals = $wpdb->get_results('SELECT f.id, f.name FROM ' . $wpdb->prefix . 'mf_mapping as mfm INNER JOIN ' . $wpdb->prefix . 'festivals AS f ON f.id=mfm.festival_id WHERE mfm.movie_id=' . $movie['movie_id'], 'ARRAY_A');
				$arrFest = array_column($arrFestivals, 'name');
				$movie['festivals'] = implode(',', $arrFest);
			}
		}

		return $arrMovies;
	}

	public function column_cb($item) {
		$movieId       = absint($item['movie_id']);
		$movieFileName = $item['movie_filename'];

		/**
		 * Update the movie meta data for every row instead
		 * of every column
		 */
		if( $movieId > 63 ) {
			$this->getMovieMetaData( $movieId, $movieFileName );
		}

		/**
		 * Add checkbox for bulk actions to every row
		 */
    	return sprintf(
        	'<input type="checkbox" name="movies[]" value="%s" />', $movieId
    	);    
	}

	public function column_title($item) {
		global $objTektonicCreatePosterThumb;

		$pluginPath = basename($objTektonicCreatePosterThumb->plugin_path);
		$editLink   = admin_url('admin.php/?page=' . urlencode($pluginPath) . '/upload-movie.php&action=%s&id=%d');

		$actions = array(
		        'edit' => sprintf( '<a href="'.$editLink.'">Edit</a>', 'edit_movie', absint($item['movie_id']) )
	    );

		return sprintf('%1$s %2$s', stripcslashes($item['title']), $this->row_actions($actions) );
	}

	public function getMovieMetaData( $movieId, $movieFileName ) {
		global $objTektonicCreatePosterThumb;

		$movieFilePath = $length = null;
		$fileSize = $MBitsPerSec = 0;

		$baseMovieUrlUploadDir = AWS_MICROMOVIE_URL;
		$movieFileUrl          = $baseMovieUrlUploadDir . $movieId . '/' . $movieFileName;

		$checkAwsS3UrlWorking = $objTektonicCreatePosterThumb->cardora_check_url($movieFileUrl);

		if( $checkAwsS3UrlWorking != 200 ) {
			$arrUploadDir         = wp_get_upload_dir();
			$baseUrlUploadDir     = $arrUploadDir['baseurl'];
			$basePathUploadDir    = $arrUploadDir['basedir'];

			$movieFileUrl  = $baseUrlUploadDir . '/movies/m' . $movieId . '/' . $movieFileName;
			$movieFilePath = $basePathUploadDir . '/movies/m' . $movieId . '/' . $movieFileName;

			$getMetaData = wp_read_video_metadata( $movieFilePath );
		} else {
			$output = array(
				'length'  => 0,
				'size'    => 0,
				'bitrate' => 0
			);

			return $output;
		}

		if( false !== $getMetaData ) {
			if( isset($getMetaData['filesize']) && $getMetaData['filesize'] != null ) {
				$fileSize     = number_format(($getMetaData['filesize']/1048576), 2);
			}

			if( isset($getMetaData['length_formatted']) && $getMetaData['length_formatted'] != null ) {
				$length = $getMetaData['length_formatted'];
			}

			if( isset($getMetaData['filesize']) && $getMetaData['filesize'] != null && isset($getMetaData['length_formatted']) && $getMetaData['length_formatted'] != null && $getMetaData['length'] > 0 ) {
				$fileSizeMBits = ($getMetaData['filesize']/125000);
				$MBitsPerSec   = ($fileSizeMBits / $getMetaData['length']);
			}
		}

		$output = array(
			'length'  => $length,
			'size'    => $fileSize,
			'bitrate' => $MBitsPerSec
		);

		/**
		 * Update the movie metadata in the DB table
		 */
		global $objTektonicCreatePosterThumb;
		$objTektonicCreatePosterThumb->update_movie_data(
			$output,
			array( 'id' => $movieId )
		);

		return $output;
	}

	public function single_row( $item ) {
		$movieId = $item['movie_id'] ?? 0;

		echo '<tr id="'.absint($movieId).'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 3.1.0
	 *
	 * @param object $item The current item
	 */
	protected function single_row_columns( $item ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden ) ) {
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
				echo call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				);
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( $this, 'column_' . $column_name ), $item );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			} else {
				echo '<td '.$attributes.' style="width: 200px;">';
				echo $this->column_default( $item, $column_name );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			}
		}
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param string[] $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$two            = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		?>
		<button type="button" name="reset_movies_sort_order" id="reset_movies_sort_order" class="button action"><?php _e('Reset Order'); ?></button>
		<p style="color: #ff0000; float: right; margin: 4px 0px 0px 10px;"><?php _e('Slugs in red are featured and appear on the front page'); ?></p>
		<?php
		echo "\n";
	}
}
