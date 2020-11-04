<?php

namespace Cgit\Postman;

/**
 * Log processing
 */
class Lumberjack
{
    /**
     * WordPress database object
     *
     * @var \wpdb
     */
    private $database;

    /**
     * WordPress database table name
     *
     * @var string
     */
    private $table;

    /**
     * Array of logs
     *
     * @var array
     */
    private $logs;

    /**
     * Array of columns used to group logs
     *
     * @var array
     */
    private $groups = [
        'post_id',
        'form_id',
    ];

    /**
     * Number of log entries deleted
     *
     * @var integer
     */
    private $deleted = null;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        global $wpdb;

        // Assign the global wpdb object to a variable for convenience
        $this->database = $wpdb;
        $this->table = $this->database->base_prefix . 'cgit_postman_log';

        // Register the menu and update the list of logs
        add_action('admin_menu', [$this, 'update']);
        add_action('admin_menu', [$this, 'register']);

        // Download some logs
        add_action('admin_menu', [$this, 'download']);

        // Clean up some logs
        add_action('init', [$this, 'clean']);
    }

    /**
     * Add menu
     *
     * @return void
     */
    public function register()
    {
        add_submenu_page(
            'tools.php',
            'Log Download',
            'Log Download',
            apply_filters('cgit_postman_log_capability', 'edit_pages'),
            'cgit-postman-logs',
            [$this, 'render']
        );
    }

    /**
     * Render menu
     *
     * @return void
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <h1>Log Download</h1>

            <?php

            if ($this->logs) {
                ?>

                <p>Use the buttons below to download log files in CSV format for each of the forms on the site.</p>

                <table class="form-table">
                    <?php

                    foreach ($this->logs as $log) {
                        ?>
                        <tr>
                            <th>
                                <?= $this->title($log) ?>
                            </th>
                            <td>
                                <form action="" method="get">
                                    <input type="hidden" name="page" value="cgit-postman-logs" />
                                    <input type="hidden" name="download" value="1" />
                                    <?php

                                    // If grouped by post, specify post
                                    if (in_array('post_id', $this->groups)) {
                                        ?>
                                        <input type="hidden" name="post_id" value="<?= $log->post_id ?>" />
                                        <?php
                                    }

                                    // If grouped by form, specify form
                                    if (in_array('form_id', $this->groups)) {
                                        ?>
                                        <input type="hidden" name="form_id" value="<?= $log->form_id ?>" />
                                        <?php
                                    }

                                    ?>
                                    <button class="button button-primary">Download</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }

                    ?>
                </table>

                <h3>Delete logs</h3>

                <form action="" method="post">
                    <input type="hidden" name="postman_delete_logs" value="limit" />
                    <p>Delete all except the latest <input type="number" name="postman_log_limit" placeholder="100" /> log entries <button type="submit" class="button">Delete</button></p>
                </form>

                <form action="" method="post">
                    <input type="hidden" name="postman_delete_logs" value="days" />
                    <p>Delete all except the latest <input type="text" name="postman_log_limit_days" placeholder="30" /> days of log entries <button type="submit" class="button">Delete</button></p>
                </form>

                <form action="" method="post">
                    <input type="hidden" name="postman_delete_logs" value="all" />
                    <p>Delete all logs <button type="submit" class="button">Delete</button></p>
                </form>

                <?php

                if (!is_null($this->deleted)) {
                    $noun = $this->deleted == 1 ? 'entry' : 'entries';

                    ?>
                    <p><i>Deleted <?= $this->deleted ?> log <?= $noun ?>.</i></p>
                    <?php
                }
            } else {
                ?>
                <p><em>There are currently no log files to download. Logs will be created automatically when users send messages using the forms on the site.</em></p>
                <?php
            }

            ?>
        </div>
        <?php
    }

    /**
     * Update list of logs
     *
     * @return void
     */
    public function update()
    {
        $this->updateGroups();

        $groups = implode(', ', $this->groups);

        $this->logs = $this->database->get_results("
            SELECT post_id, form_id
            FROM {$this->table}
            GROUP BY $groups
        ");
    }

    /**
     * Update list of groups
     *
     * The list of columns used to group the logs can be edited using a
     * WordPress filter. The list is restricted to a small range of valid
     * options.
     *
     * @return void
     */
    private function updateGroups()
    {
        // Save the defaults, apply the filter, and remove invalid columns
        $defaults = $this->groups;
        $groups = apply_filters('cgit_postman_log_groups', $this->groups);
        $groups = array_intersect($defaults, $groups);

        // We have to group the logs by something
        if (!$groups) {
            $groups = $defaults;
        }

        $this->groups = $groups;
    }

    /**
     * Log title
     *
     * Return a title for each log, based on the current group-by-column
     * settings.
     *
     * @param \stdClass $log
     * @return string
     */
    private function title($log)
    {
        $title = '';

        if (in_array('post_id', $this->groups)) {
            $title .= get_the_title($log->post_id);

            if (in_array('form_id', $this->groups)) {
                $title .= ' (' . $this->alias($log->form_id) . ')';
            }
        } else {
            $title .= $this->alias($log->form_id);
        }

        return $title;
    }

    /**
     * Form alias
     *
     * Unique form IDs might not be particularly human-readable. This lets you
     * use a filter to assign friendly aliases to form IDs. These will be used
     * in the list of download links on the menu page. In the array of aliases,
     * the key is the real form ID and the value is the alias.
     *
     * @param string $id
     */
    private function alias($id)
    {
        $aliases = apply_filters('cgit_postman_log_aliases', []);

        if (array_key_exists($id, $aliases)) {
            return $aliases[$id];
        }

        return $id;
    }

    /**
     * Log download
     *
     * If you are viewing the right admin page and you use the right GET
     * parameters, this will send the CSV version of the log.
     *
     * @return void
     */
    public function download()
    {
        if (!$this->isDownload()) {
            return;
        }

        // Generate a file name and query based on the group parameters
        $name = $this->downloadName();
        $where = $this->downloadWhere();

        // Add CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $name . '.csv"');
        header('Cache-Control: private');

        // Extract the log data from the database and convert it to CSV format
        $results = $this->database->get_results("
            SELECT *
            FROM {$this->table}
            WHERE $where
        ");

        // Print CSV output
        $csv = fopen('php://output', 'w');

        // Extract headings from a database row and add them to the CSV
        fputcsv($csv, $this->headings(end($results)));

        // Extract values from each result and add them to the CSV
        foreach ($results as $result) {
            fputcsv($csv, $this->values($result));
        }

        // Finish writing CSV
        fclose($csv);

        // Prevent the rest of the page appearing in the CSV file
        exit;
    }

    /**
     * Is this a valid download request?
     *
     * If we are looking at the wrong page or if we have not asked to download
     * anything or if we have not specified the log to download, do
     * nothing.
     *
     * @return boolean
     */
    private function isDownload()
    {
        // Have we adequately specified which log we want?
        $groups_all_present = true;
        foreach ($this->groups as $group) {
            if (!isset($_GET[$group])) {
                $groups_all_present = false;
            }
        }

        return isset($_GET['page']) &&
            isset($_GET['download']) &&
            $groups_all_present &&
            $_GET['page'] == 'cgit-postman-logs';
    }
    /**
     * Generate download file name
     *
     * Use the page or post slug and/or the form ID to produce a file name based
     * on the group settings. Non-alphanumeric characters other than hyphens and
     * underscores will be removed from the name.
     *
     * @return string
     */
    private function downloadName()
    {
        $names = [];

        if (isset($_GET['post_id'])) {
            $names[] = get_post_field('post_name', $_GET['post_id']);
        }

        if (isset($_GET['form_id'])) {
            $names[] = $_GET['form_id'];
        }

        return preg_replace('/[^a-z0-9_\-]/i', '', implode('_', $names));
    }

    /**
     * Generate download query
     *
     * @return string
     */
    private function downloadWhere()
    {
        $wheres = [];

        if (isset($_GET['post_id'])) {
            $wheres[] = 'post_id = ' . $_GET['post_id'];
        }

        if (isset($_GET['form_id'])) {
            $wheres[] = 'form_id = "' . $_GET['form_id'] . '"';
        }

        return implode(' AND ', $wheres);
    }

    /**
     * Extract CSV headings from database row
     *
     * @param array $result
     * @return array
     */
    private function headings($result)
    {
        return $this->extract($result, 'label', ['Date', 'IP']);
    }

    /**
     * Extract CSV values from database row
     *
     * @param array $result
     * @return array
     */
    private function values($result)
    {
        return $this->extract($result, 'value', [$result->date, $result->ip]);
    }

    /**
     * Extract field data as array
     *
     * Given a database row, a key, and an optional array of existing values,
     * return an array of values corresponding to that key in the JSON field
     * data.
     *
     * @param array $result
     * @param string $key
     * @param array $values
     * @return array
     */
    private function extract($result, $key, $values = [])
    {
        foreach (json_decode($result->field_data) as $field) {
            // If the field has been excluded from the message, e.g. because it
            // is a hidden field or because it is a button, do not display it in
            // the log.
            if (property_exists($field, 'exclude') && $field->exclude) {
                continue;
            }

            // If the property does not exist, assume that it should exist and
            // that its value is blank.
            if (!property_exists($field, $key)) {
                $values[] = '';
                continue;
            }

            // Add the value to the array
            $values[] = $field->$key;
        }

        return $values;
    }

    /**
     * Clean up logs
     *
     * If relevant constants have been set or if a clean up has been requested
     * from the admin panel, remove old log files.
     *
     * @return void
     */
    public function clean()
    {
        $wpdb = $this->database;
        $table = $this->table;

        $this->deleteExcept();
        $this->deleteExceptDays();
        $this->deleteAll();
    }

    /**
     * Delete logs except n entries
     *
     * @return void
     */
    private function deleteExcept()
    {
        $database = $this->database;
        $table = $this->table;
        $key = 'postman_delete_logs';
        $limit = null;

        if (defined('CGIT_POSTMAN_LOG_LIMIT')) {
            $limit = CGIT_POSTMAN_LOG_LIMIT;
        }

        if (isset($_POST[$key]) && $_POST[$key] == 'limit') {
            $limit = $_POST['postman_log_limit'];
        }

        if (is_null($limit)) {
            return;
        }

        $this->deleted = $database->query("DELETE FROM $table
            WHERE id NOT IN
                (SELECT id FROM
                    (SELECT id FROM $table
                        ORDER BY date DESC
                        LIMIT $limit) x)");
    }

    /**
     * Delete logs except n most recent days (default 180)
     *
     * @return void
     */
    private function deleteExceptDays()
    {
        $database = $this->database;
        $table = $this->table;
        $key = 'postman_delete_logs';
        $days = 180;

        if (defined('CGIT_POSTMAN_LOG_LIMIT_DAYS')) {
            $days = CGIT_POSTMAN_LOG_LIMIT_DAYS;
        }

        if (isset($_POST[$key]) && $_POST[$key] == 'days') {
            $days = $_POST['postman_log_limit_days'];
        }

        if (is_null($days)) {
            return;
        }

        $date = new \DateTime;
        $date->modify('-' . $days . ' days');
        $sql_date = $date->format('Y-m-d H:i:s');

        $this->deleted = $this->database->query("DELETE FROM $table
            WHERE date < '$sql_date'");
    }

    /**
     * Delete all logs
     *
     * @return void
     */
    private function deleteAll()
    {
        $database = $this->database;
        $table = $this->table;
        $con = 'CGIT_POSTMAN_LOG_DELETE_ALL';
        $key ='postman_delete_logs';

        $has_con = defined($con) && constant($con);
        $has_key = isset($_POST[$key]) && $_POST[$key] == 'all';

        if (!$has_con && !$has_key) {
            return;
        }

        $this->deleted = $database->query("DELETE FROM $table");
    }
}
