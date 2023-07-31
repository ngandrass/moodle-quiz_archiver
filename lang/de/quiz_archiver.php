<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     quiz_archiver
 * @category    string
 * @copyright   2023 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = $string['pluginname'];
$string['archiverreport'] = $string['pluginname'];
$string['checksum'] = 'Prüfsumme';
$string['beta_version_warning'] = 'Dieses Plugin befindet sich derzeit in der Beta-Phase. Bitte melden Sie alle Probleme und Fehler dem Website-Administrator.';

// Template: Overview
$string['users_with_attempts'] = 'Nutzende mit Versuchen';
$string['archive_quiz'] = 'Test archivieren';
$string['create_quiz_archive'] = 'Neues Archiv erstellen';
$string['archive_quiz_form_desc'] = 'Füllen Sie dieses Formular aus, um den Test zu archivieren. Die Archivierung findet asynchron statt und kann einige Zeit in Anspruch nehmen. Sie können den aktuellen Status jederzeit auf dieser Seite überprüfen und fertige Archive herunterladen.';
$string['export_attempts'] = 'Testversuche exportieren';
$string['export_attempts_help'] = 'Es werden stets alle Testversuche exportiert';
$string['export_attempts_num'] = 'Testversuche ({$a}) exportieren';
$string['export_attempts_num_help'] = $string['export_attempts_help'];
$string['export_course_backup'] = 'Vollständiges Moodle Kursbackup (.mbz) erzeugen';
$string['export_course_backup_help'] = 'Erzeugt ein vollständiges Moodle Kursbackup (.mbz) mit allen Kursinhalten und -einstellungen. Dies kann genutzt werden, um den gesamten Kurs in ein anderes Moodle-System zu importieren.';
$string['export_quiz_backup'] = 'Moodle Testbackup (.mbz) erzeugen';
$string['export_quiz_backup_help'] = 'Erzeugt ein Moodle Testbackup (.mbz) mit allen Testinhalten und Fragen. Dies kann genutzt werden, um den Test unabhängig von diesem Kurs in ein anderes Moodle-System zu importieren.';
$string['export_report_section_header'] = 'Test-Metadaten einschließen';
$string['export_report_section_header_help'] = 'Metadaten des Versuchs (z.B. Teilnehmender, Startzeitpunkt, Endzeitpunkt, Bewertung, ...) im Bericht einschließen';
$string['export_report_section_question'] = 'Fragen einschließen';
$string['export_report_section_question_help'] = 'Alle Fragen des Tests im Bericht einschließen';
$string['export_report_section_rightanswer'] = 'Richtige Antworten einschließen';
$string['export_report_section_rightanswer_help'] = 'Richtige Antworten der Fragen im Bericht einschließen';
$string['export_report_section_quiz_feedback'] = 'Testfeedback einschließen';
$string['export_report_section_quiz_feedback_help'] = 'Generelles Test-Feedback im Bericht einschließen';
$string['export_report_section_question_feedback'] = 'Individuelles Fragenfeedback einschließen';
$string['export_report_section_question_feedback_help'] = 'Individuelles Fragenfeedback im Bericht einschließen';
$string['export_report_section_general_feedback'] = 'Allgemeines Fragenfeedback einschließen';
$string['export_report_section_general_feedback_help'] = 'Allgemeines Fragenfeedback im Bericht einschließen';
$string['export_report_section_history'] = 'Bearbeitungsverlauf einschließen';
$string['export_report_section_history_help'] = 'Bearbeitungsverlauf der Testfragen im Bericht einschließen';
$string['job_overview'] = 'Testarchive';

// Job
$string['delete_job_warning'] = 'Sind Sie sicher, dass Sie diesen Archivierungsauftrag <b>inklusive aller archivierten Daten</b> löschen möchten?';
$string['jobid'] = 'Auftrags-ID';
$string['job_created_successfully'] = 'Neuer Archivierungsauftrag erfolgreich erstellt: {$a}';
$string['job_status_UNKNOWN'] = 'Unbekannt';
$string['job_status_UNINITIALIZED'] = 'Nicht initialisiert';
$string['job_status_AWAITING_PROCESSING'] = 'Wartend';
$string['job_status_RUNNING'] = 'Läuft';
$string['job_status_FINISHED'] = 'Fertig';
$string['job_status_FAILED'] = 'Fehler';
$string['job_status_TIMEOUT'] = 'Zeitüberschreitung';

// Settings
$string['setting_internal_wwwroot'] = 'Eigene Moodle Basis-URL';
$string['setting_internal_wwwroot_desc'] = 'Überschreibt die Standard Moodle Basis-URL ($CFG->wwwroot). Dies kann nützlich sein, wenn Sie den Quiz Archive Worker innerhalb eines internen Netzwerks (z.B. Docker) ausführen.';
$string['setting_job_timeout_min'] = 'Auftrags-Zeitlimit (Minuten)';
$string['setting_job_timeout_min_desc'] = 'Die maximale Laufzeit eines einzelnen Archivierungsauftrags in Minuten';
$string['setting_webservice_desc'] = 'Der Webservice, der die "generate_attempt_report" Webservice-Funktion ausführen darf';
$string['setting_webservice_userid'] = 'Webservice Nutzer-ID';
$string['setting_webservice_userid_desc'] = 'ID des Moodle Benutzers, der vom Quiz Archive Worker verwendet wird, um auf Testdaten zuzugreifen';
$string['setting_worker_url'] = 'Archive Worker URL';
$string['setting_worker_url_desc'] = 'URL des Archive Worker Services, der für die Ausführung von Archivierungsaufträgen genutzt wird';

// Errors
$string['error_worker_connection_failed'] = 'Verbindung zum Archive Worker fehlgeschlagen.';
$string['error_worker_reported_error'] = 'Der Archive Worker hat einen Fehler gemeldet: {$a}';
$string['error_worker_unknown'] = 'Ein unbekannter Fehler ist beim Senden des Auftrags zum Archive Worker aufgetreten.';
