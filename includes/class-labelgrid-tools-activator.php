<?php

/**
 * Fired during plugin activation
 *
 * @link       https://labelgrid.com
 * @since      1.0.0
 *
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/includes
 */
class LabelGrid_Tools_Activator
{

    public function __construct() {}

    /**
     * Short Description.
     * (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public function activate()
    {
        $min_php = '8.0.0';

        // Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
        if (version_compare(PHP_VERSION, $min_php, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));

            // Internationalized, sanitized, and user-friendly error message
            /* translators: 1: Required PHP version, 2: Current PHP version */
            wp_die(
                esc_html(sprintf(
                    __('This plugin requires PHP version %1$s or higher. Your current version is %2$s. The plugin has been deactivated.', 'label-grid-tools'),
                    $min_php,
                    PHP_VERSION
                )),
                esc_html__('Plugin Activation Error', 'label-grid-tools'),
                ['back_link' => true]
            );
        }

        // Proceed with installation if requirements are met
        LabelGrid_Tools::lgt_install();
    }
}
