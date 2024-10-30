<?php

/**
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 * @author     LabelGrid <team@labelgrid.com>
 */

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class SyncContent
{

    /**
     * Total rows in the CSV file
     *
     * @since 1.0
     */
    public $total;

    /**
     * The current step being processed
     *
     * @since 1.0
     */
    public $step;

    /**
     * All steps
     *
     * @since 1.0
     */
    public $actions = array(
        'artists',
        'labels',
        'genres',
        'releases'
    );

    /**
     * The number of items to process per step
     *
     * @since 1.0
     */
    public $per_step = 5;

    /**
     * The capability required to import data
     *
     * @since 1.0
     */
    public $capability_type = 'edit_users';

    public function __construct($_step = 1, $actions = null, $background = null)
    {
        $this->step = esc_attr($_step);  // Escaped for security
        if (!empty($actions)) {
            $this->actions = $actions;
        }
        $this->done = false;

        if (empty($background)) {
            $feed = $this->lgt_getFeed($this->actions[0], 1, 1);
            $this->total = isset($feed->data->data->data->total) ? esc_html($feed->data->data->data->total) : 0;
        }
    }

    /**
     * Can we import?
     *
     * @since 1.0
     * @return bool Whether we can import or not
     */
    public function lgt_can_import()
    {
        return (bool) apply_filters('lg_import_capability', current_user_can($this->capability_type));
    }

    /**
     * Process a step
     *
     * @since 1.0
     * @return bool
     */
    public function lgt_process_step()
    {
        $more = false;
        if (!$this->lgt_can_import()) {
            LabelGrid_Tools::log_event('You do not have permission to import data.', 'warning', 'sync-catalog');
            wp_die(
                esc_html__('You do not have permission to import data.', 'label-grid-tools'),
                esc_html__('Error', 'label-grid-tools'),
                array('response' => 403)
            );
        }
        return $more;
    }

    /**
     * Return the calculated completion percentage
     *
     * @since 1.0
     * @return int
     */
    public function lgt_get_percentage_complete()
    {
        $percentage = 100;
        if ($this->total > 0) {
            $percentage = (($this->per_step * $this->step) / $this->total) * 100;
        }
        return min($percentage, 100);
    }

    /**
     * Fetch data from LabelGrid API
     *
     * @since 1.0
     * @param string $action Action type
     * @param int|null $per_step Items per step
     * @param int|null $step Current step
     * @return object|null
     */
    public function lgt_getFeed($action, $per_step = null, $step = null)
    {
        $res = null;
        $this->lg_is_active = get_option('_lgt_is_active');
        if ($this->lg_is_active == 'yes') {
            $api_access_token = esc_html(get_option('_lgt_api_key'));

            try {
                $stack = HandlerStack::create();
                $stack->push(GuzzleRetryMiddleware::factory());

                $lgt_curl_interface = get_option('_lgt_curl_interface');
                $curlinterface = ($lgt_curl_interface != "1") ? [CURLOPT_INTERFACE => esc_attr($_SERVER['SERVER_ADDR'])] : [];

                $client = new Client(['handler' => $stack]);

                $response = $client->request('POST', 'https://gate.labelgrid.com/api/import-tools/catalog', [
                    'curl' => $curlinterface,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $api_access_token
                    ],
                    'form_params' => [
                        'type' => esc_attr($action),
                        'elements_step' => esc_attr($per_step),
                        'elements_page' => esc_attr($step)
                    ]
                ]);

                if ($response->getStatusCode() == 200) {
                    $body = (string) $response->getBody();
                    $res = json_decode($body);
                    LabelGrid_Tools::log_event('GET-CATALOG-FEED | Succeeded | Action: ' . esc_html($action) . ' | Elements per step: ' . esc_html($per_step) . ' | Step: ' . esc_html($step), 'debug', 'sync-catalog');
                }
            } catch (RequestException $e) {
                LabelGrid_Tools::log_event('GET-CATALOG-FEED | Error: ' . Psr7\str($e->getRequest()) . ' | Elements per step: ' . esc_html($per_step) . ' | Step: ' . esc_html($step), 'error', 'sync-catalog');
            }
        }
        return $res;
    }

    /**
     * Deletes terms based on type
     *
     * @since 1.0
     * @param string $term Term type
     */
    public function lgt_delete_terms($term)
    {
        if (is_admin() && !empty($term)) {
            LabelGrid_Tools::log_event('TERMS | DELETE | Search Term: ' . esc_html($term), 'debug', 'sync-catalog');

            $terms = get_terms($term, array(
                'fields' => 'ids',
                'hide_empty' => false
            ));

            foreach ($terms as $termd) {
                wp_delete_term($termd, $term);
                LabelGrid_Tools::log_event('TERMS | DELETE | Term: ' . esc_html($term) . ' | Element ID: ' . esc_html($termd->ID), 'debug', 'sync-catalog');
            }
        }
    }

    /**
     * Deletes custom post types
     *
     * @since 1.0
     * @param string $term Custom post type
     */
    public function lgt_delete_customtype($term)
    {
        if (is_admin() && !empty($term)) {
            LabelGrid_Tools::log_event('CUSTOMTYPE | DELETE | Search Term: ' . esc_html($term), 'debug', 'sync-catalog');

            $mycustomposts = get_posts(array(
                'post_type' => esc_attr($term),
                'numberposts' => -1,
                'post_status' => array('pending', 'draft', 'future', 'publish', 'private', 'trash')
            ));

            foreach ($mycustomposts as $mypost) {
                LabelGrid_Tools::log_event('CUSTOMTYPE | DELETE | Term: ' . esc_html($term) . ' | Element ID: ' . esc_html($mypost->ID), 'debug', 'sync-catalog');

                $media = get_children(array(
                    'post_parent' => $mypost->ID,
                    'post_type' => 'attachment'
                ));

                if (!empty($media)) {
                    LabelGrid_Tools::log_event('CUSTOMTYPE | DELETE | Has Media | Element ID: ' . esc_html($mypost->ID), 'debug', 'sync-catalog');
                    foreach ($media as $file) {
                        LabelGrid_Tools::log_event('CUSTOMTYPE | DELETE | DELETE MEDIA | Media ID: ' . esc_html($file->ID) . ' | Element ID: ' . esc_html($mypost->ID), 'debug', 'sync-catalog');
                        wp_delete_attachment($file->ID);
                    }
                }

                wp_delete_post($mypost->ID, true);
            }
        }
    }

    /**
     * Updates post meta for a post.
     *
     * @since 1.0
     * @param integer $post_id The post ID for the post we're updating
     * @param string $field_name The field we're updating/adding/deleting
     * @param string [Optional] $value The value to update/add for field_name. If left blank, data will be deleted.
     * @return void
     */
    public function lgt_update_post_meta($post_id, $field_name, $value = '')
    {
        if (empty($value)) {
            delete_post_meta($post_id, $field_name);
            LabelGrid_Tools::log_event('POSTMETA | DELETE | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name), 'debug', 'sync-catalog');
        } elseif (!get_post_meta($post_id, $field_name)) {
            add_post_meta($post_id, $field_name, $value);
            LabelGrid_Tools::log_event('POSTMETA | ADD | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name) . ' | Value: ' . esc_html($value), 'debug', 'sync-catalog');
        } else {
            update_post_meta($post_id, $field_name, $value);
            LabelGrid_Tools::log_event('POSTMETA | UPDATE | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name) . ' | Value: ' . esc_html($value), 'debug', 'sync-catalog');
        }
    }

    /**
     * Updates term meta for a term.
     *
     * @since 1.0
     * @param integer $post_id The term ID for the term we're updating
     * @param string $field_name The field we're updating/adding/deleting
     * @param string [Optional] $value The value to update/add for field_name. If left blank, data will be deleted.
     * @return void
     */
    public function lgt_update_term_meta($post_id, $field_name, $value = '')
    {
        if (empty($value)) {
            delete_term_meta($post_id, $field_name);
            LabelGrid_Tools::log_event('TERMMETA | DELETE | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name), 'debug', 'sync-catalog');
        } elseif (!get_term_meta($post_id, $field_name)) {
            add_term_meta($post_id, $field_name, esc_html($value));
            LabelGrid_Tools::log_event('TERMMETA | ADD | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name) . ' | Value: ' . esc_html($value), 'debug', 'sync-catalog');
        } else {
            update_term_meta($post_id, $field_name, esc_html($value));
            LabelGrid_Tools::log_event('TERMMETA | UPDATE | Element ID: ' . esc_html($post_id) . ' | Field Name: ' . esc_html($field_name) . ' | Value: ' . esc_html($value), 'debug', 'sync-catalog');
        }
    }

    /**
     * Imports an image from a URL.
     *
     * @since 1.0
     * @param string $url Image URL
     * @param string $name Image name
     * @param integer|null $parent_post_id Parent post ID
     * @return int|false Attachment ID or false on failure
     */
    public function lgt_import_image($url, $name, $parent_post_id = null)
    {
        $http = new WP_Http();
        $response = $http->request(esc_url_raw($url));

        if (!is_array($response) || $response['response']['code'] != 200) {
            error_log('File upload error, code: ' . print_r($response, true));
            return false;
        }
        $upload = wp_upload_bits(basename($url), null, $response['body']);
        if (!empty($upload['error'])) {
            LabelGrid_Tools::log_event('IMPORTIMAGE | Error - ' . esc_html($upload['error']), 'error', 'sync-catalog');
            return false;
        }
        $file_path = $upload['file'];
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);
        $attachment_title = sanitize_file_name($name);
        $wp_upload_dir = wp_upload_dir();
        $post_info = array(
            'guid' => esc_url($wp_upload_dir['url'] . '/' . $file_name),
            'post_mime_type' => $file_type['type'],
            'post_title' => $attachment_title,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment($post_info, $file_path, $parent_post_id);

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        // Assign metadata to attachment
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * Checks if a URL exists
     *
     * @since 1.0
     * @param string $url The URL to check
     * @return bool True if the URL exists, false otherwise
     */
    public function does_url_exists($url)
    {
        $status = false;

        $ch = curl_init(esc_url_raw($url));
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            $status = true;
        }

        curl_close($ch);
        return $status;
    }

    /**
     * Validates a UPC barcode
     *
     * @since 1.0
     * @param string $barcode The barcode to validate
     * @return bool True if the barcode is valid, false otherwise
     */
    public function isUpc($barcode)
    {
        if (!preg_match("/^[0-9]{12}$/", esc_attr($barcode))) {
            return false;
        }

        $digits = str_split($barcode);

        // 1. Sum each of the odd-numbered digits
        $odd_sum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8] + $digits[10];

        // 2. Multiply result by three
        $odd_sum_three = $odd_sum * 3;

        // 3. Add the result to the sum of each of the even-numbered digits
        $even_sum = $digits[1] + $digits[3] + $digits[5] + $digits[7] + $digits[9];

        $total_sum = $odd_sum_three + $even_sum;

        // 4. Subtract the result from the next highest power of 10
        $next_ten = (ceil($total_sum / 10)) * 10;

        $check_digit = $next_ten - $total_sum;

        return ($check_digit == $digits[11]);
    }

    /**
     * Validates an ISRC
     *
     * @since 1.0
     * @param string $isrc The ISRC to validate
     * @return bool True if valid, false otherwise
     */
    public static function isIsrc($isrc)
    {
        return (bool) preg_match('/[A-Z]{2}[A-Z0-9]{3}[0-9]{7}/i', esc_attr($isrc));
    }
}
