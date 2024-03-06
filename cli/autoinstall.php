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
 * quiz_archiver - automatic install script
 *
 * @package    quiz_archiver
 * @copyright  2024 Niels Gandraß <niels@gandrass.de>
 *             2024 Melanie Treitinger, Ruhr-Universität Bochum <melanie.treitinger@ruhr-uni-bochum.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const CLI_SCRIPT = 1;

require_once(__DIR__ . '/config.php');
require_once($CFG->libdir  . '/clilib.php');
require_once($CFG->dirroot . '/lib/testing/generator/data_generator.php');
require_once($CFG->dirroot . '/webservice/lib.php');

// Set the variables for the new webservice.
$wsname = 'quiz_archiver_webservice';
$additionalcapabilities = [
        'mod/quiz:reviewmyattempts',
        'mod/quiz:view',
        'mod/quiz:viewreports',
        'mod/quiz_archiver:use_webservice',
        'moodle/backup:anonymise',
        'moodle/backup:backupactivity',
        'moodle/backup:backupcourse',
        'moodle/backup:backupsection',
        'moodle/backup:backuptargetimport',
        'moodle/backup:configure',
        'moodle/backup:downloadfile',
        'moodle/backup:userinfo',
        'moodle/course:ignoreavailabilityrestrictions',
        'moodle/course:view',
        'moodle/course:viewhiddenactivities',
        'moodle/course:viewhiddencourses',
        'moodle/course:viewhiddensections',
        'moodle/user:ignoreuserquota',
        'webservice/rest:use',
];
$wsfunctions = [
        'quiz_archiver_generate_attempt_report',
        'quiz_archiver_get_attempts_metadata',
        'quiz_archiver_update_job_status',
        'quiz_archiver_process_uploaded_artifact',
        'quiz_archiver_get_backup_status',
];

// Set system context.
try {
    $systemcontext = context_system::instance();
} catch (dml_exception $e) {
    cli_error("Error: Cannot get system context: ".$e->getMessage());
    exit(1);
}

// Set admin user.
$USER = get_admin();

// Enable web services and REST protocol.
set_config('enablewebservices', true);
try {
    $enabledprotocols = get_config('core', 'webserviceprotocols');
} catch (dml_exception $e) {
    cli_error("Error: Cannot get config setting webserviceprotocols: ".$e->getMessage());
    exit(1);
}
if (stripos($enabledprotocols, 'rest') === false) {
    set_config('webserviceprotocols', $enabledprotocols . ',rest');
}
// Create a web service user.
$datagenerator = new testing_data_generator();
$webserviceuser = $datagenerator->create_user([
        'username' => 'ws-'.$wsname.'-user', 'firstname' => 'Webservice', 'lastname' => 'User ('.$wsname.')', 'policyagreed' => 1]);

// Create a web service role.
try {
    $rolename = 'WS Role for ' . $wsname;
    $roleshort = 'ws-' . $wsname . '-role';
    $wsroleid = create_role($rolename, $roleshort, '');
} catch (coding_exception $e) {
    cli_error("Error: Cannot create role $rolename: ".$e->getMessage());
    exit(1);
}
set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM]);

foreach ($additionalcapabilities as $cap){
    try {
        assign_capability($cap, CAP_ALLOW, $wsroleid, $systemcontext->id, true);
    } catch (coding_exception $e) {
        cli_error("Error: Cannot assign capability $cap: ".$e->getMessage());
        exit(1);
    }
}

// Give the user the role.
try {
    role_assign($wsroleid, $webserviceuser->id, $systemcontext->id);
} catch (coding_exception $e) {
    cli_error("Error: Cannot assign role to webserviceuser: ".$e->getMessage());
    exit(1);
}

// Enable the webservice.
$webservicemanager = new webservice();
$serviceid = $webservicemanager->add_external_service((object)[
        'name' => $wsname,
        'shortname' => $wsname,
        'enabled' => 1,
        'requiredcapability' => '',
        'restrictedusers' => true,
        'downloadfiles' => true,
        'uploadfiles' => true,
]);

if(!$serviceid){
    cli_error("ERROR: Service $wsname was not created.");
    exit(1);
}

// Add functions to the service
foreach ($wsfunctions as $f) {
    $webservicemanager->add_external_function_to_service($f, $serviceid);
}

// Authorise the user to use the service.
$webservicemanager->add_ws_authorised_user((object) ['externalserviceid' => $serviceid, 'userid' => $webserviceuser->id]);

$service = $webservicemanager->get_external_service_by_id($serviceid);
$webservicemanager->update_external_service($service);

cli_writeln("Service $wsname was created successfully with for user with id $webserviceuser->id.");
exit(0);
