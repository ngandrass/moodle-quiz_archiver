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
 * Defines a scheduled task to clean up temporary files
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\task;

use quiz_archiver\FileManager;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore



/**
 * Scheduled task to periodically clean up temporary files.
 *
 * @codeCoverageIgnore This is just a wrapper for FileManager::cleanup_temp_files()
 */
class cleanup_temp_files extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name(): string {
        return get_string('task_cleanup_temp_files', 'quiz_archiver');
    }

    /**
     * Execute the task.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function execute(): void {
        echo get_string('task_cleanup_temp_files_start', 'quiz_archiver') . "\n";
        $numfilesdeleted = FileManager::cleanup_temp_files();
        echo get_string('task_cleanup_temp_files_report', 'quiz_archiver', $numfilesdeleted) . "\n";
    }

}
