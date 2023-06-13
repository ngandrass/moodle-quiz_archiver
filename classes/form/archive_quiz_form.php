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
 * Defines the editing form for random questions.
 *
 * @package    quiz_archiver
 * @copyright  2023 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class archive_quiz_form extends \moodleform {

    /**
     * Form definiton.
     */
    public function definition() {
        $mform = $this->_form;

        // Internal information of mod_quiz
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options
        $mform->addElement('header', 'options_header', get_string('options'));
        $mform->addElement('advcheckbox', 'export_attempts', get_string('export_attempts', 'quiz_archiver'), '', ['disabled' => 'disabled'], ['1', '1']);
        $mform->setDefault('export_attempts', true);
        $mform->addElement('advcheckbox', 'export_course_backup', get_string('export_course_backup', 'quiz_archiver'));
        $mform->setDefault('export_course_backup', true);

        // Submit
        $mform->addElement('submit', 'submitbutton', get_string('archive_quiz', 'quiz_archiver'));
    }

}
