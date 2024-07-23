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
 * This file defines the BackupManager class.
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use backup;
use backup_controller;
use context_course;
use context_module;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php'); // @codeCoverageIgnore

/**
 * Manages everything related to backups via the Moodle Backup API
 */
class BackupManager {

    /** @var \stdClass Backup controller metadata from DB */
    protected \stdClass $backupmetadata;

    /** @var array Define what to include and exclude in backups */
    const BACKUP_SETTINGS = [
        'users' => true,
        'anonymize' => false,
        'role_assignments' => true,
        'activities' => true,
        'blocks' => true,
        'files' => true,
        'filters' => true,
        'comments' => true,
        'badges' => true,
        'calendarevents' => true,
        'userscompletion' => true,
        'logs' => true,
        'grade_histories' => true,
        'questionbank' => true,
        'groups' => true,
        'contentbankcontent' => true,
        'legacyfiles' => true,
    ];

    /**
     * Creates a new BackupManager instance
     *
     * @param string $backupid ID of the backup_controller associated with this backup
     * @throws \dml_exception
     */
    public function __construct(string $backupid) {
        global $DB;

        $this->backupmetadata = $DB->get_record(
            'backup_controllers',
            ['backupid' => $backupid],
            'id, backupid, operation, type, itemid, userid',
            MUST_EXIST
        );
        if ($this->backupmetadata->operation != 'backup') {
            throw new \ValueError('Only backup operations are supported.');
        }
    }

    /**
     * Determines if the backup finished successfully
     *
     * @return bool True if backup finished successfully
     * @throws \dml_exception
     */
    public function is_finished_successfully(): bool {
        return $this->get_status() === backup::STATUS_FINISHED_OK;
    }

    /**
     * Determines if the backup failed
     *
     * @return bool True if backup finished with error
     * @throws \dml_exception
     */
    public function is_failed(): bool {
        return $this->get_status() === backup::STATUS_FINISHED_ERR;
    }

    /**
     * Retrieves the current status of this backup
     *
     * @return int Raw backup status value according to backup_controller::STATUS_*
     * @throws \dml_exception
     */
    public function get_status(): int {
        global $DB;
        return $DB->get_record('backup_controllers', ['id' => $this->backupmetadata->id], 'status')->status;
    }

    /**
     * Retrieves the backupid of this instance
     *
     * @return string Backup ID
     */
    public function get_backupid(): string {
        return $this->backupmetadata->backupid;
    }

    /**
     * Retrieves the type of this backup controller
     *
     * @return string Type of this backup controller (e.g. course, activity)
     */
    public function get_type(): string {
        return $this->backupmetadata->type;
    }

    /**
     * Retrieves the ID of the user that initiated this backup
     *
     * @return int User-ID of the backup initiator
     */
    public function get_userid(): int {
        return $this->backupmetadata->userid;
    }

    /**
     * Determines if this BackupManager is associated with the given ArchiveJob
     *
     * @param ArchiveJob $job Job to probe relationship to
     * @return bool True if this BackupManager is related to the given ArchiveJob
     */
    public function is_associated_with_job(ArchiveJob $job): bool {
        switch ($this->get_type()) {
            case backup::TYPE_1ACTIVITY:
                return $this->backupmetadata->itemid == $job->get_cmid();
            case backup::TYPE_1COURSE:
                return $this->backupmetadata->itemid == $job->get_courseid();
            default:
                return false;
        }
    }

    /**
     * Initiates a new quiz backup
     *
     * @param string $type Type of the backup, based on backup::TYPE_*
     * @param int $id ID of the backup object
     * @param int $userid User-ID to associate this backup with
     * @return object Backup metadata object
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    protected static function initiate_backup(string $type, int $id, int $userid): object {
        global $CFG;

        // Validate type and set variables accordingly.
        switch ($type) {
            case backup::TYPE_1COURSE:
                $contextid = context_course::instance($id)->id;
                break;
            case backup::TYPE_1ACTIVITY:
                $contextid = context_module::instance($id)->id;
                break;
            default:
                throw new \ValueError("Backup type not supported");
        }

        // Initialize backup.
        $bc = new backup_controller(
            $type,
            $id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_ASYNC,
            $userid,
            backup::RELEASESESSION_YES
        );
        $backupid = $bc->get_backupid();
        $filename = 'quiz_archiver-'.$type.'-backup-'.$id.'-'.date("Ymd-His").'.mbz';

        // Configure backup.
        $tasks = $bc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if ($task instanceof \backup_root_task) {
                $task->get_setting('filename')->set_value($filename);

                foreach (self::BACKUP_SETTINGS as $name => $value) {
                    $task->get_setting($name)->set_value($value);
                }
            }
        }

        // Enqueue as adhoc task.
        $bc->set_status(backup::STATUS_AWAITING);
        $asynctask = new \core\task\asynchronous_backup_task();
        $asynctask->set_custom_data(['backupid' => $backupid]);
        $asynctask->set_userid($userid);
        \core\task\manager::queue_adhoc_task($asynctask);

        // Generate backup file url.
        $url = strval(\moodle_url::make_webservice_pluginfile_url(
            $contextid,
            'backup',
            $type,
            null,  // The make_webservice_pluginfile_url expects null if no itemid is given against it's PHPDoc specification ...
            '/',
            $filename
        ));

        $internalwwwroot = get_config('quiz_archiver')->internal_wwwroot;
        if ($internalwwwroot) {
            $url = str_replace(rtrim($CFG->wwwroot, '/'), rtrim($internalwwwroot, '/'), $url);
        }

        return (object) [
            'backupid' => $backupid,
            'userid' => $userid,
            'context' => $contextid,
            'component' => 'backup',
            'filearea' => $type,
            'filepath' => '/',
            'filename' => $filename,
            'itemid' => null,
            'pathnamehash' => \file_storage::get_pathname_hash($contextid, 'backup', $type, 0, '/', $filename),
            'file_download_url' => $url,
        ];
    }

    /**
     * Initiates a new quiz backup
     *
     * @param int $cmid ID of the course module for the quiz
     * @param int $userid User-ID to associate this backup with
     * @return object Backup metadata object
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public static function initiate_quiz_backup(int $cmid, int $userid): object {
        return self::initiate_backup(backup::TYPE_1ACTIVITY, $cmid, $userid);
    }

    /**
     * Initiates a new course backup
     *
     * @param int $courseid ID of the course module for the quiz
     * @param int $userid User-ID to associate this backup with
     * @return object Backup metadata object
     * @throws \base_setting_exception
     * @throws \base_task_exception
     * @throws \dml_exception
     */
    public static function initiate_course_backup(int $courseid, int $userid): object {
        return self::initiate_backup(backup::TYPE_1COURSE, $courseid, $userid);
    }

}
