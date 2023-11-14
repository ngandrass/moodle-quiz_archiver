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

$string['pluginname'] = 'Quiz Archiver';
$string['archiver'] = 'Quiz Archiver';
$string['archiverreport'] = 'Quiz Archiver';
$string['checksum'] = 'Prüfsumme';
$string['beta_version_warning'] = 'Dieses Plugin befindet sich derzeit in der Beta-Phase. Bitte melden Sie alle Probleme und Fehler dem Website-Administrator.';

// Capabilities
$string['quiz_archiver:view'] = 'Quiz Archiver Berichtsseite anzeigen';
$string['quiz_archiver:archive'] = 'Erstellen und Löschen von Testarchiven';
$string['quiz_archiver:use_webservice'] = 'Webservice des Quiz Archivers nutzen (lesend und schreibend)';

// General
$string['quiz_archive'] = 'Testarchiv';
$string['quiz_archive_details'] = 'Details des Testarchivs';
$string['quiz_archive_not_found'] = 'Testarchiv nicht gefunden';
$string['quiz_archive_not_ready'] = 'Testarchiv noch nicht bereit';

// Template: Overview
$string['archived'] = 'Archiviert';
$string['users_with_attempts'] = 'Nutzende mit Versuchen';
$string['archive_quiz'] = 'Test archivieren';
$string['create_quiz_archive'] = 'Neues Archiv erstellen';
$string['archive_quiz_form_desc'] = 'Füllen Sie dieses Formular aus, um den Test zu archivieren. Die Archivierung findet asynchron statt und kann einige Zeit in Anspruch nehmen. Sie können den aktuellen Status jederzeit auf dieser Seite überprüfen und fertige Archive herunterladen.';
$string['export_attempts'] = 'Testversuche exportieren';
$string['export_attempts_help'] = 'Es werden stets alle Testversuche exportiert';
$string['export_attempts_num'] = 'Testversuche ({$a}) exportieren';
$string['export_attempts_num_help'] = 'Es werden stets alle Testversuche exportiert';
$string['export_attempts_paper_format'] = 'Papierformat';
$string['export_attempts_paper_format_help'] = 'Das Papierformat für den PDF-Export. Dies hat keinen Einfluss auf HTML-Exporte.';
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
$string['num_attempts'] = 'Anzahl Testversuche';

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

// Job details
$string['archive_already_signed'] = 'Testarchiv ist bereits signiert';
$string['archive_not_signed'] = 'Testarchiv ist nicht signiert';
$string['archive_signature'] = 'Signatur';
$string['archive_signed_successfully'] = 'Testarchiv erfolgreich signiert';
$string['archive_signing_failed'] = 'Signierung des Testarchivs fehlgeschlagen';
$string['archive_signing_failed_no_artifact'] = 'Keine gültige Archivdatei gefunden';
$string['archive_signing_failed_tsp_disabled'] = 'Signierung global ist deaktiviert';
$string['sign_archive'] = 'Testarchiv jetzt signieren';
$string['sign_archive_warning'] = 'Sind Sie sicher, dass Sie dieses Testarchiv jetzt signieren möchten?';
$string['signed_on'] = 'Signiert am';
$string['signed_by'] = 'von';
$string['tsp_query_filename'] = 'query.tsq';
$string['tsp_reply_filename'] = 'reply.tsr';

// TimeStampProtocolClient
$string['tsp_client_error_content_type'] = 'TSP Server hat einen unerwarteten Content-Type {$a} zurückgegeben';
$string['tsp_client_error_curl'] = 'Fehler beim senden des TSP Requests: {$a}';
$string['tsp_client_error_http_code'] = 'TSP Server hat unerwarteten HTTP Statuscode {$a} zurückgegeben';

// Settings
$string['setting_header_archive_worker'] = 'Archive Worker Service';
$string['setting_header_archive_worker_desc'] = 'Konfiguration des Archive Worker Services und des Moodle Webservices.';
$string['setting_header_docs_desc'] = 'Dieses Plugin archiviert Testversuche als PDF- und HTML-Dateien zur langfristigen Speicherung unabhängig von Moodle. Es <b>erfordert die Installation eines separaten <a href="https://github.com/ngandrass/moodle-quiz-archive-worker" target="_blank">Archive Worker Services</a></b> um korrekt zu funktionieren. Die <a href="https://github.com/ngandrass/moodle-quiz_archiver#readme" target="_blank">Dokumentation</a> enthält alle notwendigen Informationen und Installationsanweisungen.';
$string['setting_header_tsp'] = 'Signierung von Testarchiven';
$string['setting_header_tsp_desc'] = 'Testarchive und der Zeitpunkt ihrer Erstellung können von einer vertrauenswürdigen Zertifizierungsstelle mithilfe des <a href="https://en.wikipedia.org/wiki/Time_stamp_protocol" target="_blank">Time-Stamp Protocol (TSP)</a> gemäß <a href="https://www.ietf.org/rfc/rfc3161.txt" target="_blank">RFC 3161</a> digital signiert werden. Diese Signaturen können verwendet werden, um die Datenintegrität und den Zeitpunkt der Archivierung zu einem späteren Zeitpunkt kryptografisch nachzuweisen. Testarchive können automatisch bei der Erstellung oder nachträglich manuell signiert werden.';
$string['setting_internal_wwwroot'] = 'Eigene Moodle Basis-URL';
$string['setting_internal_wwwroot_desc'] = 'Überschreibt die Moodle Basis-URL (<code>$CFG->wwwroot</code>) in den erzeugten Versuchs-Berichten. Dies kann nützlich sein, wenn der Archive Worker Service innerhalb eines privaten Netzwerks (z.B. Docker) läuft und er über das private Netzwerk auf Moodle zugreifen soll.<br/>Beispiel: <code>http://moodle/</code>';
$string['setting_job_timeout_min'] = 'Auftrags Zeitlimit (Minuten)';
$string['setting_job_timeout_min_desc'] = 'The number of minutes a single archive job is allowed to run before it is aborted by Moodle. Job web service access tokens become invalid after this timeout.';
$string['setting_job_timeout_min_desc'] = 'Die maximale Laufzeit eines einzelnen Archivierungsauftrags in Minuten, bevor er durch Moodle abgebrochen wird. Das Webservice Zugriffstoken des Auftrags wird nach diesem Zeitlimit invalidiert.';
$string['setting_tsp_automatic_signing'] = 'Testarchive automatisch signieren';
$string['setting_tsp_automatic_signing_desc'] = 'Testarchive automatisch bei der Erstellung signieren.';
$string['setting_tsp_enable'] = 'Signierung aktivieren';
$string['setting_tsp_enable_desc'] = 'Erlaubt die Signierung von Testarchiven mithilfe des Time-Stamp Protocols (TSP). Wenn diese Option deaktiviert ist können Testarchive weder manuell noch automatisch signiert werden.';
$string['setting_tsp_server_url'] = 'TSP server URL';
$string['setting_tsp_server_url_desc'] = 'URL des Time-Stamp Protocol (TSP) Servers, der für die Signierung von Testarchiven genutzt wird.<br/>Beispiele: <code>https://freetsa.org/tsr</code>, <code>https://zeitstempel.dfn.de</code>, <code>http://timestamp.digicert.com</code>';
$string['setting_webservice_desc'] = 'Der externe Service, welcher alle <code>quiz_archiver_*</code> Funktionen ausführen darf. Er muss ebenfalls die Berechtigung haben, Dateien hoch- und herunterzuladen.';
$string['setting_webservice_userid'] = 'Webservice Nutzer-ID';
$string['setting_webservice_userid_desc'] = 'User-ID des Moodle Nutzers, der vom Archive Worker Service genutzt wird, um auf Testdaten zuzugreifen. Er muss alle Berechtigungen besitzen, die in der <a href="https://github.com/ngandrass/moodle-quiz_archiver#configuration" target="_blank">Dokumentation</a> aufgelistet sind, um korrekt zu funktionieren. Aus Sicherheitsgründen sollte dies ein dedizierter Nutzer ohne globale Administrationsrechte sein.';
$string['setting_worker_url'] = 'Archive Worker URL';
$string['setting_worker_url_desc'] = 'URL des Archive Worker Services, der für die Ausführung von Archivierungsaufträgen genutzt wird.<br/>Beispiel: <code>http://127.0.0.1:8080</code> oder <code>http://moodle-quiz-archive-worker:8080</code>';

// Errors
$string['error_worker_connection_failed'] = 'Verbindung zum Archive Worker fehlgeschlagen.';
$string['error_worker_reported_error'] = 'Der Archive Worker hat einen Fehler gemeldet: {$a}';
$string['error_worker_unknown'] = 'Ein unbekannter Fehler ist beim Senden des Auftrags zum Archive Worker aufgetreten.';

// Privacy
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
