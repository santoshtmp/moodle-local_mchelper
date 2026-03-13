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
 * User course list external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\user;

use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_function_parameters;
use core_external\external_api;
use local_mchelpers\local\service\enrol_services;

defined('MOODLE_INTERNAL') || die();

/**
 * User course list external API class.
 *
 * Provides web service endpoint to get user's enrolled courses.
 * 
 * user enrolled courses.
 */
class course_list extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function course_list_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * Get list of courses where user is enrolled.
     *
     * @param int $userid User ID
     * @param int $page Page number
     * @param int $perpage Page size
     * @return array List of courses with enrollment information and pagination
     */
    public static function course_list($userid, $page = 0, $perpage = 5) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::course_list_parameters(), [
            'userid' => $userid,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $return_data = [
            'status' => false,
            'message' => '',
            'data' => [],
            'meta' => [
                'totalrecords' => 0,
                'page' => $params['page'],
                'perpage' => $params['perpage'],
                'totalpage' => 0,
                'datadisplaycount' => 0,
                'datafrom' => 0,
                'datato' => 0,
                'userid' => $params['userid']
            ],
        ];

        if (!$DB->record_exists('user', ['id' => $params['userid']])) {
            $return_data['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $return_data;
        }

        // Get all enrolled courses for the user.
        $enrolledcourses = enrol_services::get_enrol_courses(
            $params['userid'],
            [
                'page' => $params['page'],
                'perpage' => $params['perpage']
            ]
        );

        // Format course data for output.
        $meta_info = $enrolledcourses['meta'];
        $meta_info['userid'] = $params['userid'];
        // Set response data.
        $return_data['status'] = true;
        $return_data['data'] = $enrolledcourses['data'];
        $return_data['meta'] = $meta_info;
        return $return_data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function course_list_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Response message', VALUE_OPTIONAL),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'category' => new external_value(PARAM_INT, 'Category ID'),
                    'shortname' => new external_value(PARAM_RAW, 'Course short name'),
                    'fullname' => new external_value(PARAM_RAW, 'Full name'),
                    'categoryname' => new external_value(PARAM_RAW, 'Course category name'),
                    'course_url' => new external_value(PARAM_RAW, 'Course URL', VALUE_OPTIONAL),
                    'course_image_url' => new external_value(PARAM_RAW, 'Course image URL', VALUE_OPTIONAL),
                    'category_url' => new external_value(PARAM_RAW, 'Course Category URL', VALUE_OPTIONAL),
                    'summary' => new external_value(PARAM_RAW, 'Summary', VALUE_OPTIONAL),
                    'format' => new external_single_structure(
                        [
                            'shortname' => new external_value(PARAM_PLUGIN, 'course format: weeks, topics, social, site,..'),
                            'name' => new external_value(PARAM_RAW, 'Course format plugin name',  VALUE_OPTIONAL),
                        ],
                        "course ormat detail",
                        VALUE_OPTIONAL
                    ),
                    'visible' => new external_value(PARAM_INT, '1: available to students, 0: not available', VALUE_OPTIONAL),
                    'startdate' => new external_value(PARAM_INT, 'Course start timestamp', VALUE_OPTIONAL),
                    'enddate' => new external_value(PARAM_INT, 'Course end timestamp', VALUE_OPTIONAL),
                    'timecreated' => new external_value(PARAM_INT, 'Course created timestamp', VALUE_OPTIONAL),
                    'timemodified' => new external_value(PARAM_INT, 'Course modified timestamp', VALUE_OPTIONAL),
                    'customfields' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'The shortname of the custom field'),
                                'type'  => new external_value(
                                    PARAM_COMPONENT,
                                    'The type of the custom field - text, checkbox...'
                                ),
                                'valueraw' => new external_value(PARAM_RAW, 'The raw value of the custom field'),
                                'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                            ]
                        ),
                        'Custom fields and associated values',
                        VALUE_OPTIONAL
                    ),
                    'enrolinstances' => new external_multiple_structure(
                        new external_single_structure(
                            [
                                'enrol' => new external_value(PARAM_RAW, 'Enrollment plugin name'),
                                'method_name' => new external_value(PARAM_RAW, 'Enrollment method name', VALUE_OPTIONAL),
                                'status' => new external_value(PARAM_RAW, 'Enrollment status', VALUE_OPTIONAL),
                                'cost' => new external_value(PARAM_RAW, 'Enrollment cost for fee enrolment', VALUE_OPTIONAL),
                                'currency' => new external_value(PARAM_RAW, 'Enrollment currency for fee enrolment', VALUE_OPTIONAL),
                                'role' => new external_single_structure(
                                    [
                                        'id' => new external_value(PARAM_INT, 'Role ID'),
                                        'name' => new external_value(PARAM_RAW, 'Role name'),
                                        'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Role short name'),
                                    ],
                                    'Enrollment role details',
                                    VALUE_OPTIONAL
                                ),
                            ]
                        ),
                        'Course enrolment instances',
                        VALUE_OPTIONAL
                    ),
                    'lastaccess' => new external_value(PARAM_INT, 'Course last access timestamp', VALUE_OPTIONAL),
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
                ]),
                'User enrolled courses data',
                VALUE_OPTIONAL
            ),
            'meta' => new external_single_structure(
                [
                    'totalrecords' => new external_value(PARAM_INT, 'Total number of records',VALUE_OPTIONAL),
                    'page' => new external_value(PARAM_INT, 'Current page number',VALUE_OPTIONAL),
                    'perpage' => new external_value(PARAM_INT, 'Number of data shown per page',VALUE_OPTIONAL),
                    'totalpage' => new external_value(PARAM_INT, 'Total number of pages',VALUE_OPTIONAL),
                    'datadisplaycount' => new external_value(PARAM_INT, 'Current page data count',VALUE_OPTIONAL),
                    'datafrom' => new external_value(PARAM_INT, 'Current page data from record number',VALUE_OPTIONAL),
                    'datato' => new external_value(PARAM_INT, 'Current page data to record number'),
                    'userid' => new external_value(PARAM_INT, 'User ID for the course list', VALUE_OPTIONAL)
                ],
                'meta information',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
