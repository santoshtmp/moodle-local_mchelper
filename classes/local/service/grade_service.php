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
 * Grade service class for local_mchelpers plugin.
 *
 * Provides service methods for grade-related operations including
 * user course grades, grade items, and grade calculations.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\service;

use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Grade service class for handling grade-related data operations.
 */
class grade_service {

    /**
     * Returns the user's course grade.
     *
     * @param int|null $userid User ID (null for current user).
     * @param int $courseid Course ID.
     * @return stdClass|null
     */
    public static function get_user_course_grade($userid, $courseid) {
        global $CFG, $USER;

        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/grade/grade_item.php');

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Get course grade item (course total)
        $gradeitem = \grade_item::fetch_course_item($courseid);

        if (!$gradeitem) {
            return null;
        }

        // Get user grade
        $grade = \grade_grade::fetch([
            'itemid' => $gradeitem->id,
            'userid' => $userid
        ]);

        if (!$grade || is_null($grade->finalgrade)) {
            return null;
        }

        $result = new stdClass();
        $result->grade      = $grade->finalgrade;
        $result->grademax   = $gradeitem->grademax;
        $result->percentage = $gradeitem->grademax > 0
            ? round(($grade->finalgrade / $gradeitem->grademax) * 100, 2)
            : null;
        $result->formatted  = \grade_format_gradevalue($grade->finalgrade, $gradeitem);
        $result->feedback   = $grade->feedback ?? null;

        return $result;
    }

    /**
     * Returns all grades for a course.
     *
     * @param int $courseid Course ID.
     * @return array Array of grade objects keyed by user ID.
     */
    public static function get_course_grades($courseid) {
        global $CFG;

        require_once("$CFG->libdir/gradelib.php");

        $grades = [];
        $gradeinfo = \grade_get_course_grades($courseid);

        if (!$gradeinfo || empty($gradeinfo->items)) {
            return $grades;
        }

        foreach ($gradeinfo->items as $itemid => $item) {
            if (!empty($item->grades)) {
                foreach ($item->grades as $userid => $usergrade) {
                    if (!isset($grades[$userid])) {
                        $grades[$userid] = new stdClass();
                        $grades[$userid]->userid = $userid;
                        $grades[$userid]->grade = 0;
                        $grades[$userid]->percentage = 0;
                        $grades[$userid]->formatted = '';
                        $grades[$userid]->feedback = '';
                    }

                    if (isset($usergrade->grade)) {
                        $grades[$userid]->grade = $usergrade->grade;
                        $grades[$userid]->formatted = \grade_format_gradevalue(
                            $usergrade->grade,
                            $usergrade,
                            true
                        );

                        if (isset($usergrade->grademax) && $usergrade->grademax > 0) {
                            $grades[$userid]->percentage = round(
                                ($usergrade->grade / $usergrade->grademax) * 100
                            );
                        }

                        if (isset($usergrade->feedback)) {
                            $grades[$userid]->feedback = $usergrade->feedback;
                        }
                    }
                }
            }
        }

        return $grades;
    }

    /**
     * Returns grade items for a course.
     *
     * @param int $courseid Course ID.
     * @return array Array of grade item objects.
     */
    public static function get_course_grade_items($courseid) {
        global $CFG;

        require_once("$CFG->libdir/gradelib.php");

        $gradeinfo = \grade_get_course_grades($courseid);

        if (!$gradeinfo || empty($gradeinfo->items)) {
            return [];
        }

        $items = [];
        foreach ($gradeinfo->items as $itemid => $item) {
            $gradeitem = new stdClass();
            $gradeitem->id = $itemid;
            $gradeitem->itemname = $item->itemname ?? '';
            $gradeitem->grademin = $item->grademin ?? 0;
            $gradeitem->grademax = $item->grademax ?? 0;
            $items[] = $gradeitem;
        }

        return $items;
    }

    /**
     * Returns the user's grade for a specific grade item.
     *
     * @param int $userid User ID.
     * @param int $gradeitemid Grade item ID.
     * @return stdClass|null Grade object with grade, percentage, and formatted properties.
     */
    public static function get_user_grade_item($userid, $gradeitemid) {
        global $CFG;

        require_once("$CFG->libdir/gradelib.php");

        $gradeitem = new \grade_item(['id' => $gradeitemid]);

        if (!$gradeitem->id) {
            return null;
        }

        $grade = new \grade_grade(['userid' => $userid, 'itemid' => $gradeitemid]);

        if (!$grade->id || !isset($grade->finalgrade)) {
            return null;
        }

        $result = new stdClass();
        $result->grade = $grade->finalgrade;
        $result->formatted = \grade_format_gradevalue($grade->finalgrade, $gradeitem, true);
        $result->percentage = 0;

        if ($gradeitem->grademax > 0) {
            $result->percentage = round(($grade->finalgrade / $gradeitem->grademax) * 100);
        }

        return $result;
    }
}
