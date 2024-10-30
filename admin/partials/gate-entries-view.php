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

    ?>
<div class="wrap">

	<h2><?php esc_html_e('LabelGrid Gate Entries', 'label-grid-tools'); ?></h2>
	<br>

	<form id="movies-filter" method="get">
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
		<!-- Now we can render the completed list table -->
		<?php $list_table->display(); ?>
	</form>

</div>
<?php
} else {
    ?>
<p><?php esc_html_e('You are not authorized to perform this operation.', 'label-grid-tools'); ?></p>
<?php
}
?>
