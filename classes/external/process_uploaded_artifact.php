<?php
namespace quiz_archiver\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use quiz_archiver\FileManager;

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
            'artifact_itemid' => new external_value(PARAM_INT, 'File API itemid', VALUE_REQUIRED)
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'jobid' => new external_value(PARAM_TEXT, 'UUID of the job this artifact was associated with'),
            'status' => new external_value(PARAM_TEXT, 'Status of the executed wsfunction')
        ]);
    }

    /**

     */
    public static function execute(
        string $jobid_raw,
        string $artifact_component_raw,
        int $artifact_contextid_raw,
        int $artifact_userid_raw,
        string $artifact_filearea_raw,
        string $artifact_filename_raw,
        string $artifact_filepath_raw,
        int $artifact_itemid_raw
    ): array {
        global $DB;

        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid_raw,
            'artifact_component' => $artifact_component_raw,
            'artifact_contextid' => $artifact_contextid_raw,
            'artifact_userid' => $artifact_userid_raw,
            'artifact_filearea' => $artifact_filearea_raw,
            'artifact_filename' => $artifact_filename_raw,
            'artifact_filepath' => $artifact_filepath_raw,
            'artifact_itemid' => $artifact_itemid_raw
        ]);

        // Store uploaded file
        // TODO

        return [
            'jobid' => $params['jobid'],
            'status' => 'OK'
        ];
    }

}