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

/** @var bool Identifies this script as a CLI script */
const CLI_SCRIPT = true;

require_once(__DIR__ . '/../../../../../config.php');
require_once("{$CFG->libdir}/clilib.php");
require_once("{$CFG->dirroot}/lib/testing/generator/data_generator.php");
require_once("{$CFG->dirroot}/webservice/lib.php");

###################################
# Defaults and base configuration #
###################################

/** @var string Default name for the webservice to create */
const DEFAULT_WSNAME = 'quiz_archiver_webservice';

/** @var string Default username for the service account to create */
const DEFAULT_USERNAME = 'quiz_archiver_serviceacount';

/** @var string Default shortname for the role to create */
const DEFAULT_ROLESHORTNAME = 'quiz_archiver';

/** @var string[] List of capabilities to assign to the created role */
const WS_ROLECAPS = [
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

/** @var string[] List of functions to add to the created webservice */
const WS_FUNCTIONS = [
        'quiz_archiver_generate_attempt_report',
        'quiz_archiver_get_attempts_metadata',
        'quiz_archiver_update_job_status',
        'quiz_archiver_process_uploaded_artifact',
        'quiz_archiver_get_backup_status',
];

#######################
# CLI options parsing #
#######################

list($options, $unrecognised) = cli_get_params(
    [
        'help' => false,
        'wsname' => DEFAULT_WSNAME,
        'rolename' => DEFAULT_ROLESHORTNAME,
        'username' => DEFAULT_USERNAME,
    ],
    [
        'h' => 'help',
    ]
);

$usage = <<<EOT
Automatically configures Moodle for use with the quiz archiver plugin.

ATTENTION: This CLI script ...
- Enables web services and REST protocol
- Creates a quiz archiver service role and a corresponding user
- Creates a new web service with all required webservice functions
- Authorises the user to use the webservice.

Usage:
    $ php autoinstall.php
    $ php autoinstall.php --username="my-custom-archive-user"
    $ php autoinstall.php [--help|-h]

Options:
    --help, -h          Show this help message
    --wsname=<value>    Sets a custom name for the web service (default: quiz_archiver_webservice)
    --rolename=<value>  Sets a custom name for the web service role (default: quiz_archiver)
    --username=<value>  Sets a custom username for the web service user (default: quiz_archiver_serviceacount)
EOT;

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

################################
# Begin of autoinstall routine #
################################

// Set system context.
try {
    $systemcontext = context_system::instance();
} catch (dml_exception $e) {
    cli_error("Error: Cannot get system context: ".$e->getMessage());
    exit(1);
}

cli_writeln("Starting automatic installation of quiz archiver plugin...");

// Set admin user.
$USER = get_admin();

// Create a web service user.
try {
    $datagenerator = new testing_data_generator();
    $webserviceuser = $datagenerator->create_user([
        'username' => $options['username'],
        'password' => bin2hex(random_bytes(32)),
        'firstname' => 'Quiz Archiver',
        'firstnamephonetic' => '',
        'middlename' => '',
        'lastname' => 'Service Account',
        'lastnamephonetic' => '',
        'alternatename' => '',
        'email' => 'noreply@localhost',
        'policyagreed' => 1
    ]);
    cli_writeln("  -> Web service user '{$webserviceuser->username}' with ID {$webserviceuser->id} created.");
} catch (dml_exception $e) {
    cli_error("Error: Cloud not create webservice user: ".$e->getMessage());
    exit(1);
}

// Create a web service role.
try {
    $wsroleid = create_role(
        'Quiz Archiver Service Account',
        $options['rolename'],
        'A role that bundles all access rights required for the quiz archiver plugin to work.'
    );
    set_role_contextlevels($wsroleid, [CONTEXT_SYSTEM]);

    cli_writeln("  -> Role '{$options['rolename']}' created.");
} catch (coding_exception $e) {
    cli_error("Error: Cannot create role {$options['rolename']}: {$e->getMessage()}");
    exit(1);
}

foreach (WS_ROLECAPS as $cap){
    try {
        assign_capability($cap, CAP_ALLOW, $wsroleid, $systemcontext->id, true);
        cli_writeln("    -> Capability {$cap} assigned to role '{$options['rolename']}'.");
    } catch (coding_exception $e) {
        cli_error("Error: Cannot assign capability {$cap}: {$e->getMessage()}");
        exit(1);
    }
}

// Give the user the role.
try {
    role_assign($wsroleid, $webserviceuser->id, $systemcontext->id);
    cli_writeln("  -> Role '{$options['rolename']}' assigned to user '{$webserviceuser->username}'.");
} catch (coding_exception $e) {
    cli_error("Error: Cannot assign role to webservice user: ".$e->getMessage());
    exit(1);
}

// Enable web services and REST protocol.
try {
    set_config('enablewebservices', true);
    cli_writeln('  -> Web services enabled.');

    $enabledprotocols = get_config('core', 'webserviceprotocols');
    if (stripos($enabledprotocols, 'rest') === false) {
        set_config('webserviceprotocols', $enabledprotocols . ',rest');
    }
    cli_writeln('  -> REST webservice protocol enabled.');
} catch (dml_exception $e) {
    cli_error("Error: Cannot get config setting webserviceprotocols: ".$e->getMessage());
    exit(1);
}

// Enable the webservice.
$webservicemanager = new webservice();
$serviceid = $webservicemanager->add_external_service((object)[
        'name' => $options['wsname'],
        'shortname' => $options['wsname'],
        'enabled' => 1,
        'requiredcapability' => '',
        'restrictedusers' => true,
        'downloadfiles' => true,
        'uploadfiles' => true,
]);

if(!$serviceid){
    cli_error("ERROR: Service {$options['wsname']} could not be created.");
    exit(1);
} else {
    cli_writeln("  -> Web service '{$options['wsname']}' created with ID {$serviceid}.");
}

// Add functions to the service
foreach (WS_FUNCTIONS as $f) {
    $webservicemanager->add_external_function_to_service($f, $serviceid);
    cli_writeln("    -> Function {$f} added to service '{$options['wsname']}'.");
}

// Authorise the user to use the service.
$webservicemanager->add_ws_authorised_user((object) [
    'externalserviceid' => $serviceid,
    'userid' => $webserviceuser->id
]);

$service = $webservicemanager->get_external_service_by_id($serviceid);
$webservicemanager->update_external_service($service);
cli_writeln("  -> User '{$webserviceuser->username}' authorised to use service '{$options['wsname']}'.");

// Configure quiz_archiver plugin settings
try {
    cli_writeln("  -> Configuring the quiz archiver plugin...");

    set_config('webservice_id', $serviceid, 'quiz_archiver');
    cli_writeln("    -> Web service set to '{$options['wsname']}'.");

    set_config('webservice_userid', $webserviceuser->id, 'quiz_archiver');
    cli_writeln("    -> Web service user set to '{$webserviceuser->username}'.");
} catch (dml_exception $e) {
    cli_error("Error: Failed to set config settings for quiz_archiver plugin: ".$e->getMessage());
    exit(1);
}

cli_writeln('');
cli_writeln("Automatic installation of quiz archiver plugin finished successfully.");
exit(0);
