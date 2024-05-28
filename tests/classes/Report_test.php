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
 * Tests for the Report class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');


/**
 * Tests for the Report class
 */
class Report_test extends \advanced_testcase {

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
     * Called before every test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
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
     * attempt ids inside the reference quiz)
     */
    protected function prepareReferenceCourse(): \stdClass {
        global $DB, $USER;
        $this->resetAfterTest();

        // Prepare backup of reference course for restore
        $backupid = 'referencequiz';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            __DIR__ . "/../fixtures/referencequiz.mbz",
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
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();

        // 2024-05-14: Do not destroy restore_controller. This will drop temptables without removing them from
        // $DB->temptables properly, causing DB reset to fail in subsequent tests due to missing tables. Destroying the
        // restore_controller is optional and not necessary for this test.
        //$rc->destroy();

        // Get course and find the reference quiz
        $course = get_course($rc->get_courseid());
        $modinfo = get_fast_modinfo($course);
        $cms = $modinfo->get_cms();
        $cm = null;
        foreach ($cms as $curCm) {
            if ($curCm->modname == 'quiz' && strpos($curCm->name, 'Reference Quiz') === 0) {
                $cm = $curCm;
                break;
            }
        }
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $attemptids = array_values(array_map(
            fn($r): int => $r->id,
            $DB->get_records('quiz_attempts', ['quiz' => $quiz->id], '', 'id')
        ));

        return (object) [
            'course' => $course,
            'cm' => $cm,
            'quiz' => $quiz,
            'attemptids' => $attemptids,
        ];
    }

    /**
     * @return array To pass to Report::generate(), with all report sections enabled
     */
    protected static function getAllReportSectionsEnabled(): array {
        $sections = [];
        foreach (Report::SECTIONS as $section) {
            $sections[$section] = true;
        }
        return $sections;
    }

    /**
     * Test generation of a full page report with all sections
     *
     * @return void
     * @throws \DOMException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
     public function test_generate_full_report() {
         $rd = $this->prepareReferenceCourse();

         // Generate full report with all sections
         $report = new Report($rd->course, $rd->cm, $rd->quiz);
         $html = $report->generate($rd->attemptids[0], self::getAllReportSectionsEnabled());
         $this->assertNotEmpty($html, 'Generated report is empty');

         // Verify quiz header
         $this->assertMatchesRegularExpression('/<table[^<>]*quizreviewsummary[^<>]*>/', $html, 'Quiz header table not found');
         $this->assertMatchesRegularExpression('/<td[^<>]*>'.preg_quote($rd->course->fullname, '/').'[^<>]+<\/td>/', $html, 'Course name not found');
         $this->assertMatchesRegularExpression('/<td[^<>]*>'.preg_quote($rd->quiz->name, '/').'[^<>]+<\/td>/', $html, 'Quiz name not found');

         // Verify overall quiz feedback
         // TODO: Add proper overall feedback to reference quiz and check its contents
         $this->assertMatchesRegularExpression('/<th[^<>]*>\s*'.preg_quote(get_string('feedback', 'quiz'), '/').'\s*<\/th>/', $html, 'Overall feedback header not found');

         // Verify questions
         foreach (self::QUESTION_TYPES_IN_REFERENCE_QUIZ as $qtype) {
             $this->assertMatchesRegularExpression('/<[^<>]*class="[^\"<>]*que[^\"<>]*'.preg_quote($qtype, '/').'[^\"<>]*"[^<>]*>/', $html, 'Question of type '.$qtype.' not found');
         }

         // Verify individual question feedback
         $this->assertMatchesRegularExpression('/<div class="specificfeedback">/', $html, 'Individual question feedback not found');

         // Verify general question feedback
         $this->assertMatchesRegularExpression('/<div class="generalfeedback">/', $html, 'General question feedback not found');

         // Verify correct answers
         $this->assertMatchesRegularExpression('/<div class="rightanswer">/', $html, 'Correct question answers not found');

         // Verify answer history
         $this->assertMatchesRegularExpression('/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/', $html, 'Answer history not found');
    }

    /**
     * Tests generation of a report with no header
     *
     * @throws \restore_controller_exception
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_generate_report_no_header() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without a header
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['header'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that quiz header is absent
        $this->assertDoesNotMatchRegularExpression('/<table[^<>]*quizreviewsummary[^<>]*>/', $html, 'Quiz header table found when it should be absent');

        // If the quiz header is disabled, the quiz feedback should also be absent
        $this->assertDoesNotMatchRegularExpression('/<th[^<>]*>\s*'.preg_quote(get_string('feedback', 'quiz'), '/').'\s*<\/th>/', $html, 'Overall feedback header found when it should be absent');
    }

    /**
     * Tests generation of a report with no quiz feedback
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_quiz_feedback() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without quiz feedback
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['quiz_feedback'] = false;
        $sections['questions'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that quiz feedback is absent
        $this->assertMatchesRegularExpression('/<table[^<>]*quizreviewsummary[^<>]*>/', $html, 'Quiz header table not found');
        $this->assertDoesNotMatchRegularExpression('/<th[^<>]*>\s*'.preg_quote(get_string('feedback', 'quiz'), '/').'\s*<\/th>/', $html, 'Overall feedback header found when it should be absent');
    }

    /**
     * Tests generation of a report with no questions
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_questions() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without questions
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['question'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that no questions are present
        $this->assertDoesNotMatchRegularExpression('/<[^<>]*class="[^\"<>]*que[^<>]*>/', $html, 'Question found when it should be absent');

        // If questions are disabled, question_feedback, general_feedback, rightanswer and history should be absent
        $this->assertDoesNotMatchRegularExpression('/<div class="specificfeedback">/', $html, 'Individual question feedback found when it should be absent');
        $this->assertDoesNotMatchRegularExpression('/<div class="generalfeedback">/', $html, 'General question feedback found when it should be absent');
        $this->assertDoesNotMatchRegularExpression('/<div class="rightanswer">/', $html, 'Correct question answers found when they should be absent');
        $this->assertDoesNotMatchRegularExpression('/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/', $html, 'Answer history found when it should be absent');
    }

    /**
     * Tests generation of a report with no individual question feedback
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_question_feedback() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without question feedback
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['question_feedback'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that question feedback is absent
        $this->assertDoesNotMatchRegularExpression('/<div class="specificfeedback">/', $html, 'Individual question feedback found when it should be absent');
    }

    /**
     * Tests generation of a report with no general question feedback
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_general_feedback() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without general feedback
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['general_feedback'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that general feedback is absent
        $this->assertDoesNotMatchRegularExpression('/<div class="generalfeedback">/', $html, 'General question feedback found when it should be absent');
    }

    /**
     * Tests generation of a report without showing correct answers for questions
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_rightanswers() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without right answers
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['rightanswer'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that right answers are absent
        $this->assertDoesNotMatchRegularExpression('/<div class="rightanswer">/', $html, 'Correct question answers found when they should be absent');
    }

    /**
     * Tests generation of a report without showing answer histories
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_history() {
        $rd = $this->prepareReferenceCourse();

        // Generate report without answer history
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['history'] = false;
        $html = $report->generate($rd->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that answer history is absent
        $this->assertDoesNotMatchRegularExpression('/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/', $html, 'Answer history found when it should be absent');
    }

    /**
     * Tests to get the attachments of an attempt
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempt_attachments() {
        $rd = $this->prepareReferenceCourse();
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $attachments = $report->get_attempt_attachments($rd->attemptids[0]);
        $this->assertNotEmpty($attachments, 'No attachments found');

        // Find cake.md attachment
        $this->assertNotEmpty(array_filter($attachments, fn($a) => $a['file']->get_filename() === 'cake.md'), 'cake.md attachment not found');
    }

    /**
     * Tests metadata retrieval for attempt attachments
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempt_attachments_metadata() {
        $rd = $this->prepareReferenceCourse();
        $report = new Report($rd->course, $rd->cm, $rd->quiz);
        $attachments = $report->get_attempt_attachments_metadata($rd->attemptids[0]);
        $this->assertNotEmpty($attachments, 'No attachments found');

        // Find cake.md attachment
        $cake = array_values(array_filter($attachments, fn($a) => $a->filename === 'cake.md'))[0];
        $this->assertNotEmpty($cake, 'cake.md attachment not found');

        $this->assertNotEmpty($cake->slot, 'Attachment slot not set');
        $this->assertNotEmpty($cake->filename, 'Attachment filename not set');
        $this->assertNotEmpty($cake->filesize, 'Attachment filesize not set');
        $this->assertNotEmpty($cake->mimetype, 'Attachment mimetype not set');
        $this->assertNotEmpty($cake->contenthash, 'Attachment contenthash not set');
        $this->assertNotEmpty($cake->downloadurl, 'Attachment downloadurl not set');

        $this->assertEquals(sha1_file(__DIR__.'/../fixtures/cake.md'), $cake->contenthash, 'Attachment contenthash (SHA1) does not match');
    }

}