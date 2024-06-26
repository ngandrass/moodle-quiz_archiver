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
 * Tests for the BackupManager class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;


use backup;
use context_course;
use context_module;

/**
 * Tests for the BackupManager class
 */
class BackupManager_test extends \advanced_testcase {

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
     * Tests the backup of a course
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_course_backup(): void {
        // Initiate a mock course backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $backup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);

        // Check if the backup was created correctly
        $this->assertNotEmpty($backup, 'Backup was not created');
        $this->assertNotEmpty($backup->backupid, 'Backup ID was not set');
        $this->assertEquals($mock->user->id, $backup->userid, 'User ID was not set correctly');
        $this->assertSame(context_course::instance($mock->course->id)->id, $backup->context, 'Context ID was not set correctly');
        $this->assertSame('backup', $backup->component, 'Component was not set correctly');
        $this->assertSame(backup::TYPE_1COURSE, $backup->filearea, 'File area was not set correctly');
        $this->assertNotEmpty($backup->filepath, 'File path was not set');
        $this->assertStringEndsWith('.mbz', $backup->filename, 'File name was not set');
        $this->assertNotEmpty($backup->pathnamehash, 'Path name hash was not set');
        $this->assertStringStartsWith('http', $backup->file_download_url, 'Download URL was not set');
    }

    /**
     * Tests the backup of a quiz
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_quiz_backup(): void {
        // Initiate a mock course backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $backup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Check if the backup was created correctly
        $this->assertNotEmpty($backup, 'Backup was not created');
        $this->assertNotEmpty($backup->backupid, 'Backup ID was not set');
        $this->assertEquals($mock->user->id, $backup->userid, 'User ID was not set correctly');
        $this->assertSame(context_module::instance($mock->quiz->cmid)->id, $backup->context, 'Context ID was not set correctly');
        $this->assertSame('backup', $backup->component, 'Component was not set correctly');
        $this->assertSame(backup::TYPE_1ACTIVITY, $backup->filearea, 'File area was not set correctly');
        $this->assertNotEmpty($backup->filepath, 'File path was not set');
        $this->assertStringEndsWith('.mbz', $backup->filename, 'File name was not set');
        $this->assertNotEmpty($backup->pathnamehash, 'Path name hash was not set');
        $this->assertStringStartsWith('http', $backup->file_download_url, 'Download URL was not set');
    }

    /**
     * Tests the backup of a non-existing course
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_missing_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\dml_exception::class);
        BackupManager::initiate_course_backup(-1, get_admin()->id);
    }

    /**
     * Tests the backup of a non-existing quiz
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_missing_quiz(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\dml_exception::class);
        BackupManager::initiate_quiz_backup(-1, get_admin()->id);
    }

    /**
     * Tests backing up a course without the required privileges
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_course_without_privileges(): void {
        $mocks = $this->generateMockQuiz();

        $this->expectException(\backup_controller_exception::class);
        $this->expectExceptionMessageMatches('/backup_user_missing_capability/');
        BackupManager::initiate_course_backup($mocks->course->id, $mocks->user->id);
    }

    /**
     * Tests backing up a quiz without the required privileges
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_quiz_without_privileges(): void {
        $mocks = $this->generateMockQuiz();

        $this->expectException(\backup_controller_exception::class);
        $this->expectExceptionMessageMatches('/backup_user_missing_capability/');
        BackupManager::initiate_quiz_backup($mocks->quiz->cmid, $mocks->user->id);
    }

    /**
     * Tests the download URL generation with an explicitly given internal_wwwroot
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_download_url_generation_with_internal_wwwroot(): void {
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        set_config('internal_wwwroot', 'http://my-internal-hostname', 'quiz_archiver');

        $backup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $this->assertStringContainsString(
            'http://my-internal-hostname',
            $backup->file_download_url,
            'Download URL was not generated correctly when using internal_wwwroot config'
        );
    }

    /**
     * Tests BackupManager instantiation by backupid
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_initialization_by_existing_backupid(): void {
        // Prepare a course and a quiz backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $expectedCourseBackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedQuizBackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup
        $actualCourseBackup = new BackupManager($expectedCourseBackup->backupid);
        $this->assertNotEmpty($actualCourseBackup, 'Course backup was not created correctly from backup ID');
        $this->assertEquals($expectedCourseBackup->backupid, $actualCourseBackup->get_backupid(), 'Course backup ID was not set correctly');
        $this->assertEquals($expectedCourseBackup->userid, $actualCourseBackup->get_userid(), 'Course user ID was not set correctly');
        $this->assertSame(backup::TYPE_1COURSE, $actualCourseBackup->get_type(), 'Course backup type was not set correctly');

        // Quiz backup
        $actualQuizBackup = new BackupManager($expectedQuizBackup->backupid);
        $this->assertNotEmpty($actualQuizBackup, 'Quiz backup was not created correctly from backup ID');
        $this->assertEquals($expectedQuizBackup->backupid, $actualQuizBackup->get_backupid(), 'Quiz backup ID was not set correctly');
        $this->assertEquals($expectedQuizBackup->userid, $actualQuizBackup->get_userid(), 'Quiz user ID was not set correctly');
        $this->assertSame(backup::TYPE_1ACTIVITY, $actualQuizBackup->get_type(), 'Quiz backup type was not set correctly');
    }

    /**
     * Tests BackupManager instantiation by non-existing backupid
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_initialization_by_non_existing_backupid(): void {
        $this->expectException(\dml_exception::class);
        new BackupManager(-1);
    }

    /**
     * Tests access to backup status values
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_status(): void {
        // Prepare a course and a quiz backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $expectedCourseBackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedQuizBackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup
        $actualCourseBackup = new BackupManager($expectedCourseBackup->backupid);
        $this->assertSame(backup::TYPE_1COURSE, $actualCourseBackup->get_type(), 'Course backup type was not retrieved correctly');
        $actualCourseBackup->is_finished_successfully();
        $actualCourseBackup->is_failed();

        // Quiz backup
        $actualQuizBackup = new BackupManager($expectedQuizBackup->backupid);
        $this->assertSame(backup::TYPE_1ACTIVITY, $actualQuizBackup->get_type(), 'Quiz backup type was not retrieved correctly');
        $actualQuizBackup->is_finished_successfully();
        $actualQuizBackup->is_failed();
    }

    /**
     * Tests backup to job association detection
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_backup_job_association(): void {
        // Prepare a course and a quiz backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $job = ArchiveJob::create(
            '90000000-1234-5678-abcd-ef4242424242',
            $mock->course->id,
            $mock->quiz->cmid,
            $mock->quiz->id,
            $mock->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mock->attempts,
            $mock->settings
        );
        $expectedCourseBackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedQuizBackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup
        $actualCourseBackup = new BackupManager($expectedCourseBackup->backupid);
        $this->assertTrue($actualCourseBackup->is_associated_with_job($job), 'Course backup was not detected as associated with the given job');

        // Quiz backup
        $actualQuizBackup = new BackupManager($expectedQuizBackup->backupid);
        $this->assertTrue($actualQuizBackup->is_associated_with_job($job), 'Quiz backup was not detected as associated with the given job');
    }

    /**
     * Tests backup to job association detection with an invalid job
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_backup_invalid_job_association(): void {
        // Prepare a course and a quiz backup
        $this->setAdminUser();
        $mock = $this->generateMockQuiz();
        $mock->user = get_admin();
        $job = ArchiveJob::create(
            '10000000-1234-5678-abcd-ef4242424242',
            $mock->course->id + 1,
            $mock->quiz->cmid + 1,
            $mock->quiz->id + 1,
            $mock->user->id,
            null,
            'TEST-WS-TOKEN-2',
            $mock->attempts,
            $mock->settings
        );
        $expectedCourseBackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedQuizBackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup
        $actualCourseBackup = new BackupManager($expectedCourseBackup->backupid);
        $this->assertFalse($actualCourseBackup->is_associated_with_job($job), 'Course backup was detected as associated with an unrelated job');

        // Quiz backup
        $actualQuizBackup = new BackupManager($expectedQuizBackup->backupid);
        $this->assertFalse($actualQuizBackup->is_associated_with_job($job), 'Quiz backup was detected as associated with an unrelated job');
    }

}