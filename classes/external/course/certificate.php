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
 * Course certificate external API for local_mchelpers plugin.
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
use local_mchelpers\local\service\user_service;
use local_mchelpers\local\service\course_service;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Course certificate external API class.
 *
 * Provides web service endpoint to get or issue course certificates.
 */
class certificate extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function certificate_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'issuedate' => new external_value(PARAM_INT, 'Certificate issue timestamp', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get or issue course certificate for a user.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param int $issuedate Certificate issue timestamp
     * @return array Certificate data
     */
    public static function certificate($courseid, $userid, $issuedate = 0) {
        global $DB;

        $params = self::validate_parameters(self::certificate_parameters(), [
            'courseid' => $courseid,
            'userid' => $userid,
            'issuedate' => $issuedate,
        ]);

        $result = [
            'status' => false,
            'message' => '',
        ];

        // Validate user exists.
        if (!$DB->record_exists('user', ['id' => $params['userid']])) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        // Validate course exists.
        if (!$DB->record_exists('course', ['id' => $params['courseid']])) {
            $result['message'] = get_string('invalidcourseid', 'local_mchelpers');
            return $result;
        }

        // Validate certificate date if provided.
        if ($params['issuedate']) {
            $min_timestamp = 978307200;   // 2001-01-01
            $max_timestamp = 4133894399;  // 2100-12-31
            if ($params['issuedate'] < $min_timestamp || $params['issuedate'] > $max_timestamp) {
                $result['message'] = get_string('invalidcertificatedate', 'local_mchelpers');
                return $result;
            }
        }

        $course = get_course($params['courseid']);
        /** @var \context System context instance */
        $context = \context_course::instance($course->id);
        self::validate_context($context);

        // Check user is enrolled as student in the course.
        $student_role = false;
        $get_user_roles = get_user_roles($context, $params['userid']);
        foreach ($get_user_roles as $role) {
            if ($role->roleid == 5) { // Student role ID
                $student_role = true;
                break;
            }
        }

        if (!$student_role) {
            $result['message'] = get_string('usernotstudent', 'local_mchelpers');
            return $result;
        }

        // Check if the course is completed (100% progress).
        $progress_percentage = course_service::get_course_completion_progress($course, $params['userid']);
        if ($progress_percentage < 100) {
            $result['message'] = get_string('coursenotcompleted', 'local_mchelpers');
            return $result;
        }

        // Get or issue certificate.
        $course_custom_cert = course_service::get_course_mod_customcert($course->id, $params['userid']);

        if (!$course_custom_cert['certificate_issues']) {
            $customcert_id = $course_custom_cert['customcert_id'] ?? '';
            if ($customcert_id) {
                // Issue certificate using service.
                course_service::issue_certificate(
                    $params['userid'],
                    $customcert_id,
                    $params['issuedate']
                );
                // Refresh certificate data.
                $course_custom_cert = course_service::get_course_mod_customcert($course->id, $params['userid']);
            } else {
                $result['message'] = get_string('certificatenotconfigured', 'local_mchelpers');
                return $result;
            }
        }

        if ($course_custom_cert['mod_id'] && $course_custom_cert['customcert_id']) {
            $result['status'] = true;
            $result['message'] = get_string('certificatedata', 'local_mchelpers');
            $result['certificate_data'] = [
                'mod_id' => (int) ($course_custom_cert['mod_id'] ?? 0),
                'customcert_id' => (int) ($course_custom_cert['customcert_id'] ?? 0),
                'certificate_url' => (string) ($course_custom_cert['certificate_url'] ?? ''),
                'certificate_url_download' => (string) ($course_custom_cert['certificate_url_download'] ?? ''),
                'certificate_issues' => (bool) ($course_custom_cert['certificate_issues'] ?? false),
                'certificate_issues_date' => (int) ($course_custom_cert['certificate_issues_date'] ?? 0),
                'certificate_issues_code' => (string) ($course_custom_cert['certificate_issues_code'] ?? ''),
            ];
        } else {
            $result['message'] = get_string('failtoissuecertificate', 'local_mchelpers');
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function certificate_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'certificate_data' => new external_single_structure([
                'mod_id' => new external_value(PARAM_INT, 'Module ID of customcert'),
                'customcert_id' => new external_value(PARAM_INT, 'Customcert ID'),
                'certificate_url' => new external_value(PARAM_RAW, 'URL to view the certificate'),
                'certificate_url_download' => new external_value(PARAM_RAW, 'URL to download the certificate'),
                'certificate_issues' => new external_value(PARAM_BOOL, 'Whether the certificate is issued'),
                'certificate_issues_date' => new external_value(PARAM_INT, 'Issue date timestamp'),
                'certificate_issues_code' => new external_value(PARAM_RAW, 'Certificate issue code'),
            ], 'Detailed certificate data', VALUE_OPTIONAL),
        ]);
    }
}
