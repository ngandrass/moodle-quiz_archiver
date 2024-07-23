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
 * This file defines the quiz archiver class.
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

use curl;
use mod_quiz\quiz_attempt;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// Required for legacy mod_quiz functions ...
require_once("$CFG->dirroot/mod/quiz/locallib.php");  // @codeCoverageIgnore


/**
 * Quiz report renderer
 */
class Report {

    /** @var object Moodle admin settings object */
    protected object $config;
    /** @var object Moodle course this report is part of */
    protected object $course;
    /** @var object Course module the quiz is part of */
    protected object $cm;
    /** @var object Quiz the attempt is part of */
    protected object $quiz;

    /** @var array Sections that can be included in the report */
    public const SECTIONS = [
        "header",
        "quiz_feedback",
        "question",
        "question_feedback",
        "general_feedback",
        "rightanswer",
        "history",
        "attachments",
    ];

    /** @var array Dependencies of report sections */
    public const SECTION_DEPENDENCIES = [
        "header" => [],
        "question" => [],
        "quiz_feedback" => ["header"],
        "question_feedback" => ["question"],
        "general_feedback" => ["question"],
        "rightanswer" => ["question"],
        "history" => ["question"],
        "attachments" => ["question"],
    ];

    /** @var string[] Available paper formats for attempt PDFs */
    public const PAPER_FORMATS = [
        'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6',
        'Letter', 'Legal', 'Tabloid', 'Ledger',
    ];

    /**
     * Creates a new Report
     *
     * @param object $course
     * @param object $cm
     * @param object $quiz
     * @throws \dml_exception
     */
    public function __construct(object $course, object $cm, object $quiz) {
        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->config = get_config('quiz_archiver');
    }

    /**
     * Determines if the given webservice token is allowed to generate reports
     * for this quiz
     *
     * @param string $wstoken Webservice token to test
     * @return bool True if the given webservice token is allowed to export reports
     */
    public function has_access(string $wstoken): bool {
        global $DB;

        try {
            // Check if job with given wstoken exists for this quiz.
            $jobdata = $DB->get_record(ArchiveJob::JOB_TABLE_NAME, [
                'courseid' => $this->course->id,
                'cmid' => $this->cm->id,
                'quizid' => $this->quiz->id,
                'wstoken' => $wstoken,
            ], 'status, timecreated', MUST_EXIST);

            // Completed / aborted jobs invalidate access.
            if ($jobdata->status == ArchiveJob::STATUS_FINISHED || $jobdata->status == ArchiveJob::STATUS_FAILED) {
                return false;
            }

            // Job must still be valid.
            if (($jobdata->timecreated + ($this->config->job_timeout_min * 60)) > time()) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Get all attempts for all users inside this quiz, excluding previews
     *
     * @return array Array of all attempt IDs together with the userid that were
     * made inside this quiz. Indexed by attemptid.
     *
     * @throws \dml_exception
     */
    public function get_attempts(): array {
        global $DB;

        return $DB->get_records_sql(
            "SELECT id AS attemptid, userid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid",
            [
                "quizid" => $this->quiz->id,
            ]
        );
    }

    /**
     * Gets the metadata of all attempts made inside this quiz, excluding previews.
     *
     * @param array|null $filterattemptids If given, only attempts with the given
     * IDs will be returned.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_attempts_metadata(?array $filterattemptids = null): array {
        global $DB;

        // Handle attempt ID filter.
        if ($filterattemptids) {
            $filterwhereclause = "AND qa.id IN (".implode(', ', array_map(fn ($v): string => intval($v), $filterattemptids)). ")";
        }

        // Get all requested attempts.
        return $DB->get_records_sql(
            "SELECT qa.id AS attemptid, qa.userid, qa.attempt, qa.state, qa.timestart, qa.timefinish, ".
            "       u.username, u.firstname, u.lastname ".
            "FROM {quiz_attempts} qa LEFT JOIN {user} u ON qa.userid = u.id ".
            "WHERE qa.preview = 0 AND qa.quiz = :quizid " . ($filterwhereclause ?? ''),
            [
                "quizid" => $this->quiz->id,
            ]
        );
    }

    /**
     * Returns a list of IDs of all users that made at least one attempt on this
     * quiz, excluding previews
     *
     * @return array List of IDs of found users
     *
     * @throws \dml_exception
     */
    public function get_users_with_attempts(): array {
        global $DB;

        $res = $DB->get_records_sql(
            "SELECT DISTINCT userid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid",
            [
                "quizid" => $this->quiz->id,
            ]
        );

        return array_map(fn($v): int => $v->userid, $res);
    }

    /**
     * Returns the ID of the latest attempt a user made on this quiz, excluding
     * previews
     *
     * @param int $userid The ID of the user to search for an attempt
     *
     * @return ?int ID of the latest attempt the given user made on this quiz.
     * Null if no attempt was made.
     *
     * @throws \dml_exception
     */
    public function get_latest_attempt_for_user($userid): ?int {
        global $DB;

        $res = $DB->get_records_sql(
            "SELECT id AS attemptid " .
            "FROM {quiz_attempts} " .
            "WHERE preview = 0 AND quiz = :quizid AND userid = :userid ".
            "ORDER BY id DESC ".
            "LIMIT 1",
            [
                "quizid" => $this->quiz->id,
                "userid" => $userid,
            ]
        );

        if (empty($res)) {
            return null;
        }

        return array_values($res)[0]->attemptid;
    }

    /**
     * Checks if an attempt with the given ID exists inside this quiz
     *
     * @param int $attemptid ID of the attempt to check for existence
     * @return bool True if an attempt with the given ID exists inside this quiz
     * @throws \dml_exception
     */
    public function attempt_exists(int $attemptid): bool {
        global $DB;

        return $DB->count_records_sql(
            "SELECT COUNT(id) FROM {quiz_attempts} WHERE preview = 0 AND id = :attemptid",
            ['attemptid' => $attemptid]
        ) > 0;
    }

    /**
     * Builds the section selection array based on the given archive quiz form
     * data.
     *
     * @param object $archivequizformdata Data object from a submitted archive_quiz_form
     * @return array Associative array containing the selected sections for export
     */
    public static function build_report_sections_from_formdata(object $archivequizformdata): array {
        // Extract section settings from form data object.
        $reportsections = [];
        foreach (self::SECTIONS as $section) {
            $reportsections[$section] = $archivequizformdata->{'export_report_section_'.$section};
        }

        // Disable all sections that depend on a disabled section.
        foreach (self::SECTION_DEPENDENCIES as $section => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!$reportsections[$dependency]) {
                    $reportsections[$section] = 0;
                }
            }
        }

        return $reportsections;
    }

    /**
     * Returns a list of all files that were attached to questions inside the
     * given attempt
     *
     * @param int $attemptid ID of the attempt to get the files from
     * @return array containing all files that are attached to the questions
     *               inside the given attempt.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_attempt_attachments(int $attemptid): array {
        // Prepare.
        $files = [];
        $ctx = \context_module::instance($this->cm->id);
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->cm->id);

        // Get all files from all questions inside this attempt.
        foreach ($attemptobj->get_slots() as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            $qafiles = $qa->get_last_qt_files('attachments', $ctx->id);

            foreach ($qafiles as $qafile) {
                $files[] = [
                    'usageid' => $qa->get_usage_id(),
                    'slot' => $slot,
                    'file' => $qafile,
                ];
            }
        }

        return $files;
    }

    /**
     * Returns a list of metadata for all files that were attached to questions
     * inside the given attempt to be used within the webservice API
     *
     * @param int $attemptid ID of the attempt to get the files from
     * @return array containing the metadata of all files that are attached to
     * the questions inside the given attempt.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_attempt_attachments_metadata(int $attemptid): array {
        $res = [];

        foreach ($this->get_attempt_attachments($attemptid) as $attachment) {
            $downloadurl = strval(\moodle_url::make_webservice_pluginfile_url(
                $attachment['file']->get_contextid(),
                $attachment['file']->get_component(),
                $attachment['file']->get_filearea(),
                "{$attachment['usageid']}/{$attachment['slot']}/{$attachment['file']->get_itemid()}",
                /* ^-- YES, this is the abomination of a non-numeric itemid that question_attempt::get_response_file_url()
                   creates while eating innocent programmers for breakfast ... */
                $attachment['file']->get_filepath(),
                $attachment['file']->get_filename()
            ));

            $res[] = (object) [
                'slot' => $attachment['slot'],
                'filename' => $attachment['file']->get_filename(),
                'filesize' => $attachment['file']->get_filesize(),
                'mimetype' => $attachment['file']->get_mimetype(),
                'contenthash' => $attachment['file']->get_contenthash(),
                'downloadurl' => $downloadurl,
            ];
        }

        return $res;
    }

    /**
     * Generates a HTML representation of the quiz attempt
     *
     * @param int $attemptid ID of the attempt this report is for
     * @param array $sections Array of sections to include in the report
     *
     * @return string HTML DOM of the rendered quiz attempt report
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function generate(int $attemptid, array $sections): string {
        global $CFG, $DB, $PAGE;
        $ctx = \context_module::instance($this->cm->id);
        $renderer = $PAGE->get_renderer('mod_quiz');
        $html = '';

        // Get quiz data and determine state / elapsed time.
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->cm->id);
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $quba = \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
        $quba->preload_all_step_users();
        $options = \mod_quiz\question\display_options::make_from_quiz($this->quiz, quiz_attempt_state($quiz, $attempt));
        $options->flags = quiz_get_flag_option($attempt, $ctx);
        $overtime = 0;

        if ($attempt->state == quiz_attempt::FINISHED) {
            if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
                if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
                    $overtime = $timetaken - $quiz->timelimit;
                    $overtime = format_time($overtime);
                }
                $timetaken = format_time($timetaken);
            } else {
                $timetaken = "-";
            }
        } else {
            $timetaken = get_string('unfinished', 'quiz');
        }

        // Section: Quiz header.
        if ($sections['header']) {

            $quizheaderdata = [];
            $attemptuser = $DB->get_record('user', ['id' => $attemptobj->get_userid()]);
            $userpicture = new \user_picture($attemptuser);
            $userpicture->courseid = $attemptobj->get_courseid();
            $quizheaderdata['user'] = [
                'title' => $userpicture,
                'content' => new \action_link(
                    new \moodle_url('/user/view.php', ['id' => $attemptuser->id, 'course' => $attemptobj->get_courseid()]),
                    fullname($attemptuser, true)
                ),
            ];

            // Quiz metadata.
            $quizheaderdata['course'] = [
                'title' => get_string('course'),
                'content' => $this->course->fullname . ' (Course-ID: ' . $this->course->id . ')',
            ];

            $quizheaderdata['quiz'] = [
                'title' => get_string('modulename', 'quiz'),
                'content' => $this->quiz->name . ' (Quiz-ID: ' . $this->quiz->id . ')',
            ];

            // Timing information.
            $quizheaderdata['startedon'] = [
                'title' => get_string('startedon', 'quiz'),
                'content' => userdate($attempt->timestart),
            ];

            $quizheaderdata['state'] = [
                'title' => get_string('attemptstate', 'quiz'),
                'content' => quiz_attempt::state_name($attempt->state),
            ];

            if ($attempt->state == quiz_attempt::FINISHED) {
                $quizheaderdata['completedon'] = [
                    'title' => get_string('completedon', 'quiz'),
                    'content' => userdate($attempt->timefinish),
                ];
                $quizheaderdata['timetaken'] = [
                    'title' => get_string('attemptduration', 'quiz'),
                    'content' => $timetaken,
                ];
            }

            if (!empty($overtime)) {
                $quizheaderdata['overdue'] = [
                    'title' => get_string('overdue', 'quiz'),
                    'content' => $overtime,
                ];
            }

            // Grades.
            $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
            if ($options->marks >= \question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {
                if (is_null($grade)) {
                    $quizheaderdata['grade'] = [
                        'title' => get_string('gradenoun'),
                        'content' => quiz_format_grade($quiz, $grade),
                    ];
                }

                if ($attempt->state == quiz_attempt::FINISHED) {
                    // Show raw marks only if they are different from the grade (like on the view page).
                    if ($quiz->grade != $quiz->sumgrades) {
                        $a = new \stdClass();
                        $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
                        $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
                        $quizheaderdata['marks'] = [
                            'title' => get_string('marks', 'quiz'),
                            'content' => get_string('outofshort', 'quiz', $a),
                        ];
                    }

                    // Now the scaled grade.
                    $a = new \stdClass();
                    $a->grade = \html_writer::tag('b', quiz_format_grade($quiz, $grade));
                    $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                    if ($quiz->grade != 100) {
                        $a->percent = \html_writer::tag('b', format_float(
                            $attempt->sumgrades * 100 / $quiz->sumgrades, 0));
                        $formattedgrade = get_string('outofpercent', 'quiz', $a);
                    } else {
                        $formattedgrade = get_string('outof', 'quiz', $a);
                    }
                    $quizheaderdata['grade'] = [
                        'title' => get_string('gradenoun'),
                        'content' => $formattedgrade,
                    ];
                }
            }

            // Any additional summary data from the behaviour.
            $quizheaderdata = array_merge($quizheaderdata, $attemptobj->get_additional_summary_data($options));

            // Feedback if there is any, and the user is allowed to see it now.
            if ($sections['quiz_feedback']) {
                $feedback = $attemptobj->get_overall_feedback($grade);
                $quizheaderdata['feedback'] = [
                    'title' => get_string('feedback', 'quiz'),
                    'content' => $feedback,
                ];
            }

            // Add export date.
            $quizheaderdata['exportdate'] = [
                'title' => get_string('archived', 'quiz_archiver'),
                'content' => userdate(time()),
            ];

            // Add summary table to the html.
            if ($CFG->branch <= 403) {
                // TODO (MDL-0): Remove after Moodle 4.1 (LTS) support ends on 2025-12-08.
                $html .= $renderer->review_summary_table($quizheaderdata, 0);
            } else {
                // TODO (MDL-0): Rework into proper use of new 4.4 API but create appropriate test cases first.
                $html .= $renderer->review_attempt_summary(
                    \mod_quiz\output\attempt_summary_information::create_from_legacy_array($quizheaderdata),
                    0
                );
            }
        }

        // Section: Quiz questions.
        if ($sections['question']) {
            $slots = $attemptobj->get_slots();
            foreach ($slots as $slot) {
                // Define display options for this question.
                $originalslot = $attemptobj->get_original_slot($slot);
                $number = $attemptobj->get_question_number($originalslot);
                $displayoptions = $attemptobj->get_display_options_with_edit_link(true, $slot, "");
                $displayoptions->readonly = true;
                $displayoptions->marks = 2;
                $displayoptions->manualcomment = 1;
                $displayoptions->rightanswer = $sections['rightanswer'];
                $displayoptions->feedback = $sections['question_feedback'];
                $displayoptions->generalfeedback = $sections['general_feedback'];
                $displayoptions->history = $sections['history'];
                $displayoptions->correctness = 1;
                $displayoptions->numpartscorrect = 1;
                $displayoptions->flags = 1;
                $displayoptions->manualcommentlink = 0;

                // Render question as HTML.
                if ($slot != $originalslot) {
                    $attemptobj->get_question_attempt($slot)->set_max_mark(
                        $attemptobj->get_question_attempt($originalslot)->get_max_mark());
                }
                $html .= $quba->render_question($slot, $displayoptions, $number);
            }
        }

        return $html;
    }

    /**
     * Like generate() but includes a full page HTML DOM including header and
     * footer
     *
     * @param int $attemptid ID of the attempt this report is for
     * @param array $sections Array of self::SECTIONS to include in the report
     * @param bool $fixrelativeurls If true, all relative URLs will be
     * forcefully mapped to the Moodle base URL
     * @param bool $minimal If true, unneccessary elements (e.g. navbar) are
     * stripped from the generated HTML DOM
     * @param bool $inlineimages If true, all images will be inlined as base64
     * to prevent rendering issues on user side
     *
     * @return string HTML DOM of the rendered quiz attempt report
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \DOMException
     */
    public function generate_full_page(
        int   $attemptid,
        array $sections,
        bool  $fixrelativeurls = true,
        bool  $minimal = true,
        bool  $inlineimages = true
    ): string {
        global $CFG, $OUTPUT;

        // Build HTML tree.
        $html = "";
        $html .= $OUTPUT->header();
        $html .= self::generate($attemptid, $sections);
        $html .= $OUTPUT->footer();

        // Parse HTML as DOMDocument but supress consistency check warnings.
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Patch relative URLs.
        if ($fixrelativeurls) {
            $basenode = $dom->createElement("base");
            $basenode->setAttribute("href", $CFG->wwwroot);
            $dom->getElementsByTagName('head')[0]->appendChild($basenode);
        }

        // Cleanup DOM if desired.
        if ($minimal) {
            // We need to inject custom CSS to hide elements since the DOM generated by.
            // Moodle can be corrupt which causes the PHP DOMDocument parser to die...
            $csshacksnode = $dom->createElement("style", "
                nav.navbar {
                    display: none !important;
                }

                footer {
                    display: none !important;
                }

                div#page {
                    margin-top: 0 !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    height: initial !important;
                }

                div#page-wrapper {
                    height: initial !important;
                }

                .stackinputerror {
                    display: none !important;
                }
            ");
            $dom->getElementsByTagName('head')[0]->appendChild($csshacksnode);
        }

        // Convert all local images to base64 if desired.
        if ($inlineimages) {
            foreach ($dom->getElementsByTagName('img') as $img) {
                if (!$this->convert_image_to_base64($img)) {
                    $img->setAttribute('x-debug-inlining-failed', 'true');
                }
            }
        }

        return $dom->saveHTML();
    }

    // @codingStandardsIgnoreStart
    /** @var string Regex for URLs of qtype_stack plots */
    const REGEX_MOODLE_URL_STACKPLOT = '/^(?P<wwwroot>https?:\/\/.+)?(\/question\/type\/stack\/plot\.php\/)(?P<filename>[^\/\#\?\&]+\.(png|svg))$/m';

    /** @var string Regex for Moodle file API URLs */
    const REGEX_MOODLE_URL_PLUGINFILE = '/^(?P<wwwroot>https?:\/\/.+)?(\/pluginfile\.php)(?P<fullpath>\/(?P<contextid>[^\/]+)\/(?P<component>[^\/]+)\/(?P<filearea>[^\/]+)(\/(?P<itemid>\d+))?\/(?P<args>.*)?\/(?P<filename>[^\/\?\&\#]+))$/m';

    /** @var string Regex for Moodle file API URLs of specific types: component=(question|qtype_.*) */
    const REGEX_MOODLE_URL_PLUGINFILE_QUESTION_AND_QTYPE = '/^(?P<wwwroot>https?:\/\/.+)?(\/pluginfile\.php)(?P<fullpath>\/(?P<contextid>[^\/]+)\/(?P<component>[^\/]+)\/(?P<filearea>[^\/]+)\/(?P<questionbank_id>[^\/]+)\/(?P<question_slot>[^\/]+)\/(?P<itemid>\d+)\/(?P<filename>[^\/\?\&\#]+))$/m';

    /** @var string Regex for Moodle theme image files */
    const REGEX_MOODLE_URL_THEME_IMAGE = '/^(?P<wwwroot>https?:\/\/.+)?(\/theme\/image\.php\/)(?P<themename>[^\/]+)\/(?P<component>[^\/]+)\/(?P<rev>[^\/]+)\/(?P<image>.+)$/m';
    // @codingStandardsIgnoreEnd

    /** @var string[] Mapping of file extensions to file types that are allowed to process */
    const ALLOWED_IMAGE_TYPES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'ico' => 'image/x-icon',
        'tiff' => 'image/tiff',
    ];

    /**
     * Tries to download and inline images of <img> tags with src attributes as base64 encoded strings. Replacement
     * happens in-place.
     *
     * @param \DOMElement $img The <img> element to process
     * @return bool true on success
     * @throws \dml_exception
     */
    protected function convert_image_to_base64(\DOMElement $img): bool {
        global $CFG;

        // Only process images with src attribute.
        if (!$img->getAttribute('src')) {
            $img->setAttribute('x-debug-notice', 'no source present');
            return false;
        } else {
            $img->setAttribute('x-original-source', $img->getAttribute('src'));
        }

        // Remove any parameters and anchors from URL.
        $imgsrc = preg_replace('/^([^\?\&\#]+).*$/', '${1}', $img->getAttribute('src'));

        // Convert relative URLs to absolute URLs.
        $config = get_config('quiz_archiver');
        $moodlebaseurl = rtrim($config->internal_wwwroot ?: $CFG->wwwroot, '/').'/';
        if ($config->internal_wwwroot) {
            $imgsrc = str_replace(rtrim($CFG->wwwroot, '/'), rtrim($config->internal_wwwroot, '/'), $imgsrc);
        }
        $imgsrcurl = $this->ensure_absolute_url($imgsrc, $moodlebaseurl);

        // Make sure to only process web URLs and nothing that somehow remained a valid local filepath.
        if (!substr($imgsrcurl, 0, 4) === "http") { // Yes, this includes https as well ;).
            $img->setAttribute('x-debug-notice', 'not a web URL');
            return false;
        }

        // Only process allowed image types.
        $imgext = strtolower(pathinfo($imgsrcurl, PATHINFO_EXTENSION));
        if (!array_key_exists($imgext, self::ALLOWED_IMAGE_TYPES)) {
            // Edge case: Moodle theme images must not always contain extensions.
            if (!preg_match(self::REGEX_MOODLE_URL_THEME_IMAGE, $imgsrcurl)) {
                $img->setAttribute('x-debug-notice', 'image type not allowed');
                return false;
            }
        }

        // Try to get image content based on link type.
        $regexmatches = null;
        $imgdata = null;
        $imgmime = array_key_exists($imgext, self::ALLOWED_IMAGE_TYPES) ? self::ALLOWED_IMAGE_TYPES[$imgext] : null;

        // Handle special internal URLs first.
        $isinternalurl = substr($imgsrcurl, 0, strlen($moodlebaseurl)) === $moodlebaseurl;
        if ($isinternalurl) {
            if (preg_match(self::REGEX_MOODLE_URL_PLUGINFILE, $imgsrcurl, $regexmatches)) {
                // Link type: Moodle pluginfile URL.
                $img->setAttribute('x-url-type', 'MOODLE_URL_PLUGINFILE');

                // Edge case detection: question / qtype files follow another pattern,
                // inserting questionbank_id and question_slot after filearea ...
                if ($regexmatches['component'] == 'question' || strpos($regexmatches['component'], 'qtype_') === 0) {
                    $regexmatches = null;
                    if (!preg_match(self::REGEX_MOODLE_URL_PLUGINFILE_QUESTION_AND_QTYPE, $imgsrcurl, $regexmatches)) {
                        $img->setAttribute('x-url-type', 'MOODLE_URL_PLUGINFILE_QUESTION_AND_QTYPE');
                        return false;
                    }
                }

                // Decode RFC 3986 URL escaped sequences.
                $regexmatches['filename'] = urldecode($regexmatches['filename']);

                // Get file content via Moodle File API.
                $fs = get_file_storage();
                $file = $fs->get_file(
                    $regexmatches['contextid'],
                    $regexmatches['component'],
                    $regexmatches['filearea'],
                    !empty($regexmatches['itemid']) ? $regexmatches['itemid'] : 0,
                    '/',  // Dirty simplification but works for now *sigh*.
                    $regexmatches['filename'],
                );

                if (!$file) {
                    $img->setAttribute('x-debug-notice', 'moodledata file not found');
                    return false;
                }
                $imgdata = $file->get_content();
            } else if (preg_match(self::REGEX_MOODLE_URL_STACKPLOT, $imgsrcurl, $regexmatches)) {
                // Link type: qtype_stack plotfile.
                $img->setAttribute('x-url-type', 'MOODLE_URL_STACKPLOT');

                // Decode RFC 3986 URL escaped sequences.
                $regexmatches['filename'] = urldecode($regexmatches['filename']);

                // Get STACK plot file from disk.
                $filename = $CFG->dataroot . '/stack/plots/' . clean_filename($regexmatches['filename']);
                if (!is_readable($filename)) {
                    $img->setAttribute('x-debug-notice', 'stack plot file not readable');
                    return false;
                }
                $imgdata = file_get_contents($filename);
            } else {
                $img->setAttribute('x-debug-internal-url-without-handler', '');
            }
        }

        // Fall back to generic URL handling if image data not already set by internal handling routines.
        if ($imgdata === null) {
            if (preg_match(self::REGEX_MOODLE_URL_THEME_IMAGE, $imgsrcurl)) {
                // Link type: Moodle theme image.
                // We should be able to download there images using a simple HTTP request.
                // Accessing them directly from disk is a little more complicated due to
                // caching and other logic (see: /theme/image.php).
                // Let's try to keep it this way until we encounter explicit problems.
                $img->setAttribute('x-url-type', 'MOODLE_URL_THEME_IMAGE');
            } else {
                // Link type: Generic.
                $img->setAttribute('x-url-type', 'GENERIC');
            }

            // No special local file access. Try to download via HTTP request.
            $c = new curl(['ignoresecurity' => $isinternalurl]);
            $imgdata = $c->get($imgsrcurl);  // Curl handle automatically closed.
            if ($c->get_info()['http_code'] !== 200 || $imgdata === false) {
                $img->setAttribute('x-debug-more', $imgdata);
                $img->setAttribute('x-debug-notice', 'HTTP request failed');
                return false;
            }

            // Check if we need to detect mime type from response headers.
            if (!$imgmime) {
                $imgmime = $c->get_info()['content_type'];
                if (!in_array($imgmime, self::ALLOWED_IMAGE_TYPES)) {
                    $img->setAttribute('x-debug-notice', 'image type from response header is not allowed');
                    return false;
                }
            }
        }

        // Encode and replace image if present.
        if (!$imgdata) {
            $img->setAttribute('x-debug-notice', 'no image data');
            return false;
        }
        $imgbase64 = base64_encode($imgdata);
        $img->setAttribute('src', 'data:'.$imgmime.';base64,'.$imgbase64);

        return true;
    }

    /**
     * Takes any URL and ensures that if will become an absolute URL. Relative
     * URLs will be prefixed with $base. Already absolute URLs will be returned
     * as they are.
     *
     * @param string $url URL to ensure to be absolute
     * @param string $base Base to prepend to relative URLs
     * @return string Absolute URL
     */
    protected static function ensure_absolute_url(string $url, string $base): string {
        // Return if already absolute URL.
        if (parse_url($url, PHP_URL_SCHEME) != '') {
            return $url;
        }

        // Queries and anchors.
        if ($url[0] == '#' || $url[0] == '?') {
            return $base.$url;
        }

        // Parse base URL and convert to local variables: $scheme, $host, $path.
        $urlparsed = parse_url($base);
        $scheme = $urlparsed['scheme'];
        $host = $urlparsed['host'];
        $path = $urlparsed['path'];

        // Remove non-directory element from path.
        $path = preg_replace('#/[^/]*$#', '', $path);

        // Destroy path if relative url points to root.
        if ($url[0] == '/') {
            $path = '';
        }

        // Dirty absolute URL.
        $abs = "$host$path/$url";

        // Replace '//' or '/./' or '/foo/../' with '/'.
        $re = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
        for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
            continue;
        }

        // Absolute URL is ready!
        return $scheme.'://'.$abs;
    }

}
