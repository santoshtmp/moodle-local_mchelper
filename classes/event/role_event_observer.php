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
 * Role event observer for local_mchelpers plugin.
 *
 * Handles all role-related events.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling role events.
 *
 * Observers are configured in db/events.php and respond to Moodle role events.
 */
class role_event_observer {

    /**
     * Triggered when a role is assigned to a user.
     *
     * Can be used to update user capabilities or metadata.
     *
     * @param \core\event\role_assigned $event The role assigned event
     * @return void
     */
    public static function role_assigned(\core\event\role_assigned $event): void {
        $userid = $event->relateduserid;
        $roleid = $event->roleid;
        $contextid = $event->contextid;

        // Handle role assignment if needed.
        // mtrace("  mchelpers: Role {$roleid} assigned to user {$userid} in context {$contextid}");
    }

    /**
     * Triggered when a role is unassigned from a user.
     *
     * Can be used to clean up role-specific user data.
     *
     * @param \core\event\role_unassigned $event The role unassigned event
     * @return void
     */
    public static function role_unassigned(\core\event\role_unassigned $event): void {
        $userid = $event->relateduserid;
        $roleid = $event->roleid;
        $contextid = $event->contextid;

        // Handle role unassignment if needed.
        // mtrace("  mchelpers: Role {$roleid} unassigned from user {$userid} in context {$contextid}");
    }
}
