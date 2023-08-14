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

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = $string['pluginname'];
$string['archiverreport'] = $string['pluginname'];
$string['checksum'] = 'Checksum';
$string['beta_version_warning'] = 'This plugin is currently in beta stage. Please report any problems and bugs you experience to the site administrator.';

// Capabilities
$string['quiz_archiver:view'] = 'View quiz archiver report page';
$string['quiz_archiver:archive'] = 'Create and delete quiz archives';
$string['quiz_archiver:use_webservice'] = 'Use the quiz archiver webservice (read and write)';

// General
$string['quiz_archive'] = 'Quiz archive';
$string['quiz_archive_details'] = 'Quiz archive details';
$string['quiz_archive_not_found'] = 'Quiz archive not found';
$string['quiz_archive_not_ready'] = 'Quiz archive not ready yet';

// Template: Overview
$string['archived'] = 'Archived';
$string['users_with_attempts'] = 'Users with quiz attempts';
$string['archive_quiz'] = 'Archive quiz';
$string['create_quiz_archive'] = 'Create new quiz archive';
$string['archive_quiz_form_desc'] = 'Trigger the creation of a new quiz archive by submitting this form. This will spawn an asynchronous job which will take some time to complete. You can always check the current status on this page.';
$string['export_attempts'] = 'Export quiz attempts';
$string['export_attempts_help'] = 'Quiz attempts will always be exported';
$string['export_attempts_num'] = 'Export quiz attempts ({$a})';
$string['export_attempts_num_help'] = $string['export_attempts_help'];
$string['export_course_backup'] = 'Export full Moodle course backup (.mbz)';
$string['export_course_backup_help'] = 'This will export a full Moodle course backup (.mbz) including everything inside this course. This can be useful if you want to import this course into another Moodle instance.';
$string['export_quiz_backup'] = 'Export Moodle quiz backup (.mbz)';
$string['export_quiz_backup_help'] = 'This will export a Moodle quiz backup (.mbz) including questions used inside this quiz. This can be useful if you want to import this quiz independent of this course into another Moodle instance.';
$string['export_report_section_header'] = 'Include quiz header';
$string['export_report_section_header_help'] = 'Display quiz metadata (e.g., user, time taken, grade, ...) inside the report.';
$string['export_report_section_question'] = 'Include questions';
$string['export_report_section_question_help'] = 'Display all questions that are part of this attempt inside the report.';
$string['export_report_section_rightanswer'] = 'Include correct answers';
$string['export_report_section_rightanswer_help'] = 'Display the correct answers for each question inside the report.';
$string['export_report_section_quiz_feedback'] = 'Include overall quiz feedback';
$string['export_report_section_quiz_feedback_help'] = 'Display the overall quiz feedback inside the report header.';
$string['export_report_section_question_feedback'] = 'Include individual question feedback';
$string['export_report_section_question_feedback_help'] = 'Display the individual feedback for each question inside the report.';
$string['export_report_section_general_feedback'] = 'Include general question feedback';
$string['export_report_section_general_feedback_help'] = 'Display the general feedback for each question inside the report.';
$string['export_report_section_history'] = 'Include answer history';
$string['export_report_section_history_help'] = 'Display the answer history for each question inside the report.';
$string['job_overview'] = 'Archives';
$string['num_attempts'] = 'Number of attempts';

// Job
$string['delete_job_warning'] = 'Are you sure that you want to delete this archive job <b>including all archived data?</b>';
$string['jobid'] = 'Job ID';
$string['job_created_successfully'] = 'New archive job created successfully: {$a}';
$string['job_status_UNKNOWN'] = 'Unknown';
$string['job_status_UNINITIALIZED'] = 'Uninitialized';
$string['job_status_AWAITING_PROCESSING'] = 'Queued';
$string['job_status_RUNNING'] = 'Running';
$string['job_status_FINISHED'] = 'Finished';
$string['job_status_FAILED'] = 'Failed';
$string['job_status_TIMEOUT'] = 'Timeout';

// Settings
$string['setting_header_docs_desc'] = 'This plugin archives quiz attempts as PDF and HTML files for long-term storage, independent of Moodle. It <b>requires a separate <a href="https://github.com/ngandrass/moodle-quiz-archive-worker" target="_blank">worker service</a></b> to be installed for the actual archiving process to work. Please refer to the <a href="https://github.com/ngandrass/moodle-quiz_archiver#readme" target="_blank">documentation</a> for more details and setup instructions.';
$string['setting_internal_wwwroot'] = 'Custom Moodle base URL';
$string['setting_internal_wwwroot_desc'] = 'Overwrites the default Moodle base URL (<code>$CFG->wwwroot</code>) inside generated reports. This can be useful if you are running the archive worker service inside a private network (e.g., Docker) and want it to access Moodle directly.<br/>Example: <code>http://moodle/</code>';
$string['setting_job_timeout_min'] = 'Job timeout (minutes)';
$string['setting_job_timeout_min_desc'] = 'The number of minutes a single archive job is allowed to run before it is aborted by Moodle. Job web service access tokens become invalid after this timeout.';
$string['setting_webservice_desc'] = 'The webservice that is allowed to execute all <code>quiz_archiver_*</code> webservice functions. It must also have permission to up- and download files.';
$string['setting_webservice_userid'] = 'Web service user-ID';
$string['setting_webservice_userid_desc'] = 'User-ID of the Moodle user that is used by the archive worker service to access quiz data. It must have all capabilities that are listed in the <a href="https://github.com/ngandrass/moodle-quiz_archiver#configuration" target="_blank">documentation</a> to work properly. For security reasons, this should be a dedicated user account without full administrative privileges.';
$string['setting_worker_url'] = 'Archive worker URL';
$string['setting_worker_url_desc'] = 'URL of the archive worker service to call for quiz archive task execution.<br/>Example: <code>http://127.0.0.1:8080</code> or <code>http://moodle-quiz-archive-worker:8080</code>';

// Errors
$string['error_worker_connection_failed'] = 'Establishing a connection to the archive worker failed.';
$string['error_worker_reported_error'] = 'The archive worker reported an error: {$a}';
$string['error_worker_unknown'] = 'An unknown error occurred while enqueueing the job at the remote archive worker.';
