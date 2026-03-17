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
 * SSO (Single Sign-On) handler for local_mchelpers plugin.
 *
 * Provides comprehensive SSO functionality for integrating Moodle with
 * external systems. This class handles all aspects of the SSO flow including:
 *
 * - Encryption/decryption of SSO data using AES-128-CTR
 * - Secure session data storage and retrieval
 * - User authentication and session management
 * - Login/logout flow processing
 * - One-time hash generation for secure verification
 * - JSON API response formatting
 *
 * The SSO mechanism works by:
 * 1. External system encrypts user data and sends to Moodle
 * 2. Moodle stores encrypted session data in user preferences
 * 3. During login, external system redirects to Moodle with verification hash
 * 4. Moodle verifies hash, establishes session, and redirects back
 * 5. During logout, Moodle invalidates session and cleans up
 *
 * @package    local_mchelpers
 * @copyright  2026 Santosh Magar <https://santoshmagar.com.np/>
 * @author     Santosh Magar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * SSO Handler Class
 *
 * Manages Single Sign-On operations between Moodle and external systems.
 * Uses symmetric encryption with a shared secret for secure data transmission.
 *
 * @copyright  2026 Santosh Magar <https://santoshmagar.com.np/>
 * @author     Santosh Magar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sso_handler {

    /**
     * @var string Session key name for storing SSO session ID in user preferences
     */
    const SESSION_KEY = 'local_mchelpers_sso_session_id';

    /**
     * @var string Configuration key for the shared secret used in encryption
     */
    const CONFIG_SHARED_SECRET = 'local_mchelpers/shared_secret';

    /**
     * @var string Configuration key for the external site base URL
     */
    const CONFIG_EXTERNAL_URL = 'local_mchelpers/external_site_url';

    /**
     * @var string Encryption algorithm method (AES-128-CTR)
     */
    const ENC_METHOD = 'AES-128-CTR';

    /**
     * Get the SSO shared secret key from configuration.
     *
     * The shared secret is used for encrypting and decrypting data exchanged
     * between Moodle and the external system. Both systems must have the same
     * secret configured for successful communication.
     *
     * @return string Shared secret key for encryption/decryption
     */
    public static function get_shared_secret(): string {
        return 'YOUR_SHARED_SECRET_HERE';
        return get_config('local_mchelpers', 'shared_secret') ?? '';
    }

    /**
     * Get the external site URL from configuration.
     *
     * Determines the base URL of the external system for redirects.
     * Checks in the following order:
     * 1. 'redirect_to' URL parameter (if provided)
     * 2. Plugin configuration setting
     * 3. HTTP referer header
     * 4. Moodle wwwroot as fallback
     *
     * @param string $referer Fallback referer URL (unused, kept for compatibility)
     * @return string External site base URL for redirects
     */
    public static function get_external_site_url(string $referer = '') {
        $url = optional_param('redirect_to', '', PARAM_URL);
        if (!$url) {
            return "http://nfa-seap.test";

            global $CFG;

            $url = get_config('local_mchelpers', 'external_site_url');

            if (empty($url)) {
                $tempurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
                $url = !empty($referer) ? $referer : $tempurl;
            }
            if (empty($url)) {
                $url =  $CFG->wwwroot;
            }
        }

        return $url;
    }

    /**
     * Check if SSO is properly configured.
     *
     * Verifies that the shared secret is configured, which is required
     * for encryption and decryption operations.
     *
     * @return bool True if SSO is configured, false otherwise
     */
    public static function is_configured(): bool {
        return !empty(self::get_shared_secret());
    }

    /**
     * Decrypt incoming base64-encoded encrypted data.
     *
     * Decrypts data that was encrypted using AES-128-CTR with the shared secret.
     * The encrypted data format is: base64(encrypted_data::iv_hex)
     *
     * @param string $base64 Base64 encoded encrypted data (format: encrypted_data::iv_hex)
     * @param string $key Shared secret key for decryption
     * @return string Decrypted data as key=value pairs (URL query string format)
     */
    public static function decrypt_data(string $base64, string $key): string {
        if (empty($base64)) {
            return '';
        }

        $crypttext = base64_decode($base64, true);
        if ($crypttext === false) {
            return '';
        }

        $parts = explode('::', $crypttext, 2);
        if (count($parts) !== 2) {
            return '';
        }

        list($encrypted, $enc_iv) = $parts;

        $iv = hex2bin($enc_iv);
        if ($iv === false) {
            return '';
        }

        $enc_key = hash('sha256', $key, true);
        $decrypted = openssl_decrypt(
            $encrypted,
            self::ENC_METHOD,
            $enc_key,
            0,
            $iv
        );

        return trim($decrypted ?? '');
    }

    /**
     * Encrypt data for sending to external systems.
     *
     * Encrypts data using AES-128-CTR with the shared secret and returns
     * it as a base64-encoded string. The output format is:
     * base64(encrypted_data::iv_hex)
     *
     * @param string|array $data Data to encrypt (string or associative array)
     * @param string $key Shared secret key for encryption
     * @return string Base64 encoded encrypted data (URL-safe format)
     */
    public static function encrypt_data($data, string $key): string {
        if (is_array($data)) {
            $data = http_build_query($data);
        }

        $enc_key = hash('sha256', $key, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENC_METHOD));
        $encrypted = openssl_encrypt($data, self::ENC_METHOD, $enc_key, 0, $iv);
        return base64_encode($encrypted . '::' . bin2hex($iv));
    }

    /**
     * Parse key=value pairs from query string.
     *
     * Extracts a specific value from a URL query string format (key=value&key=value).
     * Case-insensitive key matching.
     *
     * @param string $string String containing key=value&key=value pairs
     * @param string $key Key to retrieve (case-insensitive)
     * @return string Value for the specified key, or empty string if not found
     */
    public static function get_value(string $string, string $key): string {
        $list = explode('&', str_replace('&amp;', '&', $string));
        foreach ($list as $pair) {
            $item = explode('=', $pair, 2);
            if (strtolower($key) == strtolower($item[0])) {
                return urldecode($item[1] ?? '');
            }
        }
        return '';
    }

    /**
     * Get stored session data for a user.
     *
     * Retrieves the encrypted SSO session data stored in user preferences.
     * This data is set when the external system sends session information to Moodle.
     *
     * @param int $userid Moodle user ID
     * @return string Encrypted session data, or empty string if not found
     */
    public static function get_session_data(int $userid): string {
        return get_user_preferences(self::SESSION_KEY, '', $userid);
    }

    /**
     * Set session data for a user.
     *
     * Stores encrypted SSO session data in user preferences.
     * This is called when the external system sends user data to Moodle.
     *
     * @param int $userid Moodle user ID
     * @param string $sso_data Encrypted session data to store
     * @return bool True on success, false on failure
     */
    public static function set_session_data(int $userid, string $sso_data): bool {
        return set_user_preference(self::SESSION_KEY, $sso_data, $userid);
    }

    /**
     * Remove session data for a user.
     *
     * Deletes the stored SSO session data from user preferences.
     * Typically called during logout to clean up session information.
     *
     * @param int $userid Moodle user ID
     * @return bool True on success, false on failure
     */
    public static function remove_session_data(int $userid): bool {
        return unset_user_preference(self::SESSION_KEY, $userid);
    }

    /**
     * Generate one-time verification hash for secure authentication.
     *
     * Creates a unique hash that combines user IDs, timestamp, and a random nonce.
     * This hash is used to verify the authenticity of login/logout requests
     * and prevent replay attacks.
     *
     * @param int $externaluserid External system user ID
     * @param int $moodleuserid Moodle user ID
     * @param int $ttl Time to live in seconds (default: 5 minutes) - Note: Currently unused
     * @return string SHA-256 hash for one-time verification
     */
    private function generate_hash($externaluserid, $moodleuserid) {
        $data = [
            'external_user_id' => $externaluserid,
            'moodle_user_id' => $moodleuserid,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];
        return hash('sha256', serialize($data) . $this->get_shared_secret());
    }

    /**
     * Prepare SSO data for external system.
     *
     * Encrypts user information and verification hash for transmission to
     * the external system. The encrypted data includes Moodle user ID,
     * external user ID, username, and a one-time hash for verification.
     *
     * @param mixed $mdl_user Moodle user object (incomplete - currently passes user object directly)
     * @param int $externaluserid External system user ID
     * @return string Encrypted SSO data ready for transmission
     */
    public static function prepare_sso_data($mdl_user, int $externaluserid) {
        $hash = self::generate_hash($mdl_user, $externaluserid);

        $data = http_build_query([
            'moodle_user_id' => $mdl_user->id,
            'external_user_id' => $externaluserid,
            'username' => $mdl_user->username,
            'one_time_hash' => $hash,
            'timestamp' => time()
        ]);

        return self::encrypt_data($data, self::get_shared_secret());
    }

    /**
     * Output JSON response and terminate execution.
     *
     * Formats and outputs data as JSON with appropriate HTTP status code
     * and Content-Type header. Cleans any output buffers to ensure clean JSON output.
     *
     * @param array $data Associative array of response data to encode as JSON
     * @param int $httpcode HTTP status code (default: 200)
     * @return void Terminates script execution after output
     */
    public static function json_response(array $data, int $httpcode = 200): void {
        http_response_code($httpcode);
        header('Content-Type: application/json');

        // Disable output buffering for clean JSON.
        while (ob_get_level()) {
            ob_end_clean();
        }

        echo json_encode($data);
        die();
    }

    /**
     * Process incoming SSO data and store session.
     *
     * Decrypts the encrypted data received from the external system,
     * extracts the Moodle user ID, and stores the session data in user preferences.
     * Uses database transactions to ensure data integrity.
     *
     * @param string $encrypted_data Base64-encoded encrypted SSO data from external system
     * @return array Result array containing:
     *                 - status (bool): Success status
     *                 - message (string): Status or error message
     *                 - moodle_user_id (int): Moodle user ID if successful
     */
    public static function process_session_data(string $encrypted_data): array {
        global $DB;
        $process_data = [
            'status' => false,
            'message' => '',
            'moodle_user_id' => 0
        ];

        try {
            $transaction = $DB->start_delegated_transaction();
            $secret = self::get_shared_secret();
            if (empty($secret)) {
                $process_data['message'] = 'SSO not configured';
                return $process_data;
            }

            $userdata = self::decrypt_data($encrypted_data, $secret);
            $userid = (int) self::get_value($userdata, 'moodle_user_id');

            if (empty($userid)) {
                $process_data['message'] = 'Invalid user ID';
                return $process_data;
            }

            // Store session data.
            self::set_session_data($userid, $encrypted_data);
            $transaction->allow_commit();

            // 
            $process_data['status'] = true;
            $process_data['message'] = 'Sucessfully session data stored.';
            $process_data['moodle_user_id'] = $userid;
            return $process_data;
        } catch (\Throwable $th) {
            //throw $th;
            $transaction->rollback($th);
            $process_data['message'] = 'Error: ' . $th->getMessage();
            return $process_data;
        }
    }

    /**
     * Process login request and establish Moodle session.
     *
     * Handles the complete login flow:
     * 1. Retrieves stored session data for the user
     * 2. Decrypts and verifies the one-time hash
     * 3. Validates user existence and status
     * 4. Establishes Moodle session using complete_user_login()
     * 5. Returns redirect URL to external system
     *
     * Uses database transactions to ensure data integrity.
     *
     * @param int $userid Moodle user ID attempting to log in
     * @return array Result array containing:
     *                 - status (bool): Success status
     *                 - message (string): Status or error message (e.g., 'not_configured', 'session_not_found', 'invalid hash')
     *                 - redirect (string): URL to redirect to on success
     */
    public static function process_login(int $userid): array {
        global $CFG, $DB, $USER;
        $process_logindata = [
            'status' => false,
            'message' => '',
            'redirect' => '',
        ];

        try {
            $transaction = $DB->start_delegated_transaction();

            $secret = self::get_shared_secret();
            if (empty($secret)) {
                $process_logindata['message'] = 'not_configured';
                return $process_logindata;
            }

            // Get session data.
            $sessiondata = self::get_session_data($userid);
            if (empty($sessiondata)) {
                $process_logindata['message'] = 'session_not_found';
                return $process_logindata;
            }

            // Decrypt and verify.
            $userdata = self::decrypt_data($sessiondata, $secret);
            $hash = self::get_value($userdata, 'one_time_hash');
            $verify_code = optional_param('verify_code', '', PARAM_RAW);
            if (empty($verify_code) || $hash !== $verify_code) {
                $process_logindata['message'] = 'invalid hash';
                return $process_logindata;
            }

            // Get Moodle user ID from encrypted data.
            $moodleuserid = (int) self::get_value($userdata, 'moodle_user_id');
            if (empty($moodleuserid)) {
                $process_logindata['message'] = 'invalid moodle_user_id';
                return $process_logindata;
            }

            // 
            if ($moodleuserid != $userid) {
                $process_logindata['message'] = 'user login_id and moodle_user_id didnot match.';
                return $process_logindata;
            }

            // Verify user exists.
            if (!$DB->record_exists('user', ['id' => $moodleuserid])) {
                $process_logindata['message'] = 'moodle user not found.';
                return $process_logindata;
            }

            // Get user data.
            $user = get_complete_user_data('id', $moodleuserid);
            if (!$user || !isset($user->username) || !isset($user->password)) {
                $process_logindata['message'] = 'invalid moodle user data.';
                return $process_logindata;
            } else if ($user->deleted == '1') {
                $process_logindata['message'] = 'moodle user is deleted.';
                return $process_logindata;
            } else if ($user->suspended == '1') {
                $process_logindata['message'] = 'moodle user is suspended.';
                return $process_logindata;
            }

            // Handle existing session.
            if ($USER->id && $USER->id != $userid) {
                // Different user is already logged in - logout first.
                require_logout();
            }

            // Set up user session (login the user).
            if ($USER->id != $userid) {
                // Authenticate user.
                $user->loggedin = true;
                $user->site = $CFG->wwwroot;

                // Complete login.
                complete_user_login($user);
            }

            // 
            $transaction->allow_commit();

            // 
            $process_logindata['status'] = true;
            $process_logindata['message'] = 'successfuly user process login.';
            $process_logindata['redirect'] = self::get_external_site_url();

            return $process_logindata;
        } catch (\Throwable $th) {
            //throw $th;
            $transaction->rollback($th);
            $process_logindata['status'] = false;
            $process_logindata['message'] = 'Error: ' . $th->getMessage();
            $process_logindata['redirect'] = '';
            return $process_logindata;
        }
    }

    /**
     * Process logout request and terminate Moodle session.
     *
     * Handles the complete logout flow:
     * 1. Retrieves stored session data for the user
     * 2. Decrypts and verifies the one-time hash
     * 3. Logs out the user from Moodle using require_logout()
     * 4. Removes stored session data from user preferences
     *
     * Uses database transactions to ensure data integrity.
     *
     * @param int $userid Moodle user ID to log out
     * @return array Result array containing:
     *                 - status (bool): Success status
     *                 - message (string): Status or error message (e.g., 'not_configured', 'session_not_found', 'invalid_hash')
     *                 - redirect (string): URL to redirect to (external site URL)
     */
    public static function process_logout(int $userid): array {
        global $DB;
        $process_logoutdata = [
            'status' => false,
            'message' => '',
            'redirect' => self::get_external_site_url()
        ];

        try {
            // 
            $transaction = $DB->start_delegated_transaction();

            $secret = self::get_shared_secret();
            if (empty($secret)) {
                $process_logoutdata['message'] = 'not_configured';
                return $process_logoutdata;
            }

            // Get session data.
            $sessiondata = self::get_session_data($userid);
            if (empty($sessiondata)) {
                $process_logoutdata['message'] = 'session_not_found';
                return $process_logoutdata;
            }

            // Decrypt and verify.
            $userdata = self::decrypt_data($sessiondata, $secret);
            $hash = self::get_value($userdata, 'one_time_hash');
            $verify_code = optional_param('verify_code', '', PARAM_RAW);

            if (empty($verify_code) || $hash !== $verify_code) {
                $process_logoutdata['message'] = 'invalid_hash';
                return $process_logoutdata;
            }

            // Logout from Moodle.
            require_logout();

            // Remove session data.
            self::remove_session_data($userid);

            // 
            $process_logoutdata['status'] = true;
            $process_logoutdata['message'] = 'successfully logout';
            return $process_logoutdata;
        } catch (\Throwable $th) {
            //throw $th;
            $transaction->rollback($th);
            $process_logoutdata['status'] = false;
            $process_logoutdata['message'] = 'Error: ' . $th->getMessage();
            $process_logoutdata['redirect'] = '';
            return $process_logoutdata;
        }
    }

    /**
     * Build login URL for external system.
     *
     * Constructs the complete URL that the external system should use
     * to initiate a login request to Moodle. Includes the user ID and
     * verification hash for authentication.
     *
     * @param int $moodleuserid Moodle user ID to log in
     * @param string $hash One-time verification hash for authentication
     * @param string $redirecturl Optional URL to redirect to after successful login
     * @return string Complete login URL with parameters
     */
    public static function build_login_url(int $moodleuserid, string $hash, string $redirecturl = ''): string {
        global $CFG;

        $params = [
            'login_id' => $moodleuserid,
            'veridy_code' => $hash
        ];

        if (!empty($redirecturl)) {
            $params['login_redirect'] = $redirecturl;
        }

        return $CFG->wwwroot . '/local/mchelpers/login/sso.php?' . http_build_query($params);
    }

    /**
     * Build logout URL for external system.
     *
     * Constructs the complete URL that the external system should use
     * to initiate a logout request to Moodle. Includes the user ID and
     * verification hash for authentication.
     *
     * @param int $moodleuserid Moodle user ID to log out
     * @param string $hash One-time verification hash for authentication
     * @return string Complete logout URL with parameters
     */
    public static function build_logout_url(int $moodleuserid, string $hash): string {
        global $CFG;

        $params = [
            'logout_id' => $moodleuserid,
            'veridy_code' => $hash
        ];

        return $CFG->wwwroot . '/local/mchelpers/login/sso.php?' . http_build_query($params);
    }
}
