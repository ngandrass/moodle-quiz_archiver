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
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\report_base;
use quiz_archiver\ArchiveJob;
use quiz_archiver\BackupManager;
use quiz_archiver\RemoteArchiveWorker;
use quiz_archiver\Report;
use quiz_archiver\form\archive_quiz_form;
use quiz_archiver\form\job_delete_form;
use quiz_archiver\output\job_overview_table;

defined('MOODLE_INTERNAL') || die();

class quiz_archiver_report extends quiz_default_report {

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
        $tplCtx = [
            'baseurl' => $this->base_url(),
            'jobOverviewTable' => ""
        ];

        // Handle job delete form
        if (optional_param('action', null, PARAM_TEXT) === 'delete_job') {
            $job_delete_form = new job_delete_form();

            if ($job_delete_form->is_cancelled()) {
                redirect($this->base_url());
            }

            if ($job_delete_form->is_submitted()) {
                // Check permissions.
                require_capability('mod/quiz_archiver:archive', $this->context);

                // Execute deletion
                $formdata = $job_delete_form->get_data();
                ArchiveJob::get_by_jobid($formdata->jobid)->delete();
            } else {
                $job_delete_form->display();
                return true;
            }
        }

        // Determine page to display
        if (!quiz_has_questions($quiz->id)) {
            $tplCtx['quizHasNoQuestionsWarning'] = quiz_no_questions_message($quiz, $cm, $this->context);
            echo $OUTPUT->render_from_template('quiz_archiver/overview', $tplCtx);
            return false;
        }

        // Archive quiz form
        $archive_quiz_form = new archive_quiz_form(
            $this->quiz->name,
            sizeof($this->report->get_attempts())
        );
        if ($archive_quiz_form->is_submitted()) {
            $job = null;
            try {
                $formdata = $archive_quiz_form->get_data();
                $job = $this->initiate_archive_job(
                    $formdata->export_attempts,
                    Report::build_report_sections_from_formdata($formdata),
                    $formdata->export_attempts_paper_format,
                    $formdata->export_quiz_backup,
                    $formdata->export_course_backup
                );
                $tplCtx['jobInitiationStatusAlert'] = [
                    "color" => "success",
                    "message" => get_string('job_created_successfully', 'quiz_archiver', $job->get_jobid()),
                    "returnMessage" => get_string('continue'),
                ];
            } catch (RuntimeException $e) {
                $tplCtx['jobInitiationStatusAlert'] = [
                    "color" => "danger",
                    "message" => $e->getMessage(),
                    "returnMessage" => get_string('retry'),
                ];
            }

            // Do not print job overview table if job creation failed
            if ($job == null) unset($tplCtx['jobOverviewTable']);
        } else {
            $tplCtx['jobInitiationForm'] = $archive_quiz_form->render();
        }

        // Job overview table
        if (array_key_exists('jobOverviewTable', $tplCtx)) {
            // Generate table
            $jobtbl = new job_overview_table('job_overview_table', $this->course->id, $this->cm->id, $this->quiz->id);
            $jobtbl->define_baseurl($this->base_url());
            ob_start();
            $jobtbl->out(10, true);
            $jobtbl_html = ob_get_contents();
            ob_end_clean();
            $tplCtx['jobOverviewTable'] = $jobtbl_html;

            // Prepare job metadata for job detail modals
            $tplCtx['jobs'] = array_map(fn($jm): array => [
                'jobid' => $jm['jobid'],
                'json' => json_encode($jm)
            ], ArchiveJob::get_metadata_for_jobs($this->course->id, $this->cm->id, $this->quiz->id));
        }

        // Housekeeping for jobs associated with this quiz
        foreach (ArchiveJob::get_jobs($this->course->id, $this->cm->id, $this->quiz->id) as $job) {
            $job->timeout_if_overdue($this->config->job_timeout_min);
        }

        // Render output
        echo $OUTPUT->render_from_template('quiz_archiver/overview', $tplCtx);

        return true;
    }

    /**
     * Initiates a new archive job for this quiz
     *
     * @param bool $export_attempts Quiz attempts will be archives if true
     * @param array $report_sections Sections to export during attempt report generation
     * @param string $paper_format Paper format to use for attempt report generation
     * @param bool $export_quiz_backup Complete quiz backup will be archived if true
     * @param bool $export_course_backup Complete course backup will be archived if true
     * @return ArchiveJob|null Created ArchiveJob on success
     * @throws coding_exception Handled by Moodle
     * @throws dml_exception Handled by Moodle
     * @throws moodle_exception Handled by Moodle
     * @throws RuntimeException Used to signal a soft failure to calling context
     */
    protected function initiate_archive_job(bool $export_attempts, array $report_sections, string $paper_format, bool $export_quiz_backup, bool $export_course_backup): ?ArchiveJob {
        global $USER;

        // Check permissions.
        require_capability('mod/quiz_archiver:archive', $this->context);

        // Create temporary webservice token
        $wstoken = core_external\util::generate_token(
            EXTERNAL_TOKEN_PERMANENT,
            core_external\util::get_service_by_id($this->config->webservice_id),
            $this->config->webservice_userid,
            context_system::instance(),
            time() + ($this->config->job_timeout_min * 60),
            0
        );

        // Prepare task: Export quiz attempts
        $task_archive_quiz_attempts = null;
        if ($export_attempts) {
            $task_archive_quiz_attempts = [
                'attemptids' => array_values(array_map(fn($obj): int => $obj->attemptid, $this->report->get_attempts())),
                'fetch_metadata' => True,
                'sections' => $report_sections,
                'paper_format' => $paper_format,
            ];
        }

        // Prepare task: Moodle backups
        $task_moodle_backups = null;
        if ($export_quiz_backup || $export_course_backup) {
            $task_moodle_backups = [];

            if ($export_quiz_backup) {
                $task_moodle_backups[] = BackupManager::initiate_quiz_backup($this->cm->id, $this->config->webservice_userid);
            }

            if ($export_course_backup) {
                $task_moodle_backups[] = BackupManager::initiate_course_backup($this->course->id, $this->config->webservice_userid);
            }
        }

        // Generate job settings array
        $job_settings = [];
        $job_settings['num_attempts'] = count($this->report->get_attempts());
        $job_settings['export_attempts'] = $export_attempts;
        if ($export_attempts) {
            foreach ($report_sections as $section_name => $section_value) {
                $job_settings["export_report_section_$section_name"] = $section_value;
            }
        }
        $job_settings['export_quiz_backup'] = $export_quiz_backup ? '1' : '0';
        $job_settings['export_course_backup'] = $export_course_backup ? '1' : '0';

        // Request archive worker
        $worker = new RemoteArchiveWorker(rtrim($this->config->worker_url, '/').'/archive', 10, 20);
        try {
            $job_metadata = $worker->enqueue_archive_job(
                $wstoken,
                $this->course->id,
                $this->cm->id,
                $this->quiz->id,
                $task_archive_quiz_attempts,
                $task_moodle_backups,
            );

            // Persist job in database
            $job = ArchiveJob::create(
                $job_metadata->jobid,
                $this->course->id,
                $this->cm->id,
                $this->quiz->id,
                $USER->id,
                $wstoken,
                $job_settings,
                $job_metadata->status
            );

            // Link all temporary files to be created, if present
            if ($task_moodle_backups) {
                foreach ($task_moodle_backups as $task) {
                    $job->link_temporary_file($task->pathnamehash);
                }
            }
        } catch (UnexpectedValueException $e) {
            throw new \RuntimeException(get_string('error_worker_connection_failed', 'quiz_archiver'));
        } catch (RuntimeException $e) {
            throw new \RuntimeException(get_string('error_worker_reported_error', 'quiz_archiver', $e->getMessage()));
        } catch (Exception $e) {
            throw new \RuntimeException(get_string('error_worker_unknown', 'quiz_archiver')." ".$e->getMessage());
        }

        return $job;
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @return string the URL.
     */
    protected function base_url() {
        return new moodle_url('/mod/quiz/report.php', ['id' => $this->cm->id, 'mode' => 'archiver']);
    }

}
