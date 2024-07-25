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
 * Tests for the autoinstall_form
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\form;


/**
 * Tests for the autoinstall_form
 */
final class autoinstall_form_test extends \advanced_testcase {

    /**
     * Basic code coverage to verify validity of form definition and detect
     * possible errors during form element definition.
     *
     * @covers \quiz_archiver\form\autoinstall_form::__construct
     * @covers \quiz_archiver\form\autoinstall_form::definition
     *
     * @return void
     */
    public function test_form_definition(): void {
        $form = new autoinstall_form();
        $this->assertInstanceOf(\moodleform::class, $form);
    }

}
