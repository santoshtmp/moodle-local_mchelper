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
 *
 * @package    local_mchelpers
 * @copyright  2025 YIPL
 * @author     santoshtmp7 https://santoshmagar.com.np/
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



namespace local_mchelpers\form;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page.
}

require_once($CFG->libdir . '/formslib.php');

/**
 *
 * @package    local_mchelpers
 * @copyright  2024 santoshtmp <https://santoshmagar.com.np/>
 * @author     santoshtmp
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class faqcategory_edit_form extends \moodleform {
    // table name
    protected static $faq_table = 'yipl_faq';
    protected static $faq_category_table = 'yipl_faq_category';

    // define form
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        // faq header
        // $mform->addElement('header', 'generalsettings', 'FAQs Category');
        $mform->addElement('html', '<h3>Add FAQs Category</h3>');

        // faq name 
        $mform->addElement('text', 'fullname', 'Fullname', ['size' => 70]);
        $mform->addRule('fullname', 'Fullname', 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);

        // faq name 
        $mform->addElement('text', 'shortname', 'Shortname', ['size' => 70]);
        $mform->setType('shortname', PARAM_TEXT);

        $this->add_action_buttons();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', 0);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_TEXT);
        $mform->setDefault('action', '');
    }

    // form validation
    function validation($data, $files) {
        global $CFG, $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($data['shortname']) {
            $data_shortname = trim($data['shortname']);
            if ($existing = $DB->get_record(self::$faq_category_table, array('shortname' => $data_shortname))) {
                if (!$data['id'] || $existing->id != $data['id']) {
                    $errors['shortname'] = 'FAQs category shortname"' . trim($data['shortname']) . '" alrady exist.';
                }
            }
        }

        return $errors;
    }

    // ----- END -----
}