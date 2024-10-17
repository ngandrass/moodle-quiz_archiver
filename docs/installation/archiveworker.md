# Quiz Archive Worker Service

This section describes the installation of the quiz archive worker service,
that works in conjunction with the [quiz_archiver](https://github.com/ngandrass/moodle-quiz_archiver)
Moodle plugin. It can be installed using multiple ways, though using
[Docker Compose](#installation-using-docker-compose) is recommended.

The quiz archive worker service processes quiz archive jobs in the background.
It renders Moodle quiz attempts into PDF files, collects Moodle backups,
generates checksums, and packs the final quiz archives before it uploads it back
the Moodle instance.

## Using the Free Public Demo Service

If you want to try the Quiz Archiver without setting up your own quiz archive
service worker, you can use the free public demo worker.

!!! notice
    The public archive worker service is running in demo mode.
    This means that a _DEMO MODE_ watermark will be added to all generated PDFs
    (see screenshot below), only a limited number of attempts will be exported
    per archive job, and only placeholder Moodle backups are included.

    Setting up your own quiz archive worker service removes these limitations.
    See below for setup instructions.

!!! warning
    The public archive worker service must be able to access your Moodle
    instance via the internet to work. Local and <b>private Moodle instances
    will not work</b> with the demo worker.

To use the free public demo worker, you can skip the installation for now and
directly proceed to the [configuration section](/configuration). Make sure to
specify the following _Archive worker URL_ (1) during configuration:

```text title="Archive worker URL"
https://demoworker.quizarchiver.gandrass.de
```

![Screenshot: Automatic Configuration Archive Worker URL](/assets/configuration/configuration_plugin_autoinstall_workerurl.png){ .img-thumbnail }
![Screenshot: Demo mode watermark in attempt PDF](/assets/screenshots/quiz_archiver_demomode_watermark.png){ .img-thumbnail }

[:material-cog: Configuration](/configuration){ .md-button }


## Installation using Docker Compose

!!! success "Info"
    This is the suggested way of installing the quiz archive worker service :thumbsup:

1. Install [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/)
2. Create a `docker-compose.yml` inside a `moodle-quiz-archive-worker` folder
   with the following content:
   ```yaml title="docker-compose.yml"
   services:
     moodle-quiz-archive-worker:
       image: ngandrass/moodle-quiz-archive-worker:latest
       container_name: moodle-quiz-archive-worker
       restart: always
       ports:
         - "8080:8080/tcp"
       environment:
         - QUIZ_ARCHIVER_LOG_LEVEL=INFO
   ```
3. From inside the `moodle-quiz-archive-worker` folder, run the application:
   ```text
   docker compose up
   ```

!!! info "Changing the service port"
    You can change the port that the quiz archive worker service is exposed on
    the Docker host by replacing the first port number in the `ports` argument
    within the `docker-compose.yml` file.

    ```yaml title="Example: Expose the service on port 4242"
    ports:
      - "4242:8080/tcp"
    ```

!!! info "Changing configuration values"
    You can change all [configuration values](#configuration) by setting the
    respective environment variables inside `docker-compose.yml`. For more
    details and all available configuration parameters see [Configuration](#configuration).

    ```yaml title="Example: Set the log level to DEBUG"
    environment:
      - QUIZ_ARCHIVER_LOG_LEVEL=DEBUG
    ```

### Running the application in the background

To run the application in the background, append the `-d` argument to your
command:

```text
docker compose up -d
```

### Removing the application

To remove all created containers, networks and volumes, run the following
command from inside the `moodle-quiz-archive-worker` folder:

```text
docker compose down
```

## Installation using Docker

!!! info
    This is an alternative way of installing the quiz archive worker service
    using Docker directly.

1. Install [Docker](https://www.docker.com/)
2. Run a new container:
  ```text
  docker run -p 8080:8080 ngandrass/moodle-quiz-archive-worker:latest
  ```

!!! info "Changing the service port"
    You can change the host port the application is bound to by changing the
    first port number in the `-p` argument of the `docker run` command.

    ```text title="Example: Expose the service on port 4242"
    docker run -p 4242:8080 moodle-quiz-archive-worker:latest
    ```

!!! info "Changing configuration values"
    You can change all [configuration values](#configuration) by setting the
    respective environment variables. For more details and all available
    configuration parameters see [Configuration](#configuration).

    ```text title="Example: Set the log level to DEBUG"
    docker run -e QUIZ_ARCHIVER_LOG_LEVEL=DEBUG -p 8080:8080 moodle-quiz-archive-worker:latest
    ```


### Building the image locally

You can also build the Docker image locally by conducting the following steps:

1. Install [Docker](https://www.docker.com/)
2. Clone the Git repository: `git clone https://github.com/ngandrass/moodle-quiz-archive-worker`
3. Switch into the repository directory: `cd moodle-quiz-archive-worker`
4. Build the Docker image: `docker build -t moodle-quiz-archive-worker:latest .`[^1]
5. Run a container: `docker run -p 8080:8080 moodle-quiz-archive-worker:latest`

[^1]: The `.` at the end of the `docker build` command **must** be part of the
command. It specifies the current directory as the build context. 

## Manual Installation

!!! warning
    This is the most complex way of installing the quiz archive worker service.
    Please try to use a Docker based installation if possible.

1. Install [Python](https://www.python.org/) version >= 3.11
2. Install [Poetry](https://python-poetry.org/): `pip install poetry`
3. Clone the Git repository: `git clone https://github.com/ngandrass/moodle-quiz-archive-worker`
4. Switch into the repository directory: `cd moodle-quiz-archive-worker`
5. Install app dependencies: `poetry install`
6. Download [Playwright](https://playwright.dev/) browser binaries: `poetry run python -m playwright install chromium`
7. Run the application: `poetry run python main.py`

!!! info "Changing configuration values"
    You can change configuration values by prepending the respective environment
    variables. For more details and all available configuration parameters see
    [Configuration](#configuration).

    ```text title="Example: Set the service port to 4242"
    QUIZ_ARCHIVER_SERVER_PORT=4242 poetry run python moodle-quiz-archive-worker.py
    ```


## Configuration

Configuration parameters are located inside `config.py` and can be overwritten
using the following environment variables:

| Environment Variable                                            | Default Value   | Description                                                                                                                                                                                                                  |
|-----------------------------------------------------------------|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `QUIZ_ARCHIVER_SERVER_HOST`                                     | `0.0.0.0`       | Host to bind to                                                                                                                                                                                                              |
| `QUIZ_ARCHIVER_SERVER_PORT`                                     | `8080`          | Port to bind to                                                                                                                                                                                                              |
| `QUIZ_ARCHIVER_LOG_LEVEL`                                       | `INFO`          | Logging level. One of the following: <br> `'CRITICAL'`, `'FATAL'`, `'ERROR'`, `'WARN'`, `'WARNING'`, `'INFO'`, `'DEBUG'`                                                                                                     |
| `QUIZ_ARCHIVER_QUEUE_SIZE`                                      | `8`             | Maximum number of jobs to enqueue                                                                                                                                                                                            |
| `QUIZ_ARCHIVER_HISTORY_SIZE`                                    | `128`           | Maximum number of jobs to remember in job history                                                                                                                                                                            |
| `QUIZ_ARCHIVER_STATUS_REPORTING_INTERVAL_SEC`                   | `15`            | Number of seconds to wait between job progress updates                                                                                                                                                                       |
| `QUIZ_ARCHIVER_REQUEST_TIMEOUT_SEC`                             | `3600`          | Maximum number of seconds a single job is allowed to run before it is terminated                                                                                                                                             |
| `QUIZ_ARCHIVER_BACKUP_STATUS_RETRY_SEC`                         | `30`            | Number of seconds to wait between backup status queries                                                                                                                                                                      |
| `QUIZ_ARCHIVER_DOWNLOAD_MAX_FILESIZE_BYTES`                     | `(1024 * 10e6)` | Maximum number of bytes a generic Moodle file is allowed to have for downloading                                                                                                                                             |
| `QUIZ_ARCHIVER_BACKUP_DOWNLOAD_MAX_FILESIZE_BYTES`              | `(512 * 10e6)`  | Maximum number of bytes Moodle backups are allowed to have                                                                                                                                                                   |
| `QUIZ_ARCHIVER_QUESTION_ATTACHMENT_DOWNLOAD_MAX_FILESIZE_BYTES` | `(128 * 10e6)`  | Maximum number of bytes a question attachment is allowed to have for downloading                                                                                                                                             |
| `QUIZ_ARCHIVER_REPORT_BASE_VIEWPORT_WIDTH`                      | `1240`          | Width of the viewport on attempt rendering in px                                                                                                                                                                             |
| `QUIZ_ARCHIVER_REPORT_PAGE_MARGIN`                              | `'5mm'`         | Margin (top, bottom, left, right) of the report PDF pages including unit (mm, cm, in, px)                                                                                                                                    |
| `QUIZ_ARCHIVER_WAIT_FOR_READY_SIGNAL`                           | `True`          | Whether to wait for the ready signal from the report page JS before generating the export                                                                                                                                    |
| `QUIZ_ARCHIVER_WAIT_FOR_READY_SIGNAL_TIMEOUT_SEC`               | `30`            | Number of seconds to wait for the ready signal from the report page JS before generating the export                                                                                                                          |
| `QUIZ_ARCHIVER_CONTINUE_AFTER_READY_SIGNAL_TIMEOUT`             | `False`         | Whether to continue with the export if the ready signal was not received in time                                                                                                                                             |
| `QUIZ_ARCHIVER_WAIT_FOR_NAVIGATION_TIMEOUT_SEC`                 | `30`            | Number of seconds to wait for the report page to load before aborting the job                                                                                                                                                |
| `QUIZ_ARCHIVER_PREVENT_REDIRECT_TO_LOGIN`                       | `True`          | Whether to supress all redirects to Moodle login pages (`/login/*.php`) after page load                                                                                                                                      |
| `QUIZ_ARCHIVER_DEMO_MODE`                                       | `False`         | Whether the app is running in demo mode. In demo mode, a watermark will be added to all generated PDFs, only a limited number of attempts will be exported per archive job, and only placeholder Moodle backups are included |



## Next Steps

After installing both the Moodle plugin and the archive worker service, you
need to perform the initial [configuration](/configuration) once, to make the
plugin work.

[:material-cog: Configuration](/configuration){ .md-button }
