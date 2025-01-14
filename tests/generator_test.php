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
 * Tests for the quiz_archiver test data generator
 *
 * @package   quiz_archiver
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;


/**
 * Tests for the quiz_archiver_generator class
 */
final class generator_test extends \advanced_testcase {

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
     * Tests that the generator can create a mock quiz
     *
     * @covers \quiz_archiver_generator::create_mock_quiz
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_create_mock_quiz(): void {
        global $DB;

        // Generate mock quiz.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $mocks = $generator->create_mock_quiz();

        // Test generic object.
        $this->assertNotEmpty($mocks, 'The mocks were not created');

        // Check user.
        $this->assertNotEmpty($mocks->user, 'The user was not created');
        $this->assertNotEmpty($DB->get_record('user', ['id' => $mocks->user->id]), 'The user was not created correctly');

        // Check course.
        $this->assertNotEmpty($mocks->course, 'The course was not created');
        $this->assertNotEmpty($DB->get_record('course', ['id' => $mocks->course->id]), 'The course was not created correctly');

        // Check quiz.
        $this->assertNotEmpty($mocks->quiz, 'The quiz was not created');
        $this->assertNotEmpty($DB->get_record('quiz', ['id' => $mocks->quiz->id]), 'The quiz was not created correctly');

        // Check attempts and settings.
        $this->assertCount(3, $mocks->attempts, 'The mock attempts were not created correctly');
        $this->assertSame(count($mocks->attempts), $mocks->settings['num_attempts'], 'The job settings attempt count is incorrect');
        $this->assertGreaterThan(10, count($mocks->settings), 'The job settings are incomplete');
    }

    /**
     * Tests the creation of an artifact file associated with a quiz
     *
     * @covers \quiz_archiver_generator::create_artifact_file
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_create_artifact_file(): void {
        // Create mock quiz and artifact file.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $mocks = $generator->create_mock_quiz();
        $artifact = $generator->create_artifact_file($mocks->course->id, $mocks->quiz->cmid, $mocks->quiz->id, 'testfile.txt');

        // Verify artifact file.
        $this->assertNotEmpty($artifact, 'The artifact file was not created');
        $this->assertEquals('testfile.txt', $artifact->get_filename(), 'The artifact file has the wrong filename');
        $this->assertEquals(
            FileManager::COMPONENT_NAME,
            $artifact->get_component(),
            'The artifact file has the wrong component'
        );
        $this->assertEquals(
            FileManager::ARTIFACTS_FILEAREA_NAME,
            $artifact->get_filearea(),
            'The artifact file has the wrong filearea'
        );
        $this->assertEquals(
            "/{$mocks->course->id}/{$mocks->quiz->cmid}/{$mocks->quiz->id}/",
            $artifact->get_filepath(),
            'The artifact file has the wrong filepath'
        );
        $this->assertStringContainsString(
            'Lorem ipsum dolor sit amet',
            $artifact->get_content(),
            'The artifact file has the wrong content'
        );
    }

    /**
     * Tests the creation of a draft file
     *
     * @covers \quiz_archiver_generator::create_draft_file
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_create_draft_file(): void {
        // Create new draft file.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $draftfile = $generator->create_draft_file('drafttestfile.txt');

        // Verify draft file.
        $this->assertNotEmpty($draftfile, 'The draft file was not created');
        $this->assertEquals('drafttestfile.txt', $draftfile->get_filename(), 'The draft file has the wrong filename');
        $this->assertEquals('user', $draftfile->get_component(), 'The draft file has the wrong component');
        $this->assertEquals('draft', $draftfile->get_filearea(), 'The draft file has the wrong filearea');
        $this->assertStringContainsString(
            'Lorem ipsum dolor sit amet',
            $draftfile->get_content(),
            'The draft file has the wrong content'
        );
    }

    /**
     * Tests the import of the reference course
     *
     * @covers \quiz_archiver_generator::import_reference_course
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function test_import_reference_course(): void {
        // Import reference course.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $rc = $generator->import_reference_course();

        // Verify reference quiz.
        $this->assertNotEmpty($rc, 'The reference quiz was not imported');
        $this->assertNotEmpty($rc->course, 'The reference course was not imported');
        $this->assertNotEmpty($rc->cm, 'The reference course module was not imported');
        $this->assertNotEmpty($rc->quiz, 'The reference quiz was not imported');
        $this->assertCount(1, $rc->attemptids, 'The reference quiz attempts were not imported');
        $this->assertCount(1, $rc->userids, 'The reference user IDs were not imported');

        $this->assertStringContainsString('Reference Quiz', $rc->quiz->name, 'The reference quiz has the wrong name');
    }

    /**
     * Tests the import of the reference quiz artifact file into the draft
     * filearea
     *
     * @covers \quiz_archiver_generator::import_reference_quiz_artifact_as_draft
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_import_reference_quiz_artifact_as_draft(): void {
        // Import reference quiz artifact as draft.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $artifact = $generator->import_reference_quiz_artifact_as_draft();

        // Verify artifact file.
        $this->assertNotEmpty($artifact, 'The artifact file was not imported');
        $this->assertEquals(
            'reference_quiz_artifact.tar.gz',
            $artifact->get_filename(),
            'The artifact file has the wrong filename'
        );
        $this->assertEquals(
            'user',
            $artifact->get_component(),
            'The artifact file has the wrong component'
        );
        $this->assertEquals(
            'draft',
            $artifact->get_filearea(),
            'The artifact file has the wrong filearea'
        );
        $this->assertGreaterThan(16384, $artifact->get_filesize(), 'The artifact file is too small');
    }

    /**
     * Tests the creation of a temp file with an expiry date
     *
     * @covers \quiz_archiver_generator::create_temp_file
     *
     * @return void
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function test_create_temp_file(): void {
        // Create a new temp file with an expiry date.
        $generator = self::getDataGenerator();
        $this->resetAfterTest();
        $tempfile = $generator->create_temp_file('tempfile.txt', 1337);

        // Verify temp file.
        $this->assertNotEmpty($tempfile, 'The temp file was not created');
        $this->assertEquals('tempfile.txt', $tempfile->get_filename(), 'The temp file has the wrong filename');
        $this->assertEquals(FileManager::COMPONENT_NAME, $tempfile->get_component(), 'The temp file has the wrong component');
        $this->assertEquals(FileManager::TEMP_FILEAREA_NAME, $tempfile->get_filearea(), 'The temp file has the wrong filearea');
        $this->assertEquals('/1337/', $tempfile->get_filepath(), 'The temp file has the wrong filepath / expiry date');
        $this->assertStringContainsString(
            'Lorem ipsum dolor sit amet',
            $tempfile->get_content(),
            'The temp file has the wrong content'
        );
    }

}
