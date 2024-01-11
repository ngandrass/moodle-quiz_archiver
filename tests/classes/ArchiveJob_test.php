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
 * Tests for the ArchiveJob class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use context_course;
use context_system;

/**
 * Tests for the ArchiveJob class
 */
class ArchiveJob_test extends \advanced_testcase {

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
            'attempts' => [
                (object) ['userid' => 1, 'attemptid' => 1],
                (object) ['userid' => 2, 'attemptid' => 42],
                (object) ['userid' => 3, 'attemptid' => 1337],
            ],
            'settings' => [
                'num_attempts' => 3,
                'export_attempts' => 1,
                'export_report_section_header' => 1,
                'export_report_section_quiz_feedback' => 1,
                'export_report_section_question' => 1,
                'export_report_section_question_feedback' => 0,
                'export_report_section_general_feedback' => 1,
                'export_report_section_rightanswer' => 0,
                'export_report_section_history' => 1,
                'export_report_section_attachments' => 1,
                'export_quiz_backup' => 1,
                'export_course_backup' => 0,
                'archive_autodelete' => 1,
                'archive_retention_time' => '42w',
            ],
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
     * Tests the creation of a new archive job
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_archive_job(): void {
        global $DB;

        // Create new archive job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '10000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Check that the job was created
        $this->assertNotNull($job, 'Job was not created');
        $this->assertEquals(
            $job,
            ArchiveJob::get_by_jobid('10000000-1234-5678-abcd-ef4242424242'),
            'Job was not found in database'
        );

        // Check that the job has the correct settings
        $this->assertEquals($mocks->settings, $job->get_settings(), 'Job settings were not stored correctly');

        // Check if attempt ids were stored correctly
        $this->assertEqualsCanonicalizing(
            array_values($mocks->attempts),
            array_values($DB->get_records(ArchiveJob::ATTEMPTS_TABLE_NAME, ['jobid' => $job->get_id()], '', 'userid, attemptid')),
            'Job attempt ids were not stored correctly'
        );
    }

    /**
     * Test the deletion of an archive job
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_archive_job(): void {
        global $DB;

        // Create new archive job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '20000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-2',
            $mocks->attempts,
            $mocks->settings
        );

        // Delete the job but remember its ID
        $this->assertNotNull(ArchiveJob::get_by_jobid('20000000-1234-5678-abcd-ef4242424242'));
        $jobid = $job->get_id();
        $job->delete();

        // Confirm that the job was deleted
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::JOB_TABLE_NAME, ['jobid' => $jobid]),
            'Job was not deleted from database'
        );

        // Confirm that the attempt ids were deleted
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::ATTEMPTS_TABLE_NAME, ['jobid' => $jobid]),
            'Attempt ids were not deleted from database'
        );

        // Confirm that the settings were deleted
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::JOB_SETTINGS_TABLE_NAME, ['jobid' => $jobid]),
            'Settings were not deleted from database'
        );
    }

    /**
     * Tests the creation and retrieval of multiple jobs for different quizzes
     * as well as their metadata arrays.
     *
     * @return void
     */
    public function test_multiple_jobs_retrieval_and_metadata(): void {
        // Generate data
        $mocks = [];
        $jobs = [];
        for ($quizIdx= 0; $quizIdx < 3; $quizIdx++) {
            $mocks[$quizIdx] = $this->generateMockQuiz();
            for ($jobIdx = 0; $jobIdx < 3; $jobIdx++) {
                $jobs[$quizIdx][$jobIdx] = ArchiveJob::create(
                    '30000000-1234-5678-abcd-'.$quizIdx.'0000000000'.$jobIdx,
                    $mocks[$quizIdx]->course->id,
                    $mocks[$quizIdx]->quiz->cmid,
                    $mocks[$quizIdx]->quiz->id,
                    $mocks[$quizIdx]->user->id,
                    3600 + $jobIdx * $quizIdx * 100,
                    'TEST-WS-TOKEN',
                    $mocks[$quizIdx]->attempts,
                    $mocks[$quizIdx]->settings
                );
            }
        }

        // Find jobs in database
        foreach ($mocks as $quizIdx => $mock) {
            $this->assertEqualsCanonicalizing(
                array_values($jobs[$quizIdx]),
                array_values(ArchiveJob::get_jobs($mock->course->id, $mock->quiz->cmid, $mock->quiz->id)),
                'Jobs for quiz '.$quizIdx.' were not returned properly by get_jobs()'
            );
        }

        // Test metadata retrieval
        foreach ($mocks as $quizIdx => $mock) {
            $metadata = ArchiveJob::get_metadata_for_jobs($mock->course->id, $mock->quiz->cmid, $mock->quiz->id);

            // Check that the metadata array contains the correct number of jobs
            $this->assertSameSize(
                $jobs[$quizIdx],
                $metadata,
                'Metadata for quiz '.$quizIdx.' does not contain the correct number of jobs'
            );

            // Check that the metadata array contains the correct data
            foreach ($jobs[$quizIdx] as $jobIdx => $expectedJob) {
                // Find job in metadata array
                $actualJobs = array_filter($metadata, function ($metadata) use ($expectedJob) {
                    return $metadata['id'] == $expectedJob->get_id();
                });

                // Assure that job was found
                $this->assertCount(
                    1,
                    $actualJobs,
                    'Metadata for job '.$jobIdx.' of quiz '.$quizIdx.' could not uniquely be identified'
                );

                // Probe that the metadata contains the correct data
                $actualJob = array_pop($actualJobs);
                $this->assertEquals($expectedJob->get_jobid(), $actualJob['jobid'], 'Jobid was not returned correctly');
                $this->assertEquals($expectedJob->get_course_id(), $actualJob['course']['id'], 'Courseid was not returned correctly');
                $this->assertEquals($expectedJob->get_cm_id(), $actualJob['quiz']['cmid'], 'Course module id was not returned correctly');
                $this->assertEquals($expectedJob->get_quiz_id(), $actualJob['quiz']['id'], 'Quiz id was not returned correctly');
                $this->assertEquals($expectedJob->get_user_id(), $actualJob['user']['id'], 'User id was not returned correctly');
                $this->assertEquals($expectedJob->get_retentiontime(), $actualJob['retentiontime'], 'Retentiontime was not returned correctly');
                $this->assertSame($expectedJob->is_autodelete_enabled(), $actualJob['autodelete'], 'Autodelete was not detected as enabled');
                $this->assertArrayHasKey('autodelete_str', $actualJob, 'Autodelete string was not generated correctly');
                $this->assertSameSize($expectedJob->get_settings(), $actualJob['settings'], 'Settings were not returned correctly');
            }
        }
    }

    /**
     * Test status changes of jobs
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_job_status(): void {
        // Job statuses to test and whether they should be considered completed
        $statuses_and_completion = [
            ArchiveJob::STATUS_UNKNOWN => false,
            ArchiveJob::STATUS_UNINITIALIZED => false,
            ArchiveJob::STATUS_AWAITING_PROCESSING => false,
            ArchiveJob::STATUS_RUNNING => false,
            ArchiveJob::STATUS_FINISHED => true,
            ArchiveJob::STATUS_FAILED => true,
            ArchiveJob::STATUS_TIMEOUT => true,
            ArchiveJob::STATUS_DELETED => true,
        ];

        // Create test job
        $mocks = $this->generateMockQuiz();
        $expectedJob = ArchiveJob::create(
            '40000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings,
            ArchiveJob::STATUS_UNINITIALIZED
        );

        // Initial job status
        $this->assertEquals(
            ArchiveJob::STATUS_UNINITIALIZED,
            ArchiveJob::get_by_jobid('40000000-1234-5678-abcd-ef4242424242')->get_status(),
            'Initial job status was not set correctly'
        );

        // Test status changes
        foreach ($statuses_and_completion as $status => $completion) {
            $expectedJob->set_status($status);
            $actualJob = ArchiveJob::get_by_jobid('40000000-1234-5678-abcd-ef4242424242');
            $this->assertEquals($status, $actualJob->get_status(),'Job status was not set correctly to '.$status);
            $this->assertEquals($completion, $actualJob->is_complete(), 'Job completion was not detected correctly');
        }
    }

    /**
     * Test webservice token access checks
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_wstoken_access_checks(): void {
        // Generate test data
        $wstokens = [
            md5('TEST-WS-TOKEN-1'),
            md5('TEST-WS-TOKEN-2'),
            md5('TEST-WS-TOKEN-3'),
            md5('TEST-WS-TOKEN-4'),
            md5('TEST-WS-TOKEN-5'),
        ];
        $mocks = $this->generateMockQuiz();

        // Create jobs and test all tokens against each job
        foreach ($wstokens as $wstoken) {
            $job = ArchiveJob::create(
                'xxx-'.$wstoken,
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                $mocks->user->id,
                null,
                $wstoken,
                $mocks->attempts,
                $mocks->settings
            );

            // Validate token access
            foreach ($wstokens as $otherwstoken) {
                $this->assertSame(
                    $wstoken === $otherwstoken,
                    $job->has_write_access($otherwstoken),
                    'Webservice token access was not validated correctly'
                );
            }
        }
    }

    /**
     * Test the deletion of a webservice token
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_webservice_token(): void {
        // Create temporary webservice token
        global $CFG, $DB;
        if ($CFG->branch <= 401) {
            // TODO: Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025
            require_once($CFG->dirroot.'/lib/externallib.php');
            $wstoken = \external_generate_token(EXTERNAL_TOKEN_PERMANENT, 1, 1, context_system::instance(), time() + 3600, 0);
        } else {
            $wstoken = \core_external\util::generate_token(EXTERNAL_TOKEN_PERMANENT, \core_external\util::get_service_by_id(1), 1, context_system::instance(), time() + 3600, 0);
        }

        // Create job and test token access
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            'xxx-'.$wstoken,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            $wstoken,
            $mocks->attempts,
            $mocks->settings
        );

        $this->assertNotEmpty($DB->get_record('external_tokens', ['token' => $wstoken]), 'Webservice token was not created correctly');
        $job->delete_webservice_token();
        $this->assertEmpty($DB->get_record('external_tokens', ['token' => $wstoken]), 'Webservice token was not deleted correctly');
    }

    /**
     * Tests the linking of an artifact file to a job
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_linking(): void {
        // Create test job
        $mocks = $this->generateMockQuiz();
        $job = ArchiveJob::create(
            '60000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );
        $this->assertNull($job->get_artifact(), 'Job artifact file was not null before linking');
        $this->assertFalse($job->has_artifact(), 'New job believes that it has an artifact file');

        // Create and link artifact file
        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $sha256dummy = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256dummy);

        // Check that the artifact file was linked correctly
        $this->assertTrue($job->has_artifact(), 'Job artifact file was not linked');
        $this->assertEquals($artifact, $job->get_artifact(), 'Linked artifact file differs from original');
        $this->assertSame($sha256dummy, $job->get_artifact_checksum(), 'Artifact checksum was not stored correctly');
    }

    /**
     * Tests the deletion of an artifact file
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_deletion(): void {
        // Create test job and link dummy artifact file
        $mocks = $this->generateMockQuiz();
        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $job = ArchiveJob::create(
            '70000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );
        $job->link_artifact($artifact->get_id(), hash('sha256', 'foo bar baz'));

        // Delete artifact and ensure that the underlying file was delete correctly
        $job->delete_artifact();
        $this->assertNull($job->get_artifact(), 'Job still returned an artifact file after deletion');
        $this->assertFalse($job->has_artifact(), 'Job believes it still has an artifact file');
        $this->assertFalse(get_file_storage()->get_file_by_id($artifact->get_id()), 'Artifact file was not deleted from file storage');
        $this->assertSame(ArchiveJob::STATUS_DELETED, $job->get_status(), 'Job status was not set to deleted');
    }

    /**
     * Tests the deletion of expired artifact files
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_delete_expired_artifacts(): void {
        // Create test job that instantly expires and link dummy artifact file
        $mocks = $this->generateMockQuiz();
        $artifact = $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test.tar.gz');
        $job = ArchiveJob::create(
            '80000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            -1,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );
        $job->link_artifact($artifact->get_id(), hash('sha256', 'foo bar baz'));

        // Ensure that the artifact is present
        $this->assertTrue($job->has_artifact(), 'Job does not have an artifact file');
        $this->assertSame(1, ArchiveJob::delete_expired_artifacts(), 'Unexpected number of artifacts were reported as deleted');
        $this->assertFalse($job->has_artifact(), 'Job still has an artifact file after deletion');
        $this->assertFalse(get_file_storage()->get_file_by_id($artifact->get_id()), 'Artifact file was not deleted from file storage');
    }

    /**
     * Tests that temporary files can be linked to a job
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_temporary_file_linking(): void {
        // Generate data
        $mocks = $this->generateMockQuiz();
        $tmpFiles = [
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test1.tar.gz'),
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test2.tar.gz'),
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test3.tar.gz'),
        ];

        // Create job
        $job = ArchiveJob::create(
            '90000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );

        // Ensure no temporary files are linked
        $this->assertEmpty($job->get_temporary_files(), 'Job returned temporary files before linking');

        // Link files and check that they were linked correctly
        foreach ($tmpFiles as $tmpFile) {
            $job->link_temporary_file($tmpFile->get_pathnamehash());
        }

        $actualTempFiles = $job->get_temporary_files();
        foreach ($tmpFiles as $tmpFile) {
            $this->assertEquals($tmpFile, $actualTempFiles[$tmpFile->get_id()], 'Temporary file was not linked correctly');
        }
    }

    public function test_temporary_file_deletion(): void {
        // Generate data
        $mocks = $this->generateMockQuiz();
        $tmpFiles = [
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test1.tar.gz'),
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test2.tar.gz'),
            $this->generateArtifactFile($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'test3.tar.gz'),
        ];

        // Create job and link files
        $job = ArchiveJob::create(
            'a0000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );
        foreach ($tmpFiles as $tmpFile) {
            $job->link_temporary_file($tmpFile->get_pathnamehash());
        }

        // Ensure link state, delete and check
        $this->assertCount(3, $job->get_temporary_files(), 'Job did not link all temporary files');
        $job->delete_temporary_files();

        $this->assertEmpty($job->get_temporary_files(), 'Job still has temporary files after deletion');
        foreach ($tmpFiles as $tmpFile) {
            $this->assertFalse(get_file_storage()->get_file_by_id($tmpFile->get_id()), 'Temporary file was not deleted from file storage');
        }
    }

    /**
     * Test archive filename pattern validation
     *
     * @dataProvider archive_filename_pattern_data_provider
     *
     * @param string $pattern Pattern to test
     * @param bool $isValid Expected result
     * @return void
     */
    public function test_archive_filename_pattern_validation(string $pattern, bool $isValid): void {
        $this->assertSame(
            $isValid,
            ArchiveJob::is_valid_archive_filename_pattern($pattern),
            'Archive filename pattern validation failed for pattern "'.$pattern.'"'
        );
    }

    /**
     * Data provider for test_archive_filename_pattern_validation()
     *
     * @return array[] Array of test cases
     */
    protected function archive_filename_pattern_data_provider(): array {
        return [
            'Default pattern' => [
                'pattern' => 'quiz-archive-${courseshortname}-${courseid}-${quizname}-${quizid}_${date}-${time}',
                'isValid' => true,
            ],
            'All allowed variables' => [
                'pattern' => array_reduce(
                    ArchiveJob::ARCHIVE_FILENAME_PATTERN_VARIABLES,
                    function ($carry, $item) {
                        return $carry.'${'.$item.'}';
                    },
                    ''
                ),
                'isValid' => true,
            ],
            'Allowed variables with additional brackets' => [
                'pattern' => 'quiz-{quizname}_${quizname}-{quizid}_${quizid}',
                'isValid' => true,
            ],
            'Invalid variable' => [
                'pattern' => 'Foo ${foo} Bar ${bar} Baz ${baz}',
                'isValid' => false,
            ],
            'Forbidden characters' => [
                'pattern' => 'quiz-archive: foo!bar',
                'isValid' => false,
            ],
            'Only invalid characters' => [
                'pattern' => '.!',
                'isValid' => false,
            ],
            'Dot' => [
                'pattern' => '.',
                'isValid' => false,
            ],
            'Empty pattern' => [
                'pattern' => '',
                'isValid' => false,
            ],
        ];
    }

    /**
     * Test attempt filename pattern validation
     *
     * @dataProvider attempt_filename_pattern_data_provider
     *
     * @param string $pattern Pattern to test
     * @param bool $isValid Expected result
     * @return void
     */
    public function test_attempt_filename_pattern_validation(string $pattern, bool $isValid): void {
        $this->assertSame(
            $isValid,
            ArchiveJob::is_valid_attempt_filename_pattern($pattern),
            'Attempt filename pattern validation failed for pattern "'.$pattern.'"'
        );
    }

    /**
     * Data provider for test_attempt_filename_pattern_validation()
     *
     * @return array[] Array of test cases
     */
    protected function attempt_filename_pattern_data_provider(): array {
        return [
            'Default pattern' => [
                'pattern' => 'attempt-${attemptid}-${username}_${date}-${time}',
                'isValid' => true,
            ],
            'All allowed variables' => [
                'pattern' => array_reduce(
                    ArchiveJob::ATTEMPT_FILENAME_PATTERN_VARIABLES,
                    function ($carry, $item) {
                        return $carry.'${'.$item.'}';
                    },
                    ''
                ),
                'isValid' => true,
            ],
            'Allowed variables with additional brackets' => [
                'pattern' => 'attempt-{quizname}_${quizname}-{quizid}_${quizid}',
                'isValid' => true,
            ],
            'Invalid variable' => [
                'pattern' => 'Foo ${foo} Bar ${bar} Baz ${baz}',
                'isValid' => false,
            ],
            'Forbidden characters' => [
                'pattern' => 'attempt: foo!bar',
                'isValid' => false,
            ],
            'Only invalid characters' => [
                'pattern' => '.!',
                'isValid' => false,
            ],
            'Dot' => [
                'pattern' => '.',
                'isValid' => false,
            ],
            'Empty pattern' => [
                'pattern' => '',
                'isValid' => false,
            ],
        ];
    }

}