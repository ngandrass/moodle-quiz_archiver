# Moodle Archiving Worker Service

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
specify the following _Archive worker URL_ {{n1}} during configuration:

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
2. Create a `docker-compose.yml` inside a `moodle-archiving-worker` folder
   with the following content:
   ```yaml title="docker-compose.yml"
   services:
     moodle-archiving-worker:
       image: ngandrass/moodle-archiving-worker:latest
       container_name: moodle-archiving-worker
       restart: always
       ports:
         - "8080:8080/tcp"
       environment:
         - MOODLE_ARCHIVER_LOG_LEVEL=INFO
   ```
3. From inside the `moodle-archiving-worker` folder, run the application:
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
      - MOODLE_ARCHIVER_LOG_LEVEL=DEBUG
    ```

### Running the application in the background

To run the application in the background, append the `-d` argument to your
command:

```text
docker compose up -d
```

### Removing the application

To remove all created containers, networks and volumes, run the following
command from inside the `moodle-archiving-worker` folder:

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
  docker run -p 8080:8080 ngandrass/moodle-archiving-worker:latest
  ```

!!! info "Changing the service port"
    You can change the host port the application is bound to by changing the
    first port number in the `-p` argument of the `docker run` command.

    ```text title="Example: Expose the service on port 4242"
    docker run -p 4242:8080 moodle-archiving-worker:latest
    ```

!!! info "Changing configuration values"
    You can change all [configuration values](#configuration) by setting the
    respective environment variables. For more details and all available
    configuration parameters see [Configuration](#configuration).

    ```text title="Example: Set the log level to DEBUG"
    docker run -e MOODLE_ARCHIVER_LOG_LEVEL=DEBUG -p 8080:8080 moodle-archiving-worker:latest
    ```


### Building the image locally

You can also build the Docker image locally by conducting the following steps:

1. Install [Docker](https://www.docker.com/)
2. Clone the Git repository: `git clone https://github.com/ngandrass/moodle-archiving-worker`
3. Switch into the repository directory: `cd moodle-archiving-worker`
4. Build the Docker image: `docker build -t moodle-archiving-worker:latest .`[^1]
5. Run a container: `docker run -p 8080:8080 moodle-archiving-worker:latest`

[^1]: The `.` at the end of the `docker build` command **must** be part of the
command. It specifies the current directory as the build context.

## Manual Installation

!!! warning
    This is the most complex way of installing the quiz archive worker service.
    Please try to use a Docker based installation if possible.

1. Install [Python](https://www.python.org/) version >= 3.11
2. Install [Poetry](https://python-poetry.org/): `pip install poetry`
3. Clone the Git repository: `git clone https://github.com/ngandrass/moodle-archiving-worker`
4. Switch into the repository directory: `cd moodle-archiving-worker`
5. Install app dependencies: `poetry install`
6. Download [Playwright](https://playwright.dev/) browser binaries: `poetry run python -m playwright install --only-shell chromium`
7. If PDF/A conversion is desired, install [Ghostscript](https://ghostscript.readthedocs.io/en/latest/Install.html). See [PDF/A Conversion](#pdfa-conversion) for more details.
8. Run the application: `poetry run python main.py`

!!! info "Changing configuration values"
    You can change configuration values by prepending the respective environment
    variables. For more details and all available configuration parameters see
    [Configuration](#configuration).

    ```text title="Example: Set the service port to 4242"
    MOODLE_ARCHIVER_SERVER_PORT=4242 poetry run python main.py
    ```


## Resource usage guidelines

The quiz archive worker is capable of processing multiple archive jobs in
parallel. For each job, a separate browser context is spawned for rendering the
quiz attempts. Therefore, the resource usage scales roughly with the number of
parallel jobs.

For reference, each archiving job uses roughly **1 CPU** and **1 GiB of RAM**
while processing. By default, up to four jobs are executed simultaneously.

!!! warning
    For complex or large quizzes, you may want to increase the amount of RAM
    provisioned beyond the default reference.

!!! info "Changing the number of parallel jobs"
    By default, the quiz archive worker will process up to 4 jobs in parallel.
    You can adjust the number of parallel jobs manually via the corresponding
    environment variable.

    Example:
    ```text
    MOODLE_ARCHIVER_PARALLEL_JOBS=2
    ```

For more details on all available configuration parameters see
[Configuration](#configuration).


## Configuration

Configuration parameters are located inside `config.py` and can be overwritten
using the following environment variables:

| Environment Variable                                            | Default         | Description                                                                                                                                                                                                                                                        |
|-----------------------------------------------------------------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `MOODLE_ARCHIVER_SERVER_HOST`                                     | `0.0.0.0`       | Host to bind to                                                                                                                                                                                                                                                    |
| `MOODLE_ARCHIVER_SERVER_PORT`                                     | `8080`          | Port to bind to                                                                                                                                                                                                                                                    |
| `MOODLE_ARCHIVER_LOG_LEVEL`                                       | `INFO`          | Logging level. One of the following: <br> `'CRITICAL'`, `'FATAL'`, `'ERROR'`, `'WARN'`, `'WARNING'`, `'INFO'`, `'DEBUG'`                                                                                                                                           |
| `MOODLE_ARCHIVER_QUEUE_SIZE`                                      | `8`             | Maximum number of jobs to enqueue                                                                                                                                                                                                                                  |
| `MOODLE_ARCHIVER_PARALLEL_JOBS`                                   | `4`             | Number of worker threads to process archive jobs in parallel. Value has to be greater than 0                                                                                                                                                                       |
| `MOODLE_ARCHIVER_HISTORY_SIZE`                                    | `128`           | Maximum number of jobs to remember in job history                                                                                                                                                                                                                  |
| `MOODLE_ARCHIVER_ZIP_COMPRESSION_ALGO`                            | `DEFLATED`      | Compression algorithm to use for ZIP archives. Use `DEFLATED` for compatibility with Windows and MacOS.<br>Possible values are: `STORED` (no compression), `DEFLATED` (light compression, default), `BZIP2` (medium compression), and `LZMA` (strong compression). |
| `MOODLE_ARCHIVER_STATUS_REPORTING_INTERVAL_SEC`                   | `15`            | Number of seconds to wait between job progress updates                                                                                                                                                                                                             |
| `MOODLE_ARCHIVER_REQUEST_TIMEOUT_SEC`                             | `3600`          | Maximum number of seconds a single job is allowed to run before it is terminated                                                                                                                                                                                   |
| `MOODLE_ARCHIVER_BACKUP_STATUS_RETRY_SEC`                         | `30`            | Number of seconds to wait between backup status queries                                                                                                                                                                                                            |
| `MOODLE_ARCHIVER_DOWNLOAD_MAX_FILESIZE_BYTES`                     | `(1024 * 10e6)` | Maximum number of bytes a generic Moodle file is allowed to have for downloading                                                                                                                                                                                   |
| `MOODLE_ARCHIVER_BACKUP_DOWNLOAD_MAX_FILESIZE_BYTES`              | `(512 * 10e6)`  | Maximum number of bytes Moodle backups are allowed to have                                                                                                                                                                                                         |
| `MOODLE_ARCHIVER_QUESTION_ATTACHMENT_DOWNLOAD_MAX_FILESIZE_BYTES` | `(128 * 10e6)`  | Maximum number of bytes a question attachment is allowed to have for downloading                                                                                                                                                                                   |
| `MOODLE_ARCHIVER_REPORT_BASE_VIEWPORT_WIDTH`                      | `1240`          | Width of the viewport on attempt rendering in px                                                                                                                                                                                                                   |
| `MOODLE_ARCHIVER_REPORT_PAGE_MARGIN`                              | `'5mm'`         | Margin (top, bottom, left, right) of the report PDF pages including unit (mm, cm, in, px)                                                                                                                                                                          |
| `MOODLE_ARCHIVER_WAIT_FOR_READY_SIGNAL`                           | `True`          | Whether to wait for the ready signal from the report page JS before generating the export                                                                                                                                                                          |
| `MOODLE_ARCHIVER_WAIT_FOR_READY_SIGNAL_TIMEOUT_SEC`               | `30`            | Number of seconds to wait for the ready signal from the report page JS before generating the export                                                                                                                                                                |
| `MOODLE_ARCHIVER_CONTINUE_AFTER_READY_SIGNAL_TIMEOUT`             | `False`         | Whether to continue with the export if the ready signal was not received in time                                                                                                                                                                                   |
| `MOODLE_ARCHIVER_WAIT_FOR_NAVIGATION_TIMEOUT_SEC`                 | `30`            | Number of seconds to wait for the report page to load before aborting the job                                                                                                                                                                                      |
| `MOODLE_ARCHIVER_PREVENT_REDIRECT_TO_LOGIN`                       | `True`          | Whether to supress all redirects to Moodle login pages (`/login/*.php`) after page load                                                                                                                                                                            |
| `MOODLE_ARCHIVER_DEMO_MODE`                                       | `False`         | Whether the app is running in demo mode. In demo mode, a watermark will be added to all generated PDFs, only a limited number of attempts will be exported per archive job, and only placeholder Moodle backups are included                                       |
| `MOODLE_ARCHIVER_PROXY_SERVER_URL`                                | `None`          | URL of the proxy server to use for all playwright requests. HTTP and SOCKS proxies are supported. If not set, auto-detection will be performed. If set to false, no proxy will be used                                                                             |
| `MOODLE_ARCHIVER_PROXY_BYPASS_DOMAINS`                            | `None`          | Comma-separated list of domains that should always be accessed directly, bypassing the proxy                                                                                                                                                                       |
| `MOODLE_ARCHIVER_SKIP_HTTPS_CERT_VALIDATION`                      | `False`         | Whether to skip validation of TLS / SSL certs for all HTTPS connections                                                                                                                                                                                            |
| `MOODLE_ARCHIVER_PDFA_CONVERSION`                                 | `True`          | Whether to convert exported attempt PDF files into a PDF/A compliant format                                                                                                                                                                                        |
| `MOODLE_ARCHIVER_PDFA_CONVERSION_TIMEOUT_SEC`                     | `30`            | Number of seconds to wait before conversion process is aborted                                                                                                                                                                                                     |
| `MOODLE_ARCHIVER_PDFA_CONVERSION_GHOSTSCRIPT_BINARY_PATH`         | `None`          | Path to the ghostscript binary that should be used for PDF/A conversion. If left unset, this will be detected automatically.                                                                                                                                       |

### Archive Compression

You can choose between different compression algorithms for the generated
archive files. By default, [DEFLATE](https://en.wikipedia.org/wiki/Deflate) is
used to allow for maximum compatibility with Windows, MacOS, and Linux. The
downside of the high compatibility is, that the compression ratio is rather low,
resulting in larger archive files.

If you need stronger compression, you can choose between different compression
algorithms by setting the `MOODLE_ARCHIVER_ZIP_COMPRESSION_ALGO` environment
variable to one of the following values:

- `STORED`: No compression, just store the files in the archive
- `DEFLATED`: Light compression, compatible with everything
- `BZIP2`: Medium compression, might require additional software like [7-Zip](https://www.7-zip.org/)
- `LZMA`: Strong compression, might require additional software like [7-Zip](https://www.7-zip.org/)

!!! tip
    Please note that changing this setting will **only affect newly created
    archives**. Existing archives will remain unchanged.

### PDF/A Conversion

The quiz archive worker can produce [PDF/A-3b compliant PDF files](https://en.wikipedia.org/wiki/PDF/A).
PDF/A is an ISO-standardized version of the PDF format that is designed for
long-term archiving and preservation of electronic documents. It ensures that
the PDF files can be displayed exactly the same way in the future, regardless of
the software used to create or view them.

For converting attempt PDF files into a PDF/A-3b compliant format, the external
dependency [Ghostscript](https://ghostscript.com) is required. If you are using
the official Docker image, Ghostscript is already included and configured
properly. If you are installing the worker service manually, please refer to the
[Manual Installation](#manual-installation) section above.


!!! info "Switching between PDF and PDF/A format"
    PDF/A conversion is enabled by default, but can be disabled by setting:
    ```text
    MOODLE_ARCHIVER_PDFA_CONVERSION = False
    ```

!!! info "Using a specific Ghostscript installation"
    The location of your Ghostscript binary is automatically detected on startup.
    If automatic detection fails or you want to use a specific Ghostscript
    distribution, you can set the path to your Ghostscript binary manually via the
    corresponding environment variable.
    ```text
    MOODLE_ARCHIVER_PDFA_CONVERSION_GHOSTSCRIPT_BINARY_PATH=/bin/gs
    ```

### Proxy Servers

Should your archive worker be required to access your Moodle instance and other
resources through a proxy server, both [HTTP and SOCKS proxies](https://en.wikipedia.org/wiki/Proxy_server#Implementations_of_proxies)
are supported. You have multiple options to configure the proxy settings as
described below.

#### Using proxy server auto-detection

If no further configuration is performed, the archive worker will automatically
try to detect and use your default system proxy.

During auto-detection, the archive worker looks inside the following environment
variables for proxy configuration data (first match takes precedence):

- `http_proxy`, `HTTP_PROXY`, `https_proxy`, `HTTPS_PROXY`, `all_proxy`,
`ALL_PROXY`

In each case, a full proxy URL including procotol, port and eventually
credentials for authenticating at the proxy server is expected. Examples:

- `http://proxy.example.com:3128`
- `http://10.0.0.2:3128`
- `socks5://foo.bar:1080`
- `http://user:password@myproxy:3128`


!!! info "Setting the environment variables for proxy servers using Docker"
    If you are deploying the archive worker service using Docker, you can set
    the environment variables for the proxy server either for the archive worker
    service specifically inside your `docker-compose.yml` file or [globally
    inside your Docker client](https://docs.docker.com/engine/cli/proxy/)[^2].

    ```yaml title="Example: Setting the proxy server URL for the archive worker service"
    environment:
      - HTTP_PROXY=http://proxy.example.com:3128
    ```

[^2]: You can find details on how to set a global proxy for your Docker client
in the [official Docker documentation](https://docs.docker.com/engine/cli/proxy/).
Be aware, that you the configuration **only applies to new containers** and
builds, and doesn't affect existing containers. Therefore, you need to re-create
all pre-existing containers in order to apply the new proxy settings.

#### Using a different proxy server

You can bypass automatic proxy detection, hereby not using your systems default
proxy, by setting the `MOODLE_ARCHIVER_PROXY_SERVER_URL` environment variable to
the URL of your desired proxy server.

#### Disabling proxy server and auto-detection

To disable automatic proxy detection and use no proxy at all, set the
`MOODLE_ARCHIVER_PROXY_SERVER_URL` environment variable to `False`.

#### Bypassing the proxy for certain domains

The archive worker is also able to bypass the proxy server for a given list
of comma-separated domains. During automatic proxy detection, the environment
variables `no_proxy` and `NO_PROXY` are scanned and listed domains will bypass
the proxy.

If manual proxy configuration is used, you can instead set the
`MOODLE_ARCHIVER_PROXY_BYPASS_DOMAINS` environment variable to a comma-separated
list of domains that will always bypass the proxy server.


## Next Steps

After installing both the Moodle plugin and the archive worker service, you
need to perform the initial [configuration](/configuration) once, to make the
plugin work.

[:material-cog: Configuration](/configuration){ .md-button }
