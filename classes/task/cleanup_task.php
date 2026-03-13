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
 * Scheduled task for cleaning up old content data.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task class for cleaning up old/draft content.
 *
 * Tasks are configured in db/tasks.php and run by Moodle's cron system.
 */
class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('cleanup_task', 'local_mchelpers');
    }

    /**
     * Execute the task.
     *
     * This is where the actual work of the task is performed.
     * Moodle's cron system will call this method automatically.
     */
    public function execute(): void {
        global $DB;

        $this->cleanup_old_drafts();
        $this->cleanup_expired_metadata();
    }

    /**
     * Clean up old draft content (older than 30 days).
     */
    private function cleanup_old_drafts(): void {
        global $DB;

        $threshold = time() - (30 * DAYSECS); // 30 days ago

        $count = $DB->delete_records_select(
            'local_mchelpers_content',
            'status = :status AND timecreated < :threshold',
            ['status' => 0, 'threshold' => $threshold]
        );

        if ($count > 0) {
            mtrace("  Deleted $count old draft content records.");
        }
    }

    /**
     * Clean up orphaned metadata entries.
     */
    private function cleanup_expired_metadata(): void {
        global $DB;

        $sql = "DELETE FROM {local_mchelpers_content_meta}
                WHERE contentid NOT IN (SELECT id FROM {local_mchelpers_content})";

        $DB->execute($sql);

        mtrace("  Cleaned up orphaned metadata records.");
    }
}
