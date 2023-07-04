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
 * Code to be executed during the plugin's database scheme upgrade.
 *
 * @package     quiz_archiver
 * @category    upgrade
 * @copyright   2023 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run to update the plugin database.
 */
function xmldb_quiz_archiver_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023070400) {
        // Define table quiz_report_archiver_files to be created.
        $table = new xmldb_table('quiz_report_archiver_files');

        // Adding fields to table quiz_report_archiver_files.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null);
        $table->add_field('pathnamehash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table quiz_report_archiver_files.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_report_archiver_jobs', ['id']);

        // Conditionally launch create table for quiz_report_archiver_files.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023070400, 'quiz', 'archiver');
    }

    return true;
}
