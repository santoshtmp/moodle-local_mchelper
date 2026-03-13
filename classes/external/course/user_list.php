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
 * Course user list external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\course;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_mchelpers\local\service\course_service;
use local_mchelpers\local\service\enrol_services;

defined('MOODLE_INTERNAL') || die();

/**
 * Course user list external API class.
 *
 * Provides web service endpoint to get list of users enrolled in a course.
 */
class user_list extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function user_list_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 30),
        ]);
    }

    /**
     * Get list of users enrolled in a course.
     *
     * @param int $courseid Course ID
     * @param int $page Page number
     * @param int $perpage Page size
     * @return array List of enrolled users with pagination
     */
    public static function user_list($courseid, $page = 0, $perpage = 30) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(self::user_list_parameters(), [
            'courseid' => $courseid,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $result = [
            'status' => false,
            'data' => [],
            'meta' => [],
            'message' => '',
        ];

        // Validate course exists.
        if (!$DB->record_exists('course', ['id' => $params['courseid']])) {
            $result['message'] = get_string('invalidcourseid', 'local_mchelpers');
            return $result;
        }

        /** @var \context System context instance */
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/course:viewparticipants', $context);

        // Get course participants enrolled users.
        $enrol_users = enrol_services::get_enrol_users(
            $params['courseid'],
            [
                'spage' => $params['page'],
                'perpage' => $params['perpage'],
                'sortby' => 'firstname',
                'sortdir' => SORT_ASC,
            ]
        );

        // Format result.
        $result['status'] = true;
        $result['data'] = $enrol_users['data'];
        $result['meta'] = $enrol_users['meta'];
        $result['meta']['courseid'] = $params['courseid'];

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function user_list_returns() {
        $userinfofields = [
            'lastcourseaccess' => new external_value(PARAM_INT, 'Course last access timestamp', VALUE_OPTIONAL),
            'completion_progress' => new external_value(PARAM_RAW, 'User course completion progress percentage', VALUE_OPTIONAL),
            'grade' => new external_value(PARAM_INT, 'User grade in course', VALUE_OPTIONAL),
            'groups' => new external_value(PARAM_RAW, 'Comma separated user groups in the course', VALUE_OPTIONAL),
            'enrolments' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'enrol' => new external_value(PARAM_RAW, 'Enrollment plugin name'),
                        'method_name' => new external_value(PARAM_RAW, 'Enrollment method name', VALUE_OPTIONAL),
                        'status' => new external_value(PARAM_RAW, 'Enrollment status', VALUE_OPTIONAL),
                        'timecreated' => new external_value(PARAM_INT, 'Enrollment timestamp', VALUE_OPTIONAL),
                    ]
                ),
                'User enrolment instances in the course',
                VALUE_OPTIONAL
            ),
            'courseroles' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'roleid' => new external_value(PARAM_INT, 'Role ID'),
                        'name' => new external_value(PARAM_RAW, 'Role name'),
                        'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Role short name'),
                    ]
                ),
                'User roles in the course',
                VALUE_OPTIONAL
            ),
            'certificate' => new external_single_structure(
                [
                    'mod_id' => new external_value(PARAM_INT, 'Module ID of the certificate activity'),
                    'customcert_id' => new external_value(PARAM_INT, 'Certificate ID'),
                    'certificate_url' => new external_value(PARAM_RAW, 'Certificate URL', VALUE_OPTIONAL),
                    'certificate_url_download' => new external_value(PARAM_RAW, 'Certificate download URL', VALUE_OPTIONAL),
                    'certificate_issues' => new external_value(PARAM_BOOL, 'Certificate issued or not', VALUE_OPTIONAL),
                    'certificate_issues_date' => new external_value(PARAM_INT, 'Certificate issue timestamp', VALUE_OPTIONAL),
                    'certificate_issues_code' => new external_value(PARAM_RAW, 'Certificate code', VALUE_OPTIONAL),
                ],
                'Course custom certificate information',
                VALUE_OPTIONAL
            )
        ];
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'data' => new external_multiple_structure(
                \local_mchelpers\external\user\get_info::user_data_return_fields($userinfofields),
            ),
            'meta' => new external_single_structure(
                [
                    'totalrecords' => new external_value(PARAM_INT, 'Total number of records', VALUE_OPTIONAL),
                    'page' => new external_value(PARAM_INT, 'Current page number', VALUE_OPTIONAL),
                    'perpage' => new external_value(PARAM_INT, 'Number of data shown per page', VALUE_OPTIONAL),
                    'totalpage' => new external_value(PARAM_INT, 'Total number of pages', VALUE_OPTIONAL),
                    'datadisplaycount' => new external_value(PARAM_INT, 'Current page data count', VALUE_OPTIONAL),
                    'datafrom' => new external_value(PARAM_INT, 'Current page data from record number', VALUE_OPTIONAL),
                    'datato' => new external_value(PARAM_INT, 'Current page data to record number', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_INT, 'Current course id.', VALUE_OPTIONAL),
                ],
                'meta information',
                VALUE_OPTIONAL
            ),
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL, VALUE_DEFAULT, null),
        ]);
    }
}
