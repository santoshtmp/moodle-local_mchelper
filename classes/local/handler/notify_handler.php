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
 * Notification handler for local_mchelpers plugin.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mchelpers\local\handler;

use core\output\html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Handler class for sending notifications and emails.
 *
 * @package    local_mchelpers
 * @copyright  2026 https://santoshmagar.com.np/
 * @author     santoshmagar.com.np
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notify_handler {

    /**
     * Send message notification within Moodle.
     *
     * @param object $user User object to send notification to
     * @param string $msg_subject Message subject
     * @param string $fullmessage Full message content
     * @return int|false Message ID on success, false on failure
     */
    public static function send_message($user, $msg_subject, $fullmessage) {

        $message = new \core\message\message();
        $message->component = 'local_mchelpers';
        $message->name = 'local_mchelpers_notification';
        $message->userfrom = \core\user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $msg_subject;
        $message->fullmessage = '';
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $fullmessage;
        $message->smallmessage = '';
        $message->notification = 1;
        $content = ['*' => ['header' => '  ', 'footer' => '  ']];
        $message->set_additional_content('email', $content);

        // Actually send the message
        $messageid = message_send($message);

        return $messageid;
    }

    /**
     * Send email notification (used in contact us form or other).
     *
     * @param string $sendto_email Recipient email address
     * @param string $sendto_name Recipient name
     * @param string $subject Email subject
     * @param string $htmlmessage HTML message content
     * @param string $no_reply Reply-to address (defaults to noreplyaddress)
     * @return bool True on success, false on failure
     */
    public static function send_email(
        $sendto_email,
        $sendto_name,
        $subject,
        $htmlmessage,
        $no_reply = ''
    ) {
        global $CFG, $USER, $SITE, $PAGE;
        $systemcontext = \context_system::instance();

        // Create the recipient.
        $to_user = self::make_emailuser($sendto_email, $sendto_name);

        // Create the sender from the submitted name and email address.
        $site_short_name = format_text($SITE->shortname, FORMAT_HTML, ['context' => $systemcontext]);
        $from = self::make_emailuser('', $site_short_name);

        // Check no reply.
        $no_reply = ($no_reply) ?: $CFG->noreplyaddress;

        // Add message footer.
        $htmlmessage .= '<hr>';
        $htmlmessage .= html_writer::tag(
            'p',
            "Message From " . $site_short_name . " : " . html_writer::link($PAGE->url->out(), $PAGE->heading) . " Page."
        );
        $htmlmessage .= $PAGE->url->out();

        $response = email_to_user(
            $to_user,
            $from,
            $subject,
            $messagetext = '',
            $messagehtml = $htmlmessage,
            $attachment = '',
            $attachname = '',
            $usetrueaddress = true,
            $replyto = $no_reply,
            $replytoname = $no_reply,
            $wordwrapwidth = 79
        );

        return $response;
    }

    /**
     * Creates a user info object based on provided parameters.
     *
     * @param string $email Email address
     * @param string $name Plain text real name (optional)
     * @param int $id Moodle user ID (optional, default -99)
     * @return object Moodle user info object
     */
    public static function make_emailuser($email, $name = '', $id = -99) {
        $emailuser = new \stdClass();
        $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailuser->email = '';
        }
        $emailuser->firstname = format_text($name, FORMAT_PLAIN, ['trusted' => false]);
        $emailuser->lastname = '';
        $emailuser->maildisplay = true;
        $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML emails.
        $emailuser->id = $id;
        $emailuser->firstnamephonetic = '';
        $emailuser->lastnamephonetic = '';
        $emailuser->middlename = '';
        $emailuser->alternatename = '';
        $emailuser->username = '';
        return $emailuser;
    }

    /**
     * Send API failure notification to configured users.
     *
     * @param string $subject Notification subject
     * @param string $full_message Full message content
     * @return void
     */
    public static function callback_api_fail_notification($subject, $full_message) {
        $api_fail_notify_user_id = get_config('local_mchelpers', 'api_fail_notify_user_id');
        $api_fail_notify_user_ids = explode(',', $api_fail_notify_user_id);
        if (is_array($api_fail_notify_user_ids)) {
            foreach ($api_fail_notify_user_ids as $user_id) {
                $user_id = (int)$user_id;
                $notify_user = \core\user::get_user($user_id);
                if ($notify_user) {
                    self::send_message($notify_user, $subject, $full_message);
                }
            }
        }
    }
}
