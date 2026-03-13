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
 * Role get external API for local_mchelpers plugin.
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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_mchelpers\local\service\role_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Role get external API class.
 *
 * Provides web service endpoint to get available roles or user's assigned roles.
 */
class get extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID to get assigned roles (0 = no user filter)', VALUE_DEFAULT, 0),
            'courseid' => new external_value(PARAM_INT, 'Course ID for context (ID= Course ID, 0 = system  context filter, -1 = no context filter)', VALUE_DEFAULT, -1),
        ]);
    }

    /**
     * Get available roles or user's assigned roles.
     *
     * Supports 4 cases:
     * 1. Both userid and courseid: Get user's roles in that course context
     * 2. userid only: Get all roles for user across all contexts
     * 3. courseid only: Get all roles available for that course context
     * 4. Neither: Get all roles defined in the system
     *
     * @param int $userid User ID (0 = no user filter)
     * @param int $courseid Course ID (0 = no context filter)
     * @return array List of roles with assignment information
     */
    public static function get($userid = 0, $courseid = -1) {
        $params = self::validate_parameters(self::get_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);

        // Get context from courseid (null if courseid is 0).
        $context = null;
        if ($params['courseid'] > 0) {
            $context = \context_course::instance($params['courseid'], IGNORE_MISSING);
        } else if ($params['courseid'] == 0) {
            /** @var \context System context instance */
            $context = \context_system::instance();
        }

        // Use role service to get roles.
        $roles_data = role_service::get_roles($params['userid'], $context);

        return [
            'status' => true,
            'data' => $roles_data,
            'count' => count($roles_data),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function get_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'roleid' => new external_value(PARAM_INT, 'Role ID'),
                    'shortname' => new external_value(PARAM_RAW, 'Role shortname'),
                    'name' => new external_value(PARAM_RAW, 'Role name'),
                    'contextid' => new external_value(PARAM_INT, 'Context ID where role is assigned (null if not applicable)', VALUE_OPTIONAL),
                ])
            ),
            'count' => new external_value(PARAM_INT, 'Number of roles returned', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_RAW, 'Response message', VALUE_OPTIONAL),

        ]);
    }
}
