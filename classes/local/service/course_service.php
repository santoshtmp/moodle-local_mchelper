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
 * Course service class for local_mchelpers plugin.
 *
 * Provides service methods for course-related operations including
 * course information, custom fields, completion tracking, and enrolment.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\service;

use completion_info;
use core_completion\progress;
use core_course_list_element;
use course_modinfo;
use local_mchelpers\datarepository\coursemeta_datarepository;
use local_mchelpers\local\mchelpers;
use local_mchelpers\local\service\enrol_services;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Course service class for handling course-related data operations.
 */
class course_service {

    /**
     * Returns user progress percentage in a course.
     *
     * @param stdClass $course Course object.
     * @param int $enrolleduserid User ID of enrolled user.
     * @return int User course progress percentage (0-100).
     */
    public static function get_course_completion_progress($course, $enrolleduserid) {
        global $CFG;

        require_once("$CFG->libdir/completionlib.php");

        $completioninfo = new \completion_info($course);
        if ($completioninfo->is_enabled()) {
            $percentage = progress::get_course_progress_percentage($course, $enrolleduserid);
            if (!is_null($percentage)) {
                return (int) $percentage;
            }
        }
        return 0;
    }

    /**
     * Get custom certificate module information for a course.
     *
     * @param int $courseid Course ID.
     * @param int $userid User ID to check issued certificates (optional).
     * @return array {
     *     mod_id: int, customcert_id: int, certificate_url: string, certificate_url_download: string,
     *     certificate_issues: bool, certificate_issues_date: int, certificate_issues_code: string
     * }
     */
    public static function get_course_mod_customcert($courseid, $userid = '') {
        global $DB;
        $customcertdata = [
            'mod_id' => 0,
            'customcert_id' => 0,
            'certificate_url' => '',
            'certificate_url_download' => '',
            'certificate_issues' => false,
            'certificate_issues_date' => 0,
            'certificate_issues_code' => '',
        ];
        $query = 'SELECT course_modules.id AS id, course_modules.instance AS instance
            FROM {course_modules} course_modules
            JOIN {modules} modules ON modules.id = course_modules.module
            WHERE course_modules.course = :courseid AND modules.name = :modules_name
                AND course_modules.visible = :course_modules_visible AND course_modules.deletioninprogress = :deletioninprogress
            Order By course_modules.id DESC
            LIMIT 1
            ';
        $params = [
            'courseid' => $courseid,
            'modules_name' => 'customcert',
            'course_modules_visible' => 1,
            'deletioninprogress' => 0,
        ];
        $modcustomcert = $DB->get_record_sql($query, $params);
        if ($modcustomcert) {
            $customcertdata['mod_id'] = $modcustomcert->id;
            $customcertdata['customcert_id'] = $modcustomcert->instance;
            $customcertdata['certificate_url'] = mchelpers::get_moodle_url('/mod/customcert/view.php', ['id' => $modcustomcert->id], true, true);
            $customcertdata['certificate_url_download'] = mchelpers::get_moodle_url(
                '/mod/customcert/view.php',
                [
                    'id' => $modcustomcert->id,
                    'downloadown' => 1,
                ],
                true,
                true
            );
            if ($userid && $modcustomcert->instance) {
                $customcertissues = $DB->get_record('customcert_issues', [
                    'userid' => $userid,
                    'customcertid' => $modcustomcert->instance,
                ]);
                if ($customcertissues) {
                    $customcertdata['certificate_issues'] = true;
                    $customcertdata['certificate_issues_date'] = $customcertissues->timecreated;
                    $customcertdata['certificate_issues_code'] = $customcertissues->code;
                }
            }
        }
        return $customcertdata;
    }

    /**
     * Issue a certificate to a user.
     *
     * @param int $userid User ID
     * @param int $customcertid Custom certificate ID
     * @param int $issuedate Issue timestamp (0 for current time)
     * @return array Certificate issue result
     */
    public static function issue_certificate($userid, $customcertid, $issuedate = 0) {
        global $DB;

        $result = [
            'success' => false,
            'message' => '',
            'code' => '',
        ];

        // Validate inputs.
        if (empty($userid) || empty($customcertid)) {
            $result['message'] = get_string('invaliduserid', 'local_mchelpers');
            return $result;
        }

        // Check if certificate already issued.
        $existing = $DB->get_record('customcert_issues', [
            'userid' => $userid,
            'customcertid' => $customcertid,
        ]);

        if ($existing) {
            $result['success'] = true;
            $result['message'] = get_string('certificatealreadyissued', 'local_mchelpers');
            $result['code'] = $existing->code;
            return $result;
        }

        // Create certificate issue record.
        $issue = new stdClass();
        $issue->userid = $userid;
        $issue->customcertid = $customcertid;
        $issue->code = \mod_customcert\certificate::generate_code();
        $issue->emailed = 0;
        $issue->timecreated = $issuedate ?: time();

        $DB->insert_record('customcert_issues', $issue);

        $result['success'] = true;
        $result['message'] = get_string('certificateissued', 'local_mchelpers');
        $result['code'] = $issue->code;

        return $result;
    }

    /**
     * Returns formatted summary of a course with embedded files resolved.
     *
     * @param stdClass $course Course record.
     * @return string Formatted summary HTML.
     */
    public static function get_course_formatted_summary($course) {
        global $CFG;
        if (!$course->summary) {
            return '';
        }
        require_once($CFG->libdir . '/filelib.php');
        $options = null;
        $context = \context_course::instance($course->id);
        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        $summary = format_text($summary, $course->summaryformat);

        return $summary;
    }

    /**
     * Returns the first summary image of a course.
     *
     * @param stdClass $course Course record.
     * @param bool $defaultimageonnull Return default image if no image found.
     * @return string URL of course image or empty string.
     */
    public static function get_course_image($course, $defaultimageonnull = false) {
        global $CFG, $OUTPUT;
        $course = new core_course_list_element($course);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $url = moodle_url::make_file_url(
                    "$CFG->wwwroot/pluginfile.php",
                    '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                        $file->get_filearea() . $file->get_filepath() . $file->get_filename(),
                    !$file->is_valid_image()
                );

                return $url->out();
            }
        }
        if ($defaultimageonnull) {
            return $OUTPUT->get_generated_image_for_id($course->id);
        }
        return '';
    }

    /**
     * Validate and return a course object.
     *
     * @param mixed $courseref Course object or course ID.
     * @return stdClass Moodle course object.
     */
    protected function get_course_record($courseref = '') {
        global $COURSE, $DB;

        // If course object is provided, extract ID.
        if (is_object($courseref)) {
            $courseref = $courseref->id;
        }

        // Convert numeric string to int.
        if (is_numeric($courseref)) {
            $courseref = (int)$courseref;
        }

        // If valid course ID provided, get course.
        if (is_int($courseref) && $courseref > 0 && $DB->record_exists('course', ['id' => $courseref])) {
            return get_course($courseref);
        }

        // Fallback to global COURSE.
        return $COURSE;
    }

    /**
     * Get completion progress of a specific section in a course.
     *
     * @param mixed $courseref Course ID or object.
     * @param stdClass $section Section record.
     * @return array|string|null Progress context (percent) or null if not applicable.
     */
    public function get_section_progress($courseref, $section) {
        global $USER, $COURSE;
        $course = $this->get_course_record($courseref);
        /** @var \context $context Course context instance. */
        $context = \context_course::instance($COURSE->id);
        $userstudent = is_enrolled($context, $USER, 'moodle/course:isincompletionreports');
        if (!$userstudent || isguestuser() || empty($course)) {
            return;
        }

        $modinfo = get_fast_modinfo($course);
        if (empty($modinfo->sections[$section->section])) {
            return '';
        }

        // Generate array with count of activities in this section.
        $sectionmods = [];
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();
        $completioninfo = new completion_info($course);
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->modname === 'label') {
                // Labels are special (not interesting for students)!
                continue;
            }

            if ($thismod->uservisible) {
                if (isset($sectionmods[$thismod->modname])) {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modplural;
                    $sectionmods[$thismod->modname]['count']++;
                } else {
                    $sectionmods[$thismod->modname]['name'] = $thismod->modfullname;
                    $sectionmods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) !== COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if (
                        $completiondata->completionstate === COMPLETION_COMPLETE ||
                        $completiondata->completionstate === COMPLETION_COMPLETE_PASS
                    ) {
                        $complete++;
                    }
                }
            }
        }

        if (empty($sectionmods)) {
            // No activities in section.
            return '';
        }
        // Output section completion data.
        $templatecontext = [];
        if ($total > 0) {
            $completion = new stdClass();
            $completion->complete = $complete;
            $completion->total = $total;

            $percent = 0;
            if ($complete > 0) {
                $percent = (int) (($complete / $total) * 100);
            }

            $templatecontext['percent'] = $percent;
        }

        return $templatecontext;
    }

    /**
     * Get course custom fields metadata with flexible formats.
     *
     * @param int $courseid Course ID.
     * @param string $returnformat Format: "raw", "shortname_value", "key_array".
     * @return array Custom field metadata.
     */
    public static function get_course_customfields($courseid, $returnformat = 'raw') {
        $handler = \core_course\customfield\course_handler::create();
        $customfields = $handler->export_instance_data($courseid);
        $customfieldsdata = [];

        foreach ($customfields as $data) {
            if ($returnformat == 'shortname_value') {
                $customfieldsdata[$data->get_shortname()] = $data->get_value();
            } else if ($returnformat == 'key_array') {
                $customfieldsdata[$data->get_shortname()] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname(),
                ];
            } else {
                $customfieldsdata[] = [
                    'type' => $data->get_type(),
                    'value' => $data->get_value(),
                    'valueraw' => $data->get_data_controller()->get_value(),
                    'name' => $data->get_name(),
                    'shortname' => $data->get_shortname(),
                ];
            }
        }
        return $customfieldsdata;
    }

    /**
     * Get group mode names.
     * @return array Group mode choices.
     */
    public static function get_groupmode_name() {
        $choices = [];
        $choices[NOGROUPS] = get_string('groupsnone', 'group');
        $choices[SEPARATEGROUPS] = get_string('groupsseparate', 'group');
        $choices[VISIBLEGROUPS] = get_string('groupsvisible', 'group');
        return $choices;
    }

    /**
     * Get compact card info for a course (summary, image, enrolment).
     *
     * @param int $courseid Course ID.
     * @param bool $defaultvalues Whether to include default images if missing.
     * @return array|false Course card info or false if course does not exist.
     */
    public static function course_card_info($courseid, $defaultvalues = false) {
        global $DB, $OUTPUT;
        $courseinfo = [];

        if ($DB->record_exists('course', ['id' => $courseid])) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            $coursecategories = $DB->get_record('course_categories', ['id' => $course->category]);

            // ... get course enrolment plugin instance.
            $enrollmentmethods = [];
            $index = 0;
            $enrolinstances = enrol_get_instances((int)$course->id, true);
            foreach ($enrolinstances as $key => $courseenrolinstance) {
                $enrollmentmethods[$index]['enrol'] = $courseenrolinstance->enrol;
                $enrollmentmethods[$index]['name'] = ($courseenrolinstance->name) ?: $courseenrolinstance->enrol;
                $enrollmentmethods[$index]['cost'] = $courseenrolinstance->cost;
                $enrollmentmethods[$index]['currency'] = $courseenrolinstance->currency;
                $enrollmentmethods[$index]['roleid'] = $courseenrolinstance->roleid;
                $enrollmentmethods[$index]['role_name'] = '';
                $index++;
            }

            $rep = ["</p>", "<br>", "</div>"];
            $summary = str_replace($rep, " ", $course->summary);
            $summary = format_string($summary);
            if (strlen($summary) > 200) {
                $summary = substr($summary, 0, 200);
                $summary .= '...';
            }

            // Manage return data.
            $courseinfo['id'] = $course->id;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['datatype'] = $course->category;
            $courseinfo['shortname'] = format_string($course->shortname);
            $courseinfo['fullname'] = format_string($course->fullname);
            $courseinfo['categoryname'] = format_string($coursecategories->name);
            $courseinfo['course_link'] = mchelpers::get_moodle_url('/course/view.php', ['id' => $course->id], true, true);
            $courseinfo['category_link'] = mchelpers::get_moodle_url('/course/index.php', ['categoryid' => $course->category], true, true);
            $courseinfo['enrollment_link'] = mchelpers::get_moodle_url('/enrol/index.php', ['id' => $course->id], true, true);
            $courseinfo['thumbnail_link'] = self::get_course_image($course, $defaultvalues);
            $courseinfo['summary'] = self::get_course_formatted_summary($course);
            $courseinfo['short_summary'] = $summary;
            $courseinfo['arrow-right'] = $OUTPUT->image_url('icons/arrow-right', 'theme_yipl');
            $courseinfo['enrollment_methods'] = $enrollmentmethods;

            return $courseinfo;
        }
        return false;
    }

    /**
     * Get detailed information for a course.
     *
     * @param int $courseid Course ID.
     * @param array $filterparam Optional filter parameters.
     * @return array Detailed course info array or empty array if not found.
     */
    public static function get_course_info($courseid, $filterparam = []) {
        global $DB;
        $courseInfo = [];

        if ($DB->record_exists('course', ['id' => $courseid])) {
            $course = get_course($courseid);

            // Get course category.
            $coursecategories = $DB->get_record('course_categories', ['id' => $course->category]);
            $course->categoryname = $coursecategories ? format_string($coursecategories->name) : '';

            // Build course info using the common builder method.
            $courseInfo = self::build_course_info(
                $course,
                $filterparam
            );
        }
        return $courseInfo;
    }

    /**
     * Get all courses information with filters and pagination.
     *
     * @param array $filterparam {
     *     Optional filter and pagination parameters.
     *
     *     int    $spage              Page number (0-indexed, default 0).
     *     int    $perpage            Number of records per page (default 50).
     *     int    $id                 Specific course ID filter (default 0).
     *     array  $courseids          Filter by multiple course IDs (default []).
     *     string $search             Search keyword in fullname/shortname (default '').
     *     array  $categoryids        Category IDs to filter by (default []).
     *     string $courseformat       Course format filter (default '').
     *     string $coursevisibility   Course visibility: 'show', 'hide', or 'all' (default '').
     *     string $enrolmethod        Enrolment method filter (default '').
     *     int    $createdfrom        Created from timestamp (default 0).
     *     int    $createdto          Created to timestamp (default 0).
     *     int    $startdatefrom      Start date from timestamp (default 0).
     *     int    $startdateto        Start date to timestamp (default 0).
     *     string $sortby             Sort field (default 'timemodified').
     *     int    $sortdir            Sort direction: SORT_ASC or SORT_DESC (default SORT_DESC).
     *     int    $download           If set, disables pagination (default 0).
     *     bool   $returncount        If true, return only total count (default false).
     *     bool   $addparticipantcount Whether to include participant count (default true).
     *     mixed  $fields             Fields to return: '*' for all, array of field names, or comma-separated string (default '*').
     * }
     * @return array {
     *     'data' => array List of course info arrays.
     *     'meta' => array Pagination meta data.
     * }
     */
    public static function get_all_course_info($filterparam = []) {
        global $DB;
        // Get filterparam.
        $pagenumber         = (int) ($filterparam['spage'] ?? 0);
        $perpage            = (int) ($filterparam['perpage'] ?? 50);
        $fieldsparam    = $filterparam['fields'] ?? '*';
        $searchcourse       = trim($filterparam['search'] ?? '');
        $courseids        = $filterparam['courseids'] ?? [];
        $categoryids        = $filterparam['categoryids'] ?? [];
        $courseformat       = $filterparam['courseformat'] ?? '';
        $coursevisibility   = $filterparam['coursevisibility'] ?? '';
        $enrolmethod        = $filterparam['enrolmethod'] ?? '';
        $createdfrom        = (int) ($filterparam['createdfrom'] ?? 0);
        $createdto          = (int) ($filterparam['createdto'] ?? 0);
        $startdatefrom      = (int) ($filterparam['startdatefrom'] ?? 0);
        $startdateto        = (int) ($filterparam['startdateto'] ?? 0);
        $sortby             = $filterparam['sortby'] ?? 'timemodified';
        $sortdir            = $filterparam['sortdir'] ?? SORT_DESC;
        $download           = $filterparam['download'] ?? 0;
        $returncount    = $filterparam['returncount'] ?? false;

        $timestamp = (bool) ($filterparam['timestamp'] ?? true);
        $defaultvalues = (bool) ($filterparam['defaultvalues'] ?? false);
        $addparticipantcount = (bool) ($filterparam['addparticipantcount'] ?? false);
        $addactivitiescount = (bool) ($filterparam['addactivitiescount'] ?? false);
        $addcourseaccessusercount = (bool) ($filterparam['addcourseaccessusercount'] ?? false);
        $addenrolinstances = (bool) ($filterparam['addenrolinstances'] ?? false);
        $addcoursegroups = (bool) ($filterparam['addcoursegroups'] ?? false);
        $addcustomnamevalueformat = (bool) ($filterparam['addcustomnamevalueformat'] ?? false);

        // Pagination.
        if ($download) {
            $limitnum = $limitfrom = 0;
        } else {
            $limitnum   = ($perpage > 0) ? $perpage : 50;
            $limitfrom  = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;
        }

        // SQL fragments.
        $jointable = [];
        $sqlparams = ['frontpagecourseid' => 1];
        $wherecondition = ["c.id <> :frontpagecourseid"];

        // Search by text.
        if ($searchcourse) {
            $sqlparams['search_fullname'] = "%" . $DB->sql_like_escape($searchcourse) . "%";
            $sqlparams['search_shortname'] = "%" . $DB->sql_like_escape($searchcourse) . "%";
            $wherecondition[] = '( ' .
                $DB->sql_like('c.fullname', ':search_fullname') .
                ' OR ' .
                $DB->sql_like('c.shortname', ':search_shortname') .
                ' )';
        }

        // Search by multiple course ids.
        if (is_array($courseids) && count($courseids) > 0) {
            [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseids');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "c.id $insql";
        }

        // Search by category id.
        if (is_array($categoryids) && count($categoryids) > 0) {
            [$insql, $inparams] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'categoryid');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "c.category $insql";
        }
        // Search by course format.
        if ($courseformat && $courseformat !== 'all') {
            $sqlparams['courseformat'] = $courseformat;
            $wherecondition[] = 'c.format = :courseformat';
        }
        // Search by course visibility.
        if ($coursevisibility && $coursevisibility !== 'all') {
            $coursevisibility = ($coursevisibility === 'show') ? 1 : 0;
            $sqlparams['coursevisibility'] = $coursevisibility;
            $wherecondition[] = 'c.visible = :coursevisibility';
        }
        // Search by enrolment method.
        if ($enrolmethod && $enrolmethod != 'all') {
            $sqlparams['enrolmethod'] = $enrolmethod;
            $wherecondition[] = 'e.enrol = :enrolmethod';
        }
        // Search by created date from.
        if ($createdfrom) {
            $sqlparams['createdfrom'] = $createdfrom;
            $wherecondition[] = 'c.timecreated >= :createdfrom';
        }
        // Search by created date to.
        if ($createdto) {
            $sqlparams['createdto'] = $createdto + 24 * 3600;
            $wherecondition[] = 'c.timecreated <= :createdto';
        }
        // Search by start date from.
        if ($startdatefrom) {
            $sqlparams['startdatefrom'] = $startdatefrom;
            $wherecondition[] = 'c.startdate >= :startdatefrom';
        }
        // Search by start date to.
        if ($startdateto) {
            $sqlparams['startdateto'] = $startdateto + 24 * 3600;
            $wherecondition[] = 'c.startdate <= :startdateto';
        }

        // Apply table join.
        $joinapply = '';
        $jointable['course_categories'] = "JOIN {course_categories} cc ON cc.id = c.category";

        $jointable['enrol'] = "LEFT JOIN {enrol} e ON e.courseid = c.id AND e.status = :enrolstatus";
        $jointable['user_enrolments'] = "LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id";


        if (count($jointable) > 0) {
            $joinapply = implode(" ", $jointable) . " ";
        }
        $sqlparams['enrolstatus'] = ENROL_INSTANCE_ENABLED;

        // Apply where conditions with AND.
        $whereapply = '';
        if (count($wherecondition) > 0) {
            $whereapply = "WHERE " . implode(" AND ", $wherecondition);
        }

        // Order by sorting.
        $essentialfields = ['id', 'fullname', 'shortname', 'sortorder', 'startdate', 'timecreated', 'timemodified'];
        if (in_array($sortby, $essentialfields)) {
            $sortby = 'c.' . $sortby;
        } else if ($sortby === 'coursename') {
            $sortby = 'c.fullname';
        } else if ($sortby === 'category') {
            $sortby = 'cc.name';
        } else if ($sortby === 'participants') {
            $sortby = 'participants';
        } else {
            $sortby = 'c.timemodified';
        }
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';
        $orderby = "ORDER BY " . $sortby . " " . $sortdir;


        // Query select fields.
        $fields = [];
        $selectall = false;

        // Handle fields parameter: '*' for all, array, or comma-separated string.
        if ($fieldsparam === '*' || $fieldsparam === '*') {
            $selectall = true;
        } else if (is_string($fieldsparam)) {
            $fields = explode(',', $fieldsparam);
            $fields = array_map('trim', $fields);
            $fields = array_filter($fields);
            if (in_array('*', $fields)) {
                $selectall = true;
            }
        } else if (is_array($fieldsparam)) {
            $fields = $fieldsparam;
            if (in_array('*', $fields)) {
                $selectall = true;
            }
        } else {
            $selectall = true;
        }

        // Build SELECT fields clause
        if ($selectall) {
            $selectfields = 'c.*';
        } else {
            // Ensure essential fields are included
            $fields = array_unique(array_merge($essentialfields, $fields));
            $selectfields = 'c.' . implode(', c.', $fields);
        }


        // Add enrolledcourses count if requested.
        if ($addparticipantcount) {
            $selectfields .= ', COUNT(DISTINCT ue.userid) AS participants';
        }

        // Build GROUP BY clause (required when using aggregate functions).
        $groupby = '';
        if ($addparticipantcount) {
            // When counting, group by all non-aggregated selected fields.
            if ($selectall) {
                // When selecting c.*, group by primary key only (Moodle handles this).
                $groupby = ' GROUP BY c.id ';
            } else {
                $groupbyfields = array_map(function ($field) {
                    return 'c.' . $field;
                }, $fields);
                $groupby = ' GROUP BY ' . implode(', ', $groupbyfields) . ' ';
            }
        }

        // Count total records.
        $sqlcount = 'SELECT COUNT(DISTINCT c.id) FROM {course} c ' .
            $joinapply .
            $whereapply;
        $totalrecords = $DB->count_records_sql($sqlcount, $sqlparams);
        // If only count is requested, return it now without executing the full data query.
        if ($returncount) {
            return $totalrecords;
        }

        // Final SQL query and execute.
        $sqlquery = "SELECT " . $selectfields .
            " FROM {course} c " .
            $joinapply .
            $whereapply .
            $groupby .
            $orderby;
        $records = $DB->get_records_sql($sqlquery, $sqlparams, $limitfrom, $limitnum);

        // Create return value.
        $allcoursesinfo = [];
        foreach ($records as $record) {
            $allcoursesinfo['data'][] = self::build_course_info(
                $record,
                [
                    'timestamp' => $timestamp,
                    'defaultvalues' => $defaultvalues,
                    'addparticipantcount' => $addparticipantcount,
                    'addactivitiescount' => $addactivitiescount,
                    'addcourseaccessusercount' => $addcourseaccessusercount,
                    'addenrolinstances' => $addenrolinstances,
                    'addcoursegroups' => $addcoursegroups,
                    'addcustomnamevalueformat' => $addcustomnamevalueformat,
                ]
            );
        }

        // Meta information.
        $recordcount = ($records) ? count($records) : 0;
        $allcoursesinfo['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ($limitnum > 0) ? ceil($totalrecords / $limitnum) : 1,
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => $recordcount,
            'datafrom' => ($recordcount > 0) ? $limitfrom + 1 : 0,
            'datato' => ($recordcount > 0) ? $limitfrom + $recordcount : 0,
        ];

        return $allcoursesinfo;
    }


    public static function build_course_info($course, $filterparam = []) {
        global $CFG;

        // Get parameters.
        $timestamp = (bool) ($filterparam['timestamp'] ?? true);
        $defaultvalues = (bool) ($filterparam['defaultvalues'] ?? false);
        $addparticipantcount = (bool) ($filterparam['addparticipantcount'] ?? false);
        $addactivitiescount = (bool) ($filterparam['addactivitiescount'] ?? false);
        $addcourseaccessusercount = (bool) ($filterparam['addcourseaccessusercount'] ?? false);
        $addenrolinstances = (bool) ($filterparam['addenrolinstances'] ?? false);
        $addcoursegroups = (bool) ($filterparam['addcoursegroups'] ?? false);
        $addcustomnamevalueformat = (bool) ($filterparam['addcustomnamevalueformat'] ?? false);

        /** @var \context $context Course context instance. */
        $context = \context_course::instance($course->id, IGNORE_MISSING);

        // Course custom field data.
        try {
            $courseformatoptions = course_get_format($course)->get_format_options();
            if (array_key_exists('numsections', $courseformatoptions)) {
                // For backward-compatibility
                $numsections = $courseformatoptions['numsections'];
            }
            $numsections = course_get_format($course)->get_last_section_number();
            // $numsections = (int)$DB->get_field_sql(
            //     'SELECT max(section) from {course_sections} WHERE course = ?',
            //     [$course->id]
            // );

        } catch (\Throwable $th) {
            $numsections = get_config('moodlecourse ')->numsections;
        }

        // Arrange course data to return.
        $courseInfo = [
            'id' => $course->id,
            'shortname' => $course->shortname,
            'fullname' => format_string($course->fullname),
            'category' => $course->category,
            'categoryname' => format_string($course->categoryname ?? ''),
            'course_url' => mchelpers::get_moodle_url('/course/view.php', ['id' => $course->id], true, true),
            'course_image_url' => self::get_course_image($course, $defaultvalues),
            'category_url' => mchelpers::get_moodle_url('/course/index.php', ['categoryid' => $course->category], true, true),
            'enrollment_url' => mchelpers::get_moodle_url('/enrol/index.php', ['id' => $course->id], true, true),
            'participant_url' => mchelpers::get_moodle_url('/user/index.php', ['id' => $course->id], true, true),
            'summary' => self::get_course_formatted_summary($course),
            'format' => ['shortname' => $course->format, 'name' => get_string('pluginname', 'format_' . $course->format)],
            'sortorder' => $course->sortorder,
            'visible' => $course->visible,
            'enablecompletion' => $course->enablecompletion,
            'newsitems' => $course->newsitems,
            'maxbytes' =>  get_max_upload_sizes($CFG->maxbytes, 0, 0, $course->maxbytes)[$course->maxbytes] ?? 0,
            'startdate' => ($timestamp) ? $course->startdate : mchelpers::format_time($course->startdate),
            'enddate' => ($timestamp) ? $course->enddate : mchelpers::format_time($course->enddate),
            'timecreated' => ($timestamp) ? $course->timecreated : mchelpers::format_time($course->timecreated),
            'timemodified' => ($timestamp) ? $course->timemodified : mchelpers::format_time($course->timemodified),
            'customfields' => self::get_course_customfields($course->id),
            'groupmode' => self::get_groupmode_name()[$course->groupmode],
            'numsections' => $numsections,
            'course_created_by' => coursemeta_datarepository::get($course->id, 'course_created_by') ?: 0,
            'course_updated_by' => coursemeta_datarepository::get($course->id, 'course_updated_by') ?: 0,
        ];

        // Add custom fields in shortname => value format.
        if ($addcustomnamevalueformat) {
            $extrametadata = self::get_course_customfields($course->id, 'shortname_value');
            $courseInfo = [...$courseInfo, ...$extrametadata];
        }

        // Add course groups.
        if ($addcoursegroups) {
            $groups = groups_get_all_groups($course->id);
            if (!empty($groups)) {
                foreach ($groups as &$group) {
                    $group->name = format_string($group->name);
                    $group->description =  format_text($group->description, $group->descriptionformat);
                }
            }
            $courseInfo['groups'] = implode(", ", array_column($groups, 'name'));
        }

        // Add enrolment instances.
        if ($addenrolinstances) {
            $courseInfo['enrolinstances'] = enrol_services::get_enrol_instances($course->id);
        }

        // Add participant count.
        if ($addparticipantcount) {
            $courseInfo['participantcount'] = $course->participants ?? count_enrolled_users($context);
        }

        // Add course access user count.
        if ($addcourseaccessusercount) {
            $courseInfo['courseaccessusercount'] = user_service::count_users_with_course_access($course->id);
        }

        // Add activities count.
        if ($addactivitiescount) {
            $courseInfo['activitiescount'] = count(course_modinfo::get_array_of_activities($course, true));
        }

        // Return final info.
        return $courseInfo;
    }
}
