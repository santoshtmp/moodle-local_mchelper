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
 * Content view page.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\exception\moodle_exception;
use local_mchelpers\datarepository\content_datarepository;
use local_mchelpers\datarepository\usermeta_datarepository;
use local_mchelpers\content\page_manager;

/**
 * ========================================================
 *             Get Require config.
 * ========================================================
 */
require_once(dirname(__FILE__) . '/../../../config.php');
defined('MOODLE_INTERNAL') || die();

/**
 * ========================================================
 *             Get parameters.
 * ========================================================
 */
$id = required_param('id', PARAM_INT);
if (empty($id)) {
    throw new moodle_exception('contentnotfound', 'local_mchelpers');
}

/**
 * ========================================================
 *              Prepare the page information.
 * ========================================================
 */
// Get content data.
$content = content_datarepository::get_by_id($id);
if (!$content) {
    throw new moodle_exception('contentnotfound', 'local_mchelpers');
}

// Prepare the page information.
/** @var \context $context */
$context = \context_system::instance();
$classes = [
    'mchelpers-content-view',
    'content-type-' . strtolower($content->contenttype),
    'content-id-' . $id
];
$strcapability = 'moodle/site:manageblocks';

// Setup page information.
$PAGE->set_context($context);
$PAGE->set_url(page_manager::get_view_page_url());
$PAGE->set_pagelayout('mchelpers');
$PAGE->set_pagetype('content-view');
$PAGE->set_subpage((string)$id);
$PAGE->set_title(format_string($content->title));
$PAGE->set_heading(get_string('contentmanagement', 'local_mchelpers'));
$PAGE->add_body_class(implode(' ', $classes));
$PAGE->set_blocks_editing_capability($strcapability);

// Add page breadcrumb navigation.
if (is_siteadmin()) {
    $PAGE->navbar->add(
        get_string('contentmanagement', 'local_mchelpers'),
        page_manager::get_listing_page_url()
    );
    $PAGE->navbar->add(format_string($content->title));
}
// Add page secondary navigation.
if (is_siteadmin()) {
    $PAGE->secondarynav->add(
        get_string('back'),
        page_manager::get_listing_page_url()
    );
    $PAGE->secondarynav->add(
        get_string('editcontent', 'local_mchelpers'),
        page_manager::get_action_page_url($id)
    );
}
/**
 * ========================================================
 *     Access checks.
 * ========================================================
 */
// Check if content is published or user has permission to view draft.
// if ($content->status == 0) {
//     // Draft content - require manage capability.
//     require_capability('local/mchelpers:managecontent', $context);
// }

/**
 * ========================================================
 *     Get the data and display
 * ========================================================
 */
// Get author name.
$author = usermeta_datarepository::get_user_by_id($content->userid);
$authorname = $author ? fullname($author) : get_string('unknown', 'local_mchelpers');

// Get content type label.
$contenttypelabel = get_string('contenttype_' . $content->contenttype, 'local_mchelpers', $content->contenttype);

// Prepare content data.
$template_content = [
    'content' => $content,
    'authorname' => $authorname,
    'contenttypelabel' => $contenttypelabel,
];

// Render content using template.
$contents = $OUTPUT->render_from_template('local_mchelpers/content/view', $template_content);

/**
 * ========================================================
 * -------------------  Output Content  -------------------
 * ========================================================
 */
echo $OUTPUT->header();
echo $contents;
echo $OUTPUT->footer();