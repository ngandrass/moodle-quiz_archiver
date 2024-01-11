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
 * Tests for the get_backup_status external service
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tests\classes\external;


use quiz_archiver\ArchiveJob;
use quiz_archiver\external\get_backup_status;

/**
 * Tests for the get_backup_status external service
 */
class get_backup_status_test extends \advanced_testcase {

    /**
     * Generates a mock quiz to use in the tests
     *
     * @return \stdClass Created mock objects
     */
    protected function generateMockQuiz(): \stdClass {
        // Create course, course module and quiz
        $this->resetAfterTest();

        // Prepare user and course
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'grade' => 100.0,
            'sumgrades' => 100
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
     * @throws \DOMException
     */
    public function test_capability_requirement(): void {
        // Create job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '10000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            []
        );
        $_GET['wstoken'] = 'TEST-WS-TOKEN';

        // Check that a user without the required capability is rejected
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');
        get_backup_status::execute($job->get_jobid(), 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15');
    }


    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     *
     * @param string $jobid Job ID
     * @param string $backupid Backup ID
     * @param bool $shouldFail Whether a failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(
        string $jobid,
        string $backupid,
        bool $shouldFail
    ): void {
        if ($shouldFail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        try {
            get_backup_status::execute($jobid, $backupid);
        } catch (\dml_exception $e) {}
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function parameter_data_provider(): array {
        // Create job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '20000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            []
        );
        $base = [
            'jobid' => $job->get_jobid(),
            'backupid' => 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15',
        ];

        return [
            'Valid' => array_merge($base, ['shouldFail' => false]),
            'Invalid jobid' => array_merge($base, ['jobid' => '<a href="localhost">Foo</a>', 'shouldFail' => true]),
            'Invalid backupid' => array_merge($base, ['backupid' => '<a href="localhost">Bar</a>', 'shouldFail' => true]),
        ];
    }

    /**
     * Test wstoken validation
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_wstoken_access_check(): void {
        // Gain webservice permission
        $this->setAdminUser();

        // Create job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '30000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-VALID',
            [],
            []
        );

        // Check that correct wstoken allows access
        $_GET['wstoken'] = 'TEST-WS-TOKEN-VALID';
        $this->assertSame(
            ['status' => 'E_BACKUP_NOT_FOUND'],
            get_backup_status::execute($job->get_jobid(), 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15'),
            'Valid wstoken was falsely rejected'
        );

        // Check that incorrect wstoken is rejected
        $_GET['wstoken'] = 'TEST-WS-TOKEN-INVALID';
        $this->assertSame(
            ['status' => 'E_ACCESS_DENIED'],
            get_backup_status::execute($job->get_jobid(), 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15'),
            'Invalid wstoken was falsely accepted'
        );
    }

    /**
     * Test that invalid jobs return no status
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_invalid_job(): void {
        $this->assertSame(
            ['status' => 'E_JOB_NOT_FOUND'],
            get_backup_status::execute('00000000-0000-0000-0000-000000000000', 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15'),
            'Invalid job ID should return E_JOB_NOT_FOUND'
        );
    }

}
