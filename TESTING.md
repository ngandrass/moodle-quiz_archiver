# Testing

**ATTENTION:** The following notes are meant **for developers only**. If you only want
to use this plugin you can fully ignore this file.


## Creating a PHPUnit test environment

See: https://moodledev.io/general/development/tools/phpunit

1. Spawn a shell inside your Moodle/php-fpm container and navigate to your
   Moodle root directory:
   ```bash
   docker exec -it my-moodle-container sh
   cd /usr/share/nginx/www/moodle/
   ```
2. Prepare PHPUnit configuration. Add the following lines to your `config.php`:
   ```php
   $CFG->phpunit_prefix = 'phpu_';
   $CFG->phpunit_dataroot = '/path/to/your/phpunit_moodledata';
   ```
3. Download composer and install dev dependencies:
   ```bash
   wget https://getcomposer.org/download/latest-stable/composer.phar
   php composer.phar install
   ```
4. Bootstrap test environment:
   ```bash
   php admin/tool/phpunit/cli/init.php
   ```


## Running tests

- Run all tests:
  ```bash
  vendor/bin/phpunit --colors --testdox
  ```
- Run all tests for a single component:
    ```bash
    vendor/bin/phpunit --colors --testdox --filter quiz_archiver
    ```
- Run a single test suite:
    ```bash
    vendor/bin/phpunit --colors --testdox mod/quiz/report/archiver/tests/classes/Report_test.php
    ```

### Automatic test execution for all supported software configurations

The configuration for automated test execution via GitHub CI can be found in
`.github/workflows/moodle-plugin-ci.yml`. It holds a matrix of all supported
software configurations and runs the tests for each of them.


## Generating code coverage reports

**You need to have xdebug installed and enabled in order to generate coverage
reports!**

1. Run PHPUnit with coverage report:
   ```bash
   XDEBUG_MODE=coverage vendor/bin/phpunit --colors --testdox --coverage-html /tmp/coverage --filter quiz_archiver
   ```
2. Copy the generated report to your machin:
    ```bash
    docker cp my-moodle-container:/tmp/coverage /tmp/coverage
    ```
3. Open the report in your browser:
   ```bash
   xdg-open /tmp/coverage/index.html
   ```
