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
 * This file defines the process_uploaded_artifact webservice function
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
use quiz_archiver\FileManager;

/**
 * API endpoint to process an artifact that was uploaded by the quiz archiver worker service
 */
class process_uploaded_artifact extends external_api {
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
            'artifact_component' => new external_value(
                PARAM_TEXT,
                'File API component',
                VALUE_REQUIRED
            ),
            'artifact_contextid' => new external_value(
                PARAM_INT,
                'File API contextid',
                VALUE_REQUIRED
            ),
            'artifact_userid' => new external_value(
                PARAM_INT,
                'File API userid',
                VALUE_REQUIRED
            ),
            'artifact_filearea' => new external_value(
                PARAM_TEXT,
                'File API filearea',
                VALUE_REQUIRED
            ),
            'artifact_filename' => new external_value(
                PARAM_TEXT,
                'File API filename',
                VALUE_REQUIRED
            ),
            'artifact_filepath' => new external_value(
                PARAM_TEXT,
                'File API filepath',
                VALUE_REQUIRED
            ),
            'artifact_itemid' => new external_value(
                PARAM_INT,
                'File API itemid',
                VALUE_REQUIRED
            ),
            'artifact_sha256sum' => new external_value(
                PARAM_TEXT,
                'SHA256 checksum of the file',
                VALUE_REQUIRED
            ),
            'artifact_count' => new external_value(
                PARAM_INT,
                'Number of individually uploaded files to process',
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
                'Status of the executed wsfunction'
            ),
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobidraw
     * @param string $artifactcomponentraw
     * @param int $artifactcontextidraw
     * @param int $artifactuseridraw
     * @param string $artifactfilearearaw
     * @param string $artifactfilenameraw
     * @param string $artifactfilepathraw
     * @param int $artifactitemidraw
     * @param string $artifactsha256sumraw
     * @param int $artifactcountraw
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(
        string $jobidraw,
        string $artifactcomponentraw,
        int $artifactcontextidraw,
        int $artifactuseridraw,
        string $artifactfilearearaw,
        string $artifactfilenameraw,
        string $artifactfilepathraw,
        int $artifactitemidraw,
        string $artifactsha256sumraw,
        int $artifactcountraw,
    ): array {
        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobidraw,
            'artifact_component' => $artifactcomponentraw,
            'artifact_contextid' => $artifactcontextidraw,
            'artifact_userid' => $artifactuseridraw,
            'artifact_filearea' => $artifactfilearearaw,
            'artifact_filename' => $artifactfilenameraw,
            'artifact_filepath' => $artifactfilepathraw,
            'artifact_itemid' => $artifactitemidraw,
            'artifact_sha256sum' => $artifactsha256sumraw,
            'artifact_count' => $artifactcountraw,
        ]);

        // Validate that the jobid exists and no artifact was uploaded previously.
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);
            if ($job->is_complete()) {
                return [
                    'status' => 'E_NO_ARTIFACT_UPLOAD_EXPECTED',
                ];
            }
        } catch (\dml_exception $e) {
            return [
                'status' => 'E_JOB_NOT_FOUND',
            ];
        }

        // Check access rights.
        if (!$job->has_write_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED',
            ];
        }

        // Check capabilities.
        $context = \context_module::instance($job->get_cmid());
        require_capability('quiz/archiver:use_webservice', $context);

        // Get or reconstruct uploaded file/-s.
        $draftfile = null;
        if ($params['artifact_count'] == 1) {
            // Find previously uploaded file.
            $draftfile = FileManager::get_draft_file(
                $params['artifact_contextid'],
                $params['artifact_itemid'],
                $params['artifact_filepath'],
                $params['artifact_filename'],
            );
            if (!$draftfile) {
                $job->set_status(ArchiveJob::STATUS_FAILED);
                return [
                    'status' => 'E_UPLOADED_ARTIFACT_NOT_FOUND',
                ];
            }
        } else if ($params['artifact_count'] > 1) {
            // Reasabmle orgininal file.
            $draftfile = FileManager::reasamble_chunked_file(
                $params['artifact_contextid'],
                $params['artifact_itemid'],
                $params['artifact_filepath'],
                $params['artifact_filename'],
                $params['artifact_count']
            );
            if (!$draftfile) {
                $job->set_status(ArchiveJob::STATUS_FAILED);
                return [
                    'status' => 'E_CHUNK_REASSEMBLY_FAILED',
                ];
            }
        } else {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            return [
                'status' => 'E_INVALID_ARTIFACT_COUNT',
            ];
        }

        // Validate uploaded file.
        // Note: We use SHA256 instead of Moodle sha1, since SHA1 is prone to.
        // hash collisions!
        if ($params['artifact_sha256sum'] != FileManager::hash_file($draftfile)) {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            $draftfile->delete();
            return [
                'status' => 'E_ARTIFACT_CHECKSUM_INVALID',
            ];
        }

        // The following code is tested covered by more specific tests.
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreStart

        // Store uploaded file.
        $fm = new FileManager($job->get_courseid(), $job->get_cmid(), $job->get_quizid());
        try {
            $artifact = $fm->store_uploaded_artifact($draftfile, $job->get_id());
            $job->link_artifact($artifact->get_id(), $params['artifact_sha256sum']);
        } catch (\Exception $e) {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            return [
                'status' => 'E_STORE_ARTIFACT_FAILED',
            ];
        }

        // Timestamp artifact file using TSP.
        if ($job->tspmanager()->wants_tsp_timestamp()) {
            try {
                $job->tspmanager()->timestamp();
            // @codingStandardsIgnoreStart
            } catch (\Exception $e) {
                // TODO: Fail silently for now ...
                /*
                $job->set_status(ArchiveJob::STATUS_FAILED);
                return [
                    'status' => 'E_TSP_TIMESTAMP_FAILED'
                ];
                */
            }
            // @codingStandardsIgnoreEnd
        }

        // Report success.
        $job->set_status(ArchiveJob::STATUS_FINISHED);
        return [
            'status' => 'OK',
        ];

        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreEnd
    }
}
