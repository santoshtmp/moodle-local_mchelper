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
 * Content get info external API for local_mchelpers plugin.
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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_mchelpers\datarepository\content_datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Content get info external API class.
 *
 * Provides web service endpoint to get content information with pagination.
 */
class get_info extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_info_parameters() {
        return new external_function_parameters([
            'contentids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Content ID'),
                'List of content IDs. If empty return all contents with pagination.',
                VALUE_OPTIONAL
            ),
            'contenttype' => new external_value(PARAM_ALPHANUM, 'Filter by content type', VALUE_DEFAULT, ''),
            'status' => new external_value(PARAM_INT, 'Filter by status (0=draft, 1=published)', VALUE_DEFAULT, -1),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 1),
            'per_page' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 10),
        ]);
    }

    /**
     * Get content information with pagination.
     *
     * @param array $contentids List of content IDs
     * @param string $contenttype Content type filter
     * @param int $status Status filter
     * @param int $page Page number
     * @param int $per_page Page size
     * @return array Content information with pagination
     */
    public static function get_info($contentids = [], $contenttype = '', $status = -1, $page = 1, $per_page = 10) {
        global $DB;

        $params = self::validate_parameters(self::get_info_parameters(), [
            'contentids' => $contentids,
            'contenttype' => $contenttype,
            'status' => $status,
            'page' => $page,
            'per_page' => $per_page,
        ]);

        // Check permissions.
        /** @var \context System context instance */
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/mchelpers:viewcontent', $context);

        $return_content_datas = [];

        // Retrieve contents.
        if (empty($params['contentids'])) {
            // Build query with filters.
            $where_conditions = ['1 = 1'];
            $sql_params = [];

            // Filter by contenttype if provided.
            if (!empty($params['contenttype'])) {
                $where_conditions[] = 'contenttype = :contenttype';
                $sql_params['contenttype'] = $params['contenttype'];
            }

            // Filter by status if provided (not -1).
            if ($params['status'] >= 0) {
                $where_conditions[] = 'status = :status';
                $sql_params['status'] = $params['status'];
            } else {
                // Default: only show published content for users without draft view capability.
                if (!has_capability('local/mchelpers:viewdraft', $context)) {
                    $where_conditions[] = 'status = :status';
                    $sql_params['status'] = 1; // Published only
                }
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query = "SELECT * FROM {local_mchelpers_content} WHERE {$where_clause}";
            $total_count_sql = "SELECT COUNT(id) AS total_count FROM {local_mchelpers_content} WHERE {$where_clause}";

            $limit_from = 0;
            $limit_num = $params['per_page'];
            if ($params['page'] > 1) {
                $limit_from = $limit_num * ($params['page'] - 1);
            }

            $contents = $DB->get_records_sql($query, $sql_params, $limit_from, $limit_num);
            $total_count = $DB->get_record_sql($total_count_sql, $sql_params);

            $meta_info = [
                'total_page' => ceil($total_count->total_count / $params['per_page']),
                'current_page' => $params['page'],
                'per_page' => $params['per_page'],
            ];
        } else {
            // Get specific content IDs.
            $contents = $DB->get_records_list('local_mchelpers_content', 'id', $params['contentids']);
            if (!$contents) {
                return [
                    'status' => false,
                    'message' => get_string('contentnotfound', 'local_mchelpers'),
                ];
            }
            $meta_info = [
                'total_page' => 1,
                'current_page' => 1,
                'per_page' => count($contents),
            ];
        }

        // Build return data.
        $contents_data = [];
        $can_view_draft = has_capability('local/mchelpers:viewdraft', $context);

        foreach ($contents as $content) {
            // Skip draft content if user doesn't have permission.
            if ($content->status == 0 && !$can_view_draft) {
                continue;
            }

            $contents_data[] = [
                'id' => (int) $content->id,
                'title' => $content->title,
                'shortname' => $content->shortname ?? '',
                'content' => $content->content,
                'contentformat' => $content->contentformat ?? 1,
                'contenttype' => $content->contenttype,
                'status' => (int) $content->status,
                'parentid' => (int) ($content->parentid ?? 0),
                'userid' => (int) $content->userid,
                'timecreated' => (int) $content->timecreated,
                'timemodified' => (int) $content->timemodified,
            ];
        }

        return [
            'status' => true,
            'data' => $contents_data,
            'meta' => $meta_info,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function get_info_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Content ID'),
                    'title' => new external_value(PARAM_TEXT, 'Content title'),
                    'shortname' => new external_value(PARAM_RAW, 'Content shortname', VALUE_OPTIONAL),
                    'content' => new external_value(PARAM_RAW, 'Content body'),
                    'contentformat' => new external_value(PARAM_INT, 'Content format', VALUE_OPTIONAL),
                    'contenttype' => new external_value(PARAM_ALPHANUM, 'Content type'),
                    'status' => new external_value(PARAM_INT, 'Status (0=draft, 1=published)'),
                    'parentid' => new external_value(PARAM_INT, 'Parent content ID', VALUE_OPTIONAL),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'timecreated' => new external_value(PARAM_INT, 'Time created'),
                    'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                ])
            ),
            'meta' => new external_single_structure([
                'total_page' => new external_value(PARAM_INT, 'Total page number'),
                'current_page' => new external_value(PARAM_INT, 'Current page number'),
                'per_page' => new external_value(PARAM_INT, 'Number of data shown per page'),
            ], 'Pagination meta information'),
            'message' => new external_value(PARAM_RAW, 'Message', VALUE_OPTIONAL),
        ]);
    }
}
