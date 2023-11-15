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
 * This file the privacy provider class for the quiz_archiver plugin.
 *
 * @package   quiz_archiver
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use quiz_archiver\ArchiveJob;
use quiz_archiver\TSPManager;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for quiz_archiver
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {


    public static function get_metadata(collection $collection): collection {
        // Quiz archive files
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        // Database tables
        $collection->add_database_table('quiz_archiver_jobs', [
            'courseid' => 'privacy:metadata:quiz_archiver_jobs:courseid',
            'cmid' => 'privacy:metadata:quiz_archiver_jobs:cmid',
            'quizid' => 'privacy:metadata:quiz_archiver_jobs:quizid',
            'userid' => 'privacy:metadata:quiz_archiver_jobs:userid',
            'timecreated' => 'privacy:metadata:quiz_archiver_jobs:timecreated',
            'timemodified' => 'privacy:metadata:quiz_archiver_jobs:timemodified',
        ], 'privacy:metadata:quiz_archiver_jobs');

        $collection->add_database_table('quiz_archiver_job_settings', [
            'key' => 'privacy:metadata:quiz_archiver_job_settings:key',
            'value' => 'privacy:metadata:quiz_archiver_job_settings:value',
        ], 'privacy:metadata:quiz_archiver_job_settings');

        $collection->add_database_table('quiz_archiver_tsp', [
            'timecreated' => 'privacy:metadata:quiz_archiver_tsp:timecreated',
            'server' => 'privacy:metadata:quiz_archiver_tsp:server',
            'timestampquery' => 'privacy:metadata:quiz_archiver_tsp:timestampquery',
            'timestampreply' => 'privacy:metadata:quiz_archiver_tsp:timestampreply',
        ], 'privacy:metadata:quiz_archiver_tsp');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // TODO: Handle userdata inside the quiz archives

        // Get all contexts where the user has a quiz archiver job
        // Note: The context stays the same across all entries for a single
        //       archive job. Hence, we only query the main job table.
        $contextlist->add_from_sql("
            SELECT DISTINCT c.id
            FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {quiz} q ON q.id = cm.instance
                JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
                    WHERE (
                    j.userid        = :userid
                    )
            ",
            [
                'modname'       => 'quiz',
                'contextlevel'  => CONTEXT_MODULE,
                'userid'        => $userid,
            ]
        );

        return $contextlist;
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // TODO: Handle userdata inside the quiz archives
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Process all contexts
        foreach ($contextlist->get_contexts() as $ctx) {
            $ctxData = [];

            // Get existing jobs for current context
            $jobs = $DB->get_records_sql("
                SELECT *
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {quiz} q ON q.id = cm.instance
                    JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
                WHERE (
                    j.userid = :userid AND
                    c.id = :contextid
                )
            ", [
                'contextid' => $ctx->id,
                'userid' => $userid,
            ]);

            foreach ($jobs as $job) {
                // Get job settings
                $job_settings = $DB->get_records(
                    ArchiveJob::JOB_SETTINGS_TABLE_NAME,
                    ['jobid' => $job->id],
                    '',
                    'key, value'
                );

                // Get TSP data
                $tsp_data = $DB->get_record(
                    TSPManager::TSP_TABLE_NAME,
                    ['jobid' => $job->id],
                    'timecreated, server, timestampquery, timestampreply',
                    IGNORE_MISSING
                );

                // Encode TSP data as base64 if present
                if ($tsp_data) {
                    $tsp_data->timestampquery = base64_encode($tsp_data->timestampquery);
                    $tsp_data->timestampreply = base64_encode($tsp_data->timestampreply);
                }

                // Add job data to current context
                $ctxData["Archive Job: {$job->jobid}"] = [
                    'courseid' => $job->courseid,
                    'cmid' => $job->cmid,
                    'quizid' => $job->quizid,
                    'userid' => $job->userid,
                    'timecreated' => $job->timecreated,
                    'timemodified' => $job->timemodified,
                    'settings' => $job_settings,
                    'tsp' => $tsp_data,
                ];

                // TODO: Add artifact file handling
            }

            // Export data to context
            writer::with_context($ctx)->export_data(
                [get_string('pluginname', 'quiz_archiver')],
                (object) $ctxData
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        // TODO: Implement delete_data_for_all_users_in_context() method.
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // TODO: Implement delete_data_for_user() method.
    }

    public static function get_users_in_context(userlist $userlist) {
        // TODO: Handle userdata inside the quiz archives
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Job metadata
        $userlist->add_from_sql(
            'userid',
            "
            SELECT j.userid
            FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {quiz} q ON q.id = cm.instance
                JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
            WHERE cm.id = :instanceid
            ",
            [
                'instanceid'    => $context->instanceid,
                'modulename'    => 'quiz',
            ]
        );
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        // TODO: Implement delete_data_for_users() method.
    }

}
