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
 * Role assign external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\role;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core\user as core_user;
use local_mchelpers\local\service\role_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Role assign external API class.
 *
 * Provides web service endpoint to assign system roles to users.
 */
class assign extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function assign_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, 0),
            'role' => new external_value(PARAM_RAW, 'Role shortname (e.g., admin, manager, coursecreator)', VALUE_DEFAULT, ''),
            'action' => new external_value(PARAM_ALPHA, 'Action: "add" or "remove"', VALUE_DEFAULT, 'add'),
            'courseid' => new external_value(PARAM_INT, 'Course ID (default: 0 = system context)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Assign or unassign a system role to/from a user.
     *
     * @param int $userid User ID
     * @param string $role Role shortname
     * @param string $action Action: 'add' or 'remove'
     * @param int $courseid Course ID (default: 0 = system context only)
     * @return array Assignment result
     */
    public static function assign($userid = 0, $role = '', $action = 'add', $courseid = 0) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::assign_parameters(), [
            'userid' => $userid,
            'role' => $role,
            'action' => $action,
            'courseid' => $courseid,
        ]);

        $result = [
            'status' => false,
            'message' => '',
        ];

        // Validate userid is provided.
        if (empty($params['userid'])) {
            $result['message'] = get_string('useridisrequired', 'local_mchelpers');
            return $result;
        }

        // Validate role is provided.
        if (empty($params['role'])) {
            $result['message'] = get_string('roleisrequired', 'local_mchelpers');
            return $result;
        }

        // Validate courseid is 0 (system level only).
        if (!empty($params['courseid'])) {
            $result['message'] = get_string('onlysystemroleallowed', 'local_mchelpers');
            return $result;
        }

        // Validate user exists.
        if (!$existing_user = core_user::get_user($params['userid'])) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        // Get context from courseid.
        if ($params['courseid']) {
            /** @var \context System context instance */
            $context = \context_course::instance($params['courseid'], IGNORE_MISSING);
        }
        if (empty($context)) {
            /** @var \context System context instance */
            $context = \context_system::instance();
        }

        self::validate_context($context);
        require_capability('moodle/role:assign', $context);

        // Validate action.
        if (!in_array($params['action'], ['add', 'remove'])) {
            $result['message'] = get_string('invalidactionparam', 'local_mchelpers');
            return $result;
        }

        // Handle admin role separately (system-level only).
        if ($params['role'] === 'admin') {
            if ($params['courseid']) {
                $result['message'] = get_string('adminroleonlysystem', 'local_mchelpers');
                return $result;
            }

            $admin_result = role_service::update_system_admin($params['userid'], $params['action']);

            $result['status'] = $admin_result['status'];
            $result['message'] = $admin_result['message'];

            if ($admin_result['status']) {
                $result['data'] = [
                    'userid' => $params['userid'],
                    'role' => 'admin',
                    'action' => $params['action'],
                ];
            }

            return $result;
        }

        // Use role service for non-admin roles.
        $service_result = role_service::update_system_role($params['userid'], $params['role'], $params['action'], $params['courseid']);

        $result['status'] = $service_result['status'];
        $result['message'] = $service_result['message'];

        if ($service_result['status']) {
            // Get role record for additional data.
            $role_record = $DB->get_record('role', ['shortname' => $params['role']]);
            if ($role_record) {
                $result['data'] = [
                    'userid' => $params['userid'],
                    'role' => $params['role'],
                    'roleid' => (int) $role_record->id,
                    'courseid' => (int) $params['courseid'],
                    'action' => $params['action'],
                ];
            }
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function assign_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'data' => new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'role' => new external_value(PARAM_RAW, 'Role shortname'),
                'action' => new external_value(PARAM_ALPHA, 'Action performed: "add" or "remove"', VALUE_OPTIONAL),
                'roleid' => new external_value(PARAM_INT, 'Role ID', VALUE_OPTIONAL),
                'courseid' => new external_value(PARAM_INT, 'Course ID (0 = system context)', VALUE_OPTIONAL),
            ], 'Assignment data', VALUE_OPTIONAL),
        ]);
    }
}
