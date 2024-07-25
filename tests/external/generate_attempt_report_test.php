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
 * Tests for the generate_attempt_report external service
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

use quiz_archiver\Report;

/**
 * Tests for the generate_attempt_report external service
 */
final class generate_attempt_report_test extends \advanced_testcase {

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
     * Generates a set of valid parameters
     *
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $quizid Quiz ID
     * @param int $attemptid Attempt ID
     * @return array Valid request parameters
     */
    protected function generate_valid_request(int $courseid, int $cmid, int $quizid, int $attemptid): array {
        return [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'attemptid' => $attemptid,
            'filenamepattern' => 'test',
            'sections' => array_fill_keys(Report::SECTIONS, true),
            'attachments' => true,
        ];
    }

    /**
     * Tests that the parameter spec is specified correctly and produces no exception.
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute_parameters
     *
     * @return void
     */
    public function test_assure_execute_parameter_spec(): void {
        $this->resetAfterTest();
        $this->assertInstanceOf(
            \core_external\external_function_parameters::class,
            generate_attempt_report::execute_parameters(),
            'The execute_parameters() method should return an external_function_parameters.'
        );
    }

    /**
     * Tests that the return parameters are specified correctly and produce no exception.
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute_returns
     *
     * @return void
     */
    public function test_assure_return_parameter_spec(): void {
        $this->assertInstanceOf(
            \core_external\external_description::class,
            generate_attempt_report::execute_returns(),
            'The execute_returns() method should return an external_description.'
        );
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @covers \quiz_archiver\external\generate_attempt_report::execute
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function test_capability_requirement(): void {
        // Check that a user without the required capability is rejected.
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');

        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 1);
        generate_attempt_report::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptid'],
            $r['filenamepattern'],
            $r['sections'],
            $r['attachments']
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     * @covers \quiz_archiver\external\generate_attempt_report::execute
     * @covers \quiz_archiver\external\generate_attempt_report::validate_parameters
     *
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $quizid Quiz ID
     * @param int $attemptid Attempt ID
     * @param string $filenamepattern Filename pattern
     * @param array $sections Sections settings array
     * @param bool $attachments Whether to include attachments
     * @param bool $shouldfail Whether a failure is expected
     * @return void
     * @throws \DOMException
     * @throws \moodle_exception
     */
    public function test_parameter_validation(
        int $courseid,
        int $cmid,
        int $quizid,
        int $attemptid,
        string $filenamepattern,
        array $sections,
        bool $attachments,
        bool $shouldfail
    ): void {
        $this->resetAfterTest();

        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        try {
            generate_attempt_report::execute($courseid, $cmid, $quizid, $attemptid, $filenamepattern, $sections, $attachments);
        // @codingStandardsIgnoreLine
        } catch (\dml_missing_record_exception $e) {
            // Ignore.
        }
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public static function parameter_data_provider(): array {
        $self = new self();
        $mocks = $self->getDataGenerator()->create_mock_quiz();
        $base = $self->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 1);
        return [
            'Valid' => array_merge($base, [
                'shouldfail' => false,
            ]),
            'Invalid filenamepattern' => array_merge($base, [
                'filenamepattern' => '<a href="localhost">Foo</a>',
                'shouldfail' => true,
            ]),
            'Invalid sections' => array_merge($base, [
                'sections' => ['foo' => true],
                'shouldfail' => true,
            ]),
        ];
    }

}
