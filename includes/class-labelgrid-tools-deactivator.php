<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://labelgrid.com
 * @since      1.0.0
 *
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 * @package LabelGrid_Tools
 * @subpackage LabelGrid_Tools/includes
 * @author LabelGrid <team@labelgrid.com>
 */
class LabelGrid_Tools_Deactivator
{

    /**
     * Short Description.
     * (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        
	    //DISABLE CRON EVENTS
	    wp_clear_scheduled_hook('lgt_sync_catalog');
	    wp_clear_scheduled_hook('lgt_check_lg_api');
	    wp_clear_scheduled_hook('lgt_plugin_stats');
	    wp_clear_scheduled_hook('lgt_log_cleaning');
	    
	}

}
