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
 * Role service for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

namespace local_mchelpers\local\service;

use core\user as core_user;
use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Role service class.
 *
 * Provides business logic for role management operations.
 */
class role_service {

    /**
     * Get role information by role ID or role shortname.
     */
    public static function get_role($roleid = null, $shortname = null) {
        global $DB;
        $role = null;
        if ($roleid && $shortname) {
            $role = $DB->get_record('role', ['id' => $roleid, 'shortname' => $shortname]);
        } else if ($roleid) {
            $role = $DB->get_record('role', ['id' => $roleid]);
        } else if ($shortname) {
            $role = $DB->get_record('role', ['shortname' => $shortname]);
        }
        if ($role) {
            $role->name = role_get_name($role);
        }
        return $role;
    }

    /**
     * Get roles based on userid and context parameters.
     *
     * Supports 4 cases:
     * 1. Both userid and context provided: Get all roles for user in that specific context
     * 2. userid only provided: Get all roles for user across all contexts
     * 3. context only provided: Get all roles that can be applied to that context
     * 4. Neither provided: Get all roles defined in the system
     *
     * @param int $userid User ID to get assigned roles (0 = no user filter)
     * @param \context|null $context Context object to filter roles (null = no context filter)
     * @return array List of roles with assignment information
     * 
     * This is the same as default get_user_roles but with some custom logic to handle different cases and include admin role if applicable.
     * 
     */
    public static function get_roles($userid = 0, $context = null, $checkparentcontexts = true) {
        global $DB;

        $roles_data = [];

        // CASE 1: Both userid and context provided
        // Get user's assigned roles in that specific context
        if ($userid && !empty($context)) {
            $contextids = [];
            if ($checkparentcontexts) {
                $contextids = $context->get_parent_context_ids();
            }
            $contextids[] = $context->id;
            [$insqlcontextids, $inparams]  = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'rolectx');

            $sql = "SELECT DISTINCT r.id, r.shortname, r.name, ra.contextid as contextid
                      FROM {role_assignments} ra
                      JOIN {role} r ON ra.roleid = r.id
                     WHERE ra.userid = :userid
                           AND ra.contextid $insqlcontextids
                  ORDER BY r.sortorder ASC";

            $params = array_merge($inparams, [
                'userid' => $userid,
            ]);

            $roles = $DB->get_records_sql($sql, $params);

            foreach ($roles as $role) {
                $roles_data[] = [
                    'roleid' => (int) $role->id,
                    'shortname' => $role->shortname,
                    'name' => $role->name ?: role_get_name($role),
                    'contextid' => (int) $role->contextid,
                ];
            }

            // Add admin role if user is site admin.
            if (is_siteadmin($userid)) {
                $roles_data[] = [
                    'roleid' => null,
                    'shortname' => 'admin',
                    'name' => get_string('admin'),
                    'contextid' => (int) $context->id,
                ];
            }

            return $roles_data;
        }

        // CASE 2: userid only provided (no context)
        // Get all roles assigned to user across all contexts
        if ($userid && empty($context)) {
            $sql = "SELECT DISTINCT r.id, r.shortname, r.name, ra.contextid as contextid
                      FROM {role_assignments} ra
                      JOIN {role} r ON ra.roleid = r.id
                     WHERE ra.userid = :userid
                  ORDER BY r.sortorder ASC";

            $params = ['userid' => $userid];
            $roles = $DB->get_records_sql($sql, $params);

            foreach ($roles as $role) {
                $roles_data[] = [
                    'roleid' => (int) $role->id,
                    'shortname' => $role->shortname,
                    'name' => $role->name ?: role_get_name($role),
                    'contextid' => (int) $role->contextid,
                ];
            }

            // Add admin role if user is site admin.
            if (is_siteadmin($userid)) {
                $roles_data[] = [
                    'roleid' => null,
                    'shortname' => 'admin',
                    'name' => get_string('admin'),
                    'contextid' => null,
                ];
            }

            return $roles_data;
        }

        // CASE 3: context only provided (no userid)
        // Get all roles that can be applied to this context
        if (!$userid && !empty($context)) {
            $contextlvls = [];
            if ($checkparentcontexts) {
                $parentcontexts = $context->get_parent_contexts();
                foreach ($parentcontexts as $parentcontext) {
                    $contextlvls[] = $parentcontext->contextlevel;
                }
            }
            $contextlvls[] = $context->contextlevel;
            [$insqlcontextlvls, $inparams]  = $DB->get_in_or_equal($contextlvls, SQL_PARAMS_NAMED, 'rolectx');

            $sql = "SELECT r.*
                      FROM {role} r
                      JOIN {role_context_levels} rcl ON r.id = rcl.roleid
                     WHERE rcl.contextlevel $insqlcontextlvls
                  ORDER BY r.sortorder ASC";

            $roles = $DB->get_records_sql($sql, $inparams);

            foreach ($roles as $role) {
                $roles_data[] = [
                    'roleid' => (int) $role->id,
                    'shortname' => $role->shortname,
                    'name' => $role->name ?: role_get_name($role),
                    'contextid' => null,
                ];
            }

            return $roles_data;
        }

        // CASE 4: Neither userid nor context provided
        // Get all roles defined in the system
        $roles = $DB->get_records('role', null, 'sortorder ASC');

        foreach ($roles as $role) {
            $roles_data[] = [
                'roleid' => (int) $role->id,
                'shortname' => $role->shortname,
                'name' => $role->name ?: role_get_name($role),
                'contextid' => null,
            ];
        }

        return $roles_data;
    }

    /**
     * Assign or unassign a system role to/from a user.
     *
     * @param int $userid User ID
     * @param string $role Role shortname (e.g., admin, manager, coursecreator)
     * @param string $action Action to perform: 'add' or 'remove'
     * @param int $courseid Course ID for role assignment (default: 0 = system context)
     * @return array Result with status and message
     */
    public static function update_system_role($userid, $role, $action = 'add', $courseid = 0) {
        global $CFG, $DB;

        $result = [
            'status' => false,
            'message' => '',
        ];

        // Validate user exists.
        if (!$existing_user = core_user::get_user($userid)) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        // Get context from courseid or use system context.
        if ($courseid) {
            /** @var \context System context instance */
            $context = \context_course::instance($courseid, IGNORE_MISSING);
        }
        if (empty($context)) {
            /** @var \context System context instance */
            $context = context_system::instance();
        }

        // Handle admin role separately (system-level only).
        if ($role === 'admin') {
            if ($courseid) {
                $result['message'] = get_string('adminroleonlysystem', 'local_mchelpers');
                return $result;
            }
            return self::update_system_admin($userid, $action);
        }

        // Check capability for non-admin roles.
        require_capability('moodle/role:assign', $context);

        // Get role ID from shortname.
        $role_record = $DB->get_record('role', ['shortname' => $role]);
        if (!$role_record) {
            $result['message'] = get_string('invalidrole', 'local_mchelpers');
            return $result;
        }

        // Check if role is assignable in this context.
        $assignable_roles = get_assignable_roles($context, ROLENAME_SHORT);
        if (!array_key_exists($role_record->id, $assignable_roles)) {
            $result['message'] = get_string('rolenotassignable', 'local_mchelpers');
            return $result;
        }

        if ($action === 'add') {
            // Check if role is already assigned.
            $existing_assignment = $DB->get_record('role_assignments', [
                'roleid' => $role_record->id,
                'contextid' => $context->id,
                'userid' => $userid,
                'component' => '',
                'itemid' => 0,
            ]);

            if ($existing_assignment) {
                $result['status'] = true;
                $result['message'] = get_string('rolealreadyassigned', 'local_mchelpers');
                return $result;
            }

            // Assign role.
            role_assign($role_record->id, $userid, $context->id);

            $result['status'] = true;
            $result['message'] = get_string('roleassigned', 'local_mchelpers');
        } else if ($action === 'remove') {
            // Unassign role.
            role_unassign($role_record->id, $userid, $context->id);

            $result['status'] = true;
            $result['message'] = get_string('roleunassigned', 'local_mchelpers');
        }

        return $result;
    }

    /**
     * Add or remove admin role from a user.
     *
     * @param int $userid User ID
     * @param string $action Action to perform: 'add' or 'remove'
     * @return array Result with status and message
     */
    public static function update_system_admin($userid, $action = 'add') {
        global $CFG;

        $result = [
            'status' => false,
            'message' => '',
        ];

        // Validate user exists.
        if (!$existing_user = core_user::get_user($userid)) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        $admins = [];
        foreach (explode(',', $CFG->siteadmins) as $admin) {
            $admin = (int) $admin;
            if ($admin) {
                $admins[$admin] = $admin;
            }
        }

        if ($action === 'add') {
            // Check if user is already an admin.
            if (isset($admins[$userid])) {
                $result['status'] = true;
                $result['message'] = get_string('useralreadyadmin', 'local_mchelpers');
                return $result;
            }

            // Add user to admins.
            $admins[$userid] = $userid;
        } else if ($action === 'remove') {
            // Check if user is an admin.
            if (!isset($admins[$userid])) {
                $result['status'] = true;
                $result['message'] = get_string('usernotadmin', 'local_mchelpers');
                return $result;
            }

            // Remove user from admins.
            unset($admins[$userid]);
        }

        $logstringold = $CFG->siteadmins;
        $logstringnew = implode(',', $admins);

        // Only update if changed.
        if ($logstringold !== $logstringnew) {
            set_config('siteadmins', $logstringnew);
            add_to_config_log('siteadmins', $logstringold, $logstringnew, 'core');
        }

        $result['status'] = true;
        $result['message'] = $action === 'add'
            ? get_string('adminassigned', 'local_mchelpers')
            : get_string('adminunassigned', 'local_mchelpers');

        return $result;
    }

    /**
     * Sync user roles - assign new roles and remove old ones.
     *
     * @param int $userid User ID
     * @param array $new_roles Array of role shortnames to assign
     * @param \context|null $context Context object (null = system context)
     * @return array Result with status and message
     */
    public static function sync_user_roles($userid, $new_roles, $context = null) {
        global $DB;

        $result = [
            'status' => true,
            'message' => '',
        ];

        // Use system context if not provided.
        if (empty($context)) {
            $context = context_system::instance();
        }

        // Get current roles (array of role arrays with roleid, shortname, name).
        $current_roles_data = self::get_roles($userid, $context);

        // Extract just the shortnames for comparison.
        $current_roles = array_column($current_roles_data, 'shortname');

        // Roles to add (in new_roles but not in current_roles).
        $roles_to_add = array_diff($new_roles, $current_roles);

        // Roles to remove (in current_roles but not in new_roles).
        $roles_to_remove = array_diff($current_roles, $new_roles);

        // Add new roles.
        foreach ($roles_to_add as $role) {
            $assign_result = self::update_system_role($userid, $role, 'add', 0);
            if (!$assign_result['status']) {
                $result['status'] = false;
                $result['message'] .= $assign_result['message'] . ' ';
            }
        }

        // Remove old roles.
        foreach ($roles_to_remove as $role) {
            $unassign_result = self::update_system_role($userid, $role, 'remove', 0);
            if (!$unassign_result['status']) {
                $result['status'] = false;
                $result['message'] .= $unassign_result['message'] . ' ';
            }
        }

        if (empty($result['message'])) {
            $result['message'] = get_string('rolesynced', 'local_mchelpers');
        }

        return $result;
    }
}
