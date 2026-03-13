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
 * Content repository class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\datarepository;


defined('MOODLE_INTERNAL') || die();

/**
 * Repository class for handling content data access.
 *
 * Provides methods for CRUD operations and metadata management for content.
 */
class content_datarepository {

    /**
     * Default content type constant.
     */
    public const DEFAULT_CONTENT_TYPE = 'page';

    /**
     * The database table name for content.
     *
     * @var string Table name without prefix
     */
    protected static $tablename = 'local_mchelpers_content';

    /**
     * The database table name for content metadata.
     *
     * @var string Table name without prefix
     */
    protected static $meta_tablename = 'local_mchelpers_content_meta';

    /**
     * Get all contents with optional filtering and pagination.
     *
     * @param array $conditions Conditions to filter by (contenttype, status, search)
     * @param string $sort Sort order (default: 'timecreated DESC')
     * @param int $pagenumber Page number for pagination (0-based)
     * @param int $limitnum Number of records per page
     * @return array Array containing 'data' (records) and 'meta' (pagination info)
     */
    public static function get_all($conditions = [], $sort = 'timecreated DESC', $pagenumber = 0, $limitnum = 30): array {
        global $DB;
        $sort = $sort ?: 'timecreated DESC';
        $limitfrom  = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;

        // Build SQL.
        $sqlquery = '1=1';
        $params = [];

        if (!empty($conditions['contenttype']) && $conditions['contenttype'] !== 'all') {
            $sqlquery .= ' AND contenttype = :contenttype';
            $params['contenttype'] = $conditions['contenttype'];
        }

        if (!empty($conditions['status']) && $conditions['status'] !== 'all') {
            $sqlquery .= ' AND status = :status';
            $params['status'] = $conditions['status'];
        }

        if (!empty($conditions['search'])) {
            $sqlquery .= ' AND (title LIKE :search OR shortname LIKE :search2)';
            $params['search'] = '%' . $DB->sql_like_escape($conditions['search']) . '%';
            $params['search2'] = '%' . $DB->sql_like_escape($conditions['search']) . '%';
        }

        // Get total count.
        $totalrecords = $DB->count_records_select(self::$tablename, $sqlquery, $params);

        // Get records.
        $records = $DB->get_records_select(
            self::$tablename,
            $sqlquery,
            $params,
            $sort,
            '*',
            $limitfrom,
            $limitnum
        );

        // Arrange data
        $get_records = [];
        $get_records['data'] = $records;
        $get_records['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ($limitnum > 0) ? ceil($totalrecords / $limitnum) : 1,
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => ($records) ? count($records) : 0,
            'datafrom' => ($records) ? $limitfrom + 1 : 0,
            'datato' => ($records) ? count($records) + $limitfrom : 0,
        ];
        return $get_records;
    }

    /**
     * Get content by ID.
     *
     * @param int $id Content ID
     * @param string $fields Fields to retrieve (default: '*')
     * @param int $strictness Strictness mode (default: IGNORE_MISSING)
     * @return object|false Content record object or false if not found
     */
    public static function get_by_id($id, $fields = '*', $strictness = IGNORE_MISSING): object|false {
        global $DB;

        return $DB->get_record(self::$tablename, ['id' => $id], $fields, $strictness);
    }

    /**
     * Get contents by content type.
     *
     * @param string $contenttype Content type (page, faq, testimonial, etc.)
     * @param int|null $status Status filter (0=draft, 1=published, null=all)
     * @param string $sort Sort order (default: 'timecreated DESC')
     * @return array Array of content records
     */
    public static function get_by_type($contenttype, $status = null, $sort = 'timecreated DESC'): array {
        global $DB;

        $conditions = ['contenttype' => $contenttype];
        if ($status !== null) {
            $conditions['status'] = $status;
        }

        return $DB->get_records(self::$tablename, $conditions, $sort);
    }

    /**
     * Get published contents.
     *
     * @param string|null $contenttype Optional content type filter
     * @param string $sort Sort order (default: 'timecreated DESC')
     * @return array Array of published content records
     */
    public static function get_published($contenttype = null, $sort = 'timecreated DESC'): array {
        global $DB;

        $conditions = ['status' => 1];
        if ($contenttype !== null) {
            $conditions['contenttype'] = $contenttype;
        }

        return $DB->get_records(self::$tablename, $conditions, $sort);
    }

    /**
     * Create new content.
     *
     * @param object $data Content data
     * @return int New content ID
     */
    public static function create($data): int {
        global $DB, $USER;

        $now = time();
        $data->timecreated = $now;
        $data->timemodified = $now;
        $data->userid = $USER->id;

        if (!isset($data->usermodified)) {
            $data->usermodified = $USER->id;
        }

        return $DB->insert_record(self::$tablename, $data);
    }

    /**
     * Update existing content.
     *
     * @param object $data Content data (must include id)
     * @return bool Success or failure
     */
    public static function update($data): bool {
        global $DB, $USER;

        $now = time();
        $data->timemodified = $now;
        $data->usermodified = $USER->id;

        return $DB->update_record(self::$tablename, $data);
    }

    /**
     * Delete content by ID.
     *
     * @param int $id Content ID
     * @return bool Success or failure
     */
    public static function delete($id): bool {
        global $DB;

        return $DB->delete_records(self::$tablename, ['id' => $id]);
    }

    /**
     * Delete content and its metadata.
     *
     * @param int $id Content ID
     * @return bool Success or failure
     */
    public static function delete_with_meta($id): bool {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            // Delete content metadata first.
            $DB->delete_records(self::$meta_tablename, ['contentid' => $id]);

            // Delete content.
            self::delete($id);

            $transaction->allow_commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Get content metadata.
     *
     * @param int $contentid Content ID
     * @return array Array of metadata records
     */
    public static function get_meta($contentid): array {
        global $DB;

        return $DB->get_records(self::$meta_tablename, ['contentid' => $contentid]);
    }

    /**
     * Get content metadata by key.
     *
     * @param int $contentid Content ID
     * @param string $metakey Metadata key
     * @return string|false Metadata value or false if not found
     */
    public static function get_meta_value($contentid, $metakey): string|false {
        global $DB;

        $record = $DB->get_record(self::$meta_tablename, [
            'contentid' => $contentid,
            'meta_key' => $metakey,
        ], 'meta_value');

        return $record ? $record->meta_value : false;
    }

    /**
     * Set content metadata.
     *
     * @param int $contentid Content ID
     * @param string $metakey Metadata key
     * @param string $metavalue Metadata value
     * @return bool Success or failure
     */
    public static function set_meta($contentid, $metakey, $metavalue): bool {
        global $DB;

        $now = time();
        $existing = $DB->get_record(self::$meta_tablename, [
            'contentid' => $contentid,
            'meta_key' => $metakey,
        ]);

        if ($existing) {
            // Update existing.
            $existing->meta_value = $metavalue;
            $existing->timemodified = $now;
            return $DB->update_record(self::$meta_tablename, $existing);
        } else {
            // Insert new.
            $data = new \stdClass();
            $data->contentid = $contentid;
            $data->meta_key = $metakey;
            $data->meta_value = $metavalue;
            $data->timecreated = $now;
            $data->timemodified = $now;
            return $DB->insert_record(self::$meta_tablename, $data);
        }
    }

    /**
     * Delete content metadata.
     *
     * @param int $contentid Content ID
     * @param string $metakey Metadata key
     * @return bool Success or failure
     */
    public static function delete_meta($contentid, $metakey): bool {
        global $DB;

        return $DB->delete_records(self::$meta_tablename, [
            'contentid' => $contentid,
            'meta_key' => $metakey,
        ]);
    }
}