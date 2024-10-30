<?php

/**
 * @package    LabelGrid_Tools
 * @subpackage LabelGrid_Tools/admin
 */

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Gate_Entries_Table extends WP_List_Table
{
    private $table_name;

    /**
     * Constructor to set up default configs.
     */
    function __construct()
    {
        parent::__construct(array(
            'singular' => 'gate-entry', // singular name of the listed records
            'plural'   => 'gate-entries', // plural name of the listed records
            'ajax'     => true // does this table support ajax?
        ));

        $this->table_name = LabelGrid_Tools::lgt_prefixTableName('gate_entries', true);

        add_action('init', 'func_export_entries');
    }

    /**
     * Default column rendering method.
     *
     * @param array $item A singular item (one full row's worth of data)
     * @param string $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     */
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'email':
            case 'gate_id':
            case 'session_id':
            case 'connected_services':
                return esc_html($item[$column_name]);
            case 'created_at':
                return esc_html($item['created_at']);
            case 'type':
                $type = ($item['type'] == 0) ? 'Gate' : 'Pre-Save';
                return esc_html($type);
            default:
                return esc_html(print_r($item, true));
        }
    }

    /**
     * Custom column for level with a specific status span.
     *
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     */
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

    /**
     * Checkbox column for bulk actions.
     *
     * @param array $item A singular item (one full row's worth of data)
     * @return string HTML checkbox input
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            esc_attr($this->_args['singular']),
            esc_attr($item['id'])
        );
    }

    /**
     * Define table columns and titles.
     *
     * @return array Column information
     */
    function get_columns()
    {
        return array(
            'cb'                => '<input type="checkbox" />',
            'id'                => 'ID',
            'created_at'        => 'Created At',
            'email'             => 'E-mail',
            'gate_id'           => 'Gate ID',
            'session_id'        => 'Session ID',
            'type'              => 'Type',
            'connected_services' => 'Connected Services',
        );
    }

    /**
     * Specify sortable columns.
     *
     * @return array Columns that should be sortable
     */
    function get_sortable_columns()
    {
        return array(
            'id'                => array('id', false),
            'created_at'        => array('created_at', false),
            'email'             => array('email', false),
            'gate_id'           => array('gate_id', false),
            'session_id'        => array('session_id', false),
            'type'              => array('session_id', false),
            'connected_services' => array('session_id', false),
        );
    }

    /**
     * Define bulk actions.
     *
     * @return array Bulk actions
     */
    function get_bulk_actions()
    {
        return array(
            'delete' => 'Delete'
        );
    }

    /**
     * Handle bulk action processing.
     */
    function process_bulk_action()
    {
        global $wpdb;

        if ('delete' === $this->current_action()) {
            if (isset($_GET['gate-entry'])) {
                foreach ($_GET['gate-entry'] as $gateentry) {
                    $wpdb->delete(
                        esc_sql($wpdb->prefix . $this->table_name),
                        array('id' => esc_sql($gateentry))
                    );
                }
            }
        }
    }

    /**
     * Load data from database with filters.
     *
     * @return array Data array
     */
    function load_data()
    {
        global $wpdb;
        $query = "SELECT * FROM `" . esc_sql($wpdb->prefix . $this->table_name) . "` WHERE 1=1 ";

        if (isset($_GET['gate-filter'])) {
            $gate_filter = absint($_GET['gate-filter']);
            if ($gate_filter > 0) {
                $query .= " AND `gate_id`='" . esc_sql($gate_filter) . "'";
            }
        }

        if (isset($_GET['type-filter'])) {
            $type_filter = absint($_GET['type-filter']);
            if ($type_filter > 0) {
                $query .= " AND `type`='" . esc_sql($type_filter) . "'";
            }
        }

        $query .= ' ORDER BY id DESC';
        $sql_results = $wpdb->get_results($query);

        $data = [];
        foreach ($sql_results as $row) {
            $data[] = array(
                'id'                => esc_html($row->id),
                'created_at'        => esc_html($row->created_at),
                'email'             => esc_html($row->email),
                'gate_id'           => esc_html($row->gate_id),
                'session_id'        => esc_html($row->session_id),
                'type'              => esc_html($row->type),
                'connected_services' => esc_html($row->connected_services),
            );
        }

        return $data;
    }

    /**
     * Prepare table items for display.
     */
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

    /**
     * Sorting function for data.
     */
    function usort_reorder($a, $b)
    {
        $orderby = (!empty($_REQUEST['orderby'])) ? esc_attr($_REQUEST['orderby']) : 'id';
        $order   = (!empty($_REQUEST['order'])) ? esc_attr($_REQUEST['order']) : 'desc';

        if (is_numeric($a[$orderby]) && is_numeric($b[$orderby])) {
            $result = bccomp($a[$orderby], $b[$orderby]);
        } else {
            $result = strcmp($a[$orderby], $b[$orderby]);
        }

        return ($order === 'asc') ? $result : -$result;
    }

    /**
     * Additional filter controls for the table.
     */
    function extra_tablenav($which)
    {
        if ($which == "top") {
?>
            <div class="alignleft actions bulkactions">
                <?php
                $args = array(
                    'post_type'      => 'gate_download',
                    'posts_per_page' => -1
                );
                $loop = new WP_Query($args);

                if ($loop->have_posts()) :
                    $log_url = isset($_GET['type-filter']) ? '&type-filter=' . esc_attr($_GET['type-filter']) . '&gate-filter=' : '&gate-filter=';

                ?>
                    <select name="gate-filter" class="lg-gate-filter">
                        <option value=""><?php esc_html_e('All Gates', 'label-grid-tools'); ?></option>
                        <?php
                        while ($loop->have_posts()) :
                            $loop->the_post();
                            $selected = (isset($_GET['gate-filter']) && $_GET['gate-filter'] == get_the_ID()) ? ' selected="selected"' : '';
                        ?>
                            <option value="<?php echo esc_attr($log_url . get_the_ID()); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html(get_the_title()); ?></option>
                        <?php
                        endwhile;
                        ?>
                    </select>
                <?php
                endif;

                wp_reset_postdata();

                $type = array(
                    0 => 'Gate',
                    1 => 'Pre-Save'
                );

                $status_url = isset($_GET['gate-filter']) ? '&gate-filter=' . esc_attr($_GET['gate-filter']) . '&type-filter=' : '&type-filter=';
                ?>
                <select name="type-filter" class="lg-type-filter">
                    <option value=""><?php esc_html_e('All Types', 'label-grid-tools'); ?></option>
                    <?php
                    foreach ($type as $id => $name) {
                        $selected = (isset($_GET['type-filter']) && (int)$_GET['type-filter'] == $id) ? ' selected="selected"' : '';
                    ?>
                        <option value="<?php echo esc_attr($status_url . $id); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($name); ?></option>
                    <?php
                    }
                    ?>
                </select>

                <input type="submit" name="export_entries" id="export_entries" class="button button-primary" value="<?php esc_attr_e('Export to CSV', 'label-grid-tools'); ?>" />
            </div>
<?php
        }
        if ($which == "bottom") {
            // Additional code for bottom of table, if any
        }
    }
}
