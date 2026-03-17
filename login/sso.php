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
 * SSO (Single Sign-On) login endpoint for external system integration.
 *
 * This script provides a bridge between Moodle and external systems
 * (WordPress, Drupal, custom PHP applications, Node.js, Python, etc.)
 * for seamless single sign-on functionality.
 *
 * Features:
 * - Processes encrypted session data from external systems (POST requests)
 * - Handles user login with session establishment
 * - Handles user logout with session cleanup
 * - Supports both HTML redirects and JSON API responses
 * - Uses AES-128-CTR encryption for secure data transmission
 *
 * Usage:
 * - POST with 'mch_data' parameter: Stores encrypted session data
 * - GET with 'login_id' parameter: Initiates login flow
 * - GET with 'logout_id' parameter: Initiates logout flow
 *
 * @package    local_mchelpers
 * @copyright  2026 Santosh Magar <https://santoshmagar.com.np/>
 * @author     Santosh Magar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       /local/mchelpers/login/sso.php
 */

use local_mchelpers\local\handler\sso_handler;

require_once('../../../config.php');

global $CFG, $SESSION, $DB, $USER;

// =============================================================================
// SESSION DATA STORAGE (POST with mch_data)
// =============================================================================
// Accepts POST requests with encrypted session data from external systems.
// The external system sends encrypted user data that gets stored in Moodle
// user preferences for later verification during login.
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mchdata = required_param('mch_data', PARAM_RAW);
    if (!empty($mchdata)) {
        // Process session data using handler.
        $result = sso_handler::process_session_data($mchdata);
        sso_handler::json_response($result);
    }
    sso_handler::json_response(
        [
            'success' => false,
            'error' => 'mch_data is empty',
            'moodle_user_id' => 0
        ]
    );
}

// =============================================================================
// LOGIN FLOW
// =============================================================================
// Handles user login by verifying the one-time hash from the external system.
// Establishes a Moodle session for the authenticated user and redirects
// back to the external system.
// =============================================================================
$userid = optional_param('login_id', 0, PARAM_INT);
if (!empty($userid) && $userid !== 0) {

    // Process login using handler.
    $result = sso_handler::process_login($userid);
    if ($result['status'] && !empty($result['redirect'])) {
        redirect($result['redirect']);
    }
}

// =============================================================================
// LOGOUT FLOW
// =============================================================================
// Handles user logout by invalidating the Moodle session and removing
// stored SSO session data. Redirects back to the external system.
// =============================================================================
$userid = optional_param('logout_id', 0, PARAM_INT);
if (!empty($userid) && $userid !== 0) {
    // Process logout using handler.
    $result = sso_handler::process_logout($userid);
    if ($result['status'] && !empty($result['redirect'])) {
        redirect($result['redirect']);
    }
}

// =============================================================================
// REDIRECT TO EXTERNAL SITE WITH ERROR MESSAGE
// =============================================================================
// Default fallback: redirect to external site with any error messages.
// This handles cases where the request doesn't match any of the above flows.
// =============================================================================
$externalurl = sso_handler::get_external_site_url($tempurl);
if (isset($result['message']) && $result['message']) {
    if (strpos($externalurl, '?') !== false) {
        $externalurl .= '&sso_error_message=' . $result['message'];
    } else {
        $externalurl .= '?sso_error_message=' . $result['message'];
    }
}

redirect($externalurl);
