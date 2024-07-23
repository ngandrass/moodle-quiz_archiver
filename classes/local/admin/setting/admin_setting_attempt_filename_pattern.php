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

namespace quiz_archiver\local\admin\setting;

use quiz_archiver\ArchiveJob;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Custom admin setting for attempt filename pattern input fields
 *
 * @codeCoverageIgnore
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_attempt_filename_pattern extends \admin_setting_configtext {

    /**
     * Validate data before storage
     * @param string $data data
     * @return mixed true if ok string if error found
     * @throws \coding_exception
     */
    public function validate($data) {
        // Basic data validation.
        $parentvalidation = parent::validate($data);
        if ($parentvalidation !== true) {
            return $parentvalidation;
        }

        // Validate filename pattern.
        if (!ArchiveJob::is_valid_attempt_filename_pattern($data)) {
            return get_string('error_invalid_attempt_filename_pattern', 'quiz_archiver');
        }

        return true;
    }

}
