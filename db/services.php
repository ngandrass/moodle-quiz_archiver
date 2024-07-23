<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Web service function declarations for the quiz_archiver plugin.
 *
 * @package     quiz_archiver
 * @copyright   2024 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


$functions = [
    'quiz_archiver_generate_attempt_report' => [
        'classname' => 'quiz_archiver\external\generate_attempt_report',
        'description' => 'Generates a full HTML DOM containing all report data on the specified attempt',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
        'capabilities' => 'mod/quiz_archiver:use_webservice',
    ],
    'quiz_archiver_get_attempts_metadata' => [
        'classname' => 'quiz_archiver\external\get_attempts_metadata',
        'description' => 'Returns metadata about attempts of a quiz',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
        'capabilities' => 'mod/quiz_archiver:use_webservice',
    ],
    'quiz_archiver_update_job_status' => [
        'classname' => 'quiz_archiver\external\update_job_status',
        'description' => 'Called by the quiz archiver worker to update the status of a job',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
        'capabilities' => 'mod/quiz_archiver:use_webservice',
    ],
    'quiz_archiver_process_uploaded_artifact' => [
        'classname' => 'quiz_archiver\external\process_uploaded_artifact',
        'description' => 'Called by the quiz archiver worker to process a previously uploaded artifact',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
        'capabilities' => 'mod/quiz_archiver:use_webservice',
    ],
    'quiz_archiver_get_backup_status' => [
        'classname' => 'quiz_archiver\external\get_backup_status',
        'description' => 'Called by the quiz archiver worker to retrieve Moodle backup information',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
        'capabilities' => 'mod/quiz_archiver:use_webservice',
    ],
];
