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
 * This file defines the TSPManager class.
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Manages all Time-Stamp Protocol (TSP) related tasks for an ArchiveJob.
 */
class TSPManager {

    /** @var ArchiveJob The associated ArchiveJob this TSPManager acts upon */
    protected ArchiveJob $job;
    /** @var \stdClass Moodle config object for this plugin */
    protected \stdClass $config;

    /** @var string Name of the TSP data table */
    const TSP_TABLE_NAME = 'quiz_archiver_tsp';

    /**
     * Creates a new TSPManager instance.
     *
     * @param ArchiveJob $job The associated ArchiveJob this TSPManager acts upon
     * @throws \dml_exception If the plugin config could not be loaded
     */
    public function __construct(ArchiveJob $job) {
        $this->job = $job;
        $this->config = get_config('quiz_archiver');
    }

    /**
     * Provides a TimeStampProtocolClient instance for this TSPManager.
     *
     * @return TimeStampProtocolClient A fresh TimeStampProtocolClient instance
     */
    protected function get_timestampprotocolclient(): TimeStampProtocolClient {
        return new TimeStampProtocolClient($this->config->tsp_server_url);
    }

    /**
     * Checks if the associated ArchiveJob wants an automatically generated TSP
     * timestamp.
     *
     * @return bool True if the ArchiveJob wants a TSP timestamp to be
     *              automatically generated, false otherwise
     * @throws \dml_exception On database error
     */
    public function wants_tsp_timestamp(): bool {
        if ($this->config->tsp_enable &&
            $this->config->tsp_automatic_signing &&
            $this->has_tsp_timestamp() === false) {
                return true;
        }

        return false;
    }

    /**
     * Checks if the associated ArchiveJob already has a TSP timestamp.
     *
     * @return bool True if the ArchiveJob already has a TSP timestamp
     * @throws \dml_exception On database error
     */
    public function has_tsp_timestamp(): bool {
        global $DB;

        $numtsprecords = $DB->count_records(self::TSP_TABLE_NAME, [
            'jobid' => $this->job->get_id(),
        ]);

        return $numtsprecords > 0;
    }

    /**
     * Returns the TSP data for the associated ArchiveJob.
     *
     * @return ?\stdClass TSP data for the associated ArchiveJob or null if no TSP
     *                data was found
     * @throws \dml_exception On database error
     */
    public function get_tsp_data(): ?\stdClass {
        global $DB;

        $tspdata = $DB->get_record(self::TSP_TABLE_NAME, [
            'jobid' => $this->job->get_id(),
        ]);

        return ($tspdata !== false) ? (object) [
            'server' => $tspdata->server,
            'timecreated' => $tspdata->timecreated,
            'query' => $tspdata->timestampquery,
            'reply' => $tspdata->timestampreply,
        ] : null;
    }

    /**
     * Deletes all TSP data for the associated ArchiveJob.
     *
     * @return void
     * @throws \dml_exception On database error
     */
    public function delete_tsp_data(): void {
        global $DB;

        $DB->delete_records(self::TSP_TABLE_NAME, [
            'jobid' => $this->job->get_id(),
        ]);
    }

    /**
     * Issues a TSP timestamp for the associated ArchiveJobs artifact
     *
     * @return void
     * @throws \dml_exception On database error
     * @throws \Exception On TSP error
     * @throws \RuntimeException If the associated ArchiveJob has no valid artifact
     */
    public function timestamp(): void {
        global $DB;

        // Get artifact checksum.
        $artifactchecksum = $this->job->get_artifact_checksum();
        if ($artifactchecksum === null) {
            throw new \RuntimeException(get_string('archive_signing_failed_no_artifact', 'quiz_archiver'));
        }

        // Check if TSP signing globally is enabled.
        if (!$this->config->tsp_enable) {
            throw new \Exception(get_string('archive_signing_failed_tsp_disabled', 'quiz_archiver'));
        }

        // Issue TSP timestamp.
        $tspclient = $this->get_timestampprotocolclient();
        $tspdata = $tspclient->sign($artifactchecksum);

        // Store TSP data.
        $DB->insert_record(self::TSP_TABLE_NAME, [
            'jobid' => $this->job->get_id(),
            'timecreated' => time(),
            'server' => $tspclient->get_serverurl(),
            'timestampquery' => $tspdata['query'],
            'timestampreply' => $tspdata['reply'],
        ]);
    }

}
