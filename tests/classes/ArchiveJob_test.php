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
                'setting 1' => 'value 1',
                'setting 2' => 2,
                'setting 3' => true,
                'setting 4' => false,
                'setting 5' => null,
                'setting 6' => 1337.42,
                'setting 7' => 'いろはにほへとちりぬるを'
            ],
        ];
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
        $this->assertEquals(
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

}