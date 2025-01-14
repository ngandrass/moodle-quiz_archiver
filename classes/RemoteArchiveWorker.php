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
 * This file defines the RemoteArchiveWorker class.
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use curl;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * A client to interface the remote archive worker service
 */
class RemoteArchiveWorker {

    /** @var string URL of the remote Quiz Archive Worker instance */
    protected string $serverurl;
    /** @var int Seconds to wait until a connection can be established before aborting */
    protected int $connectiontimeout;
    /** @var int Seconds to wait for the request to complete before aborting */
    protected int $requesttimeout;
    /** @var \stdClass Moodle config object for this plugin */
    protected \stdClass $config;

    /** @var int Version of the used API */
    public const API_VERSION = 6;

    /**
     * RemoteArchiveWorker constructor
     *
     * @param string $serverurl URL of the remote Archive Worker instance
     * @param int $connectiontimeout Seconds to wait until a connection can be established before aborting
     * @param int $requesttimeout Seconds to wait for the request to complete before aborting
     * @throws \dml_exception If retrieving of the plugin config failed
     */
    public function __construct(string $serverurl, int $connectiontimeout, int $requesttimeout) {
        $this->serverurl = $serverurl;
        $this->connectiontimeout = $connectiontimeout;
        $this->requesttimeout = $requesttimeout;
        $this->config = get_config('quiz_archiver');
    }

    /**
     * Tries to enqueue a new archive job at the archive worker service
     *
     * @param string $wstoken Moodle webervice token to use
     * @param int $courseid Moodle course id
     * @param int $cmid Moodle course module id
     * @param int $quizid Moodle quiz id
     * @param array $joboptions Associative array containing global job options
     * @param mixed $taskarchivequizattempts Array containing payload data for
     * the archive quiz attempts task, or null if it should not be executed
     * @param mixed $taskmoodlebackups Array containing payload data for
     * the moodle backups task, or null if it should not be executed
     *
     * @return mixed Job information returned from the archive worker on success
     * @throws \UnexpectedValueException if the communication to the archive worker
     * service or decoding of the response failed
     * @throws \RuntimeException if the archive worker service reported an error
     */
    public function enqueue_archive_job(
        string $wstoken,
        int    $courseid,
        int    $cmid,
        int    $quizid,
        array  $joboptions,
               $taskarchivequizattempts,
               $taskmoodlebackups
    ) {
        global $CFG;
        $moodleurlbase = rtrim($this->config->internal_wwwroot ?: $CFG->wwwroot, '/');

        // Prepare request payload.
        $payload = json_encode(array_merge(
            [
                "api_version" => self::API_VERSION,
                "moodle_base_url" => $moodleurlbase,
                "moodle_ws_url" => $moodleurlbase.'/webservice/rest/server.php',
                "moodle_upload_url" => $moodleurlbase.'/webservice/upload.php',
                "wstoken" => $wstoken,
                "courseid" => $courseid,
                "cmid" => $cmid,
                "quizid" => $quizid,
                "task_archive_quiz_attempts" => $taskarchivequizattempts,
                "task_moodle_backups" => $taskmoodlebackups,
            ],
            $joboptions
        ));

        // Execute request.
        // Moodle curl wrapper automatically closes curl handle after requests. No need to call curl_close() manually.
        // Ignore URL filter since we require custom ports and the URL is only configurable by admins.
        $c = new curl(['ignoresecurity' => true]);
        $result = $c->post($this->serverurl, $payload, [
            'CURLOPT_CONNECTTIMEOUT' => $this->connectiontimeout,
            'CURLOPT_TIMEOUT' => $this->requesttimeout,
            'CURLOPT_HTTPHEADER' => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($payload),
            ],
        ]);

        $httpstatus = $c->get_info()['http_code'];  // Invalid PHPDoc in Moodle curl wrapper. Array returned instead of string.
        $data = json_decode($result);

        // Handle errors.
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreStart
        if ($httpstatus != 200) {
            if ($data === null) {
                throw new \UnexpectedValueException("Decoding of the archive worker response failed. HTTP status code $httpstatus");
            }
            throw new \RuntimeException($data->error);
        } else {
            if ($data === null) {
                throw new \UnexpectedValueException("Decoding of the archive worker response failed.");
            }
        }

        // Decoded JSON data containing jobid and job_status returned on success.
        return $data;
        // @codingStandardsIgnoreLine
        // @codeCoverageIgnoreEnd
    }

}
