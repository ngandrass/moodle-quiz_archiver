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
 * Tests for the get_job_metadata external service
 *
 * @package   quiz_archiver
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

use core_external\external_api;
use quiz_archiver\ArchiveJob;
use quiz_archiver\TSPManager;

/**
 * Tests for the get_job_metadata external service
 */
final class get_job_metadata_test extends \advanced_testcase {
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
     * Ensures the plugin settings that execute() relies on are present.
     *
     * @return void
     */
    protected function ensure_config(): void {
        if (get_config('quiz_archiver', 'job_timeout_min') === false) {
            set_config('job_timeout_min', 60, 'quiz_archiver');
        }
        // Force-enable TSP so that signature.enabled is deterministic.
        set_config('tsp_enable', true, 'quiz_archiver');
    }

    /**
     * Creates a job for the given mocks in the requested status
     *
     * @param \stdClass $mocks Mock quiz context
     * @param string $uuid Job UUID
     * @param string $status Initial job status
     * @return ArchiveJob
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function make_job(
        \stdClass $mocks,
        string $uuid,
        string $status = ArchiveJob::STATUS_AWAITING_PROCESSING
    ): ArchiveJob {
        $job = ArchiveJob::create(
            $uuid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN',
            [],
            [],
            $status
        );
        return $job;
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function test_capability_requirement(): void {
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '10000000-1234-5678-abcd-ef4242424242');

        // Students can access the course but must not see archive jobs.
        $student = $this->getDataGenerator()->create_and_enrol($mocks->course, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*' . preg_quote(get_string('archiver:view', 'quiz_archiver')) . '.*/');
        get_job_metadata::execute($job->get_jobid());
    }

    /**
     * Test that users that can not access the course are rejected
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function test_course_access_requirement(): void {
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '11000000-1234-5678-abcd-ef4242424242');

        // A user that is not enrolled in the course must be rejected.
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\require_login_exception::class);
        get_job_metadata::execute($job->get_jobid());
    }

    /**
     * Test that users with the view capability (but without webservice
     * capability) are allowed to retrieve job metadata
     *
     * @dataProvider privileged_role_data_provider
     * @covers       \quiz_archiver\external\get_job_metadata::execute
     *
     * @param string $role Shortname of the role to enrol the user with
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public function test_access_with_view_capability(string $role): void {
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '12000000-1234-5678-abcd-ef4242424242');

        $user = $this->getDataGenerator()->create_and_enrol($mocks->course, $role);
        $this->setUser($user);

        $res = get_job_metadata::execute($job->get_jobid());
        $this->assertSame('OK', $res['status']);
        $this->assertSame($job->get_jobid(), $res['job']['uuid']);
    }

    /**
     * Data provider for test_access_with_view_capability
     *
     * @return array[] Test data
     */
    public static function privileged_role_data_provider(): array {
        return [
            'Non-editing teacher' => ['role' => 'teacher'],
            'Editing teacher' => ['role' => 'editingteacher'],
            'Manager' => ['role' => 'manager'],
        ];
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            get_job_metadata::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            get_job_metadata::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     * @covers       \quiz_archiver\external\get_job_metadata::execute
     * @covers       \quiz_archiver\external\get_job_metadata::validate_parameters
     *
     * @param string|null $jobid Job ID to check (null -> valid job)
     * @param bool $shouldfail Whether a validation failure is expected
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_parameter_validation(?string $jobid, bool $shouldfail): void {
        // Gain view permission.
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '20000000-1234-5678-abcd-ef4242424242');
        $_GET['wstoken'] = 'TEST-WS-TOKEN';

        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        // A valid jobid must be accepted (and return the full job structure).
        $res = get_job_metadata::execute($jobid === null ? $job->get_jobid() : $jobid);
        if (!$shouldfail) {
            $this->assertSame('OK', $res['status']);
        }
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public static function parameter_data_provider(): array {
        return [
            'Valid' => [
                'jobid' => null,
                'shouldfail' => false,
            ],
            'Invalid jobid' => [
                'jobid' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ],
        ];
    }

    /**
     * Tests a successful execute() call returning the full job structure
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_returns_job_data(): void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '30000000-1234-5678-abcd-ef4242424242', ArchiveJob::STATUS_RUNNING);
        $job->set_status(ArchiveJob::STATUS_RUNNING, ['progress' => 42]);

        $res = get_job_metadata::execute($job->get_jobid());

        // Top level.
        $this->assertSame('OK', $res['status']);
        $this->assertArrayHasKey('job', $res);
        $this->assertArrayHasKey('display_args', $res);

        // Job core fields.
        $jobdata = $res['job'];
        $this->assertSame($job->get_jobid(), $jobdata['uuid']);
        $this->assertSame(ArchiveJob::STATUS_RUNNING, $jobdata['status']);
        $this->assertFalse($jobdata['complete']);
        $this->assertIsNumeric($jobdata['timecreated']);
        $this->assertIsNumeric($jobdata['timemodified']);

        // Embedded entities / metadata.
        $this->assertEquals($mocks->course->id, $jobdata['course']['id']);
        $this->assertEquals($mocks->quiz->id, $jobdata['quiz']['id']);
        $this->assertEquals($mocks->quiz->cmid, $jobdata['quiz']['cmid']);
        $this->assertIsArray($jobdata['settings']);
        $this->assertEquals(42, $jobdata['statusextras']['progress']);

        // Required action URLs.
        $urls = $jobdata['actionurls'];
        $this->assertStringContainsString('mode=archiver', $urls['showcontent']);
        $this->assertStringContainsString('action=showcontents', $urls['showcontent']);
        $this->assertStringContainsString('action=delete_artifact', $urls['artifact_delete']);
        $this->assertStringContainsString('action=sign_job', $urls['artifact_sign']);
        $this->assertStringContainsString('action=delete_job', $urls['job_delete']);
        $this->assertStringContainsString('/course/view.php', $urls['course']);
        $this->assertStringContainsString('/mod/quiz/view.php', $urls['quiz']);
        $this->assertStringContainsString('/user/profile.php', $urls['user']);
        $this->assertNull($urls['artifact_download'], 'Download URL must be null without artifact');
        $this->assertArrayNotHasKey('tsp_query_download', $urls);

        // Signature + autodelete blocks.
        $this->assertTrue($jobdata['signature']['enabled']);
        $this->assertFalse($jobdata['autodelete']['enabled']);
        $this->assertFalse($jobdata['autodelete']['done']);

        // Display args.
        $display = $res['display_args'];
        $this->assertSame(
            ArchiveJob::get_status_display_args(ArchiveJob::STATUS_RUNNING, $job->get_statusextras()),
            $display['status']
        );
        $this->assertNull($display['filesize'], 'Filesize must be null without an artifact');
        $this->assertIsString($display['autodelete_str']);

        // No artifact.
        $this->assertArrayNotHasKey('artifact', $jobdata);
    }

    /**
     * Tests execute() for a finished job with an artifact.
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_finished_with_artifact(): void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '40000000-1234-5678-abcd-ef4242424242');
        $artifactfilename = 'testartifact.tar.gz';
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $artifactfilename
        );
        $job->link_artifact($artifact->get_id(), 'sha256dummy');
        $job->set_status(ArchiveJob::STATUS_FINISHED);

        $res = get_job_metadata::execute($job->get_jobid());
        $jobdata = $res['job'];

        $this->assertTrue($jobdata['complete']);
        $this->assertSame(ArchiveJob::STATUS_FINISHED, $jobdata['status']);

        // Artifact facts.
        $this->assertArrayHasKey('artifact', $jobdata);
        $this->assertSame($artifactfilename, $jobdata['artifact']['name']);
        $this->assertIsNumeric($jobdata['artifact']['size']);
        $this->assertArrayHasKey('checksum', $jobdata['artifact']);

        // Download URLs in action URls.
        $this->assertArrayHasKey('artifact_download', $jobdata['actionurls']);
        $this->assertStringContainsString('pluginfile.php', $jobdata['actionurls']['artifact_download']);
        $this->assertStringContainsString($artifactfilename, $jobdata['actionurls']['artifact_download']);

        // Filesize surfaced as a display value.
        $this->assertSame(display_size($artifact->get_filesize()), $res['display_args']['filesize']);
    }

    /**
     * Tests execute() for a finished, TSP-signed job, verifying the signature
     * and TSP download URLs are included.
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_execute_signed_artifact(): void {
        global $DB;
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->ensure_config();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = $this->make_job($mocks, '50000000-1234-5678-abcd-ef4242424242');
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'signedartifact.tar.gz'
        );
        $job->link_artifact($artifact->get_id(), 'sha256dummy');
        $job->set_status(ArchiveJob::STATUS_FINISHED);

        // Register a TSP timestamp.
        $tspserveraddress = 'testserver.example.com';
        $DB->insert_record(TSPManager::TSP_TABLE_NAME, [
            'jobid' => $job->get_id(),
            'timecreated' => time(),
            'server' => $tspserveraddress,
            'timestampquery' => 'tspquery',
            'timestampreply' => 'tspreply',
        ]);

        $res = get_job_metadata::execute($job->get_jobid());
        $jobdata = $res['job'];

        $this->assertTrue($jobdata['signature']['enabled']);
        $this->assertArrayHasKey('tsp', $jobdata['signature']);
        $this->assertIsNumeric($jobdata['signature']['tsp']['timecreated']);
        $this->assertSame($tspserveraddress, $jobdata['signature']['tsp']['server']);

        $this->assertArrayHasKey('tsp_query_download', $jobdata['actionurls']);
        $this->assertArrayHasKey('tsp_reply_download', $jobdata['actionurls']);
        $this->assertStringContainsString('query', $jobdata['actionurls']['tsp_query_download']);
        $this->assertStringContainsString('pluginfile.php', $jobdata['actionurls']['tsp_query_download']);
        $this->assertStringContainsString('reply', $jobdata['actionurls']['tsp_reply_download']);
        $this->assertStringContainsString('pluginfile.php', $jobdata['actionurls']['tsp_reply_download']);
    }

    /**
     * Test that invalid job UUIDs are reported as not found
     *
     * @covers \quiz_archiver\external\get_job_metadata::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public function test_invalid_job(): void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->ensure_config();
        $res = external_api::clean_returnvalue(
            get_job_metadata::execute_returns(),
            get_job_metadata::execute('00000000-0000-0000-0000-000000000000')
        );
        $this->assertSame(
            ['status' => 'E_JOB_NOT_FOUND'],
            $res,
            'Invalid job ID should return E_JOB_NOT_FOUND'
        );
    }
}
