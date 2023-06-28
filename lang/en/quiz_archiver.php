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
 * Plugin strings are defined here.
 *
 * @package     quiz_archiver
 * @category    string
 * @copyright   2023 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = $string['pluginname'];
$string['archiverreport'] = $string['pluginname'];

// Template: Overview
$string['users_with_attempts'] = 'Users with quiz attempts';
$string['archive_quiz'] = 'Archive Quiz';
$string['export_attempts'] = 'Export quiz attempts';
$string['export_course_backup'] = 'Export full course backup';

// Job
$string['jobid'] = 'Job ID';
$string['job_status_UNKNOWN'] = 'Unknown';
$string['job_status_UNINITIALIZED'] = 'Uninitialized';
$string['job_status_AWAITING_PROCESSING'] = 'Queued';
$string['job_status_RUNNING'] = 'Running';
$string['job_status_FINISHED'] = 'Finished';
$string['job_status_FAILED'] = 'Failed';

// Settings
$string['setting_internal_wwwroot'] = 'Custom Moodle Base URL';
$string['setting_internal_wwwroot_desc'] = 'Overwrites the default Moodle base URL (i.e. $CFG->wwwroot). This can be useful if you are running the archive worker service inside a private network (e.g. Docker).';
$string['setting_job_timeout_min'] = 'Job timeout (minutes)';
$string['setting_job_timeout_min_desc'] = 'The number of minutes a single archive job is allowed to run at max';
$string['setting_webservice_desc'] = 'The webservice that is allowed to execute the "generate_attempt_report" webservice function';
$string['setting_webservice_userid'] = 'Webservice user ID';
$string['setting_webservice_userid_desc'] = 'ID of the Moodle user that will be used by the archive worker to access quiz data';
$string['setting_worker_url'] = 'Archive worker URL';
$string['setting_worker_url_desc'] = 'URL of the archive worker service to call for quiz archive task execution';