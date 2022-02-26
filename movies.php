<?php
/**
 * New User Administration Screen.
 *
 * @package WordPress
 * @subpackage Administration
 */

/** WordPress Administration Bootstrap */
$adminPath = ABSPATH . 'wp-admin/';
global $pagenow;

require_once( $adminPath . 'admin.php' );
require_once( $adminPath . 'admin-header.php' );

if( ! class_exists( 'WP_Movies_List_Table' ) ) {
    require_once( 'classes/class-wp-movies-list-table.php' );
}

$wp_list_table = new WP_Movies_List_Table;

global $objTektonicCreatePosterThumb;

$pluginPath = $objTektonicCreatePosterThumb->plugin_path;
?>
<style>
	#poster_filename {
		width: 15% !important;
	}

	#slug {
		width: 15% !important;
	}
</style>
<div class="wrap">
	<div id="icon-tools" class="icon32"></div>
	<h1 class="wp-heading-inline"><?php _e('Movies'); ?></h1>
	<a href="<?php echo admin_url( 'admin.php/?page=' . urlencode($pluginPath . '/upload-movie.php') ); ?>" class="page-title-action"><?php _e('Add New'); ?></a>
	<hr class="wp-header-end">
	<form name="search_movies" id="search_movies" method="GET">
		<?php
		$wp_list_table->search_box( __( 'Search Movies' ), 'movie' );
		?>
	</form>
	<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
		<?php
		$wp_list_table->prepare_items();
		$wp_list_table->display(false);
		?>
	</form>
	<br class="clear" />
</div>
