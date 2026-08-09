# Archive Structure

Quiz archives can be structured in different ways with customizable file names
and locations. The archive structure can be controlled for each archive during
archive creation or globally using
[archive job presets](/configuration/presets).

## Artifact naming

The _Advanced settings_ section contains three options for naming the files
inside an archive and the archive itself:

- _Archive name_ controls the name of the final archive file.
- _Attempt folder name_ controls the folder in which each attempt's reports are
stored when using the hierarchical file structure.
- _Attempt name_ controls the names of the PDF reports generated for individual
quiz attempts. If HTML source files are kept, they use the same base name.

Patterns may contain plain text and variables. Variables must use the
`${variablename}` syntax. The file extension is added automatically to the
_Archive name_ and _Attempt name_; do not add an extension yourself.

!!! warning
      _Attempt folder name_ has no effect when the _Flatten export archive_
      option is enabled because all files are placed in the root of the
      resulting archive. See [File structure](#file-structure) for details.

### Available variables

The following table lists all variables available to the naming options. A
variable is only expanded when it is supported by the selected option.

| Variable             | Description                        | Archive name | Attempt folder name | Attempt name |
| -------------------- | ---------------------------------- | :----------: | :-----------------: | :----------: |
| `${courseid}`        | Course ID                          |     Yes      |         Yes         |     Yes      |
| `${coursename}`      | Course name                        |     Yes      |         Yes         |     Yes      |
| `${courseshortname}` | Course short name                  |     Yes      |         Yes         |     Yes      |
| `${cmid}`            | Course module ID                   |     Yes      |         Yes         |     Yes      |
| `${groupids}`        | IDs of the student's groups        |      No      |         Yes         |     Yes      |
| `${groupidnumbers}`  | ID numbers of the student's groups |      No      |         Yes         |     Yes      |
| `${groupnames}`      | Names of the student's groups      |      No      |         Yes         |     Yes      |
| `${quizid}`          | Quiz ID                            |     Yes      |         Yes         |     Yes      |
| `${quizname}`        | Quiz name                          |     Yes      |         Yes         |     Yes      |
| `${attemptid}`       | Attempt ID                         |      No      |         Yes         |     Yes      |
| `${username}`        | Student username                   |      No      |         Yes         |     Yes      |
| `${firstname}`       | Student first name                 |      No      |         Yes         |     Yes      |
| `${lastname}`        | Student last name                  |      No      |         Yes         |     Yes      |
| `${idnumber}`        | Student ID number                  |      No      |         Yes         |     Yes      |
| `${timestart}`       | Attempt start Unix timestamp       |      No      |         Yes         |     Yes      |
| `${timefinish}`      | Attempt finish Unix timestamp      |      No      |         Yes         |     Yes      |
| `${date}`            | Current date (`YYYY-MM-DD`)        |     Yes      |         Yes         |     Yes      |
| `${time}`            | Current time (`HH-MM-SS`)          |     Yes      |         Yes         |     Yes      |
| `${timestamp}`       | Current Unix timestamp             |     Yes      |         Yes         |     Yes      |


### Naming rules

The following characters are forbidden in generated archive and report names:
`.`, `:`, `;`, `*`, `?`, `!`, `"`, `<`, `>`, `|`, and `/`. The same characters
must not be used inside an attempt folder name. In addition, an attempt folder
name may use `/` as a directory separator to create nested directories, but it
must not start or end with `/`.

The following examples show common patterns:

| Option              | Example pattern                                     | Example result                           |
| ------------------- | --------------------------------------------------- | ---------------------------------------- |
| Archive name        | `quiz-archive-${courseshortname}-${quizid}-${date}` | `quiz-archive-MATH101-42-2026-08-07.zip` |
| Attempt folder name | `${username}/${attemptid}`                          | `jdoe/17`                                |
| Attempt name        | `attempt-${attemptid}-${username}`                  | `attempt-17-jdoe.pdf`                    |


## File structure

The _Flatten export archive_ checkbox determines how files are organized inside
the resulting archive:

- When it is **not selected**, the archive uses a hierarchical structure with
  separate directories for the different artifact types and attempt reports.
  The _Attempt folder name_ pattern determines the directory used for each
  attempt.
- When it is **selected**, the archive uses a flat structure. All files are
  placed directly in the root of the archive. Prefixes are added to file names
  to distinguish reports belonging to different attempts and to prevent name
  collisions. The _Attempt folder name_ setting is ignored.

For example, with an attempt folder pattern of `${username}/${attemptid}` and an
attempt name pattern of `attempt-${attemptid}-${username}`, a hierarchical
archive may contain entries like:

```text
/
├── attempts/
│   └── jdoe/
│       └── 17/
│           ├── attachments/
│           │   └── 0/
│           │       ├── essay.pdf
│           │       └── essay.pdf.sha256
│           ├── attempt-17-jdoe.pdf
│           └── attempt-17-jdoe.pdf.sha256
├── attempts_metadata.csv
├── attempts_metadata.csv.sha256
└── backups/
    ├── quiz_archiver-activity-backup-42-20260807-181229.mbz
    └── quiz_archiver-activity-backup-42-20260807-181229.mbz.sha256
```

The equivalent flat archive keeps the same files at its top level and uses
prefixes to retain their association with the attempt:

```text
/
├── attempt_17.attempt-17-jdoe.pdf
├── attempt_17.attempt-17-jdoe.pdf.sha256
├── attempt_17.attempt-17-jdoe.attachment.0.essay.pdf
├── attempt_17.attempt-17-jdoe.attachment.0.essay.pdf.sha256
├── attempts_metadata.csv
├── attempts_metadata.csv.sha256
├── backup.quiz_archiver-activity-backup-42-20260807-181229.mbz
└── backup.quiz_archiver-activity-backup-42-20260807-181229.mbz.sha256
```
