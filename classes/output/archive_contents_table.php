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
 * This file defines the archive contents table
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\output;

use core\exception\moodle_exception;
use quiz_archiver\ArchiveJob;
use quiz_archiver\Report;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir.'/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the archive contents table
 */
class archive_contents_table extends \table_sql {

    /**
     * Constructor
     *
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param int $jobid Internal ID of the archive job to display data for
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid, int $jobid) {
        parent::__construct($uniqueid);
        $this->define_columns([
            'id',
            'username',
            'attemptid',
            'numattachments',
        ]);

        $this->define_headers([
            get_string('id', 'quiz_archiver'),
            get_string('user'),
            get_string('attempt', 'quiz_archiver'),
            get_string('attachments', 'quiz_archiver'),
        ]);

        $this->set_sql(
            'am.id, am.userid, am.attemptid, am.numattachments, '.
                'j.courseid, j.cmid, j.quizid, '.
                'u.id AS userid, u.firstname, u.lastname, u.username, '.
                'a.timestart ',
            '{'.ArchiveJob::ATTEMPTS_TABLE_NAME.'} am '.
                'JOIN {'.ArchiveJob::JOB_TABLE_NAME.'} j ON j.id = am.jobid '.
                'JOIN {user} u ON am.userid = u.id '.
                'JOIN {quiz_attempts} a ON am.attemptid = a.id ',
            'am.jobid = :jobid',
            [
                'jobid' => $jobid,
            ]
        );

        $this->sortable(true, 'id', SORT_ASC);
        $this->no_sorting('flags');
        $this->collapsible(false);
    }

    /**
     * User column rendered
     *
     * @param $values object Row data values
     * @return string Rendered value representation
     * @throws moodle_exception
     */
    public function col_username($values) {
        $userurl = new \moodle_url('/user/profile.php', ['id' => $values->userid]);
        $usertitle = "{$values->firstname} {$values->lastname} ({$values->username})";
        return '<a href="'.$userurl.'" target="_blank">'.$usertitle.'</a>';
    }

    /**
     * Attempt column rendered
     *
     * @param $values object Row data values
     * @return string Rendered value representation
     * @throws moodle_exception
     */
    public function col_attemptid($values) {
        $attempturl = new \moodle_url('/mod/quiz/review.php', ['attempt' => $values->attemptid]);
        return '<a href="'.$attempturl.'" target="_blank">'.get_string('id', 'quiz_archiver').': '.$values->attemptid.'</a>'.
               '<br/>'.userdate($values->timestart, get_string('strftimedatemonthtimeshort', 'langconfig'));
    }

    /**
     * Attachments column rendered
     *
     * @param $values object Row data values
     * @return string Rendered value representation
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function col_numattachments($values) {
        $color = $values->numattachments > 0 ? 'success' : 'danger';
        $html = '<span class="badge badge-'.$color.' py-1 px-3"><b>'.$values->numattachments.'</b></span>';

        if ($values->numattachments > 0) {
            // Prepare file data for display.
            $attachments = Report::get_attempt_attachments($values->attemptid);
            $filestodisplay = [];

            foreach ($attachments as $attachment) {
                /** @var \stored_file $file */
                $file = $attachment['file'];
                $filestodisplay[] = (object)[
                    'title' => get_string('file') . ': ' . $file->get_filename() . ' (' . display_size($file->get_filesize()) . ')',
                    'url' => \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        "{$attachment['usageid']}/{$attachment['slot']}/{$attachment['file']->get_itemid()}",
                        /* ^-- YES, this is the abomination of a non-numeric itemid that question_attempt::get_response_file_url()
                           creates while eating innocent programmers for breakfast ... */
                        $file->get_filepath(),
                        $file->get_filename(),
                        true,
                    ),
                ];
            }

            // Generate HTML for files.
            $html .= '<span class="ml-2">';
            foreach ($filestodisplay as $f) {
                $html .= '<a href="'.$f->url.'" target="_blank" class="btn btn-sm btn-outline-primary ml-1" role="button" '.
                         'data-toggle="tooltip" data-placement="top" title="'.$f->title.'" alt="'.$f->title.'">'.
                         '<i class="fa fa-file"></i></a>';
            }
            $html .= '</span>';
        }

        return $html;
    }

}
