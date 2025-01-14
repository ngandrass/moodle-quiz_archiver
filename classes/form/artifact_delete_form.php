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
 * Defines the deletion form for job artifacts
 *
 * @package    quiz_archiver
 * @copyright  2025 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

use quiz_archiver\ArchiveJob;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore


/**
 * Form to trigger deletion of a job artifact
 */
class artifact_delete_form extends \moodleform {

    /**
     * Form definiton.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        // Find job.
        $job = ArchiveJob::get_by_jobid($this->optional_param('jobid', null, PARAM_TEXT));
        $artifactfile = $job->get_artifact();

        // Generic warning message.
        $warnhead = get_string('delete_artifact', 'quiz_archiver');

        if ($artifactfile) {
            $warnmsg = get_string('delete_artifact_warning', 'quiz_archiver');
            $warndetails = get_string('jobid', 'quiz_archiver').': '.$job->get_jobid();
            $warndetails .= '<br>';
            $warndetails .= get_string('quiz_archive', 'quiz_archiver').': '.$artifactfile->get_filename().
                            ' ('.display_size($artifactfile->get_filesize()).')';

            // Warn additionally if job is scheduled for automatic deletion.
            if ($job->is_autodelete_enabled()) {
                if ($job->get_status() === ArchiveJob::STATUS_FINISHED) {
                    $warnmsg .= '<br><br>';
                    $warnmsg .= get_string(
                        'delete_job_warning_retention',
                        'quiz_archiver',
                        userdate($job->get_retentiontime(), get_string('strftimedatetime', 'langconfig'))
                    );
                }
            }
        } else {
            $warnmsg = get_string('error').': '.get_string('quiz_archive_not_found', 'quiz_archiver', $job->get_jobid());
            $warndetails = get_string('jobid', 'quiz_archiver').': '.$job->get_jobid();
        }

        // Print warning element.
        $mform->addElement('html', $OUTPUT->notification(
            "<h4>$warnhead</h4> $warnmsg <hr/> $warndetails",
            \core\output\notification::NOTIFY_WARNING,
            false,
        ));

        // Preserve internal information of mod_quiz.
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        if ($artifactfile) {
            // Options.
            $mform->addElement('hidden', 'action', 'delete_artifact');
            $mform->setType('action', PARAM_TEXT);
            $mform->addElement('hidden', 'jobid', $job->get_jobid());
            $mform->setType('jobid', PARAM_TEXT);

            // Action buttons.
            $this->add_action_buttons(true, get_string('delete', 'moodle'));
        } else {
            $this->add_action_buttons(false, get_string('back'));
        }
    }

}
