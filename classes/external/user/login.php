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
// along with Moodle.  If not, see <http://www.gnu.org/copyleft/gpl.html>.

/**
 * User login external API for local_mchelpers plugin.
 *
 * Provides web service endpoint for user login with session/cookie data
 * for cross-domain single sign-on (similar to Edwiser Bridge).
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\user;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_api;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * User login external API class.
 */
class login extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function login_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username or email', VALUE_REQUIRED),
            'password' => new external_value(PARAM_RAW, 'Password', VALUE_REQUIRED),
        ]);
    }

    /**
     * Authenticate user and return session data for cross-domain SSO.
     *
     * @param string $username Username or email
     * @param string $password Password
     * @return stdClass Login result with user data and session info
     */
    public static function login($username, $password) {
        global $CFG, $DB, $SESSION;

        $params = self::validate_parameters(self::login_parameters(), [
            'username' => $username,
            'password' => $password,
        ]);

        $login_data = new stdClass();
        $login_data->status = false;

        // Validate username is provided.
        if (empty($params['username'])) {
            $login_data->message = get_string('usernameisrequired', 'local_mchelpers');
            return $login_data;
        }

        // Validate password is provided.
        if (empty($params['password'])) {
            $login_data->message = get_string('passwordisrequired', 'local_mchelpers');
            return $login_data;
        }

        // Authenticate user.
        $user = authenticate_user_login($params['username'], $params['password']);

        if (!$user) {
            $login_data->message = get_string('invalidlogin', 'local_mchelpers');
            return $login_data;
        }

        // Check if user is confirmed.
        if (empty($user->confirmed)) {
            $login_data->message = get_string('usernotconfirmed', '', fullname($user));
            return $login_data;
        }

        // Check if user is deleted.
        if (!empty($user->deleted)) {
            $login_data->message = get_string('userdeleted');
            return $login_data;
        }

        // Check if user is suspended.
        if (!empty($user->suspended)) {
            $login_data->message = get_string('suspended');
            return $login_data;
        }

        // Note: For API/web service context, we bypass complete_user_login()
        // to avoid session_regenerate_id() issues when no PHP session exists.
        // The session will be created when user visits the cookie_set_url via iframe.

        // Generate one-time hash for verification.
        // Hash contains encoded userid for security.
        $verification_data = [
            'userid' => $user->id,
            'timecreated' => time(),
        ];
        $encoded_data = json_encode($verification_data);
        $verification_hash = base64_encode($encoded_data) . ':' . sha1($encoded_data . $CFG->siteidentifier . random_string(32));

        // Store or update verification hash.
        $existing = $DB->get_record('user_preferences', [
            'userid' => $user->id,
            'name' => 'local_mchelpers_sso_verify',
        ]);

        if ($existing) {
            // Update existing record.
            $DB->set_field('user_preferences', 'value', $verification_hash, ['id' => $existing->id]);
        } else {
            // Insert new record.
            $DB->insert_record('user_preferences', (object)[
                'userid' => $user->id,
                'name' => 'local_mchelpers_sso_verify',
                'value' => $verification_hash,
            ]);
        }

        // Build cookie set URL - only hash parameter.
        $cookie_set_url = new \moodle_url('/local/mchelpers/login/sso_login.php', [
            'hash' => $verification_hash,
        ]);

        // Prepare response data.
        $login_data->status = true;
        $login_data->message = get_string('loginsuccess', 'local_mchelpers');
        $login_data->userid = $user->id;
        $login_data->username = $user->username;
        $login_data->email = $user->email;
        $login_data->fullname = fullname($user);
        $login_data->verification_hash = $verification_hash;
        $login_data->sso_login_url = $cookie_set_url->out(false);

        return $login_data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function login_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Login status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'username' => new external_value(PARAM_RAW, 'Username', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_RAW, 'Email', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_RAW, 'Full name', VALUE_OPTIONAL),
            'verification_hash' => new external_value(PARAM_RAW, 'Verification hash', VALUE_OPTIONAL),
            'sso_login_url' => new external_value(PARAM_RAW, 'SSO login URL (for ajax or iframe or other front-end purpose)', VALUE_OPTIONAL),
        ]);
    }
}
