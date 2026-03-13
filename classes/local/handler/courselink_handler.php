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
 * Course link handler for local_mchelpers plugin.
 *
 * Provides functionality for managing course enrollment links.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\handler;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use stdClass;

/**
 * Handler class for course enrollment link operations.
 *
 * Manages meta enrollment links between courses.
 */
class courselink_handler {

    /**
     * Add meta enrollment links to other courses.
     *
     * @param string $othercourses Comma-separated list of course IDs to link
     * @return void
     */
    public static function set_meta_link_enrollment($othercourses) {
        global $CFG, $DB, $COURSE;

        require_once($CFG->dirroot . '/enrol/meta/lib.php');
        require_once($CFG->dirroot . '/enrol/meta/locallib.php');
        
        $othercourseslist = $othercourses ? explode(',', $othercourses) : [];
        $enrolmetaplugin = new \enrol_meta_plugin();
        
        foreach ($othercourseslist as $courseid) {
            $fields = [
                'customint1' => $COURSE->id,
                'customint2' => $courseid
            ];
            
            $course = $DB->get_record('course', ['id' => $courseid]);
            $existing = $DB->get_record('enrol', [
                'courseid' => $courseid,
                'enrol' => 'meta',
                'customint1' => $COURSE->id
            ]);
            
            if (!$existing) {
                $enrolmetaplugin->add_instance($course, $fields);
            }
        }
    }

    /**
     * Remove meta enrollment links.
     *
     * @param int $courseid Course ID to unlink from
     * @param string $othercourses Comma-separated list of course IDs to keep (optional)
     * @return void
     */
    public static function unset_meta_link_enrollment($courseid, $othercourses = '') {
        global $CFG, $DB;
        
        require_once($CFG->libdir . '/enrollib.php');

        // Find the meta enrollment method.
        $enrolinstances = $DB->get_records('enrol', [
            'enrol' => 'meta',
            'customint1' => $courseid
        ]);
        
        $othercourseslist = $othercourses ? explode(',', $othercourses) : [];

        if ($enrolinstances) {
            $enrolplugin = enrol_get_plugin('meta');
            if ($enrolplugin) {
                foreach ($enrolinstances as $enrolinstance) {
                    if (in_array($enrolinstance->courseid, $othercourseslist)) {
                        continue;
                    }
                    $enrolplugin->delete_instance($enrolinstance);
                }
            }
        }
    }
}
