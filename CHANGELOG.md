# Changelog

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
