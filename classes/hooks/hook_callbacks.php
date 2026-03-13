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
 * Hook callbacks for local_mchelpers for moodle 4.5 and above
 * Other hooks and backward compatable are in lib.php
 * 
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 */

namespace local_mchelpers\hooks;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_http_headers;

/**
 * Hook callbacks for local_mchelpers for moodle 4.5 and above
 * Other hooks and backward compatable are in lib.php
 *
 * @package    local_mchelpers
 * @copyright  santoshtmp7
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {


	/**
	 * Callback allowing to before_http_headers
	 *
	 * @param \core\hook\output\before_http_headers $hook
	 */
	public static function before_http_headers(before_http_headers $hook): void {
		global $CFG;
		if (during_initial_install() || isset($CFG->upgraderunning)) {
			// Do nothing during installation or upgrade.
			return;
		}
		// \local_mchelpers\util\UtilYIPL_handler::security_header();
	}

	/**
	 * Callback allowing to add to <head> of the page
	 *
	 * @param \core\hook\output\before_standard_head_html_generation $hook
	 */
	public static function before_standard_head_html_generation(\core\hook\output\before_standard_head_html_generation $hook): void {
		global $CFG;
		$output = '';

		if (during_initial_install() || isset($CFG->upgraderunning)) {
			// Do nothing during installation or upgrade.
			return;
		}
		// \local_mchelpers\util\UtilYIPL_handler::set_extra_css_js('/theme/yipl');
		$hook->add_html($output);
	}

	/**
	 * Callback allowing to add contetnt inside the region-main, in the very end
	 *
	 * @param \core\hook\output\before_footer_html_generation $hook
	 */
	public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
		global $CFG;
		if (during_initial_install() || isset($CFG->upgraderunning)) {
			// Do nothing during installation or upgrade.
			return;
		}
		$output = "";
		$hook->add_html($output);
	}

	/**
	 *
	 * @param \core\hook\output\before_standard_footer_html_generation $hook
	 */
	public static function before_standard_footer_html_generation(\core\hook\output\before_standard_footer_html_generation $hook): void {
		global $CFG, $PAGE;
		if (during_initial_install() || isset($CFG->upgraderunning)) {
			// Do nothing during installation or upgrade.
			return;
		}
		// UtilLanguageTranslate_handler::goole_translate_lang();
		$output = "";
		$hook->add_html($output);
	}

	/**
	 *
	 * @param \core\hook\output\after_standard_main_region_html_generation $hook
	 */
	public static function after_standard_main_region_html_generation(\core\hook\output\after_standard_main_region_html_generation $hook): void {
		global $CFG, $PAGE;
		if (during_initial_install() || isset($CFG->upgraderunning)) {
			// Do nothing during installation or upgrade.
			return;
		}
		$output = "";
		if (function_exists('local_mchelpers_get_custom_js')) {
			// $output .= local_mchelpers_get_custom_js();
		}

		$hook->add_html($output);
	}

	/**
	 * Callback allowing to add contetnt inside the region-main, in the very end
	 *
	 * @param \core\hook\after_config $hook
	 */
	public static function after_config(\core\hook\after_config $hook): void {
		global $CFG;
		$yipl_adminer_secret = get_config('local_mchelpers', 'yipl_adminer_secret');
		if ($yipl_adminer_secret) {
			$CFG->local_adminer_secret = $yipl_adminer_secret;
		}
	}
}
