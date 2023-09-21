# Changelog

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
