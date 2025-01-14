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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use context_module;
use context_system;

/**
 * Tests for the ArchiveJob class
 */
final class archivejob_test extends \advanced_testcase {

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
     * Tests the creation of a new archive job
     *
     * @covers \quiz_archiver\ArchiveJob::create
     * @covers \quiz_archiver\ArchiveJob::__construct
     * @covers \quiz_archiver\ArchiveJob::get_by_jobid
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_archive_job(): void {
        global $DB;
        $this->resetAfterTest();

        // Create new archive job.
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        // Check that the job was created.
        $this->assertNotNull($job, 'Job was not created');
        $this->assertEquals(
            $job,
            ArchiveJob::get_by_jobid('10000000-1234-5678-abcd-ef4242424242'),
            'Job was not found in database'
        );

        // Check that the job has the correct settings.
        $this->assertEquals($mocks->settings, $job->get_settings(), 'Job settings were not stored correctly');

        // Check if attempt ids were stored correctly.
        $this->assertEqualsCanonicalizing(
            array_values($mocks->attempts),
            array_values($DB->get_records(ArchiveJob::ATTEMPTS_TABLE_NAME, ['jobid' => $job->get_id()], '', 'userid, attemptid')),
            'Job attempt ids were not stored correctly'
        );
    }

    /**
     * Tests the retrieval of an archive job by its internal database ID
     *
     * @dataProvider job_get_by_id_data_provider
     * @covers \quiz_archiver\ArchiveJob::get_id
     * @covers \quiz_archiver\ArchiveJob::get_by_id
     *
     * @param bool $shouldfail Whether the test should fail
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_job_get_by_id(bool $shouldfail): void {
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            '10000000-1234-5678-abcd-ef4242123456',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );
        $jobid = $job->get_id();

        if ($shouldfail) {
            $this->expectException(\dml_exception::class);
            $jobid += 100000;
        }
        $this->assertEquals($job, ArchiveJob::get_by_id($jobid));
    }

    /**
     * Data provider for test_job_get_by_id
     *
     * @return array Test data
     */
    public static function job_get_by_id_data_provider(): array {
        return [
            'Existing Job' => ['shouldfail' => false],
            'Non-Existing Job' => ['shouldfail' => true],
        ];
    }

    /**
     * Tests the duplicate UUID detection during job creation
     *
     * @covers \quiz_archiver\ArchiveJob::create
     * @covers \quiz_archiver\ArchiveJob::get_by_jobid
     * @covers \quiz_archiver\ArchiveJob::exists_in_db
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_job_duplicate_detection(): void {
        // Create mock job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '10000000-dupe-dupe-dupe-ef1234567890';
        $job = ArchiveJob::create(
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

        // Assert that job was created.
        $this->assertNotNull(ArchiveJob::get_by_jobid($jobid), 'Job was not created');

        // Try to create second job with same UUID.
        $this->expectException(\moodle_exception::class);
        $jobduplicate = ArchiveJob::create(
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
    }

    /**
     * Test the deletion of an archive job
     *
     * @covers \quiz_archiver\ArchiveJob::create
     * @covers \quiz_archiver\ArchiveJob::get_by_jobid
     * @covers \quiz_archiver\ArchiveJob::delete
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_archive_job(): void {
        global $DB;

        // Create new archive job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        // Delete the job but remember its ID.
        $this->assertNotNull(ArchiveJob::get_by_jobid('20000000-1234-5678-abcd-ef4242424242'));
        $jobid = $job->get_id();
        $job->delete();

        // Confirm that the job was deleted.
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::JOB_TABLE_NAME, ['jobid' => $jobid]),
            'Job was not deleted from database'
        );

        // Confirm that the attempt ids were deleted.
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::ATTEMPTS_TABLE_NAME, ['jobid' => $jobid]),
            'Attempt ids were not deleted from database'
        );

        // Confirm that the settings were deleted.
        $this->assertEmpty(
            $DB->get_records(ArchiveJob::JOB_SETTINGS_TABLE_NAME, ['jobid' => $jobid]),
            'Settings were not deleted from database'
        );
    }

    /**
     * Tests the creation and retrieval of multiple jobs for different quizzes
     * as well as their metadata arrays.
     *
     * @covers \quiz_archiver\ArchiveJob::create
     * @covers \quiz_archiver\ArchiveJob::link_artifact
     * @covers \quiz_archiver\ArchiveJob::get_jobs
     * @covers \quiz_archiver\ArchiveJob::get_metadata_for_jobs
     * @covers \quiz_archiver\ArchiveJob::get_jobid
     * @covers \quiz_archiver\ArchiveJob::get_courseid
     * @covers \quiz_archiver\ArchiveJob::get_cmid
     * @covers \quiz_archiver\ArchiveJob::get_quizid
     * @covers \quiz_archiver\ArchiveJob::get_userid
     * @covers \quiz_archiver\ArchiveJob::get_retentiontime
     * @covers \quiz_archiver\ArchiveJob::is_autodelete_enabled
     * @covers \quiz_archiver\ArchiveJob::get_settings
     * @covers \quiz_archiver\ArchiveJob::convert_archive_settings_for_display
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_multiple_jobs_retrieval_and_metadata(): void {
        global $DB;
        $this->resetAfterTest();

        // Generate data.
        $mocks = [];
        $jobs = [];
        $artifacts = [];
        for ($quizidx = 0; $quizidx < 3; $quizidx++) {
            $mocks[$quizidx] = $this->getDataGenerator()->create_mock_quiz();
            for ($jobidx = 0; $jobidx < 3; $jobidx++) {
                // Create job.
                $jobs[$quizidx][$jobidx] = ArchiveJob::create(
                    '30000000-1234-5678-abcd-'.$quizidx.'0000000000'.$jobidx,
                    $mocks[$quizidx]->course->id,
                    $mocks[$quizidx]->quiz->cmid,
                    $mocks[$quizidx]->quiz->id,
                    $mocks[$quizidx]->user->id,
                    3600 + $jobidx * $quizidx * 100,
                    'TEST-WS-TOKEN',
                    $mocks[$quizidx]->attempts,
                    $mocks[$quizidx]->settings
                );

                // Attach artifact.
                $artifacts[$quizidx][$jobidx] = $this->getDataGenerator()->create_artifact_file(
                    $mocks[$quizidx]->course->id,
                    $mocks[$quizidx]->quiz->cmid,
                    $mocks[$quizidx]->quiz->id,
                    'test'.$quizidx.'-'.$jobidx.'.tar.gz'
                );
                $jobs[$quizidx][$jobidx]->link_artifact(
                    $artifacts[$quizidx][$jobidx]->get_id(),
                    hash('sha256', 'foo bar baz')
                );

                // Generate mock TSP data.
                $DB->insert_record(TSPManager::TSP_TABLE_NAME, [
                    'jobid' => $jobs[$quizidx][$jobidx]->get_id(),
                    'timecreated' => time(),
                    'server' => 'localhost',
                    'timestampquery' => 'tspquery',
                    'timestampreply' => 'tspreply',
                ]);
            }
        }

        // Find jobs in database.
        foreach ($mocks as $quizidx => $mock) {
            $this->assertEqualsCanonicalizing(
                array_values($jobs[$quizidx]),
                array_values(ArchiveJob::get_jobs($mock->course->id, $mock->quiz->cmid, $mock->quiz->id)),
                'Jobs for quiz '.$quizidx.' were not returned properly by get_jobs()'
            );
        }

        // Test metadata retrieval.
        foreach ($mocks as $quizidx => $mock) {
            $metadata = ArchiveJob::get_metadata_for_jobs($mock->course->id, $mock->quiz->cmid, $mock->quiz->id);

            // Check that the metadata array contains the correct number of jobs.
            $this->assertSameSize(
                $jobs[$quizidx],
                $metadata,
                'Metadata for quiz '.$quizidx.' does not contain the correct number of jobs'
            );

            // Check that the metadata array contains the correct data.
            foreach ($jobs[$quizidx] as $jobidx => $expectedjob) {
                // Find job in metadata array.
                $actualjobs = array_filter($metadata, function ($metadata) use ($expectedjob) {
                    return $metadata['id'] == $expectedjob->get_id();
                });

                // Assure that job was found.
                $this->assertCount(
                    1,
                    $actualjobs,
                    'Metadata for job '.$jobidx.' of quiz '.$quizidx.' could not uniquely be identified'
                );

                // Probe that the metadata contains the correct data.
                $actualjob = array_pop($actualjobs);
                // @codingStandardsIgnoreStart
                $this->assertEquals($expectedjob->get_jobid(), $actualjob['jobid'], 'Jobid was not returned correctly');
                $this->assertEquals($expectedjob->get_courseid(), $actualjob['course']['id'], 'Courseid was not returned correctly');
                $this->assertEquals($expectedjob->get_cmid(), $actualjob['quiz']['cmid'], 'Course module id was not returned correctly');
                $this->assertEquals($expectedjob->get_quizid(), $actualjob['quiz']['id'], 'Quiz id was not returned correctly');
                $this->assertEquals($expectedjob->get_userid(), $actualjob['user']['id'], 'User id was not returned correctly');
                $this->assertEquals($expectedjob->get_retentiontime(), $actualjob['retentiontime'], 'Retentiontime was not returned correctly');
                $this->assertSame($expectedjob->is_autodelete_enabled(), $actualjob['autodelete'], 'Autodelete was not detected as enabled');
                $this->assertArrayHasKey('autodelete_str', $actualjob, 'Autodelete string was not generated correctly');
                $this->assertSameSize($expectedjob->get_settings(), $actualjob['settings'], 'Settings were not returned correctly');

                // Check that the artifact file metadata was returned correctly.
                $this->assertArrayHasKey('artifactfile', $actualjob, 'Artifact file metadata was not returned');
                $this->assertEquals($artifacts[$quizidx][$jobidx]->get_filename(), $actualjob['artifactfile']['name'], 'Artifact filename was not returned correctly');
                $this->assertEquals($artifacts[$quizidx][$jobidx]->get_filesize(), $actualjob['artifactfile']['size'], 'Artifact size was not returned correctly');
                $this->assertNotEmpty($actualjob['artifactfile']['downloadurl'], 'Artifact download URL was not returned');
                $this->assertNotEmpty($actualjob['artifactfile']['size_human'], 'Artifact size in human readable format was not returned');
                $this->assertEquals(hash('sha256', 'foo bar baz'), $actualjob['artifactfile']['checksum'], 'Artifact checksum was not returned correctly');

                // Check that the TSP data was returned correctly.
                $this->assertArrayHasKey('tsp', $actualjob, 'TSP data was not returned');
                $this->assertEquals('localhost', $actualjob['tsp']['server'], 'TSP server was not returned correctly');
                $this->assertNotEmpty($actualjob['tsp']['timecreated'], 'TSP creation time was not returned');
                $this->assertNotEmpty($actualjob['tsp']['queryfiledownloadurl'], 'TSP queryfile download URL was not returned');
                $this->assertNotEmpty($actualjob['tsp']['replyfiledownloadurl'], 'TSP replyfile download URL was not returned');
                // @codingStandardsIgnoreEnd
            }
        }
    }

    /**
     * Test status changes of jobs
     *
     * @dataProvider set_job_status_data_provider
     * @covers       \quiz_archiver\ArchiveJob::set_status
     * @covers       \quiz_archiver\ArchiveJob::get_status
     * @covers       \quiz_archiver\ArchiveJob::is_complete
     *
     * @param string $status
     * @param bool $iscompleted
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_job_status(string $status, bool $iscompleted): void {
        // Create test job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $expectedjob = ArchiveJob::create(
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

        // Initial job status.
        $this->assertEquals(
            ArchiveJob::STATUS_UNINITIALIZED,
            ArchiveJob::get_by_jobid('40000000-1234-5678-abcd-ef4242424242')->get_status(),
            'Initial job status was not set correctly'
        );

        // Test status changes.
        $expectedjob->set_status($status);
        $actualjob = ArchiveJob::get_by_jobid('40000000-1234-5678-abcd-ef4242424242');
        $this->assertEquals($status, $actualjob->get_status(), 'Job status was not set correctly to '.$status);
        $this->assertEquals($iscompleted, $actualjob->is_complete(), 'Job completion was not detected correctly');
    }

    /**
     * Data provider for test_set_job_status
     *
     * @return array[] Test data
     */
    public static function set_job_status_data_provider(): array {
        return [
            'STATUS_UNKNOWN' => ['status' => ArchiveJob::STATUS_UNKNOWN, 'iscompleted' => false],
            'STATUS_UNINITIALIZED' => ['status' => ArchiveJob::STATUS_UNINITIALIZED, 'iscompleted' => false],
            'STATUS_AWAITING_PROCESSING' => ['status' => ArchiveJob::STATUS_AWAITING_PROCESSING, 'iscompleted' => false],
            'STATUS_RUNNING' => ['status' => ArchiveJob::STATUS_RUNNING, 'iscompleted' => false],
            'STATUS_WAITING_FOR_BACKUP' => ['status' => ArchiveJob::STATUS_WAITING_FOR_BACKUP, 'iscompleted' => false],
            'STATUS_FINALIZING' => ['status' => ArchiveJob::STATUS_FINALIZING, 'iscompleted' => false],
            'STATUS_FINISHED' => ['status' => ArchiveJob::STATUS_FINISHED, 'iscompleted' => true],
            'STATUS_FAILED' => ['status' => ArchiveJob::STATUS_FAILED, 'iscompleted' => true],
            'STATUS_TIMEOUT' => ['status' => ArchiveJob::STATUS_TIMEOUT, 'iscompleted' => true],
            'STATUS_DELETED' => ['status' => ArchiveJob::STATUS_DELETED, 'iscompleted' => true],
        ];
    }

    /**
     * Test status changes of jobs with statusextras
     *
     * @dataProvider set_job_status_with_statusextras_data_provider
     * @covers       \quiz_archiver\ArchiveJob::set_status
     * @covers       \quiz_archiver\ArchiveJob::get_status
     * @covers       \quiz_archiver\ArchiveJob::get_statusextras
     *
     * @param string $status Job status to set
     * @param array|null $statusextras Statusextras to set
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_job_status_with_statusextras(string $status, ?array $statusextras): void {
        // Create test job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $expectedjob = ArchiveJob::create(
            '40000123-1234-5678-abcd-ef4242424242',
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

        // Initial job status.
        $this->assertEquals(
            ArchiveJob::STATUS_UNINITIALIZED,
            ArchiveJob::get_by_jobid('40000123-1234-5678-abcd-ef4242424242')->get_status(),
            'Initial job status was not set correctly'
        );

        // Test status changes.
        $expectedjob->set_status($status, $statusextras);
        $actualjob = ArchiveJob::get_by_jobid('40000123-1234-5678-abcd-ef4242424242');
        $this->assertEquals($status, $actualjob->get_status(), 'Job status was not set correctly to '.$status);
        $this->assertEquals($statusextras, $actualjob->get_statusextras(), 'Job statusextras were not set correctly');
    }

    /**
     * Data provider for test_set_job_status_with_statusextras
     *
     * @return array[] Test data
     */
    public static function set_job_status_with_statusextras_data_provider(): array {
        return [
            'No statusextras' => [
                'status' => ArchiveJob::STATUS_AWAITING_PROCESSING,
                'statusextras' => null,
            ],
            'Simple progress' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 42],
            ],
            'Complex data' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 100, 'foo' => 'bar'],
            ],
            'Nested data' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 0, 'nested' => ['foo' => 'bar']],
            ],
        ];
    }

    /**
     * Test webservice token access checks
     *
     * @covers \quiz_archiver\ArchiveJob::has_write_access
     * @covers \quiz_archiver\ArchiveJob::has_read_access
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_wstoken_access_checks(): void {
        // Generate test data.
        $wstokens = [
            md5('TEST-WS-TOKEN-1'),
            md5('TEST-WS-TOKEN-2'),
            md5('TEST-WS-TOKEN-3'),
            md5('TEST-WS-TOKEN-4'),
            md5('TEST-WS-TOKEN-5'),
        ];
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();

        // Create jobs and test all tokens against each job.
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

            // Validate token access.
            foreach ($wstokens as $otherwstoken) {
                $this->assertSame(
                    $wstoken === $otherwstoken,
                    $job->has_write_access($otherwstoken),
                    'Webservice token access was not validated correctly (write access)'
                );
                $this->assertSame(
                    $wstoken === $otherwstoken,
                    $job->has_read_access($otherwstoken),
                    'Webservice token access was not validated correctly (read access)'
                );
            }
        }
    }

    /**
     * Test the deletion of a webservice token
     *
     * @covers \quiz_archiver\ArchiveJob::delete_webservice_token
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_webservice_token(): void {
        // Create temporary webservice token.
        global $CFG, $DB;
        if ($CFG->branch <= 401) {
            // TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
            require_once($CFG->dirroot.'/lib/externallib.php');
            $wstoken = \external_generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                1,
                1,
                context_system::instance(),
                time() + 3600,
                0
            );
        } else {
            $wstoken = \core_external\util::generate_token(
                EXTERNAL_TOKEN_PERMANENT,
                \core_external\util::get_service_by_id(1),
                1,
                context_system::instance(),
                time() + 3600,
                0
            );
        }

        // Create job and test token access.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        $this->assertNotEmpty(
            $DB->get_record('external_tokens', ['token' => $wstoken]),
            'Webservice token was not created correctly'
        );
        $job->delete_webservice_token();
        $this->assertEmpty(
            $DB->get_record('external_tokens', ['token' => $wstoken]),
            'Webservice token was not deleted correctly'
        );
    }

    /**
     * Test job timeout
     *
     * @covers \quiz_archiver\ArchiveJob::timeout_if_overdue
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_job_timeout(): void {
        // Prepare job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            '12300000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            1,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings,
            ArchiveJob::STATUS_RUNNING,
        );

        // Not timed out job should not he set to timed out.
        $this->assertFalse($job->timeout_if_overdue(60), 'Job seems to have been set to timed out before timeout');
        $this->assertSame(ArchiveJob::STATUS_RUNNING, $job->get_status(), 'Job status was changed to timed out before timeout');

        // Time out job.
        sleep(1); // Ensure that at least one second has passed.
        $this->assertTrue($job->timeout_if_overdue(0), 'Job seems to have not been set to timed out after timeout');
        $this->assertSame(ArchiveJob::STATUS_TIMEOUT, $job->get_status(), 'Job status was not changed to timed out after timeout');

        // Do not timeout a finished job.
        $job->set_status(ArchiveJob::STATUS_FINISHED);
        $this->assertFalse($job->timeout_if_overdue(0), 'Finished job seems to have been set to timed out');
        $this->assertSame(ArchiveJob::STATUS_FINISHED, $job->get_status(), 'Finished job was changed to timed out');
    }

    /**
     * Tests the linking of an artifact file to a job
     *
     * @covers \quiz_archiver\ArchiveJob::link_artifact
     * @covers \quiz_archiver\ArchiveJob::has_artifact
     * @covers \quiz_archiver\ArchiveJob::get_artifact
     * @covers \quiz_archiver\ArchiveJob::get_artifact_checksum
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_linking(): void {
        // Create test job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
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

        // Create and link artifact file.
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'test.tar.gz'
        );
        $sha256dummy = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256dummy);

        // Check that the artifact file was linked correctly.
        $this->assertTrue($job->has_artifact(), 'Job artifact file was not linked');
        $this->assertEquals($artifact, $job->get_artifact(), 'Linked artifact file differs from original');
        $this->assertSame($sha256dummy, $job->get_artifact_checksum(), 'Artifact checksum was not stored correctly');
    }

    /**
     * Tests the deletion of an artifact file
     *
     * @covers \quiz_archiver\ArchiveJob::delete_artifact
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_artifact_deletion(): void {
        // Create test job and link dummy artifact file.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'test.tar.gz'
        );
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

        // Delete artifact and ensure that the underlying file was delete correctly.
        $job->delete_artifact();
        // @codingStandardsIgnoreStart
        $this->assertNull($job->get_artifact(), 'Job still returned an artifact file after deletion');
        $this->assertFalse($job->has_artifact(), 'Job believes it still has an artifact file');
        $this->assertFalse(get_file_storage()->get_file_by_id($artifact->get_id()), 'Artifact file was not deleted from file storage');
        $this->assertSame(ArchiveJob::STATUS_DELETED, $job->get_status(), 'Job status was not set to deleted');
        // @codingStandardsIgnoreEnd
    }

    /**
     * Tests the deletion of expired artifact files
     *
     * @covers \quiz_archiver\ArchiveJob::delete_expired_artifacts
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_delete_expired_artifacts(): void {
        // Create test job that instantly expires and link dummy artifact file.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'test.tar.gz'
        );
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

        // Ensure that the artifact is present.
        // @codingStandardsIgnoreStart
        $this->assertTrue($job->has_artifact(), 'Job does not have an artifact file');
        $this->assertSame(1, ArchiveJob::delete_expired_artifacts(), 'Unexpected number of artifacts were reported as deleted');
        $this->assertFalse($job->has_artifact(), 'Job still has an artifact file after deletion');
        $this->assertFalse(get_file_storage()->get_file_by_id($artifact->get_id()), 'Artifact file was not deleted from file storage');
        // @codingStandardsIgnoreEnd
    }

    /**
     * Tests that the artifact checksum is null for non-existing artifacts
     *
     * @covers \quiz_archiver\ArchiveJob::get_artifact_checksum
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_artifact_checksum_non_existing(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            '99000000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );

        // Check that the artifact checksum is null for non-existing artifacts.
        $this->assertNull($job->get_artifact_checksum(), 'Artifact checksum was not null for non-existing artifact');
    }

    /**
     * Tests that temporary files can be linked to a job
     *
     * @covers \quiz_archiver\ArchiveJob::link_temporary_file
     * @covers \quiz_archiver\ArchiveJob::get_temporary_files
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_temporary_file_linking(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $tmpfiles = [
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test1.tar.gz'
            ),
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test2.tar.gz'
            ),
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test3.tar.gz'
            ),
        ];

        // Create job.
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

        // Ensure no temporary files are linked.
        $this->assertEmpty($job->get_temporary_files(), 'Job returned temporary files before linking');

        // Link files and check that they were linked correctly.
        foreach ($tmpfiles as $tmpfile) {
            $job->link_temporary_file($tmpfile->get_pathnamehash());
        }

        $actualtempfiles = $job->get_temporary_files();
        foreach ($tmpfiles as $tmpfile) {
            $this->assertEquals($tmpfile, $actualtempfiles[$tmpfile->get_id()], 'Temporary file was not linked correctly');
        }
    }

    /**
     * Tests that temporary files are deleted properly
     *
     * @covers \quiz_archiver\ArchiveJob::delete_temporary_files
     *
     * @return void
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public function test_temporary_file_deletion(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $tmpfiles = [
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test1.tar.gz'
            ),
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test2.tar.gz'
            ),
            $this->getDataGenerator()->create_artifact_file(
                $mocks->course->id,
                $mocks->quiz->cmid,
                $mocks->quiz->id,
                'test3.tar.gz'
            ),
        ];

        // Create job and link files.
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
        foreach ($tmpfiles as $tmpfile) {
            $job->link_temporary_file($tmpfile->get_pathnamehash());
        }

        // Ensure link state, delete and check.
        $this->assertCount(3, $job->get_temporary_files(), 'Job did not link all temporary files');
        $job->delete_temporary_files();

        $this->assertEmpty($job->get_temporary_files(), 'Job still has temporary files after deletion');
        foreach ($tmpfiles as $tmpfile) {
            $this->assertFalse(
                get_file_storage()->get_file_by_id($tmpfile->get_id()),
                'Temporary file was not deleted from file storage'
            );
        }
    }

    /**
     * Test archive filename pattern validation
     *
     * @covers \quiz_archiver\ArchiveJob::is_valid_archive_filename_pattern
     * @covers \quiz_archiver\ArchiveJob::is_valid_filename_pattern
     *
     * @dataProvider archive_filename_pattern_data_provider
     *
     * @param string $pattern Pattern to test
     * @param bool $isvalid Expected result
     * @return void
     */
    public function test_archive_filename_pattern_validation(string $pattern, bool $isvalid): void {
        $this->assertSame(
            $isvalid,
            ArchiveJob::is_valid_archive_filename_pattern($pattern),
            'Archive filename pattern validation failed for pattern "'.$pattern.'"'
        );
    }

    /**
     * Data provider for test_archive_filename_pattern_validation()
     *
     * @return array[] Array of test cases
     */
    public static function archive_filename_pattern_data_provider(): array {
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
     * @covers \quiz_archiver\ArchiveJob::is_valid_attempt_filename_pattern
     * @covers \quiz_archiver\ArchiveJob::is_valid_filename_pattern
     *
     * @dataProvider attempt_filename_pattern_data_provider
     *
     * @param string $pattern Pattern to test
     * @param bool $isvalid Expected result
     * @return void
     */
    public function test_attempt_filename_pattern_validation(string $pattern, bool $isvalid): void {
        $this->assertSame(
            $isvalid,
            ArchiveJob::is_valid_attempt_filename_pattern($pattern),
            'Attempt filename pattern validation failed for pattern "'.$pattern.'"'
        );
    }

    /**
     * Data provider for test_attempt_filename_pattern_validation()
     *
     * @return array[] Array of test cases
     */
    public static function attempt_filename_pattern_data_provider(): array {
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

    /**
     * Test generation of valid archive filenames
     *
     * @covers \quiz_archiver\ArchiveJob::generate_archive_filename
     * @covers \quiz_archiver\ArchiveJob::sanitize_filename
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_generate_archive_filename(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $cm = context_module::instance($mocks->quiz->cmid);

        // Full pattern.
        $fullpattern = 'archive';
        foreach (ArchiveJob::ARCHIVE_FILENAME_PATTERN_VARIABLES as $var) {
            $fullpattern .= '-${'.$var.'}';
        }
        $filename = ArchiveJob::generate_archive_filename(
            $mocks->course,
            $cm,
            $mocks->quiz,
            $fullpattern
        );
        $this->assertStringContainsString($mocks->course->id, $filename, 'Course ID was not found in filename');
        $this->assertStringContainsString($cm->id, $filename, 'Course module ID was not found in filename');
        $this->assertStringContainsString($mocks->quiz->id, $filename, 'Quiz ID was not found in filename');
        $this->assertStringContainsString($mocks->course->fullname, $filename, 'Course name was not found in filename');
        $this->assertStringContainsString($mocks->course->shortname, $filename, 'Course shortname was not found in filename');
        $this->assertStringContainsString($mocks->quiz->name, $filename, 'Quiz name was not found in filename');
    }

    /**
     * Test generation of archive filenames without variables
     *
     * @covers \quiz_archiver\ArchiveJob::generate_archive_filename
     * @covers \quiz_archiver\ArchiveJob::sanitize_filename
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_generate_archive_filename_without_variables(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $cm = context_module::instance($mocks->quiz->cmid);

        // Full pattern.
        $filename = ArchiveJob::generate_archive_filename(
            $mocks->course,
            $cm,
            $mocks->quiz,
            'archive'
        );
        $this->assertSame('archive', $filename, 'Filename was not generated correctly');
    }

    /**
     * Test generation of archive filenames with invalid patterns
     *
     * @covers \quiz_archiver\ArchiveJob::generate_archive_filename
     * @covers \quiz_archiver\ArchiveJob::sanitize_filename
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_generate_archive_filename_invalid_pattern(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $cm = context_module::instance($mocks->quiz->cmid);

        // Test filename generation.
        $this->expectException(\invalid_parameter_exception::class);
        ArchiveJob::generate_archive_filename(
            $mocks->course,
            $cm,
            $mocks->quiz,
            '.'
        );
    }

    /**
     * Test generation of archive filenames with invalid variables
     *
     * @covers \quiz_archiver\ArchiveJob::generate_archive_filename
     * @covers \quiz_archiver\ArchiveJob::sanitize_filename
     *
     * @return void
     * @throws \coding_exception
     * @throws \invalid_parameter_exception
     */
    public function test_generate_archive_filename_invalid_variables(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $cm = context_module::instance($mocks->quiz->cmid);

        // Test filename generation.
        $this->expectException(\invalid_parameter_exception::class);
        $filename = ArchiveJob::generate_archive_filename(
            $mocks->course,
            $cm,
            $mocks->quiz,
            'archive-${foo}${bar}${baz}${courseid}'
        );
    }

    /**
     * Test retrieval of human-readable job status
     *
     * @covers \quiz_archiver\ArchiveJob::get_status_display_args
     *
     * @dataProvider status_display_args_data_provider
     *
     * @param string $status
     * @return void
     * @throws \coding_exception
     */
    public function test_status_display_args(string $status): void {
        $res = ArchiveJob::get_status_display_args($status);
        $this->assertSame(
            get_string('job_status_'.$status, 'quiz_archiver'),
            $res['text'],
            'Status display args were not returned correctly for status: '.$status
        );
        $this->assertNotEmpty(
            $res['color'],
            'Status display args did not contain a color for status: '.$status
        );
        $this->assertNotEmpty(
            $res['help'],
            'Status display args did not contain help text for status: '.$status
        );
    }

    /**
     * Data provider for test_status_display_args()
     *
     * @return array List of job status values to test
     */
    public static function status_display_args_data_provider(): array {
        return [
            ArchiveJob::STATUS_UNKNOWN => ['status' => ArchiveJob::STATUS_UNKNOWN],
            ArchiveJob::STATUS_UNINITIALIZED => ['status' => ArchiveJob::STATUS_UNINITIALIZED],
            ArchiveJob::STATUS_AWAITING_PROCESSING => ['status' => ArchiveJob::STATUS_AWAITING_PROCESSING],
            ArchiveJob::STATUS_RUNNING => ['status' => ArchiveJob::STATUS_RUNNING],
            ArchiveJob::STATUS_WAITING_FOR_BACKUP => ['status' => ArchiveJob::STATUS_WAITING_FOR_BACKUP],
            ArchiveJob::STATUS_FINALIZING => ['status' => ArchiveJob::STATUS_FINALIZING],
            ArchiveJob::STATUS_FINISHED => ['status' => ArchiveJob::STATUS_FINISHED],
            ArchiveJob::STATUS_FAILED => ['status' => ArchiveJob::STATUS_FAILED],
            ArchiveJob::STATUS_TIMEOUT => ['status' => ArchiveJob::STATUS_TIMEOUT],
            ArchiveJob::STATUS_DELETED => ['status' => ArchiveJob::STATUS_DELETED],
        ];
    }

    /**
     * Test retrieval of human-readable job status with statusextras
     *
     * @dataProvider status_display_args_with_statusextras_data_provider
     * @covers       \quiz_archiver\ArchiveJob::get_status_display_args
     *
     * @param string $status
     * @param array|null $statusextras
     * @return void
     * @throws \coding_exception
     */
    public function test_status_display_args_with_statusextras(string $status, ?array $statusextras): void {
        $res = ArchiveJob::get_status_display_args($status, $statusextras);
        $this->assertSame(
            get_string('job_status_'.$status, 'quiz_archiver'),
            $res['text'],
            'Status display args were not returned correctly for status: '.$status
        );
        $this->assertNotEmpty(
            $res['color'],
            'Status display args did not contain a color for status: '.$status
        );
        $this->assertNotEmpty(
            $res['help'],
            'Status display args did not contain help text for status: '.$status
        );
        $this->assertSame(
            $statusextras ?? [],
            $res['statusextras'],
            'Status display args did not contain expected statusextras'
        );
    }

    /**
     * Data provider for test_status_display_args_with_statusextras
     *
     * @return array[] Test data
     */
    public static function status_display_args_with_statusextras_data_provider(): array {
        return [
            'No statusextras' => [
                'status' => ArchiveJob::STATUS_AWAITING_PROCESSING,
                'statusextras' => null,
            ],
            'Simple progress' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 42],
            ],
            'Complex data' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 100, 'foo' => 'bar'],
            ],
            'Nested data' => [
                'status' => ArchiveJob::STATUS_RUNNING,
                'statusextras' => ['progress' => 0, 'nested' => ['foo' => 'bar']],
            ],
        ];
    }

    /**
     * Tests the retrieval of a TSPManager instance via an ArchiveJob
     *
     * @covers \quiz_archiver\ArchiveJob::tspmanager
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_tspmanager_get_instance(): void {
        // Generate data.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            'asn00000-1234-5678-abcd-ef4242424242',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            $mocks->attempts,
            $mocks->settings
        );

        // Test TSPManager creation.
        $this->assertInstanceOf(
            TSPManager::class,
            $job->tspmanager(),
            'ArchiveJob::tspmanager() did not return an instance of TSPManager'
        );
    }

}
