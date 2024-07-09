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
 * Tests for the FileManager class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use context_user;

/**
 * Tests for the FileManager class
 */
final class filemanager_test extends \advanced_testcase {

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

        return (object) [
            'user' => $user,
            'course' => $course,
            'quiz' => $quiz,
            'attempts' => [
                (object) ['userid' => 1, 'attemptid' => 1],
                (object) ['userid' => 2, 'attemptid' => 42],
                (object) ['userid' => 3, 'attemptid' => 1337],
            ],
        ];
    }

    /**
     * Imports the reference quiz artifact from the test fixtures directory into
     * the a Moodle stored_file residing inside a users draft filearea.
     *
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function import_reference_quiz_artifact(): \stored_file {
        $this->resetAfterTest();
        $ctx = context_user::instance($this->getDataGenerator()->create_user()->id);

        return get_file_storage()->create_file_from_pathname([
            'contextid'    => $ctx->id,
            'component'    => 'user',
            'filearea'     => 'draft',
            'itemid'       => 0,
            'filepath'     => "/",
            'filename'     => 'reference_quiz_artifact.tar.gz',
            'timecreated'  => time(),
            'timemodified' => time(),
        ], __DIR__.'/fixtures/referencequiz-artifact.tar.gz');
    }

    /**
     * Generates a dummy draft file, stored in the draft filearea of a user.
     *
     * @param string $filename Name of the file to create
     * @param string $filearea Filearea to store the file in
     * @return \stored_file The created file handle
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function generate_draft_file(string $filename, string $filearea = 'draft'): \stored_file {
        $this->resetAfterTest();
        $ctx = context_user::instance($this->getDataGenerator()->create_user()->id);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => 'user',
                'filearea'     => $filearea,
                'itemid'       => 0,
                'filepath'     => "/",
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do '.
            'eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

    /**
     * Generates a dummy file inside the temp filearea of this plugin.
     *
     * @param string $filename
     * @param int $expiry
     * @return \stored_file
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function generate_temp_file(string $filename, int $expiry): \stored_file {
        $this->resetAfterTest();
        $ctx = context_user::instance($this->getDataGenerator()->create_user()->id);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => FileManager::COMPONENT_NAME,
                'filearea'     => FileManager::TEMP_FILEAREA_NAME,
                'itemid'       => 0,
                'filepath'     => '/'.$expiry.'/',
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do '.
            'eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

    /**
     * Tests the generation of file paths based on context data
     *
     * @covers \quiz_archiver\FileManager::get_file_path
     *
     * @dataProvider file_path_generator_data_provider
     *
     * @param int $courseid
     * @param int $cmid
     * @param int $quizid
     * @param string $expectedpath
     * @return void
     */
    public function test_file_path_generator(int $courseid, int $cmid, int $quizid, string $expectedpath): void {
        $this->assertEquals($expectedpath, FileManager::get_file_path($courseid, $cmid, $quizid));
    }

    /**
     * Data provider for test_file_path_generator
     *
     * @return array Test data for test_file_path_generator
     */
    public function file_path_generator_data_provider(): array {
        return [
            'Full valid path' => [
                1,
                2,
                3,
                '/1/2/3/',
            ],
            'Empty path' => [
                0,
                0,
                0,
                '/',
            ],
            'Only course' => [
                1,
                0,
                0,
                '/1/',
            ],
            'Only course and cm' => [
                1,
                2,
                0,
                '/1/2/',
            ],
            'Only course and quiz' => [
                1,
                0,
                3,
                '/1/',
            ],
            'Only cm' => [
                0,
                2,
                0,
                '/',
            ],
            'Only cm and quiz' => [
                0,
                2,
                3,
                '/',
            ],
            'Only quiz' => [
                0,
                0,
                3,
                '/',
            ],
        ];
    }

    /**
     * Test artifact storing and retrieval
     *
     * @covers \quiz_archiver\FileManager::__construct
     * @covers \quiz_archiver\FileManager::store_uploaded_artifact
     * @covers \quiz_archiver\FileManager::get_stored_artifacts
     *
     * @return void
     * @throws \coding_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_storing(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $draftfile = $this->generate_draft_file('testfile.tar.gz');
        $draftfilehash = $draftfile->get_contenthash();

        // Store draftfile as artifact.
        $storedfile = $fm->store_uploaded_artifact($draftfile);
        $this->assertInstanceOf(\stored_file::class, $storedfile, 'Invalid storage handle returned');
        $this->assertEquals($draftfilehash, $storedfile->get_contenthash(), 'Stored file hash does not match draft file hash');
        $this->assertEmpty(get_file_storage()->get_file_by_id($draftfile->get_id()), 'Draft file was deleted');

        // Retrieve artifact.
        $storedfiles = $fm->get_stored_artifacts();
        $this->assertEquals($storedfile, array_shift($storedfiles), 'Stored file handle does not match retrieved file handle');
    }

    /**
     * Test that only uploaded draftfiles are stored and others are rejected
     *
     * @covers \quiz_archiver\FileManager::__construct
     * @covers \quiz_archiver\FileManager::store_uploaded_artifact
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_storing_invalid_file(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $invalidfile = $this->generate_draft_file('invalidfile.tar.gz', 'invalidarea');

        $this->expectException(\file_exception::class);
        $this->expectExceptionMessageMatches('/draftfile/');
        $fm->store_uploaded_artifact($invalidfile);
    }

    /**
     * Tests the hash generation for a valid stored_file
     *
     * @covers \quiz_archiver\FileManager::hash_file
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_hash_valid_file(): void {
        $file = $this->generate_draft_file('testartifact.tar.gz');
        $defaulthash = FileManager::hash_file($file);
        $this->assertNotEmpty($defaulthash, 'Default hash is empty');
        $this->assertSame(64, strlen($defaulthash), 'Default hash length is not 64 bytes, as expected from SHA256');

        $sha256hash = FileManager::hash_file($file, 'sha256');
        $this->assertEquals($defaulthash, $sha256hash, 'Explicitly as SHA256 selected hash does not match default hash');
    }

    /**
     * Tests hash generation using an invalid hash algorithm
     *
     * @covers \quiz_archiver\FileManager::hash_file
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_hash_file_invalid_algorithm(): void {
        $file = $this->generate_draft_file('testartifact.tar.gz');
        $this->assertNull(FileManager::hash_file($file, 'invalid-algorithm'), 'Invalid algorithm did not return null');
    }

    /**
     * Tests sending a TSP query as a virtual file
     *
     * @runInSeparateProcess
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_send_virtual_file_tsp_query(): void {
        global $CFG, $DB;

        if ($CFG->branch < 404) {
            // @codingStandardsIgnoreLine
            $this->markTestSkipped('This test requires Moodle 4.4 or higher. PHPUnit process isolation does not work properly with older versions.');
        }

        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000001',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_FINISHED
        );

        // Generate mock TSP data.
        $DB->insert_record(TSPManager::TSP_TABLE_NAME, [
            'jobid' => $job->get_id(),
            'timecreated' => time(),
            'server' => 'localhost',
            'timestampquery' => 'tspquery1',
            'timestampreply' => 'tspreply1',
        ]);

        // Try to send file.
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            "/{$mocks->course->id}/{$mocks->quiz->cmid}/{$mocks->quiz->id}/{$job->get_id()}/".FileManager::TSP_DATA_QUERY_FILENAME
        );
    }

    /**
     * Tests sending a TSP reply as a virtual file
     *
     * @runInSeparateProcess
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_send_virtual_file_tsp_reply(): void {
        global $CFG, $DB;

        if ($CFG->branch < 404) {
            // @codingStandardsIgnoreLine
            $this->markTestSkipped('This test requires Moodle 4.4 or higher. PHPUnit process isolation does not work properly with older versions.');
        }

        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000002',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_FINISHED
        );

        // Generate mock TSP data.
        $DB->insert_record(TSPManager::TSP_TABLE_NAME, [
            'jobid' => $job->get_id(),
            'timecreated' => time(),
            'server' => 'localhost',
            'timestampquery' => 'tspquery2',
            'timestampreply' => 'tspreply2',
        ]);

        // Try to send file.
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            "/{$mocks->course->id}/{$mocks->quiz->cmid}/{$mocks->quiz->id}/{$job->get_id()}/".FileManager::TSP_DATA_REPLY_FILENAME
        );
    }

    /**
     * Tests sending a virtual TSP file for a relativepath that does not match
     * the information of the respective job.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_send_virtual_files_tsp_invalid_job(): void {
        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000003',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_UNKNOWN
        );
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test invalid job.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/resource id/');
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            "/{$mocks->course->id}/{$mocks->quiz->cmid}/0/{$job->get_id()}/".FileManager::TSP_DATA_REPLY_FILENAME
        );
    }

    /**
     * Tests sending a virtual TSP file for a job that has no TSP data.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_send_virtual_files_tsp_unsigned_job(): void {
        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000004',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_FINISHED
        );
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test unsigned job.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No TSP data found/');
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            "/{$mocks->course->id}/{$mocks->quiz->cmid}/{$mocks->quiz->id}/{$job->get_id()}/".FileManager::TSP_DATA_REPLY_FILENAME
        );
    }

    /**
     * Tests sending virtual file from invalid filearea.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_send_virtual_files_invalid_filearea(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test invalid filearea.
        $this->expectException(\InvalidArgumentException::class);
        $fm->send_virtual_file('invalid', '/invalid');
    }

    /**
     * Tests sending virtual file from invalid path.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_send_virtual_files_invalid_path(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test invalid path.
        $this->expectException(\InvalidArgumentException::class);
        $fm->send_virtual_file(FileManager::TSP_DATA_FILEAREA_NAME, '../../42/secrets');
    }

    /**
     * Tests sending virtual file with invalid jobid.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_send_virtual_files_invalid_jobid(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test invalid job-id.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/jobid/');
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            '/0/0/0/invalidjobid/'.FileManager::TSP_DATA_REPLY_FILENAME
        );
    }

    /**
     * Tests sending virtual file for non-existing job.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_send_virtual_files_missing_job(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test missing job.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not found/');
        $fm->send_virtual_file(
            FileManager::TSP_DATA_FILEAREA_NAME,
            '/1/2/3/9999999/'.FileManager::TSP_DATA_REPLY_FILENAME
        );
    }

    /**
     * Tests sending virtual file with invalid filename.
     *
     * @covers \quiz_archiver\FileManager::send_virtual_file
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_send_virtual_files_invalid_filename(): void {
        $mocks = $this->generate_mock_quiz();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Test missing job.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid filename/');
        $fm->send_virtual_file(FileManager::TSP_DATA_FILEAREA_NAME, '/0/0/0/0/secrets');
    }

    /**
     * Test extracting the data of a single attempt from a job artifact file.
     *
     * @covers \quiz_archiver\FileManager::extract_attempt_data_from_artifact
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_extract_attempt_data_from_artifact(): void {
        // Prepare a finished archive job that has a valid artifact file.
        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000042',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_FINISHED
        );

        $draftartifact = $this->import_reference_quiz_artifact();
        $attemptid = 13775;

        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $fm->store_uploaded_artifact($draftartifact);
        $storedartifacts = $fm->get_stored_artifacts();
        $storedartifact = array_shift($storedartifacts);

        // Extract userdata from artifact into temporary stored_file.
        $tempfile = $fm->extract_attempt_data_from_artifact($storedartifact, $job->get_id(), $attemptid);
        $this->assertNotEmpty($tempfile, 'No temp file was returned');
        $this->assertNotEmpty($tempfile->get_contenthash(), 'Temp file has no valid content hash');
        $this->assertTrue($tempfile->get_filesize() > 1024, 'Temp file is too small to be valid');
    }

    /**
     * Test extracting a non-existing attempt from an artifact file.
     *
     * @covers \quiz_archiver\FileManager::extract_attempt_data_from_artifact
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_extract_attempt_data_for_nonexisting_attemptid(): void {
        // Prepare a finished archive job that has a valid artifact file.
        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000021',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_FINISHED
        );
        $draftartifact = $this->import_reference_quiz_artifact();
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $fm->store_uploaded_artifact($draftartifact);
        $storedartifacts = $fm->get_stored_artifacts();
        $storedartifact = array_shift($storedartifacts);

        // Extract userdata from artifact into temporary stored_file.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/Attempt not found/');
        $fm->extract_attempt_data_from_artifact($storedartifact, $job->get_id(), 9999999);
    }

    /**
     * Test extracting userdata from an invalid artifact file.
     *
     * @covers \quiz_archiver\FileManager::extract_attempt_data_from_artifact
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_extract_attempt_data_from_invalid_artifact(): void {
        // Prepare an unfinished archive job that has no artifact file.
        $mocks = $this->generate_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000043',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_RUNNING
        );
        $fm = new FileManager($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);

        // Attempt to extract data from nonexisting artifact.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessageMatches('/Error processing archive file/');
        $fm->extract_attempt_data_from_artifact($this->generate_draft_file('not-an-artifact.tar.gz'), $job->get_id(), 1337);
    }

    /**
     * Tests cleanup of temporary files produced by the attempt data extraction routine.
     *
     * @covers \quiz_archiver\FileManager::cleanup_temp_files
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_cleanup_temp_files(): void {
        // Prepare tempfiles.
        $overduetempfiles = [
            $this->generate_temp_file('tempfile1.tar.gz', 0),
            $this->generate_temp_file('tempfile2.tar.gz', 0),
            $this->generate_temp_file('tempfile3.tar.gz', 0),
        ];
        $activetempfiles = [
            $this->generate_temp_file('tempfile4.tar.gz', time() + 3600),
            $this->generate_temp_file('tempfile5.tar.gz', time() + 3600),
            $this->generate_temp_file('tempfile6.tar.gz', time() + 3600),
        ];

        // Perform cleanup.
        FileManager::cleanup_temp_files();

        foreach ($overduetempfiles as $file) {
            $this->assertEmpty(get_file_storage()->get_file_by_id($file->get_id()), 'Temp file was not deleted');
        }

        foreach ($activetempfiles as $file) {
            $this->assertNotEmpty(get_file_storage()->get_file_by_id($file->get_id()), 'Active temp file was falsely deleted');
        }
    }

}
