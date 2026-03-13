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
 * Enrolment service class for local_mchelpers plugin.
 *
 * Provides service methods for enrolment-related operations including
 * user enrolment, unenrolment, and enrolment information retrieval.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\service;

use context_course;
use core\di;
use core\clock;
use local_mchelpers\local\mchelpers;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Enrolment service class for handling enrolment-related data operations.
 */
class enrol_services {

    /**
     * Enrol a user in a course.
     *
     * @param int $userid User ID to enrol.
     * @param int $courseid Course ID.
     * @param string $enrolmethod Enrolment method (default 'manual').
     * @param int $roleid Role ID to assign (default student role).
     * @param int $timestart Enrolment start time (default 0 = now).
     * @param int $timeend Enrolment end time (default 0 = no end).
     * @param int $status Enrolment status (default ENROL_USER_ACTIVE).
     * @return array Enrolment result with success status and message.
     */
    public static function enrol_user(
        $userid,
        $courseid,
        $enrolmethod = 'manual',
        $roleid = 0,
        $timestart = 0,
        $timeend = 0,
        $status = ENROL_USER_ACTIVE
    ) {
        global $DB;

        $result = [
            'success' => false,
            'message' => '',
            'enrolmentid' => 0,
        ];

        // Validate inputs.
        if (empty($userid) || empty($courseid)) {
            $result['message'] = get_string('invaliduserid', 'error');
            return $result;
        }

        // Check if user exists.
        if (!$DB->record_exists('user', ['id' => $userid])) {
            $result['message'] = get_string('invaliduserid', 'error');
            return $result;
        }

        // Check if course exists.
        if (!$DB->record_exists('course', ['id' => $courseid])) {
            $result['message'] = get_string('invalidcourseid', 'error');
            return $result;
        }

        // Get enrolment plugin.
        $enrol = enrol_get_plugin($enrolmethod);
        if (!$enrol) {
            $result['message'] = get_string('enrolnotfound', 'enrol_' . $enrolmethod);
            return $result;
        }

        // Get or create enrolment instance.
        $enrolinstance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => $enrolmethod,
            'status' => ENROL_INSTANCE_ENABLED,
        ]);

        if (!$enrolinstance) {
            // Try to create a new instance if the plugin allows it.
            if ($enrol->get_config('status') == ENROL_INSTANCE_ENABLED) {
                $instanceid = $enrol->add_default_instance($courseid);
                if ($instanceid) {
                    $enrolinstance = $DB->get_record('enrol', ['id' => $instanceid]);
                }
            }
        }

        if (!$enrolinstance) {
            $result['message'] = get_string('noenrolinstance', 'enrol');
            return $result;
        }

        // Check if user is already enrolled.
        $existingenrolment = $DB->get_record('user_enrolments', [
            'enrolid' => $enrolinstance->id,
            'userid' => $userid,
        ]);

        if ($existingenrolment) {
            $result['success'] = true;
            $result['message'] = get_string('useralreadyenrolled', 'enrol');
            $result['enrolmentid'] = $existingenrolment->id;
            return $result;
        }

        // Use default role if not specified.
        if (empty($roleid)) {
            $roleid = $enrolinstance->roleid ?: get_config('moodle', 'defaultrole');
        }

        // Enrol the user.
        $enrol->enrol_user($enrolinstance, $userid, $roleid, $timestart, $timeend, $status);

        // Get the enrolment record.
        $userenrolment = $DB->get_record('user_enrolments', [
            'enrolid' => $enrolinstance->id,
            'userid' => $userid,
        ]);

        if ($userenrolment) {
            $result['success'] = true;
            $result['message'] = get_string('enrolusersuccess', 'enrol');
            $result['enrolmentid'] = $userenrolment->id;
        } else {
            $result['message'] = get_string('enroluserfailed', 'enrol');
        }

        return $result;
    }

    /**
     * Unenrol a user from a course.
     *
     * @param int $userid User ID to unenrol.
     * @param int $courseid Course ID.
     * @param string $enrolmethod Enrolment method (default 'manual').
     * @return array Unenrolment result with success status and message.
     */
    public static function unenrol_user($userid, $courseid, $enrolmethod = 'manual') {
        global $DB;

        $result = [
            'success' => false,
            'message' => '',
        ];

        // Validate inputs.
        if (empty($userid) || empty($courseid)) {
            $result['message'] = get_string('invaliduserid', 'error');
            return $result;
        }

        // Get enrolment plugin.
        $enrol = enrol_get_plugin($enrolmethod);
        if (!$enrol) {
            $result['message'] = get_string('enrolnotfound', 'enrol_' . $enrolmethod);
            return $result;
        }

        // Get enrolment instance.
        $enrolinstance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => $enrolmethod,
        ]);

        if (!$enrolinstance) {
            $result['message'] = get_string('noenrolinstance', 'enrol');
            return $result;
        }

        // Check if user is enrolled.
        $userenrolment = $DB->get_record('user_enrolments', [
            'enrolid' => $enrolinstance->id,
            'userid' => $userid,
        ]);

        if (!$userenrolment) {
            $result['success'] = true;
            $result['message'] = get_string('usernotenrolled', 'enrol');
            return $result;
        }

        // Unenrol the user.
        $unenrolled = $enrol->unenrol_user($enrolinstance, $userid);

        if ($unenrolled) {
            $result['success'] = true;
            $result['message'] = get_string('unenrolusersuccess', 'enrol');
        } else {
            $result['message'] = get_string('unenroluserfailed', 'enrol');
        }

        return $result;
    }

    /**
     * Get user enrolments for a specific course or all courses.
     *
     * @param int $courseid Course ID (0 for all courses).
     * @param int $userid User ID (0 for all users).
     * @param array $filterparam Additional filter parameters.
     * @return array List of enrolment records.
     */
    public static function get_enrolments($courseid = 0, $userid = 0, $filterparam = []) {
        global $DB;

        $where = [];
        $params = [];

        if ($courseid) {
            $where[] = 'e.courseid = :courseid';
            $params['courseid'] = $courseid;
        }

        if ($userid) {
            $where[] = 'ue.userid = :userid';
            $params['userid'] = $userid;
        }

        // Filter by status.
        $enrolstatus = $filterparam['enrolstatus'] ?? '';
        if ($enrolstatus !== '') {
            $where[] = 'e.status = :enrolstatus';
            $params['enrolstatus'] = $enrolstatus;
        }

        $userstatus = $filterparam['userstatus'] ?? '';
        if ($userstatus !== '') {
            $where[] = 'ue.status = :userstatus';
            $params['userstatus'] = $userstatus;
        }

        // Filter by enrolment method.
        $enrolmethod = $filterparam['enrolmethod'] ?? '';
        if ($enrolmethod) {
            $where[] = 'e.enrol = :enrolmethod';
            $params['enrolmethod'] = $enrolmethod;
        }

        $whereclause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT ue.*, e.courseid, e.enrol, e.name as enrolname,
                       u.firstname, u.lastname, u.email
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                  JOIN {user} u ON ue.userid = u.id
                 $whereclause
              ORDER BY ue.timecreated DESC";

        $enrolments = $DB->get_records_sql($sql, $params);

        // Format enrolments.
        $formattedenrolments = [];
        foreach ($enrolments as $enrolment) {
            $formattedenrolments[] = [
                'id' => $enrolment->id,
                'userid' => $enrolment->userid,
                'courseid' => $enrolment->courseid,
                'enrolid' => $enrolment->enrolid,
                'enrolmethod' => $enrolment->enrol,
                'enrolname' => $enrolment->enrolname,
                'status' => $enrolment->status,
                'timestart' => $enrolment->timestart,
                'timeend' => $enrolment->timeend,
                'timecreated' => $enrolment->timecreated,
                'timemodified' => $enrolment->timemodified,
                'user' => [
                    'firstname' => $enrolment->firstname,
                    'lastname' => $enrolment->lastname,
                    'email' => $enrolment->email,
                ],
            ];
        }

        return $formattedenrolments;
    }

    /**
     * Retrieves a paginated list of users enrolled in a specific course.
     *
     * This is a custom implementation based on get_enrolled_users($context).
     * Supports filtering by search keyword, role, enrolment method, group, and sorting options.
     * Returns user data and metadata for pagination and display.
     *
     * @param int $courseid Course ID.
     * @param array $filterparam {
     *     Optional filter and pagination options.
     *
     *     int    $spage         Page number (default 0).
     *     int    $perpage       Records per page (default 0).
     *     string $search        Search keyword (optional).
     *     array  $roleids       Filter by role IDs (optional).
     *     string $enrolmethod   Enrolment instance ID (optional).
     *     string $groupid       Group ID (optional).
     *     string $sortby        Sort field (default 'timemodified').
     *     int    $sortdir       Sort direction: SORT_ASC or SORT_DESC (default SORT_DESC).
     *     int    $download      If set, disables pagination (default 0).
     *     bool   $returncount   If true, returns only the count (default false).
     * }
     * @return array|int {
     *     'data' => array List of enrolled user records,
     *     'meta' => array Pagination and summary metadata
     * } or int if $returncount is true.
     */
    public static function get_enrol_users($courseid, $filterparam = []) {
        global $CFG, $DB;

        // Get parameters.
        $pagenumber         = $filterparam['spage'] ?? 0;
        $perpage            = $filterparam['perpage'] ?? 0;
        $searchuser         = $filterparam['search'] ?? '';
        $roleids            = $filterparam['roleids'] ?? [];
        $enrolinstanceid    = $filterparam['enrolmethod'] ?? '';
        $groupid            = $filterparam['groupid'] ?? '';
        $sortby             = $filterparam['sortby'] ?? 'timemodified';
        $sortdir            = $filterparam['sortdir'] ?? SORT_DESC;
        $download           = $filterparam['download'] ?? 0;
        $returncount        = $filterparam['returncount'] ?? false;

        // Validate courseid.
        if (!$courseid) {
            return $returncount ? 0 : ['data' => [], 'meta' => [], 'message' => get_string('invalidcourseid', 'local_mchelpers')];
        }

        // Get course context.
        /** @var \context $context Course context instance. */
        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return $returncount ? 0 : ['data' => [], 'meta' => [], 'message' => get_string('invalidcourseid', 'local_mchelpers')];
        }

        // Pagination.
        if ($download) {
            $limitnum = 0;
            $limitfrom = 0;
        } else {
            $limitnum = ($perpage > 0) ? $perpage : 30;
            $limitfrom = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;
        }

        // Order by sorting.
        $usersortfields = ['id', 'firstname', 'lastname', 'email', 'timemodified'];
        if (in_array($sortby, $usersortfields)) {
            $sortby = 'u.' . $sortby;
        } else if ($sortby === 'enrolldate') {
            $sortby = 'u.timemodified';
        } else if ($sortby === 'lastcourseaccess') {
            $sortby = 'ula.timeaccess';
        } else {
            $sortby = 'u.timemodified';
        }
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';
        $orderby = "ORDER BY " . $sortby . " " . $sortdir;

        // SQL fragments.
        $selectfields = 'u.*, ula.timeaccess as lastcourseaccess';
        $sqlparams = [
            'guest_user_id' => 1,
            'user_deleted' => 1,
            'courseid' => $courseid,
        ];
        $wherecondition = [
            "u.id <> :guest_user_id",
            "u.deleted <> :user_deleted",
        ];
        $jointable = [];
        $jointable['user_lastaccess'] = "LEFT JOIN {user_lastaccess} ula ON ula.courseid = :courseid AND ula.userid = u.id";

        // Search by text.
        if ($searchuser !== '') {
            $sqlparams['search_username'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_firstname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_lastname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_email'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $wherecondition[] = '( ' . $DB->sql_like('u.username', ':search_username') . ' OR ' .
                $DB->sql_like('u.firstname', ':search_firstname') . ' OR ' .
                $DB->sql_like('u.lastname', ':search_lastname') . ' OR ' .
                $DB->sql_like('u.email', ':search_email') . ' )';
        }

        // Search by role ids.
        if (is_array($roleids) && count($roleids) > 0) {
            $jointable['role_assignments'] = "LEFT JOIN {role_assignments} ra ON ra.userid = u.id";
            $selectfields .= ', ra.roleid as roleid';
            $rolewherecondition = [];

            // Check if admin is present in roleids (-1 represents site admins).
            if (in_array(-1, $roleids)) {
                $adminids = explode(',', $CFG->siteadmins);
                if (count($adminids) > 0) {
                    [$insql, $inparams] = $DB->get_in_or_equal($adminids, SQL_PARAMS_NAMED, 'adminids');
                    $sqlparams = array_merge($sqlparams, $inparams);
                    $rolewherecondition[] = "u.id $insql";
                }
            }

            // Remove dummy role ids: 0 and -1 values.
            $roleids = array_filter($roleids, function ($value) {
                return $value !== -1 && $value !== 0;
            });

            // Now again if there are real roles user roles.
            if (count($roleids) > 0) {
                [$insql, $inparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleids');
                $sqlparams = array_merge($sqlparams, $inparams);
                $rolewherecondition[] = "ra.roleid $insql";
            }

            // Join the role condition with OR.
            if (count($rolewherecondition) > 0) {
                $wherecondition[] = "(" . implode(" OR ", $rolewherecondition) . ")";

                $jointable['context'] = "JOIN {context} ctx ON ra.contextid = ctx.id";
                $sqlparams['contextlevel'] = CONTEXT_COURSE;
                $wherecondition[] = 'ctx.contextlevel = :contextlevel';

                [$insql, $inparams] = $DB->get_in_or_equal($courseid, SQL_PARAMS_NAMED, 'courseid');
                $sqlparams = array_merge($sqlparams, $inparams);
                $wherecondition[] = "ctx.instanceid $insql";
            }
        }

        // Search by enrolment method.
        if ($enrolinstanceid && $enrolinstanceid !== 'all') {
            $jointable['enrol'] = "JOIN {enrol} e ON e.courseid = :ecourseid";
            $jointable['user_enrolments'] = "JOIN {user_enrolments} ue ON ue.enrolid = e.id AND ue.userid = u.id";

            $sqlparams['ecourseid'] = $courseid;
            $sqlparams['enrolinstanceid'] = $enrolinstanceid;
            $wherecondition[] = 'e.id = :enrolinstanceid';
        }

        // Apply table join.
        $joinapply = '';
        if (count($jointable) > 0) {
            $joinapply = implode(" ", $jointable);
        }

        // Apply where conditions with AND.
        $whereapply = '';
        if (count($wherecondition) > 0) {
            $whereapply = "WHERE " . implode(" AND ", $wherecondition);
        }

        // SQL fragments for enrolled users.
        $withcapability = '';
        $onlyactive = '';

        // This builds SQL for enrolled users (subquery).
        [$esql, $params] = get_enrolled_sql($context, $withcapability, $groupid, $onlyactive);
        $params = array_merge($sqlparams, $params);

        // Count total records.
        $sqlquery = "SELECT COUNT(DISTINCT u.id)
            FROM {user} u
            JOIN ($esql) je ON je.id = u.id " .
            $joinapply . " " .
            $whereapply;
        $totalrecords = $DB->count_records_sql($sqlquery, $params);

        // If only count is requested, return it now without executing the full data query.
        if ($returncount) {
            return $totalrecords;
        }

        // Final SQL to get enrolled users with filters, sorting and pagination.
        // Note: We use DISTINCT to avoid duplicates when joining with role_assignments.
        $sql = "SELECT DISTINCT $selectfields
            FROM {user} u
            JOIN ($esql) je ON je.id = u.id " .
            $joinapply . " " .
            $whereapply . " " .
            $orderby;

        // Execute query.
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

        // Create return value.
        $enrolusersinfo = [
            'data' => [],
            'meta' => [],
        ];

        foreach ($records as $record) {

            // Get user groups in the course.
            $groups = groups_get_all_groups($courseid, $record->id);

            $recordarray = [
                'id' => $record->id,
                'username' => $record->username,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'fullname' => fullname($record),
                'email' => $record->email,
                'profileimageurl' => user_service::get_user_profile_image($record),
                'profileurl' => mchelpers::get_moodle_url('/user/profile.php', ['id' => $record->id], true, true),
                'lastcourseaccess' => $record->lastcourseaccess ?: 0,
                'completion_progress' => course_service::get_course_completion_progress(get_course($courseid), $record->id),
                'groups' => $groups ? implode(", ", array_column($groups, 'name')) : '',
                'enrolments' => self::get_course_user_enrolments($courseid, $record->id),
                'courseroles' => role_service::get_roles($record->id, $context),
                'certificate' => course_service::get_course_mod_customcert($courseid, $record->id),
            ];

            // Format user data to return.
            $enrolusersinfo['data'][] = $recordarray;
        }

        // Meta information.
        $recordcount = $records ? count($records) : 0;
        $enrolusersinfo['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ($limitnum > 0) ? ceil($totalrecords / $limitnum) : 1,
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => $recordcount,
            'datafrom' => ($recordcount > 0) ? $limitfrom + 1 : 0,
            'datato' => ($recordcount > 0) ? $limitfrom + $recordcount : 0,
        ];

        return $enrolusersinfo;
    }

    /**
     * Returns the list of courses where the user is enrolled.
     *
     * @param int $userid User ID.
     * @param array $filterparam Filter parameters.
     *     - onlyactive (bool) Only active enrolments.
     *     - sortby (string) Sort field (fullname, shortname, startdate, timecreated, timemodified).
     *     - sortdir (int) Sort direction (SORT_ASC, SORT_DESC).
     *     - search (string) Search keyword in fullname/shortname.
     *     - page (int) Page number (0-indexed).
     *     - perpage (int) Number of records per page.
     * @param bool $count Return count only.
     * @return array|int List of enrolled courses or count.
     */
    public static function get_enrol_courses($userid, $filterparam = []) {
        global $DB;

        $usercourselist = [];

        // Filter parameters.
        $params = [];
        $onlyactive = $filterparam['onlyactive'] ?? false;
        $search = trim($filterparam['search'] ?? '');
        $sortby = $filterparam['sortby'] ?? 'fullname';
        $sortdir = ($filterparam['sortdir'] ?? SORT_ASC) == SORT_ASC ? 'ASC' : 'DESC';
        $pagenumber = (int) ($filterparam['page'] ?? 0);
        $perpage = (int) ($filterparam['perpage'] ?? 0);
        $returncount        = $filterparam['returncount'] ?? false;

        // Validate userid.
        if (empty($userid)) {
            return $returncount ? 0 : [];
        }

        // Whitelist sortable fields to prevent SQL injection.
        $allowedsortfields = ['fullname', 'shortname', 'startdate', 'timecreated', 'timemodified'];
        if (!in_array($sortby, $allowedsortfields)) {
            $sortby = 'fullname';
        }
        $orderby = "ORDER BY c.$sortby $sortdir";

        // Build subquery where clause for user enrolment conditions.
        $subwhere = [];
        $subwhere[] = "ue.userid = :userid";
        $params['userid'] = $userid;

        if ($onlyactive) {
            $subwhere[] = "ue.status = :active";
            $subwhere[] = "e.status = :enabled";
            $subwhere[] = "ue.timestart < :now1";
            $subwhere[] = "(ue.timeend = 0 OR ue.timeend > :now2)";
            $params['now1']    = $params['now2'] = di::get(clock::class)->time();
            $params['active']  = ENROL_USER_ACTIVE;
            $params['enabled'] = ENROL_INSTANCE_ENABLED;
        }

        $subwhereclause = "WHERE " . implode(" AND ", $subwhere);

        // Build main query where clause.
        $mainwhere = [];
        $mainwhere[] = "c.id <> :siteid";
        $params['siteid'] = SITEID;

        // Search by fullname or shortname.
        if ($search !== '') {
            $mainwhere[] = '(' . $DB->sql_like('c.fullname', ':search_fullname') .
                ' OR ' . $DB->sql_like('c.shortname', ':search_shortname') . ')';
            $params['search_fullname'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search_shortname'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $mainwhereclause = !empty($mainwhere) ? "WHERE " . implode(" AND ", $mainwhere) : "";

        // If only count is needed, return count.
        $countsql = "SELECT COUNT(c.id)
                FROM {course} c
                JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                 $subwhereclause
                   ) en ON (en.courseid = c.id)
                $mainwhereclause";
        $totalrecords = $DB->count_records_sql($countsql, $params);
        if ($returncount) {
            return $totalrecords;
        }

        // Final SQL to get enrolled courses with filters and sorting.
        $sql = "SELECT c.*
                FROM {course} c
                JOIN (SELECT DISTINCT e.courseid
                      FROM {enrol} e
                      JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
                 $subwhereclause
                   ) en ON (en.courseid = c.id)
                $mainwhereclause
          $orderby";

        // Pagination.
        $limitnum = 0;
        $limitfrom = 0;
        if ($perpage > 0) {
            $limitnum = $perpage;
            $limitfrom = $pagenumber * $perpage;
        }

        // Get courses with SQL to allow more flexible filtering and sorting.
        // $mycourses = enrol_get_all_users_courses($userid, false, '*', 'visible DESC, fullname ASC, sortorder ASC');
        $mycourses = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

        // Loop through each course to get more details.
        foreach ($mycourses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);

            // Get course category.
            $coursecategories = $DB->get_record('course_categories', ['id' => $course->category]);
            $categoryname = $coursecategories ? format_string($coursecategories->name) : '';

            // Get user last access time for the course.
            $course->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
                'courseid' => $course->id,
                'userid' => $userid,
            ]) ?: 0;

            // Get user groups in the course.
            $groups = groups_get_all_groups($course->id, $userid);

            // Arrange course data to return.
            $courseinfo = [
                'id' => $course->id,
                'category' => $course->category,
                'shortname' => $course->shortname,
                'fullname' => format_string($course->fullname),
                'categoryname' => $categoryname,
                'course_url' => mchelpers::get_moodle_url('/course/view.php', ['id' => $course->id], true, true),
                'course_image_url' => course_service::get_course_image($course),
                'category_url' => mchelpers::get_moodle_url('/course/index.php', ['categoryid' => $course->category], true, true),
                'summary' => course_service::get_course_formatted_summary($course),
                'format' => ['shortname' => $course->format, 'name' => get_string('pluginname', 'format_' . $course->format)],
                'visible' => $course->visible,
                'startdate' => $course->startdate,
                'enddate' => $course->enddate,
                'customfields' => course_service::get_course_customfields($course->id),
                'enrolinstances' => self::get_enrol_instances($course->id),
                'lastaccess' => $course->lastaccess,
                'completion_progress' => course_service::get_course_completion_progress($course, $userid),
                'groups' => $groups ? implode(", ", array_column($groups, 'name')) : '',
                'enrolments' => self::get_course_user_enrolments($course->id, $userid),
                'courseroles' => role_service::get_roles($userid, $context),
                'certificate' => course_service::get_course_mod_customcert($course->id, $userid),
            ];

            // Add course info to the list.
            $usercourselist[] = $courseinfo;
        }

        return [
            'data' => $usercourselist,
            'meta' => [
                'totalrecords' => $totalrecords,
                'page' => $pagenumber,
                'perpage' => $perpage,
                'totalpage' => ($limitnum > 0) ? ceil($totalrecords / $limitnum) : 1,
                'datadisplaycount' => ($mycourses) ? count($mycourses) : 0,
                'datafrom' => ($mycourses) ? $limitfrom + 1 : 0,
                'datato' => ($mycourses) ? count($mycourses) + $limitfrom : 0,
            ],
        ];
    }

    /**
     * Unenrolls all users from all courses (except front page course).
     *
     * Iterates through all courses and unenrolls all enrolled users
     * from manual enrolment instances.
     *
     * @return bool True on success, false on failure.
     */
    public static function unenroll_all_course_users() {
        global $DB;

        try {
            $query = 'SELECT * FROM {course} course WHERE course.id <> :frontpagecourse_id';
            $sql_params = [
                'frontpagecourse_id' => 1
            ];
            $courses = $DB->get_records_sql($query, $sql_params);

            foreach ($courses as $course) {
                $enrol = enrol_get_plugin('manual');
                $instance = new stdClass();
                $enrol_instances = enrol_get_instances($course->id, true);

                foreach ($enrol_instances as $course_enrol_instance) {
                    if ($course_enrol_instance->enrol === "manual") {
                        $instance = $course_enrol_instance;
                        break;
                    }
                }

                /** @var \context $course_context Course context instance. */
                $course_context = context_course::instance($course->id);
                $enrolled_users = get_enrolled_users($course_context);

                foreach ($enrolled_users as $user) {
                    $enrol->unenrol_user($instance, $user->id);
                }
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Get enrolment instances for a course.
     *
     * @param int $courseid Course ID.
     * @return array List of enrolment instances with method names and role info.
     */
    public static function get_enrol_instances($courseid) {
        $enrolinstances = enrol_get_instances((int) $courseid, false);

        foreach ($enrolinstances as $key => &$courseenrolinstance) {
            $courseenrolinstance->method_name = enrol_get_plugin($courseenrolinstance->enrol)->get_instance_name($courseenrolinstance);
            $courseenrolinstance->role = role_service::get_role($courseenrolinstance->roleid);
            $courseenrolinstance->status = \core_course\reportbuilder\local\formatters\enrolment::enrolment_status(
                $courseenrolinstance->status ?? 1
            );
        }

        return $enrolinstances;
    }

    /**
     * Get user course enrolment information.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID.
     * @return array User enrolment records.
     */
    public static function get_course_user_enrolments($courseid, $userid) {
        global $DB;

        $query = 'SELECT ue.id, ue.status as ue_status,
                         ue.timecreated, enrol.id as enrolid,
                         enrol.courseid, enrol.name, enrol.enrol,
                         enrol.status as enrol_status
                    FROM {user_enrolments} ue
                    LEFT JOIN {enrol} enrol ON ue.enrolid = enrol.id
                   WHERE enrol.courseid = :courseid AND ue.userid = :userid';

        $params = [
            'courseid' => $courseid,
            'userid' => $userid,
        ];

        $userenrolments = $DB->get_records_sql($query, $params);
        if ($userenrolments && is_array($userenrolments)) {
            foreach ($userenrolments as $key => &$enrolinstance) {
                $enrolinstance->method_name = enrol_get_plugin($enrolinstance->enrol)->get_instance_name($enrolinstance);
                $enrolinstance->status = \core_course\reportbuilder\local\formatters\enrolment::enrolment_status(
                    $enrolinstance->ue_status ? 1 : ($enrolinstance->enrol_status ? 2 : 0)
                );
            }
        }

        return $userenrolments;
    }

    /**
     * Check if a user is enrolled in a course.
     *
     * @param int $userid User ID.
     * @param int $courseid Course ID.
     * @param string $withcapability extra capability name
     * @param bool $onlyactive Check only active enrolments.
     * @return bool True if enrolled, false otherwise.
     */
    public static function is_enrolled($userid, $courseid, $withcapability = '', $onlyactive = false) {
        global $DB;
        /** @var \context $context Course context instance. */
        $context = context_course::instance($courseid, IGNORE_MISSING);
        return  is_enrolled($context, $userid, $withcapability, $onlyactive);
    }

    /**
     * Get enrolment count for a course.
     *
     * @param int $courseid Course ID.
     * @param bool $onlyactive Count only active enrolments.
     * @return int Number of enrolled users.
     */
    public static function get_enrolment_count($courseid, $onlyactive = false) {
        global $DB;

        $where = 'e.courseid = :courseid';
        $params = ['courseid' => $courseid];

        if ($onlyactive) {
            $where .= ' AND ue.status = :active AND e.status = :enabled';
            $params['active'] = ENROL_USER_ACTIVE;
            $params['enabled'] = ENROL_INSTANCE_ENABLED;
        }

        $sql = "SELECT COUNT(ue.id)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE $where";

        return (int) $DB->count_records_sql($sql, $params);
    }
}
