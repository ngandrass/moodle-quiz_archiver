# Quiz Archiver

[![Latest Version](https://img.shields.io/github/v/release/ngandrass/moodle-quiz_archiver)](https://github.com/ngandrass/moodle-quiz_archiver/releases)
[![Maintenance Status](https://img.shields.io/maintenance/yes/9999)](https://github.com/ngandrass/moodle-quiz_archiver/)
[![License](https://img.shields.io/github/license/ngandrass/moodle-quiz_archiver)](https://github.com/ngandrass/moodle-quiz_archiver/blob/master/LICENSE)
[![GitHub Issues](https://img.shields.io/github/issues/ngandrass/moodle-quiz_archiver)](https://github.com/ngandrass/moodle-quiz_archiver/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/ngandrass/moodle-quiz_archiver)](https://github.com/ngandrass/moodle-quiz_archiver/pulls)
[![Donate with PayPal](https://img.shields.io/badge/PayPal-donate-orange)](https://www.paypal.me/ngandrass)
[![Sponsor with GitHub](https://img.shields.io/badge/GitHub-sponsor-orange)](https://github.com/sponsors/ngandrass)
[![GitHub Stars](https://img.shields.io/github/stars/ngandrass/moodle-quiz_archiver?style=social)](https://github.com/ngandrass/moodle-quiz_archiver/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/ngandrass/moodle-quiz_archiver?style=social)](https://github.com/ngandrass/moodle-quiz_archiver/network/members)
[![GitHub Contributors](https://img.shields.io/github/contributors/ngandrass/moodle-quiz_archiver?style=social)](https://github.com/ngandrass/moodle-quiz_archiver/graphs/contributors)

Archives quiz attempts as PDF and HTML files for long-term storage independent
of Moodle. If desired, Moodle backups (`.mbz`) of both the quiz and the whole
course can be included. A checksum is calculated for every file within the
archive, as well as the archive itself, to allow verification of file integrity.
Archives can optionally be cryptographically signed by a trusted authority using
the [Time-Stamp Protocol (TSP)](https://en.wikipedia.org/wiki/Time_stamp_protocol).
Comprehensive archive settings allow selecting what should be included in the
generated reports on a fine-granular level (e.g., exclude example solutions,
include answer history, ...).

Generated quiz attempt reports include all elements of the test, even complex
ones like [MathJax](https://www.mathjax.org/) formulas, [STACK](https://moodle.org/plugins/qtype_stack)
plots, [GeoGebra](https://www.geogebra.org/) applets, and other question /
content types that require JavaScript processing. All PDF and HTML files are
fully text-searchable, including rendered MathJax formulas. Content is saved
vector based, whenever possible, to allow high-quality printing and zooming
while keeping the file size down.

Quiz archives are created by an external [quiz archive worker](https://github.com/ngandrass/moodle-quiz-archive-worker)
service to remove load from Moodle and to eliminate the need to install a large
number of software dependencies on the webserver. It can easily be [deployed
using Docker](https://github.com/ngandrass/moodle-quiz-archive-worker#installation).

The Quiz Archiver is available via the [Moodle Plugin Directory](https://moodle.org/plugins/quiz_archiver):\
[![Moodle Plugin Directory](docs/assets/moodle-plugin-directory-button.png)](https://moodle.org/plugins/quiz_archiver)

More information and detailed installation / setup instructions can be found in
the [official documentation](https://quizarchiver.gandrass.de/):

[![Quiz Archiver: Official Documentation](docs/assets/docs-button.png)](https://quizarchiver.gandrass.de/)


## Features

- Archiving of quiz attempts as PDF and HTML files
- Support for file submissions / attachments (e.g., essay files)
- Quiz attempt reports are accessible completely independent of Moodle, hereby
  ensuring long-term readability
- Customization of generated PDF and HTML reports
  - Allows creation of reduced reports, e.g., without example solutions, for
    handing out to students during inspection
- Support for complex content and question types, including Drag and Drop, MathJax
  formulas, STACK plots, and other question / content types that require JavaScript
  processing
- Quiz attempt reports are fully text-searchable, including mathematical formulas 
- Moodle backups (`.mbz`) of both the quiz and the whole course are supported
- Generation of checksums for every file within the archive and the archive itself
- Cryptographic signing of archives and their creation date using the [Time-Stamp Protocol (TSP)](https://en.wikipedia.org/wiki/Time_stamp_protocol)
- Archive and attempt report names are fully customizable and support dynamic
  variables (e.g., course name, quiz name, username, ...)
- Fine granular permission / capability management (e.g., only allow archive
  creation but prevent deletion)
- Allows definition of global archiving defaults as well as forced archiving
  policies (i.e., locked archive job presets that cannot be changed by the user)
- Fully asynchronous archive creation to reduce load on Moodle Server
- Automatic deletion of quiz archives after a specified retention period
- Data compression and vector based MathJax formulas to preserve disk space
- Technical separation of Moodle and archive worker service
- Data-minimising and security driven design


## Concept

Archive jobs are execute via an external quiz archive worker service. It uses the
Moodle webservice API to query the required data and to upload the created archive.

This plugin prepares the archive job within Moodle, provides quiz data to the
archive worker, handles data validation, and stores the created quiz archives
inside the Moodle filestore. Created archives can be managed and downloaded via
the Moodle web interface. A unique webservice access token is generated for every
archive job. Each token has a limited validity and is invalidated either after
job completion or after a specified timeout. This process requires a dedicated
webservice user to be created (see [Configuration](#configuration)). A single job
webservice token can only be used for the specific quiz that is associated with
the job to restrict queryable data to the required minimum.


## Installation and Configuration

You can find detailed installation and configuration instructions within the
[official documentation](https://quizarchiver.gandrass.de/).

[![Quiz Archiver: Official Documentation](docs/assets/docs-button.png)](https://quizarchiver.gandrass.de/)

It guides you through the whole setup process from installing the Moodle plugin
to creating your first quiz archives. It also explains how to use advanced
features like image compression, automatic deletion of archives, and automated
cryptographic signing of quiz archives.

If you have problems installing the Quiz Archiver or have further questions,
please feel free to open an issue within the
[GitHub issue tracker](https://github.com/ngandrass/moodle-quiz_archiver/issues).


## Versioning and Compatibility

The [quiz_archiver Moodle Plugin](https://github.com/ngandrass/moodle-quiz_archiver)
and its corresponding [Quiz Archive Worker](https://github.com/ngandrass/moodle-quiz-archive-worker)
both use [Semantic Versioning 2.0.0](https://semver.org/).

This means that their version numbers are structured as `MAJOR.MINOR.PATCH`. The
Moodle plugin and the archive worker service are compatible as long as they use
the same `MAJOR` version number. Minor and patch versions can differ between the
two components without breaking compatibility.

However, it is **recommended to always use the latest version** of both the
Moodle plugin and the archive worker service to ensure you get all the latest
bug fixes, features, and optimizations.


### Compatibility Examples

| Moodle Plugin | Archive Worker | Compatible |
|---------------|----------------|------------|
| 1.0.0         | 1.0.0          | Yes        |
| 1.2.3         | 1.0.0          | Yes        |
| 1.0.0         | 1.1.2          | Yes        |
| 2.1.4         | 2.0.1          | Yes        |
|               |                |            |
| 2.0.0         | 1.0.0          | No         |
| 1.0.0         | 2.0.0          | No         |
| 2.4.2         | 1.4.2          | No         |


### Development / Testing Versions

Special development versions, used for testing, can be created but will never be
published to the Moodle plugin directory. Such development versions are marked
by a `+dev-[TIMESTAMP]` suffix, e.g., `2.4.2+dev-2022010100`.


## Screenshots

### Quiz Archiver overview page
![Image of quiz archiver overview page](docs/assets/screenshots/quiz_archiver_overview_page.png)

### New job queued while another job is running
![Image of new job queued while another job is running](docs/assets/screenshots/quiz_archiver_new_job_queued.png)

### Quiz archive job details
![Image of quiz archive job details](docs/assets/screenshots/quiz_archiver_job_details_modal.png)

### Example of PDF report (excerpts)
![Image of example of PDF report (extract): Header](docs/assets/screenshots/quiz_archiver_report_example_pdf_header.png)
![Image of example of PDF report (extract): Question 1](docs/assets/screenshots/quiz_archiver_report_example_pdf_question_1.png)
![Image of example of PDF report (extract): Question 2](docs/assets/screenshots/quiz_archiver_report_example_pdf_question_2.png)
![Image of example of PDF report (extract): Question 3](docs/assets/screenshots/quiz_archiver_report_example_pdf_question_3.png)


## License

2025 Niels Gandraß <niels@gandrass.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
