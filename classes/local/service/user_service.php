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
 * User service class for local_mchelpers plugin.
 *
 * Provides service methods for user-related operations including
 * user information, enrolments, roles, and profile data.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\service;

use core\output\user_picture;
use core_tag_tag;
use local_mchelpers\local\mchelpers;
use local_mchelpers\local\service\enrol_services;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * User service class for handling user-related data operations.
 */
class user_service {

    /**
     * Returns the user profile image or its URL.
     *
     * @param stdClass|int $user User object or user ID.
     * @param bool $imageurl Whether to return the image URL (true) or rendered HTML (false).
     * @param string $size Image size: 'f1' or 'f2' (default 'f1').
     * @return string User profile image URL or HTML markup.
     */
    public static function get_user_profile_image($user, $imageurl = true, $size = 'f1') {
        global $PAGE, $OUTPUT, $DB;
        if (!is_object($user)) {
            $user = $DB->get_record('user', ['id' => $user]);
        }
        if ($imageurl) {
            $userpicture = new user_picture($user);
            if ($size === 'f1') {
                $userpicture->size = 1; // Size f1.
            } else if ($size === 'f2') {
                $userpicture->size = 0; // Size f2.
            } else {
                $userpicture->size = 1; // Default to size f1.
            }
            $profileimageurl = $userpicture->get_url($PAGE)->out(false);
            return $profileimageurl;
        }
        return $OUTPUT->user_picture($user, ['size' => 35, 'link' => false, 'alttext' => false]);
    }

    /**
     * Returns user description with formatted text and file URLs.
     *
     * @param stdClass $user User object.
     * @return string User description (HTML).
     */
    public static function get_user_description($user) {
        global $CFG;

        $usercontext = \context_user::instance($user->id, MUST_EXIST);
        require_once("$CFG->libdir/filelib.php");
        $description = file_rewrite_pluginfile_urls(
            $user->description,
            'pluginfile.php',
            $usercontext->id,
            'user',
            'profile',
            null
        );
        $description = format_text($description, $user->descriptionformat);

        // [$description, $descriptionformat] =
        //     \core_external\util::format_text(
        //         $user->description,
        //         $user->descriptionformat,
        //         $usercontext,
        //         'user',
        //         'profile',
        //         null
        //     );
        return $description;
    }

    /**
     * Returns user custom profile fields with values.
     *
     * @param stdClass $user User object.
     * @return array List of custom fields with details.
     */
    public static function get_user_customofields($user) {
        global $CFG;
        require_once($CFG->dirroot . "/user/profile/lib.php"); // Custom field library.
        $categories = profile_get_user_fields_with_data_by_category($user->id);
        $usercustomfields = [];
        foreach ($categories as $categoryid => $fields) {
            foreach ($fields as $formfield) {
                if (!empty($formfield->data)) {
                    $usercustomfields[] = [
                        'name' => $formfield->field->name,
                        'value' => $formfield->data,
                        'displayvalue' => $formfield->display_data(),
                        'type' => $formfield->field->datatype,
                        'shortname' => $formfield->field->shortname,
                        'categoryname' => $formfield->get_category_name(),
                    ];
                }
            }
        }
        return $usercustomfields;
    }

    /**
     * Count users who have accessed a specific course.
     *
     * @param int $courseid Course ID.
     * @return int Number of users who have accessed the course.
     */
    public static function count_users_with_course_access($courseid) {
        global $DB;

        $countcourseaccessusers = $DB->count_records_sql(
            "
            SELECT COUNT(1)
            FROM {user_lastaccess} ula
            JOIN {user_enrolments} ue ON ue.userid = ula.userid
            JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ?
            WHERE ula.courseid = ? AND ula.timeaccess > 0
            ",
            [$courseid, $courseid]
        );
        return $countcourseaccessusers;
    }

    /**
     * Returns detailed user information.
     *
     * @param int $userid User ID.
     * @param array $filterparam Optional filter parameters for formatting.
     * @return array|false User information array or false if not found.
     */
    public static function get_user_info($userid, $filterparam = []) {
        global $DB;

        if ($DB->record_exists('user', ['id' => $userid])) {
            $user = $DB->get_record('user', ['id' => $userid]);
            return self::build_user_info($user, $filterparam);
        }
        return false;
    }


    /**
     * Returns all user information based on filters and pagination.
     *
     * @param array $filterparam {
     *  Optional filter and pagination options.
     *
     *  int    $spage                  Page number (default 0).
     *  int    $perpage                Records per page (default 50).
     *  array  $userids                Filter by multiple user IDs (default []).
     *  string $search                 Search keyword (default '').
     *  string $suspended              Filter by suspended status: 'all', 'yes', 'no' (default '').
     *  string $confirmed              Filter by confirmed status: 'all', 'yes', 'no' (default '').
     *  array  $roleids                Filter by role IDs (default []).
     *  array  $courseids              Filter by course IDs (default []).
     *  string $sortby                 Sort field (default 'timemodified').
     *  int    $sortdir                Sort direction: SORT_ASC or SORT_DESC (default SORT_DESC).
     *  int    $download               If set, disables pagination (default 0).
     *  bool   $returncount            If true, return only total count (default false).
     *  bool   $countenrolledcourses   Whether to include enrolled courses count (default false).
     *  bool   $timestamp              Whether to return raw timestamps (true) or formatted dates (false).
     *  bool   $addroles               Whether to include user roles (default false).
     *  bool   $addintereststags       Whether to include user interest tags (default false).
     *  bool   $addpreferences         Whether to include user preferences (default false).
     *  mixed  $fields                 Fields to return: '*' for all, array of field names, or comma-separated string (default '*').
     * }
     * @return array List of user information records with metadata.
     */
    public static function get_all_user_info($filterparam = []) {

        global $CFG, $DB;
        // Get parameters.
        $pagenumber     = $filterparam['spage'] ?? 0;
        $perpage        = $filterparam['perpage'] ?? 50;
        $userids        = $filterparam['userids'] ?? [];
        $searchuser     = $filterparam['search'] ?? '';
        $fieldsparam    = $filterparam['fields'] ?? '*';
        $suspended      = $filterparam['suspended'] ?? '';
        $confirmed      = $filterparam['confirmed'] ?? '';
        $roleids        = $filterparam['roleids'] ?? [];
        $courseids      = $filterparam['courseids'] ?? [];
        $sortby         = $filterparam['sortby'] ?? 'timemodified';
        $sortdir        = $filterparam['sortdir'] ?? SORT_DESC;
        $download       = $filterparam['download'] ?? 0;
        $returncount    = $filterparam['returncount'] ?? false;

        $timestamp      = (bool) ($filterparam['timestamp'] ?? true);
        $addroles       = (bool) ($filterparam['addroles'] ?? false);
        $addintereststags = (bool) ($filterparam['addintereststags'] ?? false);
        $addpreferences     = (bool) ($filterparam['addpreferences'] ?? false);
        $countenrolledcourses = (bool) ($filterparam['countenrolledcourses'] ?? false);

        // Pagination.
        if ($download || $returncount) {
            $limitnum = $limitfrom = 0;
        } else {
            $limitnum   = ($perpage > 0) ? $perpage : 50;
            $limitfrom  = ($pagenumber > 0) ? $limitnum * $pagenumber : 0;
        }

        // SQL fragments.
        $jointable = [];
        $sqlparams = [
            'guest_user_id' => 1,
            'user_deleted' => 1,
        ];
        $wherecondition = [
            "u.id <> :guest_user_id",
            "u.deleted <> :user_deleted",
        ];
        $joinapply = '';
        $whereapply = '';

        // Search by text.
        if ($searchuser) {
            $sqlparams['search_username'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_firstname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_lastname'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $sqlparams['search_email'] = "%" . $DB->sql_like_escape($searchuser) . "%";
            $wherecondition[] = '( ' . $DB->sql_like('u.username', ':search_username') . ' OR ' .
                $DB->sql_like('u.firstname', ':search_firstname') . ' OR ' .
                $DB->sql_like('u.lastname', ':search_lastname') . ' OR ' .
                $DB->sql_like('u.email', ':search_email') . ' )';
        }
        // Suspended.
        if ($suspended && $suspended != 'all') {
            $sqlparams['user_suspended'] = ($suspended == 'yes') ? 1 : 0;
            $wherecondition[] = "u.suspended = :user_suspended";
        }
        // Confirmed.
        if ($confirmed && $confirmed != 'all') {
            $sqlparams['user_confirmed'] = ($confirmed == 'yes') ? 1 : 0;
            $wherecondition[] = "u.confirmed = :user_confirmed";
        }
        // Search by multiple user ids.
        if (is_array($userids) && count($userids) > 0) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userids');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "u.id $insql";
        }
        // Search by role ids.
        if (is_array($roleids) && count($roleids) > 0) {
            $rolewherecondition = [];
            // Check if admin is present in roleids.
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
                $jointable['role_assignments'] = "INNER JOIN {role_assignments} ra ON u.id = ra.userid";
                [$insql, $inparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleids');
                $sqlparams = array_merge($sqlparams, $inparams);
                $rolewherecondition[] = "ra.roleid $insql";
            }
            // Join the role condition with OR.
            if (count($rolewherecondition) > 0) {
                $wherecondition[] = "(" . implode(" OR ", $rolewherecondition) . ")";
            }
        }
        // Search by course ids.
        if (is_array($courseids) && count($courseids) > 0) {
            $jointable['role_assignments'] = "INNER JOIN {role_assignments} ra ON u.id = ra.userid";
            $jointable['context'] = "INNER JOIN {context} ctx ON ra.contextid = ctx.id";

            $sqlparams['contextlevel'] = CONTEXT_COURSE;
            $wherecondition[] = 'ctx.contextlevel = :contextlevel';

            [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseids');
            $sqlparams = array_merge($sqlparams, $inparams);
            $wherecondition[] = "ctx.instanceid $insql";
        }

        // Apply countenrolledcourses.
        if ($countenrolledcourses) {
            $jointable['user_enrolments'] = "LEFT JOIN {user_enrolments} ue ON ue.userid = u.id";
            $jointable['enrol'] = "LEFT JOIN {enrol} e ON ue.enrolid = e.id";
        }
        // Apply table join.
        if (count($jointable) > 0) {
            $joinapply = implode(" ", $jointable) . " ";
        }

        // Apply where conditions with AND.
        if (count($wherecondition) > 0) {
            $whereapply = "WHERE " . implode(" AND ", $wherecondition) . " ";
        }

        // Order by sorting.
        $essentialfields = ['id', 'firstname', 'lastname', 'email', 'city', 'lastaccess', 'timemodified'];
        if (in_array($sortby, $essentialfields)) {
            $sortby = 'u.' . $sortby;
        } else if ($sortby == 'enrolledcourses') {
            $sortby = 'enrolledcourses';
        } else {
            $sortby = 'u.timemodified';
        }
        $sortdir = ($sortdir == SORT_ASC) ? 'ASC' : 'DESC';
        $orderby = "ORDER BY " . $sortby . " " . $sortdir . " ";

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
            $selectfields = 'u.*';
        } else {
            // Ensure essential fields are included
            $fields = array_unique(array_merge($essentialfields, $fields));
            $selectfields = 'u.' . implode(', u.', $fields);
        }

        // Add enrolledcourses count if requested.
        if ($countenrolledcourses) {
            $selectfields .= ', COUNT(DISTINCT e.courseid) AS enrolledcourses';
        }

        // Build GROUP BY clause (required when using aggregate functions).
        $groupby = '';
        if ($countenrolledcourses) {
            // When counting, group by all non-aggregated selected fields.
            if ($selectall) {
                // When selecting u.*, group by primary key only (Moodle handles this).
                $groupby = ' GROUP BY u.id ';
            } else {
                $groupbyfields = array_map(function ($field) {
                    return 'u.' . $field;
                }, $fields);
                $groupby = ' GROUP BY ' . implode(', ', $groupbyfields) . ' ';
            }
        }

        // Count total records.
        $sqlquery = 'SELECT COUNT(DISTINCT u.id) FROM {user} u ' .
            $joinapply .
            $whereapply;
        $totalrecords = $DB->count_records_sql($sqlquery, $sqlparams);
        // If only count is requested, return it now without executing the full data query.
        if ($returncount) {
            return $totalrecords;
        }

        // Final SQL query and execute.
        $sqlquery = "SELECT " . $selectfields .
            " FROM {user} u " .
            $joinapply .
            $whereapply .
            $groupby .
            $orderby;
        $records = $DB->get_records_sql($sqlquery, $sqlparams, $limitfrom, $limitnum);

        // Create return value.
        $alluserinfo = [];
        foreach ($records as $record) {
            $alluserinfo['data'][] = self::build_user_info(
                $record,
                [
                    'timestamp' => $timestamp,
                    'addroles' => $addroles,
                    'addintereststags' => $addintereststags,
                    'addpreferences' => $addpreferences,
                    'countenrolledcourses' => $countenrolledcourses,
                ]
            );
        }

        // Meta information.
        $recordcount = ($records) ? count($records) : 0;
        $alluserinfo['meta'] = [
            'totalrecords' => $totalrecords,
            'totalpage' => ($limitnum > 0) ? ceil($totalrecords / $limitnum) : 1,
            'pagenumber' => $pagenumber,
            'perpage' => $limitnum,
            'datadisplaycount' => $recordcount,
            'datafrom' => ($recordcount > 0) ? $limitfrom + 1 : 0,
            'datato' => ($recordcount > 0) ? $limitfrom + $recordcount : 0,
        ];

        return $alluserinfo;
    }

    /**
     * Builds a comprehensive user information array from a user record.
     *
     * @param stdClass $user User record object.
     * @param array $filterparam {
     *     Optional parameters for formatting and additional data.
     *
     *     bool $timestamp              Whether to return raw timestamps (true) or formatted dates (false). Default true.
     *     bool $addroles               Whether to include user roles (default false).
     *     bool $addintereststags       Whether to include user interest tags (default false).
     *     bool $addpreferences         Whether to include user preferences (default false).
     *     bool $countenrolledcourses   Whether to include enrolled courses count (default false).
     * }
     * @return array Structured user information with profile, preferences, roles, etc.
     */
    public static function build_user_info($user, $filterparam = [], $additionaldata = []) {
        // Get parameters.
        $timestamp = (bool) ($filterparam['timestamp'] ?? true);
        $addroles = (bool) ($filterparam['addroles'] ?? false);
        $addroleswithcontext = $filterparam['addroleswithcontext'] ?? null;
        $addintereststags = (bool) ($filterparam['addintereststags'] ?? false);
        $addpreferences = (bool) ($filterparam['addpreferences'] ?? false);
        $countenrolledcourses = (bool) ($filterparam['countenrolledcourses'] ?? false);

        // Data to return.
        $userInfo = [
            'id' => $user->id,
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'fullname' => fullname($user),
            'email' => $user->email,
            'address' => $user->address,
            'phone1' => $user->phone1,
            'phone2' => $user->phone2,
            'department' => $user->department,
            'institution' => $user->institution,
            'city' => $user->city,
            'country' => $user->country,
            'countryname' => ($user->country) ? get_string_manager()->get_list_of_countries()[$user->country] : '',
            'lang' => $user->lang,
            'language' => get_string_manager()->get_list_of_translations()[$user->lang],
            'timezone' => ($user->timezone == '99') ? get_config('moodle', 'timezone') : $user->timezone,
            'auth' => $user->auth,
            'authmethod' => get_string('pluginname', "auth_{$user->auth}"),
            'suspended' => $user->suspended,
            'confirmed' => $user->confirmed,
            'policyagreed' => $user->policyagreed,
            'profileimageurl' => self::get_user_profile_image($user),
            'profileimageurlsmall' => self::get_user_profile_image($user, true, 'f2'),
            'profileurl' => mchelpers::get_moodle_url('/user/profile.php', ['id' => $user->id], true, true),
            'gradereporturl' => mchelpers::get_moodle_url('/grade/report/overview/index.php', ['userid' => $user->id, 'id' => 1], true, true),
            'mycertificatesurl' => mchelpers::get_moodle_url('/mod/customcert/my_certificates.php', ['userid' => $user->id], true, true),
            'description' => self::get_user_description($user),
            'timecreated' => ($timestamp) ? $user->timecreated : mchelpers::format_time($user->timecreated),
            'timemodified' => ($timestamp) ? $user->timemodified : mchelpers::format_time($user->timemodified),
            'firstaccess' => ($timestamp) ? $user->firstaccess : mchelpers::format_time($user->firstaccess, ''),
            'lastaccess' => ($timestamp) ? $user->lastaccess : mchelpers::format_time($user->lastaccess, ''),
            'lastlogin' => ($timestamp) ? $user->lastlogin : mchelpers::format_time($user->lastlogin, ''),
            'currentlogin' => ($timestamp) ? $user->currentlogin : mchelpers::format_time($user->currentlogin, ''),
            'customofields' => self::get_user_customofields($user),
        ];

        // Add user interests tags.
        if ($addintereststags) {
            $intereststags = '';
            $interests = core_tag_tag::get_item_tags_array(
                'core',
                'user',
                $user->id,
                core_tag_tag::BOTH_STANDARD_AND_NOT,
                0,
                false
            );
            if ($interests) {
                $intereststags = join(', ', $interests);
            }
            $userInfo['interests'] = $intereststags;
        }

        // Add user preferences.
        if ($addpreferences) {
            $preferences = [];
            $userpreferences = get_user_preferences();
            foreach ($userpreferences as $prefname => $prefvalue) {
                $preferences[] = ['name' => $prefname, 'value' => $prefvalue];
            }
            $userInfo['preferences'] = $preferences;
        }

        // Add user roles.
        if ($addroles) {
            $userInfo['roles'] = role_service::get_roles($user->id, $addroleswithcontext);
        }

        // Add enrolled courses count.
        if ($countenrolledcourses) {
            $userInfo['enrolledcourses'] = $user->enrolledcourses ?? enrol_services::get_enrol_courses($user->id, ['returncount' => true]);
        }

        // Add any additional data passed.
        if (!empty($additionaldata)) {
            $userInfo = array_merge($userInfo, $additionaldata);
        }
        // Return final user info.
        return $userInfo;
    }
}
