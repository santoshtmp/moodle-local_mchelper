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
 * Page content manager class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\content;

use core\exception\moodle_exception;
use core\output\action_menu;
use core\output\pix_icon;
use local_mchelpers\local\table\mchelpers_flexible_table;
use local_mchelpers\datarepository\content_datarepository;
use local_mchelpers\datarepository\usermeta_datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class for handling page content operations.
 *
 * Handles business logic for page content including CRUD operations,
 * validation, and coordination between repository and view layers.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_manager {

    /** @var \context System context instance */
    protected $context;

    /**
     * Constructor.
     *
     * Initializes the system context for capability checks.
     */
    public function __construct() {
        $this->context = \context_system::instance();
    }

    /**
     * Get the action page path for content editing.
     *
     * @return string Path to the content edit page
     */
    public static function get_action_page_path() {
        return '/local/mchelpers/content/edit.php';
    }

    /**
     * Get the action page URL for content editing.
     *
     * @param int|null $id Content ID for edit operations (null for current id)
     * @param string $action
     *
     * @return \moodle_url URL to the content index page
     */
    public static function get_action_page_url($id = null, $action = '') {
        $id = $id ?: optional_param('id', 0, PARAM_INT);
        $param = [
            'type' => 'page',
            'id' => $id
        ];
        if ($action) {
            $param['action'] = $action;
            $param['sesskey'] = sesskey();
        }
        return new \moodle_url(self::get_action_page_path(), $param);
    }

    /**
     * Get the view page path for content viewing.
     *
     * @return string Path to the content view page
     */
    public static function get_view_page_path() {
        return '/local/mchelpers/content/view.php';
    }

    /**
     * Get the view page URL for content viewing.
     *
     * @param int|null $id Content ID for edit operations (null for current id)
     *
     * @return \moodle_url URL to the content index page
     */
    public static function get_view_page_url($id = null) {
        $id = $id ?: required_param('id', PARAM_INT);
        $param = [
            'id' => $id
        ];
        return new \moodle_url(self::get_view_page_path(), $param);
    }


    /**
     * Get the listing page path for content management.
     *
     * @return string Path to the content listing page
     */
    public static function get_listing_page_path() {
        return '/local/mchelpers/content/index.php';
    }

    /**
     * Get the listing page URL for content management.
     *
     * @return \moodle_url URL to the content index page
     */
    public static function get_listing_page_url() {
        return new \moodle_url(self::get_listing_page_path());
    }

    /**
     * Process content form submission (add/edit).
     *
     * Handles creating new content or updating existing content based on form data.
     * Uses database transactions to ensure data integrity.
     *
     * @param object $formdata Submitted form data
     * @param int|null $id Content ID for edit operations (null for create)
     * @return int|false New/updated content ID on success, false on failure
     *
     * @throws moodle_exception On database errors
     */
    public static function process_form($formdata, $id = null) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            // Prepare content data.
            $contentrecord = new \stdClass();
            $contentrecord->title = $formdata->title;
            $contentrecord->shortname = $formdata->shortname ?? null;
            $contentrecord->contenttype = $formdata->type ?? content_datarepository::DEFAULT_CONTENT_TYPE;
            $contentrecord->status = $formdata->status ?? 0;
            $contentrecord->parentid = $formdata->parentid ?? 0;

            // Handle content editor.
            if (isset($formdata->content)) {
                $contentrecord->content = $formdata->content['text'];
                $contentrecord->contentformat = $formdata->content['format'];
                $contentrecord->contentitemid = $formdata->content['itemid'];
            }

            if ($id) {
                // Validate content ID.
                if ($formdata->id != $id) {
                    \core\notification::error(get_string('contentnotfound', 'local_mchelpers'));
                    return false;
                }
                // Update existing content.
                $contentrecord->id = $id;
                $result = content_datarepository::update($contentrecord);
            } else {
                // Create new content.
                $result = content_datarepository::create($contentrecord);
            }

            if (!$result) {
                throw new moodle_exception('dberror', 'error');
            }

            $transaction->allow_commit();

            // Display success notification.
            if ($id) {
                \core\notification::success(get_string('changessaved', 'local_mchelpers'));
            } else {
                \core\notification::success(get_string('contentcreated', 'local_mchelpers'));
            }

            return $result;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            \core\notification::error($e->getMessage());
            return false;
        }
    }

    /**
     * Handle view action (single content).
     *
     * @param int $id Content ID
     * @return void
     */
    public function view(int $id): void {
        global $PAGE, $OUTPUT;

        require_capability('local/mchelpers:viewcontent', $this->context);

        // Fetch content.
        $content = content_datarepository::get_by_id($id);

        if (!$content) {
            throw new \moodle_exception('contentnotfound', 'local_mchelpers');
        }

        // Check status permission.
        if ($content->status == 0) {
            require_capability('local/mchelpers:viewdraft', $this->context);
        }

        // Set up page.
        $PAGE->set_context($this->context);
        $PAGE->set_title(format_string($content->title));
        $PAGE->set_heading(format_string($content->title));

        // Render output.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($content->title));

        // Render content item.
        $renderer = $PAGE->get_renderer('local_mchelpers');
        $contentitem = new \local_mchelpers\output\content_item($content);
        echo $renderer->render($contentitem);

        echo $OUTPUT->footer();
    }

    /**
     * Handle delete action for content.
     *
     * Validates sesskey and deletes content along with its metadata.
     * Redirects to the content listing page after successful deletion.
     *
     * @param int $id Content ID to delete
     * @return void
     *
     * @throws moodle_exception If content not found or sesskey validation fails
     */
    public static function delete(int $id): void {
        // require_capability('local/mchelpers:managecontent', $context);

        // Validate content exists.
        $content = content_datarepository::get_by_id($id);
        if (!$content) {
            throw new moodle_exception('contentnotfound', 'local_mchelpers');
        }

        // Validate sesskey and delete content.
        if (confirm_sesskey()) {
            content_datarepository::delete_with_meta($id);
            \core\notification::success(get_string('contentdeleted', 'local_mchelpers'));
            redirect(new \moodle_url('/local/mchelpers/content/index.php'));
        }

        throw new moodle_exception('failtoconfirmsesskey', 'local_mchelpers');
    }

    /**
     * Generate and render content table for listing page.
     *
     * Displays a paginated table of content items with filtering, sorting,
     * and action menu (edit, delete, view) support.
     *
     * @param moodle_url $currentpageurl Current page URL for pagination and sorting
     * @return string Rendered HTML table output
     *
     * @global \moodle_page $PAGE
     * @global \moodle_database $DB
     */
    public static function get_content_table($currentpageurl): string {
        global $PAGE;

        $corerenderer = $PAGE->get_renderer('core');
        $PAGE->requires->js_call_amd('local_mchelpers/conformdelete', 'init');

        // Get URL parameters.
        $pagenumber = optional_param('spage', 0, PARAM_INT);
        $status = optional_param('status', 'all', PARAM_ALPHA);
        $search = optional_param('search', '', PARAM_TEXT);
        $sortby = optional_param('sortby', 'timemodified', PARAM_TEXT);
        $sortdir = optional_param('sortdir', SORT_DESC, PARAM_TEXT);
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';

        // Build filter conditions.
        $filterconditions = [
            'contenttype' => 'page',
            'status' => $status,
            'search' => $search,
        ];
        $sort = $sortby . ' ' . $sortdir;
        $perpage = 5;

        // Get contents with pagination.
        $result = content_datarepository::get_all($filterconditions, $sort, $pagenumber, $perpage);
        $contents = $result['data'];
        $total = $result['meta']['totalrecords'];
        $datafrom = $result['meta']['datafrom'];

        // Table information setup.
        $tableid = 'mchelpers-content-table';
        $tableattributes = [
            'id' => $tableid,
            'class' => 'generaltable generalbox',
        ];
        $columnsheaders = [
            'sn' => get_string('sn', 'local_mchelpers'),
            'title' => get_string('title', 'local_mchelpers'),
            'status' => get_string('status'),
            'userid' => get_string('author', 'local_mchelpers'),
            'timemodified' => get_string('modified'),
            'action' => get_string('action'),
        ];
        $colsorting = ['title', 'timemodified'];

        // Initialize table.
        $table = new mchelpers_flexible_table(
            $tableid,
            $currentpageurl,
            $pagenumber,
            $perpage,
            $total
        );
        $table->define_tableattributes($tableattributes);
        $table->define_columnsheaders($columnsheaders, $colsorting);
        $table->define_reseturl($currentpageurl);

        // Setup table and start output buffering.
        $table->setup();
        ob_start();

        foreach ($contents as $content) {
            // Get author name.
            $authorname = fullname(usermeta_datarepository::get_user_by_id($content->userid));

            // Action menu setup.
            $actionmenu = new action_menu();
            $actionmenu->set_kebab_trigger('Action', $corerenderer);
            $actionmenu->set_additional_classes('fields-actions');

            // Edit action.
            $actionmenu->add(new \action_menu_link(
                self::get_action_page_url($content->id, 'edit'),
                new pix_icon('t/edit', get_string('edit')),
                get_string('edit'),
                false,
                ['data-id' => $content->id]
            ));

            // Delete action.
            $actionmenu->add(new \action_menu_link(
                self::get_action_page_url($content->id, 'delete'),
                new pix_icon('t/delete', get_string('delete')),
                get_string('delete'),
                false,
                [
                    'class' => 'text-danger mchelpers-delete-action',
                    'data-id' => $content->id,
                    'data-title' => format_string($content->title),
                    'data-heading' => get_string('confirm'),
                    'apply-confirm' => true,
                ]
            ));

            // View action.
            $actionmenu->add(new \action_menu_link(
                self::get_view_page_url($content->id),
                new pix_icon('t/hide', get_string('view')),
                get_string('view'),
                false,
                ['data-id' => $content->id]
            ));

            // Render action menu.
            $actions = $corerenderer->render($actionmenu);

            // Build table row.
            $row = [
                $datafrom++,
                format_string($content->title),
                ($content->status == 1) ? get_string('published', 'local_mchelpers') : get_string('draft', 'local_mchelpers'),
                $authorname,
                userdate($content->timemodified),
                $actions,
            ];

            $table->add_data($row);
        }

        $table->finish_output();
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
