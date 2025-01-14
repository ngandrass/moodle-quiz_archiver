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
 * Tests for the generate_attempt_report external service
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

use quiz_archiver\ArchiveJob;
use quiz_archiver\Report;

/**
 * Tests for the generate_attempt_report external service
 */
final class generate_attempt_report_test extends \advanced_testcase {

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
     * Generates a set of valid parameters
     *
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $quizid Quiz ID
     * @param int $attemptid Attempt ID
     * @return array Valid request parameters
     */
    protected function generate_valid_request(int $courseid, int $cmid, int $quizid, int $attemptid): array {
        return [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'attemptid' => $attemptid,
            'filenamepattern' => 'test',
            'sections' => array_fill_keys(Report::SECTIONS, true),
            'attachments' => true,
        ];
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            generate_attempt_report::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            generate_attempt_report::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function test_capability_requirement(): void {
        // Check that a user without the required capability is rejected.
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');

        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 1);
        generate_attempt_report::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptid'],
            $r['filenamepattern'],
            $r['sections'],
            $r['attachments']
        );
    }

    /**
     * Test web service part of processing of a valid request
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute
     *
     * @return void
     * @throws \DOMException
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        // Create mock quiz and archive job.
        $this->resetAfterTest();
        $this->setAdminUser();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '10000000-0000-0000-0000-0123456789ab';
        $wstoken = 'TEST-WS-TOKEN-1';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Create a valid request.
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 1);
        $_GET['wstoken'] = $wstoken;

        // Execute the request.
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('No attempt with given attemptid found');
        generate_attempt_report::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptid'],
            $r['filenamepattern'],
            $r['sections'],
            $r['attachments']
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_validation_data_provider
     * @covers \quiz_archiver\external\generate_attempt_report::execute
     * @covers \quiz_archiver\external\generate_attempt_report::validate_parameters
     *
     * @param string $invalidparameterkey Key of the parameter to invalidate
     * @return void
     * @throws \DOMException
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public function test_parameter_validation(string $invalidparameterkey): void {
        // Create mock quiz and archive job.
        $this->resetAfterTest();
        $this->setAdminUser();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '20000000-0000-0000-0000-0123456789ab';
        $wstoken = 'TEST-WS-TOKEN-2';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-2',
            $mocks->attempts,
            $mocks->settings
        );

        // Create a request.
        $r = $this->generate_valid_request(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->attempts[0]->attemptid
        );
        $_GET['wstoken'] = $wstoken;

        // Execute the request.
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessageMatches('/.*'.$invalidparameterkey.'.*/');
        generate_attempt_report::execute(
            $invalidparameterkey == 'courseid' ? 0 : $r['courseid'],
            $invalidparameterkey == 'cmid' ? 0 : $r['cmid'],
            $invalidparameterkey == 'quizid' ? 0 : $r['quizid'],
            $invalidparameterkey == 'attemptid' ? 0 : $r['attemptid'],
            $invalidparameterkey == 'filename pattern' ? 'invalid-${pattern' : $r['filenamepattern'],
            $invalidparameterkey == 'sections' ? [] : $r['sections'],
            $r['attachments']
        );
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public static function parameter_validation_data_provider(): array {
        return [
            'Invalid courseid' => ['courseid'],
            'Invalid cmid' => ['cmid'],
            'Invalid quizid' => ['quizid'],
            'Invalid attemptid' => ['attemptid'],
            'Invalid filenamepattern' => ['filename pattern'],
            'Invalid sections' => ['sections'],
        ];
    }

}
