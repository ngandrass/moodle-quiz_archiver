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
 * This file defines the get_attempts_metadata webservice function
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


// TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
require_once($CFG->dirroot.'/mod/quiz/report/archiver/patch_401_class_renames.php'); // @codeCoverageIgnore

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use quiz_archiver\Report;
use Random\RandomError;

/**
 * API endpoint to access quiz attempt metadata
 */
class get_attempts_metadata extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(
                PARAM_INT,
                'ID of course',
                VALUE_REQUIRED
            ),
            'cmid' => new external_value(
                PARAM_INT,
                'ID of the course module',
                VALUE_REQUIRED
            ),
            'quizid' => new external_value(
                PARAM_INT,
                'ID of the quiz',
                VALUE_REQUIRED
            ),
            'attemptids' => new external_multiple_structure(
                new external_value(
                    PARAM_INT,
                    'ID of the quiz attempt',
                    VALUE_REQUIRED
                ),
                'List of quiz attempt IDs to query',
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
            'status' => new external_value(
                PARAM_TEXT,
                'Status of the executed wsfunction',
                VALUE_REQUIRED
            ),
            'courseid' => new external_value(
                PARAM_INT,
                'ID of course',
                VALUE_OPTIONAL
            ),
            'cmid' => new external_value(
                PARAM_INT,
                'ID of the course module',
                VALUE_OPTIONAL
            ),
            'quizid' => new external_value(
                PARAM_INT,
                'ID of the quiz',
                VALUE_OPTIONAL
            ),
            'attempts' => new external_multiple_structure(
                new external_single_structure([
                    'attemptid' => new external_value(
                        PARAM_INT,
                        'ID of the quiz attempt',
                        VALUE_REQUIRED
                    ),
                    'userid' => new external_value(
                        PARAM_INT,
                        'ID of the user for this quit attempt',
                        VALUE_REQUIRED
                    ),
                    'username' => new external_value(
                        PARAM_TEXT,
                        'Username for this quiz attempt',
                        VALUE_REQUIRED
                    ),
                    'firstname' => new external_value(
                        PARAM_TEXT,
                        'First name for this quiz attempt',
                        VALUE_REQUIRED
                    ),
                    'lastname' => new external_value(
                        PARAM_TEXT,
                        'Last name for this quiz attempt',
                        VALUE_REQUIRED
                    ),
                    'idnumber' => new external_value(
                        PARAM_TEXT,
                        'ID number of the user for this quiz attempt',
                        VALUE_REQUIRED
                    ),
                    'timestart' => new external_value(
                        PARAM_INT,
                        'Timestamp of when the quiz attempt started',
                        VALUE_REQUIRED
                    ),
                    'timefinish' => new external_value(
                        PARAM_INT,
                        'Timestamp of when the quiz attempt finished',
                        VALUE_REQUIRED
                    ),
                    'attempt' => new external_value(
                        PARAM_INT,
                        'Sequential attempt number',
                        VALUE_REQUIRED
                    ),
                    'state' => new external_value(
                        PARAM_TEXT,
                        'State of the quiz attempt',
                        VALUE_REQUIRED
                    ),
                ]),
                'Attempt metadata for each attempt ID',
                VALUE_OPTIONAL
            ),
        ]);
    }

    /**
     * Generate an quiz attempt report as HTML DOM
     *
     * @param int $courseidraw ID of the course
     * @param int $cmidraw ID of the course module
     * @param int $quizidraw ID of the quiz
     * @param array $attemptidsraw IDs of the quiz attempts
     *
     * @return array According to execute_returns()
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public static function execute(int $courseidraw, int $cmidraw, int $quizidraw, array $attemptidsraw): array {
        global $DB;

        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseidraw,
            'cmid' => $cmidraw,
            'quizid' => $quizidraw,
            'attemptids' => $attemptidsraw,
        ]);

        // Check capabilities.
        try {
            $context = \context_module::instance($params['cmid']);
        } catch (\dml_exception $e) {
            throw new \invalid_parameter_exception("No module context with given cmid found");
        }
        require_capability('mod/quiz_archiver:use_webservice', $context);

        // Acquire required data objects.
        if (!$course = $DB->get_record('course', ['id' => $params['courseid']])) {
            throw new \invalid_parameter_exception("No course with given courseid found");
        }
        if (!$cm = get_coursemodule_from_id("quiz", $params['cmid'])) {
            // @codeCoverageIgnoreStart
            // This should be covered by the context query above but stays as a safeguard nonetheless.
            throw new \invalid_parameter_exception("No course module with given cmid found");
            // @codeCoverageIgnoreEnd
        }
        if (!$quiz = $DB->get_record('quiz', ['id' => $params['quizid']])) {
            throw new \invalid_parameter_exception("No quiz with given quizid found");
        }

        // Extract attempt metadata.
        $report = new Report($course, $cm, $quiz);
        if (!$report->has_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED',
            ];
        }
        $attemptmetadata = $report->get_attempts_metadata($params['attemptids']);

        return [
            'courseid' => $params['courseid'],
            'cmid' => $params['cmid'],
            'quizid' => $params['quizid'],
            'attempts' => $attemptmetadata,
            'status' => 'OK',
        ];
    }

}
