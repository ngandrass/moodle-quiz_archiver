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
 * Tests for the archive_quiz_form
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the archive_quiz_form
 */
final class archive_quiz_form_test extends \advanced_testcase {

    /**
     * Returns the data generator for the quiz_archiver plugin
     *
     * @return \quiz_archiver_generator The data generator for the quiz_archiver plugin
     */
    // @codingStandardsIgnoreLine
    public static function getDataGenerator(): \quiz_archiver_generator {
        return parent::getDataGenerator()->get_plugin_generator('quiz_archiver');
    }

    /**
     * Basic code coverage to verify validity of form definition and detect
     * possible errors during form element definition.
     *
     * @covers \quiz_archiver\form\archive_quiz_form::__construct
     * @covers \quiz_archiver\form\archive_quiz_form::definition
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_form_definition(): void {
        // Create a mock archive job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '10000000-0000-0000-0000-0123456789ab';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Create the form and define it.
        $form = new archive_quiz_form($mocks->quiz->name, count($mocks->attempts));
        $this->assertInstanceOf(\moodleform::class, $form);
    }

    /**
     * Basic code coverage to verify validity of form definition and detect
     * possible errors during form element definition with locked job presets.
     *
     * @covers \quiz_archiver\form\archive_quiz_form::__construct
     * @covers \quiz_archiver\form\archive_quiz_form::definition
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_form_definition_all_locked(): void {
        // Create a mock archive job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '10000001-0000-0000-0000-0123456789ab';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            3600,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Lock all lockable settings.
        foreach (get_config('quiz_archiver') as $key => $value) {
            if (strpos($key, '_locked') !== false) {
                set_config($key, 1, 'quiz_archiver');
            }
        }

        // Create the form and define it.
        $form = new archive_quiz_form($mocks->quiz->name, count($mocks->attempts));
        $this->assertInstanceOf(\moodleform::class, $form);
    }

    /**
     * Test the custom form validation
     *
     * @dataProvider form_validation_data_provider
     * @covers       \quiz_archiver\form\archive_quiz_form::validation
     *
     * @param array $formdata
     * @param bool $isvalid
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_form_validation(array $formdata, bool $isvalid): void {
        // Create a mock archive job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '20000000-0000-0000-0000-0123456789ab';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Test that invalid filename patterns are filtered out.
        $form = new archive_quiz_form($mocks->quiz->name, count($mocks->attempts));
        $errors = $form->validation($formdata, []);
        if ($isvalid) {
            $this->assertEmpty($errors, 'Form validation failed for valid input data.');
        } else {
            $this->assertNotEmpty($errors, 'Form validation succeeded for invalid input data.');
        }
    }

    /**
     * Data provider for test_form_validation
     *
     * @return array[] Test data
     */
    public static function form_validation_data_provider(): array {
        return [
            'Valid data' => [
                [
                    'archive_filename_pattern' => 'archive-${courseshortname}',
                    'export_attempts_filename_pattern' => 'attempt-${attemptid}',
                ],
                true,
            ],
            'Invalid archive filename pattern' => [
                [
                    'archive_filename_pattern' => 'archive-${courseshortname',
                    'export_attempts_filename_pattern' => 'attempt-${attemptid}',
                ],
                false,
            ],
            'Invalid attempt filename pattern' => [
                [
                    'archive_filename_pattern' => 'archive-${courseshortname}',
                    'export_attempts_filename_pattern' => 'attempt-${attemptid',
                ],
                false,
            ],
        ];
    }

    /**
     * Test custom form data overrides. Tests that locked settings can not be
     * overridden by spoofed POST data.
     *
     * @dataProvider get_data_data_provider
     * @covers       \quiz_archiver\form\archive_quiz_form::get_data
     *
     * @param string $optionkey Job option key to test
     * @param mixed $optionpresetvalue Preset value for the job option
     * @param mixed $postvalue Value to be provided via POST
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_data(string $optionkey, $optionpresetvalue, $postvalue): void {
        global $USER;

        // Create a mock archive job.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $jobid = '30000000-0000-0000-0000-0123456789ab';
        ArchiveJob::create(
            $jobid,
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            $mocks->user->id,
            null,
            'TEST-WS-TOKEN-1',
            $mocks->attempts,
            $mocks->settings
        );

        // Prepare locked preset value.
        set_config("job_preset_{$optionkey}", $optionpresetvalue, 'quiz_archiver');
        set_config("job_preset_{$optionkey}_locked", 1, 'quiz_archiver');

        // Load valid form POST data and create form.
        $USER->ignoresesskey = true;
        $validpostdata = json_decode(
            file_get_contents(__DIR__.'/../fixtures/archive_quiz_form_request_valid.json'),
            true
        );
        foreach ($validpostdata as $key => $value) {
            $_POST[$key] = $value;
        }
        $_POST[$optionkey] = $postvalue;
        $form = new archive_quiz_form($mocks->quiz->name, count($mocks->attempts));

        // Verify that the preset value is locked and cannot be overridden, even if different data is provided via POST.
        $this->assertEquals(
            $optionpresetvalue,
            $form->get_data()->{$optionkey},
            "Preset value for {$optionkey} was overridden even though is is locked."
        );
    }

    /**
     * Data provider for test_get_data
     *
     * @return array[] Test data
     */
    public static function get_data_data_provider(): array {
        return [
            'Job preset locked: Export quiz attempts' => ['export_attempts', '1', '0'],
            'Job preset locked: Include correct answers' => ['export_report_section_rightanswer', '0', '1'],
            'Job preset locked: Include answer history' => ['export_report_section_history', '0', '1'],
            'Job preset locked: Include file attachments' => ['export_report_section_attachments', '1', '0'],
            'Job preset locked: Export quiz backup' => ['export_quiz_backup', '1', '0'],
            'Job preset locked: Export course backup' => ['export_course_backup', '0', '1'],
            'Job preset locked: Optimize images' => ['export_attempts_image_optimize', '1', '0'],
            'Job preset locked: Automatic deletion' => ['archive_autodelete', '0', '1'],
            'Job preset locked: Retention time' => ['archive_retention_time', '315360000', '60'],
        ];
    }

}
