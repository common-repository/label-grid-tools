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

	<h2><?php esc_html_e('LabelGrid System Logs', 'label-grid-tools'); ?></h2>
	<p><?php esc_html_e('All events are logged in this table. You can change debug level in the General Settings.', 'label-grid-tools'); ?></p>
	<p><?php esc_html_e('Server Local Time: ', 'label-grid-tools'); echo "<strong>" . esc_html(date("Y-m-d H:i:s")) . "</strong>"; ?></p>
	<br>

	<form id="movies-filter" method="get">
		<!-- Ensure that the form posts back to the current page -->
		<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
		<!-- Render the completed list table -->
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
