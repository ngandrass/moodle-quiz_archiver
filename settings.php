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
 * Plugin administration pages are defined here.
 *
 * @package     quiz_archiver
 * @category    admin
 * @copyright   2023 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

if ($hassiteconfig) {
    $settings = new admin_settingpage('quiz_archiver_settings', new lang_string('pluginname', 'quiz_archiver'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext('quiz_archiver/worker_url',
            get_string('setting_worker_url', 'quiz_archiver'),
            get_string('setting_worker_url_desc', 'quiz_archiver'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configselect('quiz_archiver/webservice_id',
            get_string('webservice', 'webservice'),
            get_string('setting_webservice_desc', 'quiz_archiver'),
            null,
            [-1 => ''] + $DB->get_records_menu('external_services', null, 'name ASC', 'id, name')
        ));

        $settings->add(new admin_setting_configtext('quiz_archiver/webservice_userid',
            get_string('setting_webservice_userid', 'quiz_archiver'),
            get_string('setting_webservice_userid_desc', 'quiz_archiver'),
            '',
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext('quiz_archiver/internal_wwwroot',
            get_string('setting_internal_wwwroot', 'quiz_archiver'),
            get_string('setting_internal_wwwroot_desc', 'quiz_archiver'),
            '',
            PARAM_TEXT
        ));

    }
}
