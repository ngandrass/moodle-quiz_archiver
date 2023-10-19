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
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use context_course;
use stored_file;

defined('MOODLE_INTERNAL') || die();

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
    /** @var string Name of the virtual filearea all TSP files are served from */
    const TSP_DATA_FILEAREA_NAME = 'tspdata';
    /** @var string Name of the virtual TSP query file */
    const TSP_DATA_QUERY_FILENAME = 'timestampquery';
    /** @var string Name of the virtual TSP reply file */
    const TSP_DATA_REPLY_FILENAME = 'timestampreply';

    /** @var int ID of the course this FileManager is associated with */
    protected int $course_id;
    /** @var int ID of the course module this FileManager is associated with */
    protected int $cm_id;
    /** @var int ID of the quiz this FileManager is associated with */
    protected int $quiz_id;
    /** @var context_course Context of the course this FileManager is associated with */
    protected context_course $context;

    /**
     * Creates a new FileManager instance that is associated with the given quiz,
     * living inside a course module of a course.
     *
     * @param int $course_id ID of the course
     * @param int $cm_id ID of the course module
     * @param int $quiz_id ID of the quiz
     */
    public function __construct(int $course_id, int $cm_id, int $quiz_id) {
        $this->course_id = $course_id;
        $this->cm_id = $cm_id;
        $this->quiz_id = $quiz_id;
        $this->context = context_course::instance($course_id);
    }

    /**
     * Generates a file path based on course, course module, and quiz. If any
     * part is left empty, the respective partial path is returned.
     *
     * @param int $course_id ID of the course
     * @param int $cm_id ID of the course module
     * @param int $quiz_id ID of the quiz
     * @return string Path according to passed IDs
     */
    public static function get_file_path(int $course_id = -1, int $cm_id = -1, int $quiz_id = -1): string {
        $path = '';

        if ($course_id > 0) {
            $path .= "/$course_id";

            if ($cm_id > 0) {
                $path .= "/$cm_id";

                if ($quiz_id > 0) {
                    $path .= "/$quiz_id";
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
    protected function get_own_file_path() {
        return self::get_file_path($this->course_id, $this->cm_id, $this->quiz_id);
    }

    /**
     * Takes a stored_file and moves it as uploaded archive artifact to the
     * respective area within ARTIFACTS_FILEAREA_NAME.
     *
     * @param stored_file $draftfile Archive artifact file, residing inside
     * 'draft' filearea of the webservice user
     *
     * @return stored_file|null Stored file on success, null on error
     *
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function store_uploaded_artifact(stored_file $draftfile): ?\stored_file {
        // Check draftfile
        if ($draftfile->get_filearea() != "draft" || $draftfile->get_component() != "user") {
            throw new \file_exception('Passed draftfile does not reside inside the draft area of the webservice user. Aborting');
        }

        // Create the final stored archive file from draft file
        $fs = get_file_storage();
        $artifactfile = $fs->create_file_from_storedfile([
            'contextid'    => $this->context->id,
            'component'    => self::COMPONENT_NAME,
            'filearea'     => self::ARTIFACTS_FILEAREA_NAME,
            'itemid'       => 0,
            'filepath'     => $this->get_own_file_path(),
            'filename'     => $draftfile->get_filename(),
            'timecreated'  => $draftfile->get_timecreated(),
            'timemodified' => time(),
        ], $draftfile);

        // Unlink old draft file
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
        // Validate requested hash algorithm
        if (!array_search($algo, hash_algos())) {
            return null;
        }

        // Calculate file hash chunk-wise
        $fh = $file->get_content_file_handle(stored_file::FILE_HANDLE_FOPEN);
        $hash_ctx = hash_init($algo);
        while (!feof($fh)) {
            hash_update($hash_ctx, fgets($fh, 4096));
        }

        return hash_final($hash_ctx);
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
        // Validate relativepath
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

        if ($filename !== FileManager::TSP_DATA_QUERY_FILENAME && $filename !== FileManager::TSP_DATA_REPLY_FILENAME) {
            throw new \InvalidArgumentException("Invalid filename {$filename}");
        }

        // Get requested data from DB
        $job = ArchiveJob::get_by_id($jobid);
        if (!$job) {
            throw new \InvalidArgumentException("Job with ID {$jobid} not found");
        }

        if ($courseid != $job->get_course_id() || $cmid != $job->get_cm_id() || $quizid != $job->get_quiz_id()) {
            throw new \InvalidArgumentException("Invalid resource id in {$relativepath}");
        }

        $tspdata = $job->TSPManager()->get_tsp_data();
        if (!$tspdata) {
            throw new \InvalidArgumentException("No TSP data found for job with ID {$jobid}");
        }

        // Get requested file contents
        switch ($filename) {
            case FileManager::TSP_DATA_QUERY_FILENAME:
                $filecontents = $tspdata->query;
                $downloadfilename = "{$job->get_artifact_checksum()}.tsq";
                break;
            case FileManager::TSP_DATA_REPLY_FILENAME:
                $filecontents = $tspdata->reply;
                $downloadfilename = "{$job->get_artifact_checksum()}.tsr";
                break;
            default:
                throw new \InvalidArgumentException("Invalid filename {$filename}");
        }

        // Send file to the client
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
        die;
    }

}
