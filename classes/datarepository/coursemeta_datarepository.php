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
 * Course metadata repository class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Repository class for managing course metadata.
 *
 * Handles all database operations for course meta.
 */
class coursemeta_datarepository {

    /**
     * The database table name for course metadata.
     *
     * @var string Table name without prefix
     */
    protected static $tablename = 'local_mchelpers_course_meta';

    /**
     * Get course metadata value by course ID and meta key.
     *
     * @param int $courseid Course ID
     * @param string $metakey Metadata key
     * @return string|false Metadata value or false if not found
     */
    public static function get($courseid, $metakey) {
        global $DB;

        $record = $DB->get_record(
            self::$tablename,
            ['courseid' => $courseid, 'meta_key' => $metakey],
            'meta_value'
        );

        return $record ? $record->meta_value : false;
    }

    /**
     * Set course metadata value.
     *
     * @param int $courseid Course ID
     * @param string $metakey Metadata key
     * @param string $metavalue Metadata value (can be long text)
     * @return bool Success or failure
     */
    public static function set($courseid, $metakey, $metavalue): bool {
        global $DB;

        $record = $DB->get_record(
            self::$tablename,
            ['courseid' => $courseid, 'meta_key' => $metakey]
        );

        $now = time();

        if ($record) {
            // Update existing record.
            $record->meta_value = $metavalue;
            $record->timemodified = $now;
            return $DB->update_record(self::$tablename, $record);
        } else {
            // Insert new record.
            $newrecord = new \stdClass();
            $newrecord->courseid = $courseid;
            $newrecord->meta_key = $metakey;
            $newrecord->meta_value = $metavalue;
            $newrecord->timecreated = $now;
            $newrecord->timemodified = $now;
            return $DB->insert_record(self::$tablename, $newrecord) !== false;
        }
    }

    /**
     * Delete course metadata.
     *
     * @param int $courseid Course ID
     * @param string $metakey Metadata key
     * @return bool Success or failure
     */
    public static function delete($courseid, $metakey): bool {
        global $DB;

        return $DB->delete_records(
            self::$tablename,
            ['courseid' => $courseid, 'meta_key' => $metakey]
        );
    }

    /**
     * Get all metadata for a course.
     *
     * @param int $courseid Course ID
     * @return array Array of metadata records
     */
    public static function get_all($courseid): array {
        global $DB;

        return $DB->get_records(self::$tablename, ['courseid' => $courseid]);
    }

    /**
     * Delete all metadata for a course.
     *
     * @param int $courseid Course ID
     * @return bool Success or failure
     */
    public static function delete_all($courseid): bool {
        global $DB;

        return $DB->delete_records(self::$tablename, ['courseid' => $courseid]);
    }

    /**
     * Delete all metadata when a course is deleted (cleanup hook).
     *
     * @param int $courseid Course ID being deleted
     * @return bool Success or failure
     */
    public static function cleanup_course($courseid): bool {
        global $DB;
        return $DB->delete_records(self::$tablename, ['courseid' => $courseid]);
    }

    /**
     * Get metadata count for a course (useful for monitoring scale).
     *
     * @param int $courseid Course ID
     * @return int Count of meta records
     */
    public static function count($courseid): int {
        global $DB;
        return $DB->count_records(self::$tablename, ['courseid' => $courseid]);
    }

    /**
     * Get total meta records count (for monitoring).
     *
     * @return int Total count
     */
    public static function total_count(): int {
        global $DB;
        return $DB->count_records(self::$tablename);
    }
}
