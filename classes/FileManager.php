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
 * This file defines the FileManager class.
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use context_course;
use stored_file;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Manages everything related to file handling via the Moodle File API.
 *
 * Archive files are stored, based on the quiz location, following the following
 * path pattern: <FILEAREA_NAME>/<COURSE_ID>/<CM_ID>/<QUIZ_ID>/<FILENAME>
 */
class FileManager {

    /** @var string Name of the component passed to the Moodle File API */
    const COMPONENT_NAME = 'quiz_archiver';
    /** @var string Name of the filearea all artifact files should be stored in */
    const ARTIFACTS_FILEAREA_NAME = 'artifact';
    /** @var string Name of the filearea to store temporary files in */
    const TEMP_FILEAREA_NAME = 'temp';
    /** @var string Name of the virtual filearea all TSP files are served from */
    const TSP_DATA_FILEAREA_NAME = 'tspdata';
    /** @var string Name of the virtual TSP query file */
    const TSP_DATA_QUERY_FILENAME = 'timestampquery';
    /** @var string Name of the virtual TSP reply file */
    const TSP_DATA_REPLY_FILENAME = 'timestampreply';
    /** @var string Name of the metadata file within artifact archives */
    const ARTIFACT_METADATA_FILE = 'attempts_metadata.csv';
    /** @var int Lifetime of temporary attempt archive export files in seconds */
    const ARTIFACT_EXPORT_TEMPFILE_LIFETIME_SECONDS = 86400;

    /** @var int ID of the course this FileManager is associated with */
    protected int $courseid;
    /** @var int ID of the course module this FileManager is associated with */
    protected int $cmid;
    /** @var int ID of the quiz this FileManager is associated with */
    protected int $quizid;
    /** @var context_course Context of the course this FileManager is associated with */
    protected context_course $context;

    /**
     * Creates a new FileManager instance that is associated with the given quiz,
     * living inside a course module of a course.
     *
     * @param int $courseid ID of the course
     * @param int $cmid ID of the course module
     * @param int $quizid ID of the quiz
     */
    public function __construct(int $courseid, int $cmid, int $quizid) {
        $this->courseid = $courseid;
        $this->cmid = $cmid;
        $this->quizid = $quizid;
        $this->context = context_course::instance($courseid);
    }

    /**
     * Generates a file path based on course, course module, and quiz. If any
     * part is left empty, the respective partial path is returned.
     *
     * @param int $courseid ID of the course
     * @param int $cmid ID of the course module
     * @param int $quizid ID of the quiz
     * @return string Path according to passed IDs
     */
    public static function get_file_path(int $courseid = -1, int $cmid = -1, int $quizid = -1): string {
        $path = '';

        if ($courseid > 0) {
            $path .= "/$courseid";

            if ($cmid > 0) {
                $path .= "/$cmid";

                if ($quizid > 0) {
                    $path .= "/$quizid";
                }
            }
        }

        return $path . '/';
    }

    /**
     * As self::get_file_path but for this FileManager instance.
     *
     * @return string Filepath for this FileManager instance
     */
    protected function get_own_file_path(): string {
        return self::get_file_path($this->courseid, $this->cmid, $this->quizid);
    }

    /**
     * Takes a stored_file and moves it as uploaded archive artifact to the
     * respective area within ARTIFACTS_FILEAREA_NAME.
     *
     * @param stored_file $draftfile Archive artifact file, residing inside
     * 'draft' filearea of the webservice user
     * @param int $jobid Internal ID of the job this artifact belongs to. Used
     * as itemid for the new stored file
     *
     * @return stored_file|null Stored file on success, null on error
     *
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function store_uploaded_artifact(stored_file $draftfile, int $jobid): ?stored_file {
        // Check draftfile.
        if ($draftfile->get_filearea() != "draft" || $draftfile->get_component() != "user") {
            throw new \file_exception('Passed draftfile does not reside inside the draft area of the webservice user. Aborting');
        }

        // Create the final stored archive file from draft file.
        $fs = get_file_storage();
        $artifactfile = $fs->create_file_from_storedfile([
            'contextid'    => $this->context->id,
            'component'    => self::COMPONENT_NAME,
            'filearea'     => self::ARTIFACTS_FILEAREA_NAME,
            'itemid'       => $jobid,
            'filepath'     => $this->get_own_file_path(),
            'filename'     => $draftfile->get_filename(),
            'timecreated'  => $draftfile->get_timecreated(),
            'timemodified' => time(),
        ], $draftfile);

        // Unlink old draft file.
        $draftfile->delete();

        return $artifactfile;
    }

    /**
     * Retrieves a list of all artifact files associated with this FileManager
     *
     * @return stored_file[]
     * @throws \coding_exception
     */
    public function get_stored_artifacts(): array {
        return get_file_storage()->get_area_files(
            $this->context->id,
            self::COMPONENT_NAME,
            self::ARTIFACTS_FILEAREA_NAME,
            false,
            "itemid, filepath, filename",
            false
        );
    }

    /**
     * Retrieves the given file from the draft area
     *
     * @param int $contextid
     * @param int $itemid
     * @param string $filepath
     * @param string $filename
     * @return stored_file|null
     */
    public static function get_draft_file(int $contextid, int $itemid, string $filepath, string $filename): ?stored_file {
        return get_file_storage()->get_file($contextid, 'user', 'draft', $itemid, $filepath, $filename) ?: null;
    }

    /**
     * Calculates the contenthash of a large file chunk-wise.
     *
     * @param stored_file $file File which contents should be hashed
     * @param string $algo Hashing algorithm. Must be one of hash_algos()
     * @return string|null Hexadecimal hash
     */
    public static function hash_file(stored_file $file, string $algo = 'sha256'): ?string {
        // Validate requested hash algorithm.
        if (!array_search($algo, hash_algos())) {
            return null;
        }

        // Calculate file hash chunk-wise.
        $fh = $file->get_content_file_handle(stored_file::FILE_HANDLE_FOPEN);
        $hashctx = hash_init($algo);
        while (!feof($fh)) {
            hash_update($hashctx, fgets($fh, 4096));
        }

        return hash_final($hashctx);
    }

    /**
     * Determines if the given filearea is virtual
     *
     * @param string $filearea Name of a filearea to check
     * @return bool True if the given filearea is virtual
     */
    public static function filearea_is_virtual(string $filearea): bool {
        switch ($filearea) {
            case self::TSP_DATA_FILEAREA_NAME:
                return true;
            default:
                return false;
        }
    }

    /**
     * Send the requested virtual file to the client
     *
     * @param string $filearea Name of a valid virtual filearea
     * @param string $relativepath Relative path to the requested file, depending
     *                             on the virtual filearea
     * @return void
     * @throws \dml_exception
     */
    public function send_virtual_file(string $filearea, string $relativepath): void {
        if (!self::filearea_is_virtual($filearea)) {
            throw new \InvalidArgumentException("Filearea must be virtual");
        }

        switch ($filearea) {
            case self::TSP_DATA_FILEAREA_NAME:
                $this->send_virtual_file_tsp($relativepath);
                break;
            default:
                throw new \InvalidArgumentException("Invalid filearea {$filearea}");
        }
    }

    /**
     * Sends a virtual TSP file to the client
     *
     * @param string $relativepath Relative path to the requested file, following
     *                             the pattern: /<courseid>/<cmid>/<quizid>/<jobid>/<filename>
     * @return void
     * @throws \dml_exception On database error
     */
    protected function send_virtual_file_tsp(string $relativepath): void {
        // Validate relativepath.
        $args = explode('/', $relativepath);
        if (count($args) !== 6) {
            throw new \InvalidArgumentException("Invalid relativepath {$relativepath}");
        }

        $courseid = $args[1];
        $cmid = $args[2];
        $quizid = $args[3];
        $jobid = $args[4];
        $filename = $args[5];

        if (!is_numeric($jobid)) {
            throw new \InvalidArgumentException("Invalid jobid {$jobid}");
        }

        if ($filename !== self::TSP_DATA_QUERY_FILENAME && $filename !== self::TSP_DATA_REPLY_FILENAME) {
            throw new \InvalidArgumentException("Invalid filename {$filename}");
        }

        // Get requested data from DB.
        try {
            $job = ArchiveJob::get_by_id($jobid);
        } catch (\dml_exception $e) {
            throw new \InvalidArgumentException("Job with ID {$jobid} not found");
        }

        if ($courseid != $job->get_courseid() || $cmid != $job->get_cmid() || $quizid != $job->get_quizid()) {
            throw new \InvalidArgumentException("Invalid resource id in {$relativepath}");
        }

        $tspdata = $job->tspmanager()->get_tsp_data();
        if (!$tspdata) {
            throw new \InvalidArgumentException("No TSP data found for job with ID {$jobid}");
        }

        // Get requested file contents.
        switch ($filename) {
            case self::TSP_DATA_QUERY_FILENAME:
                $filecontents = $tspdata->query;
                $downloadfilename = "{$job->get_artifact_checksum()}.tsq";
                break;
            case self::TSP_DATA_REPLY_FILENAME:
                $filecontents = $tspdata->reply;
                $downloadfilename = "{$job->get_artifact_checksum()}.tsr";
                break;
            default:
                throw new \InvalidArgumentException("Invalid filename {$filename}");
        }

        // Send file to the client.
        \core\session\manager::write_close(); // Unlock session during file serving.
        ob_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$downloadfilename.'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, no-transform');
        header('Pragma: no-cache');
        header('Content-Length: '.strlen($filecontents));
        echo $filecontents;
        ob_flush();

        // Do not kill tests.
        if (PHPUNIT_TEST === true) {
            return;
        }

        die;
    }

    /**
     * Extracts the data of a single attempt from a given artifact file into an
     * independent archive. Created files are stored inside the temp filearea and
     * will be automatically deleted after a certain time.
     *
     * @param stored_file $artifactfile Archive artifact file to extract attempt from
     * @param int $jobid ID of the job this artifact belongs to
     * @param int $attemptid ID of the attempt to extract
     * @return ?stored_file New independent attempt archive or null on skip
     * @throws \coding_exception
     * @throws \moodle_exception On error
     */
    public function extract_attempt_data_from_artifact(stored_file $artifactfile, int $jobid, int $attemptid): ?stored_file {
        global $CFG;

        // Prepare.
        $packer = get_file_packer('application/x-gzip');
        // @codingStandardsIgnoreLine
        $workdir = "{$CFG->tempdir}/quiz_archiver/jid{$jobid}_cid{$this->courseid}_cmid{$this->cmid}_qid{$this->quizid}_aid{$attemptid}";

        // Wrap in try-catch to ensure cleanup on exit.
        try {
            // Extract metadata file from artifact and find relevant path information.
            $packer->extract_to_pathname($artifactfile, $workdir, [
                self::ARTIFACT_METADATA_FILE,
            ]);
            $metadata = array_map('str_getcsv', file($workdir."/".self::ARTIFACT_METADATA_FILE));

            if ($metadata[0][0] !== 'attemptid' || $metadata[0][9] !== 'path') {
                // Fail silently for old archives for now.
                if ($metadata[0][9] === 'report_filename') {
                    throw new \invalid_state_exception('Old artifact format is skipped');
                } else {
                    throw new \moodle_exception('Invalid metadata file in artifact archive');
                }
            }

            // Search for attempt path.
            $attemptpath = null;
            foreach ($metadata as $row) {
                if (intval($row[0]) === $attemptid) {
                    $attemptpath = $row[9];
                    break;
                }
            }

            if (!$attemptpath) {
                throw new \moodle_exception('Attempt not found in metadata file');
            }

            // Extract attempt data from artifact.
            // All files must be given explicitly to tgz_packer::extract_to_pathname(). Wildcards
            // are unsupported. Therefore, we list the contents and filter the index. This reduces
            // space and time complexity compared to extracting the whole archive at once.
            $attemptfiles = array_unique(array_values(array_map(
                fn($file): string => $file->pathname,
                array_filter($packer->list_files($artifactfile), function ($file) use ($attemptpath) {
                    return strpos($file->pathname, ltrim($attemptpath, '/')) === 0;
                })
            )));

            if (!$packer->extract_to_pathname($artifactfile, $workdir."/attemptdata", $attemptfiles)) {
                throw new \moodle_exception('Failed to extract attempt data from artifact archive');
            }

            // Create new archive from extracted attempt data into temp filearea.
            $exportexpiry = time() + self::ARTIFACT_EXPORT_TEMPFILE_LIFETIME_SECONDS;
            $exportfile = $packer->archive_to_storage(
                [
                    $workdir."/attemptdata",
                ],
                $this->context->id,
                self::COMPONENT_NAME,
                self::TEMP_FILEAREA_NAME,
                0,
                "/{$exportexpiry}/",
                "attempt_export_jid{$jobid}_cid{$this->courseid}_cmid{$this->cmid}_qid{$this->quizid}_aid{$attemptid}.tar.gz",
            );

            if (!$exportfile) {
                throw new \moodle_exception('Failed to create attempt data archive');
            }

            return $exportfile;
        } catch (\Exception $e) {
            // Ignore skipped archives but always execute cleanup code!
            if (!($e instanceof \invalid_state_exception)) {
                throw $e;
            }
        } finally {
            // Cleanup.
            remove_dir($workdir);
        }

        return null;
    }

    /**
     * Removes all files from the temp filearea that are due to delete.
     *
     * Files inside self::TEMP_FILEAREA_NAME are stored in within a path that
     * indicates the unix timestamp of their expiry. When created, the path is
     * set to the timestamp after which the file can be deleted.
     *
     * @return int Number of deleted files
     * @throws \dml_exception
     */
    public static function cleanup_temp_files(): int {
        global $DB;

        // Prepare.
        $fs = get_file_storage();
        $now = time();
        $numfilesdeleted = 0;

        // Query using raw SQL to get temp files independent of contextid to speed this up a LOT.
        $tempfilerecords = $DB->get_records_sql("
            SELECT id, filepath, filesize FROM {files}
            WHERE component = '".self::COMPONENT_NAME."'
                AND filearea = '".self::TEMP_FILEAREA_NAME."'
                AND filepath != '/';
        ");

        // Delete files that are expired (expiry date in path is smaller than now).
        foreach ($tempfilerecords as $f) {
            $match = preg_match('/^\/(?P<expiry>\d+)\/.*$/m', $f->filepath, $matches);
            if ($match) {
                $expiry = $matches['expiry'];
                if ($expiry < $now) {
                    $fs->get_file_by_id($f->id)->delete();
                    if ($f->filesize > 0) {
                        $numfilesdeleted++;
                    }
                }
            }
        }

        return $numfilesdeleted;
    }

}
