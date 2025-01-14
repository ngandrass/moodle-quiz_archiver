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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use quiz_archiver\local\util;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore



/**
 * A single quiz archive job
 */
class ArchiveJob {

    /** @var int Database id of this job */
    protected int $id;
    /** @var string UUID of the job, as assigned by the archive worker */
    protected string $jobid;
    /** @var int ID of the course this job is associated with */
    protected int $courseid;
    /** @var int ID of the course module this job is associated with */
    protected int $cmid;
    /** @var int ID of the quiz this job is associated with */
    protected int $quizid;
    /** @var int ID of the user that owns this job */
    protected int $userid;
    /** @var int Unix timestamp of job creation */
    protected int $timecreated;
    /** @var int|null Unix timestamp after which this jobs artifacts will be deleted automatically. Null indicates no deletion.*/
    protected ?int $retentiontime;
    /** @var string The webservice token that is allowed to write to this job via API */
    protected string $wstoken;

    /** @var ?TSPManager A Time-Stamp Protocol (TSP) manager associated with this class */
    protected ?TSPManager $tspmanager;

    /** @var string Name of the job status table */
    const JOB_TABLE_NAME = 'quiz_archiver_jobs';
    /** @var string Name of the table to store temporary file associations */
    const FILES_TABLE_NAME = 'quiz_archiver_files';
    /** @var string Name of the table to store archive job settings */
    const JOB_SETTINGS_TABLE_NAME = 'quiz_archiver_job_settings';
    /** @var string Name of the table to store attemptids and userids */
    const ATTEMPTS_TABLE_NAME = 'quiz_archiver_attempts';

    // Job status values.
    /** @var string Job status: Unknown */
    const STATUS_UNKNOWN = 'UNKNOWN';
    /** @var string Job status: Uninitialized */
    const STATUS_UNINITIALIZED = 'UNINITIALIZED';
    /** @var string Job status: Awaiting processing */
    const STATUS_AWAITING_PROCESSING = 'AWAITING_PROCESSING';
    /** @var string Job status: Running */
    const STATUS_RUNNING = 'RUNNING';
    /** @var string Job status: Waiting for backup */
    const STATUS_WAITING_FOR_BACKUP = 'WAITING_FOR_BACKUP';
    /** @var string Job status: Finalizing */
    const STATUS_FINALIZING = 'FINALIZING';
    /** @var string Job status: Finished */
    const STATUS_FINISHED = 'FINISHED';
    /** @var string Job status: Failed */
    const STATUS_FAILED = 'FAILED';
    /** @var string Job status: Timeout */
    const STATUS_TIMEOUT = 'TIMEOUT';
    /** @var string Job status: Deleted */
    const STATUS_DELETED = 'DELETED';

    /** @var string[] Valid variables for archive filename patterns */
    public const ARCHIVE_FILENAME_PATTERN_VARIABLES = [
        'courseid',
        'coursename',
        'courseshortname',
        'cmid',
        'quizid',
        'quizname',
        'date',
        'time',
        'timestamp',
    ];

    /** @var string[] Valid variables for attempt report filename patterns */
    public const ATTEMPT_FILENAME_PATTERN_VARIABLES = [
        'courseid',
        'coursename',
        'courseshortname',
        'cmid',
        'quizid',
        'quizname',
        'attemptid',
        'username',
        'firstname',
        'lastname',
        'idnumber',
        'timestart',
        'timefinish',
        'date',
        'time',
        'timestamp',
    ];

    /** @var string[] Characters that are forbidden in a filename pattern */
    public const FILENAME_FORBIDDEN_CHARACTERS = ["\\", "/", ".", ":", ";", "*", "?", "!", "\"", "<", ">", "|", "\0"];

    /**
     * Creates a new ArchiveJob. This does **NOT** enqueue the job anywhere.
     *
     * @param int $id ID of the job inside the database
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $courseid ID of the course this job is associated with
     * @param int $cmid ID of the course module this job is associated with
     * @param int $quizid ID of the quiz this job is associated with
     * @param int $userid ID of the user that owns this job
     * @param int $timecreated Unix timestamp of job creation
     * @param ?int $retentiontime Unix timestamp after which this jobs
     * artifacts will be deleted automatically. Null indicates no deletion.
     * @param string $wstoken The webservice token that is allowed to write to this job via API
     */
    protected function __construct(
        int    $id,
        string $jobid,
        int    $courseid,
        int    $cmid,
        int    $quizid,
        int    $userid,
        int    $timecreated,
        ?int   $retentiontime,
        string $wstoken
    ) {
        $this->id = $id;
        $this->jobid = $jobid;
        $this->courseid = $courseid;
        $this->cmid = $cmid;
        $this->quizid = $quizid;
        $this->userid = $userid;
        $this->timecreated = $timecreated;
        $this->retentiontime = $retentiontime;
        $this->wstoken = $wstoken;
        $this->tspmanager = null; // Lazy initialization.
    }

    /**
     * Provides access to the TSPManager for this ArchiveJob
     *
     * @return TSPManager The TSPManager for this ArchiveJob
     * @throws \dml_exception If the plugin config could not be loaded
     */
    public function tspmanager(): TSPManager {
        if ($this->tspmanager == null) {
            $this->tspmanager = new TSPManager($this);
        }

        return $this->tspmanager;
    }

    /**
     * Creates a new job inside the database
     *
     * @param string $jobid UUID of the job, as assigned by the archive worker
     * @param int $courseid ID of the course this job is associated with
     * @param int $cmid ID of the course module this job is associated with
     * @param int $quizid ID of the quiz this job is associated with
     * @param int $userid ID of the user that initiated this job
     * @param ?int $retentionseconds Number of seconds to retain this jobs
     * artifact after job creation. Null indicates no deletion.
     * @param string $wstoken The webservice token that is allowed to write to this job via API
     * @param array $attempts List of quiz attempts to archive, each consisting of an attemptid and a userid
     * @param array $settings Map of settings to store for this job and display in the report interface
     * @param string $status (optional) Initial status of the job. Default to STATUS_UNKNOWN
     *
     * @return ArchiveJob The newly created job
     * @throws \dml_exception On database error
     * @throws \moodle_exception If the job already exists inside the database
     */
    public static function create(
        string $jobid,
        int    $courseid,
        int    $cmid,
        int    $quizid,
        int    $userid,
        ?int   $retentionseconds,
        string $wstoken,
        array  $attempts,
        array  $settings,
        string $status = self::STATUS_UNKNOWN
    ): ArchiveJob {
        global $DB;

        // Do not re-created jobs!
        if (self::exists_in_db($jobid)) {
            throw new \moodle_exception('encryption_keyalreadyexists');
        }

        // Create database entry and return ArchiveJob object to represent it.
        $now = time();
        $retentiontime = $retentionseconds ? $now + $retentionseconds : null;
        $id = $DB->insert_record(self::JOB_TABLE_NAME, [
            'jobid' => $jobid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'userid' => $userid,
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
            'retentiontime' => $retentiontime,
            'wstoken' => $wstoken,
        ]);

        // Store job settings.
        $DB->insert_records(self::JOB_SETTINGS_TABLE_NAME, array_map(function($key, $value) use ($id): array {
            return [
                'jobid' => $id,
                'settingkey' => strval($key),
                'settingvalue' => $value === null ? null : strval($value),
            ];
        }, array_keys($settings), $settings));

        // Remember attempts associated with this archive.
        $DB->insert_records(self::ATTEMPTS_TABLE_NAME, array_map(function($data) use ($id): array {
            return [
                'jobid' => $id,
                'userid' => $data->userid,
                'attemptid' => $data->attemptid,
            ];
        }, $attempts));

        return new ArchiveJob($id, $jobid, $courseid, $cmid, $quizid, $userid, $now, $retentiontime, $wstoken);
    }

    /**
     * Tries to retrieve an ArchiveJob by its internal ID
     *
     * @param int $id Internal ID of the job to query for
     * @return ArchiveJob if found
     * @throws \dml_exception if not found
     */
    public static function get_by_id(int $id): ArchiveJob {
        global $DB;
        $jobdata = $DB->get_record(self::JOB_TABLE_NAME, ['id' => $id], '*', MUST_EXIST);
        return new ArchiveJob(
            $jobdata->id,
            $jobdata->jobid,
            $jobdata->courseid,
            $jobdata->cmid,
            $jobdata->quizid,
            $jobdata->userid,
            $jobdata->timecreated,
            $jobdata->retentiontime,
            $jobdata->wstoken
        );
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
            $jobdata->retentiontime,
            $jobdata->wstoken
        );
    }

    /**
     * Determines whether a job with the given job UUID exists inside the database
     *
     * @param string $jobid UUID to query for
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
     * @param int $courseid ID of the course to query for
     * @param int $cmid ID of the course module to query for
     * @param int $quizid ID of the quiz to query for
     * @return array<ArchiveJob> List of ArchiveJobs that match the given selectors
     * @throws \dml_exception if the database query fails
     */
    public static function get_jobs(int $courseid, int $cmid, int $quizid): array {
        global $DB;
        $records = $DB->get_records(self::JOB_TABLE_NAME, [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
        ]);

        return array_map(fn($dbdata): ArchiveJob => new ArchiveJob(
            $dbdata->id,
            $dbdata->jobid,
            $dbdata->courseid,
            $dbdata->cmid,
            $dbdata->quizid,
            $dbdata->userid,
            $dbdata->timecreated,
            $dbdata->retentiontime,
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
     * @param int $courseid
     * @param int $cmid
     * @param int $quizid
     * @return array
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function get_metadata_for_jobs(int $courseid, int $cmid, int $quizid): array {
        global $DB;
        $records = $DB->get_records_sql(
            'SELECT '.
            '    j.*, '.
            '    tsp.timecreated as tsp_timecreated, tsp.server AS tsp_server,'.
            '    u.firstname AS userfirstname, u.lastname AS userlastname, u.username, '.
            '    c.fullname AS coursename, '.
            '    q.name as quizname '.
            'FROM {quiz_archiver_jobs} j '.
            '    LEFT JOIN {quiz_archiver_tsp} tsp ON j.id = tsp.jobid '.
            '    LEFT JOIN {user} u ON j.userid = u.id '.
            '    LEFT JOIN {course} c ON j.courseid = c.id '.
            '    LEFT JOIN {quiz} q ON j.quizid = q.id '.
            'WHERE '.
            '    j.courseid = :courseid AND '.
            '    j.cmid = :cmid AND '.
            '    j.quizid = :quizid ',
            [
                'courseid' => $courseid,
                'cmid' => $cmid,
                'quizid' => $quizid,
            ]
        );

        return array_values(array_map(function($j): array {
            // Get artifactfile metadata if available.
            $artifactfilemetadata = null;
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

                    $artifactfilemetadata = [
                        'name' => $artifactfile->get_filename(),
                        'downloadurl' => $artifactfileurl->out(),
                        'size' => $artifactfile->get_filesize(),
                        'size_human' => display_size($artifactfile->get_filesize()),
                        'checksum' => $j->artifactfilechecksum,
                    ];
                }
            }

            // Prepate TSP data.
            $tspdata = null;
            if ($j->tsp_timecreated && $j->artifactfileid) {
                $tspdata = [
                    'timecreated' => $j->tsp_timecreated,
                    'server' => $j->tsp_server,
                    'queryfiledownloadurl' => \moodle_url::make_pluginfile_url(
                        $artifactfile->get_contextid(),
                        $artifactfile->get_component(),
                        FileManager::TSP_DATA_FILEAREA_NAME,
                        0,
                        $artifactfile->get_filepath()."{$j->id}/",
                        FileManager::TSP_DATA_QUERY_FILENAME,
                        true
                    )->out(),
                    'replyfiledownloadurl' => \moodle_url::make_pluginfile_url(
                        $artifactfile->get_contextid(),
                        $artifactfile->get_component(),
                        FileManager::TSP_DATA_FILEAREA_NAME,
                        0,
                        $artifactfile->get_filepath()."{$j->id}/",
                        FileManager::TSP_DATA_REPLY_FILENAME,
                        true
                    )->out(),
                ];
            }

            // Calculate autodelete metadata.
            if ($j->retentiontime !== null) {
                if ($j->status == self::STATUS_DELETED) {
                    $autodeletestr = get_string('archive_deleted', 'quiz_archiver');
                } else if ($j->retentiontime <= time()) {
                    $autodeletestr = get_string('archive_autodelete_now', 'quiz_archiver');
                } else {
                    $autodeletestr = get_string(
                        'archive_autodelete_in',
                        'quiz_archiver',
                        util::duration_to_human_readable($j->retentiontime - time())
                    );
                    $autodeletestr .= ' ('.userdate($j->retentiontime, get_string('strftimedatetime', 'core_langconfig')).')';
                }
            } else {
                $autodeletestr = get_string('archive_autodelete_disabled', 'quiz_archiver');
            }

            // Build job metadata array.
            return [
                'id' => $j->id,
                'jobid' => $j->jobid,
                'status' => $j->status,
                'status_display_args' => self::get_status_display_args(
                    $j->status,
                    $j->statusextras ? json_decode($j->statusextras, true) : null
                ),
                'timecreated' => $j->timecreated,
                'timemodified' => $j->timemodified,
                'retentiontime' => $j->retentiontime,
                'autodelete' => $j->retentiontime !== null,
                'autodelete_done' => $j->status == self::STATUS_DELETED ? true : null,
                'autodelete_str' => $autodeletestr,
                'user' => [
                    'id' => $j->userid,
                    'firstname' => $j->userfirstname,
                    'lastname' => $j->userlastname,
                    'username' => $j->username,
                ],
                'course' => [
                    'id' => $j->courseid,
                    'name' => $j->coursename,
                ],
                'quiz' => [
                    'id' => $j->quizid,
                    'cmid' => $j->cmid,
                    'name' => $j->quizname,
                ],
                'artifactfile' => $artifactfilemetadata,
                'tsp' => $tspdata,
                'settings' => self::convert_archive_settings_for_display(
                    (new self($j->id, '', -1, -1, -1, -1, -1, null, ''))->get_settings()
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
                $res[$item->settingkey] = $item->settingvalue;
                return $res;
            },
            []
        );
    }

    /**
     * Scans all jobs for expired artifacts and deletes them.
     *
     * @return int Number of deleted artifacts
     * @throws \dml_exception
     */
    public static function delete_expired_artifacts(): int {
        global $DB;

        $records = $DB->get_records_select(
            self::JOB_TABLE_NAME,
            "artifactfileid IS NOT NULL AND retentiontime IS NOT NULL AND retentiontime < :now",
            ['now' => time()],
            'id'
        );

        $numfilesdeleted = 0;
        foreach ($records as $record) {
            $job = self::get_by_id($record->id);
            $job->delete_artifact();
            $numfilesdeleted++;
        }

        return $numfilesdeleted;
    }

    /**
     * Deletes this job and all associated data
     *
     * @return void
     * @throws \dml_exception
     */
    public function delete(): void {
        global $DB;

        // Delete additional data.
        $this->delete_webservice_token();
        $this->delete_temporary_files();
        $this->delete_artifact();

        $DB->delete_records(self::JOB_SETTINGS_TABLE_NAME, ['jobid' => $this->id]);
        $DB->delete_records(self::ATTEMPTS_TABLE_NAME, ['jobid' => $this->id]);

        // Delete job from DB.
        $DB->delete_records(self::JOB_TABLE_NAME, ['id' => $this->id]);

        // Invalidate self.
        $this->id = -1;
        $this->jobid = '';
        $this->courseid = -1;
        $this->cmid = -1;
        $this->quizid = -1;
        $this->userid = -1;
        $this->wstoken = '';
    }

    /**
     * Marks this job as timeouted if it is overdue
     *
     * @param int $timeoutmin Minutes until a job is considered as timeouted after creation
     * @return bool True if the job was overdue
     * @throws \dml_exception
     */
    public function timeout_if_overdue(int $timeoutmin): bool {
        if ($this->is_complete()) {
            return false;
        }

        // Check if job is overdue.
        if ($this->timecreated < (time() - ($timeoutmin * 60))) {
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
            case self::STATUS_DELETED:
                return true;
            default:
                return false;
        }
    }

    /**
     * Returns the internal database ID of this job
     *
     * @return int Internal database ID of this job
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Returns the UUID of this job
     *
     * @return string UUID of the job, as assigned by the archive worker
     */
    public function get_jobid(): string {
        return $this->jobid;
    }

    /**
     * Returns the ID of the course this job is associated with
     *
     * @return int ID of the course this job is associated with
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Returns the ID of the course module this job is associated with
     *
     * @return int ID of the course module this job is associated with
     */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /**
     * Returns the ID of the quiz this job is associated with
     *
     * @return int ID of the quiz this job is associated with
     */
    public function get_quizid(): int {
        return $this->quizid;
    }

    /**
     * Returns the ID of the user that owns this job
     *
     * @return int ID of the user that owns this job
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Returns the retention time of this job, if enabled
     *
     * @return int|null Unix timestamp after which this jobs artifact will be
     * deleted automatically. Null indicates no automatic deletion.
     */
    public function get_retentiontime(): ?int {
        return $this->retentiontime;
    }

    /**
     * Updates the status of this ArchiveJob
     *
     * @param string $status New job status
     * @param array|null $statusextras Optional additional status information
     * @param bool $deletewstokenifcompleted If true, delete associated wstoken
     * if this status change completed the job
     * @param bool $deletetemporaryfilesifcompleted If true, all linked
     * temporary files will be deleted if this status change completed the job
     * @return void
     * @throws \dml_exception on failure
     */
    public function set_status(
        string $status,
        ?array $statusextras = null,
        bool   $deletewstokenifcompleted = true,
        bool   $deletetemporaryfilesifcompleted = true
    ): void {
        global $DB;

        // Prepare statusextras data.
        $statusextrasjson = null;
        if ($statusextras !== null) {
            $statusextrasjson = json_encode($statusextras);
        }

        // Update status in database.
        $DB->update_record(self::JOB_TABLE_NAME, (object) [
            'id' => $this->id,
            'status' => $status,
            'statusextras' => $statusextrasjson,
            'timemodified' => time(),
        ]);

        // Handle post status change actions.
        if ($this->is_complete()) {
            if ($deletewstokenifcompleted) {
                $this->delete_webservice_token();
            }

            if ($deletetemporaryfilesifcompleted) {
                $this->delete_temporary_files();
            }
        }
    }

    /**
     * Retrieves the status of this job
     *
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
     * Retrieves the statusextras of this job
     *
     * @return array|null Additional status information of this job, if available
     */
    public function get_statusextras(): ?array {
        global $DB;
        try {
            $statusextras = $DB->get_field(self::JOB_TABLE_NAME, 'statusextras', ['jobid' => $this->jobid], MUST_EXIST);
            if ($statusextras) {
                return json_decode($statusextras, true);
            } else {
                return null;
            }
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * Returns the status indicator display arguments based on the given job status
     *
     * @param string $status JOB_STATUS value to convert
     * @param array|null $statusextras Additional status information to display
     * @return array Status of this job, translated for display
     * @throws \coding_exception
     */
    public static function get_status_display_args(string $status, ?array $statusextras = null): array {
        // Translate status to display text and color.
        switch ($status) {
            case self::STATUS_UNKNOWN:
                $res = [
                    'color' => 'warning',
                    'text' => get_string('job_status_UNKNOWN', 'quiz_archiver'),
                    'help' => get_string('job_status_UNKNOWN_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_UNINITIALIZED:
                $res = [
                    'color' => 'secondary',
                    'text' => get_string('job_status_UNINITIALIZED', 'quiz_archiver'),
                    'help' => get_string('job_status_UNINITIALIZED_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_AWAITING_PROCESSING:
                $res = [
                    'color' => 'secondary',
                    'text' => get_string('job_status_AWAITING_PROCESSING', 'quiz_archiver'),
                    'help' => get_string('job_status_AWAITING_PROCESSING_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_RUNNING:
                $res = [
                    'color' => 'primary',
                    'text' => get_string('job_status_RUNNING', 'quiz_archiver'),
                    'help' => get_string('job_status_RUNNING_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_WAITING_FOR_BACKUP:
                $res = [
                    'color' => 'info',
                    'text' => get_string('job_status_WAITING_FOR_BACKUP', 'quiz_archiver'),
                    'help' => get_string('job_status_WAITING_FOR_BACKUP_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_FINALIZING:
                $res = [
                    'color' => 'info',
                    'text' => get_string('job_status_FINALIZING', 'quiz_archiver'),
                    'help' => get_string('job_status_FINALIZING_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_FINISHED:
                $res = [
                    'color' => 'success',
                    'text' => get_string('job_status_FINISHED', 'quiz_archiver'),
                    'help' => get_string('job_status_FINISHED_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_FAILED:
                $res = [
                    'color' => 'danger',
                    'text' => get_string('job_status_FAILED', 'quiz_archiver'),
                    'help' => get_string('job_status_FAILED_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_TIMEOUT:
                $res = [
                    'color' => 'danger',
                    'text' => get_string('job_status_TIMEOUT', 'quiz_archiver'),
                    'help' => get_string('job_status_TIMEOUT_help', 'quiz_archiver'),
                ];
                break;
            case self::STATUS_DELETED:
                $res = [
                    'color' => 'secondary',
                    'text' => get_string('job_status_DELETED', 'quiz_archiver'),
                    'help' => get_string('job_status_DELETED_help', 'quiz_archiver'),
                ];
                break;
            default:
                $res = ['color' => 'light', 'text' => $status, 'help' => $status];
                break;
        }

        // Add additional status information if present.
        $res['statusextras'] = $statusextras ?? [];

        return $res;
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

        foreach ($settings as $key => $value) {
            $ret[] = [
                'title' => get_string($key, 'quiz_archiver'),
                'value' => $value,
                'color' => $value ? 'primary' : 'secondary',
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
                'SELECT pathnamehash '.
                'FROM {files} files '.
                'JOIN {'.self::JOB_TABLE_NAME.'} jobs ON files.id = jobs.artifactfileid '.
                'WHERE jobs.id = :id',
                ['id' => $this->id]
            );

            if (!$file) {
                return null;
            }

            return get_file_storage()->get_file_by_hash($file->pathnamehash);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retrieves the artifact file checksum
     *
     * @return string|null Artifact file SHA256 checksum if present, else null
     */
    public function get_artifact_checksum(): ?string {
        global $DB;
        try {
            $checksum = (string) $DB->get_field(self::JOB_TABLE_NAME, 'artifactfilechecksum', ['id' => $this->id], MUST_EXIST);
            if (empty($checksum)) {
                return null;
            } else {
                return $checksum;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Determines if this job has a linked artifactfile
     *
     * @return bool True if this job has a linked artifactfile
     */
    public function has_artifact(): bool {
        return $this->get_artifact() !== null;
    }

    /**
     * Links the moodle file with the given ID to this job as the artifact
     *
     * @param int $fileid ID of the file from {files} to link to this
     * job as the artifact
     * @param string $checksum Hash of the artifact file contents to store in
     * the database
     *
     * @return bool True on success
     * @throws \dml_exception
     */
    public function link_artifact(int $fileid, string $checksum): bool {
        global $DB;

        if ($fileid < 1) {
            return false;
        }

        $DB->update_record(self::JOB_TABLE_NAME, (object) [
            'id' => $this->id,
            'artifactfileid' => $fileid,
            'artifactfilechecksum' => $checksum,
            'timemodified' => time(),
        ]);

        return true;
    }

    /**
     * Deletes the artifact file associated with this job
     *
     * @return void
     * @throws \dml_exception
     */
    public function delete_artifact(): void {
        global $DB;

        if ($artifact = $this->get_artifact()) {
            $artifact->delete();
            $this->tspmanager()->delete_tsp_data();

            $DB->update_record(self::JOB_TABLE_NAME, (object) [
                'id' => $this->id,
                'artifactfileid' => null,
                'artifactfilechecksum' => null,
                'timemodified' => time(),
            ]);

            $this->set_status(self::STATUS_DELETED);
        }
    }

    /**
     * Determines if the artifact file is scheduled for automatic deletion
     *
     * @return bool True if the artifact file is scheduled for automatic deletion
     */
    public function is_autodelete_enabled(): bool {
        return $this->retentiontime !== null;
    }

    /**
     * Links a temporary file by its future $pathnamehash to this job. Temporary
     * files will be deleted once this job completes.
     *
     * @param string $pathnamehash Pathnamehash of the file
     * @return void
     * @throws \dml_exception
     */
    public function link_temporary_file(string $pathnamehash): void {
        global $DB;

        $DB->insert_record(self::FILES_TABLE_NAME, [
            'jobid' => $this->id,
            'pathnamehash' => $pathnamehash,
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

        $numdeletedfiles = 0;
        $tempfiles = $DB->get_records(self::FILES_TABLE_NAME, ['jobid' => $this->id]);
        foreach ($tempfiles as $tempfile) {
            $f = $fs->get_file_by_hash($tempfile->pathnamehash);
            if ($f) {
                $f->delete();
                $DB->delete_records(self::FILES_TABLE_NAME, ['jobid' => $this->id, 'pathnamehash' => $tempfile->pathnamehash]);
                $numdeletedfiles++;
            }
        }

        return $numdeletedfiles;
    }

    /**
     * Retrieves all temporary files associated with this job
     *
     * @return array of stored_file objects
     * @throws \dml_exception on database error
     */
    public function get_temporary_files(): array {
        global $DB;
        $fs = get_file_storage();

        $fileentries = $DB->get_records(self::FILES_TABLE_NAME, ['jobid' => $this->id]);
        $files = [];

        foreach ($fileentries as $fileentry) {
            $f = $fs->get_file_by_hash($fileentry->pathnamehash);
            if ($f !== false) {
                $files[$f->get_id()] = $f;
            }
        }

        return $files;
    }

    /**
     * Removes / invalidates the webservice token that is associated with this ArchiveJob
     *
     * @return void
     * @throws \dml_exception
     */
    public function delete_webservice_token(): void {
        global $DB;
        $DB->delete_records('external_tokens', ['token' => $this->wstoken, 'tokentype' => EXTERNAL_TOKEN_PERMANENT]);
    }

    /**
     * Determines if the given filename pattern contains only allowed variables
     * and no orphaned dollar signs
     *
     * @param string $pattern Filename pattern to test
     * @param array $allowedvariables List of allowed variables
     * @return bool True if the pattern is valid
     */
    protected static function is_valid_filename_pattern(string $pattern, array $allowedvariables): bool {
        // Check for minimal length.
        if (strlen($pattern) < 1) {
            return false;
        }

        // Check for variables.
        $residue = preg_replace('/\$\{\s*('.implode('|', $allowedvariables).')\s*\}/m', '', $pattern);
        if (strpos($residue, '$') !== false) {
            return false;
        }

        // Check for forbidden characters.
        foreach (self::FILENAME_FORBIDDEN_CHARACTERS as $char) {
            if (strpos($pattern, $char) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if the given filename pattern is valid for an archive and does
     * not contain any invalid variables
     *
     * @param string $pattern Filename pattern to test
     * @return bool True if the pattern is valid for an archive filename
     */
    public static function is_valid_archive_filename_pattern(string $pattern): bool {
        return self::is_valid_filename_pattern($pattern, self::ARCHIVE_FILENAME_PATTERN_VARIABLES);
    }

    /**
     * Determines if the given filename pattern is valid for an attempt report
     * file and does not contain any invalid variables
     *
     * @param string $pattern Filename pattern to test
     * @return bool True if the pattern is valid for an attempt report filename
     */
    public static function is_valid_attempt_filename_pattern(string $pattern): bool {
        return self::is_valid_filename_pattern($pattern, self::ATTEMPT_FILENAME_PATTERN_VARIABLES);
    }

    /**
     * Sanitizes the given filename by removing all forbidden characters
     *
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    protected static function sanitize_filename(string $filename): string {
        $res = $filename;
        foreach (self::FILENAME_FORBIDDEN_CHARACTERS as $char) {
            $res = str_replace($char, '', $res);
        }

        return trim($res);
    }

    /**
     * Generates an archive filename based on the given pattern and context information
     *
     * @param mixed $course Course object
     * @param mixed $cm Course module object
     * @param mixed $quiz Quiz object
     * @param string $pattern Filename pattern to use
     * @return string Archive filename
     * @throws \invalid_parameter_exception If the pattern is invalid
     * @throws \coding_exception
     */
    public static function generate_archive_filename($course, $cm, $quiz, string $pattern): string {
        // Validate pattern.
        if (!self::is_valid_archive_filename_pattern($pattern)) {
            throw new \invalid_parameter_exception(get_string('error_invalid_archive_filename_pattern', 'quiz_archiver'));
        }

        // Prepare data.
        $data = [
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'quizid' => $quiz->id,
            'coursename' => $course->fullname,
            'courseshortname' => $course->shortname,
            'quizname' => $quiz->name,
            'timestamp' => time(),
            'date' => date('Y-m-d'),
            'time' => date('H-i-s'),
        ];

        // Substitute variables.
        $filename = $pattern;
        foreach ($data as $key => $value) {
            $filename = preg_replace('/\$\{\s*'.$key.'\s*\}/m', $value, $filename);
        }

        return self::sanitize_filename($filename);
    }

    /**
     * Generates an attempt filename based on the given pattern and context information
     *
     * @param mixed $course Course object
     * @param mixed $cm Course module object
     * @param mixed $quiz Quiz object
     * @param int $attemptid ID of the attempt
     * @param string $pattern Filename pattern to use
     * @return string Attempt report filename
     * @throws \dml_exception If the attempt or user could not be found in the database
     * @throws \invalid_parameter_exception If the pattern is invalid
     * @throws \coding_exception
     */
    public static function generate_attempt_filename($course, $cm, $quiz, int $attemptid, string $pattern): string {
        global $DB;

        // Validate pattern.
        if (!self::is_valid_attempt_filename_pattern($pattern)) {
            throw new \invalid_parameter_exception(get_string('error_invalid_attempt_filename_pattern', 'quiz_archiver'));
        }

        // Prepare data.
        // We query the DB directly to prevent a full question_attempt object from being created.
        $attemptinfo = $DB->get_record('quiz_attempts', ['id' => $attemptid], '*', MUST_EXIST);
        $userinfo = $DB->get_record('user', ['id' => $attemptinfo->userid], '*', MUST_EXIST);
        $data = [
            'courseid' => $course->id,
            'cmid' => $cm->id,
            'quizid' => $quiz->id,
            'attemptid' => $attemptid,
            'coursename' => $course->fullname,
            'courseshortname' => $course->shortname,
            'quizname' => $quiz->name,
            'timestamp' => time(),
            'date' => date('Y-m-d'),
            'time' => date('H-i-s'),
            'timestart' => $attemptinfo->timestart,
            'timefinish' => $attemptinfo->timefinish,
            'username' => $userinfo->username,
            'firstname' => $userinfo->firstname,
            'lastname' => $userinfo->lastname,
            'idnumber' => $userinfo->idnumber,
        ];

        // Substitute variables.
        $filename = $pattern;
        foreach ($data as $key => $value) {
            $filename = preg_replace('/\$\{\s*'.$key.'\s*\}/m', $value, $filename);
        }

        return self::sanitize_filename($filename);
    }

}
