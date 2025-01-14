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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\output;

use quiz_archiver\ArchiveJob;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir.'/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the job overview table
 */
class job_overview_table extends \table_sql {

    /**
     * Constructor
     *
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param int $courseid ID of the course
     * @param int $cmid ID of the course module
     * @param int $quizid ID of the quiz
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid, int $courseid, int $cmid, int $quizid) {
        parent::__construct($uniqueid);
        $this->define_columns([
            'timecreated',
            'user',
            'jobid',
            'filesize',
            'status',
            'actions',
        ]);

        $this->define_headers([
            get_string('task_starttime', 'admin'),
            get_string('user'),
            get_string('jobid', 'quiz_archiver'),
            get_string('size'),
            get_string('status'),
            '',
        ]);

        $this->set_sql(
            'j.jobid, j.userid, j.timecreated, j.timemodified, j.status, j.statusextras, j.retentiontime, j.artifactfilechecksum, '.
                'f.pathnamehash, f.filesize, u.username',
            '{'.ArchiveJob::JOB_TABLE_NAME.'} j '.
                'JOIN {user} u ON j.userid = u.id '.
                'LEFT JOIN {files} f ON j.artifactfileid = f.id',
            'j.courseid = :courseid AND j.cmid = :cmid AND j.quizid = :quizid',
            [
                'courseid' => $courseid,
                'cmid' => $cmid,
                'quizid' => $quizid,
            ]
        );

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('jobid');
        $this->no_sorting('actions');
        $this->collapsible(false);
    }

    /**
     * Column renderer for the timecreated column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     */
    public function col_timecreated($values) {
        return date('Y-m-d\<\b\r\\>H:i:s', $values->timecreated);
    }

    /**
     * Column renderer for the status column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_status($values) {
        $html = '';
        $s = ArchiveJob::get_status_display_args(
            $values->status,
            $values->statusextras ? json_decode($values->statusextras, true) : null
        );

        $statustooltiphtml = 'data-toggle="tooltip" data-placement="top" title="'.$s['help'].'"';
        $html .= '<span class="badge badge-'.$s['color'].'" '.$statustooltiphtml.'>'.$s['text'].'</span><br/>';

        if (isset($s['statusextras']['progress'])) {
            $html .= '<span title="'.get_string('progress', 'quiz_archiver').'">';
            $html .= '<i class="fa fa-spinner"></i>&nbsp;'.$s['statusextras']['progress'].'%';
            $html .= '</span><br/>';
        }

        $html .= '<small>'.date('H:i:s', $values->timemodified).'</small>';

        return $html;
    }

    /**
     * Column renderer for the user column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \moodle_exception
     */
    public function col_user($values) {
        return '<a href="'.new \moodle_url('/user/profile.php', ['id' => $values->userid]).'">'.$values->username.'</a>';
    }

    /**
     * Column renderer for the filesize column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_filesize($values) {
        return $values->filesize !== null ? display_size($values->filesize) : '';
    }

    /**
     * Column renderer for the actions column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($values) {
        $html = '';

        // Action: Show details.
        // @codingStandardsIgnoreLine
        $html .= '<a href="#" id="job-details-'.$values->jobid.'" class="btn btn-primary mx-1" role="button" title="'.get_string('showdetails', 'admin').'" alt="'.get_string('showdetails', 'admin').'"><i class="fa fa-info-circle"></i></a>';

        // Action: Download.
        if ($values->pathnamehash) {
            $artifactfile = get_file_storage()->get_file_by_hash($values->pathnamehash);
            $artifacturl = \moodle_url::make_pluginfile_url(
                $artifactfile->get_contextid(),
                $artifactfile->get_component(),
                $artifactfile->get_filearea(),
                $artifactfile->get_itemid(),
                $artifactfile->get_filepath(),
                $artifactfile->get_filename(),
                true,
            );

            $downloadtitle = get_string('download').': '.$artifactfile->get_filename().
                             ' ('.get_string('size').': '.display_size($artifactfile->get_filesize()).')';
            // @codingStandardsIgnoreLine
            $html .= '<a href="'.$artifacturl.'" target="_blank" class="btn btn-success mx-1" role="button" title="'.$downloadtitle.'" alt="'.$downloadtitle.'"><i class="fa fa-download"></i></a>';
        } else {
            // @codingStandardsIgnoreLine
            $html .= '<a href="#" target="_blank" class="btn btn-outline-success disabled mx-1" role="button" alt="'.get_string('download').'" disabled aria-disabled="true"><i class="fa fa-download"></i></a>';
        }

        // Action: Delete.
        $deleteurl = new \moodle_url('', [
            'id' => optional_param('id', null, PARAM_INT),
            'mode' => 'archiver',
            'action' => 'delete_job',
            'jobid' => $values->jobid,
        ]);
        // @codingStandardsIgnoreLine
        $html .= '<a href="'.$deleteurl.'" class="btn btn-danger mx-1" role="button" alt="'.get_string('delete', 'moodle').'"><i class="fa fa-times"></i></a>';

        return $html;
    }

}
