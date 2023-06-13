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
use quiz_archiver\form\archive_quiz_form;
use quiz_archiver\report;

defined('MOODLE_INTERNAL') || die();

class quiz_archiver_report extends quiz_default_report {

    /** @var object course object. */
    protected $course;
    /** @var object course module object. */
    protected $cm;
    /** @var object the quiz settings object. */
    protected $quiz;
    /** @var context the quiz context. */
    protected $context;
    /** @var report internal report instance */
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
    public function display($quiz, $cm, $course): bool {
        global $OUTPUT;

        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->report = new Report($this->course, $this->cm, $this->quiz);

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);
        require_capability('quiz/grading:viewstudentnames', $this->context);
        require_capability('quiz/grading:viewidnumber', $this->context);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'archiver');

        // What sort of page to display?
        if (!quiz_has_questions($quiz->id)) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else {
            echo "Course-ID: $course->id <br>";
            echo "CM-ID: $cm->id <br>";
            echo "Quiz-ID: $quiz->id <br>";

            echo $OUTPUT->render_from_template("quiz_archiver/overview", [
                "num_users_with_attempts" => sizeof($this->report->get_users_with_attempts()),
                "num_attempts" => sizeof($this->report->get_attempts())
            ]);

            $archive_quiz_form = new archive_quiz_form();
            if ($archive_quiz_form->is_submitted()) {
                print_r($archive_quiz_form->get_data());
            } else {
                $archive_quiz_form->display();
            }

            $config = get_config('quiz_archiver');
            echo "CONFIG: "; print_r($config);
        }

        return true;
    }

    protected function initiate_archive_job(bool $export_attempts, bool $export_course_backup) {
        // Create temporary webservice token
        external_generate_token(EXTERNAL_TOKEN_PERMANENT, $data->service, $data->user, context_system::instance(), $data->validuntil, $data->iprestriction);



        // ...
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
