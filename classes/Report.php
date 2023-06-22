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
 * @copyright 2023 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_archiver;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/quiz/locallib.php");  # Required for legacy mod_quiz functions ...
//require_once("$CFG->dirroot/lib/filelib.php");


class Report {

    /** @var object Moodle course this report is part of */
    protected object $course;
    /** @var object Course module the quiz is part of */
    protected object $cm;
    /** @var object Quiz the attempt is part of */
    protected object $quiz;

    /**
     * @param object $course
     * @param object $cm
     * @param object $quiz
     */
    public function __construct(object $course, object $cm, object $quiz) {
        $this->course = $course;
        $this->cm = $cm;
        $this->quiz = $quiz;
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
                "userid" => $userid
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
     * Generates a HTML representation of the quiz attempt
     *
     * @param int $attemptid ID of the attempt this report is for
     *
     * @return string HTML DOM of the rendered quiz attempt report
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function generate(int $attemptid): string {
        global $DB, $PAGE;
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $this->cm->id);

        // Summary table start.
        // ============================================================================.

        // Work out some time-related things.
        $attempt = $attemptobj->get_attempt();
        $quiz = $attemptobj->get_quiz();
        $options = \mod_quiz_display_options::make_from_quiz($this->quiz, quiz_attempt_state($quiz, $attempt));
        $options->flags = quiz_get_flag_option($attempt, \context_module::instance($this->cm->id));
        $overtime = 0;

        if ($attempt->state == \quiz_attempt::FINISHED) {
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

        // Prepare summary information about the whole attempt.
        $summarydata = array();
        // We want the user information no matter what.
        $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
        $userpicture = new \user_picture($student);
        $userpicture->courseid = $attemptobj->get_courseid();
        $summarydata['user'] = array(
            'title'   => $userpicture,
            'content' => new \action_link(new \moodle_url('/user/view.php', array(
                'id' => $student->id, 'course' => $attemptobj->get_courseid())),
                fullname($student, true)),
        );

        // Timing information.
        $summarydata['startedon'] = array(
            'title'   => get_string('startedon', 'quiz'),
            'content' => userdate($attempt->timestart),
        );

        $summarydata['state'] = array(
            'title'   => get_string('attemptstate', 'quiz'),
            'content' => \quiz_attempt::state_name($attempt->state),
        );

        if ($attempt->state == \quiz_attempt::FINISHED) {
            $summarydata['completedon'] = array(
                'title'   => get_string('completedon', 'quiz'),
                'content' => userdate($attempt->timefinish),
            );
            $summarydata['timetaken'] = array(
                'title'   => get_string('timetaken', 'quiz'),
                'content' => $timetaken,
            );
        }

        if (!empty($overtime)) {
            $summarydata['overdue'] = array(
                'title'   => get_string('overdue', 'quiz'),
                'content' => $overtime,
            );
        }

        // Show marks (if the user is allowed to see marks at the moment).
        $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
        if ($options->marks >= \question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {

            if ($attempt->state != \quiz_attempt::FINISHED) {
                // Cannot display grade.
                echo '';
            } else if (is_null($grade)) {
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => quiz_format_grade($quiz, $grade),
                );

            } else {
                // Show raw marks only if they are different from the grade (like on the view page).
                if ($quiz->grade != $quiz->sumgrades) {
                    $a = new \stdClass();
                    $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
                    $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
                    $summarydata['marks'] = array(
                        'title'   => get_string('marks', 'quiz'),
                        'content' => get_string('outofshort', 'quiz', $a),
                    );
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
                $summarydata['grade'] = array(
                    'title'   => get_string('grade', 'quiz'),
                    'content' => $formattedgrade,
                );
            }
        }

        // Any additional summary data from the behaviour.
        $summarydata = array_merge($summarydata, $attemptobj->get_additional_summary_data($options));

        // Feedback if there is any, and the user is allowed to see it now.
        $feedback = $attemptobj->get_overall_feedback($grade);
        if ($options->overallfeedback && $feedback) {
            $summarydata['feedback'] = array(
                'title' => get_string('feedback', 'quiz'),
                'content' => $feedback,
            );
        }

        // Summary table end.
        // ==============================================================================.

        $slots = $attemptobj->get_slots();

        $renderer = $PAGE->get_renderer('mod_quiz');
        $string = '';
        $string .= $renderer->review_summary_table($summarydata, 0);

        // Display the questions. The overall goal is to have question_display_options from question/engine/lib.php
        // set so they would show what we wand and not show what we don't want.

        // Here we would call questions function on the renderer from mod/quiz/renderer.php but instead we do this
        // manually.
        foreach ($slots as $slot) {
            // Here we would call render_question_helper function on the quiz_attempt from mod/quiz/renderer.php but
            // instead we do this manually.

            $originalslot = $attemptobj->get_original_slot($slot);
            $number = $attemptobj->get_question_number($originalslot);
            $displayoptions = $attemptobj->get_display_options_with_edit_link(true, $slot, "");
            $displayoptions->marks = 2;
            $displayoptions->manualcomment = 1;
            $displayoptions->feedback = 1;
            $displayoptions->history = true;
            $displayoptions->correctness = 1;
            $displayoptions->numpartscorrect = 1;
            $displayoptions->flags = 1;
            $displayoptions->manualcommentlink = 0;

            if ($slot != $originalslot) {
                $attemptobj->get_question_attempt($slot)->set_max_mark(
                    $attemptobj->get_question_attempt($originalslot)->get_max_mark());
            }
            $quba = \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
            $string .= $quba->render_question($slot, $displayoptions, $number);

        }

        return $string;
    }

    /**
     * Like generate() but includes a full page HTML DOM including header and
     * footer
     *
     * @param int $attemptid ID of the attempt this report is for
     * @param bool $fix_relative_urls If true, all relative URLs will be
     * forcefully mapped to the Moodle base URL
     * @param bool $minimal If true, unneccessary elements (e.g. navbar) are
     * stripped from the generated HTML DOM
     * @param bool $inline_images If true, all images will be inlined as base64
     * to prevent rendering issues on user side
     *
     * @return string HTML DOM of the rendered quiz attempt report
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function generate_full_page(int $attemptid, bool $fix_relative_urls = true, bool $minimal = true, bool $inline_images = true): string {
        global $CFG, $OUTPUT;

        // Build HTML tree
        $html = "";
        $html .= $OUTPUT->header();
        $html .= self::generate($attemptid);
        $html .= $OUTPUT->footer();

        // Parse HTML as DOMDocument but supress consistency check warnings
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Patch relative URLs
        if ($fix_relative_urls) {
            $baseNode = $dom->createElement("base");
            $baseNode->setAttribute("href", $CFG->wwwroot);
            $dom->getElementsByTagName('head')[0]->appendChild($baseNode);
        }

        // Cleanup DOM if desired
        if ($minimal) {
            // We need to inject custom CSS to hide elements since the DOM generated by
            // Moodle can be corrupt which causes the PHP DOMDocument parser to die...
            $cssHacksNode = $dom->createElement("style", "
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
            ");
            $dom->getElementsByTagName('head')[0]->appendChild($cssHacksNode);
        }

        // Convert all local images to base64 if desired
        if ($inline_images) {
            // TODO: Remove debug attributea
            foreach ($dom->getElementsByTagName('img') as $img) {
                if ($img_src = $img->getAttribute('src')) {
                    $img->setAttribute('x-debug-original-url', $img_src);

                    // Remove any parameters and anchors from URL
                    $img_src = preg_replace('/^([^\?\&\#]+).*$/', '${1}', $img_src);

                    // Convert relative URLs to absolute URLs
                    $moodle_baseurl = $CFG->wwwroot;
                    if (getenv('VIAMINT_MOODLE_INTERNAL_HOST')) {
                        $moodle_baseurl = 'http://'.getenv('VIAMINT_MOODLE_INTERNAL_HOST');
                        $img_src = str_replace(parse_url($CFG->wwwroot, PHP_URL_HOST), getenv('VIAMINT_MOODLE_INTERNAL_HOST'), $img_src);
                    }
                    $img_src_url = $this->ensure_absolute_url($img_src, $moodle_baseurl);

                    $img->setAttribute("x-debug-absolute-url", $img_src_url);

                    # Make sure to only process web URLs
                    if (substr($img_src_url, 0, 4) === "http") {
                        $img_type = pathinfo($img_src_url, PATHINFO_EXTENSION);
                        # Whitelist images to inline
                        $image_mime_types = [
                            'png' => 'image/png',
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'svg' => 'image/svg+xml',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            'bmp' => 'image/bmp',
                            'ico' => 'image/x-icon',
                            'tiff' => 'image/tiff'
                        ];

                        if (array_key_exists($img_type, $image_mime_types)) {
                            $img_data = null;

                            // Check if image can easily be loaded from moodledata
                            $regex_matches = null;
                            if (preg_match('/^(https?:\/\/[^\/]+)?(\/question\/type\/stack\/plot\.php\/)(?P<filename>[^\/\#\?\&]+\.(png|svg))/m', $img_src_url, $regex_matches)) {
                                // Get STACK plot file
                                $filename = $CFG->dataroot . '/stack/plots/' . clean_filename($regex_matches['filename']);
                                $img->setAttribute("x-debug-stack-plotfile", $filename);
                                if (is_readable($filename)) {
                                    $img_data = file_get_contents($filename);
                                }
                            } elseif (preg_match('/^(https?:\/\/[^\/]+)?(\/pluginfile\.php)(?P<fullpath>\/(?P<contextid>[^\/]+)\/(?P<component>[^\/]+)\/(?P<filearea>[^\/]+)(\/(?P<itemid>\d+))?\/(?P<args>.*)?\/(?P<filename>[^\/\?\&\#]+))/m', $img_src_url, $regex_matches)) {
                                                                // Edge case: question / qtype files follow another pattern, inserting questionbank_id and question_slot after filearea ...
                                if ($regex_matches['component'] == 'question' || strpos($regex_matches['component'], 'qtype_') === 0) {
                                    $regex_matches = null;
                                    $img->setAttribute("x-debug-moodle-question-rule", "true");
                                    if (!preg_match('/^(https?:\/\/[^\/]+)?(\/pluginfile\.php)(?P<fullpath>\/(?P<contextid>[^\/]+)\/(?P<component>[^\/]+)\/(?P<filearea>[^\/]+)\/(?P<questionbank_id>[^\/]+)\/(?P<question_slot>[^\/]+)\/(?P<itemid>\d+)\/(?P<filename>[^\/\?\&\#]+))/m', $img_src_url, $regex_matches)) {
                                        $img->setAttribute("x-debug-moodle-question-match-failed", "true");
                                        continue;
                                    }
                                    $img->setAttribute("x-debug-moodle-question-match-failed", "false");
                                }

                                // Get file via Moodle File API
                                $fs = get_file_storage();
                                $file = $fs->get_file(
                                    $regex_matches['contextid'],
                                    $regex_matches['component'],
                                    $regex_matches['filearea'],
                                    !empty($regex_matches['itemid']) ? $regex_matches['itemid'] : 0,
                                    '/',  // Dirty simplification but works for now *sigh*
                                    $regex_matches['filename']
                                );
                                if ($file) {
                                    $img_data = $file->get_content();
                                } else {
                                    $img->setAttribute("x-debug-moodle-filestore-path", $regex_matches['fullpath']);
                                    $img->setAttribute("x-debug-moodle-filestore-file", $file);
                                    $img->setAttribute("x-debug-moodle-filestore-hash", sha1($regex_matches['fullpath']));
                                }
                            } else {
                                // Try to download
                                $curl = curl_init();
                                curl_setopt($curl, CURLOPT_URL, $img_src_url);
                                curl_setopt($curl, CURLOPT_HEADER, false);
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

                                // Pass current session cookie to request
                                $session_cookies = '';
                                foreach (["MoodleSession", "MOODLEID1_$CFG->sessioncookie", "PHPSESSID", "csrftoken", "sfcsrftoken"] as $cookie) {
                                    $session_cookies .= "$cookie=$_COOKIE[$cookie];";
                                    // FIXME: This fails because the API call is not authenticated. We need to somehow perform a login here...
                                    // IDEA: Let the archive worker call the login-endpoint and generate a valid session cookie to use for all requests.
                                }
                                curl_setopt($curl, CURLOPT_COOKIE, $session_cookies." path=/");

                                $img_data = curl_exec($curl);
                                if ($img_data === false) {
                                    $img->setAttribute('x-debug-curl-status', curl_getinfo($curl, CURLINFO_RESPONSE_CODE));
                                }
                                curl_close($curl);
                            }

                            // Encode and replace image
                            if ($img_data) {
                                $img_base64 = base64_encode($img_data);
                                $img->setAttribute('src', "data:$image_mime_types[$img_type];base64,$img_base64");
                            } else {
                                $img->setAttribute('x-debug-failed-to-encode-from', $img_src_url);
                            }
                        }
                    }
                }
            }
        }

        return $dom->saveHTML();
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
    function ensure_absolute_url(string $url, string $base): string {
        /* return if already absolute URL */
        if (parse_url($url, PHP_URL_SCHEME) != '') return $url;

        /* queries and anchors */
        if ($url[0]=='#' || $url[0]=='?') return $base.$url;

        /* parse base URL and convert to local variables: $scheme, $host, $path */
        extract(parse_url($base));

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ($rel[0] == '/') $path = '';

        /* dirty absolute URL */
        $abs = "$host$path/$rel";

        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}

        /* absolute URL is ready! */
        return $scheme.'://'.$abs;
    }

}