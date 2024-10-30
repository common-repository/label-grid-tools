<?php

/**
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 */

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class System_Logs_Table extends WP_List_Table
{
    private $table_name;

    function __construct()
    {
        parent::__construct(array(
            'singular' => 'log',
            'plural'   => 'logs',
            'ajax'     => true
        ));

        add_filter('views_tools_page_log-viewer', array($this, 'my_post_views'));

        $this->table_name = LabelGrid_Tools::lgt_prefixTableName('logs', true);
    }

    function my_post_views($views)
    {
        global $wpdb;

        $sql_results = $wpdb->get_results("SELECT DISTINCT channel, COUNT(1) AS count FROM `" . esc_sql($wpdb->prefix . $this->table_name) . "` GROUP BY channel ORDER BY channel");
        $views = array();

        foreach ($sql_results as $row) {
            $channel = esc_attr(strtolower(str_replace(' ', '-', $row->channel)));
            $url_action = urlencode($channel);

            $views[$channel] = '<a href="admin.php?page=labelgrid-tools-system-logs&amp;channel=' . esc_url($url_action) . '" class="' . ((isset($_REQUEST['channel']) && $_REQUEST['channel'] == $channel) ? 'current' : '') . '">' . esc_html($row->channel) . ' <span class="count">(' . esc_html($row->count) . ')</span></a>';
        }

        return $views;
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'action':
            case 'message':
                return esc_html($item[$column_name]);
            case 'channel':
                return esc_html(strtoupper($item[$column_name]));
            case 'user':
                return esc_html(get_display_name($item[$column_name]));
            case 'time':
                return esc_html(date('Y-m-d H:i:s', $item['time']));
            case 'level':
                return esc_html($item['level']);
            default:
                return esc_html(print_r($item, true));
        }
    }

    function column_level($item)
    {
        $status_name = esc_html($this->get_name_const($item['level']));
        return "<span class='error_status error_log_" . esc_attr(strtolower($status_name)) . "'>" . $status_name . "</span>";
    }

    function get_name_const($x)
    {
        $fooClass = new ReflectionClass('Monolog\Logger');
        $constants = $fooClass->getConstants();

        $constName = null;
        foreach ($constants as $name => $value) {
            if ($value == $x) {
                $constName = $name;
                break;
            }
        }

        return $constName;
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            esc_attr($this->_args['singular']),
            esc_attr($item['id'])
        );
    }

    function get_columns()
    {
        return array(
            'cb'      => '<input type="checkbox" />',
            'id'      => 'ID',
            'time'    => 'Date',
            'channel' => 'Channel',
            'level'   => 'Severity',
            'message' => 'Message'
        );
    }

    function get_sortable_columns()
    {
        return array(
            'id'      => array('id', false),
            'time'    => array('time', false),
            'channel' => array('channel', false),
            'level'   => array('level', false),
            'message' => array('message', false)
        );
    }

    function get_bulk_actions()
    {
        return array('delete' => 'Delete');
    }

    function process_bulk_action()
    {
        global $wpdb;

        if ('delete' === $this->current_action() && isset($_GET['log'])) {
            foreach ($_GET['log'] as $log) {
                $wpdb->delete(esc_sql($wpdb->prefix . $this->table_name), array('id' => absint($log)));
            }
        }
    }

    function load_data()
    {
        global $wpdb;
        $query = "SELECT * FROM `" . esc_sql($wpdb->prefix . $this->table_name) . "` WHERE 1=1 ";

        if (!empty($_GET['log-filter'])) {
            $log_filter = sanitize_text_field($_GET['log-filter']);
            $query .= " AND `channel`='" . esc_sql($log_filter) . "'";
        }

        if (!empty($_GET['level-filter'])) {
            $level_filter = absint($_GET['level-filter']);
            if ($level_filter >= 0) {
                $query .= " AND `level`='" . esc_sql($level_filter) . "'";
            }
        }

        $query .= ' ORDER BY id DESC';
        $sql_results = $wpdb->get_results($query);

        $data = [];
        foreach ($sql_results as $row) {
            if (isset($_REQUEST['log_action']) && $_REQUEST['log_action'] !== "" && strtolower(str_replace(' ', '-', $row->action)) !== $_REQUEST['log_action']) {
                continue;
            }

            $data[] = array(
                'id'      => esc_html($row->id),
                'time'    => esc_html($row->time),
                'channel' => esc_html($row->channel),
                'level'   => esc_html($row->level),
                'message' => esc_html($row->message)
            );
        }

        return $data;
    }

    function prepare_items()
    {
        $per_page = $this->get_items_per_page('log_entries_per_page', 35);

        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $data = $this->load_data();

        usort($data, array($this, 'usort_reorder'));

        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    function usort_reorder($a, $b)
    {
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'id';
        $order = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'desc';

        if (is_numeric($a[$orderby]) && is_numeric($b[$orderby])) {
            $result = bccomp($a[$orderby], $b[$orderby]);
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }

        return ($order === 'asc') ? $result : -$result;
    }

    function extra_tablenav($which)
    {
        global $wpdb;

        if ($which == "top") {
?>
            <div class="alignleft actions bulkactions">
                <?php
                $log_channels = $wpdb->get_results('SELECT DISTINCT channel FROM `' . esc_sql($wpdb->prefix . $this->table_name) . '` ORDER BY channel ASC', ARRAY_A);
                if ($log_channels) {
                    $log_url = !empty($_GET['level-filter']) ? '&level-filter=' . esc_attr($_GET['level-filter']) . '&log-filter=' : '&log-filter=';

                ?>
                    <select name="log-filter" class="lg-log-filter-cat">
                        <option value=""><?php esc_html_e('All Channels', 'label-grid-tools'); ?></option>
                        <?php
                        foreach ($log_channels as $channel) {
                            $selected = isset($_GET['log-filter']) && $_GET['log-filter'] == $channel['channel'] ? ' selected="selected"' : '';
                        ?>
                            <option value="<?php echo esc_attr($log_url . $channel['channel']); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($channel['channel']); ?></option>
                        <?php
                        }
                        ?>
                    </select>
                <?php
                }

                $log_status = array(
                    100 => 'debug',
                    200 => 'info',
                    250 => 'notice',
                    300 => 'warning',
                    400 => 'error',
                    500 => 'critical',
                    550 => 'alert',
                    600 => 'emergency'
                );

                if ($log_status) {
                    $status_url = isset($_GET['log-filter']) ? '&log-filter=' . esc_attr($_GET['log-filter']) . '&level-filter=' : '&level-filter=';
                ?>
                    <select name="level-filter" class="lg-level-filter-cat">
                        <option value=""><?php esc_html_e('All Levels', 'label-grid-tools'); ?></option>
                        <?php
                        foreach ($log_status as $id => $name) {
                            $selected = !empty($_GET['level-filter']) && $_GET['level-filter'] == $id ? ' selected="selected"' : '';
                        ?>
                            <option value="<?php echo esc_attr($status_url . $id); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($name); ?></option>
                        <?php
                        }
                        ?>
                    </select>
                <?php
                }
                ?>
            </div>
<?php
        }
    }
}
