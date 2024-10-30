<?php

/**
 *
 * @package LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 * @author LabelGrid <team@labelgrid.com>
 */

use Carbon_Fields\Container\Container;
use Carbon_Fields\Field\Field;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class LGT_Download_Gate
{

    private $loader;

    private $gate_api_is_active;

    private $lgt_admin;

    public $table_name_gate_entries;

    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $this->plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $version The current version of this plugin.
     */
    private $version;

    private $gate_actions;

    private $debug_type;

    public function __construct($loader)
    {
        $this->loader = $loader;

        $this->plugin_name = LabelGrid_Tools::get_plugin_name();
        $this->version = LabelGrid_Tools::get_version();
        $this->gate_api_is_active = get_option('_lgt_is_active');
        $this->debug_type = get_option('_lgt_debug');
        $this->table_name_gate_entries = LabelGrid_Tools::lgt_prefixTableName('gate_entries');
    }

    public function init()
    {
        if ($this->gate_api_is_active == 'yes') {
            $this->loader->add_action('plugins_loaded', $this, 'lgt_start_session', 1);
            /*
             * $this->loader->add_action('wp_logout', $this, 'lgt_clean_session', 1);
             * $this->loader->add_action('wp_login', $this, 'lgt_clean_session', 1);
             * $this->loader->add_action('end_session_action', $this, 'lgt_clean_session', 1);
             */
            $this->loader->add_action('init', $this, 'lgt_export_entries');
            $this->loader->add_action('init', $this, 'lgt_gate_register_custom_type');
            $this->loader->add_action('init', $this, 'lgt_gate_rewrites_init');
            $this->loader->add_action('template_redirect', $this, 'lgt_gate_template_redirect_intercept');
            $this->loader->add_action('wp_enqueue_scripts', $this, 'lgt_gate_enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $this, 'lgt_gate_enqueue_scripts');
            $this->loader->add_action('rest_api_init', $this, 'lgt_gate_register_api_endpoints');
            $this->loader->add_action('the_content', $this, 'lgt_gate_default_content');

            $this->loader->add_filter('manage_gate_download_posts_columns', $this, 'lgt_gate_set_custom_gate_columns');
            $this->loader->add_filter('manage_edit-gate_download_sortable_columns', $this, 'lgt_gate_sortable_column');
            $this->loader->add_action('manage_gate_download_posts_custom_column', $this, 'lgt_gate_custom_gate_column', 10, 2);

            $this->loader->add_shortcode('labelgrid-gate-button', $this, 'lgt_labelgrid_gate_button_html');
            $this->loader->add_shortcode('labelgrid-gate-download-list', $this, 'lgt_labelgrid_gate_download_list');
            $this->loader->add_shortcode('labelgrid-gate-download-detail', $this, 'lgt_gate_download_detail_html');
            $this->loader->add_shortcode('labelgrid-presave-button', $this, 'lgt_labelgrid_spotify_presave_button');

            $this->loader->run();

            $this->lgt_gate_admin_menu();

            $this->lgt_gate_post_details();
        }
    }

    /**
     * Register the JavaScript for the frontend area.
     *
     * @since 1.0.0
     */
    public function lgt_gate_enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name . '-gate', plugin_dir_url(__FILE__) . 'public/js/labelgrid-tools-public-gate.js', array(
            'jquery',
            'wp-i18n',
            'jquery-ui-dialog'
        ), $this->version, false);

        wp_set_script_translations($this->plugin_name . '-gate', 'label-grid-tools');

        wp_localize_script('wp-api', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ));
        wp_enqueue_script('wp-api');
    }

    /**
     * Register the stylesheets for the frontend area.
     *
     * @since 1.0.0
     */
    public function lgt_gate_enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name . '-gate', plugin_dir_url(__FILE__) . 'public/css/labelgrid-tools-public-gate.min.css', array(), $this->version, 'all');

        $wp_scripts = wp_scripts();
        wp_enqueue_style('lgt_ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/jquery-ui.css', false, $this->version, false);
    }

    public function lgt_gate_default_content($content)
    {
        global $post;
        if (!empty($post->post_type) && $post->post_type == 'gate_download') {
            $content = $this->lgt_gate_download_detail_html();
        }
        return $content;
    }

    public function lgt_gate_register_api_endpoints()
    {
        register_rest_route('lgt-gate-api/v1', '/add-session/', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'lgt_gate_add_session_callback'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/save-email/', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'lg_gate_save_email'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/check-session/', array(
            'methods' => 'GET',
            'callback' => array(
                $this,
                'lgt_check_session_json'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/add-presave/', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'lgt_gate_add_presave'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/mobile-check/', array(
            'methods' => 'GET',
            'callback' => array(
                $this,
                'lgt_mobile_check'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/get-actions/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(
                $this,
                'lgt_gate_get_actions'
            )
        ));

        register_rest_route('lgt-gate-api/v1', '/get-playlists/', array(
            'methods' => 'POST',
            'callback' => array(
                $this,
                'lgt_gate_get_playlists'
            )
        ));
    }

    public function lg_gate_save_email(WP_REST_Request $request)
    {
        global $wpdb;
        $response = array();

        $email = isset($request['email']) ? trim($request['email']) : "";

        if (isset($request['gate_id']) && strpos($request['gate_id'], "p") === false) {
            $type = 0;
            $gate_id = isset($request['gate_id']) ? trim($request['gate_id']) : "";
        } else {
            $type = 1;
            $gate_id = isset($request['gate_id']) ? trim($request['gate_id'], 'p') : "";
        }

        if (! empty($email) && ! empty($gate_id)) {
            $session = $this->lgt_check_session();
            $oldemail = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$this->table_name_gate_entries} WHERE email = %s AND gate_id = %d AND session_id = %s",
                    $email,
                    $gate_id,
                    $session
                )
            );

            if (empty($oldemail->id))
                $wpdb->insert($this->table_name_gate_entries, array(
                    'email' => $email,
                    'gate_id' => $gate_id,
                    'session_id' => $session,
                    'type' => $type
                ));

            $response['message'] = "ok";
        } else
            $response['message'] = "ko";

        return rest_ensure_response($response);
    }

    public function lgt_gate_add_presave(WP_REST_Request $request)
    {
        $response = array();
        $task_action = $request['task_action'];
        $playlist_id = $request['playlist_id'];
        $release_upc = $request['release_upc'];
        $release_id = $request['release_id'];
        $release_date = $request['release_date'];

        if (!empty($task_action) && !empty($release_upc) && !empty($release_id) && !empty($release_date)) {
            $session = $this->lgt_check_session();
            $response['message'] = "ok";
            $this->gate_api_is_active = get_option('_lgt_is_active');

            if ($this->gate_api_is_active == 'yes') {
                $gate_api_access_token = get_option('_lgt_api_key');
                try {
                    $stack = HandlerStack::create();
                    $stack->push(GuzzleRetryMiddleware::factory());

                    $lgt_curl_interface = get_option('_lgt_curl_interface');
                    $client = new Client(['verify' => false, 'handler' => $stack]);

                    // Conditional initialization of WP_Filesystem for debug mode
                    $temp_file_path = null;
                    if ($this->debug_type === "DEBUG") {
                        global $wp_filesystem;
                        if (!function_exists('WP_Filesystem')) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                        }
                        WP_Filesystem();
                        $temp_file_path = wp_tempnam();
                    }

                    if ($lgt_curl_interface != "1") {
                        $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];
                    }

                    $addpresave = $client->request('POST', 'https://gate.labelgrid.com/api/gate/add-presave', [
                        'curl' => !empty($curlinterface) ? $curlinterface : null,
                        'headers' => [
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $gate_api_access_token
                        ],
                        'form_params' => [
                            'task_action' => $task_action,
                            'playlist_id' => $playlist_id,
                            'release_upc' => $release_upc,
                            'release_id' => $release_id,
                            'release_date' => $release_date,
                            'user_id' => $session
                        ],
                        'debug' => !empty($temp_file_path) ? $temp_file_path : null
                    ]);

                    $code = $addpresave->getStatusCode(); // 200

                    if ($code == 200) {
                        // Process response if needed
                    }
                } catch (RequestException $e) {
                    $response['message'] = "ko";
                    LabelGrid_Tools::log_event('Add Presave error: ' . Psr7\str($e->getRequest()), 'error', 'gate');
                }

                // Log debug output if in DEBUG mode
                if ($this->debug_type === "DEBUG" && $temp_file_path && $wp_filesystem->exists($temp_file_path)) {
                    $log_content = $wp_filesystem->get_contents($temp_file_path);
                    LabelGrid_Tools::log_event('Add Presave debug log: ' . $log_content, 'debug', 'curl');
                    $wp_filesystem->delete($temp_file_path); // Clean up temp file
                }
            }
        } else {
            $response['message'] = "ko";
        }

        return rest_ensure_response($response);
    }


    public function lgt_gate_register_rewrite_download()
    {
        add_rewrite_rule('lgtools-downloadgate/?([^/]*)/?([^/]*)/?', 'index.php?lgtools-retrieve-download=1&user_id=$matches[1]&gate_id=$matches[2]', 'top');
    }

    public function lgt_gate_template_redirect_intercept()
    {
        global $wp_query;
        if ($wp_query->get('lgtools-retrieve-download') && $wp_query->get('user_id') && $wp_query->get('gate_id')) {
            $params['user_id'] = $wp_query->get('user_id');
            $params['gate_id'] = $wp_query->get('gate_id');
            $this->lgt_gate_return_download_callback($params);
            die();
        }
    }

    public function lgt_gate_rewrites_init()
    {
        add_rewrite_tag('%lgtools-retrieve-download%', '([0-9]+)');
        add_rewrite_tag('%user_id%', '([0-9]+)');
        add_rewrite_tag('%gate_id%', '([0-9]+)');
        $this->lgt_gate_register_rewrite_download();
    }

    public function lgt_start_session()
    {
        if (! session_id())
            session_start();
    }

    public function lgt_clean_session()
    {
        session_destroy();
    }

    public function lgt_check_session()
    {
        $this->lgt_start_session();

        return sha1(session_id());
    }

    public function lgt_check_session_json(WP_REST_Request $request)
    {
        $response['session'] = $this->lgt_check_session();
        return rest_ensure_response($response);
    }

    public function lgt_gate_set_custom_gate_columns($columns)
    {
        $columns['download_stat'] = __('Downloads', 'label-grid-tools');
        $columns['email_saved'] = __('E-Mails', 'label-grid-tools');
        $columns['shortcode'] = __('Shortcode', 'label-grid-tools');
        return $columns;
    }

    public function lgt_gate_custom_gate_column($column, $post_id)
    {
        switch ($column) {
            case 'download_stat':
                $n = get_post_meta($post_id, '_download_stat', true);
                if (! empty($n))
                    echo esc_html($n);
                else
                    echo '0';
                break;
            case 'email_saved':
                echo '<a href="admin.php?page=labelgrid-tools-gate-entries&type-filter=0&gate-filter=' . esc_attr($post_id) . '">Check E-mails (' . esc_html($this->lgt_gate_custom_gate_column_emails($post_id)) . ')</a>';
                break;
            case 'shortcode':
                echo '<input type="text" readonly="readonly" value="[labelgrid-gate-button gate-id=\'' . esc_attr($post_id) . '\']">';
                break;
        }
    }

    private function lgt_gate_custom_gate_column_emails($post_id)
    {
        global $wpdb;
        $num = 0;
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name_gate_entries} WHERE gate_id = %d AND type = %d",
            $post_id,
            0
        );
        $num = $wpdb->get_var($count_query);

        return $num;
    }

    public function lgt_labelgrid_spotify_presave_button($atts)
    {
        // Attributes
        $atts = shortcode_atts(array(
            'release-id' => get_the_ID()
        ), $atts, 'labelgrid-presave-button');

        // PRESAVE SPOTIFY
        $release_data['presave_active'] = get_post_meta($atts['release-id'], '_lgt_spotify_presave', true);
        if (empty($release_data['presave_active']) || $release_data['presave_active'] == "-")
            $release_data['presave_active'] = get_option('_lgt_spotify_pre_save');

        $html = "";
        $presave_active = 1;

        $release_data['upc'] = get_post_meta($atts['release-id'], '_lgt_release_upc', true);
        $release_data['isrc'] = get_post_meta($atts['release-id'], '_lgt_release_isrc', true);
        $release_data['release_date'] = get_post_meta($atts['release-id'], '_lgt_release_date', true);

        $this->presave_title = __("PRE-SAVE ON SPOTIFY", 'label-grid-tools');
        $this->presave_description = __("Connect your Spotify account and choose a playlist where you would like to save the track/s.", 'label-grid-tools');
        $this->presave_collect_email = carbon_get_post_meta($atts['release-id'], 'lgt_spotify_presave_email');
        $this->presave_actions = carbon_get_post_meta($atts['release-id'], 'lgt_presave_accounts_follow');

        // check if current date passed
        if (($release_data['release_date'] . " 00:00:00") <= date("Y-m-d 00:00:00"))
            $presave_active = 0;

        if ($presave_active == 1) {
            $link_image = esc_url(plugins_url('public/images/ico_spotify.svg', __FILE__));
            $html = '<div class="linkTop spotify"><a style="background-image:url(\'' . $link_image . '\');" href="#"" title="' . __("Pre-Save on", 'label-grid-tools') . ' Spotify" class="spotify-save" id="lgt-presave-link">' . __("Pre-Save on", 'label-grid-tools') . ' Spotify</a></div>';

            $presave_actions_labels = $this->lgt_spotify_presave_actions($this->presave_actions);

            if (! empty($presave_actions_labels)) {
                $this->presave_description .= "<div class='notelimit'>" . __("We will perform also the following actions:", 'label-grid-tools') . "</div>";
            }

            // modal
            $html .= '<div class="container" id="modalwindow-presave">
                    <h2>' . $this->presave_title . '</h2>
                    <p id="popdescription">' . $this->presave_description . '</p>
                        <div id="content">
                            <div class="gate_option_container"><div class="gate_option_data_container">' . $presave_actions_labels . '</div></div>
                            <div class="privacydisplay">' . __("By proceeding, you are agreeing to our", 'label-grid-tools') . ' ' . get_the_privacy_policy_link() . '</div>
                        </div>
                        <div class="buttonP"><button class="btn btn-primary" id="btn-gate-continue">' . __("Continue", 'label-grid-tools') . '</button></div>
                    </div>';

            $html .= '<script id="lgt_playlist_template_spotify" type="text/x-handlebars-template">';
            $html .= '<div id="playlistdata">';
            $html .= '<table>';
            $html .= '{{#items}}';
            $html .= '<tr>';
            $html .= '<td><img src="{{images.0.url}}" width="50"></td>';
            $html .= '<td><a href="#" data-playlistid="{{id}}" class="playlistadd">{{name}}</td>';
            $html .= '<td></td>';
            $html .= '</tr>';
            $html .= '{{/items}}';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '</script>';

            // variables
            $html .= "<script>";
            $html .= "var lgt_presave_actions = " . wp_json_encode($this->presave_actions) . ";";
            $html .= "var lgt_page_id = '" . $atts['release-id'] . "';";
            $html .= "var lgt_gate_id = 'p" . $atts['release-id'] . "';";
            $html .= "var lgt_release_upc = '" . $release_data['upc'] . "';";
            $html .= "var lgt_release_date = '" . $release_data['release_date'] . "';";
            $html .= "var lgt_install_url = '" . get_site_url() . "';";
            $html .= "var lgt_collect_email = '" . $this->presave_collect_email . "';";
            $html .= "</script>";

            return $html;
        } else
            return false;
    }

    private function lgt_spotify_presave_actions($actions)
    {
        $html = '';
        if (is_array($actions)) {
            foreach ($actions as $action) {
                switch ($action['_type']) {
                    case 'playlist_follow':
                        $label = __('Follow Playlist', 'label-grid-tools');
                        break;
                    case 'user_follow':
                        $label = __('Follow User', 'label-grid-tools');
                        break;
                    case 'artist_follow':
                        $label = __('Follow Artist', 'label-grid-tools');
                        break;
                    case 'track_save':
                        $label = __('Save Track', 'label-grid-tools');
                        break;
                    case 'album_save':
                        $label = __('Save Album', 'label-grid-tools');
                        break;
                }
                if ($action === end($actions))
                    $comma = '';
                else
                    $comma = ', ';

                $html .= '<div class="gate_option_desc"><a href="' . $action['link'] . '" target="_blank">' . $label . '</a>' . $comma . '</div>';
            }
        }
        return $html;
    }

    public function lgt_gate_sortable_column($cols)
    {
        $cols['download_stat'] = '_download_stat';
        return $cols;
    }

    public function lgt_gate_add_session_callback(WP_REST_Request $request)
    {
        $response['message'] = "ok";
        $this->gate_api_is_active = get_option('_lgt_is_active');
        if ($this->gate_api_is_active == 'yes') {

            if (empty($request['user_id']) || empty($request['gate_id']))
                die();

            $gate_api_access_token = get_option('_lgt_api_key');
            try {
                $stack = HandlerStack::create();
                $stack->push(GuzzleRetryMiddleware::factory());

                $lgt_curl_interface = get_option('_lgt_curl_interface');
                if ($lgt_curl_interface != "1") $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];

                $client = new Client(['handler' => $stack]);

                $addsession = $client->request('POST', 'https://gate.labelgrid.com/api/gate/add-session', [
                    'curl' => !empty($curlinterface) ? $curlinterface : null,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $gate_api_access_token
                    ],
                    'form_params' => [
                        'user_id' => $request['user_id'],
                        'gate_id' => $request['gate_id'],
                        'return_url' => get_site_url()
                    ]
                ]);

                $code = $addsession->getStatusCode(); // 200

                if ($code == 200) {
                }
            } catch (RequestException $e) {
                $response['message'] = "ko";
                LabelGrid_Tools::log_event('Add Session error: ' . Psr7\str($e->getRequest()), 'error', 'gate');
            }
        } else
            $response['message'] = "ko";

        return rest_ensure_response($response);
    }

    public function lgt_gate_return_download_callback($parameters)
    {
        $this->gate_api_is_active = get_option('_lgt_is_active');
        if ($this->gate_api_is_active == 'yes') {
            // $parameters = $request_data->get_params();
            if (empty($parameters['user_id']) || empty($parameters['gate_id']) || $parameters['user_id'] != $this->lgt_check_session())
                die();

            $services = $this->lgt_labelgrid_gate_options(carbon_get_post_meta($parameters['gate_id'], 'lgt_gate_accounts_follow'), true);
            $lgt_gate_min_accounts = carbon_get_post_meta($parameters['gate_id'], 'lgt_gate_min_accounts');
            $lgt_gate_download_source = carbon_get_post_meta($parameters['gate_id'], 'lgt_gate_download_source');

            $gate_api_access_token = get_option('_lgt_api_key');
            try {
                $stack = HandlerStack::create();
                $stack->push(GuzzleRetryMiddleware::factory());

                $lgt_curl_interface = get_option('_lgt_curl_interface');
                if ($lgt_curl_interface != "1") $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];

                $client = new Client(['handler' => $stack]);

                $response = $client->request('POST', 'https://gate.labelgrid.com/api/gate/verify-session', [
                    'curl' => !empty($curlinterface) ? $curlinterface : null,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $gate_api_access_token
                    ],
                    'form_params' => [
                        'user_id' => $parameters['user_id'],
                        'gate_id' => $parameters['gate_id'],
                        'services' => $services,
                        'min_accounts' => $lgt_gate_min_accounts
                    ]
                ]);

                $code = $response->getStatusCode(); // 200
                if ($code == 200) {
                    $body = (string) $response->getBody();
                    $res = json_decode($body);
                    if ($res->data->status == 'ok') {
                        $this->lgt_gate_add_stats($parameters['gate_id']);

                        if ($lgt_gate_download_source == "register_contest")
                            $this->lgt_gate_save_contest_entry($parameters['gate_id'], $this->lgt_check_session(), $res->data->connected_services);
                        else
                            $this->lgt_gate_generate_download_link($parameters['gate_id']);
                    }
                } else
                    LabelGrid_Tools::log_event('Verify download error.', 'error', 'gate');
            } catch (RequestException $e) {
                LabelGrid_Tools::log_event('Verify download error: ' . Psr7\str($e->getRequest()), 'error', 'gate');
            }
        }
    }

    private function lgt_gate_save_contest_entry($gate_id, $session_id, $connected_services)
    {
        global $wpdb;

        $data['gate_id'] = $gate_id;
        $data['session_id'] = $session_id;
        $datanew['connected_services'] = $connected_services;
        $datat = array_merge($datanew, $data);

        $data = $wpdb->update($this->table_name_gate_entries, $datanew, $data);
        if ($data < 1)
            $wpdb->insert($this->table_name_gate_entries, $datat);
    }

    public function lgt_gate_add_stats($gate_id)
    {
        $download_stat = get_post_meta($gate_id, '_download_stat', true);
        if (empty($download_stat) && $download_stat != 0)
            add_post_meta($gate_id, '_download_stat', '0');
        else
            update_post_meta($gate_id, '_download_stat', ++$download_stat);
    }

    public function url_exists($url)
    {
        if (! curl_init($url))
            return false;
        return true;
    }

    public function lgt_gate_generate_download_link($gate_id)
    {
        // w3tc cache fix
        define('DONOTCACHEPAGE', true);

        $source_download = carbon_get_post_meta($gate_id, 'lgt_gate_download_source');
        LabelGrid_Tools::log_event('Verify download. | source download ' . esc_html($source_download) . ' | gate id: ' . esc_html($gate_id), 'debug', 'gate');

        switch ($source_download) {
            case 'forward_url':
                $source_file = esc_url_raw(carbon_get_post_meta($gate_id, 'lgt_gate_external_link_forward'));
                if (! headers_sent()) {
                    header("Location: " . $source_file);
                } else {
                    echo '<script type="text/javascript">document.location.href="' . esc_js($source_file) . '";</script>';
                    echo 'Redirecting to <a href="' . esc_url($source_file) . '">' . esc_html($source_file) . '</a>';
                }
                die();
                break;
            case 'upload_file':
                $file_name = get_attached_file(carbon_get_post_meta($gate_id, 'lgt_gate_dowload_file'));
                break;
            case 'download_url':
                $file_name = esc_url_raw(carbon_get_post_meta($gate_id, 'lgt_gate_external_link'));
                break;
        }

        if (empty($file_name) || ! $this->url_exists($file_name)) {
            LabelGrid_Tools::log_event('Verify download. File does not exist | source download ' . esc_html($source_download) . ' | gate id: ' . esc_html($gate_id), 'error', 'gate');
            die();
        }

        if (headers_sent()) {
            LabelGrid_Tools::log_event('Verify download. Header has been sent already. | source download ' . esc_html($source_download) . ' | gate id: ' . esc_html($gate_id), 'error', 'gate');
        }

        LabelGrid_Tools::log_event('Verify download. Serving file. | source download ' . esc_html($source_download) . ' | gate id: ' . esc_html($gate_id), 'debug', 'gate');

        // Headers for file download
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_name)) . ' GMT');
        header('Cache-Control: private', false);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . esc_attr(basename($file_name)) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file_name));
        header('Connection: close');

        session_write_close();
        readfile($file_name); // Serve the file

        die();
    }


    public function lgt_gate_admin_menu()
    {
        Container::make('theme_options', __('Download Gate', 'label-grid-tools'))->set_page_file('labelgrid-tools-download-gate-settings')
            ->set_page_parent('labelgrid-tools')
            ->add_tab(__('Gate Page Settings', 'label-grid-tools'), array(
                Field::make('select', 'lgt_gate_show_press', __('Press Release', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true),

                Field::make('select', 'lgt_gate_show_artists', __('Show featured Artists', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
            ))
            ->add_tab(__('Labels', 'label-grid-tools'), array(
                Field::make('text', 'lgt_gate_button_label', __('Button Label', 'label-grid-tools'))->set_help_text(__('This field can be overridden in each Gate Download details.', 'label-grid-tools'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_welcome_title', __('Welcome title', 'label-grid-tools'))->set_help_text(__('This field can be overridden in each Gate Download details.', 'label-grid-tools'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_welcome_description', __('Welcome description', 'label-grid-tools'))->set_help_text(__('This field can be overridden in each Gate Download details.', 'label-grid-tools'))
                    ->set_required(true),

                Field::make('text', '_lgt_gate_wizard_button_label', __('Gate button label', 'label-grid-tools'))->set_help_text(__('This field can be overridden in each Gate Download details.', 'label-grid-tools'))
                    ->set_required(true)
            ));
    }

    public function lgt_gate_check_api()
    {
        return $this->gate_api_is_active;
    }

    public function lgt_gate_register_custom_type()
    {

        // Release
        register_post_type('gate_download', array(
            'labels' => array(
                'name' => __('Gate Downloads', 'label-grid-tools'),
                'singular_name' => __('Gate Download', 'label-grid-tools'),
                'add_new_item' => __('Add New Gate Download', 'label-grid-tools'),
                'edit_item' => __('Edit Gate Download', 'label-grid-tools'),
                'new_item' => __('New Gate Download', 'label-grid-tools'),
                'view_item' => __('View Gate Download', 'label-grid-tools'),
                'view_items' => __('View Gate Downloads', 'label-grid-tools'),
                'search_items' => __('Search Gate Downloads', 'label-grid-tools'),
                'not_found' => __('No Gate Download Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Gate Downloads found in Trash', 'label-grid-tools'),
                'all_items' => __('All Gate Downloads', 'label-grid-tools')
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'free-download'
            ),
            'capability_type' => 'page',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => array(
                'title'
            ),
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg aria-hidden="true" data-prefix="fas" data-icon="cloud-download-alt" class="svg-inline--fa fa-cloud-download-alt fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M537.6 226.6c4.1-10.7 6.4-22.4 6.4-34.6 0-53-43-96-96-96-19.7 0-38.1 6-53.3 16.2C367 64.2 315.3 32 256 32c-88.4 0-160 71.6-160 160 0 2.7.1 5.4.2 8.1C40.2 219.8 0 273.2 0 336c0 79.5 64.5 144 144 144h368c70.7 0 128-57.3 128-128 0-61.9-44-113.6-102.4-125.4zm-132.9 88.7L299.3 420.7c-6.2 6.2-16.4 6.2-22.6 0L171.3 315.3c-10.1-10.1-2.9-27.3 11.3-27.3H248V176c0-8.8 7.2-16 16-16h48c8.8 0 16 7.2 16 16v112h65.4c14.2 0 21.4 17.2 11.3 27.3z"></path></svg>'),
            'taxonomies' => array(
                'gate_category'
            )
        ));

        // Taxonomy - Artist Tag
        register_taxonomy('gate_category', array(
            'gate_download'
        ), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Gate Categories', 'label-grid-tools'),
                'singular_name' => __('Gate Category', 'label-grid-tools'),
                'add_new_item' => __('Add New Gate Category', 'label-grid-tools'),
                'edit_item' => __('Edit Gate Category', 'label-grid-tools'),
                'new_item' => __('New Gate Category', 'label-grid-tools'),
                'view_item' => __('View Gate Category', 'label-grid-tools'),
                'view_items' => __('View Gate Categories', 'label-grid-tools'),
                'search_items' => __('Search Gate Categories', 'label-grid-tools'),
                'not_found' => __('No Gate Category Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Gate Categories found in Trash', 'label-grid-tools'),
                'all_items' => __('All Gate Categories', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'gate_category'
            )
        ));
    }

    public function lgt_gate_post_details()
    {
        Container::make('post_meta', 'Gate Downloads')->where('post_type', '=', 'gate_download')
            ->add_tab(__('General', 'label-grid-tools'), array(

                Field::make('select', 'lgt_gate_collect_email', __('Collect E-mail', 'label-grid-tools'))->set_options(array(
                    'no' => __('No', 'label-grid-tools'),
                    'yes-obligatory' => __('Yes - Obligatory', 'label-grid-tools'),
                    'yes-optional' => __('Yes - Optional', 'label-grid-tools')
                ))
                    ->set_required(true),
                Field::make('select', 'lgt_gate_contents', __('Choose Content Source', 'label-grid-tools'))->set_options(array(
                    '' => __('Select a Content Source', 'label-grid-tools'),
                    'upload_file' => __('Upload a Artwork and Description', 'label-grid-tools'),
                    'match_release' => __('Match with existing Release', 'label-grid-tools')
                ))
                    ->set_required(true),
                Field::make('association', 'lgt_gate_linked_release', __('Associate this Download to a Release:', 'label-grid-tools'))->set_types(array(
                    array(
                        'type' => 'post',
                        'post_type' => 'release'
                    )
                ))
                    ->set_conditional_logic(array(
                        'relation' => 'AND',
                        array(
                            'field' => 'lgt_gate_contents',
                            'value' => 'match_release', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                    ))
                    ->set_required(true),
                Field::make('image', 'lgt_gate_cover_image', __('Artwork', 'label-grid-tools'))->set_help_text(__('This field will be discarded in case the Download is associated to a Release', 'label-grid-tools'))
                    ->set_conditional_logic(array(
                        'relation' => 'AND',
                        array(
                            'field' => 'lgt_gate_contents',
                            'value' => 'upload_file', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                    ))
                    ->set_required(true),
                Field::make('rich_text', 'lgt_gate_description', __('Description', 'label-grid-tools'))->set_rows(5)
                    ->set_conditional_logic(array(
                        'relation' => 'AND',
                        array(
                            'field' => 'lgt_gate_contents',
                            'value' => 'upload_file', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                    ))
                    ->set_required(true)
            ))
            ->add_tab(__('File', 'label-grid-tools'), array(
                Field::make('select', 'lgt_gate_download_source', __('Choose Download Source', 'label-grid-tools'))->set_options(array(
                    '' => __('Select a Download Type', 'label-grid-tools'),
                    'upload_file' => __('FILE - Upload a file', 'label-grid-tools'),
                    'download_url' => __('FILE - Local Path or URL', 'label-grid-tools'),
                    'forward_url' => __('URL - Forward to URL', 'label-grid-tools'),
                    'register_contest' => __('Register users for contest', 'label-grid-tools')
                ))
                    ->set_required(true),
                Field::make('file', 'lgt_gate_dowload_file', __('Upload file', 'label-grid-tools'))->set_conditional_logic(array(
                    'relation' => 'AND',
                    array(
                        'field' => 'lgt_gate_download_source',
                        'value' => 'upload_file', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                        'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                    )
                ))
                    ->set_required(true),
                Field::make('text', 'lgt_gate_external_link', __('Local Path or URL', 'label-grid-tools'))->set_conditional_logic(array(
                    'relation' => 'AND', // Optional, defaults to "AND"
                    array(
                        'field' => 'lgt_gate_download_source',
                        'value' => 'download_url', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                        'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                    )
                ))
                    ->set_help_text(__('Full Linux/Windows Path or internal/external URL are accepted.', 'label-grid-tools'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_external_link_forward', __('Forward to URL', 'label-grid-tools'))
                    ->set_attribute('type', 'url')
                    ->set_conditional_logic(array(
                        'relation' => 'AND',
                        array(
                            'field' => 'lgt_gate_download_source',
                            'value' => 'forward_url', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                    ))
                    ->set_help_text(__('URL is accepted. Please include https://', 'label-grid-tools'))
                    ->set_required(true)
            ))
            ->add_tab(__('Download Settings', 'label-grid-tools'), array(

                Field::make('select', 'lgt_gate_min_accounts', __('Min. Accounts Required', 'label-grid-tools'))->set_options(array(
                    'all' => __('All accounts required', 'label-grid-tools'),
                    '3' => 3,
                    '2' => 2,
                    '1' => 1
                ))
                    ->set_help_text(__('You can limit the minimum number of accounts required to complete the gate.', 'label-grid-tools'))
                    ->set_required(true),

                Field::make('complex', 'lgt_gate_accounts_follow', '')->set_required(true)
                    ->set_duplicate_groups_allowed(false)
                    ->add_fields('spotify', __('SPOTIFY', 'label-grid-tools'), array(
                        Field::make('complex', 'actions', __('Actions to perform:', 'label-grid-tools'))->set_required(true)
                            ->add_fields('playlist_follow', __('Follow Playlist', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Playlist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('user_follow', __('Follow User', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('User URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('artist_follow', __('Follow Artist', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Artist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('track_save', __('Save Track', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Playlist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('album_save', __('Save Album', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Playlist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                    ))
                    ->add_fields('twitter', __('TWITTER', 'label-grid-tools'), array(
                        Field::make('complex', 'actions', __('Actions to perform:', 'label-grid-tools'))->set_required(true)
                            ->add_fields('account_follow', __('Follow Account', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Twitter Account', 'label-grid-tools'))->set_help_text(__('URL or Username accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                    ))
                    /*    ->add_fields('youtube', __('YOUTUBE', 'label-grid-tools'), array(
                Field::make('complex', 'actions', __('Actions to perform:', 'label-grid-tools'))->set_required(true)
                    ->add_fields('account_follow', __('Follow Account', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Youtube Account ID', 'label-grid-tools'))->set_help_text(__('Youtube ID.One record per line.', 'label-grid-tools'))
                        ->set_required(true)
                        ->set_rows(4)
                ))
                    ->add_fields('video_like', __('Like Video', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Youtube Video ID', 'label-grid-tools'))->set_help_text(__('Youtube Video ID.One record per line.', 'label-grid-tools'))
                        ->set_required(true)
                        ->set_rows(4)
                ))
            ))*/
                    ->add_fields('soundcloud', __('SOUNDCLOUD', 'label-grid-tools'), array(
                        Field::make('complex', 'actions', __('Actions to perform:', 'label-grid-tools'))->set_required(true)
                            ->add_fields('account_follow', __('Follow Account', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Soundcloud Account', 'label-grid-tools'))->set_help_text(__('Full URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('track_repost', __('Track Repost', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Soundcloud Track', 'label-grid-tools'))->set_help_text(__('Full URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('track_save', __('Track Save', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Soundcloud Track', 'label-grid-tools'))->set_help_text(__('Full URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('playlist_repost', __('Playlist Repost', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Soundcloud Playlist', 'label-grid-tools'))->set_help_text(__('Full URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                            ->add_fields('playlist_save', __('Playlist Save', 'label-grid-tools'), array(
                                Field::make('textarea', 'link', __('Soundcloud Playlist', 'label-grid-tools'))->set_help_text(__('Full URL accepted.One record per line.', 'label-grid-tools'))
                                    ->set_required(true)
                                    ->set_rows(4)
                            ))
                    ))
            ))
            ->add_tab(__('Labels', 'label-grid-tools'), array(
                Field::make('text', 'lgt_gate_button_label', __('Button Label', 'label-grid-tools'))->set_default_value(get_option('_lgt_gate_button_label'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_welcome_title', __('Welcome title', 'label-grid-tools'))->set_default_value(get_option('_lgt_gate_welcome_title'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_welcome_description', __('Welcome description', 'label-grid-tools'))->set_default_value(get_option('_lgt_gate_welcome_description'))
                    ->set_required(true),

                Field::make('text', 'lgt_gate_wizard_button_label', __('Gate Download Button label', 'label-grid-tools'))->set_default_value(get_option('_lgt_gate_wizard_button_label'))
                    ->set_required(true)
            ));
    }

    public function lgt_labelgrid_gate_button_html($atts)
    {
        global $post;

        // Attributes
        $atts = shortcode_atts(array(
            'gate-id' => get_the_ID(),
            'post-id' => $post->ID,
            'button-label' => null,
            'custom-class' => null,
            'download-text' => null
        ), $atts, 'labelgrid-gate-button');

        if (! empty($atts['button-label']))
            $this->gate_label = $atts['button-label'];
        else
            $this->gate_label = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_button_label');

        if (! empty($atts['download-text']))
            $this->file_download_text = esc_html($atts['download-text']);
        else
            $this->file_download_text = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_wizard_button_label');

        $this->gate_title = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_welcome_title');
        $this->gate_description = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_welcome_description');
        $this->gate_collect_email = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_collect_email');
        $this->lgt_gate_min_accounts = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_min_accounts');
        $this->gate_actions = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_accounts_follow');
        $this->download_source = carbon_get_post_meta($atts['gate-id'], 'lgt_gate_download_source');

        if ($this->lgt_gate_min_accounts != "all")
            $minaccounts = $this->lgt_gate_min_accounts;
        else
            $minaccounts = count($this->gate_actions);

        $html = '';

        if (! empty($atts['custom-class']))
            $html .= '<a href="#"  target="blank" title="' . $this->gate_label . '" class="' . $atts['custom-class'] . '" id="lgt-freedownload-link">' . $this->gate_label . '</a>';
        else
            $html .= '<div class="linkTop freedownload"><a href="#"  target="blank" title="' . $this->gate_label . '" class="free_download_gate" id="lgt-freedownload-link">' . $this->gate_label . '</a></div>';

        // modal
        $html .= '<div class="container" id="modalwindow-freedownload">
    					<h2>' . $this->gate_title . '</h2>
    					<p id="popdescription">' . $this->gate_description . '</p>
    					<div id="content">
    			';

        $html .= '<p id="maxaccountsdescription">' . __("You need to connect only ", 'label-grid-tools') . $minaccounts . __(" accounts from the following services:", 'label-grid-tools') . '</p>';

        $html .= '		<div class="gate_option_container">' . $this->lgt_labelgrid_gate_options($this->gate_actions) . '</div>
    					<div class="privacydisplay">' . __("By proceeding, you are agreeing to our", 'label-grid-tools') . ' ' . get_the_privacy_policy_link() . '</div>
    					</div>
    					<div class="buttonP"><button class="btn btn-primary" id="btn-gate-continue">' . __("Continue", 'label-grid-tools') . '</button></div>
						</div>
						';

        // variables
        $html .= "<script>";
        $html .= "var lgt_gate_actions = " . wp_json_encode($this->gate_actions) . ";";
        $html .= "var lgt_gate_services = " . wp_json_encode(array_values($this->services)) . ";";
        $html .= "var lgt_page_id = " . $atts['post-id'] . ";";
        $html .= "var lgt_gate_id = " . $atts['gate-id'] . ";";
        $html .= "var lgt_install_url = '" . get_site_url() . "';";
        $html .= "var lgt_download_text = '" . $this->file_download_text . "';";
        $html .= "var lgt_collect_email = '" . $this->gate_collect_email . "';";
        $html .= "var lgt_gate_min_accounts = '" . $this->lgt_gate_min_accounts . "';";
        $html .= "var lgt_gate_download_type = '" . $this->download_source . "';";
        $html .= "</script>";

        return $html;
    }

    public function lgt_labelgrid_gate_options($gate_options, $strict = false)
    {
        $html = '';
        foreach ($gate_options as $option) {
            $html .= '<div class="gate_option_data_container">';

            $service[] = $option['_type'];
            $html .= '<div class="gate_option_title">' . $option['_type'] . ' <a href="#lg' . $option['_type'] . 'actions">' . __("Show all actions performed", 'label-grid-tools') . '</a></div>';

            $html .= '<div id="lg' . $option['_type'] . 'actions" class="lg-gate-actions-details">';

            foreach ($option['actions'] as $actiontmp) {

                $actionf = explode("\r\n", $actiontmp['link']);
                foreach ($actionf as $actionlink) {
                    switch ($actiontmp['_type']) {
                        case 'playlist_follow':
                            $label = __('Follow Playlist', 'label-grid-tools');
                            break;
                        case 'user_follow':
                            $label = __('Follow User', 'label-grid-tools');
                            break;
                        case 'artist_follow':
                            $label = __('Follow Artist', 'label-grid-tools');
                            break;
                        case 'track_save':
                            $label = __('Save Track', 'label-grid-tools');
                            break;
                        case 'album_save':
                            $label = __('Save Album', 'label-grid-tools');
                            break;
                        case 'account_follow':
                            $label = __('Follow Account', 'label-grid-tools');
                            break;
                        case 'track_repost':
                            $label = __('Repost track', 'label-grid-tools');
                            break;
                        case 'playlist_repost':
                            $label = __('Repost playlist', 'label-grid-tools');
                            break;
                        case 'playlist_save':
                            $label = __('Save playlist', 'label-grid-tools');
                            break;
                        case 'like_page':
                            $label = __('Like Page', 'label-grid-tools');
                            break;
                        case 'video_like':
                            $label = __('Like Video', 'label-grid-tools');
                            break;
                    }
                    if (filter_var($actionlink, FILTER_VALIDATE_URL) === FALSE)
                        $link = $this->lgt_labelgrid_get_clean_urls($actionlink, $actiontmp['_type'], $option['_type']);
                    else
                        $link = $actionlink;
                    $html .= '<div class="gate_option_desc"><a href="' . $link . '" target="_blank">' . $label . '</a></div>';
                }
            }
            $html .= '</div>';
            $html .= '</div>';

            $this->services = array_unique($service);
        }

        if ($strict === true)
            return array_unique($service);
        else
            return $html;
    }

    private function lgt_labelgrid_get_clean_urls($url, $action, $type)
    {
        switch ($type) {
            case 'spotify':
                $parsed = parse_url($url);

                if ($parsed['scheme'] == 'spotify') {
                    $urldata = explode(":", $url);
                    $link = "https://open.spotify.com/" . $urldata[1] . "/" . $urldata[2];
                } else
                    $link = $url;
                break;
                /*case 'youtube':
                if ($action == 'account_follow')
                    $link = "https://www.youtube.com/channel/" . $url;
                elseif ($action == 'video_like')
                    $link = "https://www.youtube.com/watch?v=" . $url;
                break;
            */
            case 'twitter':
                $link = "https://twitter.com/" . $url;
                break;
        }

        return $link;
    }

    // SHORTCODE: Artist list
    public function lgt_labelgrid_gate_download_list($atts)
    {

        // Attributes
        $atts = shortcode_atts(array(
            'show-title' => false,
            'category' => null,
            'items-page' => 12,
            'items-row' => 4,
            'pagination' => null,
            'title-below' => null
        ), $atts, 'labelgrid-gate-download-list');

        $html = "";

        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        $rd_args = array(
            'post_type' => 'gate_download',
            'posts_per_page' => $atts['items-page'],
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        if (! empty($atts['category']) && $atts['category'] != 'all') {

            $rd_args = array_merge($rd_args, array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'gate_category',
                        'terms' => $atts['category'],
                        'field' => 'name'
                    )
                )
            ));
        }

        $my_query = new WP_Query($rd_args);

        if ($my_query->have_posts()) :
            if ($atts['show-title'] == 'true')
                $html .= '<h1>' . __('Downloads:', 'label-grid-tools') . '</h1>';

            $html .= '<div id="releaselist" class="lgsquarelist" data-columns="' . $atts['items-row'] . '">';

            while ($my_query->have_posts()) :
                $my_query->the_post();

                $content_type = get_post_meta(get_the_ID(), '_lgt_gate_contents', true);
                $permalink = get_permalink();
                if ($content_type == 'upload_file') {
                    $release_image = get_post_meta(get_the_ID(), '_lgt_gate_cover_image', true);
                    $imagesw = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
                    $title = get_the_title();
                } elseif ($content_type == 'match_release') {
                    $release_post = carbon_get_post_meta(get_the_ID(), 'lgt_gate_linked_release');

                    $rd_args_release = array(
                        'post_type' => 'release',
                        'posts_per_page' => 1,
                        'p' => $release_post[0]['id']
                    );

                    $release_query = new WP_Query($rd_args_release);

                    $release_query->the_post();

                    $release_image = get_post_meta(get_the_ID(), '_lgt_release_image', true);
                    $imagesw = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
                    $title = get_the_title();

                    $release_query->reset_postdata();
                }

                if ($atts['title-below'] == 'yes') {

                    $html .= '<a href="' . get_permalink() . '" class="releaseelement elementrow4 titlebelowyes"><div class="cover">';

                    if (empty($imagesw[0]))
                        $html .= '<img src="' . plugin_dir_url(__FILE__) . 'images/artists_placeholder.png" alt="' . __('Empty Image', 'label-grid-tools') . '" title="' . __('Empty Image', 'label-grid-tools') . '" class="image">';
                    else
                        $html .= '<img src="' . $imagesw[0] . '" alt="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '" class="image">';

                    $html .= '</div><div class="sub_text"><div class="artist">' . $title . '</div></div></a>';
                } else {

                    $html .= '<div class="releaseelement elementrow' . $atts['items-row'] . '">';
                    if (empty($imagesw[0]))
                        $html .= '<img src="' . plugin_dir_url(__FILE__) . 'images/artists_placeholder.png" alt="' . __('Empty Gate Image', 'label-grid-tools') . '" title="' . __('Empty Gate Image', 'label-grid-tools') . '" class="image">';
                    else
                        $html .= '<img src="' . $imagesw[0] . '" alt="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '" class="image">';
                    $html .= '<div class="middle"><div class="text"><div class="title">' . $title . '</div><a href="' . $permalink . '" class="button">' . __('View more', 'label-grid-tools') . '</a></div></div>';
                    $html .= '</div>';
                }
            endwhile;
            wp_reset_postdata();
            $html .= '</div>';

            if (empty($atts['pagination']) || $atts['pagination'] != 'off') {

                $html .= '<div class="pagination">';

                $html .= paginate_links(array(
                    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'total' => $my_query->max_num_pages,
                    'current' => max(1, $paged),
                    'format' => '?paged=%#%',
                    'show_all' => false,
                    'type' => 'plain',
                    'end_size' => 2,
                    'mid_size' => 1,
                    'prev_next' => true,
                    'prev_text' => sprintf('<i></i> %1$s', __('&lt;', 'label-grid-tools')),
                    'next_text' => sprintf('%1$s <i></i>', __('&gt;', 'label-grid-tools')),
                    'add_args' => false,
                    'add_fragment' => ''
                ));

                $html .= '</div>';
            }

        endif;

        return $html;
    }

    public function lgt_gate_download_detail_html()
    {
        $release_wp_id = get_the_ID();

        $gate_data['show_press'] = get_option('_lgt_gate_show_press');
        $gate_data['show_artists'] = get_option('_lgt_gate_show_artists');

        $content_type = get_post_meta($release_wp_id, '_lgt_gate_contents', true);

        if ($content_type == 'upload_file') {
            $release_image = get_post_meta($release_wp_id, '_lgt_gate_cover_image', true);
            $artwork_medium = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
            $artwork_big = wp_get_attachment_image_src($release_image, 'lgt_artwork_big');
            $title = get_the_title();
            $description = wpautop(get_post_meta($release_wp_id, '_lgt_gate_description', true));
            $shortcode = '[labelgrid-gate-button]';
        } elseif ($content_type == 'match_release') {
            $release_post = carbon_get_post_meta($release_wp_id, 'lgt_gate_linked_release');

            $rd_args_release = array(
                'post_type' => 'release',
                'posts_per_page' => 1,
                'p' => $release_post[0]['id']
            );

            $release_query = new WP_Query($rd_args_release);

            $release_query->the_post();
            $release_id = get_the_ID();
            $release_image = get_post_meta($release_id, '_lgt_release_image', true);
            $artwork_medium = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
            $artwork_big = wp_get_attachment_image_src($release_image, 'lgt_artwork_big');
            $title = get_the_title();
            $description = wpautop(get_post_meta($release_id, '_lgt_press_release', true));
            $shortcode = '[labelgrid-release-links]';

            $release_query->reset_postdata();
        }

        $html = '<div class="release_detail" id="lg_content_release">
				<div class="header_release"><div class="artwork">
    				<a href="' . $artwork_big[0] . '"><img src="' . $artwork_medium[0] . '" alt="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . $title . ' ' . __('Artwork', 'label-grid-tools') . '"></a>
    			</div>
    				    
    			<div class="release_links"><div id="releaselinks">' . do_shortcode($shortcode) . '</div></div></div>';

        if ($gate_data['show_press'] != 'no' || $gate_data['show_artists'] != 'no')
            $html .= '<div class="separator"></div>';

        if (! empty($release_id))
            $artists = carbon_get_post_meta($release_id, 'lgt_artists');

        if ($gate_data['show_artists'] != 'no' && ! empty($artists)) {

            if ($gate_data['show_press'] == 'no')
                $html .= '<div class="right_col afull_width"><div class="artists">';
            else
                $html .= '<div class="right_col"><div class="artists">';

            $html .= '<div class="titleartist">' . __('Features:', 'label-grid-tools') . '</div>';
            $tot_artists = count($artists);
            foreach ($artists as $artist) {
                $args = array(
                    'p' => $artist['id'],
                    'post_type' => 'artist'
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) :
                        $loop->the_post();
                        $artist_image = get_post_meta($artist['id'], '_lgt_artist_image', true);
                        $artwork = wp_get_attachment_image_src($artist_image, 'lgt_artwork_small');

                        $html .= '<div class="release_data totrel' . $tot_artists . '">
								<a href="' . get_the_permalink($artist['id']) . '">
									<div class="image_release"><img src="' . $artwork[0] . '"></div>
									<div class="title_release">' . get_the_title($artist['id']) . '</div>
								</a>
							</div>';
                    endwhile;
                    wp_reset_postdata();
                endif;
            }
            $html .= '</div></div>';
        }

        if ($description && $gate_data['show_press'] != 'no') {
            if ($gate_data['show_artists'] == 'no' || empty($artists))
                $html .= '<div class="press_release afull_width">';
            else
                $html .= '<div class="press_release">';
            $html .= '<div class="cont">' . wpautop($description) . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function lgt_export_entries()
    {
        if (isset($_GET['export_entries'])) {
            global $wpdb;
            $query = "SELECT * FROM `{$this->table_name_gate_entries}` WHERE 1=1";

            if (!empty($_GET['gate-filter'])) {
                $query .= " AND `gate_id` = %d";
                $query = $wpdb->prepare($query, $_GET['gate-filter']);
            }

            $query .= " ORDER BY id DESC";

            $sql_results = $wpdb->get_results($query, ARRAY_A);

            if ($sql_results) {

                header('Content-type: text/csv');
                header('Content-Disposition: attachment; filename="export.csv"');
                header('Pragma: no-cache');
                header('Expires: 0');

                $file = fopen('php://output', 'w');

                fputcsv($file, array_keys((array) $sql_results[0]));

                foreach ($sql_results as $post) {
                    setup_postdata($post);

                    fputcsv($file, array_values((array) $post));
                }

                die();
            }
        }
    }

    public function lgt_mobile_check()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4)))
            return "1";
        else
            return "0";
    }

    public function lgt_gate_get_actions($data)
    {
        $data = stripslashes($_COOKIE['lg_enabled_services_' . $data['id']]);
        return $data;
    }

    public function lgt_gate_get_playlists(WP_REST_Request $request)
    {
        $response = null;
        $final_response = true;
        $release_id = $request['release_id'];

        if (! empty($release_id)) {
            $session = $this->lgt_check_session();
            $response['message'] = "ok";

            $this->gate_api_is_active = get_option('_lgt_is_active');
            if ($this->gate_api_is_active == 'yes') {

                $gate_api_access_token = get_option('_lgt_api_key');
                try {
                    $stack = HandlerStack::create();
                    $stack->push(GuzzleRetryMiddleware::factory());

                    $lgt_curl_interface = get_option('_lgt_curl_interface');
                    if ($lgt_curl_interface != "1") $curlinterface = [CURLOPT_INTERFACE => $_SERVER['SERVER_ADDR']];

                    $client = new Client(['handler' => $stack]);

                    $addpresave = $client->request('POST', 'https://gate.labelgrid.com/api/gate/get-playlists', [
                        'curl' => !empty($curlinterface) ? $curlinterface : null,
                        'headers' => [
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $gate_api_access_token
                        ],
                        'form_params' => [
                            'release_id' => $release_id,
                            'user_id' => $session
                        ]
                    ]);

                    $code = $addpresave->getStatusCode(); // 200

                    if ($code == 200) {

                        if ($addpresave->getBody()) {
                            $stream = $addpresave->getBody();

                            $response = json_decode($stream->getContents());

                            LabelGrid_Tools::log_event('Presave - Get Playlists info: ' . print_r($response, true), 'info', 'gate');
                        }
                    }
                } catch (RequestException $e) {
                    $response['message'] = "ko";
                    LabelGrid_Tools::log_event('Presave - Get Playlists error: ' . $e->getMessage(), 'error', 'gate');
                    return false;
                }
            }
        }

        if (!empty($response->data)) $final_response = rest_ensure_response(json_decode($response->data));
        else $final_response = true;

        return $final_response;
    }
}
