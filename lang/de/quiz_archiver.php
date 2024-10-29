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
 * @copyright   2024 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreFile

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = 'Quiz Archiver';
$string['archiverreport'] = 'Quiz Archiver';
$string['checksum'] = 'Prüfsumme';
$string['beta_version_warning'] = 'Dieses Plugin befindet sich derzeit in der Beta-Phase. Bitte melden Sie alle Probleme und Fehler dem Website-Administrator.';
$string['thanks_for_installing'] = 'Vielen Dank für die Installation des Quiz Archiver Plugins!';
$string['go_to_plugin_settings'] = 'Plugin-Einstellungen öffnen';
$string['manual_configuration_continue'] = 'Um alle Plugin-Einstellungen manuell zu setzen, verwenden Sie die Schaltfläche "Weiter" am Ende dieser Seite.';

// Capabilities.
$string['quiz_archiver:view'] = 'Quiz Archiver Seite anzeigen';
$string['quiz_archiver:archive'] = 'Erstellen und Löschen von Testarchiven';
$string['quiz_archiver:use_webservice'] = 'Webservice des Quiz Archivers nutzen (lesend und schreibend)';

// General.
$string['a'] = '{$a}';
$string['progress'] = 'Fortschritt';
$string['quiz_archive'] = 'Testarchiv';
$string['quiz_archive_details'] = 'Details des Testarchivs';
$string['quiz_archive_not_found'] = 'Testarchiv nicht gefunden';
$string['quiz_archive_not_ready'] = 'Testarchiv noch nicht bereit';

// Template: Overview.
$string['archived'] = 'Archiviert';
$string['users_with_attempts'] = 'Nutzende mit Versuchen';
$string['archive_autodelete'] = 'Automatische Löschung';
$string['archive_autodelete_short'] = 'Löschung';
$string['archive_autodelete_help'] = 'Automatisches Löschen dieses Testarchivs nach einer bestimmten Zeit. Die Speicherdauer kann konfiguriert werden, sobald die automatische Löschung aktiviert ist.';
$string['archive_quiz'] = 'Test archivieren';
$string['archive_retention_time'] = 'Speicherdauer';
$string['archive_retention_time_help'] = 'Die Speicherdauer dieses Testarchivs, bevor es automatisch gelöscht wird. Diese Einstellung hat nur Auswirkungen, wenn die automatische Löschung aktiviert ist.';
$string['create_quiz_archive'] = 'Neues Testarchiv erstellen';
$string['archive_quiz_form_desc'] = 'Verwenden Sie dieses Formular um den ausgewählten Test zu archivieren. Die Archivierung findet asynchron statt und kann einige Zeit in Anspruch nehmen. Sie können den aktuellen Status jederzeit auf dieser Seite überprüfen sowie fertige Archive herunterladen.';
$string['error_archive_quiz_form_validation_failed'] = 'Validierung der gesendeten Formulardaten fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.';
$string['error_plugin_is_not_configured'] = 'Fehler: Das Quiz Archiver Plugin ist noch nicht konfiguriert. Bitte kontaktieren Sie Ihren Website-Administrator.';
$string['error_quiz_cannot_be_archived_unknown'] = 'Dieser Test kann aufgrund eines unbekannten Fehlers nicht archiviert werden. Bitte melden Sie dieses Problem an die Plugin-Entwickler.';
$string['export_attempts'] = 'Testversuche exportieren';
$string['export_attempts_help'] = 'Es werden stets alle Testversuche exportiert';
$string['export_attempts_num'] = 'Testversuche ({$a}) exportieren';
$string['export_attempts_num_help'] = 'Es werden stets alle Testversuche exportiert';
$string['export_attempts_image_optimize'] = 'Bilder optimieren';
$string['export_attempts_image_optimize_help'] = 'Wenn aktiviert, werden Bilder innerhalb der Versuchsberichte komprimiert und große Bilder unter Berücksichtigung der unten angegebenen Dimensionen verkleinert. Bilder werden ausschließlich verkleinert. Dies betrifft nur PDF-Exporte. HTML-Quelldateien behalten stets die Originalbildgröße bei.';
$string['export_attempts_image_optimize_group'] = 'Maximale Bildauflösung';
$string['export_attempts_image_optimize_group_help'] = 'Maximale Auflösung für Bilder innerhalb der Versuchsberichte in Pixeln (Breite x Höhe). Wenn ein Bild breiter oder höher als die angegebenen Dimensionen ist, wird es so verkleinert, dass es vollständig in die angegebenen Dimensionen passt. Das Seitenverhältnis wird dabei beibehalten. Dies kann nützlich sein, um die Gesamtgröße des Archivs zu reduzieren, wenn große Bilder im Test verwendet werden.';
$string['export_attempts_image_optimize_height'] = 'Maximale Bildhöhe';
$string['export_attempts_image_optimize_height_help'] = 'Maximale Höhe für Bilder innerhalb der Versuchsberichte in Pixeln. Wenn ein Bild höher als die angegebene Höhe ist, wird es auf die angegebene Höhe verkleinert, wobei das Seitenverhältnis beibehalten wird.';
$string['export_attempts_image_optimize_quality'] = 'Bildkompression';
$string['export_attempts_image_optimize_quality_help'] = 'Qualität der komprimierten Bilder (0 - 100 %). Je höher die Qualität, desto größer die Versuchsberichte. Diese Einstellung verhält sich wie die JPEG-Kompressionsintensität. Ein guter Richtwert sind 85 %.';
$string['export_attempts_image_optimize_width'] = 'Maximale Bildbreite';
$string['export_attempts_image_optimize_width_help'] = 'Maximale Breite für Bilder innerhalb der Versuchsberichte in Pixeln. Wenn ein Bild breiter als die angegebene Breite ist, wird es auf die angegebene Breite verkleinert, wobei das Seitenverhältnis beibehalten wird.';
$string['export_attempts_keep_html_files'] = 'HTML-Dateien';
$string['export_attempts_keep_html_files_desc'] = 'HTML-Quelldateien behalten';
$string['export_attempts_keep_html_files_help'] = 'Speichert die HTML-Quelldateien zusätzlich zu den erzeugten PDFs während des Exportvorgangs. Dies kann nützlich sein, wenn Sie auf den HTML DOM zugreifen möchten, aus dem die PDFs erzeugt wurden. Deaktivieren dieser Option kann die Archivgröße deutlich reduzieren!';
$string['export_attempts_paper_format'] = 'Papierformat';
$string['export_attempts_paper_format_help'] = 'Das Papierformat für den PDF-Export. Dies hat keinen Einfluss auf HTML-Exporte.';
$string['export_course_backup'] = 'Vollständiges Moodle Kursbackup (.mbz) erzeugen';
$string['export_course_backup_help'] = 'Erzeugt ein vollständiges Moodle Kursbackup (.mbz) mit allen Kursinhalten und -einstellungen. Dies kann genutzt werden, um den gesamten Kurs in einem anderen Moodle-System zu importieren.';
$string['export_quiz_backup'] = 'Moodle Testbackup (.mbz) erzeugen';
$string['export_quiz_backup_help'] = 'Erzeugt ein Moodle Testbackup (.mbz) mit allen Testinhalten und Fragen. Dies kann genutzt werden, um den Test unabhängig von diesem Kurs in einem anderen Moodle-System zu importieren.';
$string['export_report_section_header'] = 'Test-Metadaten einschließen';
$string['export_report_section_header_help'] = 'Metadaten des Versuchs (z.B. Teilnehmender, Startzeitpunkt, Endzeitpunkt, Bewertung, ...) im Bericht einschließen';
$string['export_report_section_question'] = 'Fragen einschließen';
$string['export_report_section_question_help'] = 'Alle Fragen des Versuchs im Bericht einschließen';
$string['export_report_section_rightanswer'] = 'Richtige Antworten einschließen';
$string['export_report_section_rightanswer_help'] = 'Richtige Antworten für alle Fragen im Bericht einschließen';
$string['export_report_section_quiz_feedback'] = 'Testfeedback einschließen';
$string['export_report_section_quiz_feedback_help'] = 'Generelles Test-Feedback im Bericht einschließen';
$string['export_report_section_question_feedback'] = 'Individuelles Fragenfeedback einschließen';
$string['export_report_section_question_feedback_help'] = 'Individuelles Fragenfeedback im Bericht einschließen';
$string['export_report_section_general_feedback'] = 'Allgemeines Fragenfeedback einschließen';
$string['export_report_section_general_feedback_help'] = 'Allgemeines Fragenfeedback im Bericht einschließen';
$string['export_report_section_history'] = 'Antworthistorie einschließen';
$string['export_report_section_history_help'] = 'Antworthistorie für alle Testfragen im Bericht einschließen';
$string['export_report_section_attachments'] = 'Dateiabgaben einschließen';
$string['export_report_section_attachments_help'] = 'Alle Dateiabgaben (z.B. von Freitextaufgaben) im Archiv einschließen. Warnung: Dies kann die Archivgröße erheblich erhöhen.';
$string['job_overview'] = 'Testarchive';
$string['last_updated'] = 'Zuletzt aktualisiert';
$string['num_attempts'] = 'Anzahl der Testversuche';

// Job creation form: Filename pattern.
$string['archive_filename_pattern'] = 'Archivname';
$string['archive_filename_pattern_help'] = 'Name des erzeugten Archivs. Variablen <b>müssen</b> dem <code>${variablename}</code> Muster folgen. Die Dateiendung wird automatisch hinzugefügt.<br><br><b>Verfügbare Variablen</b>: <ul>{$a->variables}</ul><b>Verbotene Zeichen</b>: <code>{$a->forbiddenchars}</code>';
// TODO (MDL-0): Remove the following 2 lines after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
$string['archive_filename_pattern_moodle42'] = 'Archivname';
$string['archive_filename_pattern_moodle42_help'] = 'Name des erzeugten Archivs. Variablen <b>müssen</b> dem <code>${variablename}</code> Muster folgen. Die Dateiendung wird automatisch hinzugefügt.<br><br><b>Verfügbare Variablen</b>: <ul><li><code>${courseid}</code>: Kurs-ID</li><li><code>${coursename}</code>: Kursname</li><li><code>${courseshortname}</code>: Kurzer Kursname</li><li><code>${cmid}</code>: Kursmodul-ID</li><li><code>${quizid}</code>: Test-ID</li><li><code>${quizname}</code>: Testname</li><li><code>${date}</code>: Aktuelles Datum <small>(YYYY-MM-DD)</small></li><li><code>${time}</code>: Aktuelle Uhrzeit <small>(HH-MM-SS)</small></li><li><code>${timestamp}</code>: Aktueller Unix-Zeitstempel</li></ul><b>Verbotene Zeichen</b>: <code>\/.:;*?!"&lt;&gt;|</code>';
$string['archive_filename_pattern_variable_courseid'] = 'Kurs-ID';
$string['archive_filename_pattern_variable_coursename'] = 'Kursname';
$string['archive_filename_pattern_variable_courseshortname'] = 'Kurzer Kursname';
$string['archive_filename_pattern_variable_cmid'] = 'Kursmodul-ID';
$string['archive_filename_pattern_variable_quizid'] = 'Test-ID';
$string['archive_filename_pattern_variable_quizname'] = 'Testname';
$string['archive_filename_pattern_variable_date'] = 'Aktuelles Datum <small>(YYYY-MM-DD)</small>';
$string['archive_filename_pattern_variable_time'] = 'Aktuelle Uhrzeit <small>(HH-MM-SS)</small>';
$string['archive_filename_pattern_variable_timestamp'] = 'Aktueller Unix-Zeitstempel';
$string['error_invalid_archive_filename_pattern'] = 'Ungültiger Archivname. Bitte korrigieren Sie Ihre Eingabe und versuchen Sie es erneut.';
$string['export_attempts_filename_pattern'] = 'Versuchsname';
$string['export_attempts_filename_pattern_help'] = 'Name eines archivierten Versuchs. Variablen <b>müssen</b> dem <code>${variablename}</code> Muster folgen. Die Dateiendung wird automatisch hinzugefügt.<br><br><b>Verfügbare Variablen</b>: <ul>{$a->variables}</ul><b>Verbotene Zeichen</b>: <code>{$a->forbiddenchars}</code>';
// TODO (MDL-0): Remove the following 2 lines after deprecation of Moodle 4.1 (LTS) on 08-12-2025.
$string['export_attempts_filename_pattern_moodle42'] = 'Versuchsname';
$string['export_attempts_filename_pattern_moodle42_help'] = 'Name eines archivierten Versuchs. Variablen <b>müssen</b> dem <code>${variablename}</code> Muster folgen. Die Dateiendung wird automatisch hinzugefügt.<br><br><b>Verfügbare Variablen</b>: <ul><li><code>${courseid}</code>: Kurs-ID</li><li><code>${coursename}</code>: Kursname</li><li><code>${courseshortname}</code>: Kurzer Kursname</li><li><code>${cmid}</code>: Kursmodul-ID</li><li><code>${quizid}</code>: Test-ID</li><li><code>${quizname}</code>: Testname</li><li><code>${attemptid}</code>: Versuchs-ID</li><li><code>${username}</code>: Nutzer Anmeldename</li><li><code>${firstname}</code>: Nutzer Vorname</li><li><code>${lastname}</code>: Nutzer Nachname</li><li><code>${timestart}</code>: Versuchsstart (Unix-Zeitstempel)</li><li><code>${timefinish}</code>: Versuchsende (Unix-Zeitstempel)</li><li><code>${date}</code>: Aktuelles Datum <small>(YYYY-MM-DD)</small></li><li><code>${time}</code>: Aktuelle Uhrzeit <small>(HH-MM-SS)</small></li><li><code>${timestamp}</code>: Aktueller Unix-Zeitstempel</li></ul><b>Verbotene Zeichen</b>: <code>\/.:;*?!"&lt;&gt;|</code>';
$string['export_attempts_filename_pattern_variable_courseid'] = 'Kurs-ID';
$string['export_attempts_filename_pattern_variable_coursename'] = 'Kursname';
$string['export_attempts_filename_pattern_variable_courseshortname'] = 'Kurzer Kursname';
$string['export_attempts_filename_pattern_variable_cmid'] = 'Kursmodul-ID';
$string['export_attempts_filename_pattern_variable_quizid'] = 'Test-ID';
$string['export_attempts_filename_pattern_variable_quizname'] = 'Testname';
$string['export_attempts_filename_pattern_variable_attemptid'] = 'Versuchs-ID';
$string['export_attempts_filename_pattern_variable_username'] = 'Nutzer Anmeldename';
$string['export_attempts_filename_pattern_variable_firstname'] = 'Nutzer Vorname';
$string['export_attempts_filename_pattern_variable_lastname'] = 'Nutzer Nachname';
$string['export_attempts_filename_pattern_variable_idnumber'] = 'Nutzer ID-Nummer';
$string['export_attempts_filename_pattern_variable_timestart'] = 'Versuchsstart (Unix-Zeitstempel)';
$string['export_attempts_filename_pattern_variable_timefinish'] = 'Versuchsende (Unix-Zeitstempel)';
$string['export_attempts_filename_pattern_variable_date'] = 'Aktuelles Datum <small>(YYYY-MM-DD)</small>';
$string['export_attempts_filename_pattern_variable_time'] = 'Aktuelle Uhrzeit <small>(HH-MM-SS)</small>';
$string['export_attempts_filename_pattern_variable_timestamp'] = 'Aktueller Unix-Zeitstempel';
$string['error_invalid_attempt_filename_pattern'] = 'Ungültiger Versuchsname. Bitte korrigieren Sie Ihre Eingabe und versuchen Sie es erneut.';

// Job.
$string['delete_artifact'] = 'Testarchiv löschen';
$string['delete_artifact_success'] = 'Testarchiv des Archivierungsauftrags mit der ID <code>{$a}</code> wurde erfolgreich gelöscht. Die Auftragsmetadaten existieren weiterhin und können mit der Schaltfläche "Archivierungsauftrag löschen" endgültig gelöscht werden.';
$string['delete_artifact_warning'] = 'Sind Sie sicher, dass Sie dieses Testarchiv inklusive <b>aller archivierten Daten</b> löschen möchten?. Die Metadaten des Archivierungsauftrags werden hierbei nicht gelöscht.';
$string['delete_job'] = 'Archivierungsauftrag löschen';
$string['delete_job_success'] = 'Archivierungsauftrag mit der ID <code>{$a}</code> wurde erfolgreich gelöscht.';
$string['delete_job_warning'] = 'Sind Sie sicher, dass Sie diesen Archivierungsauftrag <b>inklusive aller archivierten Daten</b> löschen möchten?';
$string['delete_job_warning_retention'] = '<b>Achtung:</b> Dieser Archivierungsauftrag ist für die automatische Löschung am <code>{$a}</code> vorgesehen. Sind Sie absolut sicher, dass Sie ihn <b>vor Ablauf seiner geplanten Lebensdauer</b> löschen möchten?';
$string['jobid'] = 'Auftrags-ID';
$string['job_created_successfully'] = 'Neuer Archivierungsauftrag erfolgreich erstellt. Auftrags-ID: {$a}';
$string['job_status_UNKNOWN'] = 'Unbekannt';
$string['job_status_UNKNOWN_help'] = 'Der Status dieses Auftrags ist unbekannt. Bitte melden Sie dieses Problem, wenn es weiterhin besteht.';
$string['job_status_UNINITIALIZED'] = 'Neu';
$string['job_status_UNINITIALIZED_help'] = 'Der Auftrag wurde noch nicht initialisiert.';
$string['job_status_AWAITING_PROCESSING'] = 'Wartend';
$string['job_status_AWAITING_PROCESSING_help'] = 'Der Auftrag wurde erfasst und wartet auf die Verarbeitung durch den Archive Worker Service.';
$string['job_status_RUNNING'] = 'Läuft';
$string['job_status_RUNNING_help'] = 'Der Auftrag wird derzeit vom Archive Worker Service verarbeitet. Der Fortschritt des Auftrags wird periodisch aktualisiert (Standard: alle 15 Sekunden).';
$string['job_status_WAITING_FOR_BACKUP'] = 'Backup ausstehend';
$string['job_status_WAITING_FOR_BACKUP_help'] = 'Der Auftrag wartet auf die Erstellung eines Moodle-Backups. Dies kann je nach Kursgröße einige Zeit in Anspruch nehmen.';
$string['job_status_FINALIZING'] = 'Finalisieren';
$string['job_status_FINALIZING_help'] = 'Der Archive Worker Service finalisiert das Archiv und überträgt es an Moodle. Dies kann je nach Größe des Archivs einige Zeit in Anspruch nehmen.';
$string['job_status_FINISHED'] = 'Fertig';
$string['job_status_FINISHED_help'] = 'Der Auftrag wurde erfolgreich abgeschlossen. Das Archiv ist bereit zum Download.';
$string['job_status_FAILED'] = 'Fehler';
$string['job_status_FAILED_help'] = 'Der Auftrag ist fehlgeschlagen. Bitte versuchen Sie es erneut und kontaktieren Sie Ihren Systemadministrator, wenn das Problem weiterhin besteht.';
$string['job_status_TIMEOUT'] = 'Zeitüberschreitung';
$string['job_status_TIMEOUT_help'] = 'Der Auftrag wurde aufgrund einer Zeitüberschreitung abgebrochen. Dies kann bei sehr großen Tests passieren. Bitte kontaktieren Sie Ihren Systemadministrator, wenn das Problem weiterhin besteht.';
$string['job_status_DELETED'] = 'Gelöscht';
$string['job_status_DELETED_help'] = 'Das Testarchiv und alle zugehörigen Daten wurden entfernt. Die Auftragsmetadaten existieren weiterhin und können bei Bedarf endgültig gelöscht werden.';

// Job details.
$string['archive_already_signed'] = 'Testarchiv ist bereits signiert';
$string['archive_already_signed_with_jobid'] = 'Testarchiv des Archivierungsauftrag mit der ID <code>{$a}</code> ist bereits signiert.';
$string['archive_autodelete_deleted'] = 'Testarchive wurde automatisch gelöscht';
$string['archive_autodelete_in'] = 'Testarchiv wird gelöscht in {$a}';
$string['archive_autodelete_disabled'] = 'Deaktiviert';
$string['archive_autodelete_now'] = 'Testarchiv wird zeitnah automatisch gelöscht';
$string['archive_deleted'] = 'Testarchiv wurde gelöscht';
$string['archive_not_signed'] = 'Testarchiv ist nicht signiert';
$string['archive_signature'] = 'Signatur';
$string['archive_signed_successfully'] = 'Testarchiv erfolgreich signiert';
$string['archive_signed_successfully_with_jobid'] = 'Testarchiv des Archivierungsauftrag mit der ID <code>{$a}</code> wurde erfolgreich signiert.';
$string['archive_signing_failed'] = 'Signierung des Testarchivs fehlgeschlagen';
$string['archive_signing_failed_with_jobid'] = 'Signierung des Testarchivs des Archivierungsauftrags mit der ID <code>{$a}</code> ist aufgrund eines generischen Fehlers fehlgeschlagen. Bitte überprüfen Sie die Plugin-Einstellungen und versuchen Sie es erneut.';
$string['archive_signing_failed_no_artifact'] = 'Keine gültige Archivdatei gefunden';
$string['archive_signing_failed_no_artifact_with_jobid'] = 'Signierung des Testarchivs des Archivierungsauftrags mit der ID <code>{$a}</code> ist fehlgeschlagen. Keine gültige Archivdatei gefunden.';
$string['archive_signing_failed_tsp_disabled'] = 'Signierung ist global deaktiviert';
$string['sign_archive'] = 'Testarchiv jetzt signieren';
$string['sign_archive_warning'] = 'Sind Sie sicher, dass Sie dieses Testarchiv jetzt signieren möchten?';
$string['signed_on'] = 'Signiert am';
$string['signed_by'] = 'von';
$string['tsp_query_filename'] = 'query.tsq';
$string['tsp_reply_filename'] = 'reply.tsr';

// TimeStampProtocolClient.
$string['tsp_client_error_content_type'] = 'TSP-Server hat einen unerwarteten Content-Type {$a} zurückgegeben';
$string['tsp_client_error_curl'] = 'Fehler beim senden des TSP-Requests: {$a}';
$string['tsp_client_error_http_code'] = 'TSP-Server hat einen unerwarteten HTTP Statuscode {$a} zurückgegeben';

// Settings.
$string['setting_autoconfigure'] = 'Automatische Konfiguration';
$string['setting_header_archive_worker'] = 'Archive Worker Service';
$string['setting_header_archive_worker_desc'] = 'Konfiguration des Archive Worker Services sowie des Moodle Webservices.';
$string['setting_header_docs_desc'] = 'Dieses Plugin archiviert Testversuche als PDF- und HTML-Dateien zur langfristigen Speicherung unabhängig von Moodle. Es <b>erfordert die Installation eines separaten <a href="https://quizarchiver.gandrass.de/installation/archiveworker/" target="_blank">Archive Worker Services</a></b> um korrekt zu funktionieren. Die <a href="https://quizarchiver.gandrass.de/" target="_blank">Dokumentation</a> enthält alle notwendigen Informationen und Installationsanweisungen.';
$string['setting_header_job_presets'] = 'Archivierungs-Vorgaben';
$string['setting_header_job_presets_desc'] = 'Systemweite Vorgaben für die Erstellung von Testarchiven. Hinterlegte Standardwerte können bei der Erstellung eines neuen Testarchivs individuell anpassen. Jede einzelne Einstellung kann jedoch auch gesperrt werden um zu verhindern, dass Manager / Trainer diese verändern können. Dies kann nützlich sein, um organisationsweite Archivierungsrichtlinien durchzusetzen.';
$string['setting_header_tsp'] = 'Signierung von Testarchiven';
$string['setting_header_tsp_desc'] = 'Testarchive und der Zeitpunkt ihrer Erstellung können von einer vertrauenswürdigen Zertifizierungsstelle mithilfe des <a href="https://en.wikipedia.org/wiki/Time_stamp_protocol" target="_blank">Time-Stamp Protocol (TSP)</a> gemäß <a href="https://www.ietf.org/rfc/rfc3161.txt" target="_blank">RFC 3161</a> digital signiert werden. Diese Signaturen können verwendet werden, um die Datenintegrität und den Zeitpunkt der Archivierung zu einem späteren Zeitpunkt kryptografisch nachzuweisen. Testarchive können automatisch bei der Erstellung oder nachträglich manuell signiert werden.';
$string['setting_internal_wwwroot'] = 'Eigene Moodle Basis-URL';
$string['setting_internal_wwwroot_desc'] = 'Überschreibt die Moodle Basis-URL (<code>$CFG->wwwroot</code>) in den erzeugten Versuchs-Berichten. Dies kann nützlich sein, wenn der Archive Worker Service innerhalb eines privaten Netzwerks (z.B. Docker) läuft und er über das private Netzwerk auf Moodle zugreifen soll.<br/>Beispiel: <code>http://moodle/</code>';
$string['setting_job_timeout_min'] = 'Auftrags Zeitlimit (Minuten)';
$string['setting_job_timeout_min_desc'] = 'Die maximale Laufzeit eines einzelnen Archivierungsauftrags in Minuten, bevor er durch Moodle abgebrochen wird. Das Webservice Zugriffstoken des Auftrags wird nach diesem Zeitlimit invalidiert.<br/>Hinweis: Dieses Zeitlimit kann das im Archive Worker Service konfigurierte Zeitlimit nicht überschreiten. Das kürzere Zeitlimit hat stets Vorrang.';
$string['setting_tsp_automatic_signing'] = 'Testarchive automatisch signieren';
$string['setting_tsp_automatic_signing_desc'] = 'Testarchive automatisch bei der Erstellung signieren.';
$string['setting_tsp_enable'] = 'Signierung aktivieren';
$string['setting_tsp_enable_desc'] = 'Erlaubt die Signierung von Testarchiven mithilfe des Time-Stamp Protocols (TSP). Wenn diese Option deaktiviert ist können Testarchive weder manuell noch automatisch signiert werden.';
$string['setting_tsp_server_url'] = 'TSP-Server URL';
$string['setting_tsp_server_url_desc'] = 'URL des Time-Stamp Protocol (TSP) Servers, der für die Signierung von Testarchiven genutzt wird.<br/>Beispiele: <code>https://freetsa.org/tsr</code>, <code>https://zeitstempel.dfn.de</code>, <code>http://timestamp.digicert.com</code>';
$string['setting_webservice_desc'] = 'Der externe Service (Webservice), welcher alle <code>quiz_archiver_*</code> Funktionen ausführen darf. Er muss ebenfalls die Berechtigung haben, Dateien hoch- und herunterzuladen.';
$string['setting_webservice_userid'] = 'Webservice Nutzer-ID';
$string['setting_webservice_userid_desc'] = 'User-ID des Moodle Nutzers, der vom Archive Worker Service genutzt wird, um auf Testdaten zuzugreifen. Er muss alle Berechtigungen besitzen, die in der <a href="https://quizarchiver.gandrass.de/configuration/initialconfig/manual" target="_blank">Dokumentation</a> aufgelistet sind, um korrekt zu funktionieren. Aus Sicherheitsgründen sollte dies ein dedizierter Nutzer ohne globale Administrationsrechte sein.';
$string['setting_worker_url'] = 'Archive Worker URL';
$string['setting_worker_url_desc'] = 'URL des Archive Worker Services, der für die Ausführung von Archivierungsaufträgen genutzt wird. Wenn Sie den Quiz Archiver lediglich ausprobieren wollen, können Sie vorerst auch den <a href="https://quizarchiver.gandrass.de/installation/archiveworker/#using-the-free-public-demo-service" target="_blank">kostenfreien öffentlichen Archive Worker Service</a> nutzen. <br/>Beispiel: <code>http://127.0.0.1:8080</code> oder <code>http://moodle-quiz-archive-worker:8080</code>';

// Errors.
$string['error_worker_connection_failed'] = 'Verbindung zum Archive Worker fehlgeschlagen.';
$string['error_worker_reported_error'] = 'Der Archive Worker hat einen Fehler gemeldet: {$a}';
$string['error_worker_unknown'] = 'Beim Senden des Auftrags zum Archive Worker ist ein unbekannter Fehler aufgetreten.';

// Privacy.
$string['privacy:metadata:core_files'] = 'Das Quiz Archiver Plugin speichert erstellte Testarchive im Moodle Dateisystem.';
$string['privacy:metadata:quiz_archiver_jobs'] = 'Metadaten über erstellte Testarchive.';
$string['privacy:metadata:quiz_archiver_jobs:courseid'] = 'ID des Kurses der zu einem Testarchiv gehört.';
$string['privacy:metadata:quiz_archiver_jobs:cmid'] = 'ID des Kursmoduls das zu einem Testarchiv gehört.';
$string['privacy:metadata:quiz_archiver_jobs:quizid'] = 'ID des Tests der zu einem Testarchiv gehört.';
$string['privacy:metadata:quiz_archiver_jobs:userid'] = 'ID des Nutzers der ein Testarchiv erstellt hat.';
$string['privacy:metadata:quiz_archiver_jobs:timecreated'] = 'Zeitpunkt der Erstellung des Testarchivs.';
$string['privacy:metadata:quiz_archiver_jobs:timemodified'] = 'Zeitpunkt der letzten Änderung des Testarchivs (z.B.: Status-Update).';
$string['privacy:metadata:quiz_archiver_job_settings'] = 'Einstellungen während der Erstellung eines Testarchivs (z.B.: eingeschlossene Abschnitte, Anzahl der Versuche, ...).';
$string['privacy:metadata:quiz_archiver_job_settings:key'] = 'Der Name einer entsprechenden Einstellung (z.B.: Anzahl der Versuche).';
$string['privacy:metadata:quiz_archiver_job_settings:value'] = 'Der Wert einer entsprechenden Einstellung (z.B.: 42).';
$string['privacy:metadata:quiz_archiver_tsp'] = 'Time-Stamp Protocol (TSP) Daten über die Signierung eines Testarchivs.';
$string['privacy:metadata:quiz_archiver_tsp:timecreated'] = 'Zeitpunkt der Signierung des Testarchivs.';
$string['privacy:metadata:quiz_archiver_tsp:server'] = 'Die URL des TSP Servers, der das Testarchiv signiert hat.';
$string['privacy:metadata:quiz_archiver_tsp:timestampquery'] = 'Die TimestampQuery, der an den TSP Server gesendet wurde.';
$string['privacy:metadata:quiz_archiver_tsp:timestampreply'] = 'Die TimestampReply, die vom TSP Server empfangen wurde.';

// Tasks.
$string['task_cleanup_temp_files'] = 'Bereinigen temporärer Dateien';
$string['task_cleanup_temp_files_start'] = 'Bereinige temporäre Dateien ...';
$string['task_cleanup_temp_files_report'] = '{$a} temporäre Dateien gelöscht.';
$string['task_autodelete_job_artifacts'] = 'Löschen abgelaufener Testarchive';
$string['task_autodelete_job_artifacts_start'] = 'Lösche abgelaufene Testarchive ...';
$string['task_autodelete_job_artifacts_report'] = '{$a} Testarchive gelöscht.';

// Autoinstall.
$string['autoinstall_already_configured'] = 'Plugin ist bereits konfiguriert';
$string['autoinstall_already_configured_long'] = 'Das Quiz Archiver Plugin ist bereits konfiguriert. Eine erneute automatische Konfiguration ist nicht möglich.';
$string['autoinstall_cancelled'] = 'Die automatische Konfiguration des Quiz Archiver Plugins wurde abgebrochen. Es wurden keine Einstellungen verändert.';
$string['autoinstall_explanation'] = 'Das Quiz Archiver Plugin erfordert anfangs einige Konfigurationsschritte, um zu funktionieren (siehe  <a href="https://quizarchiver.gandrass.de/configuration/" target="_blank">Konfiguration</a>). Sie können diese Einstellungen entweder manuell vornehmen, oder die automatische Konfigurationsfunktion verwenden um alle Moodle-bezogenen Einstellungen zu setzen.';
$string['autoinstall_explanation_details'] = 'Die automatische Konfiguration übernimmt die folgenden Schritte:<ul><li>Setzen aller Plugin-Einstellungen auf ihre Standardwerte</li><li>Aktivieren von Webservices und dem REST-Protokoll</li><li>Erstellen einer Quiz Archiver Service Rolle und eines entsprechenden Nutzers</li><li>Erstellen eines neuen Webservices mit allen erforderlichen Webservice-Funktionen</li><li>Autorisieren des Nutzers zur Nutzung des Webservices</li></ul>';
$string['autoinstall_failure'] = 'Die automatische Konfiguration des Quiz Archiver Plugins ist <b>fehlgeschlagen</b>.';
$string['autoinstall_plugin'] = 'Quiz Archiver: Automatische Konfiguration';
$string['autoinstall_started'] = 'Automatische Konfiguration gestartet ...';
$string['autoinstall_start_now'] = 'Automatische Konfiguration jetzt starten';
$string['autoinstall_success'] = 'Die automatische Konfiguration des Quiz Archiver Plugins wurde <b>erfolgreich abgeschlossen</b>.';
$string['autoinstall_rolename'] = 'Rollenname';
$string['autoinstall_rolename_help'] = 'Name der Rolle, die für den Quiz Archiver Service Nutzer erstellt wird.';
$string['autoinstall_username'] = 'Nutzername';
$string['autoinstall_username_help'] = 'Name des Nutzerkontos, das für den Zugriff auf den Quiz Archiver Webservice erstellt wird.';
$string['autoinstall_wsname'] = 'Webservicename';
$string['autoinstall_wsname_help'] = 'Name des Webservices, der für den Quiz Archive Worker erstellt wird.';
