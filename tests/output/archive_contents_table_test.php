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
 * Tests for the archive_contents_table
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\output;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the archive_contents_table
 */
final class archive_contents_table_test extends \advanced_testcase {

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
     * @covers \quiz_archiver\output\archive_contents_table
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_table_generation(): void {
        global $DB;

        // Create a mock job to render inside the table.
        $this->resetAfterTest();
        $rc = $this->getDataGenerator()->import_reference_course();
        $job = ArchiveJob::create(
            '00000000000000000000000001',
            $rc->course->id,
            $rc->cm->id,
            $rc->quiz->id,
            2,
            0,
            'wstoken',
            array_map(fn($attemptid) => (object) [
                'attemptid' => $attemptid,
                'userid' => 2,
            ], $rc->attemptids),
            [],
            ArchiveJob::STATUS_AWAITING_PROCESSING
        );
        $DB->set_field(ArchiveJob::ATTEMPTS_TABLE_NAME, 'numattachments', 1, ['attemptid' => $rc->attemptids[0]]);
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $rc->course->id,
            $rc->cm->id,
            $rc->quiz->id,
            'testartifact.tar.gz'
        );
        $job->link_artifact($artifact->get_id(), 'sha256dummy');
        $job->set_status(ArchiveJob::STATUS_FINISHED);

        // Create the table and render it.
        $table = new archive_contents_table('archive_contents', $job->get_id());
        $table->out(50, true);

        $this->assertdebuggingcalledcount(2);
        $this->expectOutputRegex('/<table.*>.*<\/table>/s');
    }

}
