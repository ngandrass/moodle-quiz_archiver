<?php
namespace quiz_archiver\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use quiz_archiver\ArchiveJob;
use quiz_archiver\BackupManager;

class get_backup_status extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_TEXT, 'UUID of the job this artifact is associated with', VALUE_REQUIRED),
            'backupid' => new external_value(PARAM_TEXT, 'ID of the backup controller', VALUE_REQUIRED),
        ]);
    }

    /**
     * Returns description of return parameters
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of the requested backup', VALUE_REQUIRED)
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobid_raw
     * @param string $backupid_raw
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function execute(
        string $jobid_raw,
        string $backupid_raw
    ): array {
        // Validate request
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid_raw,
            'backupid' => $backupid_raw
        ]);

        // Validate that the jobid exists
        try {
            $job = ArchiveJob::get_by_jobid($params['jobid']);
        } catch (\dml_exception $e) {
            return ['status' => 'E_JOB_NOT_FOUND'];
        }

        // Check access rights
        if (!$job->has_read_access(optional_param('wstoken', null, PARAM_TEXT))) {
            return ['status' => 'E_ACCESS_DENIED'];
        }

        // Get backup
        try {
            $bm = new BackupManager($params['backupid']);

            if (!$bm->is_associated_with_job($job)) {
                return ['status' => 'E_ACCESS_DENIED'];
            }

            if ($bm->is_failed()) {
                return ['status' => 'E_BACKUP_FAILED'];
            }

            if (!$bm->is_finished_successfully()) {
                return ['status' => 'E_BACKUP_PENDING'];
            }
        } catch (\dml_exception $e) {
            return ['status' => 'E_BACKUP_NOT_FOUND'];
        }

        // Report success
        return ['status' => 'SUCCESS'];
    }

}