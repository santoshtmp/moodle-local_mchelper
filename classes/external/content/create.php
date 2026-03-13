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
 * Content create external API for local_mchelpers plugin.
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
 * Content create external API class.
 *
 * Provides web service endpoint to create new content.
 */
class create extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function create_parameters() {
        return new external_function_parameters([
            'title' => new external_value(PARAM_TEXT, 'Content title', VALUE_REQUIRED),
            'shortname' => new external_value(PARAM_RAW, 'Content shortname', VALUE_OPTIONAL),
            'content' => new external_value(PARAM_RAW, 'Content body', VALUE_REQUIRED),
            'contentformat' => new external_value(PARAM_INT, 'Content format (0=HTML, 1=MOODLE, 2=PLAIN)', VALUE_DEFAULT, 1),
            'contenttype' => new external_value(PARAM_ALPHANUM, 'Content type', VALUE_DEFAULT, 'page'),
            'status' => new external_value(PARAM_INT, 'Status (0=draft, 1=published)', VALUE_DEFAULT, 0),
            'parentid' => new external_value(PARAM_INT, 'Parent content ID', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Create a new content.
     *
     * @param string $title Content title
     * @param string $shortname Content shortname
     * @param string $content Content body
     * @param int $contentformat Content format
     * @param string $contenttype Content type
     * @param int $status Status
     * @param int $parentid Parent content ID
     * @return array Created content info
     */
    public static function create($title, $content, $shortname = '', $contentformat = 1, $contenttype = 'page', $status = 0, $parentid = 0) {
        global $USER;

        $params = self::validate_parameters(self::create_parameters(), [
            'title' => $title,
            'shortname' => $shortname,
            'content' => $content,
            'contentformat' => $contentformat,
            'contenttype' => $contenttype,
            'status' => $status,
            'parentid' => $parentid,
        ]);

        // Check permissions.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/mchelpers:managecontent', $context);

        // Validate title is not empty.
        if (empty($params['title'])) {
            return [
                'status' => false,
                'message' => get_string('titleisrequired', 'local_mchelpers'),
            ];
        }

        // Validate content is not empty.
        if (empty($params['content'])) {
            return [
                'status' => false,
                'message' => get_string('contentisrequired', 'local_mchelpers'),
            ];
        }

        // Create content.
        $data = new \stdClass();
        $data->title = $params['title'];
        $data->shortname = $params['shortname'];
        $data->content = $params['content'];
        $data->contentformat = $params['contentformat'];
        $data->contenttype = $params['contenttype'];
        $data->status = $params['status'];
        $data->parentid = $params['parentid'];

        $id = content_datarepository::create($data);

        if (!$id) {
            return [
                'status' => false,
                'message' => get_string('errorcreatecontent', 'local_mchelpers'),
            ];
        }

        return [
            'status' => true,
            'message' => get_string('contentcreated', 'local_mchelpers'),
            'data' => [
                'id' => (int) $id,
                'title' => $params['title'],
                'shortname' => $params['shortname'],
                'contenttype' => $params['contenttype'],
                'status' => (int) $params['status'],
            ],
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function create_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'data' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Content ID'),
                'title' => new external_value(PARAM_TEXT, 'Content title'),
                'shortname' => new external_value(PARAM_RAW, 'Content shortname', VALUE_OPTIONAL),
                'contenttype' => new external_value(PARAM_ALPHANUM, 'Content type'),
                'status' => new external_value(PARAM_INT, 'Status (0=draft, 1=published)'),
            ], 'Created content data', VALUE_OPTIONAL),
        ]);
    }
}
