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
 * Course enroll external API for local_mchelpers plugin.
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
use core_external\external_single_structure;
use core_external\external_value;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Course enroll external API class.
 *
 * Provides web service endpoint to enroll users in courses.
 */
class enroll extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function enroll_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'roleid' => new external_value(PARAM_INT, 'Role ID to assign (e.g., 5 for student)', VALUE_REQUIRED),
            'timestart' => new external_value(PARAM_INT, 'Enrolment start timestamp', VALUE_DEFAULT, 0),
            'timeend' => new external_value(PARAM_INT, 'Enrolment end timestamp', VALUE_DEFAULT, 0),
            'suspend' => new external_value(PARAM_BOOL, 'Suspend enrolment', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Enroll a user in a course.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param int $roleid Role ID to assign
     * @param int $timestart Enrolment start timestamp
     * @param int $timeend Enrolment end timestamp
     * @param bool $suspend Suspend enrolment
     * @return array Enrolment result
     */
    public static function enroll($courseid, $userid, $roleid, $timestart = 0, $timeend = 0, $suspend = false) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::enroll_parameters(), [
            'courseid' => $courseid,
            'userid' => $userid,
            'roleid' => $roleid,
            'timestart' => $timestart,
            'timeend' => $timeend,
            'suspend' => $suspend,
        ]);

        $result = [
            'status' => false,
            'message' => '',
        ];

        // Validate course exists.
        if (!$DB->record_exists('course', ['id' => $params['courseid']])) {
            $result['message'] = get_string('invalidcourseid', 'local_mchelpers');
            return $result;
        }

        // Validate user exists.
        if (!$DB->record_exists('user', ['id' => $params['userid']])) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        // Validate role exists and is assignable.
        /** @var \context System context instance */
        $context = \context_course::instance($params['courseid'], IGNORE_MISSING);
        self::validate_context($context);
        require_capability('enrol/manual:enrol', $context);

        $roles = get_assignable_roles($context);
        if (!array_key_exists($params['roleid'], $roles)) {
            $result['message'] = get_string('invalidroleid', 'local_mchelpers');
            return $result;
        }

        // Get manual enrolment plugin.
        $enrol = enrol_get_plugin('manual');
        if (empty($enrol)) {
            $result['message'] = get_string('manualenrolmentnotfound', 'enrol_manual');
            return $result;
        }

        // Get manual enrolment instance.
        $instance = null;
        $enrol_instances = enrol_get_instances($params['courseid'], true);
        foreach ($enrol_instances as $course_enrol_instance) {
            if ($course_enrol_instance->enrol == 'manual') {
                $instance = $course_enrol_instance;
                break;
            }
        }

        if (empty($instance)) {
            $result['message'] = get_string('manualenrolmentnotfound', 'enrol_manual');
            return $result;
        }

        // Check if user is already enrolled.
        $existing_enrolments = enrol_get_user_enrolments($instance, $params['userid']);
        if (!empty($existing_enrolments)) {
            $result['message'] = get_string('useralreadyenrolled', 'local_mchelpers');
            return $result;
        }

        // Prepare enrolment parameters.
        $status = $params['suspend'] ? ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;

        // Enrol user.
        $enrol->enrol_user(
            $instance,
            $params['userid'],
            $params['roleid'],
            $params['timestart'],
            $params['timeend'],
            $status
        );

        $result['status'] = true;
        $result['message'] = get_string('userenrolledsuccessfully', 'local_mchelpers');
        $result['data'] = [
            'courseid' => $params['courseid'],
            'userid' => $params['userid'],
            'roleid' => $params['roleid'],
            'status' => $status,
        ];

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function enroll_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Enrolment status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'data' => new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'roleid' => new external_value(PARAM_INT, 'Role ID'),
                'status' => new external_value(PARAM_INT, 'Enrolment status (0=active, 1=suspended)'),
            ], 'Enrolment data', VALUE_OPTIONAL),
        ]);
    }
}
