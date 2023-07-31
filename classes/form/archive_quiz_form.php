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
 * Defines the job task process form
 *
 * @package    quiz_archiver
 * @copyright  2023 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

use quiz_archiver\Report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class archive_quiz_form extends \moodleform {

    /** @var string Name of the quiz to be exportet */
    protected string $quiz_name;
    /** @var int Number of attempts to be exported */
    protected int $num_attempts;

    /**
     * @param int $num_attempts Number of attempts to be exported
     */
    public function __construct(string $quiz_name, int $num_attempts) {
        $this->quiz_name = $quiz_name;
        $this->num_attempts = $num_attempts;
        parent::__construct();
    }

    /**
     * Form definiton.
     */
    public function definition() {
        $mform = $this->_form;

        // Add description text
        $mform->addElement('html', '<p>'.get_string('archive_quiz_form_desc', 'quiz_archiver').'</p>');

        // Internal information of mod_quiz
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options
        $mform->addElement('header', 'header', get_string('settings'));

        // Options: Test
        $mform->addElement('static', 'quiz_name', get_string('modulename', 'mod_quiz'), $this->quiz_name);

        // Options: Attempts
        $mform->addElement('advcheckbox', 'export_attempts', get_string('attempts', 'mod_quiz'), get_string('export_attempts_num', 'quiz_archiver', $this->num_attempts), ['disabled' => 'disabled'], ['1', '1']);
        $mform->setDefault('export_attempts', true);

        foreach (Report::SECTIONS as $section) {
            $mform->addElement('advcheckbox', 'export_report_section_'.$section, '&nbsp;', get_string('export_report_section_'.$section, 'quiz_archiver'));
            $mform->setDefault('export_report_section_'.$section, true);
        }

        // Options: Backups
        $mform->addElement('advcheckbox', 'export_quiz_backup', get_string('backups', 'admin'), get_string('export_quiz_backup', 'quiz_archiver'));
        $mform->setDefault('export_quiz_backup', true);

        $mform->addElement('advcheckbox', 'export_course_backup', '&nbsp;', get_string('export_course_backup', 'quiz_archiver'));
        $mform->setDefault('export_course_backup', false);

        // Submit
        $mform->addElement('submit', 'submitbutton', get_string('archive_quiz', 'quiz_archiver'));
    }

}
