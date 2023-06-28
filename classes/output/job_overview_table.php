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
 * This file defines the job overview table renderer
 *
 * @package   quiz_archiver
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\output;

use quiz_archiver\ArchiveJob;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/tablelib.php');


class job_overview_table extends \table_sql {

    /**
     * Constructor
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param int $courseid ID of the course
     * @param int $cmid ID of the course module
     * @param int $quizid ID of the quiz
     */
    function __construct(string $uniqueid, int $courseid, int $cmid, int $quizid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = ['timecreated', 'status', 'jobid', 'actions'];
        $this->define_columns($columns);

        // Define the titles of columns to show in header.
        $headers = ['timecreated', 'status', 'jobid', 'actions'];
        $this->define_headers($headers);

        $this->set_sql(
            'jobs.jobid, jobs.timecreated, jobs.timemodified, jobs.status, files.pathnamehash',
            '{'.ArchiveJob::JOB_TABLE_NAME.'} AS jobs LEFT JOIN {files} as files ON jobs.artifactfileid = files.id',
            'jobs.courseid = :courseid AND jobs.cmid = :cmid AND jobs.quizid = :quizid',
            [
                'courseid' => $courseid,
                'cmid' => $cmid,
                'quizid' => $quizid
            ]
        );

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('jobid');
        $this->no_sorting('actions');
    }

    function col_timecreated($values) {
        return date("Y-m-d H:i:s", $values->timecreated);
    }

    function col_status($values) {
        switch ($values->status) {
            case ArchiveJob::STATUS_RUNNING: return "Running";
            default:
                return $values->status;
        }
    }

    function col_actions($values) {
        $html = '';

        // Action: Download
        if ($values->pathnamehash) {
            $artifactfile = get_file_storage()->get_file_by_hash($values->pathnamehash);
            $artifacturl = \moodle_url::make_pluginfile_url(
                $artifactfile->get_contextid(),
                $artifactfile->get_component(),
                $artifactfile->get_filearea(),
                $artifactfile->get_itemid(),
                $artifactfile->get_filepath(),
                $artifactfile->get_filename(),
                true
            );
            $html .= '<a href="'.$artifacturl.'" target="_blank" class="btn btn-success mx-1" role="button" alt="Download"><i class="fa fa-download"></i></a>';
        } else {
            $html .= '<a href="#" target="_blank" class="btn btn-outline-success disabled mx-1" role="button" alt="Download" disabled aria-disabled="true"><i class="fa fa-download"></i></a>';
        }

        // Action: Delete
        $html .= '<a href="#" class="btn btn-danger mx-1" role="button" alt="Delete"><i class="fa fa-times"></i></a>';

        return $html;
    }

}
