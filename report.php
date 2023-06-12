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
 * This file defines the quiz archiver class.
 *
 * @package   quiz_archiver
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\report_base;
use quiz_archiver\Report;

defined('MOODLE_INTERNAL') || die();

class quiz_archiver_report extends quiz_default_report {
    /** @var object the questions that comprise this quiz.. */
    protected $questions;
    /** @var object course module object. */
    protected $cm;
    /** @var object the quiz settings object. */
    protected $quiz;
    /** @var context the quiz context. */
    protected $context;
    /** @var students the students having attempted the quiz. */
    protected $students;

    protected Report $report;

    /**
     * Display the report.
     *
     * @param object $quiz this quiz.
     * @param object $cm the course-module for this quiz.
     * @param object $course the course we are in.
     * @return bool
     * @throws moodle_exception
     */
    public function display($quiz, $cm, $course) {
        global $PAGE;

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;
        $this->report = new Report($this->course, $this->cm, $this->quiz);

        // Get the URL options.
        $slot = optional_param('slot', null, PARAM_INT);
        $userid = optional_param('userid', null, PARAM_INT);

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);
        require_capability('quiz/grading:viewstudentnames', $this->context);
        require_capability('quiz/grading:viewidnumber', $this->context);

        // Get the list of questions in this quiz.
        $this->questions = quiz_report_get_significant_questions($quiz);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'archiver');

        // What sort of page to display?
        if (!quiz_has_questions($quiz->id)) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else {
            echo "Course-ID: $course->id <br>";
            echo "CM-ID: $cm->id <br>";
            echo "Quiz-ID: $quiz->id <br>";
            echo "Users with attempts: " . implode(", ", $this->report->get_users_with_attempts()) . "<br>";
            echo "Attempts: "; print_r($this->report->get_attempts()); echo "<br>";

            if ($userid > 0) {
                echo "DISPLAY STUFF FOR USER: $userid <br>";
                echo $this->report->generate($this->report->get_latest_attempt_for_user($userid));
            } else {
                echo "No userid given D:";
            }
        }

        return true;
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @return string the URL.
     */
    protected function base_url() {
        return new moodle_url('/mod/quiz/report.php',
            array('id' => $this->cm->id, 'mode' => 'archiver'));
    }

}
