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
 * Legacy lib definitions
 *
 * @package   quiz_archiver
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quiz_archiver\FileManager;

/**
 * Serve quiz_archiver files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 *
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 *
 * @throws coding_exception
 * @throws required_capability_exception
 */
function quiz_archiver_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    // Check permissions.
    require_login($course, false, $cm);
    require_capability('mod/quiz:grade', $context);
    require_capability('quiz/grading:viewstudentnames', $context);
    require_capability('quiz/grading:viewidnumber', $context);

    // Validate course
    if ($args[1] !== $course->id) {
        send_file_not_found();
        return false;
    }

    // Try to serve file
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/".FileManager::COMPONENT_NAME."/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}
