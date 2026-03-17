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
 * mchelper functions and service definitions.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * https://moodledev.io/docs/4.5/apis/subsystems/external
 * https://moodledev.io/docs/4.5/apis/subsystems/external/description
 * https://moodledev.io/docs/4.5/apis/subsystems/external/functions
 * https://moodledev.io/docs/4.5/apis/subsystems/external/advanced/custom-services
 * 
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // User APIs.
    'local_mchelpers_user_get_info' => [
        'classname' => 'local_mchelpers\external\user\get_info',
        'methodname' => 'get_info',
        'description' => 'Get user information with pagination',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/user:viewdetails',
    ],
    'local_mchelpers_user_course_list' => [
        'classname' => 'local_mchelpers\external\user\course_list',
        'methodname' => 'course_list',
        'description' => 'Get list of courses where user is enrolled',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:viewparticipants',
    ],
    'local_mchelpers_user_create' => [
        'classname' => 'local_mchelpers\external\user\create',
        'methodname' => 'create',
        'description' => 'Create a new user',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/user:create',
    ],
    'local_mchelpers_user_update' => [
        'classname' => 'local_mchelpers\external\user\update',
        'methodname' => 'update',
        'description' => 'Update user information',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/user:update',
    ],
    'local_mchelpers_user_delete' => [
        'classname' => 'local_mchelpers\external\user\delete',
        'methodname' => 'delete',
        'description' => 'Delete a user',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/user:delete',
    ],
    'local_mchelpers_user_logout' => [
        'classname' => 'local_mchelpers\external\user\logout',
        'methodname' => 'logout',
        'description' => 'Logout user and invalidate session token',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
    ],

    // Course APIs.
    'local_mchelpers_course_get_info' => [
        'classname' => 'local_mchelpers\external\course\get_info',
        'methodname' => 'get_info',
        'description' => 'Get course information with pagination',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:view,moodle/course:viewparticipants',
    ],
    'local_mchelpers_course_enroll' => [
        'classname' => 'local_mchelpers\external\course\enroll',
        'methodname' => 'enroll',
        'description' => 'Enroll a user in a course',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'enrol/manual:enrol',
    ],
    'local_mchelpers_course_user_list' => [
        'classname' => 'local_mchelpers\external\course\user_list',
        'methodname' => 'user_list',
        'description' => 'Get list of users enrolled in a course',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:viewparticipants',
    ],
    'local_mchelpers_course_certificate' => [
        'classname' => 'local_mchelpers\external\course\certificate',
        'methodname' => 'certificate',
        'description' => 'Get or issue course certificate for a user',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'mod/customcert:view',
    ],

    // Role APIs.
    'local_mchelpers_role_get' => [
        'classname' => 'local_mchelpers\external\role\get',
        'methodname' => 'get',
        'description' => 'Get available roles',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/role:review',
    ],
    'local_mchelpers_role_assign' => [
        'classname' => 'local_mchelpers\external\role\assign',
        'methodname' => 'assign',
        'description' => 'Assign a system role to a user',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'moodle/role:assign',
    ],

    // Content APIs.
    'local_mchelpers_content_get_info' => [
        'classname' => 'local_mchelpers\external\content\get_info',
        'methodname' => 'get_info',
        'description' => 'Get content information with pagination and optional filtering',
        'type' => 'read',
        'ajax' => false,
        'loginrequired' => false,
        'capabilities' => 'local/mchelpers:viewcontent',
    ],
    'local_mchelpers_content_create' => [
        'classname' => 'local_mchelpers\external\content\create',
        'methodname' => 'create',
        'description' => 'Create a new content',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'local/mchelpers:managecontent',
    ],
    'local_mchelpers_content_delete' => [
        'classname' => 'local_mchelpers\external\content\delete',
        'methodname' => 'delete',
        'description' => 'Delete a content',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
        'capabilities' => 'local/mchelpers:managecontent',
    ],
];

$services = [
    // The name of the service.
    'Custom Helper Web Services' => [
        // A list of external functions available in this service.
        'functions' => [
            // User APIs.
            'local_mchelpers_user_get_info',
            'local_mchelpers_user_course_list',
            'local_mchelpers_user_create',
            'local_mchelpers_user_update',
            'local_mchelpers_user_delete',
            'local_mchelpers_user_logout',
            // Course APIs.
            'local_mchelpers_course_get_info',
            'local_mchelpers_course_enroll',
            'local_mchelpers_course_user_list',
            'local_mchelpers_course_certificate',
            // Role APIs.
            'local_mchelpers_role_get',
            'local_mchelpers_role_assign',
            // Content APIs.
            'local_mchelpers_content_get_info',
            'local_mchelpers_content_create',
            'local_mchelpers_content_delete',
        ],
        'shortname' => 'local_mchelpers_web_services', // This field os optional, but requried if the `restrictedusers` value is set, so as to allow configuration via the Web UI.
        'restrictedusers' => 0, // If enabled, the Moodle administrator must link a user to this service from the Web UI.
        'enabled' => 0, // Whether the service is enabled by default or not.
        'downloadfiles' => 0, // Whether to allow file downloads.
        'uploadfiles' => 0, // Whether to allow file uploads.
    ],
];
