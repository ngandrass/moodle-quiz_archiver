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
 * Web service function declarations for the quiz_archiver plugin.
 *
 * @package     quiz_archiver
 * @copyright   2023 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'quiz_archiver_generate_attempt_report' => [
        'classname' => 'quiz_archiver\external\generate_attempt_report',
        'description' => 'Generates a full HTML DOM containing all report data on the specified attempt',
        'type' => 'read',
        'ajax' => true,
        'services' => [
            MOODLE_OFFICIAL_MOBILE_SERVICE,
        ],
        'capabilities' => 'mod/quiz:grade,quiz/grading:viewstudentnames,quiz/grading:viewidnumber',
    ],
];