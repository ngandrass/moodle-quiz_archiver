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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the get_backup_status external service
 */
final class get_backup_status_test extends \advanced_testcase {

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
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\get_backup_status::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function test_capability_requirement(): void {
        // Create job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        // Check that a user without the required capability is rejected.
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');
        get_backup_status::execute($job->get_jobid(), 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15');
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\get_backup_status::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            get_backup_status::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\get_backup_status::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            get_backup_status::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     * @covers       \quiz_archiver\external\get_backup_status::execute
     * @covers       \quiz_archiver\external\get_backup_status::validate_parameters
     *
     * @param string|null $jobid Job ID to check
     * @param string|null $backupid Backup ID to check
     * @param bool $shouldfail Whether a failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(
        ?string $jobid,
        ?string $backupid,
        bool    $shouldfail
    ): void {
        // Gain webservice permission.
        $this->setAdminUser();

        // Create mock quiz.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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
        $_GET['wstoken'] = 'TEST-WS-TOKEN';

        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        get_backup_status::execute(
            $jobid === null ? $job->get_jobid() : $jobid,
            $backupid === null ? 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15' : $backupid
        );
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function parameter_data_provider(): array {
        return [
            'Valid' => array_merge([
                'jobid' => null,
                'backupid' => null,
                'shouldfail' => false,
            ]),
            'Invalid jobid' => array_merge([
                'jobid' => '<a href="localhost">Foo</a>',
                'backupid' => null,
                'shouldfail' => true,
            ]),
            'Invalid backupid' => array_merge([
                'jobid' => null,
                'backupid' => '<a href="localhost">Bar</a>',
                'shouldfail' => true,
            ]),
        ];
    }

    /**
     * Test wstoken validation
     *
     * @covers \quiz_archiver\external\get_backup_status::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_wstoken_access_check(): void {
        // Gain webservice permission.
        $this->setAdminUser();

        // Create job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        // Check that correct wstoken allows access.
        $_GET['wstoken'] = 'TEST-WS-TOKEN-VALID';
        $this->assertSame(
            ['status' => 'E_BACKUP_NOT_FOUND'],
            get_backup_status::execute($job->get_jobid(), 'f1d2d2f924e986ac86fdf7b36c94bcdf32beec15'),
            'Valid wstoken was falsely rejected'
        );

        // Check that incorrect wstoken is rejected.
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
     * @covers \quiz_archiver\external\get_backup_status::execute
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
