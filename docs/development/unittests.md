# PHPUnit tests

!!! warning
    This page is primarily targeted at developers. If you are a normal user of
    this plugin, you can safely skip this section.


## Creating a PHPUnit Test Environment

1. Spawn a shell inside your Moodle / php-fpm container and navigate to your
   Moodle root directory:
   ```text
   docker exec -it my-moodle-container sh
   cd /usr/share/nginx/www/moodle/
   ```
2. Prepare the Moodle PHPUnit configuration. Add the following lines to your
   `config.php`:
   ```php title="config.php"
   <?php
   $CFG->phpunit_prefix = 'phpu_';
   $CFG->phpunit_dataroot = '/path/to/your/phpunit_moodledata';
   ```
3. Download [composer](https://getcomposer.org/) and install dev dependencies:
   ```text
   wget https://getcomposer.org/download/latest-stable/composer.phar
   php composer.phar install
   ```
4. Bootstrap the test environment:
   ```text
   php admin/tool/phpunit/cli/init.php --disable-composer
   ```

See: [https://moodledev.io/general/development/tools/phpunit](https://moodledev.io/general/development/tools/phpunit)


## Running tests

After you have sucessfully [created a PHPUnit envirnoment](#creating-a-phpunit-test-environment),
you can run the tests using the following commands:

- Running all tests:
  ```text
  vendor/bin/phpunit --colors --testdox
  ```
- Running all tests for a single component:
  ```text
  vendor/bin/phpunit --colors --testdox --filter quiz_archiver/*
  ```
- Running a single test suite:
  ```text
  vendor/bin/phpunit --colors --testdox mod/quiz/report/archiver/tests/report_test.php
  ```
  
!!! warning
    All commands must be run from inside your Moodle root directory.


## Automatic Test Execution for All Supported Software Configurations

The configuration for automated test execution via GitHub CI can be found in
`.github/workflows/moodle-plugin-ci.yml`. It holds a matrix of all supported
software configurations and runs the tests for each of them.

See also: [Installation Requirements](/installation#requirements)
