# Moodle Quiz Archiver

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

This plugin creates archivable versions of quiz attempts as PDF and HTML files
for long-term storage independent of Moodle. If desired, Moodle backups (`.mbz`)
of both the quiz and the whole course can be included. A checksum is calculated
for every file within the archive, as well as the archive itself, to allow
verification of file integrity. Archives can optionally be cryptographically
signed by a trusted authority using the [Time-Stamp Protocol (TSP)](https://en.wikipedia.org/wiki/Time_stamp_protocol).
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
number of software dependencies on the webserver. It can easily be deployed
using Docker.


## Getting Started

Use the following buttons to jump to the desired section:

[:material-download: Installation](/installation){ .md-button }
&nbsp; &nbsp; &nbsp;
[:material-cog: Configuration](/configuration){ .md-button }
&nbsp; &nbsp; &nbsp;
[:material-account: Usage](/usage){ .md-button }


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

<br>
[:simple-moodle: Moodle Plugin Directory](https://moodle.org/plugins/quiz_archiver){ .md-button }
&nbsp; &nbsp; &nbsp;
[:simple-github: GitHub](https://github.com/ngandrass/moodle-quiz_archiver){ .md-button }
&nbsp; &nbsp; &nbsp;
[:fontawesome-solid-bug: Bug Tracker](https://github.com/ngandrass/moodle-quiz_archiver/issues){ .md-button }
