# Automatic Deletion (GDPR Compliance)

Quiz archives can be automatically deleted after a specified retention period.
Automatic deletion can either be controlled on a per-archive basis or globally
via the [archive job presets](/configuration/policies).

Archives with expired lifetimes are deleted by an asynchronous task that is, by
default, scheduled to run every hour. Only the archived user data (attempt PDFs,
attachments, ...) is deleted, while the job metadata is kept until manually
deleted. This procedure allows to document the deletion of archive data in a
traceable manner, while the privacy relevant user data is deleted.

![Screenshot: Job details modal - Automatic deletion](/assets/screenshots/quiz_archiver_job_details_modal_autodelete.png){ .img-thumbnail }

If an archive is scheduled for automatic deletion, its remaining lifetime is
shown in the job details modal, as depict above. You can access it via the
_Show details_ button on the quiz archiver overview page. Once deleted, archives
change their status from `Finished` to `Deleted`.

!!! info 
    If you try to delete an archive that is scheduled for automatic deletion
    before its retention period expired, an extra warning message will be shown
    to prevent accidental deletions.


## Enabling Automatic Deletion for a Single Quiz Archive

To enable the scheduled deletion for a single quiz archive:

1. Navigate to the quiz archiver overview page
2. Expand the _Advanced settings_ section of the _Create new quiz archive_ form
3. Check the _Automatic deletion_ checkbox (1)
4. Set the desired retention period (2)
5. Create the archive job (3)

![Screenshot: Configuration - Automatic archive deletion](/assets/configuration/configuration_job_autodelete.png){ .img-thumbnail }


## Enabling Automatic Deletion Globally

Like any other archive settings, automatic deletion can be configured globally
using the [archive job presets](/configuration/policies) within the plugin
configuration.
