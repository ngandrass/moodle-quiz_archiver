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
 * Tests for the job_overview_table
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\output;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the job_overview_table
 */
final class job_overview_table_test extends \advanced_testcase {

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
     * Basic coverage test for table generation logic
     *
     * @covers \quiz_archiver\output\job_overview_table
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_table_generation(): void {
        // Create a mock job to render inside the table.
        $this->resetAfterTest();
        $mocks = $this->getDataGenerator()->create_mock_quiz();
        $job = ArchiveJob::create(
            '00000000000000000000000001',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
            ArchiveJob::STATUS_AWAITING_PROCESSING
        );
        $job->set_status(ArchiveJob::STATUS_RUNNING, ['progress' => 42]);

        // Create a second job that is finished and has an artifact.
        $job2 = ArchiveJob::create(
            '00000000000000000000000002',
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            2,
            0,
            'wstoken',
            [],
            [],
        );
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'testartifact.tar.gz'
        );
        $job2->link_artifact($artifact->get_id(), 'sha256dummy');
        $job2->set_status(ArchiveJob::STATUS_FINISHED);

        // Create the table and render it.
        $table = new job_overview_table(100000, $mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id);
        $table->out(50, true);

        $this->assertdebuggingcalledcount(2);
        $this->expectOutputRegex('/<table.*>.*<\/table>/s');
    }

}
