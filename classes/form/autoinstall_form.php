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

use quiz_archiver\local\autoinstall;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore
require_once($CFG->dirroot.'/mod/quiz/report/archiver/classes/local/autoinstall.php'); // @codeCoverageIgnore


/**
 * Form to trigger automatic installation of the quiz archiver plugin
 */
class autoinstall_form extends \moodleform {

    /**
     * Form definiton.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'header', get_string('settings', 'plugin'));

        // Add configuration options.
        $mform->addElement('text', 'workerurl', get_string('setting_worker_url', 'quiz_archiver'), ['size' => 50]);
        $mform->addElement('static', 'workerurl_help', '', get_string('setting_worker_url_desc', 'quiz_archiver'));
        $mform->setType('workerurl', PARAM_TEXT);
        $mform->addRule('workerurl', null, 'required', null, 'client');

        $mform->addElement('text', 'wsname', get_string('autoinstall_wsname', 'quiz_archiver'), ['size' => 50]);
        $mform->addElement('static', 'wsname_help', '', get_string('autoinstall_wsname_help', 'quiz_archiver'));
        $mform->setDefault('wsname', autoinstall::DEFAULT_WSNAME);
        $mform->setType('wsname', PARAM_TEXT);
        $mform->addRule('wsname', null, 'required', null, 'client');

        $mform->addElement('text', 'rolename', get_string('autoinstall_rolename', 'quiz_archiver'), ['size' => 50]);
        $mform->addElement('static', 'rolename_help', '', get_string('autoinstall_rolename_help', 'quiz_archiver'));
        $mform->setDefault('rolename', autoinstall::DEFAULT_ROLESHORTNAME);
        $mform->setType('rolename', PARAM_TEXT);
        $mform->addRule('rolename', null, 'required', null, 'client');

        $mform->addElement('text', 'username', get_string('autoinstall_username', 'quiz_archiver'), ['size' => 50]);
        $mform->addElement('static', 'username_help', '', get_string('autoinstall_username_help', 'quiz_archiver'));
        $mform->setDefault('username', autoinstall::DEFAULT_USERNAME);
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', null, 'required', null, 'client');

        // Action buttons.
        $this->add_action_buttons(true, get_string('confirm', 'moodle'));
    }

}
