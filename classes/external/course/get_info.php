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
 * Course get info external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * core_course_external class, get_courses() function for reference:
 * 
 */

namespace local_mchelpers\external\course;

use core\exception\moodle_exception;
use core_analytics\course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_mchelpers\local\service\course_service;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Course get info external API class.
 *
 * Provides web service endpoint to get course information.
 */
class get_info extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_info_parameters() {
        return new external_function_parameters([
            'courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID'),
                'List of course IDs. If empty return all courses except front page course.',
                VALUE_DEFAULT,
                []
            ),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 1),
            'perpage' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 10),
        ]);
    }

    /**
     * Get course information.
     *
     * @param array $courseids List of course IDs
     * @param int $page Page number
     * @param int $perpage Page size
     * @return array Course information with pagination
     * 
     */
    public static function get_info($courseids = [], $page = 1, $perpage = 10) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $return_course_datas = [];
        $return_course_datas['status'] = true;
        $return_course_datas['message'] = 'message';

        $params = self::validate_parameters(self::get_info_parameters(), [
            'courseids' => $courseids,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        // Retrieve courses.
        $filterparam = [
            'courseids' => $params['courseids'],
            'page' => $params['page'],
            'perpage' => $params['perpage'],
            'timestamp' => true,
            'defaultvalues' => false,
            'addparticipantcount' => true,
            'addactivitiescount' => true,
            'addcourseaccessusercount' => true,
            'addenrolinstances' => true,
            'addcoursegroups' => true,
            'addcustomnamevalueformat' => true,

        ];
        $allcourseinfo = course_service::get_all_course_info($filterparam);

        // var_dump($allcourseinfo); // Debugging line, can be removed later.
        // die;

        $return_course_datas['data'] = $allcourseinfo['data'];
        $return_course_datas['meta'] = $allcourseinfo['meta'];
        $return_course_datas['meta']['courseids'] = $params['courseids'];
        $return_course_datas['status'] = true;

        return $return_course_datas;


        // Retrieve courses.
        if (empty($params['courseids'])) {
            $query = 'SELECT * FROM {course} course WHERE course.id <> :frontpage_course_id';
            $total_count_sql = 'SELECT COUNT(course.id) AS total_count FROM {course} course WHERE course.id <> :frontpage_course_id';
            $sql_params = [
                'frontpage_course_id' => 1,
            ];
            $page_number = $params['page'];

            $limit_from = 0;
            $limit_num = $params['perpage'];
            if ($page_number > 1) {
                $limit_from = $limit_num * ($page_number - 1);
            }
            $courses = $DB->get_records_sql($query, $sql_params, $limit_from, $limit_num);
            $total_count = $DB->get_record_sql($total_count_sql, $sql_params);

            $meta_info = [
                'total_page' => ceil($total_count->total_count / $params['perpage']),
                'current_page' => $page_number,
                'perpage' => $params['perpage'],
            ];
        } else {
            $courses = $DB->get_records_list('course', 'id', $params['courseids']);
            if (!$courses) {
                $return_course_datas['message'] = get_string('invalidcourseids', 'local_mchelpers');
            }
            $meta_info = [
                'total_page' => 1,
                'current_page' => 1,
                'perpage' => count($courses),
            ];
        }

        // Create return value.
        $courses_info = [];
        $page_data_count = 0;

        foreach ($courses as $course) {
            // Security checks.
            /** @var \context System context instance */
            $context = \context_course::instance($course->id, IGNORE_MISSING);

            try {
                self::validate_context($context);
            } catch (\Exception $e) {
                $exception_param = new stdClass();
                $exception_param->message = $e->getMessage();
                $exception_param->courseid = $course->id;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exception_param);
            }

            if ($course->id != SITEID) {
                require_capability('moodle/course:view', $context);
            }

            $filterparam = [
                'timestamp' => true,
                'defaultvalues' => false,
            ];
            $course_info = course_service::get_course_info($course->id, $filterparam);
            $courses_info[] = $course_info;
            $page_data_count++;
        }

        $meta_info['page_data_count'] = $page_data_count;
        $return_course_datas['data'] = $courses_info;
        $return_course_datas['meta'] = $meta_info;

        return $return_course_datas;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function get_info_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status', VALUE_OPTIONAL),
            'data' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'id' => new external_value(PARAM_INT, 'Course ID'),
                        'shortname' => new external_value(PARAM_RAW, 'Course short name'),
                        'fullname' => new external_value(PARAM_RAW, 'Full name'),
                        'category' => new external_value(PARAM_INT, 'Category ID'),
                        'categoryname' => new external_value(PARAM_RAW, 'Course category name', VALUE_OPTIONAL),
                        'course_url' => new external_value(PARAM_RAW, 'Course URL', VALUE_OPTIONAL),
                        'course_image_url' => new external_value(PARAM_RAW, 'Course image URL', VALUE_OPTIONAL),
                        'category_url' => new external_value(PARAM_RAW, 'Course Category URL', VALUE_OPTIONAL),
                        'enrollment_url' => new external_value(PARAM_RAW, 'Course enrollment URL', VALUE_OPTIONAL),
                        'participant_url' => new external_value(PARAM_RAW, 'Course participant URL', VALUE_OPTIONAL),
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
                        'enablecompletion' => new external_value(PARAM_INT, '1: enable activity completion, 0: disable', VALUE_OPTIONAL),
                        'newsitems' => new external_value(PARAM_INT, 'Number of news items in the course', VALUE_OPTIONAL),
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
                        'groups' => new external_value(PARAM_RAW, 'Comma separated group names', VALUE_OPTIONAL),
                        'groupmode' => new external_value(PARAM_RAW, 'Group mode: no groups, separate groups, visible groups', VALUE_OPTIONAL),
                        'participantcount' => new external_value(PARAM_INT, 'Count enrolled users', VALUE_OPTIONAL),
                        'courseaccessusercount' => new external_value(PARAM_INT, 'Count of users who have accessed the course', VALUE_OPTIONAL),
                        'activitiescount' => new external_value(PARAM_INT, 'Count of activities in the course', VALUE_OPTIONAL),
                        'sortorder' => new external_value(PARAM_INT, 'Sort order into the category', VALUE_OPTIONAL),
                        'section' => new external_value(PARAM_INT, 'Section number inside the course', VALUE_OPTIONAL),
                        'numsections' => new external_value(PARAM_INT, '(deprecated, use courseformatoptions) number of weeks/topics', VALUE_OPTIONAL),
                        'course_created_by' => new external_value(PARAM_INT, 'User ID who created the course', VALUE_OPTIONAL),
                        'course_updated_by' => new external_value(PARAM_INT, 'User ID who last updated the course', VALUE_OPTIONAL),
                    ],
                    'Course information',
                    VALUE_OPTIONAL
                ),
                'List of courses',
                VALUE_OPTIONAL
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
                    'courseids' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'Course ID'),
                        'List of course IDs included in the current page data',
                        VALUE_OPTIONAL
                    ),
                ],
                'meta information',
                VALUE_OPTIONAL
            ),
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL),
        ]);
    }
}
