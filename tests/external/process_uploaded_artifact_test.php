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
 * Tests for the process_uploaded_artifact external service
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;


use quiz_archiver\ArchiveJob;
use quiz_archiver\FileManager;

/**
 * Tests for the process_uploaded_artifact external service
 */
final class process_uploaded_artifact_test extends \advanced_testcase {

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
     * @param string $jobid Job ID
     * @param int $cmid Course module ID
     * @param int $userid User ID
     * @return array Valid request parameters
     */
    protected function generate_valid_request(string $jobid, int $cmid, int $userid): array {
        return [
            'jobid' => $jobid,
            'artifact_component' => FileManager::COMPONENT_NAME,
            'artifact_contextid' => \context_module::instance($cmid)->id,
            'artifact_userid' => $userid,
            'artifact_filearea' => FileManager::TEMP_FILEAREA_NAME,
            'artifact_filename' => 'artifact.tar.gz',
            'artifact_filepath' => '/',
            'artifact_itemid' => 1,
            'artifact_sha256sum' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
        ];
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            process_uploaded_artifact::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            process_uploaded_artifact::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_capability_requirement(): void {
        // Check that a user without the required capability is rejected.
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');

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

        // Execute test call.
        $_GET['wstoken'] = 'TEST-WS-TOKEN';
        $r = $this->generate_valid_request($job->get_jobid(), $mocks->quiz->cmid, $mocks->user->id);
        process_uploaded_artifact::execute(
            $r['jobid'],
            $r['artifact_component'],
            $r['artifact_contextid'],
            $r['artifact_userid'],
            $r['artifact_filearea'],
            $r['artifact_filename'],
            $r['artifact_filepath'],
            $r['artifact_itemid'],
            $r['artifact_sha256sum']
        );
    }

    /**
     * Tests that only web service tokens with write access to a job can trigger
     * artifact upload processing
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
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
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
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
        $r = $this->generate_valid_request($job->get_jobid(), $mocks->quiz->cmid, $mocks->user->id);
        $res = process_uploaded_artifact::execute(
            $r['jobid'],
            $r['artifact_component'],
            $r['artifact_contextid'],
            $r['artifact_userid'],
            $r['artifact_filearea'],
            $r['artifact_filename'],
            $r['artifact_filepath'],
            $r['artifact_itemid'],
            $r['artifact_sha256sum']
        );

        // Ensure that the access was denied.
        $this->assertSame(['status' => 'E_ACCESS_DENIED'], $res, 'Websertice token without access rights was falsely accepted');
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
     * @covers \quiz_archiver\external\process_uploaded_artifact::validate_parameters
     *
     * @param string|null $jobid Job ID
     * @param string|null $artifactcomponent Component name
     * @param int|null $artifactcontextid Context ID
     * @param int|null $artifactuserid User ID
     * @param string|null $artifactfilearea File area name
     * @param string|null $artifactfilename File name
     * @param string|null $artifactfilepath File path
     * @param int|null $artifactitemid Item ID
     * @param string|null $artifactsha256sum SHA256 checksum
     * @param bool $shouldfail Whether a failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(
        ?string $jobid,
        ?string $artifactcomponent,
        ?int    $artifactcontextid,
        ?int    $artifactuserid,
        ?string $artifactfilearea,
        ?string $artifactfilename,
        ?string $artifactfilepath,
        ?int    $artifactitemid,
        ?string $artifactsha256sum,
        bool    $shouldfail
    ): void {
        // Create mock quiz.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $base = $this->generate_valid_request('xxx', $mocks->quiz->cmid, $mocks->user->id);

        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        process_uploaded_artifact::execute(
            $jobid === null ? $base['jobid'] : $jobid,
            $artifactcomponent === null ? $base['artifact_component'] : $artifactcomponent,
            $artifactcontextid === null ? $base['artifact_contextid'] : $artifactcontextid,
            $artifactuserid === null ? $base['artifact_userid'] : $artifactuserid,
            $artifactfilearea === null ? $base['artifact_filearea'] : $artifactfilearea,
            $artifactfilename === null ? $base['artifact_filename'] : $artifactfilename,
            $artifactfilepath === null ? $base['artifact_filepath'] : $artifactfilepath,
            $artifactitemid === null ? $base['artifact_itemid'] : $artifactitemid,
            $artifactsha256sum === null ? $base['artifact_sha256sum'] : $artifactsha256sum
        );
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public static function parameter_data_provider(): array {
        // Create base data (no modification).
        $base = [
            "jobid" => null,
            "artifact_component" => null,
            "artifact_contextid" => null,
            "artifact_userid" => null,
            "artifact_filearea" => null,
            "artifact_filename" => null,
            "artifact_filepath" => null,
            "artifact_itemid" => null,
            "artifact_sha256sum" => null,
        ];

        // Define test datasets.
        return [
            'Valid' => array_merge($base, [
                'shouldfail' => false,
            ]),
            'Invalid jobid' => array_merge($base, [
                'jobid' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid artifact_component' => array_merge($base, [
                'artifact_component' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid artifact_filearea' => array_merge($base, [
                'artifact_filearea' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid artifact_filename' => array_merge($base, [
                'artifact_filename' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid artifact_filepath' => array_merge($base, [
                'artifact_filepath' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid artifact_sha256sum' => array_merge($base, [
                'artifact_sha256sum' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
        ];
    }

    /**
     * Test that completed jobs reject further artifact uploads
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_rejection_of_artifacts_for_complete_jobs(): void {
        // Create job.
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
            [],
            ArchiveJob::STATUS_FINISHED
        );

        // Execute test call.
        $_GET['wstoken'] = 'TEST-WS-TOKEN';
        $r = $this->generate_valid_request($job->get_jobid(), $mocks->quiz->cmid, $mocks->user->id);
        $this->assertSame(['status' => 'E_NO_ARTIFACT_UPLOAD_EXPECTED'], process_uploaded_artifact::execute(
            $r['jobid'],
            $r['artifact_component'],
            $r['artifact_contextid'],
            $r['artifact_userid'],
            $r['artifact_filearea'],
            $r['artifact_filename'],
            $r['artifact_filepath'],
            $r['artifact_itemid'],
            $r['artifact_sha256sum']
        ));
    }

    /**
     * Test that missing files are reported correctly
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_invalid_file_metadata(): void {
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
            'TEST-WS-TOKEN',
            [],
            []
        );

        // Gain access.
        $_GET['wstoken'] = 'TEST-WS-TOKEN';
        $this->setAdminUser();

        // Execute test call.
        $r = $this->generate_valid_request($job->get_jobid(), $mocks->quiz->cmid, $mocks->user->id);
        $this->assertSame(['status' => 'E_UPLOADED_ARTIFACT_NOT_FOUND'], process_uploaded_artifact::execute(
            $r['jobid'],
            $r['artifact_component'],
            $r['artifact_contextid'],
            $r['artifact_userid'],
            $r['artifact_filearea'],
            $r['artifact_filename'],
            $r['artifact_filepath'],
            $r['artifact_itemid'],
            $r['artifact_sha256sum']
        ));
    }

    /**
     * Tests rejection of artifacts with mismatching checksums
     *
     * @covers \quiz_archiver\external\process_uploaded_artifact::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     * @throws \stored_file_creation_exception
     */
    public function test_rejection_of_artifacts_with_checksum_mismatch(): void {
        // Create job and draft artifact.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            '40000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            []
        );
        $artifact = $this->getDataGenerator()->create_draft_file('testartifact.tar.gz');

        // Gain access.
        $_GET['wstoken'] = 'TEST-WS-TOKEN';
        $this->setAdminUser();

        // Execute test call.
        $r = $this->generate_valid_request($job->get_jobid(), $mocks->quiz->cmid, $mocks->user->id);
        $this->assertSame(['status' => 'E_ARTIFACT_CHECKSUM_INVALID'], process_uploaded_artifact::execute(
            $r['jobid'],
            $artifact->get_component(),
            $artifact->get_contextid(),
            (int) $artifact->get_userid(),  // Int cast is required since Moodle likes to return strings here...
            $artifact->get_filearea(),
            $artifact->get_filename(),
            $artifact->get_filepath(),
            $artifact->get_itemid(),
            '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef'
        ));
    }

}
