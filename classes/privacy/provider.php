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
        // TODO: Implement get_contexts_for_userid() method.
        $contextlist = new contextlist();
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        // TODO: Implement export_user_data() method.
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        // TODO: Implement delete_data_for_all_users_in_context() method.
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // TODO: Implement delete_data_for_user() method.
    }

    public static function get_users_in_context(userlist $userlist) {
        // TODO: Implement get_users_in_context() method.
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        // TODO: Implement delete_data_for_users() method.
    }

}