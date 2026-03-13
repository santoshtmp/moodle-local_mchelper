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
 * Privacy API provider for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy provider class for GDPR compliance.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Return meta data about this plugin.
     *
     * @param collection $collection The initialised collection to add items to
     * @return collection A listing of user data stored through this system
     */
    public static function get_metadata(collection $collection): collection {
        // Content table - stores user-created content.
        $collection->add_database_table(
            'local_mchelpers_content',
            [
                'userid' => 'privacy:metadata:local_mchelpers_content:userid',
                'usermodified' => 'privacy:metadata:local_mchelpers_content:usermodified',
                'contenttype' => 'privacy:metadata:local_mchelpers_content:contenttype',
                'title' => 'privacy:metadata:local_mchelpers_content:title',
                'shortname' => 'privacy:metadata:local_mchelpers_content:shortname',
                'content' => 'privacy:metadata:local_mchelpers_content:content',
                'status' => 'privacy:metadata:local_mchelpers_content:status',
                'timecreated' => 'privacy:metadata:local_mchelpers_content:timecreated',
                'timemodified' => 'privacy:metadata:local_mchelpers_content:timemodified',
            ],
            'privacy:metadata:local_mchelpers_content'
        );

        // User metadata table.
        $collection->add_database_table(
            'local_mchelpers_user_meta',
            [
                'userid' => 'privacy:metadata:local_mchelpers_user_meta:userid',
                'meta_key' => 'privacy:metadata:local_mchelpers_user_meta:meta_key',
                'meta_value' => 'privacy:metadata:local_mchelpers_user_meta:meta_value',
                'timecreated' => 'privacy:metadata:local_mchelpers_user_meta:timecreated',
                'timemodified' => 'privacy:metadata:local_mchelpers_user_meta:timemodified',
            ],
            'privacy:metadata:local_mchelpers_user_meta'
        );

        // Course metadata table (no direct user data, but linked to courses).
        $collection->add_database_table(
            'local_mchelpers_course_meta',
            [
                'courseid' => 'privacy:metadata:local_mchelpers_course_meta:courseid',
                'meta_key' => 'privacy:metadata:local_mchelpers_course_meta:meta_key',
                'meta_value' => 'privacy:metadata:local_mchelpers_course_meta:meta_value',
                'timecreated' => 'privacy:metadata:local_mchelpers_course_meta:timecreated',
                'timemodified' => 'privacy:metadata:local_mchelpers_course_meta:timemodified',
            ],
            'privacy:metadata:local_mchelpers_course_meta'
        );

        return $collection;
    }

    /**
     * Get contexts where user data is stored.
     *
     * @param int $userid User ID
     * @return contextlist List of contexts
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist Approved context list
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;

        // Export user's content.
        $contents = $DB->get_records('local_mchelpers_content', ['userid' => $userid]);
        foreach ($contents as $content) {
            $data = (object) [
                'title' => $content->title,
                'contenttype' => $content->contenttype,
                'status' => $content->status,
                'timecreated' => transform::datetime($content->timecreated),
                'timemodified' => transform::datetime($content->timemodified),
            ];
            writer::with_context(\context_system::instance())
                ->export_data(['local_mchelpers', 'content'], $data);
        }

        // Export user's metadata.
        $usermeta = $DB->get_records('local_mchelpers_user_meta', ['userid' => $userid]);
        foreach ($usermeta as $meta) {
            $data = (object) [
                'meta_key' => $meta->meta_key,
                'meta_value' => $meta->meta_value,
                'timecreated' => transform::datetime($meta->timecreated),
                'timemodified' => transform::datetime($meta->timemodified),
            ];
            writer::with_context(\context_system::instance())
                ->export_data(['local_mchelpers', 'user_meta'], $data);
        }
    }

    /**
     * Delete user data.
     *
     * @param approved_contextlist $contextlist Approved context list
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Delete user's content.
        $DB->delete_records('local_mchelpers_content', ['userid' => $userid]);

        // Delete user's metadata.
        $DB->delete_records('local_mchelpers_user_meta', ['userid' => $userid]);
    }

    /**
     * Delete data for all users in a context.
     *
     * @param \context $context The context to delete data for
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        // Only system context is used by this plugin.
        if (!$context instanceof \context_system) {
            return;
        }

        // Delete all content (all users).
        $DB->delete_records('local_mchelpers_content');

        // Delete all user metadata (all users).
        $DB->delete_records('local_mchelpers_user_meta');
    }
}
