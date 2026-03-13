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
 * User logout external API for local_mchelpers plugin.
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
 * User logout external API class.
 *
 * Provides web service endpoint for user logout.
 * Supports both token-based and SSO cross-domain logout.
 */
class logout extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function logout_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_REQUIRED),
            'sessionid' => new external_value(PARAM_RAW, 'Session ID from sso_login.php (optional)', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Logout user by invalidating session.
     *
     * @param int $userid User ID
     * @param string $sessionid Session ID from sso_login.php (optional)
     * @return stdClass Logout result with status and message
     */
    public static function logout($userid, $sessionid = '') {
        global $DB, $USER;

        $params = self::validate_parameters(self::logout_parameters(), [
            'userid' => $userid,
            'sessionid' => $sessionid,
        ]);

        $logout_data = new stdClass();
        $logout_data->status = false;

        // Validate user ID is provided.
        if (empty($params['userid'])) {
            $logout_data->message = get_string('useridisrequired', 'local_mchelpers');
            return $logout_data;
        }

        // Get user to verify exists.
        $user = $DB->get_record('user', ['id' => $params['userid'], 'deleted' => 0]);

        if (!$user) {
            $logout_data->message = get_string('usernotfound', 'local_mchelpers');
            return $logout_data;
        }

        // Check if user has any active sessions.
        $has_active_session = $DB->record_exists('sessions', ['userid' => $params['userid']]);

        if ($has_active_session) {
            // Delete session(s).
            if (!empty($params['sessionid'])) {
                // Delete specific session only (from sso_login.php).
                $session_deleted = $DB->delete_records('sessions', [
                    'userid' => $params['userid'],
                    'sid' => $params['sessionid'],
                ]);
                $logout_data->logout_type = 'single_session';
            } else {
                // Delete all sessions (force logout from all devices).
                $session_deleted = $DB->delete_records('sessions', [
                    'userid' => $params['userid'],
                ]);
                $logout_data->logout_type = 'all_sessions';
            }
        }

        // Note: SSO hash will be invalidated by user_event_observer::user_loggedout event.
        // Trigger logout event (only if user is currently logged in via this session).
        if ($USER->id == $params['userid']) {
            $event = \core\event\user_loggedout::create([
                'userid' => $params['userid'],
                'objectid' => $params['userid'],
            ]);
            $event->trigger();
        }

        // Re-arrange data
        $logout_data->status = true;
        $logout_data->message = (!$has_active_session) ? 'User already logged out' : get_string('logoutsuccess', 'local_mchelpers');
        $logout_data->userid = $params['userid'];
        $logout_data->session_deleted = (!$has_active_session) ? false : (bool)$session_deleted;

        return $logout_data;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function logout_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Logout status'),
            'message' => new external_value(PARAM_RAW, 'Response message'),
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'session_deleted' => new external_value(PARAM_BOOL, 'Session deleted status', VALUE_OPTIONAL),
            'logout_type' => new external_value(PARAM_RAW, 'Logout type: single_session or all_sessions', VALUE_OPTIONAL),
        ]);
    }
}
