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
 * Testimonial manager class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\content;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/mchelpers/classes/form/testimonialcontent_edit_form.php');

/**
 * Manager class for handling testimonial content operations.
 *
 * Handles business logic for testimonial content including CRUD operations,
 * form processing, and validation.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testimonial_manager {

    /**
     * Process testimonial form submission.
     *
     * Handles creating new testimonial or updating existing testimonial based on form data.
     * Uses database transactions to ensure data integrity.
     *
     * @param object $formdata Submitted form data
     * @param int|null $id Testimonial ID for edit operations (null for create)
     * @return int|false New/updated testimonial ID on success, false on failure
     */
    public static function process_form($formdata, $id = null) {
        global $DB, $USER;

        $now = time();
        $transaction = $DB->start_delegated_transaction();

        try {
            if ($id) {
                // Update existing testimonial.
                $formdata->id = $id;
                $formdata->timemodified = $now;
                $formdata->content = $formdata->content['text'];
                $formdata->contentformat = $formdata->content['format'];
                $formdata->contentitemid = $formdata->content['itemid'];

                $DB->update_record('local_mchelpers_content', $formdata);
            } else {
                // Insert new testimonial.
                $formdata->timecreated = $now;
                $formdata->timemodified = $now;
                $formdata->userid = $USER->id;
                $formdata->contenttype = 'testimonial';
                $formdata->content = $formdata->content['text'];
                $formdata->contentformat = $formdata->content['format'];
                $formdata->contentitemid = $formdata->content['itemid'];

                $id = $DB->insert_record('local_mchelpers_content', $formdata);
            }

            $transaction->allow_commit();

            // Display success notification.
            if ($id) {
                \core\notification::success(get_string('changessaved', 'local_mchelpers'));
            } else {
                \core\notification::success(get_string('contentcreated', 'local_mchelpers'));
            }

            return $id;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            \core\notification::error($e->getMessage());
            return false;
        }
    }

    /**
     * Handle delete action for testimonial.
     *
     * Validates sesskey and deletes testimonial.
     * Redirects to the testimonial listing page after successful deletion.
     *
     * @param int $id Testimonial ID to delete
     * @return void
     *
     * @throws moodle_exception If testimonial not found or sesskey validation fails
     */
    public static function delete(int $id): void {
        global $DB;

        // Validate testimonial exists.
        $testimonial = $DB->get_record('local_mchelpers_content', ['id' => $id, 'contenttype' => 'testimonial']);
        if (!$testimonial) {
            throw new \moodle_exception('contentnotfound', 'local_mchelpers');
        }

        // Validate sesskey and delete testimonial.
        if (confirm_sesskey()) {
            $DB->delete_records('local_mchelpers_content', ['id' => $id]);
            \core\notification::success(get_string('contentdeleted', 'local_mchelpers'));
            redirect(new \moodle_url('/local/mchelpers/content/testimonial/index.php'));
        }

        throw new \moodle_exception('failtoconfirmsesskey', 'local_mchelpers');
    }
}
