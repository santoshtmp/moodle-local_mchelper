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
    // Get the current active tab.
    $currenttab = optional_param('tab', 'general', PARAM_TEXT);

    // Create the main plugin settings page.
    $settings = new admin_settingpage('local_mchelpers', get_string('pluginname', 'local_mchelpers'));
    $ADMIN->add('localplugins', $settings);

    // Check if we're on the local_mchelpers settings page.
    $section = optional_param('section', '', PARAM_TEXT);

    if ($section == 'local_mchelpers') {
        // Get current page URL with preserved params.
        $currenturl = new moodle_url('/admin/settings.php', ['section' => $section]);

        // Add custom tab navigation HTML.
        $tabhtml = '
<div class="local_mchelpers_setting_tabs moremenu" style="display: flex; flex-wrap: wrap; gap: 12px; opacity: 1; margin: 20px 0;">
    <a href="' . $currenturl->out(false, ['tab' => 'general']) . '"
       class="nav-link ' . ($currenttab == 'general' ? 'active' : '') . '">
       ' . get_string('general', 'local_mchelpers', 'General Settings') . '
    </a>
    <a href="' . $currenturl->out(false, ['tab' => 'sso_integration']) . '"
       class="nav-link ' . ($currenttab == 'sso_integration' ? 'active' : '') . '">
       ' . get_string('sso_integration', 'local_mchelpers', 'SSO Integration') . '
    </a>
</div>
';

        $settings->add(new admin_setting_heading(
            'local_mchelpers_tab_navigation',
            '',
            $tabhtml
        ));



        // =============================================================================
        // TAB 1: GENERAL SETTINGS
        // =============================================================================
        if ($currenttab == 'general' || $currenttab == '') {
            // Plugin information.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_info',
                get_string('plugininfo', 'local_mchelpers', 'Plugin Information'),
                get_string('plugininfo_desc', 'local_mchelpers', '
                    <p><strong>Moodle Custom Helpers</strong> provides additional functionality for Moodle including:</p>
                    <ul>
                        <li>Content management (pages, FAQs, testimonials)</li>
                        <li>External system SSO integration</li>
                        <li>Custom course and user metadata</li>
                        <li>Google Translate integration</li>
                    </ul>
                    <p><strong>Version:</strong> 1.0.0 | <strong>Author:</strong> santoshmagar.com.np</p>
                ')
            ));

            // Content Management link.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_content_management',
                get_string('contentmanagement', 'local_mchelpers', 'Content Management'),
                html_writer::link(
                    new moodle_url('/local/mchelpers/content/admin/index.php'),
                    get_string('managecontent', 'local_mchelpers', 'Manage Content'),
                    ['class' => 'btn btn-primary']
                ) . '<p class="form-description">' .
                    get_string('contentmanagement_desc', 'local_mchelpers', 'Create and manage pages, FAQs, testimonials, and other content.') .
                    '</p>'
            ));

            // Enable/Disable plugin.
            $settings->add(new admin_setting_configcheckbox(
                'local_mchelpers/enabled',
                get_string('enable', 'local_mchelpers', 'Enable Plugin'),
                get_string('enable_desc', 'local_mchelpers', 'Enable the MHelpers plugin functionality'),
                1
            ));
        }

        // =============================================================================
        // TAB 2: SSO INTEGRATION
        // =============================================================================
        if ($currenttab == 'sso_integration') {
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_heading',
                get_string('sso_integration', 'local_mchelpers', 'SSO Integration'),
                get_string('sso_integration_desc', 'local_mchelpers', '
                    Configure SSO integration with external systems (WordPress, Drupal, custom apps, etc.).
                    Users from external systems can automatically log in to Moodle using encrypted data exchange.
                ')
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
                get_string('shared_secret_desc', 'local_mchelpers', '
                    A secret key shared between the external system and Moodle for encrypting SSO data. 
                    Use a strong, random string (at least 32 characters). 
                    This must match the value in your external system configuration.
                '),
                '',
                PARAM_RAW
            ));

            // Generate secret key tip.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_generate',
                '',
                '<div class="alert alert-info">
                    <strong>' . get_string('tip', 'local_mchelpers', 'Tip') . ':</strong> ' .
                    get_string('generate_secret_tip', 'local_mchelpers', 'Generate a secure key using: ') .
                    '<code>openssl rand -base64 32</code>
                </div>'
            ));

            // How it works.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_how',
                get_string('how_it_works', 'local_mchelpers', 'How It Works'),
                '<div class="card">
            <div class="card-body">
                <h5 class="card-title">' . get_string('sso_flow', 'local_mchelpers', 'SSO Flow') . '</h5>
                <ol>
                    <li><strong>' . get_string('step1', 'local_mchelpers', 'External Login') . ':</strong> ' .
                    get_string('step1_desc', 'local_mchelpers', 'User logs into external system (WordPress, Drupal, etc.)') . '</li>
                    <li><strong>' . get_string('step2', 'local_mchelpers', 'Encrypt Data') . ':</strong> ' .
                    get_string('step2_desc', 'local_mchelpers', 'External system encrypts user data with shared secret') . '</li>
                    <li><strong>' . get_string('step3', 'local_mchelpers', 'Send to Moodle') . ':</strong> ' .
                    get_string('step3_desc', 'local_mchelpers', 'POST encrypted data to Moodle SSO endpoint') . '</li>
                    <li><strong>' . get_string('step4', 'local_mchelpers', 'Verify & Login') . ':</strong> ' .
                    get_string('step4_desc', 'local_mchelpers', 'Moodle decrypts, verifies hash, and logs in user') . '</li>
                </ol>
            </div>
        </div>
        '
            ));

            // API Endpoints.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_api',
                get_string('api_endpoints', 'local_mchelpers', 'API Endpoints'),
                '<div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>' . get_string('endpoint', 'local_mchelpers', 'Endpoint') . '</th>
                        <th>' . get_string('method', 'local_mchelpers', 'Method') . '</th>
                        <th>' . get_string('description', 'local_mchelpers', 'Description') . '</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>local/mchelpers/login/sso.php</code></td>
                        <td><span class="badge badge-success">POST</span></td>
                        <td>' . get_string('store_session', 'local_mchelpers', 'Store session data (mch_data parameter)') . '</td>
                    </tr>
                    <tr>
                        <td><code>local/mchelpers/login/sso.php?login_id={id}&veridy_code={hash}</code></td>
                        <td><span class="badge badge-primary">GET</span></td>
                        <td>' . get_string('login_user', 'local_mchelpers', 'Login user with verification hash') . '</td>
                    </tr>
                    <tr>
                        <td><code>local/mchelpers/login/sso.php?logout_id={id}&veridy_code={hash}</code></td>
                        <td><span class="badge badge-warning">GET</span></td>
                        <td>' . get_string('logout_user', 'local_mchelpers', 'Logout user with verification hash') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>
        '
            ));

            // Documentation link.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_docs',
                get_string('documentation', 'local_mchelpers', 'Documentation'),
                html_writer::link(
                    new moodle_url('/local/mchelpers/login/sso_guide.php'),
                    get_string('view_full_guide', 'local_mchelpers', 'View Full Integration Guide'),
                    ['class' => 'btn btn-secondary', 'target' => '_blank']
                ) . '<p class="form-description mt-2">' .
                    get_string('docs_desc', 'local_mchelpers', 'Complete guide with PHP, Node.js, and Python examples.') .
                    '</p>'
            ));

            // Supported systems.
            $settings->add(new admin_setting_heading(
                'local_mchelpers_sso_systems',
                get_string('supported_systems', 'local_mchelpers', 'Supported Systems'),
                '<div class="row">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">WordPress</h5>
                        <p class="card-text">' . get_string('wordpress_desc', 'local_mchelpers', 'Full SSO support') . '</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Drupal</h5>
                        <p class="card-text">' . get_string('drupal_desc', 'local_mchelpers', 'Full SSO support') . '</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Custom PHP</h5>
                        <p class="card-text">' . get_string('php_desc', 'local_mchelpers', 'Full SSO support') . '</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Node.js/Python</h5>
                        <p class="card-text">' . get_string('other_desc', 'local_mchelpers', 'Full SSO support') . '</p>
                    </div>
                </div>
            </div>
        </div>
        '
            ));
        }
    }
}
