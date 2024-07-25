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
 * Tests for the get_attempts_metadata external service
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

use quiz_archiver\ArchiveJob;

/**
 * Tests for the get_attempts_metadata external service
 */
final class get_attempts_metadata_test extends \advanced_testcase {

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
     * @return array Valid request parameters
     */
    protected function generate_valid_request(int $courseid, int $cmid, int $quizid): array {
        return [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'attemptids' => [1, 2, 3, 4, 5],
        ];
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\get_attempts_metadata::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            get_attempts_metadata::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\get_attempts_metadata::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            get_attempts_metadata::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\get_attempts_metadata::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_capability_requirement(): void {
        // Check that a user without the required capability is rejected.
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');

        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        get_attempts_metadata::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptids']
        );
    }

    /**
     * Tests that only web service tokens with read access to a job can request
     * attempt metadata
     *
     * @covers \quiz_archiver\external\get_attempts_metadata::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_wstoken_write_access_check(): void {
        // Create job.
        $this->resetAfterTest();
        $this->setAdminUser();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        ArchiveJob::create(
            '11000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            []
        );

        // Execute test call.
        $_GET['wstoken'] = 'INVALID-WS-TOKEN';
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $res = get_attempts_metadata::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptids'],
        );

        // Ensure that the access was denied.
        $this->assertSame(['status' => 'E_ACCESS_DENIED'], $res, 'Websertice token without access rights was falsely accepted');
    }

    /**
     * Test web service part of processing of a valid request
     *
     * @covers \quiz_archiver\external\get_attempts_metadata::execute
     *
     * @return void
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
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $_GET['wstoken'] = $wstoken;

        // Execute the request.
        $res = get_attempts_metadata::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptids'],
        );
        $this->assertSame('OK', $res['status'], 'The status should be OK.');
        $this->assertArrayHasKey('attempts', $res, 'The response should contain an attempts key.');
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_validation_data_provider
     * @covers \quiz_archiver\external\get_attempts_metadata::execute
     * @covers \quiz_archiver\external\get_attempts_metadata::validate_parameters
     *
     * @param string $invalidparameterkey Key of the parameter to invalidate
     * @return void
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
            $mocks->quiz->id
        );
        $_GET['wstoken'] = $wstoken;

        // Execute the request.
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessageMatches('/.*'.$invalidparameterkey.'.*/');
        get_attempts_metadata::execute(
            $invalidparameterkey == 'courseid' ? 0 : $r['courseid'],
            $invalidparameterkey == 'cmid' ? 0 : $r['cmid'],
            $invalidparameterkey == 'quizid' ? 0 : $r['quizid'],
            $r['attemptids']
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
        ];
    }

}
