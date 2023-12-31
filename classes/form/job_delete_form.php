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
 * Defines the editing form for artifacts
 *
 * @package    quiz_archiver
 * @copyright  2024 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

use quiz_archiver\ArchiveJob;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');


/**
 * Form to trigger deletion of an archive job
 */
class job_delete_form extends \moodleform {

    /**
     * Form definiton.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        $mform = $this->_form;

        // Find job
        $job = ArchiveJob::get_by_jobid($this->optional_param('jobid', null, PARAM_TEXT));
        $artifactfile = $job->get_artifact();

        // Generic warning message
        $warn_head = get_string('delete_job', 'quiz_archiver');
        $warn_msg = get_string('delete_job_warning', 'quiz_archiver');
        $warn_details = get_string('jobid', 'quiz_archiver').': '.$job->get_jobid();

        // Add artifact details if available
        if ($artifactfile) {
            $warn_details .= '<br>';
            $warn_details .= get_string('quiz_archive', 'quiz_archiver').': ' .$artifactfile->get_filename().' ('.display_size($artifactfile->get_filesize()).')';
        }

        // Warn additionally if job is scheduled for automatic deletion
        if ($job->is_autodelete_enabled()) {
            if ($job->get_status() === ArchiveJob::STATUS_FINISHED) {
                $warn_msg .= '<br><br>';
                $warn_msg .= get_string(
                    'delete_job_warning_retention',
                    'quiz_archiver',
                    userdate($job->get_retentiontime(), get_string('strftimedatetime', 'langconfig'))
                );
            }
        }

        // Print warning element
        $mform->addElement('html', <<<EOD
            <div class="alert alert-warning" role="alert">
                <h4>$warn_head</h4>
                $warn_msg
                <hr/>
                $warn_details
            </div>
        EOD);

        // Preserve internal information of mod_quiz
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options
        $mform->addElement('hidden', 'action', 'delete_job');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'jobid', $job->get_jobid());
        $mform->setType('jobid', PARAM_TEXT);

        // Action buttons
        $this->add_action_buttons(true, get_string('delete', 'moodle'));
    }

}
