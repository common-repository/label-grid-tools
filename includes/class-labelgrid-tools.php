<?php

/**
 * @since      1.0.0
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/includes
 * @author     LabelGrid <team@labelgrid.com>
 */
require_once (plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php');

use Monolog\Logger;
use WordPressHandler\WordPressHandler;

class LabelGrid_Tools
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var LabelGrid_Tools_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    protected $download_gate;

    /**
     * The unique identifier of this plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var string $plugin_name The string used to uniquely identify this plugin.
     */
    public static $plugin_name = 'labelgrid-tools';

    /**
     * The current version of the plugin.
     *
     * @since 1.0.0
     * @access protected
     * @var string $version The current version of the plugin.
     */
    public static $version = LGT_PLUGIN_VERSION;

    /**
     * Services
     *
     * @since 1.2.1
     * @access protected
     * @var string $version The current version of the plugin.
     */
    protected static $link_services = array(
        'spotify' => array(
            'name' => 'Spotify',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'itunes' => array(
            'name' => 'iTunes',
            'linkextra' => '&app=itunes',
            'action' => 'download'
        ),
        'applemusic' => array(
            'name' => 'Apple Music',
            'linkextra' => '&app=music',
            'action' => 'listen'
        ),
        'soundcloud' => array(
            'name' => 'Soundcloud',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'beatport' => array(
            'name' => 'Beatport',
            'linkextra' => '',
            'action' => 'download'
        ),
        'youtube' => array(
            'name' => 'YouTube',
            'linkextra' => '',
            'action' => 'watch'
        ),
        'juno' => array(
            'name' => 'Juno',
            'linkextra' => '',
            'action' => 'download'
        ),
        'deezer' => array(
            'name' => 'Deezer',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'amazon' => array(
            'name' => 'Amazon Music',
            'linkextra' => '',
            'action' => 'download'
        ),
        'tidal' => array(
            'name' => 'Tidal',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'bandcamp' => array(
            'name' => 'Bandcamp',
            'linkextra' => '',
            'action' => 'download'
        ),
        'pandora' => array(
            'name' => 'Pandora',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'yandex' => array(
            'name' => 'Yandex',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'iheartradio' => array(
            'name' => 'iHeartRadio',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'napster' => array(
            'name' => 'Napster',
            'linkextra' => '',
            'action' => 'download'
        ),
        'netease' => array(
            'name' => 'NetEase Music',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'tencentqq' => array(
            'name' => 'QQ',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'tencentku' => array(
            'name' => 'KuGou',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'anghami' => array(
            'name' => 'Anghami',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'awa' => array(
            'name' => 'AWA',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'boomplay' => array(
            'name' => 'Boomplay',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'jiosaavn' => array(
            'name' => 'JioSaavn',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'jaxsta' => array(
            'name' => 'Jaxsta',
            'linkextra' => '',
            'action' => 'listen'
        )
    );

    /**
     * Services
     *
     * @since 1.2.1
     * @access protected
     * @var string $version The current version of the plugin.
     */
    protected static $link_services_artists = array(
        'digitaltunes' => array(
            'name' => 'DigitalTunes',
            'linkextra' => '',
            'action' => ''
        ),
        'facebook' => array(
            'name' => 'Facebook',
            'linkextra' => '',
            'action' => ''
        ),
        'twitter' => array(
            'name' => 'Twitter',
            'linkextra' => '',
            'action' => ''
        ),
        'instagram' => array(
            'name' => 'Instagram',
            'linkextra' => '',
            'action' => ''
        ),
        'spotify' => array(
            'name' => 'Spotify',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'itunes' => array(
            'name' => 'iTunes',
            'linkextra' => '&app=itunes',
            'action' => 'download'
        ),
        'applemusic' => array(
            'name' => 'Apple Music',
            'linkextra' => '&app=music',
            'action' => 'listen'
        ),
        'soundcloud' => array(
            'name' => 'Soundcloud',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'beatport' => array(
            'name' => 'Beatport',
            'linkextra' => '',
            'action' => 'download'
        ),
        'youtube' => array(
            'name' => 'YouTube',
            'linkextra' => '',
            'action' => 'watch'
        ),
        'juno' => array(
            'name' => 'Juno',
            'linkextra' => '',
            'action' => 'download'
        ),
        'deezer' => array(
            'name' => 'Deezer',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'amazon' => array(
            'name' => 'Amazon Music',
            'linkextra' => '',
            'action' => 'download'
        ),
        'tidal' => array(
            'name' => 'Tidal',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'bandcamp' => array(
            'name' => 'Bandcamp',
            'linkextra' => '',
            'action' => 'download'
        ),
        'pandora' => array(
            'name' => 'Pandora',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'yandex' => array(
            'name' => 'Yandex',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'iheartradio' => array(
            'name' => 'iHeartRadio',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'napster' => array(
            'name' => 'Napster',
            'linkextra' => '',
            'action' => 'download'
        ),
        'netease' => array(
            'name' => 'NetEase Music',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'tencentqq' => array(
            'name' => 'QQ',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'tencentku' => array(
            'name' => 'KuGou',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'anghami' => array(
            'name' => 'Anghami',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'awa' => array(
            'name' => 'AWA',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'boomplay' => array(
            'name' => 'Boomplay',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'jiosaavn' => array(
            'name' => 'JioSaavn',
            'linkextra' => '',
            'action' => 'listen'
        ),
        'jaxsta' => array(
            'name' => 'Jaxsta',
            'linkextra' => '',
            'action' => 'listen'
        )
    );

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (! defined('LGT_PLUGIN_VERSION')) {
            LabelGrid_Tools::$version = '0.0.0';
        }
        $this->load_dependencies();
        $this->set_locale();
        if (class_exists('LGT_Download_Gate')) {
            $this->download_gate = new LGT_Download_Gate($this->loader);
        }
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->run();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LabelGrid_Tools_Loader. Orchestrates the hooks of the plugin.
     * - LabelGrid_Tools_i18n. Defines internationalization functionality.
     * - LabelGrid_Tools_Admin. Defines all hooks for the admin area.
     * - LabelGrid_Tools_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since 1.0.0
     * @access private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-labelgrid-tools-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-labelgrid-tools-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-labelgrid-tools-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-labelgrid-tools-public.php';

        $this->loader = new LabelGrid_Tools_Loader();

        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'class-download-gate.php') && get_option('_lgt_is_active', 'no') == "yes")
            require_once (plugin_dir_path(dirname(__FILE__)) . 'class-download-gate.php');
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the LabelGrid_Tools_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since 1.0.0
     * @access private
     */
    private function set_locale()
    {
        $plugin_i18n = new LabelGrid_Tools_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    public function lgt_load_extra_admin()
    {
        \Carbon_Fields\Carbon_Fields::boot();

        if (class_exists('LGT_Download_Gate')) {
            $this->download_gate->init();
        }
    }

    public function lgt_load_extra()
    {
        if (class_exists('LGT_Download_Gate')) {
            $this->download_gate->init();
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_admin_hooks()
    {
        add_image_size('lgt_artwork_smaller', 80, 80, true);
        add_image_size('lgt_artwork_small', 250, 250, true);
        add_image_size('lgt_artwork_medium', 500, 500, true);
        add_image_size('lgt_artwork_big', 1000, 1000, true);

        $this->loader->add_action('plugins_loaded', $this, 'lgt_load_extra_admin');

        $plugin_admin = new LabelGrid_Tools_Admin();

        $this->loader->add_action('plugins_loaded', $plugin_admin, 'lgt_update_version_check');

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'lgt_enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'lgt_enqueue_scripts');

        $this->loader->add_action('admin_menu', $plugin_admin, 'lgt_add_plugin_admin_menu');

        // Update last edit date
        $this->loader->add_action('save_post', $plugin_admin, 'lgt_save_content_callback');
        $this->loader->add_action('trashed_post', $plugin_admin, 'lgt_save_content_callback');
        $this->loader->add_action('delete_post', $plugin_admin, 'lgt_save_content_callback');

        // sync-form response
        $this->loader->add_action('wp_ajax_lgt_sync', $plugin_admin, 'lgt_sync_form_response');

        // update now response
        $this->loader->add_action('wp_ajax_lgt_sync_once', $plugin_admin, 'lgt_sync_catalog_cron');

        // Register admin notices
        $this->loader->add_action('admin_notices', $plugin_admin, 'lgt_print_plugin_admin_notices');

        // register custom_type
        $this->loader->add_action('init', $plugin_admin, 'lgt_custom_type_init');

        // add buttons to top release and artist page
        $this->loader->add_action('admin_head', $plugin_admin, 'lgt_custom_js_to_head');

        // add custom post type to search
        $this->loader->add_action('pre_get_posts', $plugin_admin, 'lgt_search_filter');

        // add admin menu bar
        $this->loader->add_action('admin_bar_menu', $plugin_admin, 'lgt_add_admin_bar_menu', 200);

        // CRON schedules
        add_filter('cron_schedules', array(
            $plugin_admin,
            'lgt_cron_add_schedules'
        ));

        // Cron Hooks
        $this->loader->add_action('lgt_sync_catalog', $plugin_admin, 'lgt_sync_catalog_cron');
        $this->loader->add_action('lgt_check_lg_api', $plugin_admin, 'lgt_check_lg_api');
        $this->loader->add_action('lgt_plugin_stats', $plugin_admin, 'lgt_plugin_stats');
        $this->loader->add_action('lgt_log_cleaning', $plugin_admin, 'lgt_log_cleaning');

        // Enable CRON Events
        $this->schedule_cron_action('lgt_sync_catalog', 'lg-cron-6-hours');
        $this->schedule_cron_action('lgt_check_lg_api', 'daily');
        $this->schedule_cron_action('lgt_plugin_stats', 'daily');
        $this->schedule_cron_action('lgt_log_cleaning', 'daily');

        // api endpoints
        $this->loader->add_action('rest_api_init', $plugin_admin, 'lgt_register_api_endpoints');

        $this->loader->add_action('carbon_fields_register_fields', $plugin_admin, 'lgt_add_custom_type_fields');
        $this->loader->add_action('carbon_fields_theme_options_container_saved', $plugin_admin, 'lgt_update_api_checks_force');

        // register releases rss
        $this->loader->add_action('init', $plugin_admin, 'lgt_add_endpoint');
        $this->loader->add_filter('template_include', $plugin_admin, 'lgt_include_print_template');
    }

    private function schedule_cron_action($action_name, $schedule)
    {
        if (wp_get_schedule($action_name) !== $schedule) {
            if ($time = wp_next_scheduled($action_name)) {
                wp_unschedule_event($time, $action_name);
            }
            wp_schedule_event(time(), $schedule, $action_name);
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_public_hooks()
    {
        $plugin_public = new LabelGrid_Tools_Public();
        $this->loader->add_action('init', $plugin_public, 'lgt_add_rewrite_lite');
        $this->loader->add_action('query_vars', $plugin_public, 'lgt_declare_query_vars');

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'lgt_enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'lgt_enqueue_scripts');

        // SHORTCODES
        $this->loader->add_shortcode('labelgrid-release-list', $plugin_public, 'lgt_labelgrid_release_list');
        $this->loader->add_shortcode('labelgrid-release-list-filter', $plugin_public, 'lgt_labelgrid_release_list_filter');
        $this->loader->add_shortcode('labelgrid-release-banner', $plugin_public, 'lgt_labelgrid_release_banner');
        $this->loader->add_shortcode('labelgrid-release-links', $plugin_public, 'lgt_labelgrid_release_links');
        $this->loader->add_shortcode('labelgrid-artist-list', $plugin_public, 'lgt_labelgrid_artist_list');
        $this->loader->add_shortcode('labelgrid-artist-detail', $plugin_public, 'lgt_artist_detail_html');
        $this->loader->add_shortcode('labelgrid-release-detail', $plugin_public, 'lgt_release_detail_html');

        $this->loader->add_action('the_content', $plugin_public, 'lgt_new_default_content');

        $this->loader->add_filter('template_include', $plugin_public, 'lgt_links_page_template');
        
        include_once(ABSPATH.'wp-admin/includes/plugin.php');
        if ( function_exists('is_plugin_active') && ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) )) {
            $this->loader->add_filter('wpseo_opengraph_type', $plugin_public, 'lgt_yoast_opengraph_type',10, 2);
        }

        $this->loader->add_action('rest_api_init', $plugin_public, 'lgt_register_api_endpoints');
        
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since 1.0.0
     * @return string The name of the plugin.
     */
    public static function get_plugin_name()
    {
        return self::$plugin_name;
    }

    /**
     * Return $link_services
     *
     * @since 2.1.2
     * @return string The name of the plugin.
     */
    public static function get_link_services($type, $include_custom = false)
    {
        global $wpdb;

        if ($type == 'releases')
            $default_services = self::$link_services;
        elseif ($type == 'artists')
            $default_services = self::$link_services_artists;

        if ($include_custom == false) {
            return $default_services;
        } else {
            $custom_services = array();

            $total_custom = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(option_id) as total FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC",
                    '_lgt_custom_services|||%|value'
                )
            );

            $i = 0;
            while ($i <= $total_custom) {
                $id = strtolower(get_option('_lgt_custom_services|id|' . $i . '|0|value'));
                $name = get_option('_lgt_custom_services|name|' . $i . '|0|value');
                $action = get_option('_lgt_custom_services|action|' . $i . '|0|value');
                $image = get_option('_lgt_custom_services|image|' . $i . '|0|value');
                $extra = get_option('_lgt_custom_services|extra|' . $i . '|0|value');
                $visibility = get_option('_lgt_custom_services|visibility|' . $i . '|0|value');

                if($visibility==$type || $visibility=="artists_releases")
                    $custom_services[$id]= array('name'=>$name,'linkextra'=>$extra,'action'=>$action,'image'=>$image);
                
                $i++;
            }
            return array_merge($default_services,$custom_services);
        }
    }
    
    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since 1.0.0
     * @return LabelGrid_Tools_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since 1.0.0
     * @return string The version number of the plugin.
     */
    public static function get_version()
    {
        return self::$version;
    }
    

    static public function lgt_prefixTableName($name, $noprefix = null)
    {
        global $wpdb;
        $table_name = str_replace("-", "_", strtolower(LabelGrid_Tools::$plugin_name . '-' . $name));

        if (empty($noprefix))
            $table_name = $wpdb->prefix . $table_name;
        else
            $table_name = $table_name;

        return $table_name;
    }

    
    static public function log_event($event, $type, $context = 'system', $extra = array())
    {
        global $wpdb;
        $debug_type = (string) get_option('_lgt_debug');
        if (!empty($debug_type) && $debug_type != 'disabled') {
            $wordPressHandler = new WordPressHandler($wpdb, LabelGrid_Tools::lgt_prefixTableName('logs', true), array(), (int) constant('Monolog\Logger::' . $debug_type));
            
            // setup array of extra fields
            $record = [
                'extra' => array()
            ];
            // creates database table if needed, add extra fields from above
            $wordPressHandler->initialize($record);
            
            // Create logger
            $logger = new Logger($context);
            $logger->pushHandler($wordPressHandler);
            
            $logger->$type($event, array());
        }
    }
    
    static public function getLogger()
    {
        global $wpdb;
        $wordPressHandler = new WordPressHandler($wpdb, LabelGrid_Tools::lgt_prefixTableName('logs', true), array(), (int) constant('Monolog\Logger::DEBUG'));
            
        // setup array of extra fields
        $record = [
            'extra' => array()
        ];
        // creates database table if needed, add extra fields from above
        $wordPressHandler->initialize($record);
            
        // Create logger
        $logger = new Logger('curl');
        $logger->pushHandler($wordPressHandler);
        return $logger;
    }
    
    static public function guzzleLoggingMiddleware(string $messageFormat = 'REQUEST- STATUS: {code}, METHOD: {method}, URL: {uri}, HTTP/{version}, PAYLOAD: {req_body}, BODY: {res_body}'){
        $debug_type = (string) get_option('_lgt_debug');
        if ($debug_type == 'DEBUG') return \GuzzleHttp\Middleware::log(LabelGrid_Tools::getLogger(), new \GuzzleHttp\MessageFormatter($messageFormat),Monolog\Logger::DEBUG);
        else null;
    }
    

    
    public static function lgt_install()
    {
        global $wpdb;
        
        $installed_version = get_option("lgt_db_version",1);
        
        // Set default values
        if (empty(get_option('_lgt_gate_welcome_title')))
            update_option('_lgt_gate_welcome_title', __('Free download', 'label-grid-tools'));
        if (empty(get_option('_lgt_gate_button_label')))
            update_option('_lgt_gate_button_label', __('Free download', 'label-grid-tools'));
        if (empty(get_option('_lgt_gate_welcome_description')))
            update_option('_lgt_gate_welcome_description', __('We will perform the following actions for you and then unlock the Free Download.', 'label-grid-tools'));
        if (empty(get_option('_lgt_gate_wizard_button_label')))
            update_option('_lgt_gate_wizard_button_label', __('Download', 'label-grid-tools'));
        if (empty(get_option('_lgt_show_below_artwork')))
            update_option('_lgt_show_below_artwork','releasecode');
        if (empty(get_option('_lgt_debug')))
            update_option('_lgt_debug','ERROR');

        $charset_collate = $wpdb->get_charset_collate();
        
        $tableName_gate_entries = LabelGrid_Tools::lgt_prefixTableName('gate_entries');
        if ($wpdb->get_var("SHOW TABLES LIKE '" . $tableName_gate_entries . "'") != $tableName_gate_entries) {
            
            $sql_gate_entries = "CREATE TABLE `" . $tableName_gate_entries . "` (
  						`id` INTEGER  NOT NULL AUTO_INCREMENT,
  						`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  						`email` varchar(100) NULL DEFAULT NULL,
  						`gate_id` INTEGER NOT NULL,
                        `session_id` varchar(255) NOT NULL,
  						PRIMARY KEY (`id`)
						) " . $charset_collate;
            $wpdb->query($sql_gate_entries);
            
            LabelGrid_Tools::log_event('Create table '.$tableName_gate_entries, 'debug', 'gate');
            
        }
        
        if (version_compare($installed_version, '1.2.0', '<')){
            $wpdb->query("ALTER TABLE `" . $tableName_gate_entries . "` ADD `type` INT NOT NULL DEFAULT '0' AFTER `session_id`");
            LabelGrid_Tools::log_event("Database Update: ALTER TABLE `" . $tableName_gate_entries . "` ADD `type` INT NOT NULL DEFAULT '0' AFTER `session_id`", 'debug', 'gate');
        }
        
        if (version_compare($installed_version, '1.2.5', '<')){
            $wpdb->query("ALTER TABLE `" . $tableName_gate_entries . "` ADD `connected_services` INT NULL DEFAULT NULL AFTER `type`");
            $wpdb->query("ALTER TABLE `" . $tableName_gate_entries . "` CHANGE `email` `email` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL");
            LabelGrid_Tools::log_event("Database Update: ALTER TABLE `" . $tableName_gate_entries . "` ADD `connected_services` INT NULL DEFAULT NULL AFTER `type`", 'debug', 'gate');
        }
        
        
        $count_services = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."options WHERE `option_name` LIKE '_services_order|%'");
        if($count_services==0){
           
            $sql_services =  "INSERT INTO `".$wpdb->prefix."options` ( `option_name`, `option_value`, `autoload`) VALUES
            ('_services_order|||24|value', 'jaxsta', 'no'),
            ('_services_order|||23|value', 'jiosaavn', 'no'),
            ('_services_order|||22|value', 'boomplay', 'no'),
            ('_services_order|||21|value', 'awa', 'no'),
            ('_services_order|||20|value', 'anghami', 'no'),
            ('_services_order|||19|value', 'akazoo', 'no'),
            ('_services_order|||18|value', 'tencentku', 'no'),
            ('_services_order|||17|value', 'tencentqq', 'no'),
            ('_services_order|||16|value', 'netease', 'no'),
            ('_services_order|||15|value', 'napster', 'no'),
            ('_services_order|||14|value', 'ihearthradio', 'no'),
            ('_services_order|||13|value', 'yandex', 'no'),
            ('_services_order|||12|value', 'pandora', 'no'),
            ('_services_order|||11|value', 'bandcamp', 'no'),
            ('_services_order|||10|value', 'tidal', 'no'),
            ('_services_order|||9|value', 'amazon', 'no'),
            ('_services_order|||8|value', 'deezer', 'no'),
            ('_services_order|||7|value', 'juno', 'no'),
            ('_services_order|||5|value', 'beatport', 'no'),
            ('_services_order|||4|value', 'youtube', 'no'),
            ('_services_order|||3|value', 'soundcloud', 'no'),
            ('_services_order|||2|value', 'applemusic', 'no'),
            ('_services_order|||1|value', 'itunes', 'no'),
            ('_services_order|||0|value', 'spotify', 'no');
            ";
            $wpdb->query($sql_services);
        }
        
        
        $count_services = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."options WHERE `option_name` LIKE '_services_order_artists|%'");
        if($count_services==0){
            
            $sql_services =  "INSERT INTO `".$wpdb->prefix."options` ( `option_name`, `option_value`, `autoload`) VALUES
            ('_services_order_artists|||27|value', 'jaxsta', 'no'),
            ('_services_order_artists|||26|value', 'jiosaavn', 'no'),
            ('_services_order_artists|||25|value', 'boomplay', 'no'),
            ('_services_order_artists|||24|value', 'awa', 'no'),
            ('_services_order_artists|||23|value', 'anghami', 'no'),
            ('_services_order_artists|||22|value', 'akazoo', 'no'),
            ('_services_order_artists|||21|value', 'tencentku', 'no'),
            ('_services_order_artists|||20|value', 'tencentqq', 'no'),
            ('_services_order_artists|||19|value', 'digitaltunes', 'no'),
            ('_services_order_artists|||18|value', 'netease', 'no'),
            ('_services_order_artists|||17|value', 'napster', 'no'),
            ('_services_order_artists|||16|value', 'ihearthradio', 'no'),
            ('_services_order_artists|||15|value', 'yandex', 'no'),
            ('_services_order_artists|||14|value', 'pandora', 'no'),
            ('_services_order_artists|||13|value', 'bandcamp', 'no'),
            ('_services_order_artists|||12|value', 'tidal', 'no'),
            ('_services_order_artists|||11|value', 'deezer', 'no'),
            ('_services_order_artists|||10|value', 'juno', 'no'),
            ('_services_order_artists|||8|value', 'beatport', 'no'),
            ('_services_order_artists|||7|value', 'youtube', 'no'),
            ('_services_order_artists|||6|value', 'soundcloud', 'no'),
            ('_services_order_artists|||5|value', 'applemusic', 'no'),
            ('_services_order_artists|||4|value', 'itunes', 'no'),
            ('_services_order_artists|||3|value', 'spotify', 'no'),
            ('_services_order_artists|||2|value', 'instagram', 'no'),
            ('_services_order_artists|||1|value', 'twitter', 'no'),
            ('_services_order_artists|||0|value', 'facebook', 'no');
            ";
            $wpdb->query($sql_services);
        }

        
        update_option("lgt_db_version", LabelGrid_Tools::$version);
        
        
        flush_rewrite_rules(false);
        
    }
}
