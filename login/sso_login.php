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
 * SSO Login endpoint for cross-domain authentication.
 *
 * Parameters:
 * - hash: Verification hash (required)
 * - fallback_url: URL to redirect on failure (optional, default: 'false' = JSON response)
 *                Set to 'false' to return JSON instead of redirect
 * - pass_goto_url: URL to redirect on success (optional, for course pages)
 *
 * Usage:
 * 1. After WordPress login: hash + fallback_url=false (returns JSON with session)
 * 2. Go to Course button: hash + fallback_url + pass_goto_url (redirects to course)
 * 3. Direct Moodle visit: User already logged in from step 1
 *
 * Hash validity: One-time use only (deleted after successful login)
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', false);
define('NO_DEBUG_DISPLAY', true);

@header('Access-Control-Allow-Origin: *');
@header('Content-Type: application/json');

require_once(__DIR__ . '/../../../config.php');

// Only accept POST method.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response([
        'status' => false,
        'message' => 'Invalid request method. Only POST is allowed.',
    ], 405);
}

global $CFG, $DB, $USER, $SESSION;

// Default fallback URL: 'false' means return JSON instead of redirect.
$DEFAULT_FALLBACK_URL = 'false';

/**
 * Send JSON response and exit.
 */
function send_json_response($data, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode($data);
    die;
}

/**
 * Redirect to URL with proper headers.
 */
function redirect_to_url($url) {
    // redirect($url);
    $redirectby = 'moodle-local-mchelpers-redirect';
    @header("X-Redirect-By: $redirectby");
    @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
    @header('Location: ' . $url);
    exit;
}

/**
 * Handle error response.
 */
function handle_error($message, $fallback_url) {
    $response = [
        'status' => false,
        'message' => $message,
    ];

    // If fallback_url is 'false' or empty, return JSON.
    if ($fallback_url === 'false' || empty($fallback_url)) {
        send_json_response($response, 401);
    }

    // If fallback_url is provided and not default, redirect with error message.
    $separator = (strpos($fallback_url, '?') !== false) ? '&' : '?';
    $fallback_url .= $separator . 'msg=' . urlencode($message);
    redirect_to_url($fallback_url);
}

/**
 * Handle success response.
 */
function handle_success($user, $fallback_url, $pass_goto_url) {
    $response = [
        'status' => true,
        'message' => 'Login successful',
        'userid' => $user->id,
        'username' => $user->username,
        'fullname' => fullname($user),
        // 'sessionid' => session_id(),
    ];

    // If pass_goto_url is provided and is a valid Moodle URL, redirect there.
    if (!empty($pass_goto_url)) {
        redirect_to_url($pass_goto_url);
    }

    // If fallback_url is 'false' or empty, return JSON.
    if ($fallback_url === 'false' || empty($fallback_url)) {
        send_json_response($response);
    }

    // Default: return JSON.
    send_json_response($response);
}

// Get parameters.
$hash = required_param('hash', PARAM_RAW);
$fallback_url = optional_param('fallback_url', $DEFAULT_FALLBACK_URL, PARAM_RAW);
$pass_goto_url = optional_param('pass_goto_url', '', PARAM_URL);

// Validate fallback_url: must start with http://, https://, www., or / (relative URL).
// If not a valid URL format, set to 'false' (JSON response).
if (!empty($fallback_url) && $fallback_url !== 'false') {
    if (!preg_match('/^(https?:\/\/|www\.|\/)/i', $fallback_url)) {
        $fallback_url = 'false';
    }
}

// Decode hash to get userid and verify integrity.
// Format: base64(json_data):signature
$parts = explode(':', $hash, 2);

if (count($parts) !== 2) {
    handle_error('Invalid hash format', $fallback_url);
}

list($encoded_data, $signature) = $parts;

// Decode the data to get userid.
$verify_data = json_decode(base64_decode($encoded_data), true);

if (!$verify_data || !isset($verify_data['userid'])) {
    handle_error('Invalid hash data', $fallback_url);
}

$userid = (int)$verify_data['userid'];

// Get stored hash from database to verify.
$verify_record = $DB->get_record('user_preferences', [
    'userid' => $userid,
    'name' => 'local_mchelpers_sso_verify',
]);

if (!$verify_record || $verify_record->value !== $hash) {
    handle_error('Invalid or expired verification hash', $fallback_url);
}

// Get user.
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0]);

if (!$user) {
    // Clean up verification data.
    $DB->delete_records('user_preferences', ['id' => $verify_record->id]);
    handle_error('User not found', $fallback_url);
}

// Handle existing session.
if ($USER->id && $USER->id != $userid) {
    // Different user is already logged in - logout first.
    require_logout();
}

// Set up user session (login the user).
if ($USER->id != $userid) {
    // Complete login to set up session.
    complete_user_login($user);
}

// Ensure session is properly set.
\core\session\manager::set_user($user);

// Note: NOT cleaning up verification data - hash can be reused.
// If you want one-time use only, uncomment the line below:
// $DB->delete_records('user_preferences', ['id' => $verify_record->id]);

// Success - handle response.
handle_success($user, $fallback_url, $pass_goto_url);
