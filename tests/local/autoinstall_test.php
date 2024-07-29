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
 * Tests for the autoinstall class
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver\local;

/**
 * Tests for the autoinstall class
 */
final class autoinstall_test extends \advanced_testcase {

    /**
     * Tests that the autoinstall process checks user privileges
     *
     * @covers \quiz_archiver\local\autoinstall::execute
     *
     * @return void
     */
    public function test_autoinstall_requires_admin(): void {
        $this->resetAfterTest();
        list($success, $log) = autoinstall::execute('http://foo.bar:1337');
        $this->assertFalse($success, 'Autoinstall was successful without admin privileges');
        $this->assertStringContainsString('Error: You need to be a site administrator', $log, 'Error message was not displayed');
    }

    /**
     * Test one full autoinstall process
     *
     * @covers \quiz_archiver\local\autoinstall::execute
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_autoinstall(): void {
        global $DB;
        $this->resetAfterTest();

        // Gain privileges.
        $this->setAdminUser();

        // Execute autoinstall.
        $workerurl = 'http://foo.bar:1337';
        $wsname = 'test_webservice_name';
        $rolename = 'test_role_name';
        $username = 'test_user_name';

        list($success, $log) = autoinstall::execute(
            $workerurl,
            $wsname,
            $rolename,
            $username
        );

        // Check function return.
        $this->assertTrue($success, 'Autoinstall returned success=false');
        $this->assertNotEmpty($log, 'Autoinstall returned empty log');

        // Check worker URL.
        $this->assertSame($workerurl, get_config('quiz_archiver', 'worker_url'), 'Worker URL was not set correctly');

        // Check global config.
        $this->assertEquals(  // This can not be assertTrue, since Moodle stores a '1'.
            true,
            get_config('moodle', 'enablewebservices'),
            'Webservices were not globally enabled'
        );
        $this->assertStringContainsString(
            'rest',
            get_config('moodle', 'webserviceprotocols'),
            'REST protocol was not globally enabled'
        );

        // Check webservice.
        $webservice = $DB->get_record('external_services', ['name' => $wsname]);
        $this->assertNotEmpty($webservice, 'Webservice was not created');
        $this->assertSame($webservice->name, $wsname, 'Webservice name was not set correctly');
        $this->assertNotEmpty(
            $DB->get_records('external_services_functions', ['externalserviceid' => $webservice->id]),
            'Webservice functions were not assigned'
        );
        $this->assertSame(
            $webservice->id,
            get_config('quiz_archiver', 'webservice_id'),
            'Webservice ID was not set correctly'
        );

        // Check role.
        $role = $DB->get_record('role', ['shortname' => $rolename]);
        $this->assertNotEmpty($role, 'Role was not created');
        $this->assertNotEmpty(
            $DB->get_records('role_capabilities', ['roleid' => $role->id]),
            'Role capabilities were not assigned'
        );

        // Check user.
        $user = $DB->get_record('user', ['username' => $username]);
        $this->assertNotEmpty($user, 'User was not created');
        $this->assertNotEmpty(
            $DB->get_records('role_assignments', ['userid' => $user->id, 'roleid' => $role->id]),
            'User role was not assigned'
        );
        $this->assertSame($user->id, get_config('quiz_archiver', 'webservice_userid'), 'User ID was not set correctly');
    }

    /**
     * Tests if autoinstalls are properly detected and repeated autoinstalls
     * are prevented.
     *
     * @covers \quiz_archiver\local\autoinstall::plugin_is_unconfigured
     * @covers \quiz_archiver\local\autoinstall::execute
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_autoinstall_detection(): void {
        $this->resetAfterTest();

        // Gain privileges.
        $this->setAdminUser();

        // Plugin should be unconfigured.
        $this->assertTrue(autoinstall::plugin_is_unconfigured(), 'Plugin was not unconfigured');

        // Perform autoinstall.
        list($success, $log) = autoinstall::execute('http://foo.bar:1337');
        $this->assertTrue($success, 'First autoinstall failed');

        // Try to detect autoinstall.
        $this->assertFalse(autoinstall::plugin_is_unconfigured(), 'Successful autoinstall was not detected');

        // Try to autoinstall a second time.
        list($success, $log) = autoinstall::execute('http://foo.bar:1337');
        $this->assertFalse($success, 'Second autoinstall was successful');
        $this->assertNotEmpty($log, 'Second autoinstall returned empty log');

        // Try with force.
        list($success, $log) = autoinstall::execute(
            'http://foo.bar:1337',
            'anotherwsname',
            'anotherroleshortname',
            'anotherusername',
            true
        );
        $this->assertTrue($success, 'Forced autoinstall failed');
        $this->assertNotEmpty($log, 'Forced autoinstall returned empty log');
    }

}
