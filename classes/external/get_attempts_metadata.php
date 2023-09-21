<?php
namespace quiz_archiver\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use quiz_archiver\Report;

defined('MOODLE_INTERNAL') || die();

class get_attempts_metadata extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'ID of course', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'ID of the course module', VALUE_REQUIRED),
            'quizid' => new external_value(PARAM_INT, 'ID of the quiz', VALUE_REQUIRED),
            'attemptids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'ID of the quiz attempt', VALUE_REQUIRED)
            , 'List of quiz attempt IDs to query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the executed wsfunction', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_INT, 'ID of course', VALUE_OPTIONAL),
            'cmid' => new external_value(PARAM_INT, 'ID of the course module', VALUE_OPTIONAL),
            'quizid' => new external_value(PARAM_INT, 'ID of the quiz', VALUE_OPTIONAL),
            'attempts' => new external_multiple_structure(
                new external_single_structure([
                    'attemptid' => new external_value(PARAM_INT, 'ID of the quiz attempt', VALUE_REQUIRED),
                    'userid' => new external_value(PARAM_INT, 'ID of the user for this quit attempt', VALUE_REQUIRED),
                    'username' => new external_value(PARAM_TEXT, 'Username for this quiz attempt', VALUE_REQUIRED),
                    'firstname' => new external_value(PARAM_TEXT, 'First name for this quiz attempt', VALUE_REQUIRED),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name for this quiz attempt', VALUE_REQUIRED),
                    'timestart' => new external_value(PARAM_INT, 'Timestamp of when the quiz attempt started', VALUE_REQUIRED),
                    'timefinish' => new external_value(PARAM_INT, 'Timestamp of when the quiz attempt finished', VALUE_REQUIRED),
                    'attempt' => new external_value(PARAM_INT, 'Sequential attempt number', VALUE_REQUIRED),
                    'state' => new external_value(PARAM_TEXT, 'State of the quiz attempt', VALUE_REQUIRED),
                ])
            , 'Attempt metadata for each attempt ID', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Generate an quiz attempt report as HTML DOM
     *
     * @param int $courseid_raw ID of the course
     * @param int $cmid_raw ID of the course module
     * @param int $quizid_raw ID of the quiz
     * @param array $attemptids_raw IDs of the quiz attempts
     *
     * @return array According to execute_returns()
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public static function execute(int $courseid_raw, int $cmid_raw, int $quizid_raw, array $attemptids_raw): array {
        global $DB;

        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid_raw,
            'cmid' => $cmid_raw,
            'quizid' => $quizid_raw,
            'attemptids' => $attemptids_raw
        ]);

        // Check capabilities
        $context = \context_module::instance($params['cmid']);
        require_capability('mod/quiz_archiver:use_webservice', $context);

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

        // Extract attempt metadata
        $report = new Report($course, $cm, $quiz);
        if (!$report->has_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED'
            ];
        }
        $attempt_metadata = $report->get_attempts_metadata($params['attemptids']);

        return [
            'courseid' => $params['courseid'],
            'cmid' => $params['cmid'],
            'quizid' => $params['quizid'],
            'attempts' => $attempt_metadata,
            'status' => 'OK'
        ];
    }

}