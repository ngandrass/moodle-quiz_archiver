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
 * This file defines the get_job_ajax webservice function
 *
 * @package   quiz_archiver
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quiz_archiver\ArchiveJob;

/**
 * API endpoint to get the status of a quiz archiver job for client-side polling
 */
class get_job_ajax extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(
                PARAM_TEXT,
                'UUID of the job',
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
                'Status of the executed webservice call',
                VALUE_REQUIRED
            ),
            'job' => new external_single_structure(
                [
                    'complete' => new external_value(
                        PARAM_BOOL,
                        'Whether the job has reached a final state',
                        VALUE_REQUIRED
                    ),
                    'statusextras' => new external_single_structure(
                        [
                            'progress' => new external_value(
                                PARAM_INT,
                                'Progress of the job in percent',
                                VALUE_OPTIONAL
                            ),
                        ],
                        'Additional status information for the job',
                        VALUE_OPTIONAL
                    ),
                    'timemodified' => new external_value(
                        PARAM_INT,
                        'Timestamp of the jobs last update',
                        VALUE_REQUIRED
                    ),
                ],
                'Job data',
                VALUE_OPTIONAL
            ),
            'display_args' => new external_single_structure([
                'status' => new external_single_structure([
                    'color' => new external_value(
                        PARAM_TEXT,
                        'CSS color variant for the job status badge',
                        VALUE_OPTIONAL
                    ),
                    'text' => new external_value(
                        PARAM_TEXT,
                        'Localized human-readable status label',
                        VALUE_OPTIONAL
                    ),
                    'help' => new external_value(
                        PARAM_TEXT,
                        'Localized help text for the status badge',
                        VALUE_OPTIONAL
                    ),
                ], 'Display arguments for the current job status', VALUE_OPTIONAL),
                'filesize' => new external_value(
                    PARAM_TEXT,
                    'File size of artifact as formatted string',
                    VALUE_OPTIONAL
                ),
                'actionurls' => new external_single_structure([
                    'showcontent' => new external_value(
                        PARAM_TEXT,
                        'URL navigating to archive content view',
                        VALUE_OPTIONAL
                    ),
                    'artifact' => new external_value(
                        PARAM_TEXT,
                        'URL downloading archive artifact',
                        VALUE_OPTIONAL
                    ),
                    'delete' => new external_value(
                        PARAM_TEXT,
                        'URL navigating to job deletion confirmation view',
                        VALUE_OPTIONAL
                    ),
                ], 'URLs for action buttons', VALUE_REQUIRED),
            ], 'Display metadata for the current job status', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobidraw
     * @return array
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(
        string $jobidraw,
    ): array {
        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobidraw,
        ]);

        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);

            // Housekeeping.
            $job->timeout_if_overdue(get_config('quiz_archiver')->job_timeout_min);

            // Check capabilities.
            $context = \context_module::instance($job->get_cmid());
            require_capability('quiz/archiver:use_webservice', $context);

            // Build response.
            $statusextras = $job->get_statusextras();
            $jobdata = [
                'complete' => $job->is_complete(),
                'timemodified' => $job->get_timemodified(),
            ];
            if ($statusextras !== null) {
                $jobdata['statusextras'] = $statusextras;
            }

            return [
                'status' => 'OK',
                'job' => $jobdata,
                'display_args' => [
                    'status' => ArchiveJob::get_status_display_args($job->get_status(), $statusextras),
                    'filesize' => $job->has_artifact() ? display_size($job->get_artifact()->get_filesize()) : null,
                    'actionurls' => [
                        'showcontent' => $job->get_showcontents_url(),
                        'artifact' => $job->get_artifact_download_url(),
                        'delete' => $job->get_delete_job_url(),
                    ],
                ],
            ];
        } catch (\dml_exception $e) {
            return [
                'status' => 'E_JOB_NOT_FOUND',
            ];
        } catch (\JsonException $e) {
            return [
                'status' => 'E_INVALID_STATUSEXTRAS_JSON',
            ];
        }
    }
}
