<?php
namespace quiz_archiver\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use quiz_archiver\report;

class generate_attempt_report extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID of course', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'ID of the course module', VALUE_REQUIRED),
            'quizid' => new external_value(PARAM_INT, 'ID of the quiz', VALUE_REQUIRED),
            'attemptid' => new external_value(PARAM_INT, 'ID of the quiz attempt', VALUE_REQUIRED)
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'ID of course'),
            'cmid' => new external_value(PARAM_INT, 'ID of the course module'),
            'quizid' => new external_value(PARAM_INT, 'ID of the quiz'),
            'attemptid' => new external_value(PARAM_INT, 'ID of the quiz attempt'),
            'report' => new external_value(PARAM_RAW, 'HTML DOM of the generated quiz attempt report')
        ]);
    }

    /**
     * Generate an quiz attempt report as HTML DOM
     *
     * @param int $courseid_raw ID of the course
     * @param int $cmid_raw ID of the course module
     * @param int $quizid_raw ID of the quiz
     * @param int $attemptid_raw ID of the quiz attempt
     *
     * @return array According to execute_returns()
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public static function execute(int $courseid_raw, int $cmid_raw, int $quizid_raw, int $attemptid_raw): array {
        global $DB;

        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid_raw,
            'cmid' => $cmid_raw,
            'quizid' => $quizid_raw,
            'attemptid' => $attemptid_raw
        ]);

        $context = \context_module::instance($params['cmid']);
        require_capability('mod/quiz:grade', $context);
        require_capability('quiz/grading:viewstudentnames', $context);
        require_capability('quiz/grading:viewidnumber', $context);

        // Acquire required data objects
        if (!$course = $DB->get_record('course', array('id' => $params['courseid']))) {
            throw new \invalid_parameter_exception("No course with given courseid found");
        }
        if (!$cm = get_coursemodule_from_instance("quiz", $params['quizid'], $params['courseid'])) {
            throw new \invalid_parameter_exception("No course module with given cmid found");
        }
        if (!$quiz = $DB->get_record('quiz', array('id' => $params['quizid']))) {
            throw new \invalid_parameter_exception("No quiz with given quizid found");
        }

        // Generate report
        $report = new Report($course, $cm, $quiz);
        if (!$report->attempt_exists($params['attemptid'])) {
            throw new \invalid_parameter_exception("No attempt with given attemptid found");
        }

        return [
            'courseid' => $params['courseid'],
            'cmid' => $params['cmid'],
            'quizid' => $params['quizid'],
            'attemptid' => $params['attemptid'],
            'report' => $report->generate($params['attemptid'])
        ];
    }

}