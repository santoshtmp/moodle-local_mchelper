<?php
/**
 * WordPress Integration for Moodle SSO
 *
 * Place this code in your WordPress plugin or theme's functions.php
 *
 * Features:
 * - When user logs into WordPress, automatically redirect to Moodle with SSO
 * - Encrypted data exchange using shared secret (AES-128-CTR)
 * - Works with "Go to Moodle" links that auto-login users
 *
 * @package Moodle_SSO_Integration
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration - Update these values to match Moodle settings
 */
define('MOODLE_SSO_ENABLED', true);
define('MOODLE_SSO_URL', 'https://moodle.yoursite.com'); // Your Moodle URL
define('MOODLE_SSO_SHARED_SECRET', 'your-shared-secret-key-at-least-32-chars'); // Must match Moodle config

/**
 * Encrypts data for sending to Moodle.
 * Uses AES-128-CTR encryption with shared secret.
 */
function mch_encrypt_string($data, $key) {
    $enc_method = 'AES-128-CTR';
    $enc_key = hash('sha256', $key, true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($enc_method));
    $encrypted = openssl_encrypt($data, $enc_method, $enc_key, 0, $iv);
    $result = base64_encode($encrypted . '::' . bin2hex($iv));
    // URL-safe base64
    return str_replace(['+', '/', '='], ['-', '_', ''], $result);
}

/**
 * Generate one-time hash for verification.
 */
function mch_generate_one_time_hash($wp_user_id, $moodle_user_id) {
    $data = [
        'wp_user_id' => $wp_user_id,
        'moodle_user_id' => $moodle_user_id,
        'timestamp' => time(),
        'nonce' => wp_generate_password(32, false),
    ];
    return hash('sha256', serialize($data) . MOODLE_SSO_SHARED_SECRET);
}

/**
 * Get Moodle user ID by WordPress user.
 * Store mapping in user meta for future reference.
 */
function mch_get_moodle_user_id($wp_user) {
    // Check if already mapped
    $moodle_user_id = get_user_meta($wp_user->ID, 'moodle_user_id', true);
    if ($moodle_user_id) {
        return intval($moodle_user_id);
    }
    
    // TODO: Implement your own logic to find Moodle user ID
    // Options:
    // 1. Query Moodle via web service
    // 2. Maintain a mapping table
    // 3. Match by email address
    
    return false;
}

/**
 * Prepare encrypted SSO data for Moodle.
 */
function mch_prepare_sso_data($wp_user, $moodle_user_id, $login_redirect = '') {
    $one_time_hash = mch_generate_one_time_hash($wp_user->ID, $moodle_user_id);
    
    // Store hash temporarily for verification
    update_user_meta($wp_user->ID, 'moodle_sso_one_time_hash', $one_time_hash);
    
    // Build key=value pairs
    $data = http_build_query([
        'moodle_user_id' => $moodle_user_id,
        'external_user_id' => $wp_user->ID,
        'username' => $wp_user->user_login,
        'one_time_hash' => $one_time_hash,
        'login_redirect' => $login_redirect,
        'timestamp' => time(),
    ]);
    
    // Encrypt the data
    return mch_encrypt_string($data, MOODLE_SSO_SHARED_SECRET);
}

/**
 * Generate Moodle SSO login URL
 */
function mch_get_moodle_login_url($wp_user, $moodle_user_id, $redirect_url = '') {
    $hash = get_user_meta($wp_user->ID, 'moodle_sso_one_time_hash', true);
    
    $params = [
        'login_id' => $moodle_user_id,
        'veridy_code' => $hash,
    ];
    
    if (!empty($redirect_url)) {
        $params['login_redirect'] = $redirect_url;
    }
    
    return MOODLE_SSO_URL . '/local/mchelpers/login/sso_login.php?' . http_build_query($params);
}

/**
 * Generate Moodle SSO logout URL
 */
function mch_get_moodle_logout_url($wp_user, $moodle_user_id) {
    $hash = get_user_meta($wp_user->ID, 'moodle_sso_one_time_hash', true);
    
    $params = [
        'logout_id' => $moodle_user_id,
        'veridy_code' => $hash,
    ];
    
    return MOODLE_SSO_URL . '/local/mchelpers/login/sso_login.php?' . http_build_query($params);
}

/**
 * Redirect to Moodle with SSO
 * Call this function when you want to redirect user to Moodle
 */
function moodle_sso_redirect($course_id = 0) {
    if (!MOODLE_SSO_ENABLED) {
        wp_redirect(MOODLE_SSO_URL . '/login');
        exit;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_redirect(MOODLE_SSO_URL . '/login');
        exit;
    }
    
    $wp_user = wp_get_current_user();
    $moodle_user_id = mch_get_moodle_user_id($wp_user);
    
    if (!$moodle_user_id) {
        wp_redirect(MOODLE_SSO_URL . '/login');
        exit;
    }
    
    // Build Moodle URL
    if ($course_id) {
        $login_redirect = MOODLE_SSO_URL . '/course/view.php?id=' . (int)$course_id;
    } else {
        $login_redirect = MOODLE_SSO_URL . '/my';
    }
    
    // Redirect to Moodle SSO endpoint
    $moodle_url = mch_get_moodle_login_url($wp_user, $moodle_user_id, $login_redirect);
    
    wp_redirect($moodle_url);
    exit;
}

/**
 * Shortcode to display "Go to Moodle" link
 * Usage: [moodle_link course_id="5"]Go to Course[/moodle_link]
 */
add_shortcode('moodle_link', 'moodle_sso_link_shortcode');
function moodle_sso_link_shortcode($atts, $content = '') {
    $atts = shortcode_atts([
        'course_id' => 0,
        'url' => '',
        'class' => 'moodle-sso-link',
    ], $atts);
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '<a href="' . MOODLE_SSO_URL . '/login">Login to Moodle</a>';
    }
    
    $wp_user = wp_get_current_user();
    $moodle_user_id = mch_get_moodle_user_id($wp_user);
    
    if (!$moodle_user_id) {
        return '<a href="' . MOODLE_SSO_URL . '/login">Login to Moodle</a>';
    }
    
    // Build Moodle URL
    if ($atts['course_id']) {
        $moodle_url = MOODLE_SSO_URL . '/course/view.php?id=' . (int)$atts['course_id'];
    } else if ($atts['url']) {
        $moodle_url = esc_url($atts['url']);
    } else {
        $moodle_url = MOODLE_SSO_URL . '/my';
    }
    
    // Generate SSO login URL
    $sso_url = mch_get_moodle_login_url($wp_user, $moodle_user_id, $moodle_url);
    
    return '<a href="' . esc_url($sso_url) . '" class="' . esc_attr($atts['class']) . '">' .
           esc_html($content ?: 'Go to Moodle') . '</a>';
}

/**
 * Logout from Moodle when user logs out from WordPress
 */
function moodle_sso_logout() {
    if (!MOODLE_SSO_ENABLED) {
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return;
    }
    
    $wp_user = wp_get_current_user();
    $moodle_user_id = get_user_meta($user_id, 'moodle_user_id', true);
    
    if (!$moodle_user_id) {
        return;
    }
    
    // Get logout URL and redirect
    $logout_url = mch_get_moodle_logout_url($wp_user, $moodle_user_id);
    
    // Clean up user meta
    delete_user_meta($user_id, 'moodle_user_id');
    delete_user_meta($user_id, 'moodle_sso_one_time_hash');
    
    wp_redirect($logout_url);
    exit;
}

// Hook into WordPress logout
add_action('wp_logout', 'moodle_sso_logout');

/**
 * Admin settings page (optional)
 */
if (is_admin()) {
    add_action('admin_menu', 'moodle_sso_admin_menu');
    add_action('admin_init', 'moodle_sso_admin_init');
}

function moodle_sso_admin_menu() {
    add_options_page(
        'Moodle SSO',
        'Moodle SSO',
        'manage_options',
        'moodle-sso',
        'moodle_sso_admin_page'
    );
}

function moodle_sso_admin_init() {
    register_setting('moodle_sso_group', 'moodle_sso_enabled');
    register_setting('moodle_sso_group', 'moodle_sso_url');
    register_setting('moodle_sso_group', 'moodle_sso_shared_secret');
}

function moodle_sso_admin_page() {
    ?>
    <div class="wrap">
        <h1>Moodle SSO Integration</h1>
        <form method="post" action="options.php">
            <?php settings_fields('moodle_sso_group'); ?>
            <?php do_settings_sections('moodle_sso_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable SSO</th>
                    <td><input type="checkbox" name="moodle_sso_enabled" value="1" <?php checked(get_option('moodle_sso_enabled'), 1); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">Moodle URL</th>
                    <td><input type="text" name="moodle_sso_url" value="<?php echo esc_attr(get_option('moodle_sso_url')); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Shared Secret Key</th>
                    <td><input type="text" name="moodle_sso_shared_secret" value="<?php echo esc_attr(get_option('moodle_sso_shared_secret')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Usage Instructions</h2>
        <ol>
            <li>Set the same Shared Secret Key in both WordPress and Moodle settings.</li>
            <li>Implement <code>mch_get_moodle_user_id()</code> to map WordPress users to Moodle users.</li>
            <li>Use <code>[moodle_link]</code> shortcode to add "Go to Moodle" links.</li>
            <li>Use <code>moodle_sso_redirect($course_id)</code> function for programmatic redirects.</li>
        </ol>
        <h2>Shortcode Examples</h2>
        <ul>
            <li><code>[moodle_link]Go to Moodle[/moodle_link]</code> - Link to Moodle dashboard</li>
            <li><code>[moodle_link course_id="5"]View Course[/moodle_link]</code> - Link to specific course</li>
            <li><code>[moodle_link url="https://moodle.example.com/user/profile.php"]My Profile[/moodle_link]</code> - Custom URL</li>
        </ul>
    </div>
    <?php
}
