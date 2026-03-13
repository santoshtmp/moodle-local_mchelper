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
 * Content edit page.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\exception\moodle_exception;
use local_mchelpers\datarepository\content_datarepository;
use local_mchelpers\form\pagecontent_edit_form;
use local_mchelpers\content\page_manager;

/**
 * ========================================================
 *             Get Require config.
 * ========================================================
 */
require_once(__DIR__ . '/../../../config.php');
defined('MOODLE_INTERNAL') || die();

/**
 * ========================================================
 *             Get parameters.
 * ========================================================
 */
// Get URL parameters.
$type = required_param('type', PARAM_ALPHANUMEXT); // Content type: page, faq, testimonial, etc.
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$action = $action ? $action : ($id ? 'edit' : 'add');

// Validate action parameter.
if ($action && !in_array($action, ['add', 'edit', 'delete'])) {
    throw new moodle_exception('invalidactionparam', 'local_mchelpers');
}

/**
 * ========================================================
 *              Prepare the page information.
 * ========================================================
 */
// Get system context.
/** @var \context $context */
$context = \context_system::instance();
// Get current page URL.
$currentPageURL = page_manager::get_action_page_url($id);
// Set page title.
$pagetitle = get_string('pluginname', 'local_mchelpers');

// Setup page information.
$PAGE->set_context($context);
$PAGE->set_url($currentPageURL);
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('local-mchelpers-content-edit');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->add_body_class('local-mchelpers-content-edit');

// Add page breadcrumb navigation.
$PAGE->navbar->add(
    get_string('customhelpercontentmanagement', 'local_mchelpers'),
    page_manager::get_listing_page_url()
);
$PAGE->navbar->add($action === 'edit' ? get_string('edit') : get_string('add'));

/**
 * ========================================================
 *     Access checks.
 * ========================================================
 */
// require_capability('local/mchelpers:managecontent', $context);

/**
 * ========================================================
 *     Get the data and display
 * ========================================================
 */
// Get content data if editing.
$contentdata = null;
if ($id) {
    $contentdata = content_datarepository::get_by_id($id);
    if (!$contentdata) {
        throw new moodle_exception('contentnotfound', 'local_mchelpers');
    }
}

// Create form instance.
$content_form = new pagecontent_edit_form(null, ['content' => $contentdata]);

// Process form submission.
if ($content_form->is_cancelled()) {
    // Form cancelled - redirect to listing.
    redirect(page_manager::get_listing_page_url());
} else if ($formdata = $content_form->get_data()) {
    // Form submitted - process data.
    $result = page_manager::process_form($formdata, $id);
    if ($result) {
        redirect(page_manager::get_listing_page_url());
    }
} else {
    // Form displayed for first time.
    if ($action == 'delete') {
        // Handle delete action.
        page_manager::delete($id);
    }
}

// Render form.
$contents = $content_form->render();

/**
 * ========================================================
 * -------------------  Output Content  -------------------
 * ========================================================
 */
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();