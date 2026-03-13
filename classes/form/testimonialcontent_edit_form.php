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
 * Testimonial form class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Class for Testimonial form
 */
class testimonialcontent_edit_form extends \moodleform {

    /**
     * Define the form
     */
    public function definition() {
        global $CFG, $USER;

        $mform = $this->_form;
        $context =  \context_system::instance();

        // Hidden field for ID if editing
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Name field
        $mform->addElement('text', 'name', get_string('name', 'local_mchelpers'), 'maxlength="255" size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Designation field
        $mform->addElement('text', 'designation', get_string('designation', 'local_mchelpers'), 'maxlength="255" size="50"');
        $mform->setType('designation', PARAM_TEXT);
        $mform->addRule('designation', null, 'required', null, 'client');

        // Content field
        $mform->addElement('editor', 'content_editor', get_string('content', 'local_mchelpers'), null, null);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');

        // Testimonial image (file picker)
        $testimonial_image_options = [
            'maxfiles' => 1,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => true,
            'noclean' => true,
            'context' => $context,
            'subdirs' => false,
            'accepted_types' => ['image']
        ];
        $mform->addElement('filemanager', 'testimonial_image', get_string('image', 'local_mchelpers'), null, $testimonial_image_options);


        // Status field
        $statusoptions = [
            0 => get_string('draft', 'local_mchelpers'),
            1 => get_string('published', 'local_mchelpers')
        ];
        $mform->addElement('select', 'status', get_string('status', 'local_mchelpers'), $statusoptions);
        $mform->setDefault('status', 0);
        $mform->setType('status', PARAM_INT);

        // Add action buttons
        $this->add_action_buttons(true, get_string('savechanges'));

        // Set form defaults if editing
        if (!empty($this->_customdata['testimonial'])) {
            $testimonial = $this->_customdata['testimonial'];
            $testimonial->content_editor = [
                'text' => $testimonial->content,
                'format' => FORMAT_HTML,
            ];

            // Set up file manager for existing image if needed
            $context = \context_system::instance();
            $draftitemid = file_get_submitted_draft_itemid('image');
            if ($testimonial->image > 0) {
                file_prepare_draft_area($draftitemid, $context->id, 'local_mchelpers', 'testimonial_image', $testimonial->image, []);
                $testimonial->image = $draftitemid;
            } else {
                file_prepare_draft_area($draftitemid, $context->id, 'local_mchelpers', 'testimonial_image', 0, []);
                $testimonial->image = $draftitemid;
            }

            $this->set_data($testimonial);
        }
    }

    /**
     * Validation rules
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Add custom validation if needed
        if (isset($data['name']) && trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }

        if (isset($data['designation']) && trim($data['designation']) === '') {
            $errors['designation'] = get_string('required');
        }

        return $errors;
    }
}