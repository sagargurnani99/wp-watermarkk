<div class="wrap">
	<h1 id="add-new-user"><?php echo $title; ?></h1>
	<div id="ajax-response"></div>
	<?php
	if( current_user_can( 'create_users') ) {
		?>
		<p><?php _e($desc); ?></p>
		<form method="post" name="upload_movie_form" id="createuser" class="validate" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
			<input name="action" type="hidden" value="<?php echo esc_html($action); ?>" />
			<input name="movie_id" type="hidden" value="<?php echo absint($movieId); ?>" />
			<?php wp_nonce_field( 'upload-movie', '_wpnonce_upload-movie' ); ?>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="movie_title"><?php _e('Title'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
					<td><input name="movie_title" type="text" id="movie_title" aria-required="true" autocapitalize="none" autocorrect="off" value="<?php echo esc_html(stripslashes($movieTitle)); ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="movie_sort_order"><?php _e('Sort Order'); ?></label></th>
					<td><input name="movie_sort_order" type="number" id="movie_sort_order" aria-required="true" autocapitalize="none" autocorrect="off" value="<?php echo absint($movieSortOrder); ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="movie_category"><?php _e('Category'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
					<td>
						<?php
						wp_dropdown_categories([
							'name'             => 'movie_category',
							'required'         => true,
							'orderby'          => 'name',
							'show_option_none' => __('-- select --'),
							'hide_empty'       => false,
							'selected' 		   => esc_html($movieCategory)
						]);
						?>
					</td>
				</tr>
				<?php
				if($showMovieFileFields) {
				?>
					<tr class="form-field form-required">
						<th scope="row"><label for="upload_movie"><?php _e('Upload Movie'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
						<td><input name="upload_movie" type="file" id="upload_movie" aria-required="true" /><p><strong>- Allowed File Type:</strong> .mp4</p><p><strong>- Allowed File Size:</strong> 1 Gb</p></td>
					</tr>
					<!-- <tr class="form-field form-required">
						<th scope="row"><label for="upload_thumbnail"><?php _e('Upload Thumbnail'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
						<td><input name="upload_thumbnail" type="file" id="upload_thumbnail" aria-required="true" /></td>
					</tr>
					<tr class="form-field form-required">
						<th scope="row"><label for="upload_poster"><?php _e('Upload Poster'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
						<td><input name="upload_poster" type="file" id="upload_poster" aria-required="true" /></td>
					</tr> -->
				<?php
				}
				?>
				<tr class="form-required">
					<th scope="row"><?php _e('Movie Type'); ?><span class="description"><?php _e('(required)'); ?></span></th>
					<td>
						<input name="movie_type[]" type="checkbox" id="mt_c" aria-required="true" value="cardora" <?php if($smtC) { ?> checked <?php } ?> />
						<label for="mt_c"><?php _e('Cardora'); ?></label>
						<input name="movie_type[]" type="checkbox" id="ft_c" aria-required="true" value="cardora_featured" <?php if($smtCF) { ?> checked <?php } ?> />
						<label for="ft_c"><?php _e('Featured'); ?></label>
					</td>
				</tr>
				<tr class="form-required">
					<th scope="row"></th>
					<td>
						<input name="movie_type[]" type="checkbox" id="mt_ff" aria-required="true" value="film_festival" <?php if($smtFF) { ?> checked <?php } ?> onclick="toggleFestivals();" />
						<label for="mt_ff"><?php _e('Film Festival'); ?></label>
						<input name="movie_type[]" type="checkbox" id="ft_ff" aria-required="true" value="film_festival_featured" <?php if($smtFFF) { ?> checked <?php } ?> onclick="toggleFestivals();" />
						<label for="ft_ff"><?php _e('Featured'); ?></label>
					</td>
				</tr>
				<?php
				$arrAllFestivals = getAllFestivals('active', ['id', 'name']);
				?>
				<tr class="form-field form-required" id="festivalsRow">
					<th scope="row"><label for="display_date"><?php _e('Festivals'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
					<td>
						<select name="festivals[]" id="festivals" multiple="true" class="multiple">
							<?php
							if(!empty($arrAllFestivals)) {
								foreach($arrAllFestivals as $festival) {
									$selected     = null;
									$festivalId   = $festival['id'];
									$festivalName = $festival['name'];

									if(in_array($festivalId, $arrFestivals)) {
										$selected = 'selected';
									}
							?>
									<option value="<?php echo absint($festivalId); ?>" <?php echo $selected; ?> ><?php echo esc_html($festivalName); ?></option>
							<?php
								}
							} else {
							?>
								<option value="-1">-- no festivals found --</option>
							<?php
							}
							?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="uploaded_by"><?php _e('Filmmaker'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
					<td>
						<?php
						$arrAllUsers      = get_users(['fields' => ['ID', 'display_name']]);
						$userDropdownHtml = '<select name="uploaded_by" id="uploaded_by" required><option value="-1">-- select --</option>';						
						
						if(!empty($arrAllUsers)) {
							foreach($arrAllUsers as $user) {
								$selected = null;

								if($user->ID == $uploadedBy) {
									$selected = 'selected';
								}

								$userDropdownHtml .= '<option value="' . absint($user->ID) . '" ' . $selected . '>' . esc_html(ucwords($user->display_name)) . '</option>';
							}
						}

						echo $userDropdownHtml .= '</select>';
						?>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="producer_full_name"><?php _e('Producer'); ?></label></th>
					<td><input name="producer_full_name" type="text" id="producer_full_name" value="<?php echo $producerName; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="director_full_name"><?php _e('Director'); ?></label></th>
					<td><input name="director_full_name" type="text" id="director_full_name" value="<?php echo esc_html($directorName); ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="writer_full_name"><?php _e('Writer'); ?></label></th>
					<td><input name="writer_full_name" type="text" id="writer_full_name" value="<?php echo $writerName; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="cinematographer_full_name"><?php _e('Cinematographer'); ?></label></th>
					<td><input name="cinematographer_full_name" type="text" id="cinematographer_full_name" value="<?php echo $cinematographerName; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="production_designer_full_name"><?php _e('Production Designer'); ?></label></th>
					<td><input name="production_designer_full_name" type="text" id="production_designer_full_name" value="<?php echo $productionDesigner; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="production_company"><?php _e('Production Company'); ?></label></th>
					<td><input name="production_company" type="text" id="production_company" value="<?php echo $productionCompany; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="editor_full_name"><?php _e('Editor'); ?></label></th>
					<td><input name="editor_full_name" type="text" id="editor_full_name" value="<?php echo $editorName; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="music"><?php _e('Music'); ?></label></th>
					<td><input name="music" type="text" id="music" value="<?php echo $music; ?>" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="cast"><?php _e('Cast'); ?></label></th>
					<td><textarea name="cast" id="cast" rows="12"><?php echo esc_html($cast); ?></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="crew_details"><?php _e('Crew Details'); ?></label></th>
					<td><textarea name="crew_details" id="crew_details" rows="12"><?php echo esc_html($crewDetails); ?></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="technical_notes"><?php _e('Technical Notes'); ?></label></th>
					<td><textarea name="technical_notes" id="technical_notes" rows="12"><?php echo esc_html($technicalNotes); ?></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="synopsis"><?php _e('Synopsis'); ?></label></th>
					<td><textarea name="synopsis" id="synopsis" rows="12"><?php echo esc_html($synopsis); ?></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="display_date"><?php _e('Display Date'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
					<td><input name="display_date" type="date" id="display_date" aria-required="true" value="<?php echo esc_html($displayDate); ?>" />
						<?php
						if($action == 'edit_movie') {
						?>
							<input type="hidden" name="movie_slug" value="<?php echo esc_html($movieSlug); ?>"/>
						<?php
						}
						?>
					</td>
				</tr>
			</table>
			<?php
			submit_button( __( 'Submit & Next' ), 'primary', 'uploadmovie', false, array( 'id' => 'uploadmoviesubmit_btn' ) );
			submit_button( __( 'Submit & Finish' ), 'primary', 'uploadmovieandfinish', false, array( 'id' => 'uploadmoviesubmitandfinish_btn', 'style' => 'margin-left: 10px;' ) );
			submit_button( __( 'Cancel' ), 'secondary', 'canceluploadmovie', false, array( 'id' => 'uploadmoviecancel_btn', 'style' => 'margin-left: 10px;'));
			?>
			<p><?php _e("- On <strong>Submit & Next</strong>, movie will upload and then page will go to Next Step, where you can create or upload a Poster"); ?><br><?php _e("- <strong>Submit & Finish</strong> takes you back to Movies List page"); ?></p>
		</form>
		<?php
	}
	?>
</div>
<script type="text/javascript">
	window.load = toggleFestivals();

	function toggleFestivals() {
		var ffId   = document.getElementById('mt_ff');
		var fffId  = document.getElementById('ft_ff');
		var festId = document.getElementById('festivalsRow');

		if(ffId.checked == true || fffId.checked == true) {
			festId.style.removeProperty('display');
		} else {
			festId.style.display = 'none';
		}
	}
</script>