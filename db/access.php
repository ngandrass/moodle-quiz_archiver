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
 * Capability definitions for the quiz_archiver plugin.
 *
 * @package     quiz_archiver
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


$capabilities = [
    // Capability to view the quiz archiver report page.
    'quiz/archiver:view' => [
        'riskbitmask' => (RISK_PERSONAL),
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'mod/quiz_archiver:view',
    ],
    // Capability to create quiz archives.
    'quiz/archiver:create' => [
        'riskbitmask' => (RISK_PERSONAL),
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'mod/quiz_archiver:create',
    ],
    // Capability to delete quiz archives.
    'quiz/archiver:delete' => [
        'riskbitmask' => (RISK_DATALOSS),
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'mod/quiz_archiver:delete',
    ],
    // Capability to use the webservice. Required for the webservice user.
    'quiz/archiver:use_webservice' => [
        'riskbitmask' => (RISK_PERSONAL | RISK_DATALOSS),
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
        'clonepermissionsfrom' => 'mod/quiz_archiver:use_webservice',
    ],
];

$dedeprecatedcapabilities = [
    'mod/quiz_archiver:view' => [
        'replacement' => 'quiz/archiver:view',
        'message' => 'This capability has been deprecated, please use quiz/archiver:view instead.',
    ],
    'mod/quiz_archiver:create' => [
        'replacement' => 'quiz/archiver:create',
        'message' => 'This capability has been deprecated, please use quiz/archiver:create instead.',
    ],
    'mod/quiz_archiver:delete' => [
        'replacement' => 'quiz/archiver:delete',
        'message' => 'This capability has been deprecated, please use quiz/archiver:delete instead.',
    ],
    'mod/quiz_archiver:use_webservice' => [
        'replacement' => 'quiz/archiver:use_webservice',
        'message' => 'This capability has been deprecated, please use quiz/archiver:use_webservice instead.',
    ],
];
