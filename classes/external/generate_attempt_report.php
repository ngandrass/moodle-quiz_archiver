<?php
namespace quiz_archiver\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use quiz_archiver\Report;

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
            'attemptid' => new external_value(PARAM_INT, 'ID of the quiz attempt', VALUE_REQUIRED),
            'sections' => new external_single_structure(
                array_combine(Report::SECTIONS,
                    array_map(fn ($section): external_value => new external_value(
                        PARAM_BOOL,
                        'Whether to include the '.$section.' section',
                        VALUE_REQUIRED
                    ), Report::SECTIONS)
                ),
                'Sections to include in the report',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'ID of course', VALUE_OPTIONAL),
            'cmid' => new external_value(PARAM_INT, 'ID of the course module', VALUE_OPTIONAL),
            'quizid' => new external_value(PARAM_INT, 'ID of the quiz', VALUE_OPTIONAL),
            'attemptid' => new external_value(PARAM_INT, 'ID of the quiz attempt', VALUE_OPTIONAL),
            'report' => new external_value(PARAM_RAW, 'HTML DOM of the generated quiz attempt report', VALUE_OPTIONAL),
            'status' => new external_value(PARAM_TEXT, 'Status of the executed wsfunction', VALUE_REQUIRED)
        ]);
    }

    /**
     * Generate an quiz attempt report as HTML DOM
     *
     * @param int $courseid_raw ID of the course
     * @param int $cmid_raw ID of the course module
     * @param int $quizid_raw ID of the quiz
     * @param int $attemptid_raw ID of the quiz attempt
     * @param array $sections_raw Sections to include in the report
     *
     * @return array According to execute_returns()
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public static function execute(int $courseid_raw, int $cmid_raw, int $quizid_raw, int $attemptid_raw, $sections_raw): array {
        global $DB;

        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid_raw,
            'cmid' => $cmid_raw,
            'quizid' => $quizid_raw,
            'attemptid' => $attemptid_raw,
            'sections' => $sections_raw
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
        if (!$report->has_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED'
            ];
        }
        if (!$report->attempt_exists($params['attemptid'])) {
            throw new \invalid_parameter_exception("No attempt with given attemptid found");
        }

        return [
            'courseid' => $params['courseid'],
            'cmid' => $params['cmid'],
            'quizid' => $params['quizid'],
            'attemptid' => $params['attemptid'],
            'report' => $report->generate_full_page($params['attemptid'], $params['sections']),
            'status' => 'OK'
        ];
    }

}