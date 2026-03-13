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
 * Custom field handler for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\handler;

use core_customfield\category_controller;
use core_customfield\field_controller;
use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Handler class for managing custom fields (course and user).
 *
 * Provides utility methods for creating and managing custom field
 * categories and fields for both courses and users.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfield_handler {

    /**
     * Generate a standardized field shortname from field name.
     *
     * @param string $fieldname The field name to convert
     * @return string Generated shortname with 'yipl_' prefix
     */
    public static function get_field_shortname($fieldname) {
        $replace_for = [' ', '(', ')'];
        $replace_with = ['_', '_', '_'];
        $filteredname = str_replace($replace_for, $replace_with, $fieldname);
        $shortname = "yipl_" . strtolower($filteredname);
        return $shortname;
    }

    /**
     * Get or create custom field category ID.
     *
     * @param string $categoryname Name of the category
     * @param string $area Area type: 'course' or 'user'
     * @return int Category ID
     */
    public static function get_customfield_category_id($categoryname, $area = 'course') {
        global $DB;

        // Check if the category already exists.
        $existingcategory = $DB->get_record('customfield_category', ['name' => $categoryname]);
        if (!$existingcategory) {
            // Create a new category for the custom field.
            if ($area === 'course') {
                $handler = \core_customfield\handler::get_handler('core_course', 'course', 0);
                $categoryid = $handler->create_category($categoryname);
            } elseif ($area === 'user') {
                $categoryid = self::get_userinfo_customfield_category($categoryname);
            } else {
                $categoryid = 0;
            }
        } else {
            $categoryid = $existingcategory->id;
        }
        return (int) $categoryid;
    }

    /**
     * Create multiple course custom fields in a category.
     *
     * @param int $customfieldcategoryid Category ID
     * @param array $customfields Array of custom field definitions
     * @return void
     */
    public static function create_course_customfields($customfieldcategoryid, $customfields) {
        global $DB;

        foreach ($customfields as $customfield) {
            $fieldname = $customfield['fieldname'];
            // Shortname.
            $shortname = isset($customfield['shortname']) ? $customfield['shortname'] : '';
            $shortname = ($shortname) ? $shortname : self::get_field_shortname($customfield['fieldname']);

            // Make sure not to repeat the fields.
            if (!$DB->record_exists('customfield_field', [
                'shortname' => $shortname,
                'name' => $fieldname,
                'categoryid' => $customfieldcategoryid
            ])) {
                self::create_course_custom_field_data($customfieldcategoryid, $customfield);
            }
        }
    }

    /**
     * Create a single course custom field.
     *
     * @param int $categoryid Category ID in which new field will be created
     * @param array $customfield Field definition ['fieldname', 'shortname', 'type', 'options']
     * @return int|false Field ID on success, false on failure
     */
    public static function create_course_custom_field_data($categoryid, $customfield) {
        try {
            $fieldconfigdata = self::get_course_customfield_data($categoryid, $customfield);

            $category = category_controller::create($categoryid);
            $field = field_controller::create(0, (object)['type' => $customfield['type']], $category);
            $handler = $field->get_handler();

            $fieldid = $handler->save_field_configuration($field, $fieldconfigdata);
            return $fieldid;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get course custom field configuration data.
     *
     * @param int $categoryid Category ID
     * @param array $customfield Field definition ['fieldname', 'shortname', 'type', 'options']
     * @return \stdClass Field configuration data
     * @throws Exception If invalid field type provided
     */
    public static function get_course_customfield_data($categoryid, $customfield) {
        $fieldname = $customfield['fieldname'];
        $shortname = isset($customfield['shortname']) ? $customfield['shortname'] : '';
        $shortname = ($shortname) ? $shortname : self::get_field_shortname($customfield['fieldname']);
        $fieldtype = $customfield['type'];
        $options = $customfield['options'];

        $data = new \stdClass();
        $data->name = $fieldname;
        $data->shortname = $shortname;
        $data->mform_isexpanded_id_header_specificsettings = 1;
        $data->mform_isexpanded_id_course_handler_header = 1;
        $data->categoryid = $categoryid;
        $data->type = $fieldtype;
        $data->id = 0; // This is always zero for new fields.

        $configdata = [
            'required' => 0,
            'uniquevalues' => 0,
            'locked' => 0,
            'visibility' => 2,
        ];

        switch ($fieldtype) {
            case 'checkbox':
                $configdata['checkbydefault'] = 0;
                break;
            case 'date':
                $configdata['includetime'] = 0;
                $configdata['mindate'] = 1605158580;
                $configdata['maxdate'] = 1605158580;
                break;
            case 'select':
                $configdata['options'] = 'menuitem1';
                $configdata['defaultvalue'] = 'menuitem1';
                break;
            case 'text':
                $configdata['defaultvalue'] = '';
                $configdata['displaysize'] = 50;
                $configdata['maxlength'] = 1333;
                $configdata['ispassword'] = 0;
                break;
            case 'number':
                $configdata['defaultvalue'] = 0;
                $configdata['minimumvalue'] = 0;
                $configdata['decimalplaces'] = 0;
                $configdata['display'] = '{value}';
                $configdata['displaywhenzero'] = '0';
                break;
            case 'textarea':
                $configdata['defaultvalue_editor'] = [];
                break;
            default:
                throw new Exception('No such type of field: ' . $fieldtype);
        }

        if ($options && (is_array($options) || is_object($options))) {
            foreach ($options as $key => $value) {
                $configdata[$key] = $value;
            }
        }

        $data->configdata = $configdata;
        return $data;
    }

    /**
     * Get or create user info custom field category.
     *
     * @param string $categoryname User field category name
     * @return int User field category ID
     */
    public static function get_userinfo_customfield_category($categoryname) {
        global $DB;

        $table = 'user_info_category';
        if (!$DB->record_exists($table, ['name' => $categoryname])) {
            $data = new \stdClass();
            $data->name = $categoryname;
            $data->sortorder = 9991;
            $DB->insert_record($table, $data);
        }
        $category = $DB->get_record($table, ['name' => $categoryname]);
        return (int) $category->id ?: 0;
    }

    /**
     * Create or update user info profile field.
     *
     * @param string $userfieldname Custom user field name
     * @param int $categoryid Category ID
     * @return bool True on success, false on failure
     */
    public static function create_userinfo_profile_field($userfieldname, $categoryid) {
        global $DB;

        $status = false;
        $shortname = self::get_field_shortname($userfieldname);
        $table = 'user_info_field';

        $data = new \stdClass();
        $data->shortname = $shortname;
        $data->name = trim($userfieldname);
        $data->datatype = 'text';
        $data->description = '';
        $data->descriptionformat = 1;
        $data->categoryid = $categoryid;
        $data->defaultdata = '';
        $data->visible = 2;
        $data->param1 = 40;
        $data->param2 = 2048;
        $data->param3 = '';
        $data->param4 = '';
        $data->param5 = '';

        $userfield = $DB->get_record($table, ['shortname' => $shortname]);
        if ($userfield) {
            $data->id = $userfield->id;
            $status = $DB->update_record($table, $data);
        } else {
            $status = $DB->insert_record($table, $data);
        }

        return $status;
    }
}
