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
 * User get info external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\user;

use core_external\external_description;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_function_parameters;
use local_mchelpers\local\service\user_service;
use core\user as core_user;
use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

/**
 * User get info external API class.
 *
 * Provides web service endpoint to get user information.
 * 
 * from user/externallib.php
 */
class get_info extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_info_parameters() {
        return new external_function_parameters([
            'userids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID'),
                'List of user IDs. If empty return all users.',
                VALUE_DEFAULT,
                []
            ),
            'page' => new external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 15),
        ]);
    }

    /**
     * Get user information.
     *
     * @param array $userids List of user IDs
     * @param int $page Page number
     * @param int $perpage Page size
     * @return array User information with pagination
     */
    public static function get_info($userids = [], $page = 1, $perpage = 15) {
        global $DB;

        $params = self::validate_parameters(self::get_info_parameters(), [
            'userids' => $userids,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        // Ensure the current user is allowed to run this function.
        /** @var \context System context instance */
        $context = \context_system::instance();
        self::validate_context($context);

        $return_users_data = [];

        // Retrieve users.
        $filterparam = [
            'userids' => $params['userids'],
            'page' => $params['page'],
            'perpage' => $params['perpage'],
            'countenrolledcourses' => true,
            'timestamp' => true,
            'addroles' => true,
            'addintereststags' => false,
            'addpreferences' => false,
        ];
        $alluserinfo = user_service::get_all_user_info($filterparam);
        $return_users_data['data'] = $alluserinfo['data'];
        $return_users_data['meta'] = $alluserinfo['meta'];
        $return_users_data['meta']['userids'] = $params['userids'];
        $return_users_data['status'] = true;

        return $return_users_data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function get_info_returns() {
        $userinfofields =  [];
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'data' => new external_multiple_structure(
                self::user_data_return_fields($userinfofields),
                'Users data',
                VALUE_OPTIONAL
            ),
            'meta' => new external_single_structure(
                [
                    'totalrecords' => new external_value(PARAM_INT, 'Total number of records', VALUE_OPTIONAL),
                    'page' => new external_value(PARAM_INT, 'Current page number', VALUE_OPTIONAL),
                    'perpage' => new external_value(PARAM_INT, 'Number of data shown per page', VALUE_OPTIONAL),
                    'totalpage' => new external_value(PARAM_INT, 'Total number of pages', VALUE_OPTIONAL),
                    'datadisplaycount' => new external_value(PARAM_INT, 'Current page data count', VALUE_OPTIONAL),
                    'datafrom' => new external_value(PARAM_INT, 'Current page data from record number', VALUE_OPTIONAL),
                    'datato' => new external_value(PARAM_INT, 'Current page data to record number', VALUE_OPTIONAL),
                    'userids' => new external_multiple_structure(
                        new external_value(PARAM_INT, 'User ID'),
                        'List of user IDs included in the current page data',
                        VALUE_OPTIONAL
                    ),
                ],
                'meta information',
                VALUE_OPTIONAL
            ),
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL, VALUE_DEFAULT, null),
        ]);
    }

    /**
     * user return value description.
     *
     * @param array $additionalfields some additional field
     * @return external_description
     */
    public static function user_data_return_fields($additionalfields = []) {
        $userfields = [
            'id'    => new external_value(core_user::get_property_type('id'), 'ID of the user'),
            'username'    => new external_value(core_user::get_property_type('username'), 'The username', VALUE_OPTIONAL),
            'firstname'   => new external_value(core_user::get_property_type('firstname'), 'The first name(s) of the user', VALUE_OPTIONAL),
            'lastname'    => new external_value(core_user::get_property_type('lastname'), 'The family name of the user', VALUE_OPTIONAL),
            'fullname'    => new external_value(core_user::get_property_type('firstname'), 'The fullname of the user', VALUE_OPTIONAL),
            'email'       => new external_value(core_user::get_property_type('email'), 'An email address - allow email as root@localhost', VALUE_OPTIONAL),
            'address'     => new external_value(core_user::get_property_type('address'), 'Postal address', VALUE_OPTIONAL),
            'phone1'      => new external_value(core_user::get_property_type('phone1'), 'Phone 1', VALUE_OPTIONAL),
            'phone2'      => new external_value(core_user::get_property_type('phone2'), 'Phone 2', VALUE_OPTIONAL),
            'department'  => new external_value(core_user::get_property_type('department'), 'department', VALUE_OPTIONAL),
            'institution' => new external_value(core_user::get_property_type('institution'), 'institution', VALUE_OPTIONAL),
            'idnumber'    => new external_value(core_user::get_property_type('idnumber'), 'An arbitrary ID code number perhaps from the institution', VALUE_OPTIONAL),
            'interests'   => new external_value(PARAM_TEXT, 'user interests (separated by commas)', VALUE_OPTIONAL),
            'auth'        => new external_value(core_user::get_property_type('auth'), 'Auth plugins include manual, ldap, etc', VALUE_OPTIONAL),
            'authmethod'  => new external_value(PARAM_TEXT, 'Auth method name such as manual, ldap, etc', VALUE_OPTIONAL),
            'suspended'   => new external_value(core_user::get_property_type('suspended'), 'Suspend user account, either false to enable user login or true to disable it', VALUE_OPTIONAL),
            'confirmed'   => new external_value(core_user::get_property_type('confirmed'), 'Active user: 1 if confirmed, 0 otherwise', VALUE_OPTIONAL),
            'lang'        => new external_value(core_user::get_property_type('lang'), 'Language code such as "en", must exist on server', VALUE_OPTIONAL),
            'language' => new external_value(PARAM_TEXT, 'Language', VALUE_OPTIONAL),
            'calendartype' => new external_value(core_user::get_property_type('calendartype'), 'Calendar type such as "gregorian", must exist on server', VALUE_OPTIONAL),
            'theme'       => new external_value(core_user::get_property_type('theme'), 'Theme name such as "standard", must exist on server', VALUE_OPTIONAL),
            'timezone'    => new external_value(core_user::get_property_type('timezone'), 'Timezone code such as Australia/Perth, or 99 for default', VALUE_OPTIONAL),
            'mailformat'  => new external_value(core_user::get_property_type('mailformat'), 'Mail format code is 0 for plain text, 1 for HTML etc', VALUE_OPTIONAL),
            'trackforums'  => new external_value(core_user::get_property_type('trackforums'), 'Whether the user is tracking forums.', VALUE_OPTIONAL),
            'description' => new external_value(core_user::get_property_type('description'), 'User profile description', VALUE_OPTIONAL),
            'descriptionformat' => new external_value(core_user::get_property_type('descriptionformat'), 'User profile description format', VALUE_OPTIONAL),
            'city'        => new external_value(core_user::get_property_type('city'), 'Home city of the user', VALUE_OPTIONAL),
            'country'     => new external_value(core_user::get_property_type('country'), 'Home country code of the user, such as AU or CZ', VALUE_OPTIONAL),
            'countryname' => new external_value(PARAM_TEXT, 'Country name', VALUE_OPTIONAL),
            'customfields' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The type of the custom field - text field, checkbox...'),
                        'value' => new external_value(PARAM_RAW, 'The value of the custom field (as stored in the database)'),
                        'displayvalue' => new external_value(
                            PARAM_RAW,
                            'The value of the custom field for display',
                            VALUE_OPTIONAL
                        ),
                        'name' => new external_value(PARAM_RAW, 'The name of the custom field'),
                        'shortname' => new external_value(PARAM_RAW, 'The shortname of the custom field - to be able to build the field class in the code'),

                    ]
                ),
                'User custom fields (also known as user profile fields)',
                VALUE_OPTIONAL
            ),
            'preferences' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'name'  => new external_value(PARAM_RAW, 'The name of the preferences'),
                        'value' => new external_value(PARAM_RAW, 'The value of the preference'),
                    ]
                ),
                'Users preferences',
                VALUE_OPTIONAL
            ),
            'profileimageurl' => new external_value(PARAM_URL, 'User image profile URL - big version', VALUE_OPTIONAL),
            'profileimageurlsmall' => new external_value(PARAM_URL, 'User image profile URL - small version', VALUE_OPTIONAL),
            'profileurl' => new external_value(PARAM_URL, 'Profile URL', VALUE_OPTIONAL),
            'gradereporturl' => new external_value(PARAM_URL, 'Grade report URL', VALUE_OPTIONAL),
            'mycertificatesurl' => new external_value(PARAM_URL, 'My certificates URL from mod certificate', VALUE_OPTIONAL),
            'timecreated' => new external_value(PARAM_INT, 'Time created', VALUE_OPTIONAL),
            'timemodified' => new external_value(PARAM_INT, 'Time modified', VALUE_OPTIONAL),
            'firstaccess' => new external_value(core_user::get_property_type('firstaccess'), 'first access to the site (0 if never)', VALUE_OPTIONAL),
            'lastaccess'  => new external_value(core_user::get_property_type('lastaccess'), 'last access to the site (0 if never)', VALUE_OPTIONAL),
            'lastlogin' => new external_value(PARAM_INT, 'Last login', VALUE_OPTIONAL),
            'interests' => new external_value(PARAM_RAW, 'Interests', VALUE_OPTIONAL),
            'roles' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'roleid' => new external_value(PARAM_INT, 'Role ID'),
                        'name' => new external_value(PARAM_TEXT, 'Role name'),
                        'shortname' => new external_value(PARAM_TEXT, 'Role short name'),
                    ]
                ),
                'User roles',
                VALUE_OPTIONAL
            ),
            'enrolledcourses' => new external_value(PARAM_INT, 'Count of enrolled courses', VALUE_OPTIONAL),
        ];
        if (!empty($additionalfields)) {
            $userfields = array_merge($userfields, $additionalfields);
        }
        return new external_single_structure($userfields);
    }
}
