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
 * Course event observer for local_mchelpers plugin.
 *
 * Handles all course-related events.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\event;

use local_mchelpers\datarepository\coursemeta_datarepository;
use local_mchelpers\local\mchelpers;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling course events.
 *
 * Observers are configured in db/events.php and respond to Moodle course events.
 */
class course_event_observer {

    /**
     * Triggered when a course is created.
     *
     * Can be used to initialize default course metadata.
     *
     * @param \core\event\course_created $event The course created event
     * @return void
     */
    public static function course_created(\core\event\course_created $event): void {
        $courseid = $event->courseid;
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;

        // course created by meta data.
        $metakey_course_created_by = 'course_created_by';
        coursemeta_datarepository::set($courseid, $metakey_course_created_by, $userid);

        // Log the event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }


    /**
     * Triggered when a course is updated.
     *
     * Can be used to update cached course data or trigger notifications.
     *
     * @param \core\event\course_updated $event The course updated event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event): void {
        $courseid = $event->courseid;
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;
        // course updated by meta data.
        $metakey_course_updated_by = 'course_updated_by';
        coursemeta_datarepository::set($courseid, $metakey_course_updated_by, $userid);

        // Log the event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }

    /**
     * Triggered when a course is deleted.
     *
     * Cleans up all course metadata to prevent orphaned records.
     *
     * @param \core\event\course_deleted $event The course deleted event
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        $courseid = $event->courseid;
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;

        // Clean up course metadata.
        coursemeta_datarepository::cleanup_course($courseid);

        // Log the event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }


    /**
     * Triggered when a course is viewed.
     *
     * Can be used to track course view statistics.
     *
     * @param \core\event\course_viewed $event The course viewed event
     * @return void
     */
    public static function course_viewed(\core\event\course_viewed $event): void {
        $courseid = $event->courseid;

        // Track course view if needed.
        // mtrace("  mchelpers: Course {$courseid} viewed");
    }

    /**
     * Triggered when a course section is created.
     *
     * @param \core\event\course_section_created $event The course section created event
     * @return void
     */
    public static function course_section_created(\core\event\course_section_created $event): void {
        $courseid = $event->courseid;

        // Handle section creation if needed.
        // mtrace("  mchelpers: Course section created in course {$courseid}");
    }

    /**
     * Triggered when a course section is deleted.
     *
     * @param \core\event\course_section_deleted $event The course section deleted event
     * @return void
     */
    public static function course_section_deleted(\core\event\course_section_deleted $event): void {
        $courseid = $event->courseid;

        // Handle section deletion if needed.
        // mtrace("  mchelpers: Course section deleted in course {$courseid}");
    }

    /**
     * Triggered when a course category is created.
     *
     * @param \core\event\course_category_created $event The course category created event
     * @return void
     */
    public static function course_category_created(\core\event\course_category_created $event): void {
        $categoryid = $event->objectid;

        // Handle category creation if needed.
        // mtrace("  mchelpers: Course category {$categoryid} created");
    }

    /**
     * Triggered when a course category is deleted.
     *
     * @param \core\event\course_category_deleted $event The course category deleted event
     * @return void
     */
    public static function course_category_deleted(\core\event\course_category_deleted $event): void {
        $categoryid = $event->objectid;

        // Handle category deletion if needed.
        // mtrace("  mchelpers: Course category {$categoryid} deleted");
    }
}
