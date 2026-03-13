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
 * User update external API for local_mchelpers plugin.
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
use core_external\external_format_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_function_parameters;
use core_external\external_api;
use core\user as core_user;

defined('MOODLE_INTERNAL') || die();

/**
 * User update external API class.
 *
 * Provides web service endpoint to update users.
 */
class update extends external_api {


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_parameters() {
        $userfields = [
            'id' => new external_value(
                PARAM_RAW,
                'Encrypted ID of the user'
            ),
            // General.
            'username' => new external_value(
                core_user::get_property_type('username'),
                'Username policy is defined in Moodle security config.',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'auth' => new external_value(
                core_user::get_property_type('auth'),
                'Auth plugins include manual, ldap, etc',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'suspended' => new external_value(
                core_user::get_property_type('suspended'),
                'Suspend user account, either false to enable user login or true to disable it',
                VALUE_OPTIONAL
            ),
            'password' => new external_value(
                core_user::get_property_type('password'),
                'Plain text password consisting of any characters',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'firstname' => new external_value(
                core_user::get_property_type('firstname'),
                'The first name(s) of the user',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'lastname' => new external_value(
                core_user::get_property_type('lastname'),
                'The family name of the user',
                VALUE_OPTIONAL
            ),
            'email' => new external_value(
                core_user::get_property_type('email'),
                'A valid and unique email address',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'maildisplay' => new external_value(core_user::get_property_type('maildisplay'), 'Email visibility', VALUE_OPTIONAL),
            'city' => new external_value(core_user::get_property_type('city'), 'Home city of the user', VALUE_OPTIONAL),
            'country' => new external_value(
                core_user::get_property_type('country'),
                'Home country code of the user, such as AU or CZ',
                VALUE_OPTIONAL
            ),
            'timezone' => new external_value(
                core_user::get_property_type('timezone'),
                'Timezone code such as Australia/Perth, or 99 for default',
                VALUE_OPTIONAL
            ),
            'description' => new external_value(
                core_user::get_property_type('description'),
                'User profile description, no HTML',
                VALUE_OPTIONAL
            ),
            // User picture.
            'userpicture' => new external_value(
                PARAM_INT,
                'The itemid where the new user picture has been uploaded to, 0 to delete',
                VALUE_OPTIONAL
            ),
            // Additional names.
            'firstnamephonetic' => new external_value(
                core_user::get_property_type('firstnamephonetic'),
                'The first name(s) phonetically of the user',
                VALUE_OPTIONAL
            ),
            'lastnamephonetic' => new external_value(
                core_user::get_property_type('lastnamephonetic'),
                'The family name phonetically of the user',
                VALUE_OPTIONAL
            ),
            'middlename' => new external_value(
                core_user::get_property_type('middlename'),
                'The middle name of the user',
                VALUE_OPTIONAL
            ),
            'alternatename' => new external_value(
                core_user::get_property_type('alternatename'),
                'The alternate name of the user',
                VALUE_OPTIONAL
            ),
            // Interests.
            'interests' => new external_value(PARAM_TEXT, 'User interests (separated by commas)', VALUE_OPTIONAL),
            // Optional.
            'idnumber' => new external_value(
                core_user::get_property_type('idnumber'),
                'An arbitrary ID code number perhaps from the institution',
                VALUE_OPTIONAL
            ),
            'institution' => new external_value(core_user::get_property_type('institution'), 'Institution', VALUE_OPTIONAL),
            'department' => new external_value(core_user::get_property_type('department'), 'Department', VALUE_OPTIONAL),
            'phone1' => new external_value(core_user::get_property_type('phone1'), 'Phone', VALUE_OPTIONAL),
            'phone2' => new external_value(core_user::get_property_type('phone2'), 'Mobile phone', VALUE_OPTIONAL),
            'address' => new external_value(core_user::get_property_type('address'), 'Postal address', VALUE_OPTIONAL),
            // Other user preferences stored in the user table.
            'lang' => new external_value(
                core_user::get_property_type('lang'),
                'Language code such as "en", must exist on server',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'calendartype' => new external_value(
                core_user::get_property_type('calendartype'),
                'Calendar type such as "gregorian", must exist on server',
                VALUE_OPTIONAL,
                '',
                NULL_NOT_ALLOWED
            ),
            'theme' => new external_value(
                core_user::get_property_type('theme'),
                'Theme name such as "standard", must exist on server',
                VALUE_OPTIONAL
            ),
            'mailformat' => new external_value(
                core_user::get_property_type('mailformat'),
                'Mail format code is 0 for plain text, 1 for HTML etc',
                VALUE_OPTIONAL
            ),
            // Custom user profile fields.
            'customfields' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'type'  => new external_value(PARAM_ALPHANUMEXT, 'The name of the custom field'),
                        'value' => new external_value(PARAM_RAW, 'The value of the custom field')
                    ]
                ),
                'User custom fields (also known as user profil fields)',
                VALUE_OPTIONAL
            ),
            // User preferences.
            'preferences' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'type'  => new external_value(PARAM_RAW, 'The name of the preference'),
                        'value' => new external_value(PARAM_RAW, 'The value of the preference')
                    ]
                ),
                'User preferences',
                VALUE_OPTIONAL
            ),
        ];
        return new external_function_parameters(
            [
                'users' => new external_single_structure($userfields)
            ]
        );
    }

    /**
     * Update users.
     *
     * @param array $users An array of users to update.
     * @return array An array of update results
     */
    public static function update($users) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/user/lib.php");
        require_once($CFG->dirroot . "/user/profile/lib.php");
        require_once($CFG->dirroot . '/user/editlib.php');

        // Ensure the current user is allowed to run this function.
        /** @var \context System context instance */
        $context = \context_system::instance();
        require_capability('moodle/user:update', $context);
        self::validate_context($context);

        $params = self::validate_parameters(
            self::update_parameters(),
            ['users' => $users]
        );

        $filemanageroptions = array(
            'maxbytes' => $CFG->maxbytes,
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => 'optimised_image'
        );

        $updateDate = array();
        $updateDate['status'] = false;
        foreach (array($params['users']) as $user) {
            if ($user['id']) {
                try {
                    $user_old = $DB->get_record('external_tokens', array('userid' => $user['id']), '*', MUST_EXIST);
                    if ($user_old) {
                        $updateDate['message'] = 'cannot update this user, because it is used with external api';
                        return $updateDate;
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
            foreach (array('username', 'firstname', 'lastname') as $fieldname) {
                if ($user[$fieldname]) {
                    $user[$fieldname] = trim($user[$fieldname]);
                    $user[$fieldname] = substr($user[$fieldname], 0, 97);
                }
            }
            // Catch any exception while updating a user and return it as a warning.
            try {
                $transaction = $DB->start_delegated_transaction();

                // First check the user exists.
                if (!$existinguser = core_user::get_user($user['id'])) {
                    $updateDate['message'] = 'invalid user id';
                    $userid['exception'] = true;
                    return $updateDate;
                }
                // Check if we are trying to update an admin.
                if ($existinguser->id != $USER->id and is_siteadmin($existinguser) and !is_siteadmin($USER)) {
                    $updateDate['message'] = 'Can not update admin accounts';
                    $userid['exception'] = true;
                    return $updateDate;
                }
                // Other checks (deleted, remote or guest users).
                if ($existinguser->deleted) {
                    $updateDate['message'] = 'User is a deleted user';
                    $userid['exception'] = true;
                    return $updateDate;
                }
                if (is_mnet_remote_user($existinguser)) {
                    $updateDate['message'] = 'User is a remote user';
                    $userid['exception'] = true;
                    return $updateDate;
                }
                if (isguestuser($existinguser->id)) {
                    $updateDate['message'] = 'Cannot update guest account';
                    $userid['exception'] = true;
                    return $updateDate;
                }
                // Check duplicated emails.
                if (isset($user['email']) && $user['email'] !== $existinguser->email) {
                    if (!validate_email($user['email'])) {
                        $updateDate['message'] = 'Invalid email address';
                        $userid['exception'] = true;
                        return $updateDate;
                    } else if (empty($CFG->allowaccountssameemail)) {
                        // Make a case-insensitive query for the given email address
                        // and make sure to exclude the user being updated.
                        $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                        $params = array(
                            'email' => $user['email'],
                            'mnethostid' => $CFG->mnet_localhost_id,
                            'userid' => $user['id']
                        );
                        // Skip if there are other user(s) that already have the same email.
                        if ($DB->record_exists_select('user', $select, $params)) {
                            $updateDate['message'] = 'Duplicate email address';
                            $userid['exception'] = true;
                            return $updateDate;
                        }
                    }
                }

                user_update_user($user, true, false);

                $userobject = (object)$user;

                // Update user picture if it was specified for this user.
                if (empty($CFG->disableuserimages) && isset($user['userpicture'])) {
                    $userobject->deletepicture = null;

                    if ($user['userpicture'] == 0) {
                        $userobject->deletepicture = true;
                    } else {
                        $userobject->imagefile = $user['userpicture'];
                    }

                    core_user::update_picture($userobject, $filemanageroptions);
                }

                // Update user interests.
                if (!empty($user['interests'])) {
                    $trimmedinterests = array_map('trim', explode(',', $user['interests']));
                    $interests = array_filter($trimmedinterests, function ($value) {
                        return !empty($value);
                    });
                    useredit_update_interests($userobject, $interests);
                }

                // Update user custom fields.
                if (!empty($user['customfields'])) {

                    foreach ($user['customfields'] as $customfield) {
                        // Profile_save_data() saves profile file it's expecting a user with the correct id,
                        // and custom field to be named profile_field_"shortname".
                        $user["profile_field_" . $customfield['type']] = $customfield['value'];
                    }
                    profile_save_data((object) $user);
                }

                // Trigger event.
                \core\event\user_updated::create_from_userid($user['id'])->trigger();

                // Preferences.
                if (!empty($user['preferences'])) {
                    $userpref = clone($existinguser);
                    foreach ($user['preferences'] as $preference) {
                        $userpref->{'preference_' . $preference['type']} = $preference['value'];
                    }
                    useredit_update_user_preference($userpref);
                }
                if (isset($user['suspended']) and $user['suspended']) {
                    \core\session\manager::kill_user_sessions($user['id']);
                }

                $updateDate['status'] = true;
                $updateDate['message'] = 'user updated sucessfully';

                $transaction->allow_commit();
            } catch (\Exception $e) {
                try {
                    $transaction->rollback($e);
                } catch (\Exception $e) {
                    // $warning = [];
                    // $warning['item'] = 'user';
                    // $warning['itemid'] = $user['id'];
                    // if ($e instanceof \moodle_exception) {
                    //     $warning['warningcode'] = $e->errorcode;
                    // } else {
                    //     $warning['warningcode'] = $e->getCode();
                    // }
                    // $warning['message'] = $e->getMessage();
                    // $warnings[] = $warning;
                    $updateDate['status'] = false;
                    $userid['exception'] = true;
                    $updateDate['message'] = $e->getMessage();
                }
            }
        }

        return $updateDate;
    }

    /**
     * Returns description of method result value
     *
     * @return external_multiple_structure
     */
    public static function update_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status'),
                'message' => new external_value(PARAM_RAW, 'Response message', VALUE_OPTIONAL),
                'exception' => new external_value(PARAM_RAW, 'message', VALUE_OPTIONAL),
            )
        );
    }
}
