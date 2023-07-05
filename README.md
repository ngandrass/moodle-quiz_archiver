# Quiz Archiver

Archives quiz attempts as PDF and HTML files for long-term storage in an Moodle
independent fashion. Optionally Moodle backups (`.mbz`) of both the quiz and the
whole course can be included. A checksum is calculated for every file to allow
verification of file integrity.

Quiz archives are created by an external quiz archive worker service to remove
load from Moodle and to eliminate the need to install a large number of software
dependencies on the webserver.


## Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.


## Installing manually

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/quiz/report/archiver

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.


## Concept

Archive jobs are execute via an external quiz archive worker service. It uses the
Moodle webservice API to query the required data and to upload the created archive.

This plugin prepares the archive job within Moodle, provides quiz data to the
archive worker, handles data validation, and stores the created quiz archives.
A unique webservice access token is generated for every archive job. Each token
has a limited validity and is invalidated either after job completion or after a
specified timeout. This process requires a dedicated webservice user to be
created (see [Configuration](#configuration). A single job webservice token can
only be used for the specific quiz that is associated with the job to restrict
queryable data to the required minimum. 


## Configuration

To setup this plugin execute the following steps:

1. Create a designated Moodle user for the quiz archiver webservice with the
   following rights:
   - `webservice/rest:use`
   - `mod/quiz:grade`
   - `quiz/grading:viewstudentnames`
   - `quiz/grading:viewidnumber`
   - `moodle/backup:*`
2. Create a new `quiz_archiver` external service at `$CFG->wwwroot/admin/settings.php?section=externalservices`
   - Enable file download and upload for this service
3. Add all `quiz_archiver_*` webservice functions to the `quiz_archiver` external
   service.
4. Configure `quiz_archiver` plugin settings at `$CFG->wwwroot/admin/settings.php?section=quiz_archiver_settings`
   1. Set `worker_url` to the URL under which the quiz archive worker can be
      reached (e.g., `http://quiz-archive-worker:5000` or `http://127.0.0.1:5000`)
   2. Select the in step (2.) created `quiz_archiver` webservice for `webservice_id`
   3. Enter the user ID of the in step (1.) created Moodle user for `webservice_userid`
   4. (Optional) Specify a custom job timeout in minutes
   5. (Optional) Specify a custom Moodle base URL. This is required if you run
      the quiz archive worker in an internal/private network, e.g., when using
      Docker. If this setting is present, the public Moodle `wwwroot` will be
      replaced by the `internal_wwwroot` setting.
      Example: `https://your.public.moodle/` will be replaced by `http://moodle.local/`.
5. Save all settings and create your first quiz archive :)


## License

2023 Niels Gandraß <niels@gandrass.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
