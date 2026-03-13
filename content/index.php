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
 * Content listing page.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\html_writer;
use local_mchelpers\datarepository\content_datarepository;
use local_mchelpers\content\page_manager;

/**
 * ========================================================
 *             Get Require config.
 * ========================================================
 */
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
defined('MOODLE_INTERNAL') || die();

/**
 * ========================================================
 *             Get parameters.
 * ========================================================
 */
$id = optional_param('id', 0, PARAM_INT);
$type = optional_param('type', content_datarepository::DEFAULT_CONTENT_TYPE, PARAM_ALPHA);

/**
 * ========================================================
 *              Prepare the page information.
 * ========================================================
 */
// Get system context.
/** @var \context $context */
$context = \context_system::instance();
// Get current page URL.
$currentPageURL = page_manager::get_listing_page_url();
// Set page title.
$pagetitle = get_string('pluginname', 'local_mchelpers');

// Setup page information.
$PAGE->set_context($context);
$PAGE->set_url($currentPageURL);
$PAGE->set_pagelayout('admin');
$PAGE->set_pagetype('local-mchelpers-content');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->add_body_class('local-mchelpers-content');

// Add page breadcrumb navigation.
$PAGE->navbar->add(get_string('contentmanagement', 'local_mchelpers'), $currentPageURL);

// Add page secondary navigation.
$PAGE->secondarynav->add(get_string('addnewcontent', 'local_mchelpers'), page_manager::get_action_page_path());

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
// Add new content button.
$addnewbutton = html_writer::link(
    page_manager::get_action_page_path(),
    get_string('addnewcontent', 'local_mchelpers'),
    ['class' => 'btn btn-primary mb-3']
);

// Get content table.
$contenttable = page_manager::get_content_table($currentPageURL);

/**
 * ========================================================
 * -------------------  Output Content  -------------------
 * ========================================================
 */
echo $OUTPUT->header();
echo $addnewbutton;
echo $contenttable;
echo $OUTPUT->footer();