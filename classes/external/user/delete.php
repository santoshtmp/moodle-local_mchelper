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
 * User delete external API for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @category   external
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\external\user;

use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

/**
 * User delete external API class.
 *
 * Provides web service endpoint to delete users.
 * from user/externallib.php
 */
class delete extends external_api {


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters(
            [
                'userid' => new external_value(
                    PARAM_RAW,
                    'ID of the user to be delete'
                )
            ]
        );
        // return parent::delete_users_parameters();
    }

    /**
     * Delete a user.
     *
     * @param string $userid Encrypted user ID to delete
     * @return array Status and message
     */
    public static function delete($userid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/user/lib.php");

        // Ensure the current user is allowed to run this function.
        /** @var \context System context instance */
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/user:delete', $context);

        $params = self::validate_parameters(
            self::delete_parameters(),
            ['userid' => $userid]
        );

        try {
            if ($params['userid']) {
                $userid = (int) $params['userid'];
                $transaction = $DB->start_delegated_transaction();
                $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

                // Must not allow deleting of admins or self.
                if (is_siteadmin($user)) {
                    return [
                        'status' => false,
                        'message' => 'Given user is an admin, admin cannot be deleted'
                    ];
                }
                if ($USER->id == $user->id) {
                    return [
                        'status' => false,
                        'message' => 'You cannot delete yourself'
                    ];
                }

                user_delete_user($user);
                $transaction->allow_commit();

                return [
                    'status' => true,
                    'message' => 'User successfully deleted: ' . $user->username
                ];
            }
            return [
                'status' => false,
                'message' => 'Error: invalid userid'
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage()
            ];
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function delete_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'Status'),
                'message' => new external_value(PARAM_RAW, 'Message'),
            ]
        );
    }
}
