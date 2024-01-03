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
 * @copyright   2024 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run to update the plugin database.
 *
 * @param int $oldversion The version we are upgrading from
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

    if ($oldversion < 2023070500) {
        // Define field artifactfilechecksum to be added to quiz_report_archiver_jobs.
        $table = new xmldb_table('quiz_report_archiver_jobs');
        $field = new xmldb_field('artifactfilechecksum', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'artifactfileid');

        // Conditionally launch add field artifactfilechecksum.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023070500, 'quiz', 'archiver');
    }

    if ($oldversion < 2023072700) {
        // Replace foreign-unique key with simple foreign key for userid in quiz_report_archiver_jobs.
        $table = new xmldb_table('quiz_report_archiver_jobs');
        $old_key = new xmldb_key('userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);
        $new_key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Perform key exchange.
        $dbman->drop_key($table, $old_key);
        $dbman->add_key($table, $new_key);

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023072700, 'quiz', 'archiver');
    }

    if ($oldversion < 2023080104) {
        // Remove foreign key constraints with reftables to be renamed
        $dbman->drop_key(
            new xmldb_table('quiz_report_archiver_files'),
            new xmldb_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_report_archiver_jobs', ['id'])
        );

        // Rename tables to remove the "report_" prefix
        $dbman->rename_table(
            new xmldb_table('quiz_report_archiver_jobs'),
            'quiz_archiver_jobs'
        );
        $dbman->rename_table(
            new xmldb_table('quiz_report_archiver_files'),
            'quiz_archiver_files'
        );

        // Restore foreign key constraints
        $dbman->add_key(
            new xmldb_table('quiz_archiver_files'),
            new xmldb_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_archiver_jobs', ['id'])
        );

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023080104, 'quiz', 'archiver');
    }

    if ($oldversion < 2023081400) {
        // Define table quiz_archiver_job_settings to be created.
        $table = new xmldb_table('quiz_archiver_job_settings');

        // Adding fields to table quiz_archiver_job_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('key', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table quiz_archiver_job_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_archiver_jobs', ['id']);

        // Conditionally launch create table for quiz_archiver_job_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023081400, 'quiz', 'archiver');
    }

    if ($oldversion < 2023101800) {
        // Define table quiz_archiver_tsp to be created.
        $table = new xmldb_table('quiz_archiver_tsp');

        // Adding fields to table quiz_archiver_tsp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('server', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestampquery', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestampreply', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table quiz_archiver_tsp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_archiver_jobs', ['id']);

        // Conditionally launch create table for quiz_archiver_tsp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023101800, 'quiz', 'archiver');
    }

    if ($oldversion < 2023111500) {
        // Define table quiz_archiver_attempts to be created.
        $table = new xmldb_table('quiz_archiver_attempts');

        // Adding fields to table quiz_archiver_attempts.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('jobid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table quiz_archiver_attempts.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('jobid', XMLDB_KEY_FOREIGN, ['jobid'], 'quiz_archiver_jobs', ['id']);

        // Conditionally launch create table for quiz_archiver_attempts.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2023111500, 'quiz', 'archiver');
    }

    if ($oldversion < 2024010300) {
        // Define field retentiontime to be added to quiz_archiver_jobs.
        $table = new xmldb_table('quiz_archiver_jobs');
        $field = new xmldb_field('retentiontime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field retentiontime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Archiver savepoint reached.
        upgrade_plugin_savepoint(true, 2024010300, 'quiz', 'archiver');
    }

    return true;
}
