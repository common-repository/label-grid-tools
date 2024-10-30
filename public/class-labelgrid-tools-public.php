<?php

/**
 * @link       https://labelgrid.com
 * @since      1.0.0
 *
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/public
 */
class LabelGrid_Tools_Public
{

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

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name
     *            The name of the plugin.
     * @param string $version
     *            The version of this plugin.
     */
    public function __construct()
    {
        $this->plugin_name = LabelGrid_Tools::get_plugin_name();
        $this->version = LabelGrid_Tools::get_version();
    }

    public function lgt_links_page_template($template)
    {
        global $post;
        $template_releases = 'lite_release.php';
        $template_gate = 'lite_gate.php';

        if (get_query_var('lite', false) !== false && $post->post_type == 'release') {
            return dirname(__FILE__) . '/templates/' . $template_releases;
        }

        if (get_query_var('lite', false) !== false && $post->post_type == 'gate_download') {
            return dirname(__FILE__) . '/templates/' . $template_gate;
        }

        return $template;
    }

    public function lgt_add_rewrite_lite()
    {
        add_rewrite_endpoint('lite', EP_PERMALINK | EP_PAGES);
    }

    public function checkGetLink()
    {
        $release_wp_id = get_the_ID();

        if (!empty($release_wp_id)) {

            foreach (LabelGrid_Tools::get_link_services('releases', true) as $service_name => $service) {

                if (isset($_GET[$service_name])) {

                    if ($newForward = get_post_meta($release_wp_id, '_lgt_url_' . $service_name, true)) {
                        header("Location: " . $newForward);
                        die();
                    }
                }
            }
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function lgt_enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LabelGrid_Tools_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The LabelGrid_Tools_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name . '-frontend', plugin_dir_url(__FILE__) . 'css/labelgrid-tools-public.min.css', array(), $this->version, 'all');

        if (is_admin_bar_showing()) {
            wp_enqueue_style($this->plugin_name . '-admin-toolbar', plugin_dir_url(__DIR__) . 'admin/css/labelgrid-tools-admin-toolbar.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function lgt_enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name . '-frontend', plugin_dir_url(__FILE__) . 'js/labelgrid-tools-public.js', array(
            'wp-i18n',
            'jquery'
        ), $this->version, false);

        wp_set_script_translations($this->plugin_name . '-frontend', 'label-grid-tools');

        // Generate a nonce and pass it to the JavaScript
        wp_localize_script($this->plugin_name . '-frontend', 'LabelGridTools', array(
            'nonce' => wp_create_nonce('wp_rest')
        ));

        if (is_admin_bar_showing()) {
            wp_enqueue_script($this->plugin_name . '-admin-toolbar', plugin_dir_url(__DIR__) . 'admin/js/labelgrid-tools-admin-toolbar.js', array(
                'wp-i18n',
                'jquery'
            ), $this->version, false);
            wp_localize_script($this->plugin_name . '-admin-toolbar', 'lgbar', array(
                'ajaxurl' => admin_url('admin-ajax.php')
            ));
            wp_set_script_translations($this->plugin_name . '-admin-toolbar', 'label-grid-tools');
        }

        wp_enqueue_script($this->plugin_name . '-json2', plugin_dir_url(__FILE__) . 'js/json2.min.js', array(
            'jquery'
        ), $this->version, true);
        wp_enqueue_script($this->plugin_name . '-handlebars', plugin_dir_url(__FILE__) . 'js/handlebars.min.js', array(
            'jquery'
        ), $this->version, true);

        wp_enqueue_script($this->plugin_name . '-moment', plugin_dir_url(__DIR__) . 'vendor/bower-asset/moment/moment.js', array(
            'jquery'
        ), $this->version, true);
    }

    public function lgt_new_default_content($content)
    {
        global $post;

        if (is_singular() && in_the_loop() && is_main_query()) {
            $this->checkGetLink();

            if (!empty($post->post_type)) {
                if ($post->post_type == 'release') {
                    $content = $this->lgt_release_detail_html();
                } elseif ($post->post_type == 'artist') {
                    $content = $this->lgt_artist_detail_html();
                }
            }
        }

        return $content;
    }

    public function lgt_release_detail_html()
    {
        $player = '';
        $cachetype = 'release';
        $gmt_wp_date = gmdate("Y-m-d H:i:s", time() + 3600 * (get_option('gmt_offset') + gmdate("I")));
        $release_wp_id = get_the_ID();
        $last_edit = get_the_modified_date("Y-m-d H:i:s", $release_wp_id);
        $category = wp_get_object_terms($release_wp_id, 'record_label', array(
            'fields' => 'names'
        ));
        $release_data['release_date'] = get_post_meta($release_wp_id, '_lgt_release_date', true);

        if (($release_data['release_date'] . " 00:00:00") > $gmt_wp_date)
            $cachetype = 'presave';

        $transient_name = 'lgt_labelgrid_release_' . $release_wp_id . $cachetype . strtotime($last_edit) . current_user_can('editor');
        $html = get_transient($transient_name);

        if (false === $html) {
            $html = '';
            $release_image = get_post_meta($release_wp_id, '_lgt_release_image', true);

            $artwork_medium = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
            $artwork_big = wp_get_attachment_image_src($release_image, 'lgt_artwork_big');

            $release_data['cat_number'] = get_post_meta($release_wp_id, '_lgt_cat_number', true);
            $release_data['artist_names'] = get_post_meta($release_wp_id, '_lgt_artist_names', true);
            $release_data['release_name'] = get_post_meta($release_wp_id, '_lgt_release_name', true);
            $release_data['press_release'] = get_post_meta($release_wp_id, '_lgt_press_release', true);
            $release_data['url_spotify'] = get_post_meta($release_wp_id, '_lgt_url_spotify', true);

            $release_data['show_press'] = get_option('_lgt_show_press');
            $release_data['show_spotify_player'] = get_option('_lgt_show_spotify_player');

            // SIMILAR RELEASES
            $release_data['show_similar_releases'] = get_post_meta($release_wp_id, '_lgt_show_similar_releases', true);
            if (empty($release_data['show_similar_releases']) || $release_data['show_similar_releases'] == "-")
                $release_data['show_similar_releases'] = get_option('_lgt_show_similar_releases');

            $release_data['show_artists'] = get_post_meta($release_wp_id, '_lgt_show_artists', true);
            if (empty($release_data['show_artists']) || $release_data['show_artists'] == "-")
                $release_data['show_artists'] = get_option('_lgt_show_artists');

            $release_data['show_below_artwork'] = get_post_meta($release_wp_id, '_lgt_show_below_artwork', true);
            if (empty($release_data['show_below_artwork']) || $release_data['show_below_artwork'] == "-")
                $release_data['show_below_artwork'] = get_option('_lgt_show_below_artwork');

            if (!empty($release_data['url_spotify']) && $release_data['show_spotify_player'] == 'yes' && $release_data['release_date'] && ($release_data['release_date'] . " 00:00:00") <= $gmt_wp_date) {
                $urldata = wp_parse_url($release_data['url_spotify']);

                $player = '<iframe src="' . esc_url($urldata['scheme'] . '://' . $urldata['host'] . '/embed' . $urldata['path']) . '" width="100%" height="80" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>';
            }

            $html .= '<div class="release_detail" id="lg_content_release">';
            $html .= '<div class="header_release"><div class="artwork">';
            $html .= '<a href="' . esc_url($artwork_big[0]) . '"><img src="' . esc_url($artwork_medium[0]) . '" loading="lazy" alt="' . esc_attr(get_the_title()) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '" title="' . esc_attr(get_the_title()) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '"></a>';
            $html .= '<div class="sub_artwork_details">';

            if ($release_data['show_below_artwork'] == "releasecode")
                $html .= '<span class="catalog_number">' . esc_html($release_data['cat_number']) . '</span>';
            else
                $html .= '<span class="catalog_recordlabel">' . esc_html($category[0]) . '</span>';

            $html .= '<span class="release_date">' . esc_html(date_i18n(get_option('date_format'), strtotime($release_data['release_date']))) . '</span>';
            $html .= '' . $player . '';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="release_links">' . $this->lgt_labelgrid_release_links(array(
                'release-id' => $release_wp_id
            )) . '</div></div>';

            if ($release_data['show_press'] != 'no' || $release_data['show_artists'] != 'no')
                $html .= '<div class="separator"></div>';

            if ($release_data['show_artists'] != 'no') {

                if ($release_data['show_press'] == 'no')
                    $html .= '<div class="right_col afull_width"><div class="artists">';
                else
                    $html .= '<div class="right_col"><div class="artists">';

                $artists = carbon_get_post_meta($release_wp_id, 'lgt_artists');

                if (!empty($artists)) {
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
                                $artwork = wp_get_attachment_image_src($artist_image, 'lgt_artwork_smaller');

                                $release_artists[] = $artist['id'];

                                $html .= '<div class="release_data totrel' . $tot_artists . '"><a href="' . get_the_permalink() . '"><div class="image_release"><img src="' . $artwork[0] . '"  loading="lazy" alt="' . get_the_title() . '"></div><div class="title_release">' . get_the_title() . '</div></a></div>';
                            endwhile;
                            wp_reset_postdata();
                        endif;
                    }
                }
                $html .= '</div></div>';
            }

            if ($release_data['press_release'] && $release_data['show_press'] != 'no') {
                if ($release_data['show_artists'] == 'no')
                    $html .= '<div class="press_release afull_width">';
                else
                    $html .= '<div class="press_release">';
                $html .= '<div class="cont">' . wpautop($release_data['press_release']) . '</div>';
                $html .= '</div>';
            }

            if ($release_data['show_similar_releases'] == 'yes') {

                $short_var = array(
                    'show-title' => false,
                    'label' => 'all',
                    'items-page' => 6,
                    'items-row' => 6,
                    'filter-artists' => $release_artists,
                    'pagination' => 'off',
                    'title-below' => 'yes',
                    'exclude' => array(
                        $release_wp_id
                    ),
                    'image-quality' => 'lgt_artwork_small'
                );
                $related_html = $this->lgt_labelgrid_release_list($short_var);

                if ($related_html) {
                    $html .= '<div class="clear"></div><br/>';
                    $html .= '<div class="titleartist">' . __('More from the artists:', 'label-grid-tools') . '</div>';
                    $html .= $related_html;
                }
            }

            $html .= '</div>';
            if (!empty($html))
                set_transient($transient_name, $html, WEEK_IN_SECONDS);
        }

        return $html;
    }

    public function lgt_artist_detail_html()
    {
        $release_wp_id = get_the_ID();
        $last_edit = get_the_modified_date("Y-m-d H:i:s", $release_wp_id);

        $transient_name = 'lgt_labelgrid_artist_' . $release_wp_id . strtotime($last_edit) . current_user_can('editor');
        $html = get_transient($transient_name);

        if (false === $html) {

            $html = '';
            $release_image = get_post_meta($release_wp_id, '_lgt_artist_image', true);

            $artwork_medium = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
            $artwork_big = wp_get_attachment_image_src($release_image, 'lgt_artwork_big');

            $release_data['biography'] = get_post_meta($release_wp_id, '_lgt_biography', true);
            $release_data['url_twitter'] = get_post_meta($release_wp_id, '_lgt_url_twitter', true);
            $release_data['url_spotify'] = get_post_meta($release_wp_id, '_lgt_url_spotify', true);

            $release_data['show_twitter_feed'] = get_option('_lgt_show_twitter_feed');
            $release_data['show_artist_bandsintown'] = get_option('_lgt_show_artist_bandsintown');

            $html .= '<div class="release_detail" id="lg_content_release">';
            $html .= '<div class="header_release"><div class="artwork">';
            $html .= '<a href="' . $artwork_big[0] . '"><img src="' . $artwork_medium[0] . '" loading="lazy" alt="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '"></a>';
            $html .= '</div>';
            $html .= '<div class="release_links">' . do_shortcode('[labelgrid-release-links artists="true"]') . '</div></div>';
            $html .= '<div class="separator"></div>';
            $html .= '<div class="right_col">';

            $artists = new WP_Query(array(
                'post_type' => 'release',
                'posts_per_page' => 12,
                'orderby' => 'meta_value',
                'order' => 'DESC',
                'meta_key' => '_lgt_release_date',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'lgt_artists',
                        'carbon_field_property' => 'id',
                        'compare' => '=',
                        'value' => $release_wp_id
                    ),
                    array(
                        'key' => '_lgt_show_list',
                        'value' => 'yes',
                        'compare' => '!='
                    )
                )
            ));

            if ($artists->have_posts()) {
                $html .= '<div class="artists"><div class="titleartist">' . __('Releases:', 'label-grid-tools') . '</div>';
                while ($artists->have_posts()) {
                    $artists->the_post();
                    $artist_image = get_post_meta($artists->post->ID, '_lgt_release_image', true);
                    $artwork = wp_get_attachment_image_src($artist_image, 'lgt_artwork_smaller');
                    $html .= '<div class="release_data"><a href="' . esc_url(get_permalink($artists->post->ID)) . '"><div class="image_release"><img src="' . esc_url($artwork[0]) . '" loading="lazy" alt="' . esc_attr(get_the_title($artists->post->ID)) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '" title="' . esc_attr(get_the_title($artists->post->ID)) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '"></div><div class="title_release">' . esc_html(get_the_title($artists->post->ID)) . '</div></a></div>';
                }
                wp_reset_postdata();
                $html .= '</div>';
            }

            if (!empty($release_data['url_twitter']) && $release_data['show_twitter_feed'] != 'no') {
                global $wp_embed;
                $html .= '<div class="twitter_preview">' . $wp_embed->run_shortcode('[embed height="350"]' . $release_data['url_twitter'] . '[/embed]') . '</div>';
            }
            if (!empty($release_data['show_artist_bandsintown']) && $release_data['show_artist_bandsintown'] != 'no') {
                // Conditionally enqueue the Bandsintown widget script
                wp_enqueue_script(
                    'bandsintown-widget',
                    'https://widget.bandsintown.com/main.min.js',
                    [],
                    null,
                    true // Load in the footer
                );

                // Output the HTML for the widget
                $html .= '<div id="bandsintown_events_box">';
                $html .= '<a class="bit-widget-initializer" data-artist-name="' . esc_attr(get_the_title()) . '" data-display-local-dates="false" data-display-past-dates="false" data-auto-style="false" data-text-color="#000000" data-link-color="#be1e2d" data-background-color="rgba(0,0,0,0)" data-display-limit="5" data-display-start-time="false" data-link-text-color="#FFFFFF" data-display-lineup="false" data-display-play-my-city="false" data-separator-color="rgba(124,124,124,0.25)"></a>';
                $html .= '</div>';
            }

            $html .= '</div>';
            if ($release_data['url_spotify']) :
                global $wp_embed;
                $html .= '<div class="spotify_preview">' . $wp_embed->run_shortcode('[embed]' . $release_data['url_spotify'] . '[/embed]') . '</div>';
            endif;

            if ($release_data['biography']) {
                $html .= '<div class="press_release">';
                $html .= '<div class="cont">' . wpautop($release_data['biography']) . '</div>';
                $html .= '</div>';
            }

            $html .= '</div>';

            if (!empty($html))
                set_transient($transient_name, $html, WEEK_IN_SECONDS);
        }
        return $html;
    }

    // SHORTCODE: Release links
    public function lgt_labelgrid_release_links($atts)
    {

        // Attributes
        $atts = shortcode_atts(array(
            'release-id' => null,
            'artists' => null
        ), $atts, 'labelgrid-release-links');

        if (!empty($atts['release-id']))
            $id = $atts['release-id'];
        else
            $id = get_the_ID();

        $html = "";

        if (!empty($id)) {

            $aff_token = get_option('_lgt_itunes_affiliate_token');
            if (!empty($aff_token))  $html .= "<script>var itunes_affiliate_token='" . $aff_token . "';</script>";

            $gmt_wp_date = gmdate("Y-m-d H:i:s", time() + 3600 * (get_option('gmt_offset') + gmdate("I")));

            $html .= '<div id="releaselinks">';

            $release_data['id'] = $id;
            $release_data['release_date'] = get_post_meta($id, '_lgt_release_date', true);

            $release_data['upc'] = get_post_meta($id, '_lgt_release_upc', true);
            $release_data['isrc'] = get_post_meta($id, '_lgt_release_isrc', true);

            // links
            $release_data['url_itunes'] = get_post_meta($id, '_lgt_url_itunes', true);
            $release_data['url_beatport'] = get_post_meta($id, '_lgt_url_beatport', true);
            $release_data["url_freedownload"] = get_post_meta($id, '_lgt_url_freedownload', true);

            $download_gate = new WP_Query(array(
                'post_type' => 'gate_download',
                'meta_query' => array(
                    array(
                        'key' => 'lgt_gate_linked_release',
                        'carbon_field_property' => 'id',
                        'compare' => '=',
                        'value' => $release_data['id']
                    )
                )
            ));

            // GATED DOWNLOAD
            if (shortcode_exists('labelgrid-gate-button') && $download_gate->have_posts()) {
                $download_gate->the_post();
                $html .= do_shortcode('[labelgrid-gate-button gate-id="' . $download_gate->post->ID . '" post-id="' . $release_data['id'] . '"]');
                wp_reset_postdata();
            }

            if ($release_data['release_date'] && ($release_data['release_date'] . " 00:00:00") >= $gmt_wp_date) {

                // PREORDER SPOTIFY
                $release_data['spotify_preorder'] = get_post_meta($release_data['id'], '_lgt_spotify_pre_save', true);
                if (empty($release_data['spotify_preorder']) || $release_data['spotify_preorder'] == "-")
                    $release_data['spotify_preorder'] = get_option('_lgt_spotify_pre_save');

                if (!empty($release_data['spotify_preorder']) && $release_data['spotify_preorder'] == "yes" && shortcode_exists('labelgrid-presave-button') && !empty($release_data["upc"]) && !empty($release_data["isrc"])) {
                    $html .= do_shortcode('[labelgrid-presave-button release_id="$id"]');
                    wp_reset_postdata();
                }

                // PREORDER ITUNES
                $release_data['itunes_preorder'] = get_post_meta($release_data['id'], '_lgt_itunes_pre_order', true);
                if (empty($release_data['itunes_preorder']) || $release_data['itunes_preorder'] == "-")
                    $release_data['itunes_preorder'] = get_option('_lgt_itunes_pre_order');

                if (!empty($release_data["url_itunes"]) && !empty($release_data['itunes_preorder']) and $release_data['itunes_preorder'] == "yes") {
                    $link_image = esc_url(plugins_url('public/images/ico_itunes.svg', dirname(__FILE__)));
                    $html .= '<div class="linkTop itunes"><a style="background-image:url(\'' . $link_image . '\');" href="' . $release_data["url_itunes"] . '&app=itunes" data-service="iTunes" target="blank" title="' . __("Pre-Save on", 'label-grid-tools') . ' iTunes"> ' . __("Pre-Save on", 'label-grid-tools') . ' iTunes</a></div>';
                }

                // PREORDER BEATPORT
                $release_data['beatport_preorder'] = get_post_meta($release_data['id'], '_lgt_beatport_pre_order', true);
                if (empty($release_data['beatport_preorder']) || $release_data['beatport_preorder'] == "-")
                    $release_data['beatport_preorder'] = get_option('_lgt_beatport_pre_order');

                if (!empty($release_data["url_beatport"]) && !empty($release_data['beatport_preorder']) and $release_data['beatport_preorder'] == "yes") {
                    $link_image = esc_url(plugins_url('public/images/ico_beatport.svg', dirname(__FILE__)));
                    $html .= '<div class="linkTop beatport"><a style="background-image:url(\'' . $link_image . '\');" href="' . $release_data["url_beatport"] . '" data-service="Beatport" target="blank" title="' . __("Pre-Save on", 'label-grid-tools') . ' iTunes"> ' . __("Pre-Save on", 'label-grid-tools') . ' Beatport</a></div>';
                }
            } else {
                if (!empty($atts['artists'])) {
                    $services = LabelGrid_Tools::get_link_services('artists', true);
                    $orders = carbon_get_theme_option('services_order_artists');
                } else {
                    $services = LabelGrid_Tools::get_link_services('releases', true);
                    $orders = carbon_get_theme_option('services_order');
                }

                $button_translation['listen'] = __("Listen on ", 'label-grid-tools');
                $button_translation['download'] = __("Download from ", 'label-grid-tools');
                $button_translation['watch'] = __("Watch on ", 'label-grid-tools');
                $button_translation['none'] = '';

                foreach ($orders as $service) {
                    $release_data['url_' . $service['_type']] = get_post_meta($id, '_lgt_url_' . $service['_type'], true);
                    if ($release_data["url_" . $service['_type']]) {
                        if (empty($services[$service['_type']]['image']))
                            $link_image = esc_url(plugins_url('public/images/ico_' . $service['_type'] . '.svg', dirname(__FILE__)));
                        else {
                            $img = wp_get_attachment_image_src($services[$service['_type']]['image'], 'lgt_artwork_medium');
                            $link_image = $img[0];
                        }
                        $button_label = null;
                        if ($services[$service['_type']]['action'])
                            $button_label = $button_translation[$services[$service['_type']]['action']];
                        $html .= '<div class="linkTop ' . $service['_type'] . '"><a href="' . $release_data['url_' . $service['_type']] . $services[$service['_type']]['linkextra'] . '" style="background-image:url(\'' . $link_image . '\');"  target="blank" data-service="' . $service['_type'] . '" title="' . $button_label . $services[$service['_type']]['name'] . '" rel="nofollow">' . $button_label . $services[$service['_type']]['name'] . '</a></div>';
                    }
                }

                if ($release_data["url_freedownload"]) :
                    $html .= '<div class="linkTop freedownload"><a href="' . $release_data["url_freedownload"] . '"  target="blank" data-service="Free Download" title="' . __("Free Download", 'label-grid-tools') . '">' . __("Free Download", 'label-grid-tools') . '</a></div>';
                endif;
            }
            $html .= '</div>';
        }

        return $html;
    }

    // SHORTCODE: Release list
    public function lgt_labelgrid_release_list($atts)
    {
        $gmt_wp_date = gmdate("Y-m-d", time() + 3600 * (get_option('gmt_offset') + gmdate("I")));
        $gmt_wp_datetime = gmdate("Y-m-d H:i:s", time() + 3600 * (get_option('gmt_offset') + gmdate("I")));

        // Attributes
        $atts = shortcode_atts(array(
            'show-title' => null,
            'label' => null,
            'items-page' => 12,
            'items-row' => 4,
            'pagination' => null,
            'showcase-last' => null,
            'item-filter' => 'released',
            'title-below' => null,
            'filter-artists' => null,
            'exclude' => null,
            'image-quality' => 'lgt_artwork_medium'
        ), $atts, 'labelgrid-release-list');

        $show_unreleased = get_option('_lgt_show_unreleased');
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $release_list_filter_genre = (get_query_var('lg_release_filter_genre')) ? get_query_var('lg_release_filter_genre') : -1;
        $release_list_filter_record_label = (get_query_var('lg_release_filter_record_label')) ? get_query_var('lg_release_filter_record_label') : -1;

        if (!empty($atts['showcase-last']) && $atts['showcase-last'] == 'on') {
            $atts['items-page'] = 1;
            $atts['items-row'] = 'x';
            $atts['pagination'] = 'off';
            $atts['show-title'] = false;
            $atts['only-future'] = null;
            $show_unreleased = 'no';
            $paged = 1;
        }

        $lastedit = get_option('_lgt_release_lastedit');
        $key = md5(wp_json_encode($atts) . $paged . $release_list_filter_genre . $release_list_filter_record_label . strtotime($lastedit) . current_user_can('editor'));

        $html = get_transient('lgt_labelgrid_release_list_' . $key);

        if (false === $html) {
            $html = "";

            $rd_args = array(
                'post_status' => 'publish',
                'post_type' => 'release',
                'paged' => $paged,
                'orderby' => 'meta_value',
                'order' => 'DESC',
                'meta_key' => '_lgt_release_date',
                'posts_per_page' => $atts['items-page'],
                'post__not_in' => $atts['exclude']
            );
            $meta_query = array();
            if ((!empty($show_unreleased) && $show_unreleased == 'no') || $atts['item-filter'] == 'released') {

                $meta_query[] = array(
                    'key' => '_lgt_release_date',
                    'value' => $gmt_wp_date,
                    'compare' => '<=',
                    'type' => 'DATE'
                );
            } elseif ($atts['item-filter'] == 'unreleased') {

                $meta_query[] = array(
                    'key' => '_lgt_release_date',
                    'value' => $gmt_wp_date,
                    'compare' => '>',
                    'type' => 'DATE'
                );
            }

            if (is_array($atts['filter-artists'])) {

                $meta_query[] = array(
                    'key' => 'lgt_artists',
                    'carbon_field_property' => 'id',
                    'value' => $atts['filter-artists'],
                    'compare' => 'IN'
                );
            }

            // Hide certain releases
            $meta_query[] = array(
                'key' => '_lgt_show_list',
                'value' => 'yes',
                'compare' => '!='
            );

            $rd_args = array_merge($rd_args, array(
                'meta_query' => array(
                    'relation' => 'AND',
                    $meta_query
                )
            ));


            // Taxonomy filter
            $taxonomyfilter = array();

            if (!empty($atts['label']) && $atts['label'] != 'all') {

                $taxonomyfilter[] = array(
                    'taxonomy' => 'record_label',
                    'terms' => $atts['label'],
                    'field' => 'name'
                );
            } else if (!empty($release_list_filter_record_label) && $release_list_filter_record_label != -1) {

                $taxonomyfilter[] = array(
                    'taxonomy' => 'record_label',
                    'terms' => $release_list_filter_record_label,
                    'field' => 'id'
                );
            }

            if (!empty($release_list_filter_genre) && $release_list_filter_genre != -1) {

                $taxonomyfilter[] = array(
                    'taxonomy' => 'genre',
                    'terms' => $release_list_filter_genre,
                    'field' => 'id'
                );
            }



            if (!empty($taxonomyfilter)) {
                $rd_args = array_merge($rd_args, array(
                    'tax_query' => array(
                        $taxonomyfilter
                    )
                ));
            }

            $my_query = new WP_Query($rd_args);

            if ($my_query->have_posts()) :
                if ($atts['show-title'] == 'true')
                    $html .= '<h1>' . __('Releases', 'label-grid-tools') . '</h1>';

                $html .= '<div id="releaselist" class="lgsquarelist" data-columns="' . $atts['items-row'] . '">';

                while ($my_query->have_posts()) {
                    $my_query->the_post();
                    $release_image = get_post_meta(get_the_ID(), '_lgt_release_image', true);
                    $release_release_date = get_post_meta(get_the_ID(), '_lgt_release_date', true);
                    $release_artist_names = get_post_meta(get_the_ID(), '_lgt_artist_names', true);
                    $release_release_name = get_post_meta(get_the_ID(), '_lgt_release_name', true);

                    $imagesw = wp_get_attachment_image_src($release_image, $atts['image-quality']);

                    $presave_active = get_post_meta(get_the_ID(), '_lgt_spotify_presave', true);
                    if (empty($presave_active))
                        $presave_active = get_option('_lgt_spotify_pre_save');

                    if ($atts['title-below'] == 'yes') {

                        $html .= '<a href="' . get_permalink() . '" class="releaseelement elementrow' . $atts['items-row'] . ' titlebelowyes"><div class="cover">';

                        if ($release_release_date && ($release_release_date . " 00:00:00") >= $gmt_wp_datetime && $presave_active == 'yes')
                            $html .= '<div class="ribbonCover"><span>' . __('PRE-SAVE', 'label-grid-tools') . '</span></div>';

                        $html .= '<img src="' . $imagesw[0] . '" loading="lazy" alt="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" class="image"></div><div class="sub_text"><div class="title">' . $release_release_name . '</div><div class="artist">' . $release_artist_names . '</div></div></a>';
                    } else {

                        $html .= '<div class="releaseelement elementrow' . $atts['items-row'] . '">';
                        if ($release_release_date && ($release_release_date . " 00:00:00") >= $gmt_wp_datetime && $presave_active == 'yes')
                            $html .= '<div class="ribbonCover"><span>' . __('PRE-SAVE', 'label-grid-tools') . '</span></div>';
                        $html .= '<img src="' . $imagesw[0] . '" loading="lazy" alt="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" class="image">';
                        $html .= '<div class="middle"><div class="text"><div class="artist">' . $release_artist_names . '</div><div class="title">' . $release_release_name . '</div><div class="release_date"><span>' . __('Release date:', 'label-grid-tools') . ' ' . $release_release_date . '</div><a href="' . get_permalink() . '" class="button">' . __('View more', 'label-grid-tools') . '</a></div></div>';
                        $html .= '</div>';
                    }
                }
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

            if (!empty($html))
                set_transient('lgt_labelgrid_release_list_' . $key, $html, WEEK_IN_SECONDS);
        }
        return $html;
    }


    // SHORTCODE: Release list filter
    public function lgt_labelgrid_release_list_filter($atts)
    {
        // Attributes
        $atts = shortcode_atts(array(
            'record_label' => null,
        ), $atts, 'labelgrid-release-list-filter');

        $release_list_filter_genre = (get_query_var('lg_release_filter_genre')) ? get_query_var('lg_release_filter_genre') : 0;

        $genre_dropdown_args = array(
            'show_option_none' => __('FILTER BY GENRE', 'label-grid-tools'),
            'taxonomy'       => 'genre',
            'orderby'          => 'name',
            'echo'             => 0,
            'hide_if_empty'    => true,
            'hierarchical'     => true,
            'depth'            => '2',
            'pad_counts'     => true,
            'name' => 'lg_release_filter_genre',
            'selected' => $release_list_filter_genre
        );

        if ($atts['record_label']) {

            $release_list_filter_record_label = (get_query_var('lg_release_filter_record_label')) ? get_query_var('lg_release_filter_record_label') : 0;

            $record_label_dropdown_args = array(
                'show_option_none' => __('FILTER BY RECORD LABEL', 'label-grid-tools'),
                'taxonomy'       => 'record_label',
                'orderby'          => 'name',
                'echo'             => 0,
                'hide_if_empty'    => true,
                'hierarchical'     => true,
                'depth'            => '2',
                'pad_counts'     => true,
                'name' => 'lg_release_filter_record_label',
                'selected' => $release_list_filter_record_label
            );
        }

        $post_id = get_the_ID();
        $shortcodename = 'labelgrid-release-list';
        $content = get_the_content($post_id);

        $pattern = get_shortcode_regex(array($shortcodename));

        $this->lg_check_shortcode($pattern, $content, $shortcodename, 'label');

        $html = '<div id="lg_release_list_filter_container"><form name="lg_release_list_filter" id="lg_release_list_filter" method="GET" action="' . get_permalink($post_id) . '">';

        $html .= wp_dropdown_categories($genre_dropdown_args);

        if ($atts['record_label']) $html .= wp_dropdown_categories($record_label_dropdown_args);

        $html .= '</form></div>';

        return $html;
    }

    public function lgt_declare_query_vars($qvars)
    {
        $qvars[] = 'lg_release_filter_genre';
        $qvars[] = 'lg_release_filter_record_label';
        return $qvars;
    }


    private function lg_check_shortcode($pattern, $content, $shortcode, $value)
    {
        if (preg_match_all('/' . $pattern . '/s', $content, $matches)) {
            $attributes = shortcode_parse_atts($matches[3][0]);

            if (array_key_exists($value, $attributes)) {
                return $attributes[$value];
            }
        }
    }




    // SHORTCODE: Artist list
    public function lgt_labelgrid_artist_list($atts)
    {

        // Attributes
        $atts = shortcode_atts(array(
            'show-title' => false,
            'category' => null,
            'items-page' => 12,
            'items-row' => 4,
            'pagination' => null,
            'title-below' => null
        ), $atts, 'labelgrid-artist-list');

        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        $lastedit = get_option('_lgt_artist_lastedit');
        $key = md5(wp_json_encode($atts) . $paged . strtotime($lastedit) . current_user_can('editor'));

        $html = get_transient('lgt_labelgrid_artist_list_' . $key);

        if (false === $html) {

            $html = "";

            $rd_args = array(
                'post_type' => 'artist',
                'posts_per_page' => $atts['items-page'],
                'paged' => $paged,
                'orderby' => 'title',
                'order' => 'ASC'
            );

            if (!empty($atts['category']) && $atts['category'] != 'all')

                $rd_args = array_merge($rd_args, array(
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'artist_category',
                            'terms' => $atts['category'],
                            'field' => 'name'
                        )
                    )
                ));

            $my_query = new WP_Query($rd_args);

            if ($my_query->have_posts()) :
                if ($atts['show-title'] == 'true')
                    $html .= '<h1>' . __('Artists', 'label-grid-tools') . '</h1>';

                $html .= '<div id="artistlist" class="lgsquarelist" data-columns="' . $atts['items-row'] . '">';

                while ($my_query->have_posts()) :
                    $my_query->the_post();
                    $release_image = get_post_meta(get_the_ID(), '_lgt_artist_image', true);
                    $imagesw = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');

                    if ($atts['title-below'] == 'yes') {

                        $html .= '<a href="' . esc_url(get_permalink()) . '" class="releaseelement elementrow' . esc_attr($atts['items-row']) . ' titlebelowyes"><div class="cover">';

                        if (empty($imagesw[0]))
                            $html .= '<img src="' . plugin_dir_url(__FILE__) . 'images/artists_placeholder.png" loading="lazy" alt="' . __('Empty Artist Image', 'label-grid-tools') . '" title="' . __('Empty Artist Image', 'label-grid-tools') . '" class="image">';
                        else
                            $html .= '<img src="' . esc_url($imagesw[0]) . '" loading="lazy" alt="' . esc_attr(get_the_title()) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '" title="' . esc_attr(get_the_title()) . ' ' . esc_attr__('Artwork', 'label-grid-tools') . '" class="image">';

                        $html .= '</div><div class="sub_text"><div class="title">' . get_the_title() . '</div></div></a>';
                    } else {

                        $html .= '<div class="releaseelement elementrow' . $atts['items-row'] . '">';
                        if (empty($imagesw[0]))
                            $html .= '<img src="' . plugin_dir_url(__FILE__) . 'images/artists_placeholder.png" loading="lazy" alt="' . __('Empty Artist Image', 'label-grid-tools') . '" title="' . __('Empty Artist Image', 'label-grid-tools') . '" class="image">';
                        else
                            $html .= '<img src="' . $imagesw[0] . '" loading="lazy" alt="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" class="image">';

                        $html .= '<div class="middle"><div class="text"><div class="title">' . get_the_title() . '</div><a href="' . get_permalink() . '" class="button">' . __('View more', 'label-grid-tools') . '</a></div></div>';
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

            if (!empty($html))
                set_transient('lgt_labelgrid_artist_list_' . $key . $paged, $html, WEEK_IN_SECONDS);
        }
        return $html;
    }

    // SHORTCODE: Release list
    public function lgt_labelgrid_release_banner($atts)
    {

        $gmt_wp_date = gmdate("Y-m-d", time() + 3600 * (get_option('gmt_offset') + gmdate("I")));

        // Attributes
        $atts = shortcode_atts(array(
            'label' => null,
            'items-page' => 1,
            'item-filter' => 'released',
            'item-order' => 'DESC',
            'filter-artists' => null,
            'exclude' => null
        ), $atts, 'labelgrid-release-banner');

        $show_unreleased = get_option('_lgt_show_unreleased');

        $html = "";

        $rd_args = array(
            'post_status' => 'publish',
            'post_type' => 'release',
            'paged' => 1,
            'orderby' => 'meta_value',
            'order' => $atts['item-order'],
            'meta_key' => '_lgt_release_date',
            'posts_per_page' => $atts['items-page'],
            'post__not_in' => $atts['exclude']
        );
        $meta_query = array();
        if ((!empty($show_unreleased) && $show_unreleased == 'no') || $atts['item-filter'] == 'released') {
            $future_posts = true;
            $meta_query[] = array(
                'key' => '_lgt_release_date',
                'value' => $gmt_wp_date,
                'compare' => '<=',
                'type' => 'DATE'
            );
        } elseif ($atts['item-filter'] == 'unreleased') {
            $future_posts = false;
            $meta_query[] = array(
                'key' => '_lgt_release_date',
                'value' => $gmt_wp_date,
                'compare' => '>',
                'type' => 'DATE'
            );
        }

        if (is_array($atts['filter-artists'])) {

            $meta_query[] = array(
                'key' => 'lgt_artists',
                'carbon_field_property' => 'id',
                'value' => $atts['filter-artists'],
                'compare' => 'IN'
            );
        }

        // Hide certain releases
        $meta_query[] = array(
            'key' => '_lgt_show_list',
            'value' => 'yes',
            'compare' => '!='
        );

        $rd_args = array_merge($rd_args, array(
            'meta_query' => array(
                'relation' => 'AND',
                $meta_query
            )
        ));

        if (!empty($atts['label']) && $atts['label'] != 'all')
            $rd_args = array_merge($rd_args, array(
                'tax_query' => array(
                    array(
                        'taxonomy' => 'record_label',
                        'terms' => $atts['label'],
                        'field' => 'name'
                    )
                )
            ));

        $my_query = new WP_Query($rd_args);

        if ($my_query->have_posts()) :

            $html .= '<div id="lg-release-banner">';

            while ($my_query->have_posts()) :
                $my_query->the_post();
                $release_image = get_post_meta(get_the_ID(), '_lgt_release_image', true);
                $release_date = get_post_meta(get_the_ID(), '_lgt_release_date', true);
                $release_artist_names = get_post_meta(get_the_ID(), '_lgt_artist_names', true);
                $release_release_name = get_post_meta(get_the_ID(), '_lgt_release_name', true);
                $release_image_banner = get_post_meta(get_the_ID(), '_lgt_release_image_banner', true);
                $imagesw = wp_get_attachment_image_src($release_image, 'large');
                $imagebanner = wp_get_attachment_image_src($release_image_banner, 'large');


                $bgurl = !empty($imagebanner) ? esc_url($imagebanner[0]) : esc_url($imagesw[0]);

                $html .= '<a href="' . esc_url(get_permalink()) . '">';
                $html .= '<div class="release-banner" style="background-image:url(' . esc_url($bgurl) . ');">';

                $html .= '<div class="banner-image"><img src="' . $imagesw[0] . '" loading="lazy" alt="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" title="' . get_the_title() . ' ' . __('Artwork', 'label-grid-tools') . '" class="image"></div>';

                $html .= '<div class="banner-texts">';

                $html .= '<div class="banner-artists">' . $release_artist_names . '</div>';

                $html .= '<div class="banner-release-name">' . $release_release_name . '</div>';

                if ($future_posts)
                    $html .= '<div class="banner-out-now">' . __('OUT NOW', 'label-grid-tools') . '</div>';
                else
                    $html .= '<div class="banner-out-now">' . __('OUT ON ', 'label-grid-tools') . ' ' . date_format(date_create($release_date), get_option('date_format')) . '</div>';

                $html .= '<div class="banner-release-more">' . __('MORE INFO', 'label-grid-tools') . '</div>';

                $html .= '</div>';

                $html .= '</div>';
                $html .= '</a>';
            endwhile;
            wp_reset_postdata();
            $html .= '</div>';


        endif;

        return $html;
    }

    public function lgt_get_client_ip()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }


    public function lgt_yoast_opengraph_type($output, $presentation)
    {
        if ('release' == get_post_type()) $output = 'music.album';
        if ('artist' == get_post_type()) $output = 'profile';
        return $output;
    }

    public function lgt_register_api_endpoints()
    {
        register_rest_route('lgt-api/v1', '/geolocation', array(
            'methods' => 'GET',
            'callback' => array(
                $this,
                'fetch_geolocation_data'
            ),
            'permission_callback' => '__return_true',
        ));
    }

    public function fetch_geolocation_data($request)
    {
        // Verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
        }

        // Verify referer
        $referer = $request->get_header('referer');
        if (strpos($referer, home_url()) !== 0) {
            return new WP_Error('invalid_referer', 'Invalid referer', array('status' => 403));
        }

        $ip = $this->lgt_get_client_ip();
        $geoplugin_url = "http://www.geoplugin.net/json.gp?ip=" . $ip;

        // Use wp_remote_get with non-SSL URL and a timeout
        $response = wp_remote_get($geoplugin_url, array('timeout' => 5));

        // Check if the request resulted in an error
        if (is_wp_error($response)) {
            return new WP_Error('fetch_error', 'Unable to fetch data from geoplugin.net: ' . $response->get_error_message(), array('status' => 500));
        }

        // Retrieve and decode the body of the response
        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            return new WP_Error('fetch_error', 'Empty response from geoplugin.net', array('status' => 500));
        }

        return rest_ensure_response(json_decode($body));
    }
}
