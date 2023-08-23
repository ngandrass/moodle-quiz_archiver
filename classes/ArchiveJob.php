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
    /** @var int Unix timestamp of job creation */
    protected int $timecreated;
    /** @var string The webservice token that is allowed to write to this job via API */
    protected string $wstoken;

    /** @var string Name of the job status table */
    const JOB_TABLE_NAME = 'quiz_archiver_jobs';
    /** @var string Name of the table to store temporary file associations */
    const FILES_TABLE_NAME = 'quiz_archiver_files';
    /** @var string Name of the table to store archive job settings */
    const JOB_SETTINGS_TABLE_NAME = 'quiz_archiver_job_settings';

    // Job status values
    const STATUS_UNKNOWN = 'UNKNOWN';
    const STATUS_UNINITIALIZED = 'UNINITIALIZED';
    const STATUS_AWAITING_PROCESSING = 'AWAITING_PROCESSING';
    const STATUS_RUNNING = 'RUNNING';
    const STATUS_FINISHED = 'FINISHED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_TIMEOUT = 'TIMEOUT';

    /**
     * Creates a new ArchiveJob. This does **NOT** enqueue the job anywhere.
     *
     * @param int $id ID of the job inside the database
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $course_id ID of the course this job is associated with
     * @param int $cm_id ID of the course module this job is associated with
     * @param int $quiz_id ID of the quiz this job is associated with
     * @param int $user_id ID of the user that owns this job
     * @param int $timecreated Unix timestamp of job creation
     * @param string $wstoken The webservice token that is allowed to write to this job via API
     */
    protected function __construct(int $id, string $jobid, int $course_id, int $cm_id, int $quiz_id, int $user_id, int $timecreated, string $wstoken) {
        $this->id = $id;
        $this->jobid = $jobid;
        $this->course_id = $course_id;
        $this->cm_id = $cm_id;
        $this->quiz_id = $quiz_id;
        $this->user_id = $user_id;
        $this->timecreated = $timecreated;
        $this->wstoken = $wstoken;
    }

    /**
     * Creates a new job inside the database
     *
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $course_id ID of the course this job is associated with
     * @param int $cm_id ID of the course module this job is associated with
     * @param int $quiz_id ID of the quiz this job is associated with
     * @param int $user_id ID of the user that initiated this job
     * @param array $settings Map of settings to store for this job and display in the report interface
     * @param string $status (optional) Initial status of the job. Default to STATUS_UNKNOWN
     * @param string $wstoken The webservice token that is allowed to write to this job via API
     * @return ArchiveJob
     * @throws \dml_exception On database error
     * @throws \moodle_exception If the job already exists inside the database
     */
    public static function create(string $jobid, int $course_id, int $cm_id, int $quiz_id, int $user_id, string $wstoken, array $settings, string $status = self::STATUS_UNKNOWN): ArchiveJob {
        global $DB;

        // Do not re-created jobs!
        if (self::exists_in_db($jobid)) {
            throw new \moodle_exception('encryption_keyalreadyexists');
        }

        // Create database entry and return ArchiveJob object to represent it
        $now = time();
        $id = $DB->insert_record(self::JOB_TABLE_NAME, [
            'jobid' => $jobid,
            'courseid' => $course_id,
            'cmid' => $cm_id,
            'quizid' => $quiz_id,
            'userid' => $user_id,
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
            'wstoken' => $wstoken
        ]);

        // Store job settings
        $DB->insert_records(self::JOB_SETTINGS_TABLE_NAME, array_map(function($key, $value) use ($id): array {
            return [
                'jobid' => $id,
                'key' => strval($key),
                'value' => $value === null ? null : strval($value)
            ];
        }, array_keys($settings), $settings));

        return new ArchiveJob($id, $jobid, $course_id, $cm_id, $quiz_id, $user_id, $now, $wstoken);
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
            $jobdata->userid,
            $jobdata->timecreated,
            $jobdata->wstoken
        );
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
     * @return array<ArchiveJob>
     * @throws \dml_exception
     */
    public static function get_jobs(int $course_id, int $cm_id, int $quiz_id): array {
        global $DB;
        $records = $DB->get_records(self::JOB_TABLE_NAME, [
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
            $dbdata->userid,
            $dbdata->timecreated,
            $dbdata->wstoken
        ), $records);
    }

    /**
     * Generates an array containing all jobs that match the given selector
     * containing: jobid, status, timecreated, timemodified.
     *
     * This is the preferred way to access status of ALL jobs, instead of using
     * ArchiveJob::get_jobs() and call get_status() on each job individually!
     *
     * @param int $course_id
     * @param int $cm_id
     * @param int $quiz_id
     * @return array
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function get_metadata_for_jobs(int $course_id, int $cm_id, int $quiz_id): array {
        global $DB;
        $records = $DB->get_records_sql(
            'SELECT '.
            '    j.*, '.
            '    u.firstname AS userfirstname, u.lastname AS userlastname, u.username, '.
            '    c.fullname AS coursename, '.
            '    q.name as quizname '.
            'FROM {quiz_archiver_jobs} AS j '.
            '    LEFT JOIN {user} u ON j.userid = u.id '.
            '    LEFT JOIN {course} c ON j.courseid = c.id '.
            '    LEFT JOIN {quiz} q ON j.quizid = q.id '.
            'WHERE '.
            '    j.courseid = :courseid AND '.
            '    j.cmid = :cmid AND '.
            '    j.quizid = :quizid ',
            [
                'courseid' => $course_id,
                'cmid' => $cm_id,
                'quizid' => $quiz_id
            ]
        );

        return array_values(array_map(function($j): array {
            // Get artifactfile metadata if available
            $artifactfile_metadata = null;
            if ($j->artifactfileid) {
                $artifactfile = get_file_storage()->get_file_by_id($j->artifactfileid);
                if ($artifactfile) {
                    $artifactfileurl = \moodle_url::make_pluginfile_url(
                        $artifactfile->get_contextid(),
                        $artifactfile->get_component(),
                        $artifactfile->get_filearea(),
                        $artifactfile->get_itemid(),
                        $artifactfile->get_filepath(),
                        $artifactfile->get_filename(),
                        true
                    );

                    $artifactfile_metadata = [
                        'name' => $artifactfile->get_filename(),
                        'downloadurl' => $artifactfileurl->out(),
                        'size' => $artifactfile->get_filesize(),
                        'size_human' => display_size($artifactfile->get_filesize()),
                        'checksum' => $j->artifactfilechecksum
                    ];
                }
            }

            // Build job metadata array
            return [
                'id' => $j->id,
                'jobid' => $j->jobid,
                'status' => $j->status,
                'status_display_args' => self::get_status_display_args($j->status),
                'timecreated' => $j->timecreated,
                'timemodified' => $j->timemodified,
                'user' => [
                    'id' => $j->userid,
                    'firstname' => $j->userfirstname,
                    'lastname' => $j->userlastname,
                    'username' => $j->username
                ],
                'course' => [
                    'id' => $j->courseid,
                    'name' => $j->coursename
                ],
                'quiz' => [
                    'id' => $j->quizid,
                    'cmid' => $j->cmid,
                    'name' => $j->quizname
                ],
                'artifactfile' => $artifactfile_metadata,
                'settings' => self::convert_archive_settings_for_display(
                    (new self($j->id, '', -1, -1, -1, -1, -1, ''))->get_settings()
                ),
            ];
        }, $records));
    }

    /**
     * Returns the archive settings memorized for this job
     *
     * @return array Key value pairs of archive settings
     * @throws \dml_exception
     */
    public function get_settings(): array {
        global $DB;
        return array_reduce(
            $DB->get_records(self::JOB_SETTINGS_TABLE_NAME, ['jobid' => $this->id]),
            function($res, $item): array {
                $res[$item->key] = $item->value;
                return $res;
            },
            []
        );
    }

    /**
     * Deletes this job and all associated data
     *
     * @return void
     * @throws \dml_exception
     */
    public function delete(): void {
        global $DB;

        // Delete additional data
        $this->delete_webservice_token();
        $this->delete_temporary_files();
        if ($artifact = $this->get_artifact()) {
            $artifact->delete();
        }
        $DB->delete_records(self::JOB_SETTINGS_TABLE_NAME, ['jobid' => $this->id]);

        // Delete job from DB
        $DB->delete_records(self::JOB_TABLE_NAME, ['id' => $this->id]);

        // Invalidate self
        $this->id = -1;
        $this->jobid = '';
        $this->course_id = -1;
        $this->cm_id = -1;
        $this->quiz_id = -1;
        $this->user_id = -1;
        $this->wstoken = '';
    }

    /**
     * Marks this job as timeouted if it is overdue
     *
     * @param int $timeout_min Minutes until a job is considered as timeouted after creation
     * @return bool True if the job was overdue
     * @throws \dml_exception
     */
    public function timeout_if_overdue(int $timeout_min): bool {
        if ($this->is_complete()) return false;

        // Check if job is overdue
        if ($this->timecreated < (time() - ($timeout_min * 60))) {
            $this->set_status(self::STATUS_TIMEOUT);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if the given webservice token is allowed to read from this job
     * via the Moodle API
     *
     * @param string $wstoken Webservice token to test access rights for
     * @return bool True if $wstoken permits read operations to this job
     */
    public function has_read_access(string $wstoken): bool {
        return $this->has_write_access($wstoken);
    }

    /**
     * Determines if the given webservice token is allowed to write to this job
     * via the Moodle API
     *
     * @param string $wstoken Webservice token to test access rights for
     * @return bool True if $wstoken permits write operations to this job
     */
    public function has_write_access(string $wstoken): bool {
        return $wstoken === $this->wstoken;
    }

    /**
     * Determines if the job as reached one of its final states and is considered
     * as completed
     *
     * @return bool True if job is completed, regardless of success or error
     */
    public function is_complete(): bool {
        switch ($this->get_status()) {
            case self::STATUS_FINISHED:
            case self::STATUS_FAILED:
            case self::STATUS_TIMEOUT:
                return true;
            default:
                return false;
        }
    }

    /**
     * @return string UUID of the job, as assigned by the archive worker
     */
    public function get_jobid(): string {
        return $this->jobid;
    }

    /**
     * @return int ID of the course this job is associated with
     */
    public function get_course_id(): int {
        return $this->course_id;
    }

    /**
     * @return int ID of the course module this job is associated with
     */
    public function get_cm_id(): int {
        return $this->cm_id;
    }

    /**
     * @return int ID of the quiz this job is associated with
     */
    public function get_quiz_id(): int {
        return $this->quiz_id;
    }

    /**
     * @return int ID of the user that owns this job
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * Updates the status of this ArchiveJob
     *
     * @param string $status New job status
     * @param bool $delete_wstoken_if_completed If true, delete associated wstoken
     * if this status change completed the job
     * @param bool $delete_temporary_files_if_completed If true, all linked
     * temporary files will be deleted if this status change completed the job
     * @return void
     * @throws \dml_exception on failure
     */
    public function set_status(string $status, bool $delete_wstoken_if_completed = true, $delete_temporary_files_if_completed = true) {
        global $DB;
        $DB->update_record(self::JOB_TABLE_NAME, (object) [
            'id' => $this->id,
            'status' => $status,
            'timemodified' => time()
        ]);

        if ($this->is_complete()) {
            if ($delete_wstoken_if_completed) {
                $this->delete_webservice_token();
            }

            if ($delete_temporary_files_if_completed) {
                $this->delete_temporary_files();
            }
        }
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
     * @return array Status of this job, translated for display
     * @throws \coding_exception
     */
    public static function get_status_display_args(string $status): array {
        switch ($status) {
            case ArchiveJob::STATUS_UNKNOWN:
                return ['color' => 'warning', 'text' => get_string('job_status_UNKNOWN', 'quiz_archiver')];
            case ArchiveJob::STATUS_UNINITIALIZED:
                return ['color' => 'secondary', 'text' => get_string('job_status_UNINITIALIZED', 'quiz_archiver')];
            case ArchiveJob::STATUS_AWAITING_PROCESSING:
                return ['color' => 'secondary', 'text' => get_string('job_status_AWAITING_PROCESSING', 'quiz_archiver')];
            case ArchiveJob::STATUS_RUNNING:
                return ['color' => 'primary', 'text' => get_string('job_status_RUNNING', 'quiz_archiver')];
            case ArchiveJob::STATUS_FINISHED:
                return ['color' => 'success', 'text' => get_string('job_status_FINISHED', 'quiz_archiver')];
            case ArchiveJob::STATUS_FAILED:
                return ['color' => 'danger', 'text' => get_string('job_status_FAILED', 'quiz_archiver')];
            case ArchiveJob::STATUS_TIMEOUT:
                return ['color' => 'danger', 'text' => get_string('job_status_TIMEOUT', 'quiz_archiver')];
            default:
                return ['color' => 'light', 'text' => $status];
        }
    }

    /**
     * Exports a archive settings array for display rendering via mustache
     *
     * @param array $settings Archive settings to convert
     * @return array List of title and value pairs based on given $settings
     * @throws \coding_exception
     */
    public static function convert_archive_settings_for_display(array $settings): array {
        $ret = [];

        foreach($settings as $key => $value) {
            $ret[] = [
                'title' => get_string($key, 'quiz_archiver'),
                'value' => $value,
                'color' => $value ? 'primary' : 'secondary'
            ];
        }

        return $ret;
    }

    /**
     * Retrieves the artifact file if present
     *
     * @return \stored_file|null Artifact file if present, else null
     */
    public function get_artifact(): ?\stored_file {
        global $DB;
        try {
            $file = $DB->get_record_sql(
                'SELECT pathnamehash FROM {files} AS files JOIN {'.self::JOB_TABLE_NAME.'} AS jobs ON files.id = jobs.artifactfileid WHERE jobs.id = :id',
                ['id' => $this->id]
            );

            if (!$file) return null;

            return get_file_storage()->get_file_by_hash($file->pathnamehash);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Links the moodle file with the given ID to this job as the artifact
     *
     * @param int $file_id ID of the file from {files} to link to this
     * job as the artifact
     * @param string $checksum Hash of the artifact file contents to store in
     * the database
     *
     * @return bool True on success
     * @throws \dml_exception
     */
    public function link_artifact(int $file_id, string $checksum): bool {
        global $DB;

        if ($file_id < 1) return false;

        $DB->update_record(self::JOB_TABLE_NAME, (object) [
            'id' => $this->id,
            'artifactfileid' => $file_id,
            'artifactfilechecksum' => $checksum,
            'timemodified' => time()
        ]);

        return true;
    }

    /**
     * Links a temporary file by its future $pathnamehash to this job. Temporary
     * files will be deleted once this job completes.
     *
     * @param string $pathnamehash Pathnamehash of the file
     * @return void
     */
    public function link_temporary_file(string $pathnamehash): void {
        global $DB;

        $DB->insert_record(self::FILES_TABLE_NAME, [
            'jobid' => $this->id,
            'pathnamehash' => $pathnamehash
        ]);
    }

    /**
     * Deletes all temporary files that are associated with this job
     *
     * @return int Number of deleted files
     * @throws \dml_exception
     */
    public function delete_temporary_files(): int {
        global $DB;
        $fs = get_file_storage();

        $num_deleted_files = 0;
        $tempfiles = $DB->get_records(self::FILES_TABLE_NAME, ['jobid' => $this->id]);
        foreach ($tempfiles as $tempfile) {
            $f = $fs->get_file_by_hash($tempfile->pathnamehash);
            if ($f) {
                $f->delete();
                $DB->delete_records(self::FILES_TABLE_NAME, ['jobid' => $this->id, 'pathnamehash' => $tempfile->pathnamehash]);
                $num_deleted_files++;
            }
        }

        return $num_deleted_files;
    }

    /**
     * Removes / invalidates the webservice token that is associated with this ArchiveJob
     *
     * @return void
     * @throws dml_exception
     */
    public function delete_webservice_token(): void {
        global $DB;
        $DB->delete_records('external_tokens', array('token' => $this->wstoken, 'tokentype' => EXTERNAL_TOKEN_PERMANENT));
    }

}
