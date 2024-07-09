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
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;


use quiz_archiver\ArchiveJob;
use quiz_archiver\FileManager;

/**
 * Tests for the process_uploaded_artifact external service
 */
class process_uploaded_artifact_test extends \advanced_testcase {

    /**
     * Generates a mock quiz to use in the tests
     *
     * @return \stdClass Created mock objects
     */
    protected function generate_mock_quiz(): \stdClass {
        // Create course, course module and quiz.
        $this->resetAfterTest();

        // Prepare user and course.
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
     * Test that users without the required capabilities are rejected
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
        $mocks = $this->generate_mock_quiz();
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
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     *
     * @param string $jobid Job ID
     * @param string $artifactcomponent Component name
     * @param int $artifactcontextid Context ID
     * @param int $artifactuserid User ID
     * @param string $artifactfilearea File area name
     * @param string $artifactfilename File name
     * @param string $artifactfilepath File path
     * @param int $artifactitemid Item ID
     * @param string $artifactsha256sum SHA256 checksum
     * @param bool $shouldfail Whether a failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(
        string $jobid,
        string $artifactcomponent,
        int    $artifactcontextid,
        int    $artifactuserid,
        string $artifactfilearea,
        string $artifactfilename,
        string $artifactfilepath,
        int    $artifactitemid,
        string $artifactsha256sum,
        bool   $shouldfail
    ): void {
        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        process_uploaded_artifact::execute(
            $jobid,
            $artifactcomponent,
            $artifactcontextid,
            $artifactuserid,
            $artifactfilearea,
            $artifactfilename,
            $artifactfilepath,
            $artifactitemid,
            $artifactsha256sum
        );
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public function parameter_data_provider(): array {
        $mocks = $this->generate_mock_quiz();
        $base = $this->generate_valid_request('xxx', $mocks->quiz->cmid, $mocks->user->id);
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
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_rejection_of_artifacts_for_complete_jobs(): void {
        // Create job.
        $mocks = $this->generate_mock_quiz();
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
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_invalid_file_metadata(): void {
        // Create job.
        $mocks = $this->generate_mock_quiz();
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

}
