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
    protected function prepareReferenceCourse(): \stdClass {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

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
     * @return \stdClass That emulates the data received from the archive_form
     */
    protected static function getFormdataAllReportsSectionsEnabled(): object {
        $formdata = new \stdClass();
        foreach (Report::SECTIONS as $section) {
            $formdata->{'export_report_section_'.$section} = 1;
        }
        return $formdata;
    }

    /**
     * Tests validation of webservice tokens
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_webservice_token_access_validation() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $validToken = md5("VALID-TEST-TOKEN");
        $invalidToken = md5("INVALID-TEST-TOKEN");
        $job = ArchiveJob::create(
            'test-job',
            $rc->course->id,
            $rc->cm->id,
            $rc->quiz->id,
            2,
            null,
            $validToken,
            [],
            [],
        );

        $this->assertTrue($report->has_access($validToken), 'Valid token rejected');
        $this->assertFalse($report->has_access($invalidToken), 'Invalid token accepted');

        $job->set_status(ArchiveJob::STATUS_FINISHED);
        $this->assertFalse($report->has_access($validToken), 'Valid token accepted for finished job');
        $this->assertFalse($report->has_access($invalidToken), 'Invalid token accepted for finished job');
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
         $rc = $this->prepareReferenceCourse();

         // Generate full report with all sections
         $report = new Report($rc->course, $rc->cm, $rc->quiz);
         $html = $report->generate($rc->attemptids[0], self::getAllReportSectionsEnabled());
         $this->assertNotEmpty($html, 'Generated report is empty');

         // Verify quiz header
         $this->assertMatchesRegularExpression('/<table[^<>]*quizreviewsummary[^<>]*>/', $html, 'Quiz header table not found');
         $this->assertMatchesRegularExpression('/<td[^<>]*>'.preg_quote($rc->course->fullname, '/').'[^<>]+<\/td>/', $html, 'Course name not found');
         $this->assertMatchesRegularExpression('/<td[^<>]*>'.preg_quote($rc->quiz->name, '/').'[^<>]+<\/td>/', $html, 'Quiz name not found');

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

    public function test_generate_full_page_stub() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $html = $report->generate_full_page(
            $rc->attemptids[0],
            self::getAllReportSectionsEnabled(),
            false,  // We need to disable this since $OUTPUT->header() is not working during tests
            false,  // We need to disable this since $OUTPUT->header() is not working during tests
            true
        );
        $this->assertNotEmpty($html, 'Generated report is empty');
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without a header
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['header'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without quiz feedback
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['quiz_feedback'] = false;
        $sections['questions'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without questions
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['question'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without question feedback
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['question_feedback'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without general feedback
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['general_feedback'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without right answers
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['rightanswer'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();

        // Generate report without answer history
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::getAllReportSectionsEnabled();
        $sections['history'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
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
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attachments = $report->get_attempt_attachments($rc->attemptids[0]);
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
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attachments = $report->get_attempt_attachments_metadata($rc->attemptids[0]);
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

    /**
     * Tests to get the attempts of a quiz
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempts() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attempts = $report->get_attempts();

        $this->assertNotEmpty($attempts, 'No attempts found');
        $this->assertCount(count($rc->attemptids), $attempts, 'Incorrect number of attempts found');
    }

    /**
     * Tests to get the attempt metadata array for a quiz
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempts_metadata() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        // Test without filters
        $attempts = $report->get_attempts_metadata();
        $this->assertNotEmpty($attempts, 'No attempts found without filters set');
        $this->assertCount(count($rc->attemptids), $attempts, 'Incorrect number of attempts found without filters set');

        $attempt = array_shift($attempts);
        $this->assertObjectHasProperty('attemptid', $attempt, 'Attempt metadata does not contain attemptid');
        $this->assertObjectHasProperty('userid', $attempt, 'Attempt metadata does not contain userid');
        $this->assertObjectHasProperty('attempt', $attempt, 'Attempt metadata does not contain attempt');
        $this->assertObjectHasProperty('state', $attempt, 'Attempt metadata does not contain state');
        $this->assertObjectHasProperty('timestart', $attempt, 'Attempt metadata does not contain timestart');
        $this->assertObjectHasProperty('timefinish', $attempt, 'Attempt metadata does not contain timefinish');
        $this->assertObjectHasProperty('username', $attempt, 'Attempt metadata does not contain username');
        $this->assertObjectHasProperty('firstname', $attempt, 'Attempt metadata does not contain firstname');
        $this->assertObjectHasProperty('lastname', $attempt, 'Attempt metadata does not contain lastname');

        // Test filtered
        $attempts_filtered_existing = $report->get_attempts_metadata($rc->attemptids);
        $this->assertNotEmpty($attempts_filtered_existing, 'No attempts found with existing attempt ids');
        $this->assertCount(count($rc->attemptids), $attempts_filtered_existing, 'Incorrect number of attempts found with existing attempt ids');

        $attempts_filtered_nonexisting = $report->get_attempts_metadata([-1, -2, -3]);
        $this->assertEmpty($attempts_filtered_nonexisting, 'Attempts found for non-existing attempt ids');
    }

    /**
     * Tests to get the IDs of users with attempts in a quiz
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_users_with_attempts() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $users = $report->get_users_with_attempts();
        $this->assertNotEmpty($users, 'No users found with attempts');
        $this->assertEquals(array_values($rc->userids), array_values($users), 'Incorrect IDs found for users with attempts');
    }

    /**
     * Tests to retrieve the latest attemptid of a user
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_latest_attempt_for_user() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $latest_attempt = $report->get_latest_attempt_for_user($rc->userids[0]);
        $this->assertNotEmpty($latest_attempt, 'No latest attempt found for user');

        $latest_attempt_missing = $report->get_latest_attempt_for_user(-1);
        $this->assertEmpty($latest_attempt_missing, 'Latest attempt found for non-existing user');
    }

    /**
     * Tests to retrieve existing and nonexisting attempts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_attempt_exists() {
        $rc = $this->prepareReferenceCourse();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $this->assertTrue($report->attempt_exists($rc->attemptids[0]), 'Existing attempt not found');
        $this->assertFalse($report->attempt_exists(-1), 'Non-existing attempt found');
    }

    /**
     * Tests conversion/sanitization of formdata to report section settings
     *
     * @return void
     */
    public function test_build_report_sections_from_formdata() {
        // Test all sections enabled
        $formdata = self::getFormdataAllReportsSectionsEnabled();
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEquals(self::getAllReportSectionsEnabled(), $sections, 'Full formdata not correctly converted to report sections');

        // Test removal of dependent sections
        $formdata = self::getFormdataAllReportsSectionsEnabled();
        $formdata->export_report_section_question = 0;
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEmpty($sections['question'], 'Root section not removed correctly');
        $this->assertEmpty($sections['question_feedback'], 'Dependent section question_feedback not removed correctly');
        $this->assertEmpty($sections['general_feedback'], 'Dependent section general_feedback not removed correctly');
        $this->assertEmpty($sections['rightanswer'], 'Dependent section rightanswer not removed correctly');
        $this->assertEmpty($sections['history'], 'Dependent section history not removed correctly');
        $this->assertEmpty($sections['attachments'], 'Dependent section attachments not removed correctly');

        // Test removal of superfluous sections
        $formdata = self::getFormdataAllReportsSectionsEnabled();
        $formdata->export_report_section_superfluous = 1;
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEquals(self::getAllReportSectionsEnabled(), $sections, 'Superfluous section not removed correctly');
    }

}