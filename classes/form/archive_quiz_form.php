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
 * Defines the job task process form
 *
 * @package    quiz_archiver
 * @copyright  2024 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;

use quiz_archiver\ArchiveJob;
use quiz_archiver\local\util;
use quiz_archiver\Report;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


require_once($CFG->dirroot.'/lib/formslib.php'); // @codeCoverageIgnore


/**
 * Form to initiate a new quiz archive job
 */
class archive_quiz_form extends \moodleform {

    /** @var string Name of the quiz to be exported */
    protected string $quizname;
    /** @var int Number of attempts to be exported */
    protected int $numattempts;

    /**
     * Creates a new archive_quiz_form instance
     *
     * @param string $quizname Name of the quiz to be exported
     * @param int $numattempts Number of attempts to be exported
     */
    public function __construct(string $quizname, int $numattempts) {
        $this->quizname = $quizname;
        $this->numattempts = $numattempts;
        parent::__construct();
    }

    /**
     * Form definiton.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        global $CFG;

        $config = get_config('quiz_archiver');
        $mform = $this->_form;

        // Title and description.
        $mform->addElement('html', '<h1>'.get_string('create_quiz_archive', 'quiz_archiver').'</h1>');
        $mform->addElement('html', '<p>'.get_string('archive_quiz_form_desc', 'quiz_archiver').'</p>');

        // Internal information of mod_quiz.
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'mode', 'archiver');
        $mform->setType('mode', PARAM_TEXT);

        // Options.
        $mform->addElement('header', 'header_settings', get_string('settings'));

        // Options: Test.
        $mform->addElement('static', 'quiz_name', get_string('modulename', 'mod_quiz'), $this->quizname);

        // Options: Attempts.
        $mform->addElement(
            'advcheckbox',
            'export_attempts',
            get_string('attempts', 'mod_quiz'),
            get_string('export_attempts_num', 'quiz_archiver', $this->numattempts),
            ['disabled' => 'disabled'],
            ['1', '1']
        );
        $mform->addHelpButton('export_attempts', 'export_attempts', 'quiz_archiver');
        $mform->setDefault('export_attempts', true);

        foreach (Report::SECTIONS as $section) {
            $mform->addElement(
                'advcheckbox',
                'export_report_section_'.$section, '&nbsp;',
                get_string('export_report_section_'.$section, 'quiz_archiver'),
                $config->{'job_preset_export_report_section_'.$section.'_locked'} ? 'disabled' : null
            );
            $mform->addHelpButton('export_report_section_'.$section, 'export_report_section_'.$section, 'quiz_archiver');
            $mform->setDefault('export_report_section_'.$section, $config->{'job_preset_export_report_section_'.$section});

            if (!$config->{'job_preset_export_report_section_'.$section.'_locked'}) {
                foreach (REPORT::SECTION_DEPENDENCIES[$section] as $dependency) {
                    $mform->disabledIf('export_report_section_'.$section, 'export_report_section_'.$dependency, 'notchecked');
                }
            }
        }

        // Options: Backups.
        $mform->addElement(
            'advcheckbox',
            'export_quiz_backup',
            get_string('backups', 'admin'),
            get_string('export_quiz_backup', 'quiz_archiver'),
            $config->job_preset_export_quiz_backup_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_quiz_backup', 'export_quiz_backup', 'quiz_archiver');
        $mform->setDefault('export_quiz_backup', $config->job_preset_export_quiz_backup);

        $mform->addElement(
            'advcheckbox',
            'export_course_backup',
            '&nbsp;',
            get_string('export_course_backup', 'quiz_archiver'),
            $config->job_preset_export_course_backup_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_course_backup', 'export_course_backup', 'quiz_archiver');
        $mform->setDefault('export_course_backup', $config->job_preset_export_course_backup);

        // Advanced options.
        $mform->addElement('header', 'header_advanced_settings', get_string('advancedsettings'));
        $mform->setExpanded('header_advanced_settings', false);

        // Advanced options: Paper format.
        $mform->addElement(
            'select',
            'export_attempts_paper_format',
            get_string('export_attempts_paper_format', 'quiz_archiver'),
            array_combine(Report::PAPER_FORMATS, Report::PAPER_FORMATS),
            $config->job_preset_export_attempts_paper_format_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_attempts_paper_format', 'export_attempts_paper_format', 'quiz_archiver');
        $mform->setDefault('export_attempts_paper_format', $config->job_preset_export_attempts_paper_format);

        // Advanced options: Archive filename pattern.
        $mform->addElement(
            'text',
            'archive_filename_pattern',
            get_string('archive_filename_pattern', 'quiz_archiver'),
            $config->job_preset_archive_filename_pattern_locked ? 'disabled' : null
        );
        if ($CFG->branch > 402) {
            $mform->addHelpButton(
                'archive_filename_pattern',
                'archive_filename_pattern',
                'quiz_archiver',
                '',
                false,
                [
                    'variables' => array_reduce(
                        ArchiveJob::ARCHIVE_FILENAME_PATTERN_VARIABLES,
                        fn($res, $varname) => $res."<li>".
                                "<code>\${".$varname."}</code>: ".
                                get_string('archive_filename_pattern_variable_'.$varname, 'quiz_archiver').
                            "</li>",
                        ""
                    ),
                    'forbiddenchars' => implode('', ArchiveJob::FILENAME_FORBIDDEN_CHARACTERS),
                ]
            );
        } else {
            // TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
            $mform->addHelpButton('archive_filename_pattern', 'archive_filename_pattern_moodle42', 'quiz_archiver');
        }
        $mform->setType('archive_filename_pattern', PARAM_TEXT);
        $mform->setDefault('archive_filename_pattern', $config->job_preset_archive_filename_pattern);
        $mform->addRule('archive_filename_pattern', null, 'maxlength', 255, 'client');

        // Advanced options: Attempts filename pattern.
        $mform->addElement(
            'text',
            'export_attempts_filename_pattern',
            get_string('export_attempts_filename_pattern', 'quiz_archiver'),
            $config->job_preset_export_attempts_filename_pattern_locked ? 'disabled' : null
        );
        if ($CFG->branch > 402) {
            $mform->addHelpButton(
                'export_attempts_filename_pattern',
                'export_attempts_filename_pattern',
                'quiz_archiver',
                '',
                false,
                [
                    'variables' => array_reduce(
                        ArchiveJob::ATTEMPT_FILENAME_PATTERN_VARIABLES,
                        fn($res, $varname) => $res."<li>".
                                "<code>\${".$varname."}</code>: ".
                                get_string('export_attempts_filename_pattern_variable_'.$varname, 'quiz_archiver').
                            "</li>",
                        ""
                    ),
                    'forbiddenchars' => implode('', ArchiveJob::FILENAME_FORBIDDEN_CHARACTERS),
                ]
            );
        } else {
            // TODO (MDL-0): Remove after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
            $mform->addHelpButton('export_attempts_filename_pattern', 'export_attempts_filename_pattern_moodle42', 'quiz_archiver');
        }
        $mform->setType('export_attempts_filename_pattern', PARAM_TEXT);
        $mform->setDefault('export_attempts_filename_pattern', $config->job_preset_export_attempts_filename_pattern);
        $mform->addRule('export_attempts_filename_pattern', null, 'maxlength', 255, 'client');

        // Advanced options: Image optimization.
        $mform->addElement(
            'advcheckbox',
            'export_attempts_image_optimize',
            get_string('export_attempts_image_optimize', 'quiz_archiver'),
            get_string('enable'),
            $config->job_preset_export_attempts_image_optimize_locked ? 'disabled' : null,
            ['0', '1']
        );
        $mform->addHelpButton('export_attempts_image_optimize', 'export_attempts_image_optimize', 'quiz_archiver');
        $mform->setDefault('export_attempts_image_optimize', $config->job_preset_export_attempts_image_optimize);

        // Image max width/height fields.
        $mformgroup = [];
        $mformgroupfieldseperator = 'x';
        if ($config->job_preset_export_attempts_image_optimize_width_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_width_static',
                '',
                $config->job_preset_export_attempts_image_optimize_width
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_width',
                $config->job_preset_export_attempts_image_optimize_width
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_width',
                get_string('export_attempts_image_optimize_width', 'quiz_archiver'),
                ['size' => 4]
            );
            $mform->setDefault('export_attempts_image_optimize_width', $config->job_preset_export_attempts_image_optimize_width);
        }
        $mform->setType('export_attempts_image_optimize_width', PARAM_INT);

        if ($config->job_preset_export_attempts_image_optimize_height_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_height_static',
                '',
                $config->job_preset_export_attempts_image_optimize_height
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_height',
                $config->job_preset_export_attempts_image_optimize_height
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_height',
                get_string('export_attempts_image_optimize_height', 'quiz_archiver'),
                ['size' => 4]
            );
            $mform->setDefault('export_attempts_image_optimize_height', $config->job_preset_export_attempts_image_optimize_height);
            $mformgroupfieldseperator .= '&nbsp;';
        }
        $mform->setType('export_attempts_image_optimize_height', PARAM_INT);

        $mformgroup[] = $mform->createElement('static', 'export_attempts_image_optimize_px', '', 'px');

        $mform->addGroup(
            $mformgroup,
            'export_attempts_image_optimize_group',
            get_string('export_attempts_image_optimize_group', 'quiz_archiver'),
            [$mformgroupfieldseperator, ''],
            false
        );
        $mform->addHelpButton('export_attempts_image_optimize_group', 'export_attempts_image_optimize_group', 'quiz_archiver');
        $mform->hideIf('export_attempts_image_optimize_group', 'export_attempts_image_optimize', 'notchecked');

        // Image quality field.
        $mformgroup = [];
        if ($config->job_preset_export_attempts_image_optimize_quality_locked) {
            $mformgroup[] = $mform->createElement(
                'static',
                'export_attempts_image_optimize_quality_static',
                '',
                $config->job_preset_export_attempts_image_optimize_quality
            );
            $mform->addElement(
                'hidden',
                'export_attempts_image_optimize_quality',
                $config->job_preset_export_attempts_image_optimize_quality
            );
        } else {
            $mformgroup[] = $mform->createElement(
                'text',
                'export_attempts_image_optimize_quality',
                get_string('export_attempts_image_optimize_quality', 'quiz_archiver'),
                ['size' => 2]
            );
            $mform->setDefault(
                'export_attempts_image_optimize_quality',
                $config->job_preset_export_attempts_image_optimize_quality
            );
        }
        $mform->setType('export_attempts_image_optimize_quality', PARAM_INT);

        $mformgroup[] = $mform->createElement('static', 'export_attempts_image_optimize_quality_percent', '', '%');
        $mform->addGroup(
            $mformgroup,
            'export_attempts_image_optimize_quality_group',
            get_string('export_attempts_image_optimize_quality', 'quiz_archiver'),
            '',
            false
        );
        $mform->addHelpButton(
            'export_attempts_image_optimize_quality_group',
            'export_attempts_image_optimize_quality',
            'quiz_archiver'
        );
        $mform->hideIf('export_attempts_image_optimize_quality_group', 'export_attempts_image_optimize', 'notchecked');

        // Advanced options: Keep HTML files.
        $mform->addElement(
            'advcheckbox',
            'export_attempts_keep_html_files',
            get_string('export_attempts_keep_html_files', 'quiz_archiver'),
            get_string('export_attempts_keep_html_files_desc', 'quiz_archiver'),
            $config->job_preset_export_attempts_keep_html_files_locked ? 'disabled' : null
        );
        $mform->addHelpButton('export_attempts_keep_html_files', 'export_attempts_keep_html_files', 'quiz_archiver');
        $mform->setDefault('export_attempts_keep_html_files', $config->job_preset_export_attempts_keep_html_files);

        // Advanced options: Autodelete.
        $mform->addElement(
            'advcheckbox',
            'archive_autodelete',
            get_string('archive_autodelete', 'quiz_archiver'),
            get_string('enable'),
            $config->job_preset_archive_autodelete_locked ? 'disabled' : null,
            ['0', '1']
        );
        $mform->addHelpButton('archive_autodelete', 'archive_autodelete', 'quiz_archiver');
        $mform->setDefault('archive_autodelete', $config->job_preset_archive_autodelete);

        $mformgroup = [];  // This is wrapped in a form group to make hideIf() work with static elements.
        if ($config->job_preset_archive_retention_time_locked) {
            $durationwithunit = util::duration_to_unit($config->job_preset_archive_retention_time);
            $mformgroup[] = $mform->createElement(
                'static',
                'archive_retention_time_static',
                '',
                $durationwithunit[0].' '.$durationwithunit[1]
            );
            $mform->addElement('hidden', 'archive_retention_time', $config->job_preset_archive_retention_time);
        } else {
            $mformgroup[] = $mform->createElement(
                'duration',
                'archive_retention_time',
                '',
                ['optional' => false, 'defaultunit' => DAYSECS],
            );
            $mform->setDefault('archive_retention_time', $config->job_preset_archive_retention_time);
        }
        $mform->setType('archive_retention_time', PARAM_INT);

        $mform->addGroup(
            $mformgroup,
            'archive_retention_time_group',
            get_string('archive_retention_time', 'quiz_archiver'),
            '',
            false
        );
        $mform->addHelpButton('archive_retention_time_group', 'archive_retention_time', 'quiz_archiver');
        $mform->hideIf('archive_retention_time_group', 'archive_autodelete', 'notchecked');

        // Submit.
        $mform->closeHeaderBefore('submitbutton');
        $mform->addElement('submit', 'submitbutton', get_string('archive_quiz', 'quiz_archiver'));
    }

    /**
     * Server-side form data validation
     *
     * @param mixed $data Submitted form data
     * @param mixed $files Uploaed files
     * @return array Associative array with error messages for invalid fields
     * @throws \coding_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate filename pattern.
        if (!ArchiveJob::is_valid_archive_filename_pattern($data['archive_filename_pattern'])) {
            $errors['archive_filename_pattern'] = get_string('error_invalid_archive_filename_pattern', 'quiz_archiver');
        }
        if (!ArchiveJob::is_valid_attempt_filename_pattern($data['export_attempts_filename_pattern'])) {
            $errors['export_attempts_filename_pattern'] = get_string('error_invalid_attempt_filename_pattern', 'quiz_archiver');
        }

        return $errors;
    }

    /**
     * Returns the data submitted by the user but forces all locked fields to
     * their preset values
     *
     * @return \stdClass Cleared, submitted form data
     * @throws \dml_exception
     */
    public function get_data(): \stdClass {
        $data = parent::get_data();
        $config = get_config('quiz_archiver');

        // Force locked fields to their preset values.
        foreach ($config as $key => $value) {
            if (strpos($key, 'job_preset_') === 0 && strrpos($key, '_locked') === strlen($key) - 7) {
                if ($value) {
                    $data->{substr($key, 11, -7)} = $config->{substr($key, 0, -7)};
                }
            }
        }

        return $data;
    }

}
