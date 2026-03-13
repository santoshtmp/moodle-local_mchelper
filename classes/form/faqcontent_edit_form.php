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
 * FAQ form class for local_mchelpers plugin.
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
 * Class for FAQ form
 */
class faqcontent_edit_form extends \moodleform {
    // table name
    protected static $faq_table = 'yipl_faq';
    protected static $faq_category_table = 'yipl_faq_category';

    /**
     * Define the form
     */
    public function definition() {
        global $CFG, $DB;
        $context =  \context_system::instance();
        $mform = $this->_form;

        // Hidden field for ID if editing
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Title field
        $mform->addElement('text', 'title', get_string('title', 'local_mchelpers'), 'maxlength="255" size="50"');
        $mform->addRule('title', 'Default faq title', 'required', null, 'client');
        $mform->addHelpButton('title', 'faq_title', 'local_mchelpers');
        $mform->setType('title', PARAM_TEXT);

        // FAQ Category
        $all_faq_category = $DB->get_records(self::$faq_category_table);
        if ($all_faq_category) {
            $areanames = [];
            foreach ($all_faq_category as $key => $faq_category) {
                $areanames[$faq_category->id] = $faq_category->fullname;
            }
            $options = array(
                'multiple' => true,
                'noselectionstring' => get_string('search_faq_category', 'local_mchelpers'),
            );
            $mform->addElement('autocomplete', 'faq_category', get_string('faq_category', 'local_mchelpers'), $areanames, $options);
        }

        // FAQ Content
        $editor_options = array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'trusttext' => true,
            'noclean' => true,
            'context' => $context,
            'subdirs' => false
        );
        $attr = ' placeholder=""  cols="20" class="theme-yipl-faq-textarea" rows="15" ';
        $mform->addElement('editor', 'content', get_string('faq_content', 'local_mchelpers'), $attr, $editor_options);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', '', 'required', null, 'client');
        $mform->addHelpButton('content', 'faq_content', 'local_mchelpers');

        // Status field
        $statusoptions = [
            0 => get_string('draft', 'local_mchelpers'),
            1 => get_string('published', 'local_mchelpers')
        ];
        $mform->addElement('select', 'status', get_string('status', 'local_mchelpers'), $statusoptions);
        $mform->setDefault('status', 0);
        $mform->setType('status', PARAM_INT);

        // Add action buttons
        // $this->add_action_buttons(true, get_string('savechanges'));
        $classarray = array('class' => 'form-submit');
        $buttonarray = [
            // $mform->createElement('cancel', 'returnback', 'Return Back', $classarray),
            $mform->createElement('submit', 'submitbutton', get_string('savechanges'), $classarray),
            $mform->createElement('cancel'),
        ];
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');


        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', '');


        // Set form defaults if editing
        if (!empty($this->_customdata['faq'])) {
            $faq = $this->_customdata['faq'];
            $faq->content_editor = [
                'text' => $faq->content,
                'format' => $faq->contentformat,
                'itemid' => $faq->contentitemid
            ];
            $this->set_data($faq);
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
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate FAQ title.
        if ($data['title']) {
            $data_title = trim($data['title']);
            if ($existing = $DB->get_record(self::$faq_table, array('title' => $data_title))) {
                if (!$data['id'] || $existing->id != $data['id']) {
                    $errors['title'] = 'FAQ "' . trim($data['title']) . '" alrady exist.';
                }
            }
        }

        return $errors;
    }
}