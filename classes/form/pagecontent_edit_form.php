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
 * Form for editing content in mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\form;

use local_mchelpers\datarepository\content_datarepository;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form class for editing content.
 *
 * Extends Moodle's moodleform for standardized form handling.
 */
class pagecontent_edit_form extends \moodleform {

    /**
     * Form definition.
     *
     * Defines all form elements and their settings.
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $contentdata = $this->_customdata['content'] ?? null;
        $foemHeader = ($contentdata) ? 'editcontent' : 'addcontent';

        // ----------------------------------------------------------------------------
        // General section.
        // ----------------------------------------------------------------------------
        // $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('header', 'generalsettings', get_string($foemHeader, 'local_mchelpers'));

        // Title field.
        $mform->addElement('text', 'title', get_string('title', 'local_mchelpers'), ['size' => '60']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('title', 'title', 'local_mchelpers');

        // Short name.
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_mchelpers'), ['size' => '60']);
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->addHelpButton('shortname', 'shortname', 'local_mchelpers');

        // // Content type selector.
        // $contenttypes = $this->get_content_types();
        // $mform->addElement('select', 'contenttype', get_string('contenttype', 'local_mchelpers'), $contenttypes);
        // $mform->setDefault('contenttype', 'page');

        // Content editor.
        $editoroptions = [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => true,
            'context' => \context_system::instance(),
            'subdirs' => true,
        ];
        $attr = ' placeholder=""  cols="20" class="custom-pages-textarea" rows="15" ';

        $mform->addElement('editor', 'content', get_string('content', 'local_mchelpers'), $attr, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required', null, 'client');
        $mform->addHelpButton('content', 'content', 'local_mchelpers');

        // Feature image.
        $mform->addElement('filemanager', 'image_filemanager', get_string('image', 'local_mchelpers'), null, [
            'maxfiles' => 1,
            'maxbytes' => $CFG->maxbytes,
            'accepted_types' => ['image'],
        ]);
        $mform->addHelpButton('image_filemanager', 'image_filemanager', 'local_mchelpers');


        // Status.
        // $mform->addElement('select', 'status', get_string('status', 'local_mchelpers'), [
        //     0 => get_string('draft', 'local_mchelpers'),
        //     1 => get_string('publish', 'local_mchelpers'),
        // ]);
        $mform->addElement('checkbox', 'status', get_string('status', 'local_mchelpers'), get_string('publish', 'local_mchelpers'));
        $mform->setDefault('status', 1);
        $mform->setType('status', PARAM_INT);
        $mform->setDefault('status', 0);
        $mform->addHelpButton('status', 'status', 'local_mchelpers');


        // ----------------------------------------------------------------------------
        // Hidden fields.
        // ----------------------------------------------------------------------------

        // Hidden fields id.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        // Hidden fields parentid.
        $mform->addElement('hidden', 'parentid');
        $mform->setType('parentid', PARAM_INT);
        $mform->setDefault('parentid', 0);

        // Hidden fields type.
        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_TEXT);
        $mform->setDefault('type', content_datarepository::DEFAULT_CONTENT_TYPE);


        // ----------------------------------------------------------------------------
        // Buttons.
        // ----------------------------------------------------------------------------
        $this->add_action_buttons();


        // Set defaults if editing.
        if ($contentdata) {
            $this->set_data($this->prepare_data_for_form($contentdata));
        }
    }

    /**
     * Prepare content data for form
     *
     * @param object $contentdata Content record
     * @return array Form data
     */
    private function prepare_data_for_form($contentdata) {
        $data = (array) $contentdata;

        // Prepare content editor.
        if (isset($contentdata->content)) {
            $data['content'] = [
                'text' => $contentdata->content,
                'format' => $contentdata->contentformat ?? FORMAT_HTML,
                'itemid' => $contentdata->contentitemid ?? 0,
            ];
        }

        return $data;
    }

    /**
     * Get available content types.
     *
     * @return array
     */
    private function get_content_types(): array {
        return [
            'page' => get_string('contenttype_page', 'local_mchelpers'),
            'faq' => get_string('contenttype_faq', 'local_mchelpers'),
            'testimonial' => get_string('contenttype_testimonial', 'local_mchelpers'),
            'article' => get_string('contenttype_article', 'local_mchelpers'),
            'video' => get_string('contenttype_video', 'local_mchelpers'),
        ];
    }

    /**
     * Validation
     *
     * @param array $data Submitted data
     * @param array $files Submitted files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if (empty(trim($data['title']))) {
            $errors['title'] = get_string('required');
        }

        if (empty($data['content']['text'])) {
            $errors['content'] = get_string('required');
        }

        if ($data['shortname']) {
            $datashortname = trim($data['shortname']);
            if ($existing = $DB->get_record('local_mchelpers_content', array('shortname' => $datashortname))) {
                if (!$data['id'] || $existing->id != $data['id']) {
                    $errors['shortname'] = 'short name "' . trim($data['shortname']) . '" alrady exist.';
                }
            }
        }

        return $errors;
    }
}