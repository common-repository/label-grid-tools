<?php

trait PluginAdminMenu
{

    public function lgt_getSettingsSlug()
    {
        return $this->plugin_name . '-settings';
    }

    public function lgt_getSyncSlug()
    {
        return $this->plugin_name . '-sync';
    }


    public function lgt_getSystemLogsSlug()
    {
        return $this->plugin_name . '-system-logs';
    }
    
    public function lgt_getGateEntriesSlug()
    {
        return $this->plugin_name . '-gate-entries';
    }

    /**
     * Callback for the admin menu
     *
     * @since 1.0.0
     */
    public function lgt_add_plugin_admin_menu()
    {
        add_menu_page(__('LabelGrid Tools', 'label-grid-tools'), // page title
        __('LabelGrid Tools', 'label-grid-tools'), // menu title
        'manage_options', // capability
        $this->plugin_name, // menu_slug
        '', 'data:image/svg+xml;base64,PHN2ZyBpZD0iTGF5ZXJfMSIgZGF0YS1uYW1lPSJMYXllciAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI5ODEuNzUiIGhlaWdodD0iOTkxIiB2aWV3Qm94PSIwIDAgOTgxLjc1IDk5MSI+ICA8ZGVmcz4gICAgPHN0eWxlPiAgICAgIC5jbHMtMSB7ICAgICAgICBmaWxsOiAjMjMyMzIzOyAgICAgICAgc3Ryb2tlOiAjZmZmOyAgICAgICAgc3Ryb2tlLW1pdGVybGltaXQ6IDEwOyAgICAgIH0gICAgPC9zdHlsZT4gIDwvZGVmcz4gIDx0aXRsZT5sb2dvPC90aXRsZT4gIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTMwNS43LDMwMS4wN0g5LjYzVjVIMzA1LjdaTTY0OCw2OTguOTNIMzUyVjk5NUg2NDhabTM0Mi4zMywwSDY5NC4zVjk5NUg5OTAuMzdabTAtMzQ3SDY5NC4zVjY0OEg5OTAuMzdaTTY0OCw1SDM1MlYzMDEuMDdINjQ4Wk0zMDUuNywzNTJIOS42M1Y2NDhIMzA1LjdabTAsMzQ3SDkuNjNWOTk1SDMwNS43Wk05OTAuMzcsNUg2OTQuM1YzMDEuMDdIOTkwLjM3Wk02NDgsMzUySDM1MlY2NDhINjQ4WiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTkuMTMgLTQuNSkiLz48L3N2Zz4=' // icon
        );

        // Add a submenu page and save the returned hook suffix.
        add_submenu_page($this->plugin_name, // parent slug
        __('General Settings', 'label-grid-tools'), // page title
        __('General Settings', 'label-grid-tools'), // menu title
        'manage_options', // capability
        $this->plugin_name, // menu_slug
        '' // callback for page content
        );

        // Add a submenu page and save the returned hook suffix.
        $html_form_sync_content_hook = add_submenu_page($this->plugin_name, // parent slug
        __('Sync content', 'label-grid-tools'), // page title
        __('Sync content', 'label-grid-tools'), // menu title
        'manage_options', // capability
        $this->lgt_getSyncSlug(), // menu_slug
        array(
            $this,
            'lgt_sync_content_page_content'
        ) // callback for page content
        );

        // Add a submenu page and save the returned hook suffix.
        $html_form_system_logs_hook = add_submenu_page($this->plugin_name, // parent slug
        __('System Logs', 'label-grid-tools'), // page title
        __('System Logs', 'label-grid-tools'), // menu title
        'manage_options', // capability
        $this->lgt_getSystemLogsSlug(), // menu_slug
        array(
            $this,
            'lgt_system_logs_page_content'
        ) // callback for page content
        );
        
        // Add a submenu page and save the returned hook suffix.
        $html_form_system_logs_hook = add_submenu_page($this->plugin_name, // parent slug
            __('Gate Entries', 'label-grid-tools'), // page title
            __('Gate Entries', 'label-grid-tools'), // menu title
            'manage_options', // capability
            $this->lgt_getGateEntriesSlug(), // menu_slug
            array(
                $this,
                'lgt_gate_entries_page_content'
        ) // callback for page content
        );
        
        /*
         * The $page_hook_suffix can be combined with the load-($page_hook) action hook
         * https://codex.wordpress.org/Plugin_API/Action_Reference/load-(page)
         *
         * The callback below will be called when the respective page is loaded
         */
        add_action('load-' . $html_form_sync_content_hook, array(
            $this,
            'lgt_loaded_form_sync_content'
        ));
        add_action('load-' . $html_form_system_logs_hook, array(
            $this,
            'lgt_loaded_form_system_logs'
        ));
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_page_content()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-view.php');
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_general_options()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-general_options-view.php');
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_sync()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-sync-view.php');
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_releases()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-releases-view.php');
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_services()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-services-view.php');
    }

    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML form is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_general_settings_artists()
    {
        // show the form
        include_once (plugin_dir_path(__DIR__) . 'partials/general-settings-artists-view.php');
    }


    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML Ajax is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_sync_content_page_content()
    {
        include_once (plugin_dir_path(__DIR__) . 'partials/sync-page-view.php');
    }

    
    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML Ajax is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_system_logs_page_content()
    {
        include_once (plugin_dir_path(__DIR__) . 'class-system-logs-table.php');
        
        $list_table = new System_Logs_Table();
        $list_table->prepare_items();
        
        
        global $logListTable;
        $option = 'per_page';
        $args = array(
            'label' => 'Log Entries',
            'default' => 25,
            'option' => 'log_entries_per_page'
        );
        add_screen_option( $option, $args );
        //Create an instance of our package class...
        $logListTable = new System_Logs_Table();
        $logListTable->prepare_items();
        
        include_once (plugin_dir_path(__DIR__) . 'partials/system-logs-view.php');
    }
    
    
    /*
     * Callback for the add_submenu_page action hook
     *
     * The plugin's HTML Ajax is loaded from here
     *
     * @since 1.0.0
     */
    public function lgt_gate_entries_page_content()
    {
        include_once (plugin_dir_path(__DIR__) . 'class-gate-entries-table.php');
        
        $list_table = new Gate_Entries_Table();
        $list_table->prepare_items();
        
        
        global $logListTable;
        $option = 'per_page';
        $args = array(
            'label' => 'Gate Entries',
            'default' => 25,
            'option' => 'gate_entries_per_page'
        );
        add_screen_option( $option, $args );
        //Create an instance of our package class...
        $logListTable = new Gate_Entries_Table();
        $logListTable->prepare_items();
        
        include_once (plugin_dir_path(__DIR__) . 'partials/gate-entries-view.php');
    }

    /*
     * Callback for the load-($html_form_page_hook)
     * Called when the plugin's submenu HTML form page is loaded
     *
     * @since 1.0.0
     */
    public function lgt_loaded_form_general_settings()
    {
        // Called when General settings page is loaded
    }

    /*
     * Callback for the load-($ajax_form_page_hook)
     * Called when the plugin's submenu Ajax form page is loaded
     *
     * @since 1.0.0
     */
    public function lgt_loaded_form_sync_content()
    {
        // Called when Sync Content page is loaded
    }

    
    /*
     * Callback for the load-($html_form_page_hook)
     * Called when the plugin's submenu HTML form page is loaded
     *
     * @since 1.0.0
     */
    public function lgt_loaded_form_system_logs()
    {
        // Called when System logs page is loaded
    }
}

?>