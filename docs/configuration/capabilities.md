# Capabilities

Moodle capabilities are used to define what a user can and cannot do within the
system. The Quiz Archiver plugin uses several custom capabilities to control
access to its features.

The following capabilities are introduced by the plugin and required for the
listed actions:

| Capability                         | Context | Default assignments                    | Description                                                                                                                                                                   |
|------------------------------------|---------|----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `mod/quiz_archiver:view`           | Module  | `teacher`, `editingteacher`, `manager` | Required to view the quiz archiver overview page. It allows to download all created archives but does not allow do create new or delete existing archives (read-only access). |
| `mod/quiz_archiver:create`         | Module  | `editingteacher`, `manager`            | Allows creation of new quiz archives.                                                                                                                                         |
| `mod/quiz_archiver:delete`         | Module  | `editingteacher`, `manager`            | Allows deletion of existing quiz archives.                                                                                                                                    |
| `mod/quiz_archiver:use_webservice` | System  | *None*                                 | Required to use any of this plugins webservice functions. The webservice user[^1] needs to have this capability in order to create new quiz archives.                         |

[^1]: The webservice user is created during the [initial plugin configuration](/configuration).