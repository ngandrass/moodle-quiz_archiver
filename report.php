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
 * This file defines the quiz archiver class.
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


// TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
require_once($CFG->dirroot.'/mod/quiz/report/archiver/patch_401_class_renames.php'); // @codeCoverageIgnore

use mod_quiz\local\reports\report_base;
use quiz_archiver\ArchiveJob;
use quiz_archiver\BackupManager;
use quiz_archiver\form\artifact_delete_form;
use quiz_archiver\local\autoinstall;
use quiz_archiver\local\util;
use quiz_archiver\RemoteArchiveWorker;
use quiz_archiver\Report;
use quiz_archiver\form\archive_quiz_form;
use quiz_archiver\form\job_delete_form;
use quiz_archiver\form\job_sign_form;
use quiz_archiver\output\job_overview_table;

/**
 * The quiz archiver report class.
 */
class quiz_archiver_report extends report_base {

    /** @var object course object. */
    protected $course;
    /** @var object course module object. */
    protected $cm;
    /** @var object the quiz settings object. */
    protected $quiz;
    /** @var context the quiz context. */
    protected $context;
    /** @var object Moodle admin settings object */
    protected $config;
    /** @var Report internal report instance */
    protected Report $report;

    /**
     * Creates a new quiz_archiver_report instance and automatically loads the
     * Moodle config for this plugin.
     *
     * @throws dml_exception
     */
    public function __construct() {
        $this->config = get_config('quiz_archiver');
    }

    /**
     * Display the report.
     *
     * @param object $quiz this quiz.
     * @param object $cm the course-module for this quiz.
     * @param object $course the course we are in.
     * @return bool
     * @throws moodle_exception
     */
    public function display($quiz, $cm, $course): bool {
        global $OUTPUT;

        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->report = new Report($this->course, $this->cm, $this->quiz);

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz_archiver:view', $this->context);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'archiver');
        $tplctx = [
            'baseurl' => $this->base_url(),
            'jobOverviewTable' => "",
        ];

        // Handle job delete form.
        if (optional_param('action', null, PARAM_TEXT) === 'delete_job') {
            $jobdeleteform = new job_delete_form();

            if ($jobdeleteform->is_cancelled()) {
                redirect($this->base_url());
            }

            if ($jobdeleteform->is_submitted()) {
                // Check permissions.
                require_capability('mod/quiz_archiver:delete', $this->context);

                // Execute deletion.
                $formdata = $jobdeleteform->get_data();
                ArchiveJob::get_by_jobid($formdata->jobid)->delete();
            } else {
                $jobdeleteform->display();
                return true;
            }
        }

        // Handle artifact delete form.
        if (optional_param('action', null, PARAM_TEXT) === 'delete_artifact') {
            $arfifactdeleteform = new artifact_delete_form();

            if ($arfifactdeleteform->is_cancelled()) {
                redirect($this->base_url());
            }

            if ($arfifactdeleteform->is_submitted()) {
                // Check permissions.
                require_capability('mod/quiz_archiver:delete', $this->context);

                // Execute deletion.
                $formdata = $arfifactdeleteform->get_data();
                ArchiveJob::get_by_jobid($formdata->jobid)->delete_artifact();
            } else {
                $arfifactdeleteform->display();
                return true;
            }
        }

        // Handle job sign form.
        if (optional_param('action', null, PARAM_TEXT) === 'sign_job') {
            $jobsignform = new job_sign_form();

            if ($jobsignform->is_cancelled()) {
                redirect($this->base_url());
            }

            if ($jobsignform->is_submitted()) {
                // Check permissions.
                require_capability('mod/quiz_archiver:create', $this->context);

                // Execute signing.
                $formdata = $jobsignform->get_data();
                $tspmanager = ArchiveJob::get_by_jobid($formdata->jobid)->tspmanager();
                $jobidlogstr = ' ('.get_string('jobid', 'quiz_archiver').': '.$formdata->jobid.')';
                if ($tspmanager->has_tsp_timestamp()) {
                    $tplctx['jobInitiationStatusAlert'] = [
                        "color" => "danger",
                        "dismissible" => true,
                        "message" => get_string('archive_already_signed', 'quiz_archiver').$jobidlogstr,
                    ];
                } else {
                    try {
                        $tspmanager->timestamp();
                        $tplctx['jobInitiationStatusAlert'] = [
                            "color" => "success",
                            "dismissible" => true,
                            "message" => get_string('archive_signed_successfully', 'quiz_archiver').$jobidlogstr,
                        ];
                    } catch (RuntimeException $e) {
                        $tplctx['jobInitiationStatusAlert'] = [
                            "color" => "danger",
                            "dismissible" => true,
                            "message" => get_string('archive_signing_failed_no_artifact', 'quiz_archiver').$jobidlogstr,
                        ];
                    } catch (Exception $e) {
                        $tplctx['jobInitiationStatusAlert'] = [
                            "color" => "danger",
                            "dismissible" => true,
                            "message" => get_string('archive_signing_failed', 'quiz_archiver').': '.$e->getMessage().$jobidlogstr,
                        ];
                    }
                }
            } else {
                $jobsignform->display();
                return true;
            }
        }

        // Determine page to display.
        if (!quiz_has_questions($quiz->id)) {
            $tplctx['quizMissingSomethingWarning'] = quiz_no_questions_message($quiz, $cm, $this->context);
        } else {
            if (!quiz_has_attempts($quiz->id)) {
                $tplctx['quizMissingSomethingWarning'] = $OUTPUT->notification(
                    get_string('noattempts', 'quiz'),
                    \core\output\notification::NOTIFY_ERROR,
                    false
                );
            }
        }

        // Archive quiz form.
        if (!array_key_exists('quizMissingSomethingWarning', $tplctx)) {
            $archivequizform = new archive_quiz_form(
                $this->quiz->name,
                count($this->report->get_attempts())
            );
            if ($archivequizform->is_submitted()) {
                $job = null;
                try {
                    if (!$archivequizform->is_validated()) {
                        throw new RuntimeException(get_string('error_archive_quiz_form_validation_failed', 'quiz_archiver'));
                    }

                    $formdata = $archivequizform->get_data();
                    $job = $this->initiate_archive_job(
                        $formdata->export_attempts,
                        Report::build_report_sections_from_formdata($formdata),
                        $formdata->export_attempts_keep_html_files,
                        $formdata->export_attempts_paper_format,
                        $formdata->export_quiz_backup,
                        $formdata->export_course_backup,
                        $formdata->archive_filename_pattern,
                        $formdata->export_attempts_filename_pattern,
                        $formdata->export_attempts_image_optimize ? [
                            'width' => (int) $formdata->export_attempts_image_optimize_width,
                            'height' => (int) $formdata->export_attempts_image_optimize_height,
                            'quality' => (int) $formdata->export_attempts_image_optimize_quality,
                        ] : null,
                        $formdata->archive_autodelete ? $formdata->archive_retention_time : null,
                    );
                    $tplctx['jobInitiationStatusAlert'] = [
                        "color" => "success",
                        "message" => get_string('job_created_successfully', 'quiz_archiver', $job->get_jobid()),
                        "returnMessage" => get_string('continue'),
                    ];
                } catch (RuntimeException $e) {
                    $tplctx['jobInitiationStatusAlert'] = [
                        "color" => "danger",
                        "message" => $e->getMessage(),
                        "returnMessage" => get_string('retry'),
                    ];
                }

                // Do not print job overview table if job creation failed.
                if ($job == null) {
                    unset($tplctx['jobOverviewTable']);
                }
            } else {
                $tplctx['jobInitiationForm'] = $archivequizform->render();
            }
        }

        // Job overview table.
        if (array_key_exists('jobOverviewTable', $tplctx)) {
            // Generate table.
            $jobtbl = new job_overview_table('job_overview_table', $this->course->id, $this->cm->id, $this->quiz->id);
            $jobtbl->define_baseurl($this->base_url());
            ob_start();
            $jobtbl->out(10, true);
            $jobtblhtml = ob_get_contents();
            ob_end_clean();
            $tplctx['jobOverviewTable'] = $jobtblhtml;

            // Prepare job metadata for job detail modals.
            $tplctx['jobs'] = array_map(function($jm): array {
                // Generate action URLs.
                $jm['action_urls'] = [
                    'delete_job' => (new moodle_url($this->base_url(), [
                        'id' => optional_param('id', null, PARAM_INT),
                        'mode' => 'archiver',
                        'action' => 'delete_job',
                        'jobid' => $jm['jobid'],
                    ]))->out(),
                    'delete_artifact' => (new moodle_url($this->base_url(), [
                        'id' => optional_param('id', null, PARAM_INT),
                        'mode' => 'archiver',
                        'action' => 'delete_artifact',
                        'jobid' => $jm['jobid'],
                    ]))->out(),
                    'sign_artifact' => (new moodle_url('', [
                        'id' => optional_param('id', null, PARAM_INT),
                        'mode' => 'archiver',
                        'action' => 'sign_job',
                        'jobid' => $jm['jobid'],
                    ]))->out(),
                    'course' => (new moodle_url('/course/view.php', [
                        'id' => $this->course->id,
                    ]))->out(),
                    'quiz' => (new moodle_url('/mod/quiz/view.php', [
                        'id' => $this->cm->id,
                    ]))->out(),
                    'user' => (new moodle_url('/user/profile.php', [
                        'id' => $jm['user']['id'],
                    ]))->out(),
                ];

                // Inject global TSP settings.
                // Moodle stores checkbox values as '0' and '1'. Mustache interprets '0' as true.
                $jm['tsp_enabled'] = ($this->config->tsp_enable == true);

                return [
                    'jobid' => $jm['jobid'],
                    'json' => json_encode($jm),
                ];
            }, ArchiveJob::get_metadata_for_jobs($this->course->id, $this->cm->id, $this->quiz->id));
        }

        // Housekeeping for jobs associated with this quiz.
        foreach (ArchiveJob::get_jobs($this->course->id, $this->cm->id, $this->quiz->id) as $job) {
            $job->timeout_if_overdue($this->config->job_timeout_min);
        }

        // Render output.
        echo $OUTPUT->render_from_template('quiz_archiver/overview', $tplctx);

        return true;
    }

    /**
     * Initiates a new archive job for this quiz
     *
     * @param bool $exportattempts Quiz attempts will be archives if true
     * @param array $reportsections Sections to export during attempt report generation
     * @param bool $reportkeephtmlfiles If true, HTML files are kept alongside PDFs
     *                                     within the created archive
     * @param string $paperformat Paper format to use for attempt report generation
     * @param bool $exportquizbackup Complete quiz backup will be archived if true
     * @param bool $exportcoursebackup Complete course backup will be archived if true
     * @param string $archivefilenamepattern Filename pattern to use for archive generation
     * @param string $attemptsfilenamepattern Filename pattern to use for attempt report generation
     * @param array|null $imageoptimize If set, images in the attempt report will be optimized according to the passed array
     * containing 'width', 'height', and 'quality'
     * @param int|null $retentionseconds If set, the archive will be deleted automatically this many seconds after creation
     * @return ArchiveJob|null Created ArchiveJob on success
     * @throws coding_exception Handled by Moodle
     * @throws dml_exception Handled by Moodle
     * @throws moodle_exception Handled by Moodle
     * @throws RuntimeException Used to signal a soft failure to calling context
     */
    protected function initiate_archive_job(
        bool   $exportattempts,
        array  $reportsections,
        bool   $reportkeephtmlfiles,
        string $paperformat,
        bool   $exportquizbackup,
        bool   $exportcoursebackup,
        string $archivefilenamepattern,
        string $attemptsfilenamepattern,
        ?array $imageoptimize = null,
        ?int   $retentionseconds = null
    ): ?ArchiveJob {
        global $CFG, $USER;

        // Check permissions.
        require_capability('mod/quiz_archiver:create', $this->context);

        // Check if webservice is configured properly.
        if (autoinstall::plugin_is_unconfigured()) {
            throw new \RuntimeException(get_string('error_plugin_is_not_configured', 'quiz_archiver'));
        }

        // Create temporary webservice token.
        if ($CFG->branch > 401 && class_exists('core_external\util')) {
            // Moodle 4.2 and above.
            $wstoken = core_external\util::generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                core_external\util::get_service_by_id($this->config->webservice_id),
                $this->config->webservice_userid,
                context_system::instance(),
                time() + ($this->config->job_timeout_min * 60),
                0
            );
        } else {
            // Moodle 4.1 and below.
            // TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
            $wstoken = external_generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                $this->config->webservice_id,
                $this->config->webservice_userid,
                context_system::instance(),
                time() + ($this->config->job_timeout_min * 60),
                0
            );
        }

        // Get attempt metadata.
        $attempts = $this->report->get_attempts();

        // Prepare task: Export quiz attempts.
        $taskarchivequizattempts = null;
        if ($exportattempts) {
            $taskarchivequizattempts = [
                'attemptids' => array_values(array_keys($attempts)),
                'fetch_metadata' => true,
                'sections' => $reportsections,
                'paper_format' => $paperformat,
                'keep_html_files' => $reportkeephtmlfiles,
                'filename_pattern' => $attemptsfilenamepattern,
                'image_optimize' => $imageoptimize ?? false,
            ];
        }

        // Prepare task: Moodle backups.
        $taskmoodlebackups = null;
        if ($exportquizbackup || $exportcoursebackup) {
            $taskmoodlebackups = [];

            if ($exportquizbackup) {
                $taskmoodlebackups[] = BackupManager::initiate_quiz_backup($this->cm->id, $this->config->webservice_userid);
            }

            if ($exportcoursebackup) {
                $taskmoodlebackups[] = BackupManager::initiate_course_backup($this->course->id, $this->config->webservice_userid);
            }
        }

        // Generate job settings array.
        $jobsettings = [];
        $jobsettings['num_attempts'] = count($attempts);
        $jobsettings['export_attempts'] = $exportattempts;
        if ($exportattempts) {
            foreach ($reportsections as $name => $value) {
                $jobsettings["export_report_section_$name"] = $value;
            }
        }
        $jobsettings['export_quiz_backup'] = $exportquizbackup ? '1' : '0';
        $jobsettings['export_course_backup'] = $exportcoursebackup ? '1' : '0';
        $jobsettings['archive_autodelete'] = $retentionseconds ? '1' : '0';
        if ($retentionseconds) {
            $jobsettings['archive_retention_time'] = util::duration_to_human_readable($retentionseconds);
        }

        // Request archive worker.
        $worker = new RemoteArchiveWorker(rtrim($this->config->worker_url, '/').'/archive', 10, 20);
        try {
            $jobmetadata = $worker->enqueue_archive_job(
                $wstoken,
                $this->course->id,
                $this->cm->id,
                $this->quiz->id,
                [
                    'archive_filename' => ArchiveJob::generate_archive_filename(
                        $this->course,
                        $this->cm,
                        $this->quiz,
                        $archivefilenamepattern
                    ),
                ],
                $taskarchivequizattempts,
                $taskmoodlebackups,
            );

            // Persist job in database.
            $job = ArchiveJob::create(
                $jobmetadata->jobid,
                $this->course->id,
                $this->cm->id,
                $this->quiz->id,
                $USER->id,
                $retentionseconds,
                $wstoken,
                $attempts,
                $jobsettings,
                $jobmetadata->status
            );

            // Link all temporary files to be created, if present.
            if ($taskmoodlebackups) {
                foreach ($taskmoodlebackups as $task) {
                    $job->link_temporary_file($task->pathnamehash);
                }
            }
        } catch (UnexpectedValueException $e) {
            throw new \RuntimeException(get_string('error_worker_connection_failed', 'quiz_archiver'));
        } catch (RuntimeException $e) {
            throw new \RuntimeException(get_string('error_worker_reported_error', 'quiz_archiver', $e->getMessage()));
        } catch (\invalid_parameter_exception $e) {
            throw new \RuntimeException(get_string('error_preparing_job', 'quiz_archiver', $e->getMessage()));
        } catch (Exception $e) {
            throw new \RuntimeException(get_string('error_worker_unknown', 'quiz_archiver')." ".$e->getMessage());
        }

        return $job;
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     *
     * @return moodle_url the URL
     * @throws moodle_exception
     */
    protected function base_url(): moodle_url {
        return new moodle_url('/mod/quiz/report.php', ['id' => $this->cm->id, 'mode' => 'archiver']);
    }

}
