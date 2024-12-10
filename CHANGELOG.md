# Changelog

## Version X.X.X (YYYYMMDDXX)

- Add hint about font rendering problems to the documentation


## Version 2.2.0 (2024102900)

- Add student ID number to quiz attempt header
- Add student ID number to exported `attempts_metadata.csv` file inside quiz archives
- Allow student ID number to be used in attempt filename pattern as `${idnumber}`
- Fix creation of quiz archives with duplicate archive names (e.g., when using `quiz-archive-${quizid}-${quizname}` as the archive name pattern)
- Improve display of user firstname, lastname, and avatar in quiz attempt header
- Improve display of empty values in quiz attempt header (e.g., feedback, idnumber, ...)
- Fix name of `QUIZ_ARCHIVER_PREVENT_REDIRECT_TO_LOGIN` environment variable in archive worker documentation
- Fix single unit test suit execution command in developer documentation
- Improve content spacing in docs
- Only run Moodle CI for commits and PRs on master and develop branches to prevent duplicate runs


## Version 2.1.0 (2024101000)

- Ensure compatibility with Moodle 4.5 (LTS)
- Create an official Quiz Archiver documentation website: [https://quizarchiver.gandrass.de/](https://quizarchiver.gandrass.de/)
    - Great thanks to @melanietreitinger for reviewing and providing valuable feedback!
- Automate building and deployment of documentation website
- Cleanup and restructure existing documentation within README
- Add demo quiz archive worker information to admin settings page
- Fix job details dialog not showing up if artifact file was deleted but metadata still remains
- Fix PHP warning on autoinstall admin page
- Add Moodle 4.5 to automated (CI) test matrix


## Version 2.0.0 (2024082100)

- Switch to semantic versioning (see README.md, Section: "Versioning and Compatibility")
- Fix rendering of GeoGebra applets under certain conditions
- Improve robustness of attempt page rendering state detection ("ready for export" detection)
- Improve status and error notifications for all actions (job creation, deletion, ...)
- Prevent form data resubmission on page reload
- Add tooltip to archive overview refresh button and list time of last page refresh
- Improve visual presentation of the quiz archive overview table
- Improve visual presentation of the quiz archive creation form
- Add complex examples (large image compression, GeoGebra applets) to reference course

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v2.0.0` is required.


## Version 1.4.0 (2024072900)

- Show periodically updated progress of running archive jobs in job overview table and job details modal
- Creation of new job status values:
    - `WAITING_FOR_BACKUP`: All attempt reports are generated and the archive worker service is waiting for the Moodle backup to be ready.
    - `FINALIZING`: The archive worker service is finalizing the archive creation process (checksums, compression, ...).
- Create hover tooltip with help text for all job status values
- Add additional soft error handling to some web service functions
- Minor compatibility fixes for PHP 7.4 and Moodle 4.1 (LTS)
- Expanding unit test coverage to include the whole plugin logic
- Optimizing unit test code to improve readability and maintainability
- Create generic testing data generator
- Code quality improvements

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v1.6.0` is required.


## Version 1.3.0 (2024071800)

- Optionally scale down large images within quiz reports to preserve space and keep PDF files compact
- Optionally compress images within quiz reports to preserve space and keep PDF files compact
- Fix image inlining for files with non-lowercase file extensions (e.g., `image.JPG`)
- Fix conditional hide/show of retention time in quiz archive form when locked
- Optimize order of settings in quiz archive form and plugin admin settings

**Note:** Use of [moodle-quiz-archive-worker](https://github.com/ngandrass/moodle-quiz-archive-worker) `>= v1.5.0` is required.


## Version 1.2.10 (2024070900)

- Full code overhaul to comply with the [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- Enforce strict coding style checks during CI runs / prior to any new releases
- Improve English and German translations
 

## Version 1.2.9 (2024070800)

- Synchronize default job timeout setting with quiz archive worker and add hint about the additional timeout inside the 
  archive worker config
- Describe different job timeout settings inside the "Known Pitfalls" section of
  the README file.
- Fix display of variables in archive / report names help texts in Moodle <= 4.2

_Note: Keep in mind to update your
[Quiz Archive Worker](https://github.com/ngandrass/moodle-quiz-archive-worker) too!_


## Version 1.2.8 (2024052900)

- Fix autoinstall admin UI form for Moodle 4.1 (LTS)
- Fix edge case during GDPR exports via the Moodle privacy API when using PHP 7.4
- Fix webservice token generation on Moodle 4.1 (LTS)
- Largely extend the test coverage. Now almost everything is tested automatically
  for all combinations of:
    - Moodle version: 4.1 - 4.4
    - PHP versions: 7.4 - 8.3
    - Database backends: mariadb, pgsql
- Cleanup attempt report generation code
- Provide documentation how to run tests locally
- Fix typos

_Note: Keep in mind to update your
[Quiz Archive Worker](https://github.com/ngandrass/moodle-quiz-archive-worker) too!_


## Version 1.2.7 (2024051300)

- Fix inlining of images with filenames that contains URL encoded characters (e.g., `image (1).jpg`)
- Fix inlining of Moodle theme icons (e.g., drag and drop markers)
- Fix PHP warning on quiz_archiver_generate_attempt_report webservice call
- Fix quiz header / summary table injection in Moodle 4.4+
- Replace deprecated Moodle 4.4+ language strings


## Version 1.2.6 (2024042900)

- Extend automated tests to cover Moodle 4.4 with PHP 8.1 to 8.3 using PostgreSQL and MariaDB
- Removal of deprecated function use for Moodle 4.4 (See MDL-67667)


## Version 1.2.5 (2024040900)

- Add an automatic plugin configuration feature, to simplify the setup process (#15 - Thanks to @melanietreitinger)
- Display a welcome message with setup instructions during plugin installation
- Add support for automated configuration using a CLI script
- Add error message during job creation, when plugin is not fully configured yet
- Create quizzes with 100, 250, 500, and 1000 attempts in the reference course `res/backup-moodle2-course-qa-ref.mbz`
- Update installation instructions in README.md to reflect the new setup process


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
