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
 * Language file.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// =============================================================================
// PLUGIN STRINGS
// =============================================================================

// Plugin identification and configuration.
$string['pluginname'] = 'Moodle Custom Helpers';
$string['pluginname_desc'] = 'Local plugin to manage course and user metadata';
$string['enable'] = 'Enable MHelpers';
$string['configenable'] = 'Enable the MHelpers plugin functionality';
$string['manage'] = 'Manage Moodle custom Helpers';

// =============================================================================
// GENERAL SETTINGS
// =============================================================================

$string['general'] = 'General';
$string['generalsettings'] = 'General Settings';
$string['enable_desc'] = 'Enable the MHelpers plugin functionality';
$string['plugininfo'] = 'Plugin Information';
$string['plugininfo_desc'] = 'Plugin information and description.';
$string['contentmanagement_desc'] = 'Create and manage pages, FAQs, testimonials, and other content.';
$string['tip'] = 'Tip';
$string['generate_secret_tip'] = 'Generate a secure key using:';

// =============================================================================
// SSO INTEGRATION TAB
// =============================================================================

$string['sso_integration'] = 'SSO Integration';
$string['sso_integration_desc'] = 'Configure SSO integration with external systems (WordPress, Drupal, custom apps, etc.). Users from external systems can automatically log in to Moodle using encrypted data exchange.';
$string['configure_sso'] = 'Configure Single Sign-On';
$string['external_site_integration'] = 'External Site Integration';
$string['external_site_url'] = 'External Site URL';
$string['external_site_url_desc'] = 'The base URL of your external site (e.g., https://example.com). Used for error redirects.';
$string['shared_secret'] = 'Shared Secret Key';
$string['shared_secret_desc'] = 'A secret key shared between the external system and Moodle for encrypting SSO data. Use a strong, random string (at least 32 characters). This must match the value in your external system configuration.';
$string['plugin_not_configured'] = 'SSO integration has not yet been configured. Please contact the Moodle administrator for details.';

// SSO Flow steps.
$string['how_it_works'] = 'How It Works';
$string['sso_flow'] = 'SSO Flow';
$string['step1'] = 'External Login';
$string['step1_desc'] = 'User logs into external system (WordPress, Drupal, etc.)';
$string['step2'] = 'Encrypt Data';
$string['step2_desc'] = 'External system encrypts user data with shared secret';
$string['step3'] = 'Send to Moodle';
$string['step3_desc'] = 'POST encrypted data to Moodle SSO endpoint';
$string['step4'] = 'Verify & Login';
$string['step4_desc'] = 'Moodle decrypts, verifies hash, and logs in user';

// API Endpoints.
$string['api_endpoints'] = 'API Endpoints';
$string['endpoint'] = 'Endpoint';
$string['method'] = 'Method';
$string['description'] = 'Description';
$string['store_session'] = 'Store session data (mch_data parameter)';
$string['login_user'] = 'Login user with verification hash';
$string['logout_user'] = 'Logout user with verification hash';

// Documentation.
$string['documentation'] = 'Documentation';
$string['view_full_guide'] = 'View Full Integration Guide';
$string['docs_desc'] = 'Complete guide with PHP, Node.js, and Python examples.';

// Supported systems.
$string['supported_systems'] = 'Supported Systems';
$string['supported_systems_desc'] = 'This plugin works with WordPress, Drupal, Joomla, custom PHP apps, Node.js, Python, and any system with OpenSSL support.';
$string['wordpress_desc'] = 'Full SSO support';
$string['drupal_desc'] = 'Full SSO support';
$string['php_desc'] = 'Full SSO support';
$string['other_desc'] = 'Full SSO support';

// Integration guide.
$string['integration_guide'] = 'Integration Guide';
$string['integration_guide_content'] = 'How to integrate external systems with Moodle SSO.';

$string['sso_settings'] = 'SSO Settings';
$string['sso_settings_desc'] = 'Configure Single Sign-On (SSO) for external systems (WordPress).';
$string['sso_wordpress_url'] = 'WordPress URL';
$string['sso_wordpress_url_desc'] = 'The WordPress site URL for SSO redirect (e.g., https://example.com).';
$string['sso_shared_secret'] = 'Shared Secret';
$string['sso_shared_secret_desc'] = 'Encryption key for SSO communication. Must match the WordPress side. Leave empty to auto-generate.';

// =============================================================================
// GENERAL STRINGS
// =============================================================================

// Common UI labels.
$string['sn'] = 'S.N';
$string['name'] = 'Name';
$string['title'] = 'Title';
$string['description'] = 'Description';
$string['image'] = 'Image';
$string['content'] = 'Content';
$string['status'] = 'Status';
$string['type'] = 'Type';
$string['author'] = 'Author';
$string['modified'] = 'Modified';
$string['actions'] = 'Actions';
$string['back'] = 'Back';
$string['none'] = 'None';
$string['unknown'] = 'Unknown';

// Status values.
$string['draft'] = 'Draft';
$string['publish'] = 'Publish';
$string['published'] = 'Published';

// Actions.
$string['savechanges'] = 'Save changes';
$string['addfaq'] = 'Add FAQ';
$string['editfaq'] = 'Edit FAQ';
$string['addtestimonial'] = 'Add Testimonial';
$string['edittestimonial'] = 'Edit Testimonial';
$string['filter'] = 'Filter';
$string['search'] = 'Search';
$string['all'] = 'All';
$string['readmore'] = 'Read More';

// Content-related.
$string['shortname'] = 'Short Name';
$string['parent'] = 'Parent Content';

// =============================================================================
// CONTENT MANAGEMENT STRINGS
// =============================================================================

// Section titles.
$string['contentmanagement'] = 'Content Management';
$string['customhelpercontentmanagement'] = 'Custom Helper Content Management';
$string['managecontent'] = 'Manage Content';

// Content actions.
$string['addnewcontent'] = 'Add New Content';
$string['addcontent'] = 'Add Content';
$string['editcontent'] = 'Edit Content';

// Content types.
$string['contenttype'] = 'Content Type';
$string['contenttype_page'] = 'Page';
$string['contenttype_faq'] = 'FAQ';
$string['contenttype_testimonial'] = 'Testimonial';
$string['contenttype_all'] = 'All Types';

// Content messages.
$string['nocontentfound'] = 'No content found';
$string['contentnotfound'] = 'Content not found';
$string['contentdeleted'] = 'Content deleted successfully';
$string['contentcreated'] = 'Content created successfully';
$string['changessaved'] = 'Changes saved successfully';
$string['errordeletecontent'] = 'Error deleting content';
$string['deletecontentconfirm'] = 'Are you sure you want to delete "{$a->title}"? This action cannot be undone.';
$string['invalidactionparam'] = 'Invalid action param. Check action param';
$string['failtoconfirmsesskey'] = 'Fail to confirm sesskey.';

// =============================================================================
// CAPABILITIES
// =============================================================================

$string['local/mchelpers:managecontent'] = 'Manage content';
$string['local/mchelpers:viewcontent'] = 'View content';
$string['local/mchelpers:viewdraft'] = 'View draft content';

// =============================================================================
// SETTINGS STRINGS
// =============================================================================

$string['content_settings'] = 'Content Settings';
$string['content_settings_desc'] = 'Configure content management settings.';
$string['maxuploadsize'] = 'Maximum upload size';
$string['maxuploadsize_help'] = 'Maximum file size for uploads in bytes.';
$string['invalidmaxuploadsize'] = 'Invalid maximum upload size. Please enter a positive number.';

// =============================================================================
// FORM FIELD HELP STRINGS
// =============================================================================

$string['title_help'] = 'Enter a descriptive title for this content.';
$string['shortname_help'] = 'A unique short name or identifier for this content (e.g., "about-us", "contact").';
$string['content_help'] = 'The main content or body text. You can use the HTML editor to format your content.';
$string['image_filemanager'] = 'Image File Manager';
$string['image_filemanager_help'] = 'Upload or select an image to associate with this content. Supported formats: JPG, PNG, GIF.';
$string['status_help'] = 'Set the content status. Draft is visible only to administrators, Published is visible to all users.';

// =============================================================================
// TESTIMONIALS AND FAQ STRINGS
// =============================================================================

$string['designation'] = 'Designation';

// =============================================================================
// USER API STRINGS
// =============================================================================

// Login/Logout.
$string['usernameisrequired'] = 'Username is required.';
$string['passwordisrequired'] = 'Password is required.';
$string['invalidlogin'] = 'Invalid login credentials.';
$string['usernotconfirmed'] = 'User account has not been confirmed.';
$string['loginsuccess'] = 'Login successful.';
$string['webservicesnotenabled'] = 'Web services are not enabled. Please contact administrator.';
$string['usernotfound'] = 'User not found.';
$string['ssotokengenerated'] = 'SSO token generated successfully.';
$string['useridisrequired'] = 'User ID is required.';
$string['tokenisrequired'] = 'Session token is required.';
$string['invalidtoken'] = 'Invalid session token.';
$string['logoutsuccess'] = 'Logout successful.';
$string['logoutfailed'] = 'Logout failed.';

// User management.
$string['invaliduserids'] = 'Invalid user IDs.';
$string['invaliduserid'] = 'Invalid user ID.';
$string['useralreadyenrolled'] = 'User is already enrolled.';
$string['userenrolledsuccessfully'] = 'User enrolled successfully.';

// =============================================================================
// COURSE API STRINGS
// =============================================================================

// Course management.
$string['invalidcourseids'] = 'Invalid course IDs.';
$string['invalidcourseid'] = 'Invalid course ID.';
$string['invalidroleid'] = 'Invalid role ID.';
$string['manualenrolmentnotfound'] = 'Manual enrolment method not found.';
$string['coursenotcompleted'] = 'Course not completed (requires 100% progress).';
$string['certificatenotconfigured'] = 'Certificate not configured for this course.';
$string['certificatedata'] = 'Certificate data retrieved successfully.';
$string['failtoissuecertificate'] = 'Failed to issue certificate.';
$string['invalidcertificatedate'] = 'Invalid certificate date. Must be between 2001-01-01 and 2100-12-31.';
$string['usernotstudent'] = 'User is not enrolled as a student in this course.';

// =============================================================================
// ROLE API STRINGS
// =============================================================================

$string['invalidrole'] = 'Invalid role.';
$string['rolenotassignable'] = 'Role is not assignable in this context.';
$string['rolealreadyassigned'] = 'Role is already assigned to this user.';
$string['roleassigned'] = 'Role assigned successfully.';
$string['roleunassigned'] = 'Role unassigned successfully.';
$string['roleisrequired'] = 'Role shortname is required.';
$string['useridisrequired'] = 'User ID is required.';
$string['onlysystemroleallowed'] = 'Only system-level role assignment is allowed.';
$string['useralreadyadmin'] = 'User is already an administrator.';
$string['usernotadmin'] = 'User is not an administrator.';
$string['adminassigned'] = 'Administrator role assigned successfully.';
$string['adminunassigned'] = 'Administrator role unassigned successfully.';
$string['adminroleonlysystem'] = 'Admin role can only be assigned at system level.';
$string['rolesynced'] = 'User roles synced successfully.';

// =============================================================================
// TESTIMONIALS AND FAQ STRINGS (ADDITIONAL)
// =============================================================================

$string['testimonial'] = 'Testimonial';
$string['faq'] = 'FAQ';
$string['page'] = 'Page';

// =============================================================================
// CONTENT API STRINGS
// =============================================================================

$string['titleisrequired'] = 'Title is required.';
$string['contentisrequired'] = 'Content is required.';
$string['errorcreatecontent'] = 'Error creating content.';

// =============================================================================
// CERTIFICATE STRINGS
// =============================================================================

$string['certificateissued'] = 'Certificate issued successfully.';
$string['certificatealreadyissued'] = 'Certificate already issued.';
