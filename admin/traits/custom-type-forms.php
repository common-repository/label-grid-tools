<?php

use Carbon_Fields\Container\Container;
use Carbon_Fields\Field\Field;

trait CustomTypeForms
{

    public function lgt_add_custom_type_fields()
    {
        $stat_general = $this->lgt_checkStatus();

        if ($stat_general == 'no') {
            $statuscolor = "red";
            $statusname = __('DISABLED', 'label-grid-tools');
        } else {
            $statuscolor = "green";
            $statusname = __('ENABLED', 'label-grid-tools');
        }
        $error_general = '';
        if ($stat_general == 'no')
            $error_general = __('<strong>LabelGrid GATE is disabled.</strong> Please verify the LabelGrid API Token and that your account is enabled for LabelGrid API Services.<a href="https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugininstallation/configuration/labelgrid-api-token" target="_blank">Get your API Token here</a>.<br><strong>LabelGrid Gate</strong> is an advanced dowload gate and pre-save tool, <a href="https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Pluginlabelgrid-gate" target="_blank">Read more about it here</a>. ', 'label-grid-tools');

        $server_host = $_SERVER['HTTP_HOST'];
        $host_ip = gethostbyname($server_host);
        $outbound_ip = get_option('_lgt_outboundip');

        if ($host_ip == $outbound_ip) $apitouse = $server_host;
        elseif (!empty($outbound_ip)) $apitouse = $outbound_ip;
        else $apitouse = __("<strong style='color:red'>ERROR - The plugin is unable to retrieve the correct API Key name.</strong><br>Please try to disable CURLOPT_INTERFACE in General Settings->Advanced.
               Get in contact with LabelGrid technical support if the issue persist.</strong>", 'label-grid-tools');


        $infopanel = '<table cellspacing="1" cellpadding="2"><tbody>
                                    
            <tr><td colspan="2"><strong>' . __('DOCUMENTATION:', 'label-grid-tools') . '</strong> <a href="https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugin" target="_blank">https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugin</a></td>
            </tr>
            <tr><td colspan="2"></td>
            </tr>
            <tr valign="top">
            	<td><strong>' . __('LabelGrid GATE Status:', 'label-grid-tools') . '</strong></td>
                <td><span style="text-transform:uppercase;font-weight:bold;color:' . $statuscolor . ' ">' . $statusname . '</span> <span style="font-weight:normal;font-style:italic;padding-left:5px;">' . $error_general . '</span></td>
            </tr>
            <tr><td><strong>' . __('String for API Key', 'label-grid-tools') . ':</strong></td>
                <td>' . $apitouse . '</td>
            </tr>
            </tbody></table>';

        $systeminfopanel = '<table cellspacing="1" cellpadding="2"><tbody>
                
            <tr><td><strong>' . __('System', 'label-grid-tools') . ':</strong></td><td>' . php_uname() . '</td></tr>
            <tr><td><strong>' . __('PHP Version', 'label-grid-tools') . ':</strong></td>
                <td>' . phpversion() . '</td>
            </tr>
            <tr><td><strong>' . __('MySQL Version', 'label-grid-tools') . ':</strong></td>
                <td>' . $this->lgt_getMySqlVersion() . '</td>
            </tr>
            <tr><td><strong>' . __('LabelGrid Plugin Version', 'label-grid-tools') . ':</strong></td>
                <td>' . $this->version . '</td>
            </tr>
            <tr><td><strong>' . __('WordPress Local Domain', 'label-grid-tools') . ':</strong></td>
                <td>' . $server_host . ' (IP: ' . $host_ip . ')</td>
            </tr>
            <tr><td><strong>' . __('Server Outbound IP', 'label-grid-tools') . ':</strong></td>
                <td>' . $outbound_ip . '</td>
            </tr>
            </tbody></table>';

        $service_reorder = Field::make('complex', 'services_order', __('RELEASES Services', 'label-grid-tools'))
            ->set_duplicate_groups_allowed(false)->set_required(true)->set_width(50)
            ->set_help_text(__('You can enable or disable services for Releases and order them.', 'label-grid-tools'));

        foreach (LabelGrid_Tools::get_link_services('releases', true) as $service_name => $service) {
            $service_id = preg_replace('/\s+/', '', $service_name);
            $store_links[] = Field::make('text', 'lgt_url_' . $service_id, $service['name'])->set_width(80);
            $store_links[] = Field::make('checkbox', 'lgt_url_' . $service_id . '_sync', __('Stop sync', 'label-grid-tools'))->set_option_value('yes')
                ->set_width(20);
            $service_reorder->add_fields($service_id, $service['name'], array());
        }

        $service_reorder_artists = Field::make('complex', 'services_order_artists', __('ARTISTS Services', 'label-grid-tools'))
            ->set_duplicate_groups_allowed(false)->set_required(true)->set_width(50)
            ->set_help_text(__('You can enable or disable services for Artists and order them.', 'label-grid-tools'));

        foreach (LabelGrid_Tools::get_link_services('artists', true) as $service_name => $service) {
            $service_id = preg_replace('/\s+/', '', $service_name);
            $store_links_artists[] = Field::make('text', 'lgt_url_' . $service_id, $service['name'])->set_width(80);
            $service_reorder_artists->add_fields($service_id, $service['name'], array());
        }

        $store_links[] = Field::make('text', 'lgt_url_freedownload', __('Fee Download', 'label-grid-tools'))->set_width(80);
        $store_links[] = Field::make('checkbox', 'lgt_url_freedownload_sync', __('Stop sync', 'label-grid-tools'))->set_option_value('yes')
            ->set_width(20);

        Container::make('theme_options', __('General Settings', 'label-grid-tools'))->set_page_file('labelgrid-tools')
            ->set_page_parent(true)
            ->add_tab(__('LabelGrid GATE', 'label-grid-tools'), array(
                Field::make('html', 'labelgrid-tools-info')->set_html($infopanel),
                Field::make('textarea', 'lgt_api_key', __('LabelGrid API Token', 'label-grid-tools'))->set_required(false)
                    ->set_help_text(sprintf(
                        __('The API Key is necessary for LabelGrid Sync functionalities and more. Each API Key is tied to a single domain/server to avoid improper use. <a href="https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugininstallation/configuration/labelgrid-api-token" target="_blank">Create your API Key</a> with this value: "%s"', 'label-grid-tools'),
                        esc_html($apitouse)
                    ))
                    ->set_rows(10)
            ))
            ->add_tab(__('General Options', 'label-grid-tools'), array(
                Field::make('select', 'lgt_debug', __('Enable Error Log Reporting', 'label-grid-tools'))->set_help_text(__('Error logs will be stored only above this level.', 'label-grid-tools'))
                    ->set_options(array(
                        'disabled' => 'Disabled',
                        'DEBUG' => 'DEBUG',
                        'INFO' => 'INFO',
                        'NOTICE' => 'NOTICE',
                        'WARNING' => 'WARNING',
                        'ERROR' => 'ERROR',
                        'CRITICAL' => 'CRITICAL',
                        'ALERT' => 'ALERT',
                        'EMERGENCY' => 'EMERGENCY'
                    ))
                    ->set_required(true)
                    ->set_default_value('ERROR'),
                Field::make('text', 'lgt_debug_retention_days', __('Log Retention Days', 'label-grid-tools'))->set_required(false)
                    ->set_help_text(__('This needs to be a numeric value, the number of days to keep the logs before cleaning. If this value is 0 the logs are cleaned every 12 hours.', 'label-grid-tools'))
                    ->set_default_value('7')
                    ->set_required(true),
                Field::make('select', 'lgt_automatic_sync', __('Automatic LabelGrid API Content Sync', 'label-grid-tools'))->set_help_text(__('If disabled, this release will NOT be synced with LabelGrid', 'label-grid-tools'))
                    ->set_options(array(
                        'enabled' => 'Enabled',
                        'disabled' => 'Disabled'
                    ))
                    ->set_required(true),
                Field::make('text', 'lgt_itunes_affiliate_token', __('Apple Affiliate Program Token', 'label-grid-tools'))->set_required(false)
                    ->set_help_text(__('More informations about Apple Affiliate Program <a href="https://affiliate.itunes.apple.com/" target="_blank">here</a>', 'label-grid-tools'))
            ))
            ->add_tab(__('Releases', 'label-grid-tools'), array(
                Field::make('separator', 'lgt_separator2', __('Release List:', 'label-grid-tools')),

                Field::make('select', 'lgt_show_unreleased', __('Show Unreleased Releases', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true),

                Field::make('separator', 'lgt_separator3', __('Pre-Save/Pre-Order:', 'label-grid-tools')),

                Field::make('select', 'lgt_spotify_pre_save', __('Spotify Pre-Save', 'label-grid-tools'))->set_width(50)
                    ->set_options(array(
                        'yes' => 'Enabled',
                        'no' => 'Disabled'
                    ))
                    ->set_required(true)
                    ->set_help_text(__('Enable Spotify Pre-Save before Release Date.<br>This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('select', 'lgt_spotify_pre_save_email', __('Spotify Pre-Save - Collect Email', 'label-grid-tools'))->set_width(50)
                    ->set_options(array(
                        'no' => __('No', 'label-grid-tools'),
                        'yes-obligatory' => __('Yes - Obligatory', 'label-grid-tools'),
                        'yes-optional' => __('Yes - Optional', 'label-grid-tools')
                    ))
                    ->set_required(true)
                    ->set_help_text(__('Enable Spotify PreSave to collect and store e-mails.<br>This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('select', 'lgt_itunes_pre_order', __('iTunes Pre-Order', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('Enable iTunes Pre-Order.This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('select', 'lgt_beatport_pre_order', __('Beatport Pre-Order', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('Enable Beatport Pre-Order.This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('separator', 'lgt_separator4', __('Release Landing Page:', 'label-grid-tools')),


                Field::make('select', 'lgt_show_spotify_player', __('Show Spotify Web Player', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true),

                Field::make('select', 'lgt_show_press', __('Show Press Release', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true),

                Field::make('select', 'lgt_show_artists', __('Show featured Artists', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('select', 'lgt_show_similar_releases', __('Show similar releases', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field can be overridden in the Release details.', 'label-grid-tools')),

                Field::make('select', 'lgt_show_below_artwork', __('Field below Artwork', 'label-grid-tools'))->set_options(array(
                    'releasecode' => 'Release Code',
                    'recordlabel' => 'Record Label'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field can be overridden in the Release details.', 'label-grid-tools'))
            ))
            ->add_tab(__('Artists', 'label-grid-tools'), array(
                Field::make('select', 'lgt_show_twitter_feed', __('Show Twitter Feed', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('Add a Bandsintown feed on Artist Pages', 'label-grid-tools')),
                Field::make('select', 'lgt_show_artist_bandsintown', __('Show Bandsintown Feed', 'label-grid-tools'))->set_options(array(
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('Add a Bandsintown feed on Artist Pages', 'label-grid-tools'))
            ))
            ->add_tab(__('Default Visible Services', 'label-grid-tools'), array(
                $service_reorder,
                $service_reorder_artists
            ))
            ->add_tab(__('Custom Services', 'label-grid-tools'), array(
                Field::make('complex', 'lgt_custom_services', __('Custom Services', 'label-grid-tools'))->set_required(false)
                    ->add_fields('customservice', __('Custom Service', 'label-grid-tools'), array(
                        Field::make('text', 'id', __('ID', 'label-grid-tools'))->set_help_text(__('Unique ID of the service. Ex: spotify/itunes/deezer', 'label-grid-tools'))
                            ->set_required(true),
                        Field::make('text', 'name', __('Name', 'label-grid-tools'))->set_help_text(__('Name displayed for the service.', 'label-grid-tools'))
                            ->set_required(true),
                        Field::make('text', 'extra', __('Link parameters', 'label-grid-tools'))->set_help_text(__('Optional: Add URL parameters to all links.', 'label-grid-tools'))
                            ->set_required(false),
                        Field::make('select', 'action', __('Preferred Action', 'label-grid-tools'))
                            ->set_options(array(
                                'none' => 'None',
                                'listen' => 'Listen on',
                                'download' => 'Download from',
                                'watch' => 'Watch on',
                            ))->set_help_text(__('Preferred action displayed on the link. Ex: Listen on CUSTOMSERVICE', 'label-grid-tools'))
                            ->set_required(true),
                        Field::make('image', 'image', __('Image', 'label-grid-tools'))->set_required(true),
                        Field::make('select', 'visibility', __('Visibility', 'label-grid-tools'))
                            ->set_options(array(
                                'artists' => 'Artists',
                                'releases' => 'Releases',
                                'artists_releases' => 'Artists and Releases',
                            ))->set_help_text(__('Where the custom service link will be visible.', 'label-grid-tools'))
                            ->set_required(true),
                    ))
                    ->set_header_template('<% if (name) { %> Custom Service: <%- name %> <% } else { %> Custom Service <% } %>')
                    ->set_help_text(__('Custom Services that can be displayed on Releases/Artists pages', 'label-grid-tools'))
            ))
            ->add_tab(__('Advanced', 'label-grid-tools'), array(
                Field::make('select', 'lgt_curl_interface', __('Disable CURLOPT_INTERFACE', 'label-grid-tools'))->set_options(array(
                    '0' => 'No',
                    '1' => 'Yes'
                ))
                    ->set_required(true)
                    ->set_default_value('0')
                    ->set_help_text(__('Disabling CURL identification may help in case of issues with API due to shared hostings.', 'label-grid-tools')),
            ))
            ->add_tab(__('System Informations', 'label-grid-tools'), array(
                Field::make('html', 'labelgrid-tools-system-info')->set_html($systeminfopanel)
            ));

        // RELEASES
        $releases_container = Container::make('post_meta', 'Release Details')->where('post_type', '=', 'release')
            ->add_tab(__('General Settings', 'label-grid-tools'), array(
                Field::make('select', 'lgt_show_list', __('Hide from Release list', 'label-grid-tools'))->set_help_text(__('If enabled the release will not show in the releases lists and will be available only with direct link.', 'label-grid-tools'))
                    ->set_options(array(
                        'no' => __('No', 'label-grid-tools'),
                        'yes' => __('Yes', 'label-grid-tools')
                    ))
                    ->set_required(true)
                    ->set_default_value('no'),

                Field::make('select', 'lgt_stop_updates', __('Disable updates', 'label-grid-tools'))->set_help_text(__('If enabled, this release will NOT be synced with LabelGrid', 'label-grid-tools'))
                    ->set_options(array(
                        'no' => __('No', 'label-grid-tools'),
                        'yes' => __('Yes', 'label-grid-tools')
                    ))
                    ->set_required(true),

                Field::make('select', 'lgt_show_artists', __('Show featured Artists', 'label-grid-tools'))->set_options(array(
                    '-' => __('Default', 'label-grid-tools'),
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field Default settings is set in the General Settings.', 'label-grid-tools')),

                Field::make('select', 'lgt_show_similar_releases', __('Show Similar Releases', 'label-grid-tools'))->set_options(array(
                    '-' => __('Default', 'label-grid-tools'),
                    'yes' => 'Enabled',
                    'no' => 'Disabled'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field Default settings is set in the General Settings.', 'label-grid-tools')),

                Field::make('select', 'lgt_show_below_artwork', __('Field below Artwork', 'label-grid-tools'))->set_options(array(
                    '-' => __('Default', 'label-grid-tools'),
                    'releasecode' => 'Release Code',
                    'recordlabel' => 'Record Label'
                ))
                    ->set_required(true)
                    ->set_help_text(__('This field Default settings is set in the General Settings.', 'label-grid-tools')),





            ))
            ->add_tab(__('Release Info', 'label-grid-tools'), array(
                Field::make('text', 'lgt_release_name', __('Release Name', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('text', 'lgt_artist_names', __('Artist Names', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('date', 'lgt_release_date', __('Release date', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('text', 'lgt_cat_number', __('Release Code', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('text', 'lgt_short_url', __('Short Url', 'label-grid-tools'))->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('image', 'lgt_release_image', __('Artwork', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid<br>Note: Set this image as a Featured Image to benefit from standard WordPress features depending from your theme.', 'label-grid-tools')),
                Field::make('image', 'lgt_release_image_banner', __('Banner Image', 'label-grid-tools'))->set_help_text(__('This field replace the default Artwork Background in the Release Banner shortcode.', 'label-grid-tools'))
            ))
            ->add_tab(__('UPC/ISRC', 'label-grid-tools'), array(
                Field::make('text', 'lgt_release_upc', __('UPC', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
                Field::make('textarea', 'lgt_release_isrc', __('ISRC', 'label-grid-tools'))->set_required(true)
                    ->set_rows(4)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools')),
            ))
            ->add_tab(__('Artists', 'label-grid-tools'), array(
                Field::make('association', 'lgt_artists', __('Select the Artists', 'label-grid-tools'))->set_types(array(
                    array(
                        'type' => 'post',
                        'post_type' => 'artist'
                    )
                ))
                    ->set_help_text(__('This field will be automatically synced with LabelGrid', 'label-grid-tools'))
            ))
            ->add_tab(__('Store Links', 'label-grid-tools'), $store_links)
            ->add_tab(__('Press Release', 'label-grid-tools'), array(
                Field::make('rich_text', 'lgt_press_release', __('Press Release', 'label-grid-tools'))->set_rows(15),
                Field::make('checkbox', 'lgt_press_release_sync', __('Stop sync press release', 'label-grid-tools'))->set_option_value('yes')
            ));


        if ($stat_general == "yes") $releases_container->add_tab(__('Pre-Order/Saves', 'label-grid-tools'), array(

            Field::make('select', 'lgt_itunes_pre_order', __('iTunes Pre-orders', 'label-grid-tools'))->set_options(array(
                '-' => __('Default', 'label-grid-tools'),
                'yes' => __('Yes', 'label-grid-tools'),
                'no' => __('No', 'label-grid-tools')
            ))
                ->set_required(true)
                ->set_default_value(get_option('_lgt_itunes_pre_order')),

            Field::make('select', 'lgt_beatport_pre_order', __('Beatport Pre-orders', 'label-grid-tools'))->set_options(array(
                '-' => __('Default', 'label-grid-tools'),
                'yes' => __('Yes', 'label-grid-tools'),
                'no' => __('No', 'label-grid-tools')
            ))
                ->set_required(true)
                ->set_default_value(get_option('_lgt_beatport_pre_order')),


            Field::make('select', 'lgt_spotify_presave', __('Spotify Pre-Save', 'label-grid-tools'))->set_options(array(
                '-' => __('Default', 'label-grid-tools'),
                'yes' => __('Yes', 'label-grid-tools'),
                'no' => __('No', 'label-grid-tools')
            ))
                ->set_required(true)
                ->set_default_value(get_option('_lgt_spotify_pre_save')),


            Field::make('complex', 'lgt_presave_accounts_follow', __('Optional Pre-Save Actions', 'label-grid-tools'))->set_required(false)
                ->add_fields('playlist_follow', __('Follow Playlist', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Playlist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted. One record per line.', 'label-grid-tools'))
                        ->set_required(true)->set_rows(4)
                ))
                ->add_fields('user_follow', __('Follow User', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('User URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted. One record per line.', 'label-grid-tools'))
                        ->set_required(true)->set_rows(4)
                ))
                ->add_fields('artist_follow', __('Follow Artist', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Artist URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted. One record per line.', 'label-grid-tools'))
                        ->set_required(true)->set_rows(4)
                ))
                ->add_fields('track_save', __('Save Track', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Track URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted. One record per line.', 'label-grid-tools'))
                        ->set_required(true)->set_rows(4)
                ))
                ->add_fields('album_save', __('Save Album', 'label-grid-tools'), array(
                    Field::make('textarea', 'link', __('Track URI/URL', 'label-grid-tools'))->set_help_text(__('URI or URL accepted. One record per line.', 'label-grid-tools'))
                        ->set_required(true)->set_rows(4)
                ))
                ->set_conditional_logic(array(
                    'relation' => 'AND',
                    array(
                        'field' => 'lgt_spotify_presave',
                        'value' => 'yes', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                        'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                    )
                ))
                ->set_help_text(__('You can optionally specify other actions on top of the pre-save standard feature', 'label-grid-tools')),


            Field::make('select', 'lgt_spotify_presave_email', __('Spotify Pre-Save - Collect Email', 'label-grid-tools'))->set_options(array(
                'no' => __('No', 'label-grid-tools'),
                'yes-obligatory' => __('Yes - Obligatory', 'label-grid-tools'),
                'yes-optional' => __('Yes - Optional', 'label-grid-tools')
            ))
                ->set_default_value(get_option('_lgt_spotify_pre_save_email'))
                ->set_conditional_logic(array(
                    'relation' => 'AND',
                    array(
                        'field' => 'lgt_spotify_presave',
                        'value' => 'yes', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                        'compare' => '=' // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                    )
                ))
                ->set_required(true),


        ));

        // ARTISTS
        Container::make('post_meta', 'Artist Details')->where('post_type', '=', 'artist')
            ->add_tab(__('General', 'label-grid-tools'), array(
                Field::make('select', 'lgt_stop_updates', __('Disable updates', 'label-grid-tools'))->set_help_text(__('If enabled, this artist will NOT be synced with LabelGrid', 'label-grid-tools'))
                    ->set_options(array(
                        'no' => __('No', 'label-grid-tools'),
                        'yes' => __('Yes', 'label-grid-tools')
                    ))
                    ->set_required(true),
                Field::make('image', 'lgt_artist_image', __('Artist image', 'label-grid-tools'))->set_required(true)
                    ->set_help_text(__('This field will be automatically synced with LabelGrid<br>Note: Do not forget to set this image as a Featured Image', 'label-grid-tools'))
            ))
            ->add_tab(__('Media Links', 'label-grid-tools'), $store_links_artists)
            ->add_tab(__('Biography', 'label-grid-tools'), array(
                Field::make('rich_text', 'lgt_biography', __('Biography', 'label-grid-tools'))->set_rows(15),
                Field::make('checkbox', 'lgt_biography_sync', __('Stop sync press release', 'label-grid-tools'))->set_option_value('yes')
            ));
    }

    public function lgt_custom_type_init()
    {

        // Release
        register_post_type('release', array(
            'labels' => array(
                'name' => __('Releases', 'label-grid-tools'),
                'singular_name' => __('Release', 'label-grid-tools'),
                'add_new_item' => __('Add New Release', 'label-grid-tools'),
                'edit_item' => __('Edit Release', 'label-grid-tools'),
                'new_item' => __('New Release', 'label-grid-tools'),
                'view_item' => __('View Release', 'label-grid-tools'),
                'view_items' => __('View Releases', 'label-grid-tools'),
                'search_items' => __('Search Releases', 'label-grid-tools'),
                'not_found' => __('No Release Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No releases found in Trash', 'label-grid-tools'),
                'all_items' => __('All Releases', 'label-grid-tools')
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'release'
            ),
            'capability_type' => 'page',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => array(
                'title',
                'thumbnail'
            ),
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" id="Music" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve"><path fill="#FFFFFF" d="M16,1H4C3.447,1,3,1.447,3,2v16c0,0.552,0.447,1,1,1h12c0.553,0,1-0.448,1-1V2C17,1.448,16.553,1,16,1zM12.795,11.519c-0.185,0.382-0.373,0.402-0.291,0C12.715,10.48,12.572,8.248,11,8v4.75c0,0.973-0.448,1.82-1.639,2.203c-1.156,0.369-2.449-0.016-2.752-0.846s0.377-1.84,1.518-2.256C8.764,11.619,9.502,11.559,10,11.75V5h1C11,7.355,15.065,6.839,12.795,11.519z"/></svg>'),
            'taxonomies' => array(
                'release_tag',
                'record_label'
            )
        ));

        // Taxonomy - Release Tag
        register_taxonomy('release_tag', array(
            'release'
        ), array(
            'hierarchical' => false,
            'labels' => array(
                'name' => __('Release Tags', 'label-grid-tools'),
                'singular_name' => __('Release Tag', 'label-grid-tools'),
                'add_new_item' => __('Add New Release Tag', 'label-grid-tools'),
                'edit_item' => __('Edit Release Tag', 'label-grid-tools'),
                'new_item' => __('New Release Tag', 'label-grid-tools'),
                'view_item' => __('View Release Tag', 'label-grid-tools'),
                'view_items' => __('View Release Tags', 'label-grid-tools'),
                'search_items' => __('Search Release Tags', 'label-grid-tools'),
                'not_found' => __('No Release Tag Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No release tags found in Trash', 'label-grid-tools'),
                'all_items' => __('All Release Tags', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'release_tag'
            )
        ));

        // Taxonomy - Record Label
        register_taxonomy('record_label', array(
            'release'
        ), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Record Labels', 'label-grid-tools'),
                'singular_name' => __('Record Label', 'label-grid-tools'),
                'add_new_item' => __('Add New Record Label', 'label-grid-tools'),
                'edit_item' => __('Edit Record Label', 'label-grid-tools'),
                'new_item' => __('New Record Label', 'label-grid-tools'),
                'view_item' => __('View Record Label', 'label-grid-tools'),
                'view_items' => __('View Record Labels', 'label-grid-tools'),
                'search_items' => __('Search Record Labels', 'label-grid-tools'),
                'not_found' => __('No Record Label Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Record Labels found in Trash', 'label-grid-tools'),
                'all_items' => __('All Record Labels', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'record_label'
            )
        ));

        // Taxonomy - Genres
        register_taxonomy('genre', array(
            'release'
        ), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Genres', 'label-grid-tools'),
                'singular_name' => __('Genre', 'label-grid-tools'),
                'add_new_item' => __('Add New Genre', 'label-grid-tools'),
                'edit_item' => __('Edit Genre', 'label-grid-tools'),
                'new_item' => __('New Genre', 'label-grid-tools'),
                'view_item' => __('View Genre', 'label-grid-tools'),
                'view_items' => __('View Genres', 'label-grid-tools'),
                'search_items' => __('Search Genres', 'label-grid-tools'),
                'not_found' => __('No Genre Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Genres found in Trash', 'label-grid-tools'),
                'all_items' => __('All Genres', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'genre'
            )
        ));

        // Artist
        register_post_type('artist', array(
            'labels' => array(
                'name' => __('Artist', 'label-grid-tools'),
                'singular_name' => __('Artists', 'label-grid-tools'),
                'add_new_item' => __('Add New Artist', 'label-grid-tools'),
                'edit_item' => __('Edit Artist', 'label-grid-tools'),
                'new_item' => __('New Artist', 'label-grid-tools'),
                'view_item' => __('View Artist', 'label-grid-tools'),
                'view_items' => __('View Artists', 'label-grid-tools'),
                'search_items' => __('Search Artists', 'label-grid-tools'),
                'not_found' => __('No Artist Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Artists found in Trash', 'label-grid-tools'),
                'all_items' => __('All Artists', 'label-grid-tools')
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'artist'
            ),
            'capability_type' => 'page',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 5,
            'supports' => array(
                'title',
                'thumbnail'
            ),
            'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg aria-hidden="true" data-prefix="fas" data-icon="headphones-alt" class="svg-inline--fa fa-headphones-alt fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M160 288h-16c-35.35 0-64 28.7-64 64.12v63.76c0 35.41 28.65 64.12 64 64.12h16c17.67 0 32-14.36 32-32.06V320.06c0-17.71-14.33-32.06-32-32.06zm208 0h-16c-17.67 0-32 14.35-32 32.06v127.88c0 17.7 14.33 32.06 32 32.06h16c35.35 0 64-28.71 64-64.12v-63.76c0-35.41-28.65-64.12-64-64.12zM256 32C112.91 32 4.57 151.13 0 288v112c0 8.84 7.16 16 16 16h16c8.84 0 16-7.16 16-16V288c0-114.67 93.33-207.8 208-207.82 114.67.02 208 93.15 208 207.82v112c0 8.84 7.16 16 16 16h16c8.84 0 16-7.16 16-16V288C507.43 151.13 399.09 32 256 32z"></path></svg>'),
            'taxonomies' => array(
                'artist_tag',
                'artist_category'
            )
        ));

        // Taxonomy - Artist Tag
        register_taxonomy('artist_tag', array(
            'artist'
        ), array(
            'hierarchical' => false,
            'labels' => array(
                'name' => __('Artists Tags', 'label-grid-tools'),
                'singular_name' => __('Artist Tag', 'label-grid-tools'),
                'add_new_item' => __('Add New Artist Tag', 'label-grid-tools'),
                'edit_item' => __('Edit Artist Tag', 'label-grid-tools'),
                'new_item' => __('New Artist Tag', 'label-grid-tools'),
                'view_item' => __('View Artist Tag', 'label-grid-tools'),
                'view_items' => __('View Artist Tags', 'label-grid-tools'),
                'search_items' => __('Search Artist Tags', 'label-grid-tools'),
                'not_found' => __('No Artist Tag Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Artist Tags found in Trash', 'label-grid-tools'),
                'all_items' => __('All Artist Tags', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'artist_tag'
            )
        ));

        // Taxonomy - Category
        register_taxonomy('artist_category', array(
            'artist'
        ), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Categories', 'label-grid-tools'),
                'singular_name' => __('Category', 'label-grid-tools'),
                'add_new_item' => __('Add New Category', 'label-grid-tools'),
                'edit_item' => __('Edit Category', 'label-grid-tools'),
                'new_item' => __('New Category', 'label-grid-tools'),
                'view_item' => __('View Category', 'label-grid-tools'),
                'view_items' => __('View Categories', 'label-grid-tools'),
                'search_items' => __('Search Categories', 'label-grid-tools'),
                'not_found' => __('No Category Found', 'label-grid-tools'),
                'not_found_in_trash' => __('No Categories found in Trash', 'label-grid-tools'),
                'all_items' => __('All Categories', 'label-grid-tools')
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'artist_category'
            )
        ));
    }
}
