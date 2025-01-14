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
 * Tests for the job_delete_form
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;


use quiz_archiver\ArchiveJob;

/**
 * Tests for the job_delete_form
 */
final class job_delete_form_test extends \advanced_testcase {

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
     * @covers \quiz_archiver\form\job_delete_form::__construct
     * @covers \quiz_archiver\form\job_delete_form::definition
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
        $job = ArchiveJob::create(
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

        // Create a mock artifact file.
        $artifact = $this->getDataGenerator()->create_artifact_file(
            $mocks->course->id,
            $mocks->quiz->cmid,
            $mocks->quiz->id,
            'testartifact.tar.gz'
        );
        $sha256dummy = hash('sha256', 'foo bar baz');
        $job->link_artifact($artifact->get_id(), $sha256dummy);
        $job->set_status(ArchiveJob::STATUS_FINISHED);

        // Create and define the form.
        $_POST['jobid'] = $jobid;
        $form = new job_delete_form();
        $this->assertInstanceOf(\moodleform::class, $form);
    }

}
