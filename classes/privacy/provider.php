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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
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
use quiz_archiver\FileManager;
use quiz_archiver\TSPManager;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Privacy provider for quiz_archiver
 *
 * @codeCoverageIgnore This is handled by Moodle core tests
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // Quiz archive files.
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        // Database tables.
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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get all contexts where the user has a quiz archiver job.
        // Note: The context stays the same across all entries for a single
        // archive job. Hence, we only query the main job table.
        $contextlist->add_from_sql("
            SELECT DISTINCT c.id
            FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {quiz} q ON q.id = cm.instance
                JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
            WHERE j.userid = :userid
            ",
            [
                'modname'       => 'quiz',
                'contextlevel'  => CONTEXT_MODULE,
                'userid'        => $userid,
            ]
        );

        // Add all contexts where the user is part of a quiz archive.
        $contextlist->add_from_sql("
            SELECT DISTINCT c.id
            FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {quiz} q ON q.id = cm.instance
                JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
                JOIN {".ArchiveJob::ATTEMPTS_TABLE_NAME."} a ON a.jobid = j.id
            WHERE a.userid = :userid
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
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Process all contexts.
        $subctxbase = get_string('pluginname', 'quiz_archiver');
        foreach ($contextlist->get_contexts() as $ctx) {
            // Get existing jobs for current context.
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

            // Export each job.
            foreach ($jobs as $job) {
                // Set correct subcontext for the job.
                $subctx = [$subctxbase, "Job: {$job->jobid}"];

                // Get job settings.
                $jobsettings = $DB->get_records(
                    ArchiveJob::JOB_SETTINGS_TABLE_NAME,
                    ['jobid' => $job->id],
                    '',
                    'key, value'
                );

                // Get TSP data.
                $tspdata = $DB->get_record(
                    TSPManager::TSP_TABLE_NAME,
                    ['jobid' => $job->id],
                    'timecreated, server, timestampquery, timestampreply',
                    IGNORE_MISSING
                );

                // Encode TSP data as base64 if present.
                if ($tspdata) {
                    $tspdata->timestampquery = base64_encode($tspdata->timestampquery);
                    $tspdata->timestampreply = base64_encode($tspdata->timestampreply);
                }

                // Add job data to current context.
                writer::with_context($ctx)->export_data($subctx, (object) [
                    'courseid' => $job->courseid,
                    'cmid' => $job->cmid,
                    'quizid' => $job->quizid,
                    'userid' => $job->userid,
                    'timecreated' => $job->timecreated,
                    'timemodified' => $job->timemodified,
                    'settings' => $jobsettings,
                    'tsp' => $tspdata,
                ]);

                if ($job->artifactfileid) {
                    writer::with_context($ctx)->export_file(
                        $subctx,
                        get_file_storage()->get_file_by_id($job->artifactfileid)
                    );
                }
            }

            // Process artifact files for the user in the given context.
            $attemptartifacts = $DB->get_records_sql("
                SELECT a.id, j.id AS jobid, j.courseid, j.cmid, j.quizid, j.artifactfileid, a.attemptid
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {quiz} q ON q.id = cm.instance
                    JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
                    JOIN {".ArchiveJob::ATTEMPTS_TABLE_NAME."} a ON a.jobid = j.id
                WHERE (
                    a.userid = :userid AND
                    c.id = :contextid
                )
            ", [
                'contextid' => $ctx->id,
                'userid' => $userid,
            ]);

            foreach ($attemptartifacts as $row) {
                $fm = new FileManager($row->courseid, $row->cmid, $row->quizid);
                $artifact = get_file_storage()->get_file_by_id($row->artifactfileid);
                $archive = $fm->extract_attempt_data_from_artifact($artifact, $row->jobid, $row->attemptid);

                if ($archive) {
                    writer::with_context($ctx)->export_file([$subctxbase, "Attempts"], $archive);
                }
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Job metadata.
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

        // Quiz archive file contents.
        $userlist->add_from_sql(
            'userid',
            "
            SELECT DISTINCT a.userid
            FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                JOIN {quiz} q ON q.id = cm.instance
                JOIN {".ArchiveJob::JOB_TABLE_NAME."} j ON j.quizid = q.id
                JOIN {".ArchiveJob::ATTEMPTS_TABLE_NAME."} a ON a.jobid = j.id
            WHERE cm.id = :instanceid
            ",
            [
                'instanceid'    => $context->instanceid,
                'modulename'    => 'quiz',
            ]
        );
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // We cannot simply delete data that needs to be archived for a specified amount of time.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // We cannot simply delete data that needs to be archived for a specified amount of time.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // We cannot simply delete data that needs to be archived for a specified amount of time.
    }

}
