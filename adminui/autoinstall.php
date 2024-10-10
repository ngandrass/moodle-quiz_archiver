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
 * Handler for autoinstall feature from the admin UI of the quiz archiver plugin.
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../../config.php');
require_once("{$CFG->libdir}/moodlelib.php");
require_once("{$CFG->dirroot}/mod/quiz/report/archiver/classes/form/autoinstall_form.php");

use quiz_archiver\form\autoinstall_form;
use quiz_archiver\local\autoinstall;

// Disable error reporting to prevent warning of potential redefinition of constants.
$olderrorreporting = error_reporting();
error_reporting(0);

/** @var bool Disables output buffering */
const NO_OUTPUT_BUFFERING = true;

error_reporting($olderrorreporting);

// Ensure user has permissions.
require_login();
$ctx = context_system::instance();
require_capability('moodle/site:config', $ctx);

// Setup page.
$PAGE->set_context($ctx);
$PAGE->set_url('/mod/quiz/report/archiver/adminui/autoinstall.php');
$title = get_string('autoinstall_plugin', 'quiz_archiver');
$PAGE->set_title($title);

$returnlink = html_writer::link(
    new moodle_url('/admin/settings.php', ['section' => 'quiz_archiver_settings']),
    get_string('back')
);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// Content.
if (autoinstall::plugin_is_unconfigured()) {
    $form = new autoinstall_form();

    if ($form->is_cancelled()) {
        // Cancelled.
        echo '<p>'.get_string('autoinstall_cancelled', 'quiz_archiver').'</p>';
        echo '<p>'.$returnlink.'</p>';
    } else if ($data = $form->get_data()) {
        // Perform autoinstall.
        list($success, $log) = autoinstall::execute(
            $data->workerurl,
            $data->wsname,
            $data->rolename,
            $data->username
        );

        // Show result.
        echo '<p>'.get_string('autoinstall_started', 'quiz_archiver').'</p>';
        echo '<p>'.get_string('logs').'</p>';
        echo "<pre>{$log}</pre><br/>";

        if ($success) {
            echo '<p>'.get_string('autoinstall_success', 'quiz_archiver').'</p>';
        } else {
            echo '<p>'.get_string('autoinstall_failure', 'quiz_archiver').'</p>';
        }

        echo '<p>'.$returnlink.'</p>';
    } else {
        echo '<p>'.get_string('autoinstall_explanation', 'quiz_archiver').'</p>';
        echo '<p>'.get_string('autoinstall_explanation_details', 'quiz_archiver').'</p>';
        $form->display();
    }
} else {
    echo '<p>'.get_string('autoinstall_already_configured_long', 'quiz_archiver').'</p>';
    echo '<p>'.$returnlink.'</p>';
}

// End page.
echo $OUTPUT->footer();
