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
 * Tests for the update_job_status external service
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the update_job_status external service
 */
class update_job_status_test extends \advanced_testcase {

    /**
     * Generates a mock quiz to use in the tests
     *
     * @return \stdClass Created mock objects
     */
    protected function generatemockquiz(): \stdClass {
        // Create course, course module and quiz
        $this->resetAfterTest();

        // Prepare user and course
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'grade' => 100.0,
            'sumgrades' => 100,
        ]);

        return (object)[
            'user' => $user,
            'course' => $course,
            'quiz' => $quiz,
        ];
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_capability_requirement(): void {
        // Create mock quiz and job
        $mocks = $this->generatemockquiz();
        $job = ArchiveJob::create(
            '00000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            [],
        );
        $_GET['wstoken'] = 'TEST-WS-TOKEN';

        // Check that a user without the required capability is rejected
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');
        update_job_status::execute($job->get_jobid(), ArchiveJob::STATUS_UNINITIALIZED);
    }

    /**
     * Tests that webservice tokens are validated against the requested job
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_wstoken_validation(): void {
        // Gain access to webservice
        $this->setAdminUser();

        // Create mock quiz and job
        $mocks = $this->generatemockquiz();
        $job = ArchiveJob::create(
            '00000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-VALID',
            [],
            [],
        );

        // Check that a valid token is accepted
        $_GET['wstoken'] = 'TEST-WS-TOKEN-VALID';
        $this->assertSame(
            ['status' => 'OK'],
            update_job_status::execute($job->get_jobid(), ArchiveJob::STATUS_UNINITIALIZED),
            'Valid token was rejected'
        );

        // Check that an invalid token is rejected
        $_GET['wstoken'] = 'TEST-WS-TOKEN-INVALID';
        $this->assertSame(
            ['status' => 'E_ACCESS_DENIED'],
            update_job_status::execute($job->get_jobid(), ArchiveJob::STATUS_UNINITIALIZED),
            'Invalid token was accepted'
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     *
     * @param string $jobid Raw jobid parameter
     * @param string $status Raw status parameter
     * @param bool $shouldFail Whether a failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(string $jobid, string $status, bool $shouldfail): void {
        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        update_job_status::execute($jobid, $status);
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public function parameter_data_provider(): array {
        return [
            'Valid' => ['jobid' => '00000000-1234-5678-abcd-ef4242424242', 'status' => ArchiveJob::STATUS_UNINITIALIZED, 'shouldFail' => false],
            'Invalid jobid' => ['jobid' => '<a href="localhost">Foo</a>', 'status' => ArchiveJob::STATUS_UNINITIALIZED, 'shouldFail' => true],
            'Invalid status' => ['jobid' => '00000000-1234-5678-abcd-ef4242424242', 'status' => '<a href="localhost">Bar</a>', 'shouldFail' => true],
            'Invalid jobid and status' => ['jobid' => '<a href="localhost">Foo</a>', 'status' => '<a href="localhost">Bar</a>', 'shouldFail' => true],
        ];
    }

    /**
     * Test updating a valid job
     *
     * @dataProvider job_status_data_provider
     *
     * @param string $originStatus Status to transition from
     * @param string $targetStatus Status to transition to
     * @param array $expected Expected result
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_update_job_status(string $originstatus, string $targetstatus, array $expected) {
        // Gain privileges
        $this->setAdminUser();
        $_GET['wstoken'] = 'TEST-WS-TOKEN';

        // Create mock quiz and job
        $mocks = $this->generatemockquiz();
        $job = ArchiveJob::create(
            '00000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            [],
            $originstatus
        );

        // Ensure job is in the expected state
        $this->assertSame($originstatus, $job->get_status());

        // Execute the external function and check the result
        $result = update_job_status::execute(
            $job->get_jobid(),
            $targetstatus
        );
        $this->assertSame($expected, $result, 'Invalid webservice answer');
    }

    /**
     * Data provider for test_update_job_status
     *
     * @return array[] Test data
     */
    public function job_status_data_provider(): array {
        return [
            'Status: UNKNOWN -> UNINITIALIZED' => ['originStatus' => ArchiveJob::STATUS_UNKNOWN, 'targetStatus' => ArchiveJob::STATUS_UNINITIALIZED, 'expected' => ['status' => 'OK']],
            'Status: UNINITIALIZED -> AWAITING_PROCESSING' => ['originStatus' => ArchiveJob::STATUS_UNINITIALIZED, 'targetStatus' => ArchiveJob::STATUS_AWAITING_PROCESSING, 'expected' => ['status' => 'OK']],
            'Status: UNINITIALIZED -> FINISHED' => ['originStatus' => ArchiveJob::STATUS_UNINITIALIZED, 'targetStatus' => ArchiveJob::STATUS_FINISHED, 'expected' => ['status' => 'OK']],
            'Status: AWAITING_PROCESSING -> RUNNING' => ['originStatus' => ArchiveJob::STATUS_AWAITING_PROCESSING, 'targetStatus' => ArchiveJob::STATUS_RUNNING, 'expected' => ['status' => 'OK']],
            'Status: RUNNING -> FINISHED' => ['originStatus' => ArchiveJob::STATUS_RUNNING, 'targetStatus' => ArchiveJob::STATUS_FINISHED, 'expected' => ['status' => 'OK']],
            'Status: RUNNING -> FAILED' => ['originStatus' => ArchiveJob::STATUS_RUNNING, 'targetStatus' => ArchiveJob::STATUS_FAILED, 'expected' => ['status' => 'OK']],
            'Status: RUNNING -> TIMEOUT' => ['originStatus' => ArchiveJob::STATUS_RUNNING, 'targetStatus' => ArchiveJob::STATUS_TIMEOUT, 'expected' => ['status' => 'OK']],
            'Status: FINISHED -> DELETED' => ['originStatus' => ArchiveJob::STATUS_FINISHED, 'targetStatus' => ArchiveJob::STATUS_DELETED, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
            'Status: FINISHED -> RUNNING' => ['originStatus' => ArchiveJob::STATUS_FINISHED, 'targetStatus' => ArchiveJob::STATUS_RUNNING, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
            'Status: FINISHED -> FAILED' => ['originStatus' => ArchiveJob::STATUS_FINISHED, 'targetStatus' => ArchiveJob::STATUS_FAILED, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
            'Status: FINISHED -> TIMEOUT' => ['originStatus' => ArchiveJob::STATUS_FINISHED, 'targetStatus' => ArchiveJob::STATUS_TIMEOUT, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
            'Status: FINISHED -> UNINITIALIZED' => ['originStatus' => ArchiveJob::STATUS_FINISHED, 'targetStatus' => ArchiveJob::STATUS_UNINITIALIZED, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
            'Status: FAILED -> DELETED' => ['originStatus' => ArchiveJob::STATUS_FAILED, 'targetStatus' => ArchiveJob::STATUS_DELETED, 'expected' => ['status' => 'E_JOB_ALREADY_COMPLETED']],
        ];
    }

}
