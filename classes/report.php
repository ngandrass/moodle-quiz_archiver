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

namespace quiz_archiver;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/report/default.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->libdir . '/pagelib.php');

class Report {

    /** @var object Moodle course this report is part of */
    protected object $course;
    /** @var object Course module the quiz is part of */
    protected object $cm;
    /** @var object Quiz the attempt is part of */
    protected object $quiz;

    /**
     * @param object $course
     * @param object $cm
     * @param object $quiz
     */
    public function __construct(object $course, object $cm, object $quiz) {
        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
    }

    /**
     * Get all attempts for all users inside this quiz, excluding previews
     *
     * @return array Array of all attempt IDs together with the userid that were
     * made inside this quiz. Indexed by attemptid.
     *
     * @throws \dml_exception
     */
    public function get_attempts(): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT id AS attemptid, userid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid",
            [
                "quizid" => $this->quiz->id,
            ]
        );
    }

    /**
     * Returns a list of IDs of all users that made at least one attempt on this
     * quiz, excluding previews
     *
     * @return array List of IDs of found users
     *
     * @throws \dml_exception
     */
    public function get_users_with_attempts(): array {
        global $DB;

        $res = $DB->get_records_sql(
            "SELECT DISTINCT userid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid",
            [
                "quizid" => $this->quiz->id,
            ]
        );

        return array_map(fn($v): int => $v->userid, $res);
    }

    /**
     * Returns the ID of the latest attempt a user made on this quiz, excluding
     * previews
     *
     * @param int $userid The ID of the user to search for an attempt
     *
     * @return ?int ID of the latest attempt the given user made on this quiz.
     * Null if no attempt was made.
     *
     * @throws \dml_exception
     */
    public function get_latest_attempt_for_user($userid): ?int {
        global $DB;

        $res = $DB->get_records_sql(
            "SELECT id AS attemptid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid AND userid = :userid ".
            "ORDER BY id DESC ".
            "LIMIT 1",
            [
                "quizid" => $this->quiz->id,
                "userid" => $userid
            ]
        );

        if (empty($res)) {
            return null;
        }

        return array_values($res)[0]->attemptid;
    }

    /**
     * Checks if an attempt with the given ID exists inside this quiz
     *
     * @param int $attemptid ID of the attempt to check for existence
     * @return bool True if an attempt with the given ID exists inside this quiz
     * @throws \dml_exception
     */
    public function attempt_exists(int $attemptid): bool {
        global $DB;

        return $DB->count_records_sql(
            "SELECT COUNT(id) FROM {quiz_attempts} WHERE preview = 0 AND id = :attemptid",
            ['attemptid' => $attemptid]
        ) > 0;
    }

    /**
     * Generates a HTML representation of the quiz attempt
     *
     * @param int $attemptid ID of the attempt this report is for
     *
     * @return string HTML DOM of the rendered quiz attempt report
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function generate(int $attemptid): string {
        global $DB, $PAGE;
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->cm->id);

        // Summary table start.
        // ============================================================================.

        // Work out some time-related things.
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $options = \mod_quiz_display_options::make_from_quiz($this->quiz, quiz_attempt_state($quiz, $attempt));
        $options->flags = quiz_get_flag_option($attempt, \context_module::instance($this->cm->id));
        $overtime = 0;

        if ($attempt->state == \quiz_attempt::FINISHED) {
            if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
                if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
                    $overtime = $timetaken - $quiz->timelimit;
                    $overtime = format_time($overtime);
                }
                $timetaken = format_time($timetaken);
            } else {
                $timetaken = "-";
            }
        } else {
            $timetaken = get_string('unfinished', 'quiz');
        }

        // Prepare summary information about the whole attempt.
        $summarydata = array();
        // We want the user information no matter what.
        $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
        $userpicture = new \user_picture($student);
        $userpicture->courseid = $attemptobj->get_courseid();
        $summarydata['user'] = array(
            'title'   => $userpicture,
            'content' => new \action_link(new \moodle_url('/user/view.php', array(
                'id' => $student->id, 'course' => $attemptobj->get_courseid())),
                fullname($student, true)),
        );

        // Timing information.
        $summarydata['startedon'] = array(
            'title'   => get_string('startedon', 'quiz'),
            'content' => userdate($attempt->timestart),
        );

        $summarydata['state'] = array(
            'title'   => get_string('attemptstate', 'quiz'),
            'content' => \quiz_attempt::state_name($attempt->state),
        );

        if ($attempt->state == \quiz_attempt::FINISHED) {
            $summarydata['completedon'] = array(
                'title'   => get_string('completedon', 'quiz'),
                'content' => userdate($attempt->timefinish),
            );
            $summarydata['timetaken'] = array(
                'title'   => get_string('timetaken', 'quiz'),
                'content' => $timetaken,
            );
        }

        if (!empty($overtime)) {
            $summarydata['overdue'] = array(
                'title'   => get_string('overdue', 'quiz'),
                'content' => $overtime,
            );
        }

        // Show marks (if the user is allowed to see marks at the moment).
        $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
        if ($options->marks >= \question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {

            if ($attempt->state != \quiz_attempt::FINISHED) {
                // Cannot display grade.
                echo '';
            } else if (is_null($grade)) {
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => quiz_format_grade($quiz, $grade),
                );

            } else {
                // Show raw marks only if they are different from the grade (like on the view page).
                if ($quiz->grade != $quiz->sumgrades) {
                    $a = new \stdClass();
                    $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
                    $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
                    $summarydata['marks'] = array(
                        'title'   => get_string('marks', 'quiz'),
                        'content' => get_string('outofshort', 'quiz', $a),
                    );
                }

                // Now the scaled grade.
                $a = new \stdClass();
                $a->grade = \html_writer::tag('b', quiz_format_grade($quiz, $grade));
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                if ($quiz->grade != 100) {
                    $a->percent = \html_writer::tag('b', format_float(
                        $attempt->sumgrades * 100 / $quiz->sumgrades, 0));
                    $formattedgrade = get_string('outofpercent', 'quiz', $a);
                } else {
                    $formattedgrade = get_string('outof', 'quiz', $a);
                }
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => $formattedgrade,
                );
            }
        }

        // Any additional summary data from the behaviour.
        $summarydata = array_merge($summarydata, $attemptobj->get_additional_summary_data($options));

        // Feedback if there is any, and the user is allowed to see it now.
        $feedback = $attemptobj->get_overall_feedback($grade);
        if ($options->overallfeedback && $feedback) {
            $summarydata['feedback'] = array(
                'title' => get_string('feedback', 'quiz'),
                'content' => $feedback,
            );
        }

        // Summary table end.
        // ==============================================================================.

        $slots = $attemptobj->get_slots();

        $renderer = $PAGE->get_renderer('mod_quiz');
        $string = '';
        $string .= $renderer->review_summary_table($summarydata, 0);

        // Display the questions. The overall goal is to have question_display_options from question/engine/lib.php
        // set so they would show what we wand and not show what we don't want.

        // Here we would call questions function on the renderer from mod/quiz/renderer.php but instead we do this
        // manually.
        foreach ($slots as $slot) {
            // Here we would call render_question_helper function on the quiz_attempt from mod/quiz/renderer.php but
            // instead we do this manually.

            $originalslot = $attemptobj->get_original_slot($slot);
            $number = $attemptobj->get_question_number($originalslot);
            $displayoptions = $attemptobj->get_display_options_with_edit_link(true, $slot, "");
            $displayoptions->marks = 2;
            $displayoptions->manualcomment = 1;
            $displayoptions->feedback = 1;
            $displayoptions->history = true;
            $displayoptions->correctness = 1;
            $displayoptions->numpartscorrect = 1;
            $displayoptions->flags = 1;
            $displayoptions->manualcommentlink = 0;

            if ($slot != $originalslot) {
                $attemptobj->get_question_attempt($slot)->set_max_mark(
                    $attemptobj->get_question_attempt($originalslot)->get_max_mark());
            }
            $quba = \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
            $string .= $quba->render_question($slot, $displayoptions, $number);

        }

        return $string;
    }

}