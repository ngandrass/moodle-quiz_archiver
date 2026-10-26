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
 * This file defines the get_job_metadata webservice function. It provides any
 * information of a job that is required to update its different corresponding
 * UI elements.
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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use quiz_archiver\ArchiveJob;

/**
 * API endpoint to get the status of a quiz archiver job for client-side polling
 */
class get_job_metadata extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(
                PARAM_ALPHANUMEXT,
                'UUID of the job',
                VALUE_REQUIRED,
            ),
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'status' => new external_value(
                    PARAM_TEXT,
                    'Status of the executed webservice call',
                    VALUE_REQUIRED,
                ),
                'job' => new external_single_structure(
                    [
                        'uuid' => new external_value(
                            PARAM_TEXT,
                            'UUID of the job',
                            VALUE_REQUIRED,
                        ),
                        'complete' => new external_value(
                            PARAM_BOOL,
                            'Whether the job has reached a final state',
                            VALUE_REQUIRED,
                        ),
                        'status' => new external_value(
                            PARAM_TEXT,
                            'Raw job status enum string',
                            VALUE_REQUIRED,
                        ),
                        'status_display_args' => new external_single_structure(
                            [
                                'color' => new external_value(
                                    PARAM_TEXT,
                                    'CSS color variant for the job status badge',
                                    VALUE_REQUIRED,
                                ),
                                'text' => new external_value(
                                    PARAM_TEXT,
                                    'Localized human-readable status label',
                                    VALUE_REQUIRED,
                                ),
                                'help' => new external_value(
                                    PARAM_TEXT,
                                    'Localized help text for the status badge',
                                    VALUE_REQUIRED,
                                ),
                                'statusextras' => new external_single_structure(
                                    [
                                        'progress' => new external_value(
                                            PARAM_INT,
                                            'Progress of the job in percent',
                                            VALUE_OPTIONAL,
                                        ),
                                    ],
                                    'Additional status information for the job',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'Display arguments for the current job status',
                            VALUE_REQUIRED,
                        ),
                        'timecreated' => new external_value(
                            PARAM_INT,
                            'Timestamp of job creation',
                            VALUE_REQUIRED,
                        ),
                        'timemodified' => new external_value(
                            PARAM_INT,
                            'Timestamp of the job\'s last update',
                            VALUE_REQUIRED,
                        ),
                        'user' => new external_single_structure(
                            [
                                'id' => new external_value(
                                    PARAM_INT,
                                    'User ID',
                                    VALUE_REQUIRED,
                                ),
                                'firstname' => new external_value(
                                    PARAM_TEXT,
                                    'First name',
                                    VALUE_OPTIONAL,
                                ),
                                'lastname' => new external_value(
                                    PARAM_TEXT,
                                    'Last name',
                                    VALUE_OPTIONAL,
                                ),
                                'username' => new external_value(
                                    PARAM_TEXT,
                                    'Username',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'User who owns this job',
                            VALUE_REQUIRED,
                        ),
                        'course' => new external_single_structure(
                            [
                                'id' => new external_value(
                                    PARAM_INT,
                                    'Course ID',
                                    VALUE_REQUIRED,
                                ),
                                'name' => new external_value(
                                    PARAM_TEXT,
                                    'Course name',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'Course this job belongs to',
                            VALUE_REQUIRED,
                        ),
                        'quiz' => new external_single_structure(
                            [
                                'id' => new external_value(
                                    PARAM_INT,
                                    'Quiz ID',
                                    VALUE_REQUIRED,
                                ),
                                'cmid' => new external_value(
                                    PARAM_INT,
                                    'Course module ID',
                                    VALUE_REQUIRED,
                                ),
                                'name' => new external_value(
                                    PARAM_TEXT,
                                    'Quiz name',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'Quiz this job belongs to',
                            VALUE_REQUIRED,
                        ),
                        'settings' => new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'title' => new external_value(
                                        PARAM_TEXT,
                                        'Setting label',
                                        VALUE_REQUIRED,
                                    ),
                                    'value' => new external_value(
                                        PARAM_TEXT,
                                        'Setting value',
                                        VALUE_OPTIONAL,
                                    ),
                                    'color' => new external_value(
                                        PARAM_TEXT,
                                        'Badge color',
                                        VALUE_OPTIONAL,
                                    ),
                                ],
                                'A single archive setting',
                                VALUE_REQUIRED,
                            ),
                            'List of archive settings captured for this job',
                            VALUE_REQUIRED,
                        ),
                        'artifact' => new external_single_structure(
                            [
                                'name' => new external_value(
                                    PARAM_TEXT,
                                    'Artifact file name',
                                    VALUE_REQUIRED,
                                ),
                                'size' => new external_value(
                                    PARAM_INT,
                                    'Artifact file size in bytes',
                                    VALUE_OPTIONAL,
                                ),
                                'size_human' => new external_value(
                                    PARAM_TEXT,
                                    'Artifact file size as human readable string',
                                    VALUE_REQUIRED,
                                ),
                                'checksum' => new external_value(
                                    PARAM_TEXT,
                                    'SHA256 checksum of the artifact',
                                    VALUE_OPTIONAL,
                                ),
                            ],
                            'Artifact file information, present while an artifact is available',
                            VALUE_OPTIONAL,
                        ),
                        'signature' => new external_single_structure(
                            [
                                'enabled' => new external_value(
                                    PARAM_BOOL,
                                    'Whether TSP signing is enabled globally',
                                    VALUE_REQUIRED,
                                ),
                                'tsp' => new external_single_structure(
                                    [
                                        'timecreated' => new external_value(
                                            PARAM_INT,
                                            'Timestamp the artifact was signed',
                                            VALUE_REQUIRED,
                                        ),
                                        'server' => new external_value(
                                            PARAM_TEXT,
                                            'TSP server that signed the artifact',
                                            VALUE_OPTIONAL,
                                        ),
                                    ],
                                    'TSP signature information, present when the artifact is signed',
                                    VALUE_OPTIONAL,
                                ),
                            ],
                            'Signature information for the artifact',
                            VALUE_REQUIRED,
                        ),
                        'autodelete' => new external_single_structure(
                            [
                                'enabled' => new external_value(
                                    PARAM_BOOL,
                                    'Whether automatic artifact deletion is enabled',
                                    VALUE_REQUIRED,
                                ),
                                'done' => new external_value(
                                    PARAM_BOOL,
                                    'Whether the artifact has already been auto-deleted',
                                    VALUE_REQUIRED,
                                ),
                                'str' => new external_value(
                                    PARAM_TEXT,
                                    'Human readable automatic-deletion status string',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'Automatic artifact deletion information',
                            VALUE_REQUIRED,
                        ),
                        'actionurls' => new external_single_structure(
                            [
                                'showcontent' => new external_value(
                                    PARAM_TEXT,
                                    'URL to view the archive contents',
                                    VALUE_OPTIONAL,
                                ),
                                'artifact_download' => new external_value(
                                    PARAM_TEXT,
                                    'URL to download the archive artifact',
                                    VALUE_OPTIONAL,
                                ),
                                'artifact_delete' => new external_value(
                                    PARAM_TEXT,
                                    'URL to delete the artifact',
                                    VALUE_REQUIRED,
                                ),
                                'artifact_sign' => new external_value(
                                    PARAM_TEXT,
                                    'URL to sign the artifact',
                                    VALUE_REQUIRED,
                                ),
                                'tsp_query_download' => new external_value(
                                    PARAM_TEXT,
                                    'URL to download the TSP query file',
                                    VALUE_OPTIONAL,
                                ),
                                'tsp_reply_download' => new external_value(
                                    PARAM_TEXT,
                                    'URL to download the TSP reply file',
                                    VALUE_OPTIONAL,
                                ),
                                'job_delete' => new external_value(
                                    PARAM_TEXT,
                                    'URL to the job deletion confirmation view',
                                    VALUE_REQUIRED,
                                ),
                                'course' => new external_value(
                                    PARAM_TEXT,
                                    'URL of the course',
                                    VALUE_REQUIRED,
                                ),
                                'quiz' => new external_value(
                                    PARAM_TEXT,
                                    'URL of the quiz',
                                    VALUE_REQUIRED,
                                ),
                                'user' => new external_value(
                                    PARAM_TEXT,
                                    'URL of the user profile',
                                    VALUE_REQUIRED,
                                ),
                            ],
                            'Action and navigation URLs for this job',
                            VALUE_REQUIRED,
                        ),
                    ],
                    'Complete data about the job, suitable for any client-side consumption',
                    VALUE_OPTIONAL,
                ),
            ],
            'Job data for client-side polling',
            VALUE_REQUIRED,
        );
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobidraw
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     * @throws \require_login_exception
     * @throws restricted_context_exception
     */
    public static function execute(
        string $jobidraw,
    ): array {
        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobidraw,
        ]);

        // Validate that the jobid exists.
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);
        } catch (\dml_missing_record_exception $e) {
            return ['status' => 'E_JOB_NOT_FOUND'];
        }

        // Check capabilities.
        $context = \context_module::instance($job->get_cmid());
        self::validate_context($context);
        require_capability('quiz/archiver:view', $context);

        // Housekeeping.
        $config = get_config('quiz_archiver');
        $job->timeout_if_overdue($config->job_timeout_min);

        // Build response.
        $metadata = $job->get_metadata();
        $jobdata = [
            'uuid' => $job->get_jobid(),
            'complete' => $job->is_complete(),
            'status' => $job->get_status(),
            'status_display_args' => $metadata['status_display_args'],
            'timecreated' => $job->get_timecreated(),
            'timemodified' => $job->get_timemodified(),
            'user' => $metadata['user'],
            'course' => $metadata['course'],
            'quiz' => $metadata['quiz'],
            'settings' => $metadata['settings'],
            'signature' => [
                'enabled' => (bool) $config->tsp_enable,
            ],
            'autodelete' => [
                'enabled' => (bool) $metadata['autodelete'],
                'done' => (bool) $metadata['autodelete_done'],
                'str' => $metadata['autodelete_str'],
            ],
            'actionurls' => [
                'showcontent' => $job->get_showcontents_url(),
                'artifact_download' => $job->get_artifact_download_url(),
                'artifact_delete' => $job->get_delete_artifact_url(),
                'artifact_sign' => $job->get_sign_artifact_url(),
                'job_delete' => $job->get_delete_job_url(),
                'course' => (new \moodle_url('/course/view.php', ['id' => $job->get_courseid()]))->out(false),
                'quiz' => (new \moodle_url('/mod/quiz/view.php', ['id' => $job->get_cmid()]))->out(false),
                'user' => (new \moodle_url('/user/profile.php', ['id' => $job->get_userid()]))->out(false),
            ],
        ];

        // Optional data, only included while it is present.
        if ($metadata['artifactfile'] !== null) {
            // NOTE: The download URL is an action URL
            // NOTE: (actionurls.artifact_download), so it is not duplicated.
            $jobdata['artifact'] = [
                'name' => $metadata['artifactfile']['name'],
                'size' => $metadata['artifactfile']['size'],
                'size_human' => $metadata['artifactfile']['size_human'],
                'checksum' => $metadata['artifactfile']['checksum'],
            ];
        }
        if ($metadata['tsp'] !== null) {
            $jobdata['signature']['tsp'] = [
                'timecreated' => $metadata['tsp']['timecreated'],
                'server' => $metadata['tsp']['server'],
            ];

            // NOTE: The query/reply file download URLs are action URLs,
            // NOTE: therefore they live in actionurls.
            $jobdata['actionurls']['tsp_query_download'] = $metadata['tsp']['queryfiledownloadurl'];
            $jobdata['actionurls']['tsp_reply_download'] = $metadata['tsp']['replyfiledownloadurl'];
        }

        return [
            'status' => 'OK',
            'job' => $jobdata,
        ];
    }
}
