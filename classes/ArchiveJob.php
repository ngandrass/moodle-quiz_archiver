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
 * This file defines the ArchiveJob class.
 *
 * @package   quiz_archiver
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

defined('MOODLE_INTERNAL') || die();

class ArchiveJob {

    /** @var int Database id of this job */
    protected int $id;
    /** @var string UUID of the job, as assigned by the archive worker */
    protected string $jobid;
    /** @var int ID of the course this job is associated with */
    protected int $course_id;
    /** @var int ID of the course module this job is associated with */
    protected int $cm_id;
    /** @var int ID of the quiz this job is associated with */
    protected int $quiz_id;
    /** @var int ID of the user that owns this job */
    protected int $user_id;

    /** @var string Name of the job status table */
    const JOB_TABLE_NAME = 'quiz_report_archiver_jobs';

    // Job status values
    const STATUS_UNKNOWN = 'UNKNOWN';
    const STATUS_UNINITIALIZED = 'UNINITIALIZED';
    const STATUS_AWAITING_PROCESSING = 'AWAITING_PROCESSING';
    const STATUS_RUNNING = 'RUNNING';
    const STATUS_FINISHED = 'FINISHED';
    const STATUS_FAILED = 'FAILED';

    /**
     * Creates a new ArchiveJob. This does **NOT** enqueue the job anywhere.
     *
     * @param int $id ID of the job inside the database
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $course_id ID of the course this job is associated with
     * @param int $cm_id ID of the course module this job is associated with
     * @param int $quiz_id ID of the quiz this job is associated with
     */
    protected function __construct(int $id, string $jobid, int $course_id, int $cm_id, int $quiz_id, int $user_id) {
        $this->id = $id;
        $this->jobid = $jobid;
        $this->course_id = $course_id;
        $this->cm_id = $cm_id;
        $this->quiz_id = $quiz_id;
        $this->user_id = $user_id;
    }

    /**
     * Creates a new job inside the database
     *
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $course_id ID of the course this job is associated with
     * @param int $cm_id ID of the course module this job is associated with
     * @param int $quiz_id ID of the quiz this job is associated with
     * @param int $user_id ID of the user that initiated this job
     * @param string $status (optional) Initial status of the job. Default to STATUS_UNKNOWN
     * @return ArchiveJob
     * @throws \dml_exception On database error
     * @throws \moodle_exception If the job already exists inside the database
     */
    public static function create(string $jobid, int $course_id, int $cm_id, int $quiz_id, int $user_id, string $status = self::STATUS_UNKNOWN): ArchiveJob {
        global $DB;

        // Do not re-created jobs!
        if (self::exists_in_db($jobid)) {
            throw new \moodle_exception('encryption_keyalreadyexists');
        }

        // Create database entry and return ArchiveJob object to represent it
        $id = $DB->insert_record(self::JOB_TABLE_NAME, [
            'jobid' => $jobid,
            'courseid' => $course_id,
            'cmid' => $cm_id,
            'quizid' => $quiz_id,
            'userid' => $user_id,
            'status' => $status,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        return new ArchiveJob($id, $jobid, $course_id, $cm_id, $quiz_id, $user_id);
    }

    /**
     * Tries to retrieve an ArchiveJob by its UUID.
     *
     * @param string $jobid UUID to query for
     * @return ArchiveJob if found
     * @throws \dml_exception if not found
     */
    public static function get_by_jobid(string $jobid): ArchiveJob {
        global $DB;
        $jobdata = $DB->get_record(self::JOB_TABLE_NAME, ['jobid' => $jobid], '*', MUST_EXIST);
        return new ArchiveJob(
            $jobdata->id,
            $jobdata->jobid,
            $jobdata->courseid,
            $jobdata->cmid,
            $jobdata->quizid,
            $jobdata->userid
        );
    }

    /**
     * Updates the status of this ArchiveJob
     *
     * @param string $status New job status
     * @return void
     * @throws \dml_exception on failure
     */
    public function set_status(string $status) {
        global $DB;
        $DB->update_record(self::JOB_TABLE_NAME, (object) [
            'id' => $this->id,
            'status' => $status,
            'timemodified' => time()
        ]);
    }

    /**
     * @return string Status of this job
     */
    public function get_status(): string {
        global $DB;
        try {
            return $DB->get_field(self::JOB_TABLE_NAME, 'status', ['jobid' => $this->jobid], MUST_EXIST);
        } catch (\dml_exception $e) {
            return self::STATUS_UNKNOWN;
        }
    }

    /**
     * Determines whether a job with the given job UUID exists inside the database
     *
     * @return bool True if this job is persisted inside the database
     */
    protected static function exists_in_db(string $jobid): bool {
        global $DB;
        try {
            return $DB->get_field(self::JOB_TABLE_NAME, 'id', ['jobid' => $jobid], MUST_EXIST) > 0;
        } catch (\dml_exception $e) {
            return false;
        }
    }

    /**
     * Returns all ArchiveJobs that match given selectors.
     *
     * @param int $course_id
     * @param int $cm_id
     * @param int $quiz_id
     * @return array
     * @throws \dml_exception
     */
    public static function get_jobs(int $course_id, int $cm_id, int $quiz_id): array {
        global $DB;
        $records =  $DB->get_records(self::JOB_TABLE_NAME, [
            'courseid' => $course_id,
            'cmid' => $cm_id,
            'quizid' => $quiz_id
        ]);

        return array_map(fn($dbdata): ArchiveJob => new ArchiveJob(
            $dbdata->id,
            $dbdata->jobid,
            $dbdata->courseid,
            $dbdata->cmid,
            $dbdata->quizid,
            $dbdata->userid
        ), $records);
    }

}
