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
 * This file defines the generate_attempt_report webservice function
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
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
use quiz_archiver\ArchiveJob;
use quiz_archiver\Report;

/**
 * API endpoint to generate a quiz attempt report
 */
class generate_attempt_report extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(
                PARAM_TEXT,
                'UUID of the job this artifact is associated with',
                VALUE_REQUIRED
            ),
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
            'attemptid' => new external_value(
                PARAM_INT,
                'ID of the quiz attempt',
                VALUE_REQUIRED
            ),
            'foldernamepattern' => new external_value(
                PARAM_TEXT,
                'Folder name pattern to use for generating the attempt folder'
            ),
            'filenamepattern' => new external_value(
                PARAM_TEXT,
                'Filename pattern to use for the generated attempt files',
                VALUE_REQUIRED
            ),
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
            'attachments' => new external_value(
                PARAM_BOOL,
                'Whether to check for attempts and include metadata if present',
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
            'attemptid' => new external_value(
                PARAM_INT,
                'ID of the quiz attempt',
                VALUE_OPTIONAL
            ),
            'foldername' => new external_value(
                PARAM_TEXT,
                'Desired name of the folder to store this quiz attempt report in',
                VALUE_OPTIONAL
            ),
            'filename' => new external_value(
                PARAM_TEXT,
                'Desired filename of this quiz attempt report',
                VALUE_OPTIONAL
            ),
            'report' => new external_value(
                PARAM_RAW,
                'HTML DOM of the generated quiz attempt report',
                VALUE_OPTIONAL
            ),
            'attachments' => new external_multiple_structure(
                new external_single_structure([
                    'slot' => new external_value(
                        PARAM_INT,
                        'Number of the quiz slot this file is attached to',
                        VALUE_REQUIRED
                    ),
                    'filename' => new external_value(
                        PARAM_TEXT,
                        'Filename of the attachment',
                        VALUE_REQUIRED
                    ),
                    'filesize' => new external_value(
                        PARAM_INT,
                        'Filesize of the attachment',
                        VALUE_REQUIRED
                    ),
                    'mimetype' => new external_value(
                        PARAM_TEXT,
                        'Mimetype of the attachment',
                        VALUE_REQUIRED
                    ),
                    'contenthash' => new external_value(
                        PARAM_TEXT,
                        'Contenthash (SHA-1) of the attachment',
                        VALUE_REQUIRED
                    ),
                    'downloadurl' => new external_value(
                        PARAM_TEXT,
                        'URL to download the attachment',
                        VALUE_REQUIRED
                    ),
                ]),
                'Files attached to the quiz attempt',
                VALUE_OPTIONAL
            ),
            'status' => new external_value(
                PARAM_TEXT,
                'Status of the executed wsfunction',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Generate an quiz attempt report as HTML DOM
     *
     * @param string $jobidraw UUID of the job
     * @param int $courseidraw ID of the course
     * @param int $cmidraw ID of the course module
     * @param int $quizidraw ID of the quiz
     * @param int $attemptidraw ID of the quiz attempt
     * @param string $foldernamepatternraw Folder name pattern to use for report name generation
     * @param string $filenamepatternraw Filename pattern to use for report name generation
     * @param array $sectionsraw Sections to include in the report
     * @param bool $attachmentsraw Whether to check for attempts and include metadata if present
     *
     * @return array According to execute_returns()
     *
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public static function execute(
        string $jobidraw,
        int    $courseidraw,
        int    $cmidraw,
        int    $quizidraw,
        int    $attemptidraw,
        string $foldernamepatternraw,
        string $filenamepatternraw,
        array  $sectionsraw,
        bool   $attachmentsraw
    ): array {
        global $DB, $PAGE;

        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobidraw,
            'courseid' => $courseidraw,
            'cmid' => $cmidraw,
            'quizid' => $quizidraw,
            'attemptid' => $attemptidraw,
            'foldernamepattern' => $foldernamepatternraw,
            'filenamepattern' => $filenamepatternraw,
            'sections' => $sectionsraw,
            'attachments' => $attachmentsraw,
        ]);

        // Validate that the jobid exists.
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);
        } catch (\dml_exception $e) {
            return ['status' => 'E_JOB_NOT_FOUND'];
        }

        // Check access rights.
        if (!$job->has_read_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return ['status' => 'E_ACCESS_DENIED'];
        }

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

        // Validate folder and filename pattern.
        if (!ArchiveJob::is_valid_attempt_foldername_pattern($params['foldernamepattern'])) {
            throw new \invalid_parameter_exception("Invalid foldername pattern");
        }
        if (!ArchiveJob::is_valid_attempt_filename_pattern($params['filenamepattern'])) {
            throw new \invalid_parameter_exception("Report filename pattern is invalid");
        }

        // Prepare response.
        $res = [
            'courseid' => $params['courseid'],
            'cmid' => $params['cmid'],
            'quizid' => $params['quizid'],
            'attemptid' => $params['attemptid'],
        ];

        // Forcefully set URL in $PAGE to the webservice handler to prevent further warnings.
        $PAGE->set_url(new \moodle_url('/webservice/rest/server.php', ['wsfunction' => 'quiz_archiver_generate_attempt_report']));

        // The following code is tested covered by more specific tests.
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreStart

        // Generate report.
        $report = new Report($course, $cm, $quiz);
        if (!$report->has_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED',
            ];
        }
        if (!$report->attempt_exists($params['attemptid'])) {
            throw new \invalid_parameter_exception("No attempt with given attemptid found");
        }

        $res['report'] = $report->generate_full_page($params['attemptid'], $params['sections']);

        // Check for attachments.
        if ($params['attachments']) {
            $res['attachments'] = $report->get_attempt_attachments_metadata($params['attemptid']);

            // Update attachment count in attempt metadata table.
            $numattachments = count($res['attachments']);
            $DB->set_field(ArchiveJob::ATTEMPTS_TABLE_NAME, 'numattachments', $numattachments, [
                'jobid' => $job->get_id(),
                'attemptid' => $params['attemptid'],
            ]);
        } else {
            $res['attachments'] = [];
        }

        // Generate folder- and filename.
        $res['foldername'] = ArchiveJob::generate_attempt_foldername(
            $course,
            $cm,
            $quiz,
            $params['attemptid'],
            $params['foldernamepattern']
        );
        $res['filename'] = ArchiveJob::generate_attempt_filename(
            $course,
            $cm,
            $quiz,
            $params['attemptid'],
            $params['filenamepattern']
        );

        // Return response.
        $res['status'] = 'OK';

        return $res;
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreEnd
    }

}
