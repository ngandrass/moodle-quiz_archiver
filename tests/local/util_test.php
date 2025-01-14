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
 * Tests for the util class
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\local;

/**
 * Tests for the autoinstall class
 */
final class util_test extends \advanced_testcase {

    /**
     * Tests the duration_to_human_readable util function
     *
     * @dataProvider duration_to_human_readable_data_provider
     * @covers       \quiz_archiver\local\util::duration_to_human_readable
     *
     * @param int $duration
     * @param string $expected
     * @return void
     */
    public function test_duration_to_human_readable(int $duration, string $expected): void {
        $this->assertEquals($expected, util::duration_to_human_readable($duration));
    }

    /**
     * Data provider for test_duration_to_human_readable
     *
     * @return array[] Test data
     */
    public static function duration_to_human_readable_data_provider(): array {
        return [
            '0 seconds' => [0, '0s'],
            '1 second' => [1, '1s'],
            '59 seconds' => [59, '59s'],
            '1 minute' => [MINSECS, '1m'],
            '1 minute 1 second' => [MINSECS + 1, '1m 1s'],
            '1 minute 59 seconds' => [MINSECS + 59, '1m 59s'],
            '2 minutes' => [2 * MINSECS, '2m'],
            '59 minutes 59 seconds' => [HOURSECS - 1, '59m 59s'],
            '1 hour' => [HOURSECS, '1h'],
            '1 hour 1 second' => [HOURSECS + 1, '1h 1s'],
            '1 hour 1 minute' => [HOURSECS + MINSECS, '1h 1m'],
            '1 hour 1 minute 1 second' => [HOURSECS + MINSECS + 1, '1h 1m 1s'],
            '23 hours' => [23 * HOURSECS, '23h'],
            '23 hours 59 minutes 59 seconds' => [DAYSECS - 1, '23h 59m 59s'],
            '1 day' => [DAYSECS, '1d'],
            '10 days' => [10 * DAYSECS, '10d'],
            '1 month' => [YEARSECS / 12, '1m'],
            '1 year' => [YEARSECS, '1y'],
            '1 year 4 months 2 days 13 hours 37 minutes' => [
                YEARSECS + 4 * (YEARSECS / 12) + 2 * DAYSECS + 13 * HOURSECS + 37 * MINSECS,
                '1y 4m 2d 13h 37m',
            ],
        ];
    }

    /**
     * Tests the duration_to_unit util function
     *
     * @dataProvider duration_to_unit_data_provider
     * @covers       \quiz_archiver\local\util::duration_to_unit
     *
     * @param int $duration
     * @param int $expectedvalue
     * @param string $expectedunit
     * @return void
     * @throws \coding_exception
     */
    public function test_duration_to_unit(int $duration, int $expectedvalue, string $expectedunit): void {
        $this->assertEquals(
            [$expectedvalue, get_string($expectedunit)],
            util::duration_to_unit($duration)
        );
    }

    /**
     * Data provider for test_duration_to_unit
     *
     * @return array[] Test data
     */
    public static function duration_to_unit_data_provider(): array {
        return [
            '1 week' => [WEEKSECS, 1, 'weeks'],
            '1 day' => [DAYSECS, 1, 'days'],
            '1 hour' => [HOURSECS, 1, 'hours'],
            '1 minute' => [MINSECS, 1, 'minutes'],
            '1 second' => [1, 1, 'seconds'],
            '61 seconds' => [61, 61, 'seconds'],
            '42 hours' => [42 * HOURSECS, 42, 'hours'],
        ];
    }

}
