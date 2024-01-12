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
 * Tests for the TSPManager class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;


use context_course;

/**
 * Tests for the TSPManager class
 */
class TSPManager_test extends \advanced_testcase {

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

        return (object) [
            'user' => $user,
            'course' => $course,
            'quiz' => $quiz,
        ];
    }

    /**
     * Generates a dummy artifact file, stored in the context of the given course.
     *
     * @param int $courseid ID of the course to store the file in
     * @param int $cmid ID of the course module to store the file in
     * @param int $quizid ID of the quiz to store the file in
     * @param string $filename Name of the file to create
     * @return \stored_file The created file handle
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    protected function generateArtifactFile(int $courseid, int $cmid, int $quizid, string $filename): \stored_file {
        $this->resetAfterTest();
        $ctx = context_course::instance($courseid);

        return get_file_storage()->create_file_from_string(
            [
                'contextid'    => $ctx->id,
                'component'    => FileManager::COMPONENT_NAME,
                'filearea'     => FileManager::ARTIFACTS_FILEAREA_NAME,
                'itemid'       => 0,
                'filepath'     => "/{$courseid}/{$cmid}/{$quizid}/",
                'filename'     => $filename,
                'timecreated'  => time(),
                'timemodified' => time(),
            ],
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
        );
    }

    /**
     * Creates a TSPManager that uses a mocked TimeStampProtocolClient
     *
     * @param ArchiveJob $job Job to create the TSPManager for
     * @param string $dummy_server Dummy TSP server URL
     * @param string $dummy_query Dummy TSP query data
     * @param string $dummy_reply Dummy TSP reply data
     * @return TSPManager
     */
    protected function createMockTSPManager(
        ArchiveJob $job,
        string $dummy_server = 'localhost',
        string $dummy_query = 'tsp-dummy-query',
        string $dummy_reply = 'tsp-dummy-reply-0123456789abcdef'
    ): TSPManager {
        // Prepare TimeStampProtocolClient that returns dummy data
        $tspClientMock = $this->getMockBuilder(TimeStampProtocolClient::class)
            ->onlyMethods(['sign'])
            ->setConstructorArgs([$dummy_server])
            ->getMock();
        $tspClientMock->expects($this->any())
            ->method('sign')
            ->willReturn([
                'query' => $dummy_query,
                'reply' => $dummy_reply,
            ]);

        // Create TSPManager that uses the mocked TimeStampProtocolClient
        $tspManager = $this->getMockBuilder(TSPManager::class)
            ->onlyMethods(['getTimestampProtocolClient'])
            ->setConstructorArgs([$job])
            ->getMock();
        $tspManager->expects($this->any())
            ->method('getTimestampProtocolClient')
            ->willReturn($tspClientMock);

        return $tspManager;
    }

    /**
     * Tests signing of valid artifacts using TSP
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_tsp_timestamp(): void {
        // Prepare plugin settings
        set_config('tsp_server_url', 'localhost', 'quiz_archiver');
        set_config('tsp_enable', true, 'quiz_archiver');

        // Generate job with artifact
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
            [],
            ArchiveJob::STATUS_FINISHED
        );

        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $sha256dummy = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256dummy);

        // Ensure that artifact was not yet signed
        $this->assertFalse($job->TSPManager()->has_tsp_timestamp(), 'Artifact was detected as signed without it being signed');

        // Try signing the artifact using TSP
        $tspManager = $this->createMockTSPManager($job);
        $tspManager->timestamp();
        $this->assertTrue($tspManager->has_tsp_timestamp(), 'Artifact was not detected as signed after signing it');

        // Ensure that the TSP data was stored correctly
        $this->assertEquals('tsp-dummy-query', $tspManager->get_tsp_data()->query, 'TSP query was not stored correctly');
        $this->assertEquals('tsp-dummy-reply-0123456789abcdef', $tspManager->get_tsp_data()->reply, 'TSP reply was not stored correctly');
        $this->assertEquals('localhost', $tspManager->get_tsp_data()->server, 'TSP server URL was not stored correctly');
    }

    /**
     * Tests deletion of TSP data
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_delete_tsp_data(): void {
        // Prepare plugin settings
        set_config('tsp_server_url', 'localhost', 'quiz_archiver');
        set_config('tsp_enable', true, 'quiz_archiver');

        // Generate job with artifact
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
            [],
            ArchiveJob::STATUS_FINISHED
        );

        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $sha256dummy = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256dummy);

        // Sign the artifact using TSP
        $tspManager = $this->createMockTSPManager($job);
        $tspManager->timestamp();
        $this->assertTrue($tspManager->has_tsp_timestamp(), 'Artifact was not detected as signed after signing it');

        // Delete the TSP data
        $tspManager->delete_tsp_data();
        $this->assertFalse($tspManager->has_tsp_timestamp(), 'Artifact was detected as signed after deleting the TSP data');
    }

    /**
     * Tests error handling when trying to sign non-existing artifacts
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_signing_invalid_artifact(): void {
        // Prepare plugin settings
        set_config('tsp_server_url', 'localhost', 'quiz_archiver');
        set_config('tsp_enable', true, 'quiz_archiver');

        // Generate job without artifact
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '30000000-1234-5678-abcd-ef4242424242',
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

        // Try signing the artifact using TSP
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(get_string('archive_signing_failed_no_artifact', 'quiz_archiver'));
        $this->createMockTSPManager($job)->timestamp();
    }

    /**
     * Tests error handling when trying to sign an artifact while TSP is globally disabled
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_signing_disabled(): void {
        // Ensure signing is disabled
        set_config('tsp_enable', false, 'quiz_archiver');

        // Generate job with artifact
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '40000000-1234-5678-abcd-ef4242424242',
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

        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $sha256sum = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256sum);

        // Try signing the artifact using TSP
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(get_string('archive_signing_failed_tsp_disabled', 'quiz_archiver'));
        $this->createMockTSPManager($job)->timestamp();
    }

}