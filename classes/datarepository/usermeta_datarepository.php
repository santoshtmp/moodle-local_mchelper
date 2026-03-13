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
 * User metadata repository class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\datarepository;

defined('MOODLE_INTERNAL') || die();

/**
 * Repository class for managing user metadata.
 *
 * Handles all database operations for user meta.
 */
class usermeta_datarepository {

    /**
     * The database table name for user metadata.
     *
     * @var string Table name without prefix
     */
    protected static $tablename = 'local_mchelpers_user_meta';

    /**
     * Get user metadata value by user ID and meta key.
     *
     * @param int $userid User ID
     * @param string $metakey Metadata key
     * @param string $fields Fields to retrieve (default: '*')
     * @param int $strictness How to handle missing records (default: IGNORE_MISSING)
     * @return string|false Metadata value or false if not found
     */
    public static function get($userid, $metakey, $fields = '*', $strictness = IGNORE_MISSING) {
        global $DB;

        $record = $DB->get_record(
            self::$tablename,
            ['userid' => $userid, 'meta_key' => $metakey],
            $fields,
            $strictness
        );

        return $record ? $record->meta_value : false;
    }

    /**
     * Set user metadata value.
     *
     * @param int $userid User ID
     * @param string $metakey Metadata key
     * @param string $metavalue Metadata value
     * @return bool Success or failure
     */
    public static function set($userid, $metakey, $metavalue): bool {
        global $DB;

        $record = $DB->get_record(
            self::$tablename,
            ['userid' => $userid, 'meta_key' => $metakey]
        );

        $now = time();

        if ($record) {
            $record->meta_value = $metavalue;
            $record->timemodified = $now;
            return $DB->update_record(self::$tablename, $record);
        } else {
            $newrecord = new \stdClass();
            $newrecord->userid = $userid;
            $newrecord->meta_key = $metakey;
            $newrecord->meta_value = $metavalue;
            $newrecord->timecreated = $now;
            $newrecord->timemodified = $now;
            return $DB->insert_record(self::$tablename, $newrecord) !== false;
        }
    }

    /**
     * Delete user metadata.
     *
     * @param int $userid User ID
     * @param string $metakey Metadata key
     * @return bool Success or failure
     */
    public static function delete($userid, $metakey): bool {
        global $DB;

        return $DB->delete_records(
            self::$tablename,
            ['userid' => $userid, 'meta_key' => $metakey]
        );
    }

    /**
     * Delete all metadata for a user.
     *
     * @param int $userid User ID
     * @return bool Success or failure
     */
    public static function delete_all($userid): bool {
        global $DB;

        return $DB->delete_records(self::$tablename, ['userid' => $userid]);
    }

    /**
     * Get metadata count for a user (useful for monitoring scale).
     *
     * @param int $userid User ID
     * @return int Count of meta records
     */
    public static function count($userid): int {
        global $DB;
        return $DB->count_records(self::$tablename, ['userid' => $userid]);
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

    /**
     * Get user by ID from user table.
     *
     * @param int $userid User ID
     * @param string $fields Fields to return (default: all fields)
     * @param int $strictness How to handle missing records (default: IGNORE_MISSING)
     * @return object|false User record object or false if not found
     */
    public static function get_user_by_id($userid, $fields = '*', $strictness = IGNORE_MISSING) {
        global $DB;

        return $DB->get_record('user', ['id' => $userid], $fields, $strictness) ?: false;
    }

    /**
     * Get all metadata for a user.
     *
     * @param int $userid User ID
     * @return array Array of metadata records
     */
    public static function get_all($userid): array {
        global $DB;

        return $DB->get_records(self::$tablename, ['userid' => $userid]);
    }
}
