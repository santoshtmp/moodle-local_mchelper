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
 * Google Translate handler for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Handler class for Google Translate functionality.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class google_translate_handler {

    /**
     * Check if language translation should be enabled.
     *
     * @return bool True if translation should be enabled, false otherwise
     */
    private static function check_lang_trans() {
        global $CFG, $PAGE;

        if (empty($CFG->langmenu)) {
            return false;
        }

        if ($PAGE->course != SITEID and !empty($PAGE->course->lang)) {
            // Do not show lang menu if language forced.
            return false;
        }

        $langs = \get_string_manager()->get_list_of_translations();
        if (count($langs) < 2) {
            return false;
        }

        $currentlang = current_language();
        if ($currentlang === 'en') {
            return false;
        }

        $google_translate = get_config('theme_yipl', 'google_translate');
        if (!$google_translate) {
            return false;
        }

        return true;
    }

    /**
     * Output Google Translate JavaScript and CSS.
     *
     * @return void
     */
    public static function google_translate_lang() {
        if (!self::check_lang_trans()) {
            return;
        }

        global $CFG;
        $currentlang = current_language();
        $cookie_name = "googtrans";
        $cookie_value = "";
        setcookie($cookie_name, $cookie_value, time() + 100, "/", "." . str_replace(['https://', 'http://'], '', $CFG->wwwroot));
        $cookie_value = "/en/" . $currentlang;
        setcookie($cookie_name, $cookie_value, time() + 3600);


        $output = "";
        $output .= "
             <style>
                .skiptranslate,
                #goog-gt-tt {
                    display: none !important;
                }
                font:focus-visible {
                    outline: unset !important
                }
            </style>
             ";
        $output .= "<script type='text/javascript'>
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({
                        pageLanguage: 'en'
                    });
                }
                (function() {
                    var googleTranslateScript = document.createElement('script');
                    googleTranslateScript.type = 'text/javascript';
                    googleTranslateScript.async = true;
                    googleTranslateScript.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
                    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(googleTranslateScript);
                })();
            </script>";
        echo $output;
    }
}
