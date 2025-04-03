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
            'user',
            'attempt',
            'numattachments',
        ]);

        $this->define_headers([
            get_string('id', 'quiz_archiver'),
            get_string('user'),
            get_string('attempt', 'quiz_archiver'),
            get_string('attachments', 'quiz_archiver'),
        ]);

        $this->set_sql(
            'am.id, am.userid, am.attemptid, am.numattachments, u.id AS userid, u.firstname, u.lastname, u.username',
            '{'.ArchiveJob::ATTEMPTS_TABLE_NAME.'} am '.
                'JOIN {user} u ON am.userid = u.id ',
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
    public function col_user($values) {
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
    public function col_attempt($values) {
        $attempturl = new \moodle_url('/mod/quiz/review.php', ['attempt' => $values->attemptid]);
        return '<a href="'.$attempturl.'" target="_blank">'.$values->attemptid.'</a>';
    }

    /**
     * Attachments column rendered
     *
     * @param $values object Row data values
     * @return string Rendered value representation
     */
    public function col_numattachments($values) {
        $color = $values->numattachments > 0 ? 'success' : 'danger';
        return '<span class="badge badge-'.$color.' py-1 px-3"><b>'.$values->numattachments.'</b></span>';
    }

}
