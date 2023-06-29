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
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use backup;
use backup_controller;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');

/**
 * Manages everything related to backups via the Moodle Backup API
 */
class BackupManager {

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
        'legacyfiles' => true
    ];

    public static function initiate_quiz_backup(int $cm_id, int $user_id): object {
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $cm_id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_ASYNC,
            $user_id,
            backup::RELEASESESSION_YES
        );
        $backupid = $bc->get_backupid();
        $filename = 'quiz_archiver-quiz-backup-'.$cm_id.'-'.date("Ymd-His").'.mbz';

        // Configure backup
        $tasks = $bc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if ($task instanceof \backup_root_task) {
                $task->get_setting('filename')->set_value($filename);

                foreach (self::BACKUP_SETTINGS as $setting_name => $setting_value) {
                    $task->get_setting($setting_name)->set_value($setting_value);
                }
            }
        }

        // Enqueue as adhoc task
        $bc->set_status(backup::STATUS_AWAITING);
        $asynctask = new \core\task\asynchronous_backup_task();
        $asynctask->set_blocking(false);
        $asynctask->set_custom_data(['backupid' => $backupid]);
        $asynctask->set_userid($user_id);
        \core\task\manager::queue_adhoc_task($asynctask);

        return (object) [
            'backupid' => $backupid,
            'filename' => $filename,
        ];
    }

}
