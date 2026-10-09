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
 * Tests for the job actions of the quiz archiver report page
 *
 * @package   quiz_archiver
 * @copyright 2026 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use quiz_archiver\form\job_delete_form;

// @codingStandardsIgnoreLine
global $CFG;

require_once($CFG->dirroot . '/mod/quiz/report/archiver/report.php');

/**
 * Tests for the job actions of the quiz archiver report page
 */
final class report_job_actions_test extends \advanced_testcase {
    /**
     * Submits the job delete form for the given job on the report page of the given quiz
     *
     * @param \stdClass $course Course of the report page
     * @param \stdClass $quiz Quiz of the report page
     * @param string $jobid Submitted jobid
     * @return void
     */
    protected static function submit_job_delete_form(\stdClass $course, \stdClass $quiz, string $jobid): void {
        $report = new \quiz_archiver_report();
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        $props = ['course' => $course, 'cm' => $cm, 'quiz' => $quiz, 'context' => \context_module::instance($cm->id)];
        foreach ($props as $name => $value) {
            (new \ReflectionProperty(\quiz_archiver_report::class, $name))->setValue($report, $value);
        }

        job_delete_form::mock_submit(['jobid' => $jobid]);
        $_GET['action'] = 'delete_job';
        (new \ReflectionMethod(\quiz_archiver_report::class, 'handle_posted_forms'))->invoke($report);
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Tests that a job of another quiz can not be deleted via the report page of a quiz
     *
     * @covers \quiz_archiver_report::get_job_in_context
     *
     * @return void
     */
    public function test_delete_job_of_other_quiz_rejected(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $othercourse = $this->getDataGenerator()->create_course();
        $otherquiz = $this->getDataGenerator()->create_module('quiz', ['course' => $othercourse->id]);
        $otherjob = ArchiveJob::create('other-job', $othercourse->id, $otherquiz->cmid, $otherquiz->id, 2, null, 'x', [], []);

        try {
            self::submit_job_delete_form($course, $quiz, $otherjob->get_jobid());
            $this->fail('Job of another quiz was accepted');
        } catch (\moodle_exception $e) {
            $this->assertSame('job_not_found_in_context', $e->errorcode);
        }
        $this->assertTrue($DB->record_exists('quiz_archiver_jobs', ['jobid' => 'other-job']));
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Tests that a job of the current quiz can still be deleted via the report page
     *
     * @covers \quiz_archiver_report::get_job_in_context
     *
     * @return void
     */
    public function test_delete_job_of_own_quiz_allowed(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $job = ArchiveJob::create('own-job', $course->id, $quiz->cmid, $quiz->id, 2, null, 'x', [], []);

        try {
            self::submit_job_delete_form($course, $quiz, $job->get_jobid());
            $this->fail('No redirect after deletion');
        } catch (\moodle_exception $e) {
            $this->assertSame('redirecterrordetected', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('quiz_archiver_jobs', ['jobid' => 'own-job']));
    }
}
