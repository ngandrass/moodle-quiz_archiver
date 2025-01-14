<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Patch renamed classes for Moodle 4.1 and below.
 *
 * @see https://moodledev.io/docs/4.2/devupdate
 *
 * @package     quiz_archiver
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


global $CFG;

if ($CFG->branch <= 401) {
    require_once($CFG->dirroot.'/mod/quiz/locallib.php');
    require_once($CFG->dirroot.'/lib/external/externallib.php');

    // Patch renamed classes.
    foreach ([
        // External API.
        'external_api' => 'core_external\external_api',
        'external_description' => 'core_external\external_description',
        'external_files' => 'core_external\files',
        'external_format_value' => 'core_external\external_format_value',
        'external_function_parameters' => 'core_external\external_function_parameters',
        'external_multiple_structure' => 'core_external\external_multiple_structure',
        'external_settings' => 'core_external\external_settings',
        'external_single_structure' => 'core_external\external_single_structure',
        'external_util' => 'core_external\util',
        'external_value' => 'core_external\external_value',
        'external_warnings' => 'core_external\external_warnings',
        'restricted_context_exception' => 'core_external\restricted_context_exception',

        // Module: mod_quiz.
        'quiz_default_report' => 'mod_quiz\local\reports\report_base',
        'quiz_attempt' => 'mod_quiz\quiz_attempt',
        'mod_quiz_display_options' => 'mod_quiz\question\display_options',
    ] as $old => $new) {
        if (class_exists($old)) {
            class_alias($old, $new);
        }
    }
}
