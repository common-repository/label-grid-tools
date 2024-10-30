<?php

/**
 * Provide an admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://labelgrid.com
 * @since      1.0.0
 *
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin/partials
 */

if (current_user_can('edit_users')) {

    // Generate a custom nonce value.
    $lgt_sync_nonce = wp_create_nonce('lgt_sync_nonce');

    ?>
<div class="wrap">
	<h2><?php esc_html_e('LabelGrid Tools Manual Synchronization', 'label-grid-tools'); ?></h2>
	<br>
	<div id="lgt_form_feedback"></div>
		
	<?php if ($this->is_active === "yes") { ?>	
		<p><?php esc_html_e('Synchronize this WordPress instance pulling data from LabelGrid databases.', 'label-grid-tools'); ?></p>
		<p><strong><?php esc_html_e('This manual import mode should be used only on first import or to debug issues. For a faster import, please use the "Manual Update Catalog" link in the LabelGrid Tools admin top bar.', 'label-grid-tools'); ?></strong></p>
		<br>
		<div class="lgt_sync_form">

			<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="lgt_sync">
				<input type="hidden" name="action" value="lgt_sync">
				<input type="hidden" name="lgt_sync_nonce" value="<?php echo esc_attr($lgt_sync_nonce); ?>" />

				<p>
					<label for="delete_artists"><?php esc_html_e('DELETE all Artists before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_artists" id="delete_artists">
				</p>
				<p>
					<label for="delete_releases"><?php esc_html_e('DELETE all Releases before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_releases" id="delete_releases">
				</p>
				<p>
					<label for="delete_genres"><?php esc_html_e('DELETE all Genres before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_genres" id="delete_genres">
				</p>
				<p>
					<label for="delete_labels"><?php esc_html_e('DELETE all Record Labels before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_labels" id="delete_labels">
				</p>
				<p>
					<label for="delete_artist_category"><?php esc_html_e('DELETE all Artist Categories before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_artist_category" id="delete_artist_category">
				</p>
				<p>
					<label for="delete_artist_tag"><?php esc_html_e('DELETE all Artists Tags before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_artist_tag" id="delete_artist_tag">
				</p>
				<p>
					<label for="delete_release_tag"><?php esc_html_e('DELETE all Release Tags before importing', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="delete_release_tag" id="delete_release_tag">
				</p>
				<br>
				<p>
					<label for="force_sync_releases"><?php esc_html_e('FORCE SYNC RELEASES - Force updates on all releases', 'label-grid-tools'); ?></label>
					<input type="checkbox" name="force_sync_releases" id="force_sync_releases">
				</p>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Synchronize data', 'label-grid-tools'); ?>">
				</p>
			</form>
			<br>
		</div>
	<?php
    } else {
        ?>
		<p><strong><?php esc_html_e('This Functionality is not Enabled. To sync content with LabelGrid, you need to be a customer with a valid API Token. Add your LabelGrid API Token in General Settings.', 'label-grid-tools'); ?></strong></p>	
	<?php
    }
    ?>
</div>
<?php
} else {
    ?>
	<p><?php esc_html_e('You are not authorized to perform this operation.', 'label-grid-tools'); ?></p>
<?php   
}
?>
