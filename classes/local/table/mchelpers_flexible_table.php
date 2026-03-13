<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Base util class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\table;

use core\output\html_writer;
use core\output\paging_bar;
use core_table\flexible_table;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class mchelpers_flexible_table
 *
 * Extended flexible table class for local_mchelpers plugin.
 * Provides enhanced table functionality with custom reset, paging, and download features.
 *
 * @package    local_mchelpers
 * @copyright  2025 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mchelpers_flexible_table extends flexible_table {
    /** @var moodle_url|null The base URL for the table reset button */
    protected $reseturl = null;

    /**
     * Constructor for mchelpers_flexible_table.
     *
     * Initializes the table with basic configuration including URL, pagination, and control variables.
     *
     * @param string $tableid Unique identifier for the table
     * @param moodle_url|null $baseurl Base URL for the table requests (optional)
     * @param int $pagenumber Current page number (optional, defaults to 1)
     * @param int $perpage Number of rows per page (optional, defaults to 30)
     * @param int $totaldatarows Total number of data rows for pagination (optional, defaults to 0)
     */
    public function __construct($tableid, $baseurl = null, $pagenumber = 1, $perpage = 30, $totaldatarows = 0) {
        parent::__construct($tableid);

        // Set the base URL if provided.
        if ($baseurl !== null) {
            $this->define_baseurl($baseurl);
        }

        // Set page number.
        $this->set_page_number($pagenumber);

        // Set the page size if perpage and totaldatarows are provided.
        if ($perpage && $totaldatarows) {
            $this->pagesize($perpage, $totaldatarows);
        }

        // Set control variables for table state management.
        $this->set_control_variables(
            [
                TABLE_VAR_SORT   => 'sortby',
                TABLE_VAR_DIR    => 'sortdir',
                TABLE_VAR_IFIRST => 'sifirst',
                TABLE_VAR_ILAST  => 'silast',
                TABLE_VAR_PAGE   => 'spage',
            ]
        );

        // Handle reset parameter if present in request.
        $parameters = $_GET + $_POST;
        if (isset($parameters['treset']) && $parameters['treset'] == 1) {
            $this->mark_table_to_reset();
        }

        // // Download handling.
        // $this->show_download_buttons_at([TABLE_P_BOTTOM]);
        // $download = optional_param('download', 0, PARAM_ALPHA);
        // if ($this->is_downloading($download, $tableid, $tableid . '-' . time())) {
        //     raise_memory_limit(MEMORY_EXTRA);
        // }
    }

    /**
     * Define table columns and headers from an associative array.
     *
     * Automatically assigns column classes and configures sorting for specified columns.
     *
     * @param array $headerscolumns Associative array of column name => header text
     * @param array $colsorting Array of column names that should be sortable (optional, defaults to [])
     * @return void
     */
    public function define_columnsheaders($headerscolumns, $colsorting = []) {
        $tablecolumns = array_keys($headerscolumns);
        $tableheaders = array_values($headerscolumns);

        // Define table columns.
        $this->define_columns($tablecolumns);

        // Define table headers.
        $this->define_headers($tableheaders);

        // Add CSS class to each column.
        foreach ($tablecolumns as $col) {
            $this->column_class($col, 'col-' . $col);
        }

        // Configure sorting if sortable columns are specified.
        if (array_intersect($colsorting, $tablecolumns)) {
            $this->sortable(true);
            foreach ($tablecolumns as $col) {
                if (!in_array($col, $colsorting)) {
                    $this->no_sorting($col);
                }
            }
        }
    }

    /**
     * Define HTML attributes for the table element.
     *
     * @param array $tableattributes Associative array of attribute name => value
     * @return void
     */
    public function define_tableattributes($tableattributes) {
        foreach ($tableattributes as $key => $value) {
            $this->set_attribute($key, $value);
        }
    }

    /**
     * Define the URL to use for the table preferences reset button.
     *
     * @param string|moodle_url $url The URL to use for resetting table preferences
     * @return void
     */
    public function define_reseturl($url) {
        $this->reseturl = new moodle_url($url);
    }

    /**
     * Prints a message when there is no data to display in the table.
     *
     * This method outputs the table header, a reset button, the initials bar,
     * a message indicating no data is available, and the table footer.
     *
     * @return void
     */
    public function print_nothing_to_display() {
        global $OUTPUT;

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button(true);

        $this->print_initials_bar();

        // echo html_writer::tag('p', get_string('nothingtodisplay'), ['colspan' => 4]);
        echo $OUTPUT->notification(get_string('nothingtodisplay'), 'info', false);

        // Render the dynamic table footer.
        echo $this->get_dynamic_table_html_end();
    }

    /**
     * Generate the HTML for the table preferences reset button.
     *
     * @param bool $showreset
     * @return string HTML fragment, or empty string if no reset is possible or needed.
     */
    protected function render_reset_button($showreset = false) {

        if (!$showreset) {
            if (!$this->can_be_reset()) {
                return '';
            }
        }

        if ($this->reseturl) {
            $url = $this->reseturl->out(false, [$this->request[TABLE_VAR_RESET] => 1]);
        } else {
            $url = $this->baseurl->out(false, [$this->request[TABLE_VAR_RESET] => 1]);
        }

        $html  = html_writer::start_div('resettable mdl-right');
        $html .= html_writer::link($url, get_string('resettable'), ['role' => 'button']);
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Get the html for the download buttons
     *
     * Usually only use internally
     * @return string HTML fragment with download options, or empty string if not applicable.
     */
    public function download_buttons() {
        global $OUTPUT;
        $params = [];
        foreach ($this->baseurl->params() as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    $params[$key . "[" . $k . "]"] = $val;
                }
            } else {
                $params[$key] = $value;
            }
        }
        if ($this->is_downloadable() && !$this->is_downloading()) {
            return $OUTPUT->download_dataformat_selector(
                get_string('downloadas', 'table'),
                $this->baseurl->out_omit_querystring(),
                'download',
                $params,
            );
        } else {
            return '';
        }
    }

    /**
     * Print paging bar.
     *
     * @param string $position 'top' or 'bottom'
     */
    protected function print_paging_bar($position = 'top') {
        global $OUTPUT;

        $pagingbar = new \core\output\paging_bar(
            $this->totalrows,
            $this->currpage * $this->pagesize,
            $this->pagesize,
            $this->baseurl
        );
        $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
        echo $OUTPUT->render($pagingbar);
    }

    /**
     * This function is not part of the public api.
     */
    public function start_html() {
        global $OUTPUT;

        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        // // Paging bar.
        // if ($this->use_pages) {
        //     $pagingbar = new paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
        //     $pagingbar->pagevar = $this->request[TABLE_VAR_PAGE];
        //     echo $OUTPUT->render($pagingbar);
        // }

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table.

        if ($this->responsive) {
            echo html_writer::start_tag('div', ['class' => 'no-overflow']);
        }
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }
}
