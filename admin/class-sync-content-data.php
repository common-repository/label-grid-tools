<?php

/**
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 * @author     LabelGrid <team@labelgrid.com>
 */
set_time_limit(0);

require_once(plugin_dir_path(__DIR__) . 'admin/class-sync-content.php');
include_once(ABSPATH . WPINC . '/class-wp-http.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class SyncContentData extends SyncContent
{

    /**
     * Process a step
     *
     * @since 1.0
     * @return bool
     */
    public function lgt_process_step()
    {
        if (empty(get_option('_lgt_first_import'))) {
            update_option('_lgt_first_import', true);
        }

        $more = false;
        if (!$this->lgt_can_import()) {
            LabelGrid_Tools::log_event(
                esc_html__("User doesn't have the permissions to import data.", 'label-grid-tools'),
                'error',
                'sync-catalog'
            );
            wp_die(
                esc_html__('You do not have permission to import data.', 'label-grid-tools'),
                esc_html__('Error', 'label-grid-tools'),
                array(
                    'response' => 403
                )
            );
        }

        if ($this->step == 1 && !empty($_POST['firstAction']) && $_POST['firstAction'] == true) {
            $formdata = array();
            parse_str($_POST['form'], $formdata);
            if (!empty($formdata['delete_artists']) && $formdata['delete_artists'] == 'on')
                $this->lgt_delete_customtype('artist');
            if (!empty($formdata['delete_releases']) && $formdata['delete_releases'] == 'on')
                $this->lgt_delete_customtype('release');
            if (!empty($formdata['delete_genres']) && $formdata['delete_genres'] == 'on')
                $this->lgt_delete_terms('genre');
            if (!empty($formdata['delete_labels']) && $formdata['delete_labels'] == 'on')
                $this->lgt_delete_terms('record_label');
            if (!empty($formdata['delete_artist_category']) && $formdata['delete_artist_category'] == 'on')
                $this->lgt_delete_terms('artist_category');
            if (!empty($formdata['delete_release_tag']) && $formdata['delete_release_tag'] == 'on')
                $this->lgt_delete_terms('release_tag');
            if (!empty($formdata['delete_artist_tag']) && $formdata['delete_artist_tag'] == 'on')
                $this->lgt_delete_terms('artist_tag');
            if (!empty($formdata['force_sync_releases']) && $formdata['force_sync_releases'] == 'on') {
                update_option('_lgt_force_sync_release', true);
                LabelGrid_Tools::log_event('Force Sync Release TRUE', 'debug', 'sync-catalog');
            } else {
                update_option('_lgt_force_sync_release', false);
                LabelGrid_Tools::log_event('Force Sync Release FALSE', 'debug', 'sync-catalog');
            }
        }

        $i = 1;
        $offset = $this->step > 1 ? ($this->per_step * ($this->step - 1)) : 0;

        if ($this->step > ceil($this->total / $this->per_step)) {
            $this->done = true;
            LabelGrid_Tools::log_event('COMPLETED | Step: ' . $this->step . ' | Elements per step: ' . $this->per_step . ' Processed: ' . $offset . ' | Total: ' . $this->total, 'info', 'sync-catalog');
        } else {
            $this->done = false;
            LabelGrid_Tools::log_event('Processing: ' . $this->actions[0] . ' | Step: ' . $this->step . ' | Elements per step: ' . $this->per_step . ' Processed: ' . $offset . ' | Total: ' . $this->total, 'debug', 'sync-catalog');
        }

        if (!$this->done) {
            $more = true;

            $feed = $this->lgt_getFeed($this->actions[0], $this->per_step, $this->step);

            if (isset($feed->success) && $feed->success === true) {

                if (!empty($feed->data->data->data))
                    $content_data = $feed->data->data->data;
                else {
                    LabelGrid_Tools::log_event('Update - No data retrieved', 'error', 'sync-catalog');
                    exit();
                }

                if (isset($content_data)) {

                    foreach ($content_data as $content) {

                        // Done with this batch
                        if ($i > $this->per_step) {
                            break;
                        }

                        $function_name = 'lgt_create_' . $this->actions[0];
                        $this->$function_name($content);

                        $i++;
                    }
                }
            }
        }

        return array(
            'more' => $more
        );
    }

    public function lgt_process_silent_update()
    {
        if ((!empty(get_option('_lgt_sync_enabled')) && get_option('_lgt_sync_enabled') == 'disabled') || empty(get_option('_lgt_first_import'))) {
            exit();
        }
        LabelGrid_Tools::log_event('Silent Update - Start task', 'info', 'sync-catalog');

        update_option('_lgt_auto_sync_last', date("Y-m-d H:i:s"));

        update_option('_lgt_force_sync_release', false);

        foreach ($this->actions as $action) {
            $feed = $this->lgt_getFeed($action, 1000, 1);

            if (isset($feed->success) && $feed->success === true) {

                if (!empty($feed->data->data->data))
                    $content_data = $feed->data->data->data;
                else {
                    LabelGrid_Tools::log_event('Silent Update - No data retrieved || Action: ' . $action . ' feed: ' . print_r($feed->data, true), 'error', 'sync-catalog');
                    exit();
                }

                if (isset($content_data)) {
                    LabelGrid_Tools::log_event('Silent Update - Data correctly retrieved from API || Action: ' . $action, 'info', 'sync-catalog');

                    foreach ($content_data as $content) {
                        $function_name = 'lgt_create_' . $action;
                        $this->$function_name($content);
                    }
                }
            }
        }

        LabelGrid_Tools::log_event('Finished ', 'info', 'sync-catalog');
    }

    public function lgt_create_artists($artist)
    {
        if (empty($artist->id)) {
            LabelGrid_Tools::log_event('ARTIST | Error: The Artist ID is empty.', 'error', 'sync-catalog');
            return false;
        }

        $count_updated = 0;
        $count_new = 0;

        $update = false;
        $skip = false;

        $rd_args = array(
            'meta_query' => array(
                array(
                    'key' => '_lgt_labelgrid_id',
                    'value' => $artist->id
                )
            ),
            'post_type' => 'artist',
            'post_status' => array(
                'pending',
                'draft',
                'future',
                'publish',
                'private',
                'trash'
            )
        );

        $check_if_exist = new WP_Query($rd_args);

        if ($check_if_exist->have_posts()) {

            while ($check_if_exist->have_posts()) {
                $check_if_exist->the_post();
                $post_old_id = get_the_ID();

                $last_edited = get_post_meta($post_old_id, '_lgt_labelgrid_last_edit', true);
                $stop_updates = get_post_meta($post_old_id, '_lgt_stop_updates', true);

                if ($artist->dateLastEdited > $last_edited) {
                    if ($stop_updates == 'no') {
                        $update = true;
                        $skip = false;
                    } else {
                        $skip = true;
                    }
                } else {
                    $skip = true;
                }
            }
            wp_reset_postdata();
        }

        if (empty($artist->artistName))
            $skip = true;

        LabelGrid_Tools::log_event('ARTIST | Name: ' . $artist->artistName . ' || Last Edit LG: ' . $artist->dateLastEdited . ' || Last Import WP: ' . $last_edited, 'debug', 'sync-catalog');

        $the_post_id = 0;
        if ($update === true) {

            $time = current_time('mysql');

            $my_post = array(
                'ID' => $post_old_id,
                'post_title' => $artist->artistName,
                'post_type' => 'artist',
                'post_name' => $artist->artistName,
                'post_modified'   => $time,
                'post_modified_gmt' =>  get_gmt_from_date($time)
            );
            $the_post_id = wp_update_post($my_post);
            LabelGrid_Tools::log_event('ARTIST | UPDATE | Name: ' . $artist->artistName . ' || Last Edit LG: ' . $artist->dateLastEdited . ' || Last Import WP: ' . $last_edited, 'info', 'sync-catalog');
            $count_updated++;
        } elseif ($skip === false) {
            $my_post = array(
                'post_title' => $artist->artistName,
                'post_status' => 'publish',
                'post_type' => 'artist',
                'post_status' => 'draft'
            );
            $the_post_id = wp_insert_post($my_post);
            $this->lgt_update_post_meta($the_post_id, '_lgt_stop_updates', 'no');
            $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_id', $artist->id);
            LabelGrid_Tools::log_event('ARTIST | CREATED | Name: ' . $artist->artistName, 'info', 'sync-catalog');
            $count_new++;

            // Associate default category
            $terms = get_term_by('slug', 'record-label', 'artist_category');

            if (!empty($terms)) {
                wp_set_object_terms($the_post_id, $terms->term_taxonomy_id, 'artist_category', true);
            } else {
                $the_label_id = wp_insert_term('Record Label', 'artist_category');
                wp_set_object_terms($the_post_id, $the_label_id, 'artist_category', true);
            }
            wp_reset_postdata();
        }

        if (is_wp_error($the_post_id)) {
            $error_string = $the_post_id->get_error_message();
            LabelGrid_Tools::log_event('ARTIST | Error: ' . $error_string . ' || Artist ID: ' . $artist->id . ' || Name: ' . $artist->artistName, 'error', 'sync-catalog');
        } elseif (!empty($the_post_id) && $the_post_id != 0) {

            $last_edited_image = get_post_meta($post_old_id, '_lgt_labelgrid_last_edit_image', true);

            if (!empty($artist->photo_url) && $artist->dateImageUpdated > $last_edited_image) {

                if (!$this->does_url_exists($artist->photo_url)) {
                    LabelGrid_Tools::log_event('ARTIST | Error: Image 404 || Artist ID: ' . $artist->id . ' || Name: ' . $artist->artistName . ' URL: ' . $artist->photo_url, 'error', 'sync-catalog');
                } else {

                    $imageid = $this->lgt_import_image($artist->photo_url, $artist->artistName, $the_post_id);
                    if (!empty($imageid)) {
                        if ($update === true)
                            wp_delete_attachment(get_post_meta($the_post_id, '_lgt_artist_image', true));
                        $this->lgt_update_post_meta($the_post_id, '_lgt_artist_image', $imageid);
                        set_post_thumbnail($the_post_id, $imageid);

                        LabelGrid_Tools::log_event('ARTIST | Image Updated || Image id: ' . $imageid . ' || Last Edit LG: ' . $artist->dateImageUpdated . ' || Last Import WP: ' . $last_edited_image, 'debug', 'sync-catalog');

                        // Update latest edit date
                        $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_last_edit_image', $artist->dateImageUpdated);
                    }
                }
            } else
                LabelGrid_Tools::log_event('ARTIST | Error: No image associated || Artist ID: ' . $artist->id . ' || Name: ' . $artist->artistName, 'warning', 'sync-catalog');

            $this->lgt_update_post_meta($the_post_id, '_lgt_biography', $artist->bioFull);

            // links
            foreach (LabelGrid_Tools::get_link_services('artists') as $service_id => $service) {
                if (!empty($artist->{$service_id . 'Url'}))
                    $this->lgt_update_post_meta($the_post_id, '_lgt_url_' . $service_id . '', $artist->{$service_id . 'Url'});
            }

            // Update latest edit date
            $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_last_edit', $artist->dateLastEdited);

            LabelGrid_Tools::log_event('ARTIST | FINISHED | Name: ' . $artist->artistName, 'info', 'sync-catalog');
        }

        return array(
            'updated' => $count_updated,
            'new' => $count_new
        );
    }

    public function lgt_create_releases($release)
    {
        if (empty($release->id)) {
            LabelGrid_Tools::log_event('RELEASE | Error: The Release ID is empty.', 'error', 'sync-catalog');
            return false;
        }

        $count_updated = 0;
        $count_new = 0;

        $update = false;
        $skip = false;

        $rd_args = array(
            'meta_query' => array(
                array(
                    'key' => '_lgt_labelgrid_id',
                    'value' => $release->id
                )
            ),
            'post_type' => 'release',
            'post_status' => array(
                'pending',
                'draft',
                'future',
                'publish',
                'private',
                'trash'
            )
        );

        $check_if_exist = new WP_Query($rd_args);

        if ($check_if_exist->have_posts()) {

            while ($check_if_exist->have_posts()) {
                $check_if_exist->the_post();
                $post_old_id = get_the_ID();
                $last_edited = get_post_meta($post_old_id, '_lgt_labelgrid_last_edit', true);
                $stop_updates = get_post_meta($post_old_id, '_lgt_stop_updates', true);

                if ($release->dateLastEdited > $last_edited) {
                    if ($stop_updates == 'no') {
                        $update = true;
                        $skip = false;
                    } else {
                        $skip = true;
                    }
                } else {
                    $skip = true;
                }
            }
            wp_reset_postdata();
        }

        $force_releases = get_option('_lgt_force_sync_release');

        if ($force_releases && $post_old_id) {
            $update = true;
            $skip = false;
        }

        if (empty($release->title))
            $skip = true;

        LabelGrid_Tools::log_event('RELEASE | Release CAT#:' . $release->cat . ' || Title: ' . $release->title . ' || Last Edit LG: ' . $release->dateLastEdited . ' || Last Import WP: ' . $last_edited, 'debug', 'sync-catalog');

        $the_post_id = 0;
        if ($update === true) {

            $status = get_post_status($post_old_id);
            $last_edited = get_post_meta($post_old_id, '_lgt_labelgrid_last_edit', true);
            $time = current_time('mysql');

            $my_post = array(
                'ID' => $post_old_id,
                'post_title' => $release->displayArtist . ' - ' . $release->title,
                'post_type' => 'release',
                'post_status' => $status,
                'post_modified'   => $time,
                'post_modified_gmt' =>  get_gmt_from_date($time),
                'post_name' => $release->displayArtist . ' - ' . $release->title
            );
            $the_post_id = wp_update_post($my_post);
            LabelGrid_Tools::log_event('RELEASE | UPDATE | Release CAT#:' . $release->cat . ' || Title: ' . $release->title . ' || Last Edit LG: ' . $release->dateLastEdited . ' || Last Import WP: ' . $last_edited, 'info', 'sync-catalog');
            $count_updated++;
        } elseif ($skip === false) {
            $my_post = array(
                'post_title' => $release->displayArtist . ' - ' . $release->title,
                'post_status' => 'draft',
                'post_type' => 'release'
            );
            $the_post_id = wp_insert_post($my_post);
            $this->lgt_update_post_meta($the_post_id, '_lgt_stop_updates', 'no');
            $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_id', $release->id);
            $this->lgt_update_post_meta($the_post_id, '_lgt_show_list', 'no');
            LabelGrid_Tools::log_event('RELEASE | CREATED |  Release CAT#:' . $release->cat . ' || Title: ' . $release->title, 'info', 'sync-catalog');
            $count_new++;
        }

        if (is_wp_error($the_post_id)) {
            $error_string = $the_post_id->get_error_message();
            LabelGrid_Tools::log_event('RELEASE - Error: ' . $error_string . ' ID: ' . $release->id, 'error', 'sync-catalog');
        } elseif (!empty($the_post_id) && $the_post_id != 0) {

            $last_edited_image = get_post_meta($post_old_id, '_lgt_labelgrid_last_edit_image', true);

            if (!empty($release->frontCover_url_lgsource) && $release->dateImageUpdated > $last_edited_image) {
                if (!$this->does_url_exists($release->frontCover_url_lgsource)) {
                    LabelGrid_Tools::log_event('RELEASE | Error: Image 404 || Release CAT#:' . $release->cat . ' || Title: ' . $release->title . ' Image URL: ' . $release->frontCover_url_lgsource, 'error', 'sync-catalog');
                } else {
                    $imageid = $this->lgt_import_image($release->frontCover_url_lgsource, $release->displayArtist . ' - ' . $release->title, $the_post_id);
                    if (!empty($imageid)) {
                        if ($update === true)
                            wp_delete_attachment(get_post_meta($the_post_id, '_lgt_release_image', true));
                        $this->lgt_update_post_meta($the_post_id, '_lgt_release_image', $imageid);
                        set_post_thumbnail($the_post_id, $imageid);

                        LabelGrid_Tools::log_event('RELEASE | Image Updated || Image id: ' . $imageid . ' || Release CAT#:' . $release->cat . ' || Title: ' . $release->title, 'debug', 'sync-catalog');

                        // Update latest edit date
                        $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_last_edit_image', $release->dateImageUpdated);
                    }
                }
            } else
                LabelGrid_Tools::log_event('RELEASE | Error: No image associated || Release ID: ' . $release->id . ' || Title: ' . $release->title, 'warning', 'sync-catalog');

            LabelGrid_Tools::log_event('RELEASE | full data ' . print_r($release, true), 'debug', 'sync-catalog');

            $release_isrc = array();
            foreach ($release->tracks as $track) {
                if ($this->isIsrc(str_replace('-', '', $track->isrc)))
                    $release_isrc[] = str_replace('-', '', $track->isrc);
            }

            if ($this->isUpc($release->barcodeNumber))
                $this->lgt_update_post_meta($the_post_id, '_lgt_release_upc', $release->barcodeNumber);
            $this->lgt_update_post_meta($the_post_id, '_lgt_release_isrc', implode(PHP_EOL, $release_isrc));

            $this->lgt_update_post_meta($the_post_id, '_lgt_cat_number', $release->cat);
            $this->lgt_update_post_meta($the_post_id, '_lgt_release_date', str_replace(' 00:00:00', '', $release->releaseDate));
            $this->lgt_update_post_meta($the_post_id, '_lgt_short_url', $release->shortUrl);
            $this->lgt_update_post_meta($the_post_id, '_lgt_artist_names', $release->displayArtist);
            $this->lgt_update_post_meta($the_post_id, '_lgt_release_name', $release->title);
            $this->lgt_update_post_meta($the_post_id, '_lgt_press_release', $release->descriptionLong);
            $this->lgt_update_post_meta($the_post_id, '_lgt_allow_itunes_preorders', $release->allowiTunesPreorders);
            $this->lgt_update_post_meta($the_post_id, '_lgt_allow_beatport_preorders', $release->allowiTunesPreorders);
            $this->lgt_update_post_meta($the_post_id, '_lgt_show_similar_releases', '-');
            $this->lgt_update_post_meta($the_post_id, '_lgt_show_artists', '-');
            $this->lgt_update_post_meta($the_post_id, '_lgt_show_below_artwork', '-');

            // links
            foreach (LabelGrid_Tools::get_link_services('releases') as $service_id => $service) {
                if (empty(get_post_meta($the_post_id, '_lgt_url_' . $service_id . '_sync', true)) || get_post_meta($the_post_id, '_lgt_url_' . $service_id . '_sync', true) != "yes" && !empty($release->{$service_id . 'Url'}))
                    $this->lgt_update_post_meta($the_post_id, '_lgt_url_' . $service_id . '', $release->{$service_id . 'Url'});
            }

            // artists
            $artists = explode(",", $release->artistIds);
            $artists_array = array();
            $artist = null;
            foreach ($artists as $artist) {
                LabelGrid_Tools::log_event('RELEASE | LINK ARTIST || Artist id: ' . $artist, 'debug', 'sync-catalog');

                $rd_args = array(
                    'post_type' => 'artist',
                    'posts_per_page' => 1,
                    'meta_query' => array(
                        array(
                            'key' => '_lgt_labelgrid_id',
                            'compare' => '=',
                            'value' => $artist
                        )
                    )
                );

                $artist_obj = new WP_Query($rd_args);

                if ($artist_obj->have_posts()) :
                    while ($artist_obj->have_posts()) :
                        $artist_obj->the_post();
                        $artists_array[] = array(
                            "id" => get_the_ID(),
                            "value" => "post:artist:" . get_the_ID(),
                            "type" => 'post',
                            'subtype' => 'artist'
                        );
                    endwhile;
                    LabelGrid_Tools::log_event('RELEASE | LINK ARTIST SUCCEDED | Artist id: ' . $artist . ' || Artist WP id: ' . get_the_ID(), 'debug', 'sync-catalog');

                    wp_reset_postdata();


                endif;
            }
            if (is_array($artists_array))
                carbon_set_post_meta($the_post_id, 'lgt_artists', $artists_array);

            // reset label
            wp_set_object_terms($the_post_id, null, 'record_label');

            // reset genres
            wp_set_object_terms($the_post_id, null, 'genre');

            // associate label
            $rd_args = array(
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => '_lgt_labelgrid_id',
                        'value' => $release->labelId
                    )
                ),
                'taxonomy' => 'record_label'
            );

            $terms = get_terms($rd_args);

            if (!empty($terms)) {

                foreach ($terms as $label) {
                    wp_set_object_terms($the_post_id, $label->term_taxonomy_id, 'record_label', true);
                }
                wp_reset_postdata();
            }

            // associate genres #1
            $rd_args = array(
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => '_lgt_labelgrid_id',
                        'value' => $release->genre1->id
                    )
                ),
                'taxonomy' => 'genre'
            );

            $terms = get_terms($rd_args);

            if (!empty($terms)) {

                foreach ($terms as $label) {
                    wp_set_object_terms($the_post_id, $label->term_taxonomy_id, 'genre', true);
                }
                wp_reset_postdata();
            }

            // associate genres #2
            $rd_args = array(
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => '_lgt_labelgrid_id',
                        'value' => $release->genre2->id
                    )
                ),
                'taxonomy' => 'genre'
            );

            $terms = get_terms($rd_args);

            if (!empty($terms)) {

                foreach ($terms as $label) {
                    wp_set_object_terms($the_post_id, $label->term_taxonomy_id, 'genre', true);
                }
                wp_reset_postdata();
            }

            if (!empty($release->genre3->id)) {
                // associate genres #3
                $rd_args = array(
                    'hide_empty' => false,
                    'meta_query' => array(
                        array(
                            'key' => '_lgt_labelgrid_id',
                            'value' => $release->genre3->id
                        )
                    ),
                    'taxonomy' => 'genre'
                );

                $terms = get_terms($rd_args);

                if (!empty($terms)) {

                    foreach ($terms as $label) {
                        wp_set_object_terms($the_post_id, $label->term_taxonomy_id, 'genre', true);
                    }
                    wp_reset_postdata();
                }
            }

            // Update latest edit date
            $this->lgt_update_post_meta($the_post_id, '_lgt_labelgrid_last_edit', $release->dateLastEdited);

            LabelGrid_Tools::log_event('RELEASE | FINISHED | Release CAT#:' . $release->cat . ' || Title: ' . $release->title, 'info', 'sync-catalog');
        }

        return array(
            'updated' => $count_updated,
            'new' => $count_new
        );
    }

    public function lgt_create_labels($record_label)
    {
        if (empty($record_label->id)) {
            LabelGrid_Tools::log_event('RECORDLABEL | Error: The Record Label ID is empty.', 'error', 'sync-catalog');
            return false;
        }

        $count_updated = 0;
        $count_new = 0;

        $update = false;
        $skip = false;

        $rd_args = array(
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_lgt_labelgrid_id',
                    'value' => $record_label->id
                )
            ),
            'taxonomy' => 'record_label'
        );

        $terms = get_terms($rd_args);

        if (!empty($terms)) {

            foreach ($terms as $label) {

                $post_old_id = $label->term_taxonomy_id;

                $last_edited = get_term_meta($post_old_id, '_lgt_labelgrid_last_edit', true);
                $stop_updates = get_term_meta($post_old_id, '_lgt_stop_updates', true);

                if ($record_label->timestamp > $last_edited) {
                    if ($stop_updates == 'no') {
                        $update = true;
                        $skip = false;
                    } else {
                        $skip = true;
                    }
                } else {
                    $skip = true;
                }
            }
            wp_reset_postdata();
        }

        $the_post_id = 0;
        if ($update === true) {
            $my_post = array(
                'name' => $record_label->name
            );
            $the_post_id = wp_update_term($post_old_id, 'record_label', $my_post);
            LabelGrid_Tools::log_event('RECORDLABEL | UPDATE | Name:' . $record_label->name . ' || Last Edit LG: ' . $record_label->timestamp . ' || Last Import WP: ' . $last_edited, 'info', 'sync-catalog');
            $count_updated++;
        } elseif ($skip === false) {
            $the_post_id = wp_insert_term($record_label->name, 'record_label');
            LabelGrid_Tools::log_event('RECORDLABEL | CREATED | Name:' . $record_label->name, 'info', 'sync-catalog');
            $count_new++;
        }

        if (is_wp_error($the_post_id)) {
            $error_string = $the_post_id->get_error_message();
            LabelGrid_Tools::log_event('RECORDLABEL | Error: ' . $error_string . ' || Record Label LG ID: ' . $record_label->id, 'error', 'sync-catalog');
        } elseif (!empty($the_post_id) && !empty($the_post_id['term_taxonomy_id'])) {

            $the_post_id = $the_post_id['term_taxonomy_id'];

            if ($update === false) {
                $this->lgt_update_term_meta($the_post_id, '_lgt_stop_updates', 'no');
                $this->lgt_update_term_meta($the_post_id, '_lgt_labelgrid_id', $record_label->id);
            }

            $this->lgt_update_term_meta($the_post_id, '_lgt_labelgrid_last_edit', $record_label->timestamp);

            if (!empty($record_label->image_url)) {

                if (!$this->does_url_exists($record_label->image_url)) {
                    LabelGrid_Tools::log_event('LABEL | Error: Image 404 || Label ID: ' . $record_label->id . ' || Name: ' . $record_label->name . ' URL: ' . $record_label->image_url, 'error', 'sync-catalog');
                } else {
                    $imageid = $this->lgt_import_image($record_label->image_url, $record_label->name);
                    if (!empty($imageid))
                        $this->lgt_update_term_meta($the_post_id, '_lgt_record_label_image', $imageid);
                }
            }

            LabelGrid_Tools::log_event('RECORDLABEL | FINISHED | Name:' . $record_label->name, 'info', 'sync-catalog');
        }

        return array(
            'updated' => $count_updated,
            'new' => $count_new
        );
    }

    public function lgt_create_genres($genre)
    {
        if (empty($genre->id)) {
            LabelGrid_Tools::log_event('GENRE | Error: The Genre ID is empty.', 'error', 'sync-catalog');
            return false;
        }

        $count_updated = 0;
        $count_new = 0;

        $update = false;
        $skip = false;

        $rd_args = array(
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_lgt_labelgrid_id',
                    'value' => $genre->id
                )
            ),
            'taxonomy' => 'genre'
        );

        $terms = get_terms($rd_args);

        if (!empty($terms)) {

            foreach ($terms as $label) {

                $post_old_id = $label->term_taxonomy_id;

                $last_edited = get_term_meta($post_old_id, '_lgt_labelgrid_last_edit', true);
                $stop_updates = get_term_meta($post_old_id, '_lgt_stop_updates', true);

                if ($genre->timestamp > $last_edited) {

                    if ($stop_updates == 'no') {
                        $update = true;
                        $skip = false;
                    } else {
                        $skip = true;
                    }
                } else {
                    $skip = true;
                }
            }
            wp_reset_postdata();
        }

        $the_post_id = 0;

        if ($genre->active == 'off')
            $skip = true;

        if ($update === true) {

            $parent_id = $this->lg_genre_categories($genre->category, $genre->baseGenre);

            $args = array(
                'name' => $genre->name,
                'parent' => $parent_id
            );

            $the_post_id = wp_update_term($post_old_id, 'genre', $args);
            LabelGrid_Tools::log_event('GENRE | UPDATE | Name:' . $genre->name . ' || Last Edit LG: ' . $genre->timestamp . ' || Last Import WP: ' . $last_edited, 'info', 'sync-catalog');
            $count_updated++;
        } elseif ($skip === false) {

            $parent_id = $this->lg_genre_categories($genre->category, $genre->baseGenre);

            $args = array(
                'parent' => $parent_id,
                'name' => $genre->name
            );

            $the_post_id = wp_insert_term($genre->name, 'genre', $args);
            LabelGrid_Tools::log_event('GENRE | CREATED | Name:' . $genre->name, 'info', 'sync-catalog');
            $count_new++;
        }

        if (is_wp_error($the_post_id)) {
            $error_string = $the_post_id->get_error_message();
            LabelGrid_Tools::log_event('GENRE | Error: ' . $error_string . ' Genre LG ID: ' . $genre->id, 'error', 'sync-catalog');
        } elseif (!empty($the_post_id) && !empty($the_post_id['term_taxonomy_id'])) {

            $the_post_id = $the_post_id['term_taxonomy_id'];

            if ($update === false) {
                $this->lgt_update_term_meta($the_post_id, '_lgt_stop_updates', 'no');
                $this->lgt_update_term_meta($the_post_id, '_lgt_labelgrid_id', $genre->id);
            }

            $this->lgt_update_term_meta($the_post_id, '_lgt_labelgrid_last_edit', $genre->timestamp);

            LabelGrid_Tools::log_event('GENRE | FINISHED | Name:' . $genre->name, 'info', 'sync-catalog');
        }

        return array(
            'updated' => $count_updated,
            'new' => $count_new
        );
    }

    private function lg_genre_categories($category, $basegenre)
    {
        if (!empty($category)) {

            $rd_args = array(
                'hide_empty' => false,
                'name' => $category,
                'taxonomy' => 'genre',
                'parent' => 0
            );

            $terms_category = get_terms($rd_args);

            if (!empty($terms_category))
                foreach ($terms_category as $_category) {
                    $return = $_category->term_taxonomy_id;
                }
            else {
                $return = wp_insert_term($category, 'genre');
                $return = $return['term_taxonomy_id'];
            }
        }

        if (!empty($basegenre)) {
            $rd_args = array(
                'hide_empty' => false,
                'name' => $basegenre,
                'taxonomy' => 'genre',
                'parent' => $return
            );

            $terms_basegenre = get_terms($rd_args);

            if (!empty($terms_basegenre))
                foreach ($terms_basegenre as $_basegenre)
                    $return = $_basegenre->term_taxonomy_id;
            else {
                $args = array(
                    'parent' => $return,
                    'name' => $basegenre
                );

                $return = wp_insert_term($basegenre, 'genre', $args);
                $return = $return['term_taxonomy_id'];
            }
        }

        return $return;
    }
}
