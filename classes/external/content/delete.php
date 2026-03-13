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
 * Content delete external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\content;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_mchelpers\datarepository\content_datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Content delete external API class.
 *
 * Provides web service endpoint to delete content.
 */
class delete extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Content ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Delete a content.
     *
     * @param int $id Content ID
     * @return array Result info
     */
    public static function delete($id) {
        global $DB;

        $params = self::validate_parameters(self::delete_parameters(), [
            'id' => $id,
        ]);

        // Check permissions.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/mchelpers:managecontent', $context);

        // Validate content exists.
        $content = content_datarepository::get_by_id($params['id']);
        if (!$content) {
            return [
                'status' => false,
                'message' => get_string('contentnotfound', 'local_mchelpers'),
            ];
        }

        // Delete content with metadata.
        $success = content_datarepository::delete_with_meta($params['id']);

        if (!$success) {
            return [
                'status' => false,
                'message' => get_string('errordeletecontent', 'local_mchelpers'),
            ];
        }

        return [
            'status' => true,
            'message' => get_string('contentdeleted', 'local_mchelpers'),
            'data' => [
                'id' => (int) $params['id'],
                'deleted' => true,
            ],
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function delete_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'data' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Content ID'),
                'deleted' => new external_value(PARAM_BOOL, 'Whether deletion was successful'),
            ], 'Deleted content data', VALUE_OPTIONAL),
        ]);
    }
}
