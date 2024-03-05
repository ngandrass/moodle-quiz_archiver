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
 * @copyright   2024 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Capability to view the quiz archiver report page.
    'mod/quiz_archiver:view' => [
        'riskbitmask' => (RISK_PERSONAL),
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    // Capability to create and delete quiz archives.
    'mod/quiz_archiver:create' => [
        'riskbitmask' => (RISK_PERSONAL),
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    // Capability to delete quiz archives.
    'mod/quiz_archiver:delete' => [
        'riskbitmask' => (RISK_DATALOSS),
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    // Capability to use the webservice. Required for the webservice user.
    'mod/quiz_archiver:use_webservice' => [
        'riskbitmask' => (RISK_PERSONAL | RISK_DATALOSS),
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],
    // Capability to use the webservice. Required for the webservice user.
    'mod/quiz_archiver:getownarchive' => [
        'riskbitmask' => (RISK_PERSONAL),
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
