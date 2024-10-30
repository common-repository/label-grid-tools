<?php

/**
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 * @author     LabelGrid <team@labelgrid.com>
 */
require_once('traits/custom-type-forms.php');
require_once('traits/plugin-admin-menu.php');
require_once('traits/sync-page.php');

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class LabelGrid_Tools_Admin
{
    use CustomTypeForms;
    use PluginAdminMenu;
    use SyncPage;

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $plugin_name The ID of this plugin.
     */
    public $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $version The current version of this plugin.
     */
    public $version;

    /**
     * Is active the plugin
     *
     * @since 1.0.0
     * @access private
     * @var string $version The current version of this plugin.
     */
    private $is_active;

    /**
     * Debug type
     *
     * @since 1.0.0
     * @access private
     * @var string $version The current version of this plugin.
     */
    private $debug_type;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name
     *            The name of this plugin.
     * @param string $version
     *            The version of this plugin.
     */
    public function __construct()
    {
        $this->plugin_name = LabelGrid_Tools::get_plugin_name();
        $this->version = LabelGrid_Tools::get_version();
        $this->is_active = get_option('_lgt_is_active');
        $this->debug_type = get_option('_lgt_debug');
    }

    public function lgt_update_version_check()
    {
        if (get_option('lgt_db_version') != $this->version) {
            LabelGrid_Tools::lgt_install();
        }
    }

    public function lgt_update_api_checks()
    {
        $this->is_active = $this->lgt_check_lg_api();
    }

    public function lgt_update_api_checks_force()
    {
        $this->is_active = $this->lgt_check_lg_api(true);
        $this->lgt_plugin_stats();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function lgt_enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/labelgrid-tools-admin.min.css', array(), $this->version, 'all');

        if (is_admin_bar_showing()) {
            wp_enqueue_style($this->plugin_name . '-admin-toolbar', plugin_dir_url(__FILE__) . 'css/labelgrid-tools-admin-toolbar.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function lgt_enqueue_scripts()
    {
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php')
        );
        wp_enqueue_script('lgt_ajax_handle', plugin_dir_url(__FILE__) . 'js/labelgrid-tools-admin.js', array(
            'wp-i18n',
            'jquery'
        ), $this->version, false);

        if (is_admin_bar_showing()) {
            wp_enqueue_script('lgt_admin_toolbar', plugin_dir_url(__FILE__) . 'js/labelgrid-tools-admin-toolbar.js', array(
                'wp-i18n',
                'jquery'
            ), $this->version, false);
            wp_localize_script('lgt_admin_toolbar', 'lgbar', array(
                'ajaxurl' => admin_url('admin-ajax.php')
            ));
        }

        wp_localize_script('lgt_ajax_handle', 'params', $params);

        wp_enqueue_script('jquery');
    }

    public function lgt_add_admin_bar_menu(\WP_Admin_Bar $wp_admin_bar)
    {
        $iconspan = '<span class="labelgrid-toolbar-icon"></span>';

        $args = array(
            'id' => 'labelgrid_tools',
            'title' => $iconspan . __('LabelGrid Tools', 'label-grid-tools'),
            'meta' => array(
                'class' => 'labelgrid-toolbar-group'
            )
        );
        $wp_admin_bar->add_node($args);

        $args = array();

        array_push($args, array(
            'id' => 'update_catalog',
            'title' => __('Manual Update Catalog', 'label-grid-tools'),
            'parent' => 'labelgrid_tools',
            'href' => '#',
            'meta' => array(
                'class' => 'labelgrid-toolbar-update-catalog'
            )
        ));

        sort($args);

        for ($a = 0; $a < sizeOf($args); $a++) {
            $wp_admin_bar->add_node($args[$a]);
        }
    }

    /**
     * Redirect
     *
     * @since 1.0.0
     */
    public function lgt_custom_redirect($admin_notice, $response)
    {
        wp_redirect(esc_url_raw(add_query_arg(array(
            'lgt_admin_add_notice' => $admin_notice,
            'lgt_response' => $response
        ), admin_url('admin.php?page=' . $this->plugin_name))));
    }

    /**
     * Print Admin Notices
     *
     * @since 1.0.0
     */
    public function lgt_print_plugin_admin_notices()
    {
        if ($this->is_active === 'no') {
            $api = 1;
        }

        if (isset($api)) {
            // Build the notice message with fully escaped output
            $html = '<div class="notice notice-error is-dismissible"><br>';

            if (isset($api)) {
                $html .= '<p>' . esc_html__('LabelGrid API DISABLED:', 'label-grid-tools') . ' '
                    . esc_html__('Verify the API Token is correct and that your account is enabled for LabelGrid API Services.', 'label-grid-tools')
                    . ' <a href="' . esc_url(admin_url('admin.php?page=labelgrid-tools')) . '">'
                    . esc_html__('Set API in General Settings', 'label-grid-tools')
                    . '</a></p>';
            }

            $html .= '<br></div>';

            // Print the escaped HTML notice
            echo wp_kses_post($html);
        }
    }

    /**
     * Query MySQL DB for its version
     *
     * @return string|false
     */
    protected function lgt_getMySqlVersion()
    {
        global $wpdb;
        $rows = $wpdb->get_results('select version() as mysqlversion');
        if (!empty($rows)) {
            return $rows[0]->mysqlversion;
        }
        return false;
    }

    public function lgt_custom_js_to_head()
    {
?>
        <script type="text/javascript">
            jQuery(function() {
                // Append the 'Sync Releases' button
                jQuery("body.edit-php.post-type-release .wrap h1").append(
                    '<a href="<?php echo esc_url(admin_url('admin.php?page=labelgrid-tools-sync')); ?>" class="page-title-action"><?php echo esc_js(__('Sync Releases with LabelGrid', 'label-grid-tools')); ?></a>'
                );

                // Append the 'Sync Artists' button
                jQuery("body.edit-php.post-type-artist .wrap h1").append(
                    '<a href="<?php echo esc_url(admin_url('admin.php?page=labelgrid-tools-sync')); ?>" class="page-title-action"><?php echo esc_js(__('Sync Artists with LabelGrid', 'label-grid-tools')); ?></a>'
                );
            });
        </script>
<?php
    }

    public function lgt_search_filter($query)
    {
        if (!is_admin() && $query->is_main_query()) {
            if ($query->is_search) {
                $query->set('post_type', array(
                    'post',
                    'page',
                    'release',
                    'artist'
                ));
            }
        }
    }

    public function lgt_cron_add_schedules($schedules)
    {
        $schedules['lg-cron-10-minutes'] = array(
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display' => __('Every 10 minutes', 'label-grid-tools')
        );
        $schedules['lg-cron-20-minutes'] = array(
            'interval' => 20 * MINUTE_IN_SECONDS,
            'display' => __('Every 20 minutes', 'label-grid-tools')
        );
        $schedules['lg-cron-30-minutes'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 minutes', 'label-grid-tools')
        );
        $schedules['lg-cron-120-minutes'] = array(
            'interval' => 120 * MINUTE_IN_SECONDS,
            'display' => __('Every 2 hours', 'label-grid-tools')
        );
        $schedules['lg-cron-6-hours'] = array(
            'interval' => 360 * MINUTE_IN_SECONDS,
            'display' => __('Every 6 hours', 'label-grid-tools')
        );
        return $schedules;
    }

    public function lgt_sync_catalog_cron()
    {
        LabelGrid_Tools::log_event('CATALOG SYNC - Default Schedule', 'debug', 'cron');
        $sync_auto = get_option('_lgt_automatic_sync');
        $first_import = get_option('_lgt_first_import');


        if ($sync_auto != 'disabled' && $first_import == true) {
            LabelGrid_Tools::log_event('CATALOG SYNC - Start', 'debug', 'cron');
            require_once(plugin_dir_path(__DIR__) . 'admin/class-sync-content-data.php');
            $import = new SyncContentData(1, null, true);
            $import->lgt_process_silent_update();
            $response = 1;
        } elseif ($first_import == false) {
            $response = 2;
        } else {
            $response = 0;
            LabelGrid_Tools::log_event('CATALOG SYNC - Notice: Automatic Sync is disabled in General Settings. Sync will be not executed.', 'debug', 'cron');
        }

        echo json_encode($response);
        wp_die();
    }

    public function lgt_checkStatus()
    {
        return $this->is_active;
    }


    public function lgt_register_api_endpoints() {}


    public function lgt_check_lg_api($force = null)
    {
        LabelGrid_Tools::log_event('VALIDATE: LabelGrid API', 'debug', 'cron');

        $gate_api_access_token = get_option('_lgt_api_key');
        $lgt_curl_interface = get_option('_lgt_curl_interface');

        $apistatus = 'no';

        if (!empty($gate_api_access_token) && ($this->is_active == "yes" || $force == true)) {
            try {

                $stack = HandlerStack::create();
                $stack->push(GuzzleRetryMiddleware::factory());

                if ($gmiddle = LabelGrid_Tools::guzzleLoggingMiddleware()) $stack->push($gmiddle);

                $client = new Client(['verify' => false, 'handler' => $stack]);

                if ($this->debug_type == "DEBUG")  $stemp = fopen('php://temp', 'rw');

                if ($lgt_curl_interface != "1") $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];

                $response = $client->request('GET', 'https://gate.labelgrid.com/api/gate/user-credentials', [
                    'curl' => !empty($curlinterface) ? $curlinterface : null,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $gate_api_access_token
                    ],
                    'debug' => !empty($stemp) ? $stemp : null
                ]);

                $code = $response->getStatusCode(); // 200

                $body = (string) $response->getBody();
                $res = json_decode($body);

                if ($code == 200) {

                    if ($res->data->status == 'authenticated')
                        $apistatus = 'yes';
                }
            } catch (RequestException $e) {

                $response_status = '';
                $response_phrase = '';
                $response_body = '';
                $response_headers = '';
                $response_request = '';

                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $response_status = $response->getStatusCode();
                    $response_phrase = $response->getReasonPhrase();
                    $response_body = (string) $response->getBody();
                    $response_headers = $response->getHeader('Content-Type')[0];
                }

                $response_request = Psr7\str($e->getRequest());

                LabelGrid_Tools::log_event('VALIDATE - ERROR - Status code: ' . $response_status . ' | Response: ' . $response_phrase . ' | Body: ' . $response_body . ' |  Headers: ' . $response_headers . ' |  Request: ' . $response_request, 'error', 'cron');
            }


            if ($this->debug_type == "DEBUG") {
                fseek($stemp, 0);
                LabelGrid_Tools::log_event('VALIDATE - Request debug: ' . fread($stemp, 1024), 'debug', 'curl');
            }
        } else LabelGrid_Tools::log_event('VALIDATE: LabelGrid API - SKIP', 'debug', 'cron');

        update_option('_lgt_is_active', $apistatus);

        return $apistatus;
    }

    public function lgt_log_cleaning()
    {

        LabelGrid_Tools::log_event('Log Cleaning routine', 'debug', 'cron');

        global $wpdb;

        $retention = get_option('_lgt_debug_retention_days');

        if (is_numeric($retention) && $retention != 0)
            $offset = $retention * 24;
        else
            $offset = 12;

        $sql = "DELETE FROM " . LabelGrid_Tools::lgt_prefixTableName('logs') . " WHERE time < UNIX_TIMESTAMP(DATE_ADD(NOW(),INTERVAL -" . $offset . " HOUR))";
        LabelGrid_Tools::log_event('Log Cleaning routine. Retention: ' . $retention, 'debug', 'cron');
        $wpdb->query($sql);
    }

    public function lgt_plugin_stats()
    {
        try {
            $stack = HandlerStack::create();
            $stack->push(GuzzleRetryMiddleware::factory());

            $lgt_curl_interface = get_option('_lgt_curl_interface');
            if ($lgt_curl_interface != "1") $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];

            $client = new Client(['handler' => $stack]);

            $response = $client->request('POST', 'https://gate.labelgrid.com/gate/stats', [
                'curl' => !empty($curlinterface) ? $curlinterface : null,
                'form_params' => [
                    'host' => get_site_url(),
                    'version' => LabelGrid_Tools::$version
                ]
            ]);

            $res = json_decode((string) $response->getBody());

            if (!empty($res->data->outboundip)) update_option('_lgt_outboundip', $res->data->outboundip);
        } catch (RequestException $e) {
            LabelGrid_Tools::log_event('STATS - Error: ' . Psr7\str($e->getRequest()), 'error', 'cron');
        }
    }

    public function lgt_add_endpoint()
    {
        add_rewrite_endpoint('releases-feed', EP_ROOT);
    }

    public function lgt_include_print_template($template)
    {
        get_query_var('releases-feed');
        if (false === get_query_var('releases-feed', false)) {
            return $template;
        }

        $posts = $this->lgt_releases_json_array();

        wp_send_json(array(
            'posts' => $posts
        ));
    }

    public function lgt_releases_json_array()
    {
        if (!empty($_REQUEST['releases']) && $_REQUEST['releases'] == "future") {
            $slg = ">";
            $order = 'ASC';
        } else {
            $slg = "<=";
            $order = 'DESC';
        }

        if (!empty($_REQUEST['limit-date']))
            $args = array(
                'post_type' => 'release',
                'posts_per_page' => 3,
                'orderby' => 'meta_value',
                'order' => $order,
                'meta_key' => '_lgt_release_date',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_lgt_release_date',
                        'value' => $_REQUEST['limit-date'],
                        'compare' => '=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_lgt_show_list',
                        'value' => 'no',
                        'compare' => '='
                    )
                )
            );
        elseif (!empty($_REQUEST['label-name']))
            $args = array(
                'post_type' => 'release',
                'posts_per_page' => 3,
                'orderby' => 'meta_value',
                'order' => $order,
                'meta_key' => '_lgt_release_date',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_lgt_release_date',
                        'value' => date('Y-m-d'),
                        'compare' => $slg,
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_lgt_show_list',
                        'value' => 'no',
                        'compare' => '='
                    )
                ),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'record_label',
                        'terms' => $_REQUEST['label-name'],
                        'field' => 'name'
                    )
                )
            );

        else
            $args = array(
                'post_type' => 'release',
                'posts_per_page' => 3,
                'orderby' => 'meta_value',
                'order' => $order,
                'meta_key' => '_lgt_release_date',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => '_lgt_release_date',
                        'value' => date('Y-m-d'),
                        'compare' => $slg,
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => '_lgt_show_list',
                        'value' => 'no',
                        'compare' => '='
                    )
                )
            );

        $random_posts_raw = get_posts($args);

        // Load the date format set in General > Settings to
        // return post date properly
        //$date_format = get_option('date_format');

        $random_posts = [];
        foreach ($random_posts_raw as $post_raw) {

            $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id($post_raw->ID), 'thumb-square');
            $title = apply_filters('the_title', $post_raw->post_title);
            $content = apply_filters('the_content', $post_raw->post_content);
            $label = wp_get_post_terms($post_raw->ID, 'record_label', array(
                'fields' => 'names'
            ));

            $random_posts[] = array(
                'id' => $post_raw->ID,
                'link' => get_the_permalink($post_raw->ID),
                'title' => $title,
                'content' => $content,
                'release_date' => get_post_meta($post_raw->ID, '_lgt_release_date', true),
                'release_code' => get_post_meta($post_raw->ID, '_lgt_cat_number', true),
                'short_link' => get_post_meta($post_raw->ID, '_lgt_short_url', true),
                'image' => $image_attributes[0],
                'genres' => '',
                'label' => $label[0]
            );
        }

        return $random_posts;
    }


    public function lgt_save_content_callback($post_id)
    {
        global $post;

        $gmt_wp_date = gmdate("Y-m-d h:i:s", time() + 3600 * (get_option('gmt_offset') + date("I")));

        if (!empty($post->post_type)) {
            if ($post->post_type == 'release') update_option('_lgt_release_lastedit', $gmt_wp_date);
            if ($post->post_type == 'artist') update_option('_lgt_artist_lastedit', $gmt_wp_date);
        }
    }
}
