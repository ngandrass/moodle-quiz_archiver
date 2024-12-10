# Known Pitfalls

This section lists some common pitfalls that you might encounter when setting
up or using the quiz archiver plugin.


## A job timeouts prior to the configured timeout value

Be aware that there is a configurable job timeout within the Moodle plugin
settings (`quiz_archiver | job_timeout_min`) as well as one within the quiz
archive worker service (`QUIZ_ARCHIVER_REQUEST_TIMEOUT_SEC`).

!!! warning
    Since the shortest timeout always takes precedence, make sure to adjust
    **both** timeout settings as required.


## The archive worker service cannot access the Moodle instance

If the archive worker service can not access your Moodle instance, you should
check the following:

1. Ensure that the archive worker service is able to resolve the hostname of your
   Moodle instance.
    - You can test this by running `nslookup yourmoodle.tld` on the machine that
    runs the archive worker service.
    - If the lookup fails, check the DNS settings on the machine that runs the
      archive worker service.
2. Ensure that the archive worker service is able to reach the machine that runs
   your Moodle instance. 
    - You can test this by running `ping yourmoodle.tld` on the machine that
      runs the archive worker service.
    - If the ping fails, check the network and firewall settings on both
      machines.
3. Ensure that the archive worker service is able to retrieve a basic web page
   from your Moodle instance.
    - You can test this by running `curl -v https://yourmoodle.tld` on the
      machine that runs the archive worker service.
    - If the curl command fails, check the firewall settings on both machines.


## Access to Moodle webservice functions fails

If you get an error message that access to one or more webservice functions is
denied, you should check the following:

1. Ensure that your archive worker service is able to [connect to your Moodle
   instance](#the-archive-worker-service-cannot-access-the-moodle-instance).
2. Ensure that webservices and the REST protocol are enabled globally.
3. Ensure that all required webservice functions are enabled for the
  `quiz_archiver` webservice.
4. Ensure that the `quiz_archiver` webservice has the rights to download and
   upload files.
5. Ensure that the `quiz_archiver` webservice user has accepted all site
   policies (e.g., privacy policy).


## Upload of the quiz archive fails

If the archive worker is able to create the quiz archive but fails to upload it
back to your Moodle, you should check the following:

1. Ensure you have configured PHP to accept large file uploads. The
   `upload_max_filesize` and `post_max_size` settings in your `php.ini` should
   be set to a value that is large enough to allow the upload of the largest
   quiz archive file that you expect to be created. Setting it to `512MB` is a
   good starting point.
2. Ensure that your Moodle is configured to allow large file uploads.
   `$CFG->maxbytes` should be set to the same value as PHP `upload_max_filesize`.
3. If you are using an ingress webserver and PHP-FPM via FastCGI, ensure that the
   `fastcgi_send_timeout` and `fastcgi_read_timeout` settings are long enough to
   allow the upload of the largest quiz archive file that you expect.
   Nginx usually signals this problem by returning a '504 Gateway Time-out'
   after 60 seconds (default).
4. Ensure that your antivirus plugin is capable of handling large files. When
   using ClamAV you can control maximum file sizes by setting `MaxFileSize`,
   `MaxScanSize`, and `StreamMaxLength` (when using a TCP socket) inside
   `clamd.conf`.


## Text is not rendered correctly

If the text in the generated PDF files is not rendered correctly, e.g., when
only rectangles are displayed instead of characters, please make sure that an
extended set of base fonts is available on your server.

If you are running Ubuntu or Debian, you can ensure this by installing the
`fonts-noto` package via your package manager.

If you are using the official Docker image, please open a bug report instead.


## Checking the plugin config

If you are unsure whether there is a problem with your plugin configuration, you
can check the [manual configuration instructions](/configuration/initialconfig/manual)
and compare your local config against it.

[:material-wrench-cog: Manual Configuration](/configuration/initialconfig/automatic){ .md-button }
