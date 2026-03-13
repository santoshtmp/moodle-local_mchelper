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
 * Module event observer for local_mchelpers plugin.
 *
 * Handles all course module-related events.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\event;

use local_mchelpers\local\mchelpers;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling course module events.
 *
 * Observers are configured in db/events.php and respond to Moodle module events.
 */
class module_event_observer {

    /**
     * Triggered when a course module is created.
     *
     * Can be used to initialize module-specific metadata or settings.
     *
     * @param \core\event\course_module_created $event The course module created event
     * @return void
     */
    public static function course_module_created(\core\event\course_module_created $event): void {
        $courseid = $event->courseid;
        $cmid = $event->objectid;
        $modulename = $event->other['modulename'];
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;

        // Log the module creation event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, CMID={$cmid}, ModuleName={$modulename}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }

    /**
     * Triggered when a course module is updated.
     *
     * Can be used to update cached module data or trigger notifications.
     *
     * @param \core\event\course_module_updated $event The course module updated event
     * @return void
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        $courseid = $event->courseid;
        $cmid = $event->objectid;
        $modulename = $event->other['modulename'];
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;
        // $event_data = $event->get_data();
        // $cm = get_coursemodule_from_id($modulename, $cmid, 0, false, MUST_EXIST);

        // Log the event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, CMID={$cmid}, ModuleName={$modulename}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }

    /**
     * Triggered when a course module is deleted.
     *
     * Can be used to clean up module-specific metadata.
     *
     * @param \core\event\course_module_deleted $event The course module deleted event
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        $courseid = $event->courseid;
        $cmid = $event->objectid;
        $modulename = $event->other['modulename'];
        $userid = $event->userid;
        $target = $event->target;
        $eventname = $event::class;

        // Log the event with details.
        $log_message = "event={$eventname}, target={$target}, CourseID={$courseid}, CMID={$cmid}, ModuleName={$modulename}, UserID={$userid}";
        mchelpers::set_log_message($log_message, 'log');
    }

    /**
     * Triggered when a course module is viewed.
     *
     * Can be used to track module view statistics.
     *
     * @param \core\event\course_module_viewed $event The course module viewed event
     * @return void
     */
    public static function course_module_viewed(\core\event\course_module_viewed $event): void {
        $cmid = $event->objectid;
        $courseid = $event->courseid;

        // Track module view if needed.
        // mtrace("  mchelpers: Course module {$cmid} viewed in course {$courseid}");
    }

    /**
     * Triggered when a course module completion is updated.
     *
     * Can be used to track completion statistics or trigger notifications.
     *
     * @param \core\event\course_module_completion_updated $event The completion updated event
     * @return void
     */
    public static function course_module_completion_updated(\core\event\course_module_completion_updated $event): void {
        $cmid = $event->objectid;
        $userid = $event->relateduserid;

        // Track completion if needed.
        // mtrace("  mchelpers: Module {$cmid} completion updated for user {$userid}");
    }
}
