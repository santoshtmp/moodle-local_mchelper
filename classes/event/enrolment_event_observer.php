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
 * Enrollment event observer for local_mchelpers plugin.
 *
 * Handles all enrollment-related events.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling enrollment events.
 *
 * Observers are configured in db/events.php and respond to Moodle enrollment events.
 */
class enrolment_event_observer {

    /**
     * Triggered when a user enrollment is created.
     *
     * Can be used to initialize enrollment-specific metadata.
     *
     * @param \core\event\user_enrolment_created $event The enrollment created event
     * @return void
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        // Handle enrollment creation if needed.
        // mtrace("  mchelpers: User {$userid} enrolled in course {$courseid}");
    }

    /**
     * Triggered when a user enrollment is updated.
     *
     * Can be used to update enrollment metadata or trigger notifications.
     *
     * @param \core\event\user_enrolment_updated $event The enrollment updated event
     * @return void
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        // Handle enrollment update if needed.
        // mtrace("  mchelpers: User {$userid} enrollment updated in course {$courseid}");
    }

    /**
     * Triggered when a user enrollment is deleted.
     *
     * Can be used to clean up enrollment-specific metadata.
     *
     * @param \core\event\user_enrolment_deleted $event The enrollment deleted event
     * @return void
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event): void {
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        // Clean up enrollment metadata if needed.
        // mtrace("  mchelpers: User {$userid} enrollment deleted from course {$courseid}");
    }
}
