# Changelog


## Version 1.2.4 (2024021901)

- Fix image inlining for Moodle instances that reside in subdirectories (e.g., `https://your.domain/moodle`)
  - Thanks a lot to @500gLychee for extensive testing and reporting!
- Fix inlining of miscellaneous local images that do not fall into any specific link type category
- Detect quizzes without attempts and prevent archive creation until at least one attempt was registered
- Create GitHub issue template forms for bug reports and feature requests
  - Found a bug? Please report it here: https://github.com/ngandrass/moodle-quiz_archiver/issues


## Version 1.2.3 (2024011200)

- Fix job setting storage issues with MySQL / MariaDB
- Create PHPUnit tests for key components
- Set up automated Moodle-CI tests for all supported Moodle and PHP versions with both PostgreSQL and MariaDB
- Remove use of 'mixed' union type to fix PHP 7.4 compatibility
- Replace ValueError exceptions to fix PHP 7.4 compatibility
- Improve PHPDoc comments
- Fix deserialization of example JSON test data within Mustache templates
- Improve error reporting of Moodle backup management class
- Improve webservice parameter strictness


## Version 1.2.2 (2024010800)

- Fix locking of archive retention time job preset
- Fix wrong interpretation of checked and locked checkboxes within job preset settings
- Server-side prevention of job setting changes/tinkering if presets are locked globally


## Version 1.2.1 (2024010500)

- Fix compatibility with PHP 7.4 by replacing union type usages
- Improve code quality


## Version 1.2.0 (2024010400)

- Support for automatic deletion of quiz archives after a specified retention period
- Allow to enforce a global retention policy via the plugin settings
- Allow manual deletion of artifact files while keeping job metadata from job details modal
- Differentiate between quiz archive creation and deletion permission
- Link to course, quiz, and archive creation user in job details modal
- Reduce job action form URL arguments and improve displayed warnings / information
- Delete TSP data, if job or artifact is deleted


## Version 1.1.0 (2023121400)

- Allow customization of archive and attempt report filenames based on variables (e.g., quiz name, username, ...)
- Add option to exclude HTML files from generated archives to save space
- Implement global archive job presets (e.g., attempt report sections, exported data, paper format, ...)
- Introduce global archiving policies (i.e., locked archive job presets that cannot be changed by the user)
- Fix archive job timeout on Moodle instances where `filter_mathjaxloader` is loaded, but attempts do not contain any MathJax formulas

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v1.3.0`
is required.

**Note:** Since version 1.1.0, HTML files will be excluded from the archive by
default. This behavior can be changed for each job individually or via the
global archive presets.


## Version 1.0.0 (2023113000)

- Switch from BETA to STABLE status! :)
- Backwards-compatibility to Moodle 4.1 (LTS) until end of support on 08-12-2025
- Include question attachments (e.g., essay file submissions) in quiz archive
- Fix dynamic rendering of Drag and Drop question types
- Provide reference quiz, containing all standard Moodle question types

**WARNING**: If you are upgrading from v0.6.3 or earlier, you need to add the
permissions `mod/quiz:reviewmyattempts` and `mod/quiz:viewreports` to the quiz
archiver service account role. This is required to allow access to quiz
attachments (e.g., uploaded essay files). The provided XML role definition
[res/moodle_role_quiz_archiver.xml](res/moodle_role_quiz_archiver.xml) is
already updated to reflect these changes.

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v1.2.0`
is required.


## Version 0.6.3 (2023112200)

- Implement privacy API to ensure GDPR compliance
- Remember attempts and users that are contained within a given quiz archive
- Add support to internally extract a single attempt from a full quiz archive
- Introduce a scheduled task to execute cleanup routines

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v1.1.3`
is required during archive creation to export individual attempts via the Moodle privacy API.


## Version 0.6.2 (2023110900)

- Honor Moodle proxy configuration and other network settings during requests
- Preparation for listing within the Moodle plugin directory


## Version 0.6.1 (2023110800)

- Support for quiz archive signing using the [Time-Stamp Protocol (TSP)](https://en.wikipedia.org/wiki/Time_stamp_protocol) according to [RFC 3161](https://www.ietf.org/rfc/rfc3161.txt).
- Minor UI improvements
- Documentation improvements


## Version 0.6.0 (2023092101)

- **BREAKING**: This version requires Moodle >= 4.2
- Adapt to Moodle 4.2 API changes. See [Moodle 4.2 Developer Update](https://docs.moodle.org/dev/Moodle_4.2_release_notes#External_API_changes) for details.


## Version 0.5.3 (2023092100)

- **WARNING**: This is the last version that supports Moodle 4.0 and 4.1. Future releases will require Moodle >= 4.2 due to External API changes.
- Mark plugin as incompatible with Moodle >= 4.2 and explicitly specify compatible version range


## Version 0.5.2 (2023091400)

- Fix saving of archive settings for options that should be disabled because they depend on other report sections


## Version 0.5.1 (2023082300)

- Fix hard coded database table prefix error in job metadata access function


## Version 0.5.0 (2023082200)

- **BREAKING:** API version updated to 3. Requires `moodle-quiz-archive-worker` >= v1.1.0
- Add support for paper size selection to work together with new PDF export method
- Provide XML definition for quiz archive user role
- Create highly detailed configuration instructions


## Version 0.4.4 (2023081400)

- Create modal to display all information about an archive job
- Display archive size in archive download button title
- Store selected archive settings during job creation
- Display archive settings in job details modal
- Cleanup: Moved HTML rendering from `report.php` to mustache template
- Update documentation
