<?php
require_once (plugin_dir_path(__DIR__) . 'class-sync-content-data.php');

trait SyncPage
{

    /**
     *
     * @since 1.0.0
     */
    public function lgt_sync_form_response()
    {
        $step = absint($_REQUEST['step']);

        if (is_array($_REQUEST['actions']))
            $import = new SyncContentData($step, $_REQUEST['actions']);
        else
            $import = new SyncContentData($step);

        if (! $import->lgt_can_import()) {
            wp_send_json_error(array(
                'error' => __('You do not have permission to import data', 'label-grid-tools')
            ));
        }

        $ret = $import->lgt_process_step();
        $percentage = $import->lgt_get_percentage_complete();

        if ($ret['more']) {
            $step += 1;
            wp_send_json_success(array(
                'step' => $step,
                'percentage' => $percentage,
                'total' => $import->total,
                'actions' => $import->actions
            ));
        } else {
            array_shift($import->actions);
            if (empty($import->actions))
                $import->actions = 'done';

            wp_send_json_success(array(
                'step' => 'done',
                'actions' => $import->actions,
                'message' => __('Import complete.', 'label-grid-tools')
            ));
        }
    }
}
?>