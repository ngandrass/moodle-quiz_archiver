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

use backup;

// @codingStandardsIgnoreLine
global $CFG;

require_once($CFG->dirroot . '/mod/quiz/report/archiver/patch_401_class_renames.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Tests for the Report class
 */
final class report_test extends \advanced_testcase {

    /**
     * Returns the data generator for the quiz_archiver plugin
     *
     * @return \quiz_archiver_generator The data generator for the quiz_archiver plugin
     */
    // @codingStandardsIgnoreLine
    public static function getDataGenerator(): \quiz_archiver_generator {
        return parent::getDataGenerator()->get_plugin_generator('quiz_archiver');
    }

    /**
     * Generates an report section settings array with all sections enabled
     *
     * @return array To pass to Report::generate(), with all report sections enabled
     */
    protected static function get_all_report_sections_enabled(): array {
        $sections = [];
        foreach (Report::SECTIONS as $section) {
            $sections[$section] = true;
        }
        return $sections;
    }

    /**
     * Generates an archive_form formdata object with all report sections enabled
     *
     * @return \stdClass That emulates the data received from the archive_form
     */
    protected static function get_formdata_all_reports_sections_enabled(): object {
        $formdata = new \stdClass();
        foreach (Report::SECTIONS as $section) {
            $formdata->{'export_report_section_'.$section} = 1;
        }
        return $formdata;
    }

    /**
     * Tests validation of webservice tokens
     *
     * @covers \quiz_archiver\Report::has_access
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_webservice_token_access_validation(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $validtoken = md5("VALID-TEST-TOKEN");
        $invalidtoken = md5("INVALID-TEST-TOKEN");
        $job = ArchiveJob::create(
            'test-job',
            $rc->course->id,
            $rc->cm->id,
            $rc->quiz->id,
            2,
            null,
            $validtoken,
            [],
            [],
        );

        $this->assertTrue($report->has_access($validtoken), 'Valid token rejected');
        $this->assertFalse($report->has_access($invalidtoken), 'Invalid token accepted');

        $job->set_status(ArchiveJob::STATUS_FINISHED);
        $this->assertFalse($report->has_access($validtoken), 'Valid token accepted for finished job');
        $this->assertFalse($report->has_access($invalidtoken), 'Invalid token accepted for finished job');
    }

    /**
     * Test generation of a full attempt report with all sections
     *
     * @covers \quiz_archiver\Report::__construct
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \DOMException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_generate_full_report(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate full report with all sections.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $html = $report->generate($rc->attemptids[0], self::get_all_report_sections_enabled());
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify quiz header.
        $this->assertMatchesRegularExpression(
            '/<table[^<>]*quizreviewsummary[^<>]*>/',
            $html,
            'Quiz header table not found'
        );
        $this->assertMatchesRegularExpression(
            '/<td[^<>]*>' . preg_quote($rc->course->fullname,
                '/') . '[^<>]+<\/td>/',
            $html, 'Course name not found'
        );
        $this->assertMatchesRegularExpression(
            '/<td[^<>]*>' . preg_quote($rc->quiz->name,
                '/') . '[^<>]+<\/td>/',
            $html, 'Quiz name not found'
        );

        // Verify overall quiz feedback.
        // TODO (MDL-0): Add proper overall feedback to reference quiz and check its contents.
        $this->assertMatchesRegularExpression(
            '/<th[^<>]*>\s*' . preg_quote(get_string('feedback',
                'quiz'),
                '/'
            ) . '\s*<\/th>/', $html, 'Overall feedback header not found');

        // Verify questions.
        foreach ($this->getDataGenerator()::QUESTION_TYPES_IN_REFERENCE_QUIZ as $qtype) {
            $this->assertMatchesRegularExpression(
                '/<[^<>]*class="[^\"<>]*que[^\"<>]*' . preg_quote($qtype, '/') . '[^\"<>]*"[^<>]*>/',
                $html,
                'Question of type ' . $qtype . ' not found'
            );
        }

        // Verify individual question feedback.
        $this->assertMatchesRegularExpression(
            '/<div class="specificfeedback">/',
            $html,
            'Individual question feedback not found'
        );

        // Verify general question feedback.
        $this->assertMatchesRegularExpression(
            '/<div class="generalfeedback">/',
            $html,
            'General question feedback not found'
        );

        // Verify correct answers.
        $this->assertMatchesRegularExpression(
            '/<div class="rightanswer">/',
            $html,
            'Correct question answers not found'
        );

        // Verify answer history.
        $this->assertMatchesRegularExpression(
            '/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/',
            $html,
            'Answer history not found'
        );
    }

    /**
     * Tests generation of a full page report with all sections
     *
     * @covers \quiz_archiver\Report::generate_full_page
     * @covers \quiz_archiver\Report::convert_image_to_base64
     * @covers \quiz_archiver\Report::ensure_absolute_url
     *
     * @return void
     * @throws \DOMException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_full_page_stub(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $html = $report->generate_full_page(
            $rc->attemptids[0],
            self::get_all_report_sections_enabled(),
            false,  // We need to disable this since $OUTPUT->header() is not working during tests.
            false,  // We need to disable this since $OUTPUT->header() is not working during tests.
            true
        );
        $this->assertNotEmpty($html, 'Generated report is empty');
    }

    /**
     * Tests generation of a report with no header
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @throws \restore_controller_exception
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_generate_report_no_header(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without a header.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['header'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that quiz header is absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<table[^<>]*quizreviewsummary[^<>]*>/',
            $html,
            'Quiz header table found when it should be absent'
        );

        // If the quiz header is disabled, the quiz feedback should also be absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<th[^<>]*>\s*'.preg_quote(get_string('feedback', 'quiz'), '/').'\s*<\/th>/',
            $html,
            'Overall feedback header found when it should be absent'
        );
    }

    /**
     * Tests generation of a report with no quiz feedback
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_quiz_feedback(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without quiz feedback.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['quiz_feedback'] = false;
        $sections['questions'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that quiz feedback is absent.
        $this->assertMatchesRegularExpression(
            '/<table[^<>]*quizreviewsummary[^<>]*>/',
            $html,
            'Quiz header table not found'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<th[^<>]*>\s*'.preg_quote(get_string('feedback', 'quiz'), '/').'\s*<\/th>/',
            $html,
            'Overall feedback header found when it should be absent'
        );
    }

    /**
     * Tests generation of a report with no questions
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_questions(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without questions.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['question'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that no questions are present.
        $this->assertDoesNotMatchRegularExpression(
            '/<[^<>]*class="[^\"<>]*que[^<>]*>/',
            $html,
            'Question found when it should be absent'
        );

        // If questions are disabled, question_feedback, general_feedback, rightanswer and history should be absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="specificfeedback">/',
            $html,
            'Individual question feedback found when it should be absent'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="generalfeedback">/',
            $html,
            'General question feedback found when it should be absent'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="rightanswer">/',
            $html,
            'Correct question answers found when they should be absent'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/',
            $html,
            'Answer history found when it should be absent'
        );
    }

    /**
     * Tests generation of a report with no individual question feedback
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_question_feedback(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without question feedback.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['question_feedback'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that question feedback is absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="specificfeedback">/',
            $html,
            'Individual question feedback found when it should be absent'
        );
    }

    /**
     * Tests generation of a report with no general question feedback
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_general_feedback(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without general feedback.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['general_feedback'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that general feedback is absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="generalfeedback">/',
            $html,
            'General question feedback found when it should be absent'
        );
    }

    /**
     * Tests generation of a report without showing correct answers for questions
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_rightanswers(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without right answers.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['rightanswer'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that right answers are absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<div class="rightanswer">/',
            $html,
            'Correct question answers found when they should be absent'
        );
    }

    /**
     * Tests generation of a report without showing answer histories
     *
     * @covers \quiz_archiver\Report::generate
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_generate_report_no_history(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        // Generate report without answer history.
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $sections = self::get_all_report_sections_enabled();
        $sections['history'] = false;
        $html = $report->generate($rc->attemptids[0], $sections);
        $this->assertNotEmpty($html, 'Generated report is empty');

        // Verify that answer history is absent.
        $this->assertDoesNotMatchRegularExpression(
            '/<[^<>]*class="responsehistoryheader[^\"<>]*"[^<>]*>/',
            $html,
            'Answer history found when it should be absent'
        );
    }

    /**
     * Tests to get the attachments of an attempt
     *
     * @covers \quiz_archiver\Report::get_attempt_attachments
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempt_attachments(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attachments = $report->get_attempt_attachments($rc->attemptids[0]);
        $this->assertNotEmpty($attachments, 'No attachments found');

        // Find cake.md attachment.
        $this->assertNotEmpty(
            array_filter(
                $attachments,
                fn($a) => $a['file']->get_filename() === 'cake.md'
            ),
            'cake.md attachment not found'
        );
    }

    /**
     * Tests metadata retrieval for attempt attachments
     *
     * @covers \quiz_archiver\Report::get_attempt_attachments_metadata
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempt_attachments_metadata(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attachments = $report->get_attempt_attachments_metadata($rc->attemptids[0]);
        $this->assertNotEmpty($attachments, 'No attachments found');

        // Find cake.md attachment.
        $cake = array_values(array_filter($attachments, fn($a) => $a->filename === 'cake.md'))[0];
        $this->assertNotEmpty($cake, 'cake.md attachment not found');

        $this->assertNotEmpty($cake->slot, 'Attachment slot not set');
        $this->assertNotEmpty($cake->filename, 'Attachment filename not set');
        $this->assertNotEmpty($cake->filesize, 'Attachment filesize not set');
        $this->assertNotEmpty($cake->mimetype, 'Attachment mimetype not set');
        $this->assertNotEmpty($cake->contenthash, 'Attachment contenthash not set');
        $this->assertNotEmpty($cake->downloadurl, 'Attachment downloadurl not set');

        $this->assertEquals(
            sha1_file(__DIR__ . '/fixtures/cake.md'),
            $cake->contenthash,
            'Attachment contenthash (SHA1) does not match'
        );
    }

    /**
     * Tests to get the attempts of a quiz
     *
     * @covers \quiz_archiver\Report::get_attempts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempts(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();

        $report = new Report($rc->course, $rc->cm, $rc->quiz);
        $attempts = $report->get_attempts();

        $this->assertNotEmpty($attempts, 'No attempts found');
        $this->assertCount(count($rc->attemptids), $attempts, 'Incorrect number of attempts found');
    }

    /**
     * Tests to get the attempt metadata array for a quiz
     *
     * @covers \quiz_archiver\Report::get_attempts_metadata
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_attempts_metadata(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        // Test without filters.
        $attempts = $report->get_attempts_metadata();
        $this->assertNotEmpty($attempts, 'No attempts found without filters set');
        $this->assertCount(count($rc->attemptids), $attempts, 'Incorrect number of attempts found without filters set');

        $attempt = array_shift($attempts);
        $this->assertNotEmpty($attempt->attemptid, 'Attempt metadata does not contain attemptid');
        $this->assertNotEmpty($attempt->userid, 'Attempt metadata does not contain userid');
        $this->assertNotEmpty($attempt->attempt, 'Attempt metadata does not contain attempt');
        $this->assertNotEmpty($attempt->state, 'Attempt metadata does not contain state');
        $this->assertNotEmpty($attempt->timestart, 'Attempt metadata does not contain timestart');
        $this->assertNotEmpty($attempt->timefinish, 'Attempt metadata does not contain timefinish');
        $this->assertNotEmpty($attempt->username, 'Attempt metadata does not contain username');
        $this->assertNotEmpty($attempt->firstname, 'Attempt metadata does not contain firstname');
        $this->assertNotEmpty($attempt->lastname, 'Attempt metadata does not contain lastname');
        $this->assertNotNull($attempt->idnumber, 'Attempt metadata does not contain idnumber');  // ID number can be empty.

        // Test filtered.
        $attemptsfilteredexisting = $report->get_attempts_metadata($rc->attemptids);
        $this->assertNotEmpty($attemptsfilteredexisting, 'No attempts found with existing attempt ids');
        $this->assertCount(
            count($rc->attemptids),
            $attemptsfilteredexisting,
            'Incorrect number of attempts found with existing attempt ids'
        );

        $attemptsfilterednonexisting = $report->get_attempts_metadata([-1, -2, -3]);
        $this->assertEmpty($attemptsfilterednonexisting, 'Attempts found for non-existing attempt ids');
    }

    /**
     * Tests to get the IDs of users with attempts in a quiz
     *
     * @covers \quiz_archiver\Report::get_users_with_attempts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_users_with_attempts(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $users = $report->get_users_with_attempts();
        $this->assertNotEmpty($users, 'No users found with attempts');
        $this->assertEquals(array_values($rc->userids), array_values($users), 'Incorrect IDs found for users with attempts');
    }

    /**
     * Tests to retrieve the latest attemptid of a user
     *
     * @covers \quiz_archiver\Report::get_latest_attempt_for_user
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_get_latest_attempt_for_user(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $latestattempt = $report->get_latest_attempt_for_user($rc->userids[0]);
        $this->assertNotEmpty($latestattempt, 'No latest attempt found for user');

        $latestattemptmissing = $report->get_latest_attempt_for_user(-1);
        $this->assertEmpty($latestattemptmissing, 'Latest attempt found for non-existing user');
    }

    /**
     * Tests to retrieve existing and nonexisting attempts
     *
     * @covers \quiz_archiver\Report::attempt_exists
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_attempt_exists(): void {
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $report = new Report($rc->course, $rc->cm, $rc->quiz);

        $this->assertTrue($report->attempt_exists($rc->attemptids[0]), 'Existing attempt not found');
        $this->assertFalse($report->attempt_exists(-1), 'Non-existing attempt found');
    }

    /**
     * Tests conversion/sanitization of formdata to report section settings
     *
     * @covers \quiz_archiver\Report::build_report_sections_from_formdata
     *
     * @return void
     */
    public function test_build_report_sections_from_formdata(): void {
        // Test all sections enabled.
        $formdata = self::get_formdata_all_reports_sections_enabled();
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEquals(
            self::get_all_report_sections_enabled(),
            $sections,
            'Full formdata not correctly converted to report sections'
        );

        // Test removal of dependent sections.
        $formdata = self::get_formdata_all_reports_sections_enabled();
        $formdata->export_report_section_question = 0;
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEmpty($sections['question'], 'Root section not removed correctly');
        $this->assertEmpty($sections['question_feedback'], 'Dependent section question_feedback not removed correctly');
        $this->assertEmpty($sections['general_feedback'], 'Dependent section general_feedback not removed correctly');
        $this->assertEmpty($sections['rightanswer'], 'Dependent section rightanswer not removed correctly');
        $this->assertEmpty($sections['history'], 'Dependent section history not removed correctly');
        $this->assertEmpty($sections['attachments'], 'Dependent section attachments not removed correctly');

        // Test removal of superfluous sections.
        $formdata = self::get_formdata_all_reports_sections_enabled();
        $formdata->export_report_section_superfluous = 1;
        $sections = Report::build_report_sections_from_formdata($formdata);
        $this->assertEquals(self::get_all_report_sections_enabled(), $sections, 'Superfluous section not removed correctly');
    }

}
