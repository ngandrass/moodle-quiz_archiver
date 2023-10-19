<?php
namespace quiz_archiver\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use quiz_archiver\ArchiveJob;
use quiz_archiver\FileManager;

defined('MOODLE_INTERNAL') || die();

class process_uploaded_artifact extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_TEXT, 'UUID of the job this artifact is associated with', VALUE_REQUIRED),
            'artifact_component' => new external_value(PARAM_TEXT, 'File API component', VALUE_REQUIRED),
            'artifact_contextid' => new external_value(PARAM_INT, 'File API contextid', VALUE_REQUIRED),
            'artifact_userid' => new external_value(PARAM_INT, 'File API userid', VALUE_REQUIRED),
            'artifact_filearea' => new external_value(PARAM_TEXT, 'File API filearea', VALUE_REQUIRED),
            'artifact_filename' => new external_value(PARAM_TEXT, 'File API filename', VALUE_REQUIRED),
            'artifact_filepath' => new external_value(PARAM_TEXT, 'File API filepath', VALUE_REQUIRED),
            'artifact_itemid' => new external_value(PARAM_INT, 'File API itemid', VALUE_REQUIRED),
            'artifact_sha256sum' => new external_value(PARAM_TEXT, 'SHA256 checksum of the file', VALUE_REQUIRED)
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the executed wsfunction')
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobid_raw
     * @param string $artifact_component_raw
     * @param int $artifact_contextid_raw
     * @param int $artifact_userid_raw
     * @param string $artifact_filearea_raw
     * @param string $artifact_filename_raw
     * @param string $artifact_filepath_raw
     * @param int $artifact_itemid_raw
     * @param string $artifact_sha256sum_raw
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(
        string $jobid_raw,
        string $artifact_component_raw,
        int $artifact_contextid_raw,
        int $artifact_userid_raw,
        string $artifact_filearea_raw,
        string $artifact_filename_raw,
        string $artifact_filepath_raw,
        int $artifact_itemid_raw,
        string $artifact_sha256sum_raw
    ): array {
        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid_raw,
            'artifact_component' => $artifact_component_raw,
            'artifact_contextid' => $artifact_contextid_raw,
            'artifact_userid' => $artifact_userid_raw,
            'artifact_filearea' => $artifact_filearea_raw,
            'artifact_filename' => $artifact_filename_raw,
            'artifact_filepath' => $artifact_filepath_raw,
            'artifact_itemid' => $artifact_itemid_raw,
            'artifact_sha256sum' => $artifact_sha256sum_raw
        ]);

        // Validate that the jobid exists and no artifact was uploaded previously
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);
            if ($job->is_complete()) {
                return [
                    'status' => 'E_NO_ARTIFACT_UPLOAD_EXPECTED'
                ];
            }
        } catch (\dml_exception $e) {
            return [
                'status' => 'E_JOB_NOT_FOUND'
            ];
        }

        // Check access rights
        if (!$job->has_write_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return [
                'status' => 'E_ACCESS_DENIED'
            ];
        }

        // Check capabilities
        $context = \context_module::instance($job->get_cm_id());
        require_capability('mod/quiz_archiver:use_webservice', $context);

        // Validate uploaded file
        // Note: We use SHA256 instead of Moodle sha1, since SHA1 is prone to
        // hash collisions!
        $draftfile = FileManager::get_draft_file(
            $params['artifact_contextid'],
            $params['artifact_itemid'],
            $params['artifact_filepath'],
            $params['artifact_filename']
        );
        if (!$draftfile) {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            return [
                'status' => 'E_UPLOADED_ARTIFACT_NOT_FOUND'
            ];
        }

        if ($params['artifact_sha256sum'] != FileManager::hash_file($draftfile)) {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            $draftfile->delete();
            return [
                'status' => 'E_ARTIFACT_CHECKSUM_INVALID'
            ];
        }

        // Store uploaded file
        $fm = new FileManager($job->get_course_id(), $job->get_cm_id(), $job->get_quiz_id());
        try {
            $artifact = $fm->store_uploaded_artifact($draftfile);
            $job->link_artifact($artifact->get_id(), $params['artifact_sha256sum']);
        } catch (\Exception $e) {
            $job->set_status(ArchiveJob::STATUS_FAILED);
            return [
                'status' => 'E_STORE_ARTIFACT_FAILED'
            ];
        }

        // Timestamp artifact file using TSP
        if ($job->TSPManager()->wants_tsp_timestamp()) {
            try {
                $job->TSPManager()->timestamp();
            } catch (\Exception $e) {
                // TODO: Fail silently for now ...
                // $job->set_status(ArchiveJob::STATUS_FAILED);
                // return [
                //     'status' => 'E_TSP_TIMESTAMP_FAILED'
                // ];
            }
        }

        // Report success
        $job->set_status(ArchiveJob::STATUS_FINISHED);
        return [
            'status' => 'OK'
        ];
    }

}