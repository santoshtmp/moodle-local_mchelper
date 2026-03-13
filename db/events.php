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
 * Event observers for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//  https://docs.moodle.org/dev/Events_API

/* 
eventname – fully qualified event class name or "*" indicating all events, ex.: \plugintype_pluginname\event\something_happened.
callback - PHP callable type.
includefile - optional. File to be included before calling the observer. Path relative to dirroot.
priority - optional. Defaults to 0. Observers with higher priority are notified first.
internal - optional. Defaults to true. Non-internal observers are not called during database transactions, but instead after a successful commit of the transaction. 
*/


defined('MOODLE_INTERNAL') || die();

/**
 * Event observer configuration.
 *
 * Each observer is configured with:
 * - eventname: Fully qualified event class name
 * - callback: PHP callable (class::method)
 * - priority: Optional, defaults to 0 (higher = notified first)
 * - internal: Optional, defaults to true (false = called after transaction commit)
 */

$observers = [

    // =========================================================================
    // USER EVENTS
    // =========================================================================

    // User deleted.
    [
        'eventname' => '\core\event\user_deleted',
        'callback'  => '\local_mchelpers\event\user_event_observer::user_deleted',
    ],

    // User created.
    [
        'eventname' => '\core\event\user_created',
        'callback'  => '\local_mchelpers\event\user_event_observer::user_created',
    ],

    // User updated.
    [
        'eventname' => '\core\event\user_updated',
        'callback'  => '\local_mchelpers\event\user_event_observer::user_updated',
    ],

    // User logged in - track activity.
    // [
    //     'eventname' => '\core\event\user_loggedin',
    //     'callback'  => '\local_mchelpers\event\user_event_observer::user_loggedin',
    // ],

    // User logged out.
    [
        'eventname' => '\core\event\user_loggedout',
        'callback'  => '\local_mchelpers\event\user_event_observer::user_loggedout',
    ],

    // =========================================================================
    // COURSE EVENTS
    // =========================================================================

    // Course created.
    [
        'eventname' => '\core\event\course_created',
        'callback'  => '\local_mchelpers\event\course_event_observer::course_created',
    ],

    // Course deleted.
    [
        'eventname' => '\core\event\course_deleted',
        'callback'  => '\local_mchelpers\event\course_event_observer::course_deleted',
    ],

    // Course updated.
    [
        'eventname' => '\core\event\course_updated',
        'callback'  => '\local_mchelpers\event\course_event_observer::course_updated',
    ],

    // Course viewed - track statistics.
    // [
    //     'eventname' => '\core\event\course_viewed',
    //     'callback'  => '\local_mchelpers\event\course_event_observer::course_viewed',
    // ],

    // Course section created.
    // [
    //     'eventname' => '\core\event\course_section_created',
    //     'callback'  => '\local_mchelpers\event\course_event_observer::course_section_created',
    // ],

    // Course section deleted.
    // [
    //     'eventname' => '\core\event\course_section_deleted',
    //     'callback'  => '\local_mchelpers\event\course_event_observer::course_section_deleted',
    // ],

    // Course category created.
    // [
    //     'eventname' => '\core\event\course_category_created',
    //     'callback'  => '\local_mchelpers\event\course_event_observer::course_category_created',
    // ],

    // Course category deleted.
    // [
    //     'eventname' => '\core\event\course_category_deleted',
    //     'callback'  => '\local_mchelpers\event\course_event_observer::course_category_deleted',
    // ],

    // =========================================================================
    // MODULE EVENTS
    // =========================================================================

    // Course module created.
    [
        'eventname' => '\core\event\course_module_created',
        'callback'  => '\local_mchelpers\event\module_event_observer::course_module_created',
    ],

    // Course module updated.
    [
        'eventname' => '\core\event\course_module_updated',
        'callback'  => '\local_mchelpers\event\module_event_observer::course_module_updated',
    ],

    // Course module deleted.
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => '\local_mchelpers\event\module_event_observer::course_module_deleted',
    ],

    // Course module viewed.
    // [
    //     'eventname' => '\core\event\course_module_viewed',
    //     'callback'  => '\local_mchelpers\event\module_event_observer::course_module_viewed',
    // ],

    // Course module completion updated.
    // [
    //     'eventname' => '\core\event\course_module_completion_updated',
    //     'callback'  => '\local_mchelpers\event\module_event_observer::course_module_completion_updated',
    // ],

    // =========================================================================
    // ROLE EVENTS
    // =========================================================================

    // Role assigned to user.
    // [
    //     'eventname' => '\core\event\role_assigned',
    //     'callback'  => '\local_mchelpers\event\role_event_observer::role_assigned',
    // ],

    // Role unassigned from user.
    // [
    //     'eventname' => '\core\event\role_unassigned',
    //     'callback'  => '\local_mchelpers\event\role_event_observer::role_unassigned',
    // ],

    // Role capability changed.
    // [
    //     'eventname' => '\core\event\role_capability_changed',
    //     'callback'  => '\local_mchelpers\event\role_event_observer::role_capability_changed',
    // ],

    // =========================================================================
    // ENROLLMENT EVENTS
    // =========================================================================

    // User enrollment created.
    // [
    //     'eventname' => '\core\event\user_enrolment_created',
    //     'callback'  => '\local_mchelpers\event\enrolment_event_observer::user_enrolment_created',
    // ],

    // User enrollment updated.
    // [
    //     'eventname' => '\core\event\user_enrolment_updated',
    //     'callback'  => '\local_mchelpers\event\enrolment_event_observer::user_enrolment_updated',
    // ],

    // User enrollment deleted.
    // [
    //     'eventname' => '\core\event\user_enrolment_deleted',
    //     'callback'  => '\local_mchelpers\event\enrolment_event_observer::user_enrolment_deleted',
    // ],

];
