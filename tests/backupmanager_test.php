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
final class backupmanager_test extends \advanced_testcase {

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
     * Tests the backup of a course
     *
     * @covers \quiz_archiver\BackupManager::initiate_course_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_course_backup(): void {
        // Initiate a mock course backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
        $mock->user = get_admin();
        $backup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);

        // Check if the backup was created correctly.
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
     * @covers \quiz_archiver\BackupManager::initiate_quiz_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_quiz_backup(): void {
        // Initiate a mock course backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
        $mock->user = get_admin();
        $backup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Check if the backup was created correctly.
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
     * @covers \quiz_archiver\BackupManager::initiate_course_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
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
     * @covers \quiz_archiver\BackupManager::initiate_quiz_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
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
     * @covers \quiz_archiver\BackupManager::initiate_course_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_course_without_privileges(): void {
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();

        $this->expectException(\backup_controller_exception::class);
        $this->expectExceptionMessageMatches('/backup_user_missing_capability/');
        BackupManager::initiate_course_backup($mocks->course->id, $mocks->user->id);
    }

    /**
     * Tests backing up a quiz without the required privileges
     *
     * @covers \quiz_archiver\BackupManager::initiate_quiz_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_quiz_without_privileges(): void {
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();

        $this->expectException(\backup_controller_exception::class);
        $this->expectExceptionMessageMatches('/backup_user_missing_capability/');
        BackupManager::initiate_quiz_backup($mocks->quiz->cmid, $mocks->user->id);
    }

    /**
     * Tests the download URL generation with an explicitly given internal_wwwroot
     *
     * @covers \quiz_archiver\BackupManager::initiate_course_backup
     * @covers \quiz_archiver\BackupManager::initiate_backup
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_download_url_generation_with_internal_wwwroot(): void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
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
     * @covers \quiz_archiver\BackupManager::__construct
     * @covers \quiz_archiver\BackupManager::get_backupid
     * @covers \quiz_archiver\BackupManager::get_userid
     * @covers \quiz_archiver\BackupManager::get_type
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_initialization_by_existing_backupid(): void {
        // Prepare a course and a quiz backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
        $mock->user = get_admin();
        $expectedcoursebackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedquizbackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup.
        // @codingStandardsIgnoreStart
        $actualcoursebackup = new BackupManager($expectedcoursebackup->backupid);
        $this->assertNotEmpty($actualcoursebackup, 'Course backup was not created correctly from backup ID');
        $this->assertEquals($expectedcoursebackup->backupid, $actualcoursebackup->get_backupid(), 'Course backup ID was not set correctly');
        $this->assertEquals($expectedcoursebackup->userid, $actualcoursebackup->get_userid(), 'Course user ID was not set correctly');
        $this->assertSame(backup::TYPE_1COURSE, $actualcoursebackup->get_type(), 'Course backup type was not set correctly');

        // Quiz backup.
        $actualquizbackup = new BackupManager($expectedquizbackup->backupid);
        $this->assertNotEmpty($actualquizbackup, 'Quiz backup was not created correctly from backup ID');
        $this->assertEquals($expectedquizbackup->backupid, $actualquizbackup->get_backupid(), 'Quiz backup ID was not set correctly');
        $this->assertEquals($expectedquizbackup->userid, $actualquizbackup->get_userid(), 'Quiz user ID was not set correctly');
        $this->assertSame(backup::TYPE_1ACTIVITY, $actualquizbackup->get_type(), 'Quiz backup type was not set correctly');
        // @codingStandardsIgnoreEnd
    }

    /**
     * Tests BackupManager instantiation by non-existing backupid
     *
     * @covers \quiz_archiver\BackupManager::__construct
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
     * @covers \quiz_archiver\BackupManager::get_status
     * @covers \quiz_archiver\BackupManager::is_finished_successfully
     * @covers \quiz_archiver\BackupManager::is_failed
     * @covers \quiz_archiver\BackupManager::get_type
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public function test_backup_status(): void {
        // Prepare a course and a quiz backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
        $mock->user = get_admin();
        $expectedcoursebackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedquizbackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup.
        $actualcoursebackup = new BackupManager($expectedcoursebackup->backupid);
        $this->assertSame(backup::TYPE_1COURSE, $actualcoursebackup->get_type(), 'Course backup type was not retrieved correctly');
        $actualcoursebackup->is_finished_successfully();
        $actualcoursebackup->is_failed();

        // Quiz backup.
        $actualquizbackup = new BackupManager($expectedquizbackup->backupid);
        $this->assertSame(backup::TYPE_1ACTIVITY, $actualquizbackup->get_type(), 'Quiz backup type was not retrieved correctly');
        $actualquizbackup->is_finished_successfully();
        $actualquizbackup->is_failed();
    }

    /**
     * Tests backup to job association detection
     *
     * @covers \quiz_archiver\BackupManager::is_associated_with_job
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_backup_job_association(): void {
        // Prepare a course and a quiz backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
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
        $expectedcoursebackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedquizbackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup.
        $actualcoursebackup = new BackupManager($expectedcoursebackup->backupid);
        $this->assertTrue(
            $actualcoursebackup->is_associated_with_job($job),
            'Course backup was not detected as associated with the given job'
        );

        // Quiz backup.
        $actualquizbackup = new BackupManager($expectedquizbackup->backupid);
        $this->assertTrue(
            $actualquizbackup->is_associated_with_job($job),
            'Quiz backup was not detected as associated with the given job'
        );
    }

    /**
     * Tests backup to job association detection with an invalid job
     *
     * @covers \quiz_archiver\BackupManager::is_associated_with_job
     *
     * @return void
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_backup_invalid_job_association(): void {
        // Prepare a course and a quiz backup.
        $this->setAdminUser();
        $this->resetAfterTest();
        $mock = $this->getDataGenerator()->create_mock_quiz();
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
        $expectedcoursebackup = BackupManager::initiate_course_backup($mock->course->id, $mock->user->id);
        $expectedquizbackup = BackupManager::initiate_quiz_backup($mock->quiz->cmid, $mock->user->id);

        // Course backup.
        $actualcoursebackup = new BackupManager($expectedcoursebackup->backupid);
        $this->assertFalse(
            $actualcoursebackup->is_associated_with_job($job),
            'Course backup was detected as associated with an unrelated job'
        );

        // Quiz backup.
        $actualquizbackup = new BackupManager($expectedquizbackup->backupid);
        $this->assertFalse(
            $actualquizbackup->is_associated_with_job($job),
            'Quiz backup was detected as associated with an unrelated job'
        );
    }

}
