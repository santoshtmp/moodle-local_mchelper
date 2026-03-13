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
 * Generic SSO Login endpoint for external system integration.
 *
 * This file handles Single Sign-On authentication between Moodle and any external system
 * (WordPress, Drupal, custom PHP apps, Node.js, Python, etc.) using encrypted data exchange.
 *
 * Features:
 * - Encrypted data payload from external systems (POST with mch_data)
 * - Login flow with hash verification (login_id parameter)
 * - Logout flow with hash verification (logout_id parameter)
 * - Course-specific redirects
 *
 * Parameters:
 * - mch_data: Encrypted user data from external system (POST)
 * - login_id: User ID for login flow
 * - logout_id: User ID for logout flow
 * - veridy_code: Verification hash for security
 * - redirect_url: URL to redirect on success/failure
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', false);
define('REQUIRE_CORRECT_ACCESS', false);

require_once(__DIR__ . '/../../../config.php');

global $CFG, $DB, $USER, $SESSION;

// Logon may somehow modify this.
$SESSION->wantsurl = $CFG->wwwroot;

// Get external site URL from referer or config.
$temp_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

// =============================================================================
// 1. SESSION DATA STORAGE (POST with mch_data)
// =============================================================================
// Receives encrypted user data from external system, decrypts and stores it.
$mch_data = optional_param('mch_data', '', PARAM_RAW);
if (!empty($mch_data)) {
    $shared_secret = check_shared_secret_is_set();

    if ($shared_secret == '') {
        echo "Sorry, SSO integration has not yet been configured. Please contact the Moodle administrator for details";
        die();
    }

    $rawdata = $mch_data;
    $userdata = decrypt_string($rawdata, $shared_secret);
    $user_id = get_key_value($userdata, 'moodle_user_id');

    // Store session data.
    $key = 'local_mchelpers_sso_session_data';
    set_sso_user_session($user_id, $key, $mch_data);

    // Clean up POST data.
    unset($_POST['mch_data']);
    die();
}

// =============================================================================
// 2. CHECK SHARED SECRET CONFIGURATION
// =============================================================================
/**
 * Check if the shared secret key is configured.
 * Redirects to external site with error if not configured.
 *
 * @return string Shared secret key
 */
function check_shared_secret_is_set() {
    global $temp_url;

    $shared_secret = get_config('local_mchelpers', 'shared_secret');

    if (empty($shared_secret)) {
        $external_url = get_external_site_url_from_referer($temp_url);
        if (strpos($external_url, '?') !== false) {
            $external_url .= '&mch_sso_error=not_configured';
        } else {
            $external_url .= '?mch_sso_error=not_configured';
        }
        redirect($external_url);
        return '';
    }

    return $shared_secret;
}

/**
 * Extract external site URL from referer.
 *
 * @param string $referer Referer URL
 * @return string External site base URL
 */
function get_external_site_url_from_referer($referer) {
    if (empty($referer)) {
        $referer = get_config('local_mchelpers', 'external_site_url');
    }
    if (empty($referer)) {
        global $CFG;
        $referer = $CFG->wwwroot;
    }
    return $referer;
}

// Set default temp_url if not set.
if ($temp_url == null) {
    $temp_url = get_config('local_mchelpers', 'external_site_url');
}

if ($temp_url == "") {
    $temp_url = $CFG->wwwroot;
}

// Get shared secret.
$shared_secret = get_config('local_mchelpers', 'shared_secret');

if (empty($shared_secret)) {
    $external_url = get_external_site_url_from_referer($temp_url);
    if (strpos($external_url, '?') !== false) {
        $external_url .= '&mch_sso_error=not_configured';
    } else {
        $external_url .= '?mch_sso_error=not_configured';
    }
    redirect($external_url);
    return;
}

// =============================================================================
// 3. ENCRYPTION/DECRYPTION HELPER FUNCTIONS
// =============================================================================
/**
 * Decrypts incoming base64-encoded encrypted data.
 * Uses AES-128-CTR encryption with shared secret.
 *
 * @param string $base64 Base64 encoded encrypted data
 * @param string $key Shared secret key
 * @return string Decrypted data (key=value pairs)
 */
function decrypt_string($base64, $key) {
    if (!$base64) {
        return '';
    }

    // Manual de-hack URL formatting (replace URL-safe base64 chars).
    $data = str_replace(array('-', '_'), array('+', '/'), $base64);

    // Base64 length must be evenly divisible by 4.
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }

    $crypttext = base64_decode($data);

    // Extract IV and encrypted data (format: encrypted_data::iv).
    if (preg_match("/^(.*)::(.*)$/", $crypttext, $regs)) {
        list(, $crypttext, $enc_iv) = $regs;
        $enc_method = 'AES-128-CTR';
        $enc_key = openssl_digest($key, 'SHA256', true);
        $decrypted_token = openssl_decrypt($crypttext, $enc_method, $enc_key, 0, hex2bin($enc_iv));
    }

    return trim($decrypted_token);
}

/**
 * Encrypts data for sending to external systems.
 * Uses AES-128-CTR encryption with shared secret.
 *
 * @param string $data Data to encrypt (key=value pairs)
 * @param string $key Shared secret key
 * @return string Base64 encoded encrypted data (URL-safe)
 */
function encrypt_string($data, $key) {
    $enc_method = 'AES-128-CTR';
    $enc_key = openssl_digest($key, 'SHA256', true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($enc_method));
    $encrypted = openssl_encrypt($data, $enc_method, $enc_key, 0, $iv);
    $result = base64_encode($encrypted . '::' . bin2hex($iv));
    // URL-safe base64.
    $result = str_replace(array('+', '/', '='), array('-', '_', ''), $result);
    return $result;
}

/**
 * Parses key=value pairs from string.
 *
 * @param string $string String containing key=value&key=value pairs
 * @param string $key Key to retrieve
 * @return string Value for the key
 */
function get_key_value($string, $key) {
    $list = explode('&', str_replace('&amp;', '&', $string));
    foreach ($list as $pair) {
        $item = explode('=', $pair);
        if (strtolower($key) == strtolower($item[0])) {
            return urldecode($item[1]);
        }
    }
    return '';
}

// =============================================================================
// 4. LOGOUT FLOW
// =============================================================================
$user_id = optional_param('logout_id', 0, PARAM_INT);
if (!empty($user_id) && $user_id !== 0) {
    $sess_key = 'local_mchelpers_sso_session_data';

    $record = get_sso_user_session($user_id, $sess_key);
    $rawdata = isset($record) ? $record : '';
    $userdata = decrypt_string($rawdata, $shared_secret);
    $hash = get_key_value($userdata, 'one_time_hash');

    remove_sso_user_session($user_id, $sess_key);

    $veridy_code = optional_param('veridy_code', '', PARAM_RAW);
    if (!empty($veridy_code) && $hash === $veridy_code) {
        $logout_redirect = get_key_value($userdata, 'logout_redirect');
        if (empty($logout_redirect)) {
            redirect($temp_url);
        }
        require_logout();
        redirect($logout_redirect);
    } else {
        $external_url = get_config('local_mchelpers', 'external_site_url');
        $external_url = empty($external_url) ? $CFG->wwwroot : $external_url;
        redirect($external_url);
    }
}

// =============================================================================
// 5. LOGIN FLOW
// =============================================================================
$user_id = optional_param('login_id', 0, PARAM_INT);
if (!empty($user_id) && $user_id !== 0) {
    $temp_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    $sess_key = 'local_mchelpers_sso_session_data';

    $record = get_sso_user_session($user_id, $sess_key);
    $rawdata = isset($record) ? $record : '';

    remove_sso_user_session($user_id, $sess_key);

    $userdata = decrypt_string($rawdata, $shared_secret);
    $moodle_user_id = get_key_value($userdata, 'moodle_user_id');
    $hash = get_key_value($userdata, 'one_time_hash');

    $veridy_code = optional_param('veridy_code', '', PARAM_RAW);
    if (!empty($veridy_code) && $hash === $veridy_code) {
        if (empty($moodle_user_id)) {
            $external_url = get_external_site_url_from_referer($temp_url);
            if (strpos($external_url, '?') !== false) {
                $external_url .= '&mch_sso_error=invalid_user';
            } else {
                $external_url .= '?mch_sso_error=invalid_user';
            }
            redirect($external_url);
            return;
        }

        $login_redirect = get_key_value($userdata, 'login_redirect');

        // Get course ID from login_redirect if present.
        $course_id = 0;
        if (strpos($login_redirect, 'course/view.php?id=') !== false) {
            $course_id = explode('course/view.php?id=', $login_redirect)[1];
        }

        if ($course_id != 0) {
            $course = $DB->record_exists('course', array('id' => $course_id));
            // If course is not available then redirect to site home page.
            if (empty($course)) {
                $login_redirect = $CFG->wwwroot;
            }
        }

        if ($DB->record_exists('user', array('id' => $moodle_user_id))) {
            // Get user data.
            $user = get_complete_user_data('id', $moodle_user_id);
        } else {
            $external_url = get_external_site_url_from_referer($temp_url);
            if (strpos($external_url, '?') !== false) {
                $external_url .= '&mch_sso_error=user_not_found';
            } else {
                $external_url .= '?mch_sso_error=user_not_found';
            }
            redirect($external_url);
            return;
        }

        // Authenticate and set up active session.
        // Try edwiserbridge auth plugin first if available.
        $authplugin = get_auth_plugin('edwiserbridge');
        if ($authplugin && method_exists($authplugin, 'user_login')) {
            if ($authplugin->user_login($user->username, $user->password)) {
                $user->loggedin = true;
                $user->site = $CFG->wwwroot;
                complete_user_login($user);
            } else {
                // Fallback: just complete login without password check (SSO already verified).
                $user->loggedin = true;
                $user->site = $CFG->wwwroot;
                complete_user_login($user);
            }
        } else {
            // Fallback: just complete login without password check (SSO already verified).
            $user->loggedin = true;
            $user->site = $CFG->wwwroot;
            complete_user_login($user);
        }

        if (!empty($login_redirect)) {
            redirect($login_redirect);
        }

        $course_id = get_key_value($userdata, 'moodle_course_id');
        if (!empty($course_id)) {
            $SESSION->wantsurl = $CFG->wwwroot . '/course/view.php?id=' . $course_id;
        }
    } else {
        $external_url = get_config('local_mchelpers', 'external_site_url');
        $external_url = empty($external_url) ? $CFG->wwwroot : $external_url;
        redirect($external_url);
    }
}

redirect($SESSION->wantsurl);

// =============================================================================
// 6. SESSION MANAGEMENT FUNCTIONS
// =============================================================================
/**
 * Get stored session data for user.
 *
 * @param int $user_id User ID
 * @param string $sess_key Session key name
 * @return string Session data
 */
function get_sso_user_session($user_id, $sess_key) {
    return get_user_preferences($sess_key, '', $user_id);
}

/**
 * Set session data for user.
 *
 * @param int $user_id User ID
 * @param string $sess_key Session key name
 * @param string $sso_data Session data (encrypted)
 */
function set_sso_user_session($user_id, $sess_key, $sso_data) {
    set_user_preference($sess_key, $sso_data, $user_id);
}

/**
 * Remove session data for user.
 *
 * @param int $user_id User ID
 * @param string $sess_key Session key name
 */
function remove_sso_user_session($user_id, $sess_key) {
    unset_user_preference($sess_key, $user_id);
}

/**
 * Clean up POST data.
 */
function unset_post_method() {
    unset($_POST['mch_data']);
    unset($_POST['redirect_to']);
    unset($_POST['next_user_id']);
}
