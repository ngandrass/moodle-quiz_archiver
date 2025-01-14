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
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreFile

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = 'Quiz Archiver';
$string['archiverreport'] = 'Quiz Archiver';
$string['checksum'] = 'Checksum';
$string['beta_version_warning'] = 'This plugin is currently in beta stage. Please report any problems and bugs you experience to the site administrator.';
$string['thanks_for_installing'] = 'Thank you for installing the Quiz Archiver plugin!';
$string['go_to_plugin_settings'] = 'Go to plugin settings';
$string['manual_configuration_continue'] = 'To manually configure all plugin settings use the "Continue" button at the bottom of this page.';

// Capabilities.
$string['quiz_archiver:view'] = 'View quiz archiver page';
$string['quiz_archiver:archive'] = 'Create and delete quiz archives';
$string['quiz_archiver:use_webservice'] = 'Use the quiz archiver webservice (read and write)';

// General.
$string['a'] = '{$a}';
$string['progress'] = 'Progress';
$string['quiz_archive'] = 'Quiz archive';
$string['quiz_archive_details'] = 'Quiz archive details';
$string['quiz_archive_not_found'] = 'Quiz archive not found';
$string['quiz_archive_not_ready'] = 'Quiz archive not ready yet';

// Template: Overview.
$string['archived'] = 'Archived';
$string['users_with_attempts'] = 'Users with quiz attempts';
$string['archive_autodelete'] = 'Automatic deletion';
$string['archive_autodelete_short'] = 'Deletion';
$string['archive_autodelete_help'] = 'Automatically delete this quiz archive after a certain amount of time. The retention time can be configured below, once automatic deletion is activated.';
$string['archive_quiz'] = 'Archive quiz';
$string['archive_retention_time'] = 'Retention time';
$string['archive_retention_time_help'] = 'The amount of time this quiz archive should be kept before it is automatically deleted. This setting only takes effect if automatic deletion is activated.';
$string['create_quiz_archive'] = 'Create new quiz archive';
$string['archive_quiz_form_desc'] = 'Trigger the creation of a new quiz archive by submitting this form. This will spawn an asynchronous job which will take some time to complete. You can always check the current status on this page and download finished archives.';
$string['error_archive_quiz_form_validation_failed'] = 'Form data validation failed. Please correct your input and try again.';
$string['error_plugin_is_not_configured'] = 'Error: The quiz archiver plugin is not configured yet. Please contact your site administrator.';
$string['error_quiz_cannot_be_archived_unknown'] = 'This quiz can not be archived due to an unknown error. Please report this problem to the plugin developers.';
$string['export_attempts'] = 'Export quiz attempts';
$string['export_attempts_help'] = 'Quiz attempts will always be exported';
$string['export_attempts_num'] = 'Export quiz attempts ({$a})';
$string['export_attempts_num_help'] = 'Quiz attempts will always be exported';
$string['export_attempts_image_optimize'] = 'Optimize images';
$string['export_attempts_image_optimize_help'] = 'If enabled, images inside the quiz attempt reports will compressed and large images will be shrunk with respect to the specified dimensions. Images will only ever be scaled down. This only affects PDF exports. HTML source files will always keep the original image size.';
$string['export_attempts_image_optimize_group'] = 'Maximum image dimensions';
$string['export_attempts_image_optimize_group_help'] = 'Maximum dimensions for images inside the quiz attempt reports in pixels (width x height). If an image is larger than the given width or height, it will be scaled down so that it fully fits into the given dimensions while maintaining its aspect ratio. This can be useful to reduce the overall archive size if large images are used within the quiz.';
$string['export_attempts_image_optimize_height'] = 'Maximum image height';
$string['export_attempts_image_optimize_height_help'] = 'Maximum height of images inside the quiz attempt reports in pixels. If an images height is larger than the given height, it will be scaled down to the given height while maintaining its aspect ratio.';
$string['export_attempts_image_optimize_quality'] = 'Image compression';
$string['export_attempts_image_optimize_quality_help'] = 'Quality of compressed images (0 - 100 %). The higher the quality, the larger the file size. This behaves like JPEG compression intensity. A good default value is 85 %.';
$string['export_attempts_image_optimize_width'] = 'Maximum image width';
$string['export_attempts_image_optimize_width_help'] = 'Maximum width of images inside the quiz attempt reports in pixels. If an images width is larger than the given width, it will be scaled down to the given width while maintaining its aspect ratio.';
$string['export_attempts_keep_html_files'] = 'HTML files';
$string['export_attempts_keep_html_files_desc'] = 'Keep HTML source files';
$string['export_attempts_keep_html_files_help'] = 'Save HTML source files in addition to the generated PDFs during the export process. This can be useful if you want to access the raw HTML DOM the PDFs were generated from. Disabling this option can significantly reduce the archive size.';
$string['export_attempts_paper_format'] = 'Paper size';
$string['export_attempts_paper_format_help'] = 'The paper size to use for the PDF export. This does not not affect HTML exports.';
$string['export_course_backup'] = 'Export full Moodle course backup (.mbz)';
$string['export_course_backup_help'] = 'This will export a full Moodle course backup (.mbz) including everything inside this course. This can be useful if you want to import this course into another Moodle instance.';
$string['export_quiz_backup'] = 'Export Moodle quiz backup (.mbz)';
$string['export_quiz_backup_help'] = 'This will export a Moodle quiz backup (.mbz) including questions used inside this quiz. This can be useful if you want to import this quiz independent of this course into another Moodle instance.';
$string['export_report_section_header'] = 'Include quiz header';
$string['export_report_section_header_help'] = 'Display quiz metadata (e.g., user, time taken, grade, ...) inside the attempt report.';
$string['export_report_section_question'] = 'Include questions';
$string['export_report_section_question_help'] = 'Display all questions that are part of this attempt inside the attempt report.';
$string['export_report_section_rightanswer'] = 'Include correct answers';
$string['export_report_section_rightanswer_help'] = 'Display the correct answers for each question inside the attempt report.';
$string['export_report_section_quiz_feedback'] = 'Include overall quiz feedback';
$string['export_report_section_quiz_feedback_help'] = 'Display the overall quiz feedback inside the attempt report header.';
$string['export_report_section_question_feedback'] = 'Include individual question feedback';
$string['export_report_section_question_feedback_help'] = 'Display the individual feedback for each question inside the attempt report.';
$string['export_report_section_general_feedback'] = 'Include general question feedback';
$string['export_report_section_general_feedback_help'] = 'Display the general feedback for each question inside the attempt report.';
$string['export_report_section_history'] = 'Include answer history';
$string['export_report_section_history_help'] = 'Display the answer history for each question inside the attempt report.';
$string['export_report_section_attachments'] = 'Include file attachments';
$string['export_report_section_attachments_help'] = 'Include all file attachments (e.g., essay file submissions) inside the archive. Warning: This can significantly increase the archive size.';
$string['job_overview'] = 'Archives';
$string['last_updated'] = 'Last updated';
$string['num_attempts'] = 'Number of attempts';

// Job creation form: Filename pattern.
$string['archive_filename_pattern'] = 'Archive name';
$string['archive_filename_pattern_help'] = 'Name of the generated quiz archive. Variables <b>must</b> follow the <code>${variablename}</code> pattern. The file extension will be added automatically.<br><br><b>Available variables</b>: <ul>{$a->variables}</ul><b>Forbidden characters</b>: <code>{$a->forbiddenchars}</code>';
// TODO (MDL-0): Remove the following 2 lines after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
$string['archive_filename_pattern_moodle42'] = 'Archive name';
$string['archive_filename_pattern_moodle42_help'] = 'Name of the generated quiz archive. Variables <b>must</b> follow the <code>${variablename}</code> pattern. The file extension will be added automatically.<br><br><b>Available variables</b>: <ul><li><code>${courseid}</code>: Course ID</li><li><code>${coursename}</code>: Course name</li><li><code>${courseshortname}</code>: Course short name</li><li><code>${cmid}</code>: Course module ID</li><li><code>${quizid}</code>: Quiz ID</li><li><code>${quizname}</code>: Quiz name</li><li><code>${date}</code>: Current date <small>(YYYY-MM-DD)</small></li><li><code>${time}</code>: Current time <small>(HH-MM-SS)</small></li><li><code>${timestamp}</code>: Current unix timestamp</li></ul><b>Forbidden characters</b>: <code>\/.:;*?!"&lt;&gt;|</code>';
$string['archive_filename_pattern_variable_courseid'] = 'Course ID';
$string['archive_filename_pattern_variable_coursename'] = 'Course name';
$string['archive_filename_pattern_variable_courseshortname'] = 'Course short name';
$string['archive_filename_pattern_variable_cmid'] = 'Course module ID';
$string['archive_filename_pattern_variable_quizid'] = 'Quiz ID';
$string['archive_filename_pattern_variable_quizname'] = 'Quiz name';
$string['archive_filename_pattern_variable_date'] = 'Current date <small>(YYYY-MM-DD)</small>';
$string['archive_filename_pattern_variable_time'] = 'Current time <small>(HH-MM-SS)</small>';
$string['archive_filename_pattern_variable_timestamp'] = 'Current unix timestamp';
$string['error_invalid_archive_filename_pattern'] = 'Invalid archive filename pattern. Please correct your input and try again.';
$string['export_attempts_filename_pattern'] = 'Attempt name';
$string['export_attempts_filename_pattern_help'] = 'Name of the generated quiz attempt reports (PDF files). Variables <b>must</b> follow the <code>${variablename}</code> pattern. The file extension will be added automatically.<br><br><b>Available variables</b>: <ul>{$a->variables}</ul><b>Forbidden characters</b>: <code>{$a->forbiddenchars}</code>';
// TODO (MDL-0): Remove the following 2 lines after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
$string['export_attempts_filename_pattern_moodle42'] = 'Attempt name';
$string['export_attempts_filename_pattern_moodle42_help'] = 'Name of the generated quiz attempt reports (PDF files). Variables <b>must</b> follow the <code>${variablename}</code> pattern. The file extension will be added automatically.<br><br><b>Available variables</b>: <ul><li><code>${courseid}</code>: Course ID</li><li><code>${coursename}</code>: Course name</li><li><code>${courseshortname}</code>: Course short name</li><li><code>${cmid}</code>: Course module ID</li><li><code>${quizid}</code>: Quiz ID</li><li><code>${quizname}</code>: Quiz name</li><li><code>${attemptid}</code>: Attempt ID</li><li><code>${username}</code>: Student username</li><li><code>${firstname}</code>: Student first name</li><li><code>${lastname}</code>: Student last name</li><li><code>${timestart}</code>: Attempt start unix timestamp</li><li><code>${timefinish}</code>: Attempt finish unix timestamp</li><li><code>${date}</code>: Current date <small>(YYYY-MM-DD)</small></li><li><code>${time}</code>: Current time <small>(HH-MM-SS)</small></li><li><code>${timestamp}</code>: Current unix timestamp</li></ul><b>Forbidden characters</b>: <code>\/.:;*?!"&lt;&gt;|</code>';
$string['export_attempts_filename_pattern_variable_courseid'] = 'Course ID';
$string['export_attempts_filename_pattern_variable_coursename'] = 'Course name';
$string['export_attempts_filename_pattern_variable_courseshortname'] = 'Course short name';
$string['export_attempts_filename_pattern_variable_cmid'] = 'Course module ID';
$string['export_attempts_filename_pattern_variable_quizid'] = 'Quiz ID';
$string['export_attempts_filename_pattern_variable_quizname'] = 'Quiz name';
$string['export_attempts_filename_pattern_variable_attemptid'] = 'Attempt ID';
$string['export_attempts_filename_pattern_variable_username'] = 'Student username';
$string['export_attempts_filename_pattern_variable_firstname'] = 'Student first name';
$string['export_attempts_filename_pattern_variable_lastname'] = 'Student last name';
$string['export_attempts_filename_pattern_variable_idnumber'] = 'Student ID number';
$string['export_attempts_filename_pattern_variable_timestart'] = 'Attempt start unix timestamp';
$string['export_attempts_filename_pattern_variable_timefinish'] = 'Attempt finish unix timestamp';
$string['export_attempts_filename_pattern_variable_date'] = 'Current date <small>(YYYY-MM-DD)</small>';
$string['export_attempts_filename_pattern_variable_time'] = 'Current time <small>(HH-MM-SS)</small>';
$string['export_attempts_filename_pattern_variable_timestamp'] = 'Current unix timestamp';
$string['error_invalid_attempt_filename_pattern'] = 'Invalid attempt report filename pattern. Please correct your input and try again.';

// Job.
$string['delete_artifact'] = 'Delete quiz archive';
$string['delete_artifact_success'] = 'Quiz archive for Job with ID <code>{$a}</code> was deleted successfully. The job metadata still exists and can be fully deleted using the "Delete job" button.';
$string['delete_artifact_warning'] = 'Are you sure that you want to delete this quiz archive including <b>all archived data?</b>. The job metadate will be kept.';
$string['delete_job'] = 'Delete archive job';
$string['delete_job_success'] = 'Archive job with ID <code>{$a}</code> was deleted successfully.';
$string['delete_job_warning'] = 'Are you sure that you want to delete this archive job <b>including all archived data?</b>';
$string['delete_job_warning_retention'] = '<b>Attention:</b> This archive job is scheduled for automatic deletion on <code>{$a}</code>. Are you absolutely sure that you want to delete it <b>before its scheduled lifetime expired</b>?';
$string['jobid'] = 'Job ID';
$string['job_created_successfully'] = 'New archive job created successfully. Job ID: <code>{$a}</code>';
$string['job_status_UNKNOWN'] = 'Unknown';
$string['job_status_UNKNOWN_help'] = 'The status of this job is unknown. Please open a bug report if this problem persists.';
$string['job_status_UNINITIALIZED'] = 'Uninitialized';
$string['job_status_UNINITIALIZED_help'] = 'The job has not been initialized yet.';
$string['job_status_AWAITING_PROCESSING'] = 'Queued';
$string['job_status_AWAITING_PROCESSING_help'] = 'The job registered by the archive worker service and is waiting to be processed.';
$string['job_status_RUNNING'] = 'Running';
$string['job_status_RUNNING_help'] = 'The job is currently being processed by the archive worker service. The job progress is updated periodically (default: every 15 seconds).';
$string['job_status_WAITING_FOR_BACKUP'] = 'Backup wait';
$string['job_status_WAITING_FOR_BACKUP_help'] = 'The job is waiting for a Moodle backup to be created. This can take some time depending on the size of the course.';
$string['job_status_FINALIZING'] = 'Finalizing';
$string['job_status_FINALIZING_help'] = 'The archive worker is finalizing the archive and transfers it to Moodle. This can take some time depending on the size of the archive.';
$string['job_status_FINISHED'] = 'Finished';
$string['job_status_FINISHED_help'] = 'The job has been successfully completed. The archive is ready for download.';
$string['job_status_FAILED'] = 'Failed';
$string['job_status_FAILED_help'] = 'The job has failed. Please try again and contact your system administrator if this problem persists.';
$string['job_status_TIMEOUT'] = 'Timeout';
$string['job_status_TIMEOUT_help'] = 'The job has been aborted due to a timeout. This can happen for very large quizzes. Please contact your system administrator if this problem persists.';
$string['job_status_DELETED'] = 'Deleted';
$string['job_status_DELETED_help'] = 'The quiz archive and all associated data has been removed. The job metadata still exists and can be fully deleted, if required.';

// Job details.
$string['archive_already_signed'] = 'Archive is already signed';
$string['archive_already_signed_with_jobid'] = 'Quiz archive for job with ID <code>{$a}</code> is already signed.';
$string['archive_autodelete_deleted'] = 'Archive was automatically deleted';
$string['archive_autodelete_in'] = 'Archive will be deleted in {$a}';
$string['archive_autodelete_disabled'] = 'Disabled';
$string['archive_autodelete_now'] = 'Archive is scheduled for deletion';
$string['archive_deleted'] = 'Archive was deleted';
$string['archive_not_signed'] = 'Archive is unsigned';
$string['archive_signature'] = 'Signature';
$string['archive_signed_successfully'] = 'Archive signed successfully';
$string['archive_signed_successfully_with_jobid'] = 'Quiz archive for job with ID <code>{$a}</code> was signed successfully.';
$string['archive_signing_failed'] = 'Archive signing failed';
$string['archive_signing_failed_with_jobid'] = 'Signing the quiz archive for job with ID <code>{$a}</code> failed due to a generic error. Please make sure that TSP archive signing is enabled within the plugin settings.';
$string['archive_signing_failed_no_artifact'] = 'No valid artifact file found';
$string['archive_signing_failed_no_artifact_with_jobid'] = 'Signing the quiz archive for job with ID <code>{$a}</code> failed. No valid artifact file found.';
$string['archive_signing_failed_tsp_disabled'] = 'TSP signing is disabled globally';
$string['sign_archive'] = 'Sign archive now';
$string['sign_archive_warning'] = 'Are you sure that you want to sign this archive now?';
$string['signed_on'] = 'Signed on';
$string['signed_by'] = 'by';
$string['tsp_query_filename'] = 'query.tsq';
$string['tsp_reply_filename'] = 'reply.tsr';

// TimeStampProtocolClient.
$string['tsp_client_error_content_type'] = 'TSP server returned unexpected content type {$a}';
$string['tsp_client_error_curl'] = 'Error while sending TSP request: {$a}';
$string['tsp_client_error_http_code'] = 'TSP server returned HTTP status code {$a}';

// Settings.
$string['setting_autoconfigure'] = 'Automatic configuration';
$string['setting_header_archive_worker'] = 'Archive Worker Service';
$string['setting_header_archive_worker_desc'] = 'Configuration of the archive worker service and the Moodle web service it uses.';
$string['setting_header_docs_desc'] = 'This plugin archives quiz attempts as PDF and HTML files for long-term storage, independent of Moodle. It <b>requires a separate <a href="https://quizarchiver.gandrass.de/installation/archiveworker/" target="_blank">worker service</a></b> to be installed for the actual archiving process to work. Please refer to the <a href="https://quizarchiver.gandrass.de/" target="_blank">documentation</a> for more details and setup instructions.';
$string['setting_header_job_presets'] = 'Archive Presets';
$string['setting_header_job_presets_desc'] = 'System wide default settings for quiz archive creation. These defaults can be overridden when creating a new quiz archive. However, each individual setting can also be locked to prevent managers / teachers from changing it. This can be useful when enforcing organization wide archive policies.';
$string['setting_header_tsp'] = 'Archive Signing';
$string['setting_header_tsp_desc'] = 'Quiz archives and their creation date can be digitally signed by a trusted authority using the <a href="https://en.wikipedia.org/wiki/Time_stamp_protocol" target="_blank">Time-Stamp Protocol (TSP)</a> according to <a href="https://www.ietf.org/rfc/rfc3161.txt" target="_blank">RFC 3161</a>. This can be used to cryptographically prove the integrity and creation date of the archive at a later point in time. Quiz archives can be signed automatically at creation or manually later on.';
$string['setting_internal_wwwroot'] = 'Custom Moodle base URL';
$string['setting_internal_wwwroot_desc'] = 'Overwrites the default Moodle base URL (<code>$CFG->wwwroot</code>) inside generated attempt reports. This can be useful if you are running the archive worker service inside a private network (e.g., Docker) and want it to access Moodle directly.<br/>Example: <code>http://moodle/</code>';
$string['setting_job_timeout_min'] = 'Job timeout (minutes)';
$string['setting_job_timeout_min_desc'] = 'The number of minutes a single archive job is allowed to run before it is aborted by Moodle. Job web service access tokens become invalid after this timeout.<br/>Note: This timeout can not exceed the timeout configured within the archive worker service. The shorter timeout always takes precedence.';
$string['setting_tsp_automatic_signing'] = 'Automatically sign quiz archives';
$string['setting_tsp_automatic_signing_desc'] = 'Automatically sign quiz archives when they are created.';
$string['setting_tsp_enable'] = 'Enable quiz archive signing';
$string['setting_tsp_enable_desc'] = 'Allow quiz archives to be signed using the Time-Stamp Protocol (TSP). If this option is disabled, quiz archives can neither be signed manually nor automatically.';
$string['setting_tsp_server_url'] = 'TSP server URL';
$string['setting_tsp_server_url_desc'] = 'URL of the Time-Stamp Protocol (TSP) server to use.<br/>Examples: <code>https://freetsa.org/tsr</code>, <code>https://zeitstempel.dfn.de</code>, <code>http://timestamp.digicert.com</code>';
$string['setting_webservice_desc'] = 'The external service (webservice) that is allowed to execute all <code>quiz_archiver_*</code> webservice functions. It must also have permission to up- and download files.';
$string['setting_webservice_userid'] = 'Web service user-ID';
$string['setting_webservice_userid_desc'] = 'User-ID of the Moodle user that is used by the archive worker service to access quiz data. It must have all capabilities that are listed in the <a href="https://quizarchiver.gandrass.de/configuration/initialconfig/manual" target="_blank">documentation</a> to work properly. For security reasons, this should be a dedicated user account without full administrative privileges.';
$string['setting_worker_url'] = 'Archive worker URL';
$string['setting_worker_url_desc'] = 'URL of the archive worker service to call for quiz archive task execution. If you only want to try the Quiz Archiver, you can use the <a href="https://quizarchiver.gandrass.de/installation/archiveworker/#using-the-free-public-demo-service" target="_blank">free public demo quiz archive worker service</a>, eliminating the need to set up your own worker service right away.<br/>Example: <code>http://127.0.0.1:8080</code> or <code>http://moodle-quiz-archive-worker:8080</code>';

// Errors.
$string['error_worker_connection_failed'] = 'Establishing a connection to the archive worker failed.';
$string['error_worker_reported_error'] = 'The archive worker reported an error: {$a}';
$string['error_worker_unknown'] = 'An unknown error occurred while enqueueing the job at the remote archive worker.';

// Privacy.
$string['privacy:metadata:core_files'] = 'The quiz archiver plugin stores created quiz archives inside the Moodle file system.';
$string['privacy:metadata:quiz_archiver_jobs'] = 'Metadata about created quiz archives.';
$string['privacy:metadata:quiz_archiver_jobs:courseid'] = 'The course ID of the course the quiz archive belongs to.';
$string['privacy:metadata:quiz_archiver_jobs:cmid'] = 'The course module ID of the quiz the quiz archive belongs to.';
$string['privacy:metadata:quiz_archiver_jobs:quizid'] = 'The quiz ID of the quiz the quiz archive belongs to.';
$string['privacy:metadata:quiz_archiver_jobs:userid'] = 'The user ID of the user who created the quiz archive.';
$string['privacy:metadata:quiz_archiver_jobs:timecreated'] = 'The time when the quiz archive was created.';
$string['privacy:metadata:quiz_archiver_jobs:timemodified'] = 'The time when the quiz archive was last modified (e.g., job status updated).';
$string['privacy:metadata:quiz_archiver_job_settings'] = 'Job settings during quiz archive creation (e.g., included sections, number of attempts, ...).';
$string['privacy:metadata:quiz_archiver_job_settings:key'] = 'The key / name of a respective setting (e.g., number of attempts).';
$string['privacy:metadata:quiz_archiver_job_settings:value'] = 'The value of a respective setting (e.g., 42).';
$string['privacy:metadata:quiz_archiver_tsp'] = 'Time-Stamp Protocol (TSP) data for quiz archives.';
$string['privacy:metadata:quiz_archiver_tsp:timecreated'] = 'The time when the quiz archive was signed.';
$string['privacy:metadata:quiz_archiver_tsp:server'] = 'The URL of the TSP server that was used to sign the quiz archive.';
$string['privacy:metadata:quiz_archiver_tsp:timestampquery'] = 'The timestamp query that was sent to the TSP server.';
$string['privacy:metadata:quiz_archiver_tsp:timestampreply'] = 'The timestamp reply that was received from the TSP server.';

// Tasks.
$string['task_cleanup_temp_files'] = 'Cleanup temporary files';
$string['task_cleanup_temp_files_start'] = 'Cleaning up expired temporary files ...';
$string['task_cleanup_temp_files_report'] = 'Deleted {$a} temporary files.';
$string['task_autodelete_job_artifacts'] = 'Delete expired quiz archives';
$string['task_autodelete_job_artifacts_start'] = 'Deleting expired quiz archives ...';
$string['task_autodelete_job_artifacts_report'] = 'Deleted {$a} quiz archives.';

// Autoinstall.
$string['autoinstall_already_configured'] = 'Plugin is already configured';
$string['autoinstall_already_configured_long'] = 'The Quiz Archiver plugin is already configured. Automatic configuration is not possible twice.';
$string['autoinstall_cancelled'] = 'The automatic configuration of the Quiz Archiver Plugin was cancelled. No changes were made.';
$string['autoinstall_explanation'] = 'The Quiz Archiver plugin requires a few initial configuration steps to work (see <a href="https://quizarchiver.gandrass.de/configuration/" target="_blank">Configuration</a>). You can either configure all of these settings manually or use the automatic configuration feature to take care of all Moodle related settings.';
$string['autoinstall_explanation_details'] = 'The automatic configuration feature will take care of the following steps:<ul><li>Setting all plugin settings to their default values</li><li>Enabling web services and REST protocol</li><li>Creating a quiz archiver service role and a corresponding user</li><li>Creating a new web service with all required webservice functions</li><li>Authorising the user to use the webservice</li></ul>';
$string['autoinstall_failure'] = 'The automatic configuration of the Quiz Archiver Plugin has <b>failed</b>.';
$string['autoinstall_plugin'] = 'Quiz Archiver: Automatic configuration';
$string['autoinstall_started'] = 'Automatic configuration started ...';
$string['autoinstall_start_now'] = 'Start automatic configuration now';
$string['autoinstall_success'] = 'The automatic configuration of the Quiz Archiver Plugin was <b>successful</b>.';
$string['autoinstall_rolename'] = 'Role name';
$string['autoinstall_rolename_help'] = 'Name of the role that is created for the quiz archiver service user.';
$string['autoinstall_username'] = 'Username';
$string['autoinstall_username_help'] = 'Name of the service user that is created to access the quiz archiver webservice.';
$string['autoinstall_wsname'] = 'Web service name';
$string['autoinstall_wsname_help'] = 'Name of the webservice that is created for the quiz archive worker.';
