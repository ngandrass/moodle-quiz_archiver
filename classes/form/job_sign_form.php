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
 * Defines the signing form for artifacts
 *
 * @package    quiz_archiver
 * @copyright  2025 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore


/**
 * Form to trigger manual archive signing
 */
class job_sign_form extends \moodleform {

    /**
     * Form definiton.
     *
     * @throws \coding_exception
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        // Warning message.
        $warnhead = get_string('areyousure', 'moodle');
        $warnmsg = get_string('sign_archive_warning', 'quiz_archiver', $this->optional_param('jobid', null, PARAM_TEXT));
        $warndetails = get_string('jobid', 'quiz_archiver').': '.$this->optional_param('jobid', null, PARAM_TEXT);
        $mform->addElement('html', $OUTPUT->notification(
            "<h4>$warnhead</h4> $warnmsg <hr/> $warndetails",
            \core\output\notification::NOTIFY_INFO,
            false,
        ));

        // Preserve internal information of mod_quiz.
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options.
        $mform->addElement('hidden', 'action', 'sign_job');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'jobid', $this->optional_param('jobid', null, PARAM_TEXT));
        $mform->setType('jobid', PARAM_TEXT);

        // Action buttons.
        $this->add_action_buttons(true, get_string('sign_archive', 'quiz_archiver'));
    }

}
