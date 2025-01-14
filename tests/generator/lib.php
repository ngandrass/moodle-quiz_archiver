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

use quiz_archiver\FileManager;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

global $CFG; // @codeCoverageIgnore
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php'); // @codeCoverageIgnore

/**
 * Tests generator for the quiz_archiver plugin
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_archiver_generator extends \testing_data_generator {

    /** @var string[] Question types present in the reference quiz */
    const QUESTION_TYPES_IN_REFERENCE_QUIZ = [
        'description',
        'multichoice',
        'truefalse',
        'match',
        'shortanswer',
        'numerical',
        'essay',
        'calculated',
        'calculatedmulti',
        'calculatedsimple',
        'ddwtos',
        'ddmarker',
        'ddimageortext',
        'multianswer',
        'gapselect',
    ];

    /**
     * Creates a course that contains a quiz module as a new user.
     *
     * @return stdClass The user, course and quiz, as well as mock attempts and
     * archive job settings.
     */
    public function create_mock_quiz(): \stdClass {
        // Prepare user and course.
        $user = $this->create_user();
        $course = $this->create_course();
        $quiz = $this->create_module('quiz', [
            'course' => $course->id,
            'grade' => 100.0,
            'sumgrades' => 100,
        ]);

        return (object) [
            'user' => $user,
            'course' => $course,
            'quiz' => $quiz,
            'attempts' => [
                (object) ['userid' => 1, 'attemptid' => 1],
                (object) ['userid' => 2, 'attemptid' => 42],
                (object) ['userid' => 3, 'attemptid' => 1337],
            ],
            'settings' => [
                'num_attempts' => 3,
                'export_attempts' => 1,
                'export_report_section_header' => 1,
                'export_report_section_quiz_feedback' => 1,
                'export_report_section_question' => 1,
                'export_report_section_question_feedback' => 0,
                'export_report_section_general_feedback' => 1,
                'export_report_section_rightanswer' => 0,
                'export_report_section_history' => 1,
                'export_report_section_attachments' => 1,
                'export_quiz_backup' => 1,
                'export_course_backup' => 0,
                'archive_autodelete' => 1,
                'archive_retention_time' => '42w',
            ],
        ];
    }

    /**
     * Generates a dummy artifact file, stored in the context of the given course.
     *
     * @param int $courseid ID of the course to store the file in
     * @param int $cmid ID of the course module to store the file in
     * @param int $quizid ID of the quiz to store the file in
     * @param string $filename Name of the file to create
     * @return \stored_file The created file handle
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_artifact_file(int $courseid, int $cmid, int $quizid, string $filename): \stored_file {
        $ctx = context_course::instance($courseid);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => FileManager::COMPONENT_NAME,
                'filearea'     => FileManager::ARTIFACTS_FILEAREA_NAME,
                'itemid'       => 0,
                'filepath'     => "/{$courseid}/{$cmid}/{$quizid}/",
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do '.
            'eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

    /**
     * Generates a dummy draft file, stored in the given filearea (default: user
     * draft filearea).
     *
     * @param string $filename Name of the file to create
     * @param string $filearea Filearea to store the file in
     * @return \stored_file The created file handle
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_draft_file(string $filename, string $filearea = 'draft'): \stored_file {
        $ctx = context_user::instance($this->create_user()->id);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => 'user',
                'filearea'     => $filearea,
                'itemid'       => 0,
                'filepath'     => "/",
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do '.
            'eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

    /**
     * Imports the reference course into a new course and returns the reference
     * quiz, the respective cm, and the course itself.
     *
     * @throws \restore_controller_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @return \stdClass Object with keys 'quiz' (the reference quiz), 'cm' (the
     * respective cm), 'course' (the course itself), 'attemptids' (array of all
     * attempt ids inside the reference quiz), 'userids' (array of all user ids
     * with attempts in the reference quiz)
     */
    public function import_reference_course(): \stdClass {
        global $DB;

        // Prepare backup of reference course for restore.
        $backupid = 'referencequiz';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            __DIR__."/../fixtures/referencequiz.mbz",
            $backuppath
        );

        // Restore reference course as a new course with default settings.
        $categoryid = $DB->get_field('course_categories', 'MIN(id)', []);
        $newcourseid = \restore_dbops::create_new_course('Reference Course', 'REF', $categoryid);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            get_admin()->id,
            \backup::TARGET_NEW_COURSE
        );

        if (!$rc->execute_precheck()) {
            throw new \restore_controller_exception('Backup restore precheck failed.'); // @codeCoverageIgnore
        }
        $rc->execute_plan();
        if ($rc->get_status() != backup::STATUS_FINISHED_OK) {
            throw new \restore_controller_exception('Restore of reference course failed.'); // @codeCoverageIgnore
        }

        // 2024-05-14: Do not destroy restore_controller. This will drop temptables without removing them from
        // $DB->temptables properly, causing DB reset to fail in subsequent tests due to missing tables. Destroying the
        // restore_controller is optional and not necessary for this test.
        // $rc->destroy();.

        // Get course and find the reference quiz.
        $course = get_course($rc->get_courseid());
        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();
        $cm = null;
        foreach ($cms as $curcm) {
            if ($curcm->modname == 'quiz' && strpos($curcm->name, 'Reference Quiz') === 0) {
                $cm = $curcm;
                break;
            }
        }
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $attemptids = array_values(array_map(
            fn($r): int => $r->id,
            $DB->get_records('quiz_attempts', ['quiz' => $quiz->id], '', 'id')
        ));

        $userids = array_values(array_map(
            fn($r): int => $r->userid,
            $DB->get_records('quiz_attempts', ['quiz' => $quiz->id], '', 'userid')
        ));

        return (object) [
            'course' => $course,
            'cm' => $cm,
            'quiz' => $quiz,
            'attemptids' => $attemptids,
            'userids' => $userids,
        ];
    }

    /**
     * Imports the reference quiz artifact from the test fixtures directory into
     * the a Moodle stored_file residing inside a users draft filearea.
     *
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function import_reference_quiz_artifact_as_draft(): \stored_file {
        $ctx = context_user::instance($this->create_user()->id);

        return get_file_storage()->create_file_from_pathname([
            'contextid'    => $ctx->id,
            'component'    => 'user',
            'filearea'     => 'draft',
            'itemid'       => 0,
            'filepath'     => "/",
            'filename'     => 'reference_quiz_artifact.tar.gz',
            'timecreated'  => time(),
            'timemodified' => time(),
        ], __DIR__.'/../fixtures/referencequiz-artifact.tar.gz');
    }

    /**
     * Generates a dummy file inside the temp filearea of this plugin.
     *
     * @param string $filename
     * @param int $expiry
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function create_temp_file(string $filename, int $expiry): \stored_file {
        $ctx = context_user::instance($this->create_user()->id);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => FileManager::COMPONENT_NAME,
                'filearea'     => FileManager::TEMP_FILEAREA_NAME,
                'itemid'       => 0,
                'filepath'     => '/'.$expiry.'/',
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do '.
            'eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

}
