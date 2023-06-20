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
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

defined('MOODLE_INTERNAL') || die();

class RemoteArchiveWorker {

    /**
     * @var string URL of the remote Quiz Archive Worker instance
     */
    private string $server_url;

    /**
     * @var int Seconds to wait until a connection can be established before aborting
     */
    private int $connection_timeout;

    /**
     * @var int Seconds to wait for the request to complete before aborting
     */
    private int $request_timeout;

    /**
     * @var int Version of the used API
     */
    public const API_VERSION = 1;

    /**
     * RemoteArchiveWorker constructor
     *
     * @param $server_url string URL of the remote Archive Worker instance
     * @param $connection_timeout int Seconds to wait until a connection can be established before aborting
     * @param $request_timeout int Seconds to wait for the request to complete before aborting
     */
    public function __construct($server_url, $connection_timeout, $request_timeout) {
        $this->server_url = $server_url;
        $this->connection_timeout = $connection_timeout;
        $this->request_timeout = $request_timeout;
    }

    /**
     * Tries to enqueue a new archive job at the archive worker service
     *
     * @param $wstoken str Moodle webervice token to use
     * @param $courseid int Moodle course id
     * @param $cmid int Moodle course module id
     * @param $quizid int Moodle quiz id
     * @param $task_archive_quiz_attempts mixed Array containing payload data for
     * the archive quiz attempts task, or null if it should not be executed
     * @param $task_moodle_course_backup mixed Array containing payload data for
     * the moodle course backup task, or null if it should not be executed
     *
     * @throws \UnexpectedValueException if the communication to the archive worker
     * service or decoding of the response failed
     * @throws \RuntimeException if the archive worker service reported an error
     *
     * @return mixed Job information returned from the archive worker on success
     */
    public function enqueue_archive_job($wstoken, $courseid, $cmid, $quizid, $task_archive_quiz_attempts, $task_moodle_course_backup) {
        # Prepare and execute request
        $request_payload = json_encode([
            "api_version" => self::API_VERSION,
            "moodle_ws_url" => (string) new \moodle_url('/webservice/rest/server.php'),
            "wstoken" => $wstoken,
            "courseid" => $courseid,
            "cmid" => $cmid,
            "quizid" => $quizid,
            "task_archive_quiz_attempts" => $task_archive_quiz_attempts,
            "task_moodle_course_backup" => $task_moodle_course_backup
        ]);

        die(print_r($request_payload));

        $request = $this->prepare_curl_request($request_payload);
        $result = curl_exec($request);
        $http_status = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $data = json_decode($result);

        # Handle errors
        if ($http_status != 200) {
            if ($data === null) {
                throw new \UnexpectedValueException("Decoding of the archive worker response failed. HTTP status code $http_status");
            }
            throw new \RuntimeException($data['error']);
        } else {
            if ($data === null) {
                throw new \UnexpectedValueException("Decoding of the archive worker response failed.");
            }
        }

        # Decoded JSON data containing jobid and job_status returned on success
        return $data;
    }

    /**
     * Prepares a JSON POST-request containing given $json_data to $this->server_url.
     *
     * @param string $json_data Encoded JSON-data to post to the server
     *
     * @return resource Preconfigured CURL resource
     */
    private function prepare_curl_request($json_data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->server_url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connection_timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->request_timeout);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data)
        ]);

        return $curl;
    }

}