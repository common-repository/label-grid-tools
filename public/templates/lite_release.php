<?php
/**
 * Template used for pages.
 *
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/public
 */

// Do not allow directly accessing this file.
if (! defined('ABSPATH')) {
    exit('Direct script access denied.');
}

$release_wp_id = get_the_ID();
$release_image = get_post_meta($release_wp_id, '_lgt_release_image', true);
if (empty($release_image)) {
    $release_image = get_post_meta($release_wp_id, '_lgt_gate_cover_image', true);
}
$artwork_medium = wp_get_attachment_image_src($release_image, 'lgt_artwork_medium');
$category = wp_get_object_terms($release_wp_id, 'record_label', array(
    'fields' => 'names'
));
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body id="lg_lite_page">
    <div id="lg_custom_background">
        <?php echo '<img src="' . esc_url($artwork_medium[0]) . '" alt="' . esc_attr(get_the_title()) . '" title="' . esc_attr(get_the_title()) . '">'; ?>
    </div>
    <main id="lg_content_release" class="release_detail release_lite">
        <div class="release_box_content">
            <div class="release_title">
                <h1><?php echo esc_html(get_the_title()); ?></h1>
                <?php 
                if (!empty($category[0])) {
                    echo esc_html(__('Released by ', 'label-grid-tools')) . esc_html($category[0]);
                }
                ?>
            </div>
            <div class="artwork">
                <?php echo '<img src="' . esc_url($artwork_medium[0]) . '" alt="' . esc_attr(get_the_title()) . '" title="' . esc_attr(get_the_title()) . '">'; ?>
            </div>
            <div class="release_links">
                <?php echo do_shortcode('[labelgrid-release-links]'); ?>
            </div>
        </div>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
