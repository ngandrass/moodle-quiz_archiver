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

use quiz_archiver\ArchiveJob;
use quiz_archiver\Report;

defined('MOODLE_INTERNAL') || die();

global $DB;

if ($hassiteconfig) {
    $settings = new admin_settingpage('quiz_archiver_settings', new lang_string('pluginname', 'quiz_archiver'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Descriptive text
        $settings->add(new admin_setting_heading('quiz_archiver/header_docs',
            null,
            get_string('setting_header_docs_desc', 'quiz_archiver')
        ));

        // Generic settings
        $settings->add(new admin_setting_heading('quiz_archiver/header_archive_worker',
            get_string('setting_header_archive_worker', 'quiz_archiver'),
            get_string('setting_header_archive_worker_desc', 'quiz_archiver')
        ));

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

        $settings->add(new admin_setting_configtext('quiz_archiver/job_timeout_min',
            get_string('setting_job_timeout_min', 'quiz_archiver'),
            get_string('setting_job_timeout_min_desc', 'quiz_archiver'),
            '120',
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext('quiz_archiver/internal_wwwroot',
            get_string('setting_internal_wwwroot', 'quiz_archiver'),
            get_string('setting_internal_wwwroot_desc', 'quiz_archiver'),
            '',
            PARAM_TEXT
        ));

        // Job Presets
        $settings->add(new admin_setting_heading('quiz_archiver/header_job_presets',
            get_string('setting_header_job_presets', 'quiz_archiver'),
            get_string('setting_header_job_presets_desc', 'quiz_archiver'),
        ));

        $settings->add(new class('quiz_archiver/job_preset_export_attempts',
            get_string('export_attempts', 'quiz_archiver'),
            get_string('export_attempts_help', 'quiz_archiver'),
            '1',
        ) extends admin_setting_configcheckbox {
            public function get_setting() {
                return 1;
            }
            public function is_readonly(): bool {
                return true;
            }
        });

        foreach (Report::SECTIONS as $section) {
            $set = new admin_setting_configcheckbox('quiz_archiver/job_preset_export_report_section_'.$section,
                get_string('export_report_section_'.$section, 'quiz_archiver'),
                get_string('export_report_section_'.$section.'_help', 'quiz_archiver'),
                '1',
            );
            $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);

            foreach (Report::SECTION_DEPENDENCIES[$section] as $dependency) {
                $set->add_dependent_on('quiz_archiver/job_preset_export_report_section_'.$dependency);
            }

            $settings->add($set);
        }

        $set = new admin_setting_configcheckbox('quiz_archiver/job_preset_export_quiz_backup',
            get_string('export_quiz_backup', 'quiz_archiver'),
            get_string('export_quiz_backup_help', 'quiz_archiver'),
            '1',
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        $set = new admin_setting_configcheckbox('quiz_archiver/job_preset_export_course_backup',
            get_string('export_course_backup', 'quiz_archiver'),
            get_string('export_course_backup_help', 'quiz_archiver'),
            '0',
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        $set = new admin_setting_configselect('quiz_archiver/job_preset_export_attempts_paper_format',
            get_string('export_attempts_paper_format', 'quiz_archiver'),
            get_string('export_attempts_paper_format_help', 'quiz_archiver'),
            'A4',
            array_combine(Report::PAPER_FORMATS, Report::PAPER_FORMATS),
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        $set = new admin_setting_configcheckbox('quiz_archiver/job_preset_export_attempts_keep_html_files',
            get_string('export_attempts_keep_html_files', 'quiz_archiver'),
            get_string('export_attempts_keep_html_files_help', 'quiz_archiver'),
            '1',
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        // TODO: Implement validation
        $settings->add($set);

        $set = new admin_setting_configtext('quiz_archiver/job_preset_archive_filename_pattern',
            get_string('archive_filename_pattern', 'quiz_archiver'),
            get_string('archive_filename_pattern_help', 'quiz_archiver', [
                'variables' => array_reduce(
                    ArchiveJob::ARCHIVE_FILENAME_PATTERN_VARIABLES,
                    fn ($res, $varname) => $res . "<li><code>\${".$varname."}</code>: ".get_string('export_attempts_filename_pattern_variable_'.$varname, 'quiz_archiver')."</li>"
                    , ""
                ),
                'forbiddenchars' => implode('', ArchiveJob::FILENAME_FORBIDDEN_CHARACTERS),
            ]),
            'quiz_archive_${courseshortname}(${courseid})_${quizname}(${quizid})_${date}_${time}',
            PARAM_TEXT,
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        // TODO: Implement validation
        $settings->add($set);

        $set = new admin_setting_configtext('quiz_archiver/job_preset_export_attempts_filename_pattern',
            get_string('export_attempts_filename_pattern', 'quiz_archiver'),
            get_string('export_attempts_filename_pattern_help', 'quiz_archiver', [
                'variables' => array_reduce(
                    ArchiveJob::ATTEMPT_FILENAME_PATTERN_VARIABLES,
                    fn ($res, $varname) => $res . "<li><code>\${".$varname."}</code>: ".get_string('export_attempts_filename_pattern_variable_'.$varname, 'quiz_archiver')."</li>"
                    , ""
                ),
                'forbiddenchars' => implode('', ArchiveJob::FILENAME_FORBIDDEN_CHARACTERS),
            ]),
            'attempt_${attemptid}_${username}_${date}_${time}',
            PARAM_TEXT,
        );
        $set->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        $settings->add($set);

        // Time-Stamp Protocol settings
        $settings->add(new admin_setting_heading('quit_archiver/header_tsp',
            get_string('setting_header_tsp', 'quiz_archiver'),
            get_string('setting_header_tsp_desc', 'quiz_archiver')
        ));

        $settings->add(new admin_setting_configcheckbox('quiz_archiver/tsp_enable',
            get_string('setting_tsp_enable', 'quiz_archiver'),
            get_string('setting_tsp_enable_desc', 'quiz_archiver'),
            '0'
        ));

        $settings->add(new admin_setting_configcheckbox('quiz_archiver/tsp_automatic_signing',
            get_string('setting_tsp_automatic_signing', 'quiz_archiver'),
            get_string('setting_tsp_automatic_signing_desc', 'quiz_archiver'),
            '1'
        ));

        $settings->add(new admin_setting_configtext('quiz_archiver/tsp_server_url',
            get_string('setting_tsp_server_url', 'quiz_archiver'),
            get_string('setting_tsp_server_url_desc', 'quiz_archiver'),
            'https://freetsa.org/tsr',
            PARAM_URL
        ));

    }
}
