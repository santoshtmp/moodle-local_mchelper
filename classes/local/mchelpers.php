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
 * Base helper class for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Base helper class for common mchelpers functionality.
 *
 * Provides utility methods used throughout the plugin.
 */
class mchelpers {

    /**
     * Redirect to a page within the plugin.
     *
     * @param string $page Page path (e.g., '/content/index.php')
     * @param array $params Additional URL parameters
     * @return void
     */
    public static function redirect($page, $params = []): void {
        redirect(new \moodle_url('/local/mchelpers' . $page, $params));
    }

    /**
     * Show success notification.
     *
     * @param string $message Message to display
     * @return void
     */
    public static function show_success($message): void {
        \core\notification::success($message);
    }

    /**
     * Show error notification.
     *
     * @param string $message Message to display
     * @return void
     */
    public static function show_error($message): void {
        \core\notification::error($message);
    }

    /**
     * Show warning notification.
     *
     * @param string $message Message to display
     * @return void
     */
    public static function show_warning($message): void {
        \core\notification::warning($message);
    }

    /**
     * Show info notification.
     *
     * @param string $message Message to display
     * @return void
     */
    public static function show_info($message): void {
        \core\notification::info($message);
    }

    /**
     * Get plugin version.
     *
     * @return string Version string
     */
    public static function get_version(): string {
        return get_config('local_mchelpers', 'version') ?? 'unknown';
    }

    /**
     * Check if user can manage content.
     *
     * @param int $userid User ID (defaults to current user)
     * @return bool
     */
    public static function can_manage($userid = null): bool {
        global $USER;

        $userid = $userid ?? $USER->id;
        $context = \context_system::instance();

        return has_capability('local/mchelpers:managecontent', $context, $userid);
    }

    /**
     * Check if user can view content.
     *
     * @param int $userid User ID (defaults to current user)
     * @return bool
     */
    public static function can_view($userid = null): bool {
        global $USER;

        $userid = $userid ?? $USER->id;
        $context = \context_system::instance();

        return has_capability('local/mchelpers:viewcontent', $context, $userid);
    }

    /**
     * Format timestamp for display.
     *
     * @param int $timestamp Unix timestamp
     * @param string $format Date format (short, long, relative, (default: %b %d, %Y) )
     * @return string Formatted date
     */
    public static function format_time($timestamp, $format = '%b %d, %Y'): string {
        if (!$timestamp) {
            return get_string('never');
        }
        switch ($format) {
            case 'long':
                return userdate($timestamp, get_string('strftimedatetime', 'langconfig'));
            case 'relative':
                return format_time(time() - $timestamp);
            case 'short':
                return userdate($timestamp, get_string('strftimedate', 'langconfig'));
            default:
                $date = new \DateTime();
                $date->setTimestamp(intval($timestamp));
                return userdate($date->getTimestamp(), $format);
        }
    }

    /**
     * Generate a unique reference ID.
     *
     * @param string $prefix Optional prefix
     * @return string Unique reference
     */
    public static function generate_reference($prefix = 'MC'): string {
        return $prefix . '-' . strtoupper(uniqid());
    }

    /**
     * Clean and validate content data.
     *
     * @param object $data Content data object
     * @return object Cleaned data
     */
    public static function clean_content_data($data): object {
        $data->title = clean_param($data->title ?? '', PARAM_TEXT);
        $data->contenttype = clean_param($data->contenttype ?? 'page', PARAM_ALPHANUM);
        $data->status = isset($data->status) ? (int) $data->status : 0;
        $data->visible = isset($data->visible) ? (int) $data->visible : 1;
        $data->sortorder = isset($data->sortorder) ? (int) $data->sortorder : 0;

        return $data;
    }

    /**
     * Get content type options for forms.
     *
     * @return array Options array
     */
    public static function get_content_type_options(): array {
        return [
            'page' => get_string('contenttype_page', 'local_mchelpers'),
            'faq' => get_string('contenttype_faq', 'local_mchelpers'),
            'testimonial' => get_string('contenttype_testimonial', 'local_mchelpers'),
            'article' => get_string('contenttype_article', 'local_mchelpers'),
            'video' => get_string('contenttype_video', 'local_mchelpers'),
            'announcement' => get_string('contenttype_announcement', 'local_mchelpers'),
        ];
    }

    /**
     * Get status label.
     *
     * @param int $status Status value
     * @return string Status label
     */
    public static function get_status_label($status): string {
        return $status === 1
            ? get_string('published', 'local_mchelpers')
            : get_string('draft', 'local_mchelpers');
    }

    /**
     * Get status class for styling.
     *
     * @param int $status Status value
     * @return string CSS class name
     */
    public static function get_status_class($status): string {
        return $status === 1 ? 'text-success' : 'text-muted';
    }

    /**
     * Logs exceptions with backtrace to a secure file.
     *
     * @param \Throwable $throwable
     *   The exception or error to log.
     * $type can be 'error', 'message', 'log' or any other string to categorize the log entry.
     *
     * @return void
     */
    public static function set_log_message($th, $type = ''): void {
        global $CFG;

        $log_dir = $CFG->dataroot . '/helperbox_log';
        $log_file = $log_dir . '/' . date("Y-m") . '-log.txt';

        // Create directory if it does not exist
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0775, true);
        }

        $log_message = "[" . date("Y-m-d H:i:s") . "] ";

        if (strtolower($type) === 'error') {
            $backtrace = debug_backtrace();
            $initial_error_file = $backtrace[1]['file'] ?? '';
            $initial_error_line = $backtrace[1]['line'] ?? '';

            $log_message .= "ERROR: " . $th->getMessage() . " in " . $th->getFile() . " on line " . $th->getLine();

            if ($initial_error_file && $initial_error_line) {
                $log_message .= " | Initial Error File: {$initial_error_file} on line {$initial_error_line}";
            }

            $log_message .= PHP_EOL;
        } elseif (strtolower($type) === 'message') {
            $log_message .= "MESSAGE: " . $th . PHP_EOL;
        } else {
            $log_message .= $th . PHP_EOL;
        }

        // Write log safely
        if (is_writable($log_dir)) {
            file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
        }
    }


    /**
     * security_header
     */
    public static function security_header() {
        // security header
        @header('X-Frame-Options: SAMEORIGIN');
        @header('Referrer-Policy: strict-origin-when-cross-origin');
        @header('X-Content-Type-Options: nosniff');
        @header('X-XSS-Protection: 1; mode=block');
        @header("Content-Security-Policy: frame-ancestors 'self';");
        @header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    /**
     * set_extra_css_js
     * @param string $path path to your theme example '/theme/yourtheme'
     */
    public static function set_extra_css_js($path) {
        global $PAGE, $CFG;
        // $theme = isset($PAGE->theme->name) ? $PAGE->theme->name : '';
        $style_path = $path . "/style";
        $js_path = $path . "/javascript";
        /**
         * css files
         */
        $page_css = [];
        // By page type
        $pagetype = $PAGE->pagetype;
        $filepath = $CFG->dirroot . $style_path . '/' . $pagetype . '.css';
        if (file_exists($filepath)) {
            $page_css[] =  $pagetype;
        }
        // By page layout
        $pagelayout = $PAGE->pagelayout;
        $filepath = $CFG->dirroot . $style_path . '/' . $pagelayout . '.css';
        if (file_exists($filepath)) {
            $page_css[] =  $pagelayout;
        }
        foreach ($page_css as $key => $value) {
            $PAGE->requires->css(new moodle_url($CFG->wwwroot . $style_path . '/' . $value . '.css'));
        }

        /**
         * js files
         */
        $page_js = [];
        // By page type
        $pagetype = $PAGE->pagetype;
        $filepath = $CFG->dirroot . $js_path . '/' . $pagetype . '.js';
        if (file_exists($filepath)) {
            $page_js[] =  $pagetype;
        }
        // By page layout
        $pagelayout = $PAGE->pagelayout;
        $filepath = $CFG->dirroot . $js_path . '/' . $pagelayout . '.js';
        if (file_exists($filepath)) {
            $page_js[] =  $pagelayout;
        }
        foreach ($page_js as $key => $value) {
            $PAGE->requires->js(new moodle_url($CFG->wwwroot .  $js_path . '/' . $value . '.js'));
        }
    }


    /**
     * Get Moodle URL object.
     *
     * @param string $path URL path
     * @param array $params URL parameters
     * @param bool $asstring Whether to return the URL as a string (default: false = return moodle_url object)
     * @param bool $raw Whether to return raw URL without HTML encoding (default: false = HTML escaped for display)
     * @return \moodle_url|string|false Moodle URL object, URL string, or false if the path is invalid
     */
    public static function get_moodle_url($path, $params = [], $asstring = false, $raw = false) {
        global $CFG;

        if (file_exists($CFG->dirroot . $path)) {
            $moodle_url = new \moodle_url($path, $params);

            if ($asstring) {
                return $raw ? $moodle_url->raw_out(false) : $moodle_url->out(false);
            }

            return $moodle_url;
        }
        return false;
    }
}
