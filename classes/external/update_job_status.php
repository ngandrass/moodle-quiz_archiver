<?php
namespace quiz_archiver\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use quiz_archiver\ArchiveJob;

class update_job_status extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_TEXT, 'UUID of the job this artifact is associated with', VALUE_REQUIRED),
            'status' => new external_value(PARAM_TEXT, 'New status to set for job with UUID of jobid', VALUE_REQUIRED),
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
     * @param string $status_raw
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function execute(
        string $jobid_raw,
        string $status_raw
    ): array {
        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'status' => $status_raw,
        ]);

        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);

            if ($job->is_complete()) {
                return [
                    'status' => 'E_JOB_ALREADY_COMPLETED'
                ];
            }

            if (!$job->has_write_access(optional_param('wstoken', null, PARAM_TEXT))) {
                return [
                    'status' => 'E_ACCESS_DENIED'
                ];
            }

            $job->set_status($params['status']);
        } catch (\dml_exception $e) {
            return [
                'status' => 'E_UPDATE_FAILED'
            ];
        }

        // Report success
        return [
            'status' => 'OK'
        ];
    }

}