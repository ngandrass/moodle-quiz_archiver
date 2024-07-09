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
 * Tests for the RemoteArchiveWorker class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

/**
 * Tests for the RemoteArchiveWorker class
 */
final class remotearchiveworker_test extends \advanced_testcase {

    /**
     * Test creation of request and interaction with the Moodle curl wrapper
     *
     * @covers \quiz_archiver\RemoteArchiveWorker::__construct
     * @covers \quiz_archiver\RemoteArchiveWorker::enqueue_archive_job
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_enqueue_archive_job_stub(): void {
        $worker = new RemoteArchiveWorker('http://localhost:12345', 1, 1);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/archive worker response failed/');
        $worker->enqueue_archive_job(
            'invalid-wstoken',
            -1,
            -1,
            -1,
            [],
            [],
            []
        );
    }

}
