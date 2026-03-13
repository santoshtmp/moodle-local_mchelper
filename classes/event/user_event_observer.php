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
 * User event observer for local_mchelpers plugin.
 *
 * Handles all user-related events.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\event;

use local_mchelpers\datarepository\usermeta_datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling user events.
 *
 * Observers are configured in db/events.php and respond to Moodle user events.
 */
class user_event_observer {

    /**
     * Triggered when a user is deleted.
     *
     * Cleans up all user metadata to prevent orphaned records.
     *
     * @param \core\event\user_deleted $event The user deleted event
     * @return void
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        $userid = $event->relateduserid;

        // Clean up user metadata.
        \local_mchelpers\datarepository\usermeta_datarepository::delete_all($userid);
    }

    /**
     * Triggered when a user is created.
     *
     * Can be used to initialize default user metadata.
     *
     * @param \core\event\user_created $event The user created event
     * @return void
     */
    public static function user_created(\core\event\user_created $event): void {
        $userid = $event->objectid;

        // Initialize default user metadata if needed.
        // \local_mchelpers\datarepository\usermeta_datarepository::set($userid, 'account_initialized', '1');
    }

    /**
     * Triggered when a user is updated.
     *
     * Can be used to update cached user data or trigger notifications.
     *
     * @param \core\event\user_updated $event The user updated event
     * @return void
     */
    public static function user_updated(\core\event\user_updated $event): void {
        $userid = $event->objectid;

        // usermeta_datarepository::set($userid, 'csckey', '909090');

        // Handle user update events (e.g., clear caches).
        // mtrace("  mchelpers: User {$userid} updated");
    }

    /**
     * Triggered when a user is logged in.
     *
     * Can be used to track user activity or update last login metadata.
     *
     * @param \core\event\user_loggedin $event The user loggedin event
     * @return void
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        $userid = $event->objectid;

        // Track user login if needed.
        // mtrace("  mchelpers: User {$userid} logged in");
    }

    /**
     * Triggered when a user is logged out.
     *
     * @param \core\event\user_loggedout $event The user loggedout event
     * @return void
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        global $DB;

        $userid = $event->objectid;

        // Invalidate SSO verification hash (cleanup).
        $DB->set_field('user_preferences', 'value', '', [
            'userid' => $userid,
            'name' => 'local_mchelpers_sso_verify',
        ]);
    }
}
