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
        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->report = new Report($this->course, $this->cm, $this->quiz);

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);
        require_capability('quiz/grading:viewstudentnames', $this->context);
        require_capability('quiz/grading:viewidnumber', $this->context);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'archiver');

        // Handle job delete form
        if (optional_param('action', null, PARAM_TEXT) === 'delete_job') {
            $job_delete_form = new job_delete_form();

            if ($job_delete_form->is_cancelled()) {
                redirect($this->base_url());
            }

            if ($job_delete_form->is_submitted()) {
                $formdata = $job_delete_form->get_data();
                ArchiveJob::get_by_jobid($formdata->jobid)->delete();
            } else {
                $job_delete_form->display();
                return true;
            }
        }

        // Determine page to display
        if (!quiz_has_questions($quiz->id)) {
            echo quiz_no_questions_message($quiz, $cm, $this->context);
        } else {
            // Archive quiz form
            echo '<h1>'.get_string('create_quiz_archive', 'quiz_archiver').'</h1>';
            echo '<div class="alert alert-info" role="alert">'.get_string('beta_version_warning', 'quiz_archiver').'</div>';
            echo '<div>';
            $archive_quiz_form = new archive_quiz_form(
                $this->quiz->name,
                sizeof($this->report->get_attempts())
            );
            if ($archive_quiz_form->is_submitted()) {
                $job = null;
                try {
                    $formdata = $archive_quiz_form->get_data();
                    $job = $this->initiate_archive_job($formdata->export_attempts, $formdata->export_quiz_backup, $formdata->export_course_backup);
                    $initiation_status_color = 'success';
                    $initiation_status_msg = get_string('job_created_successfully', 'quiz_archiver', $job->get_jobid());
                    $initiation_status_back_msg = get_string('continue');
                } catch (RuntimeException $e) {
                    $initiation_status_color = 'danger';
                    $initiation_status_msg = $e->getMessage();
                    $initiation_status_back_msg = get_string('retry');
                }
                echo <<<EOD
                    <div class="alert alert-$initiation_status_color" role="alert">
                        $initiation_status_msg
                        <br/>
                        <br/>
                        <a href="{$this->base_url()}">$initiation_status_back_msg</a>
                    </div>
                EOD;

                // Stop printing rest of the page if job creation failed
                if ($job == null) return false;
            } else {
                $archive_quiz_form->display();
            }
            echo '</div>';

            // Housekeeping for jobs associated with this quiz
            foreach (ArchiveJob::get_jobs($this->course->id, $this->cm->id, $this->quiz->id) as $job) {
                $job->timeout_if_overdue($this->config->job_timeout_min);
            }

            // Job overview table
            echo '<h1>'.get_string('job_overview', 'quiz_archiver').'<a href="'.$this->base_url().'" class="small mx-2" alt="'.get_string('refresh', 'moodle').'"><i class="fa fa-rotate-right"></i></a></h1>';
            echo '<div>';
            $jobtbl = new job_overview_table('job_overview_table', $this->course->id, $this->cm->id, $this->quiz->id);
            $jobtbl->define_baseurl($this->base_url());
            $jobtbl->out(10, true);
            echo '</div>';
        }

        return true;
    }

    /**
     * Initiates a new archive job for this quiz
     *
     * @param bool $export_attempts Quiz attempts will be archives if true
     * @param bool $export_quiz_backup Complete quiz backup will be archived if true
     * @param bool $export_course_backup Complete course backup will be archived if true
     * @return ArchiveJob|null Created ArchiveJob on success
     * @throws coding_exception Handled by Moodle
     * @throws dml_exception Handled by Moodle
     * @throws moodle_exception Handled by Moodle
     * @throws RuntimeException Used to signal a soft failure to calling context
     */
    protected function initiate_archive_job(bool $export_attempts, bool $export_quiz_backup, bool $export_course_backup): ?ArchiveJob {
        global $USER;

        // Create temporary webservice token
        $wstoken = external_generate_token(
            EXTERNAL_TOKEN_PERMANENT,
            $this->config->webservice_id,
            $this->config->webservice_userid,
            context_system::instance(),
            time() + ($this->config->job_timeout_min * 60),
            0
        );

        // Prepare task: Export quiz attempts
        $task_archive_quiz_attempts = null;
        if ($export_attempts) {
            $task_archive_quiz_attempts = [
                'attemptids' => array_values(array_map(fn($obj): int => $obj->attemptid, $this->report->get_attempts()))
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
            throw new \RuntimeException(get_string('error_worker_unknown', 'quiz_archiver'). $e->getMessage());
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
