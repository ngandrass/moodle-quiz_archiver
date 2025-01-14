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
 * This file defines the get_backup_status webservice function
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
use core_external\external_single_structure;
use core_external\external_value;
use quiz_archiver\ArchiveJob;
use quiz_archiver\BackupManager;

/**
 * API endpoint to get the status of a Moodle backup
 */
class get_backup_status extends external_api {

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
            'backupid' => new external_value(
                PARAM_TEXT,
                'ID of the backup controller',
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
                'Status of the requested backup',
                VALUE_REQUIRED
            ),
        ]);
    }

    /**
     * Execute the webservice function
     *
     * @param string $jobidraw
     * @param string $backupidraw
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(
        string $jobidraw,
        string $backupidraw
    ): array {
        // Validate request.
        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobidraw,
            'backupid' => $backupidraw,
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
        $context = \context_module::instance($job->get_cmid());
        require_capability('mod/quiz_archiver:use_webservice', $context);

        // The following code is tested covered by more specific tests.
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreStart

        // Get backup.
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

        // Report success.
        return ['status' => 'SUCCESS'];

        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreEnd
    }

}
