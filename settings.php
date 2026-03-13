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
 * Settings for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\html_writer;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Create the main plugin settings page.
    $settings = new admin_settingpage('local_mchelpers', get_string('pluginname', 'local_mchelpers'));
    $ADMIN->add('localplugins', $settings);

    // Add a heading inside the settings page.
    $settings->add(new admin_setting_heading(
        'local_mchelpers_settings',
        '',
        html_writer::link(new moodle_url('/local/mchelpers/content/admin/index.php'), get_string('contentmanagement', 'local_mchelpers'))
    ));

    // External Site Integration Settings Section.
    $settings->add(new admin_setting_heading(
        'local_mchelpers_external_sites',
        get_string('external_site_integration', 'local_mchelpers', 'External Site Integration'),
        get_string('external_site_integration_desc', 'local_mchelpers', 'Configure Single Sign-On (SSO) integration with an external system (WordPress, Drupal, custom PHP app, etc.). You can connect to one external site at a time.')
    ));

    // External Site URL setting.
    $settings->add(new admin_setting_configtext(
        'local_mchelpers/external_site_url',
        get_string('external_site_url', 'local_mchelpers', 'External Site URL'),
        get_string('external_site_url_desc', 'local_mchelpers', 'The base URL of your external site (e.g., https://example.com). Used for error redirects.'),
        '',
        PARAM_URL
    ));

    // Shared Secret setting for encryption.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_mchelpers/shared_secret',
        get_string('shared_secret', 'local_mchelpers', 'Shared Secret Key'),
        get_string('shared_secret_desc', 'local_mchelpers', 'A secret key shared between the external system and Moodle for encrypting SSO data. Use a strong, random string (at least 32 characters). This must match the value in your external system configuration.'),
        '',
        PARAM_RAW
    ));

    // Integration Guide.
    $settings->add(new admin_setting_heading(
        'local_mchelpers_integration_guide',
        get_string('integration_guide', 'local_mchelpers', 'Integration Guide'),
        '
<h4>How to Integrate Your External System</h4>
<ol>
    <li><strong>Set Shared Secret:</strong> Configure the same shared secret key in both Moodle and your external system.</li>
    <li><strong>Encrypt Data:</strong> Your external system should encrypt user data using AES-128-CTR with the shared secret.</li>
    <li><strong>Send to Moodle:</strong> POST encrypted data to: <code>{moodle_url}/local/mchelpers/login/sso_login.php</code></li>
    <li><strong>Login Flow:</strong> Redirect users to: <code>{moodle_url}/local/mchelpers/login/sso_login.php?login_id={user_id}&veridy_code={hash}</code></li>
    <li><strong>Logout Flow:</strong> Redirect users to: <code>{moodle_url}/local/mchelpers/login/sso_login.php?logout_id={user_id}&veridy_code={hash}</code></li>
</ol>

<h4>Encrypted Data Format</h4>
<p>Data should be key=value pairs encrypted and URL-safe base64 encoded:</p>
<pre>
moodle_user_id=123&external_user_id=456&one_time_hash=abc123&login_redirect=https://moodle.example.com/my
</pre>

<h4>Supported Systems</h4>
<p>This plugin works with any external system that can perform AES-128-CTR encryption:</p>
<ul>
    <li>WordPress</li>
    <li>Drupal</li>
    <li>Joomla</li>
    <li>Custom PHP applications</li>
    <li>Node.js applications</li>
    <li>Python applications</li>
    <li>Any system with OpenSSL support</li>
</ul>

<p>See <code>INTEGRATION_GUIDE.md</code> in the <code>login/</code> folder for detailed code examples.</p>
')
    );
}
