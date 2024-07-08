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
 * Tests for the get_attempts_metadata external service
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\external;

/**
 * Tests for the get_attempts_metadata external service
 */
class get_attempts_metadata_test extends \advanced_testcase {

    /**
     * Generates a mock quiz to use in the tests
     *
     * @return \stdClass Created mock objects
     */
    protected function generate_mock_quiz(): \stdClass {
        // Create course, course module and quiz
        $this->resetAfterTest();

        // Prepare user and course
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'grade' => 100.0,
            'sumgrades' => 100,
        ]);

        return (object)[
            'user' => $user,
            'course' => $course,
            'quiz' => $quiz,
        ];
    }

    /**
     * Generates a set of valid parameters
     *
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $quizid Quiz ID
     * @return array Valid request parameters
     */
    protected function generate_valid_request(int $courseid, int $cmid, int $quizid): array {
        return [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'quizid' => $quizid,
            'attemptids' => [1, 2, 3, 4, 5],
        ];
    }

    /**
     * Test that users without the required capabilities are rejected
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_capability_requirement(): void {
        // Check that a user without the required capability is rejected
        $this->expectException(\required_capability_exception::class);
        $this->expectExceptionMessageMatches('/.*mod\/quiz_archiver:use_webservice.*/');

        $mocks = $this->generate_mock_quiz();
        $r = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        get_attempts_metadata::execute(
            $r['courseid'],
            $r['cmid'],
            $r['quizid'],
            $r['attemptids']
        );
    }

    /**
     * Verifies webservice parameter validation
     *
     * @dataProvider parameter_data_provider
     *
     * @param int $courseid Course ID
     * @param int $cmid Course module ID
     * @param int $quizid Quiz ID
     * @param array $attemptids Array of attempt IDs
     * @param bool $shouldfail Whether a failure is expected
     * @return void
     * @throws \moodle_exception
     */
    public function test_parameter_validation(
        int $courseid,
        int $cmid,
        int $quizid,
        array $attemptids,
        bool $shouldfail
    ): void {
        if ($shouldfail) {
            $this->expectException(\invalid_parameter_exception::class);
        }

        try {
            get_attempts_metadata::execute($courseid, $cmid, $quizid, $attemptids);
        // @codingStandardsIgnoreLine
        } catch (\dml_exception $e) {
            // Ignore
        }
    }

    /**
     * Data provider for test_parameter_validation
     *
     * @return array[] Test data
     */
    public function parameter_data_provider(): array {
        $mocks = $this->generate_mock_quiz();
        $base = $this->generate_valid_request($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 1);
        return [
            'Valid' => array_merge($base, ['shouldfail' => false]),
            'Invalid attemptids (simple)' => array_merge($base, ['attemptids' => ['a'], 'shouldfail' => true]),
            'Invalid attemptids (mixed)' => array_merge($base, ['attemptids' => [1, 2, 3, 4, 5, 'a'], 'shouldfail' => true]),
        ];
    }

}
