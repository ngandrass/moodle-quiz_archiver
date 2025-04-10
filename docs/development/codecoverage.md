# Code Coverage

!!! warning
    This page is primarily targeted at developers. If you are a normal user of
    this plugin, you can safely skip this section.

## Prerequisites

To generate code coverage reports, you need to have:

1. your [PHPUnit test environment](/development/unittests) set up.
2. the `xdebug` extension installed and enabled in your PHP environment.


## Generating Coverage Reports

To generate code coverage reports, follow these steps:

1. Run PHPUnit with coverage report:
   ```text
   XDEBUG_MODE=coverage vendor/bin/phpunit --colors --testdox --coverage-html /tmp/coverage --filter quiz_archiver/*
   ```
2. Copy the generated report to your host system:
    ```text
    docker cp my-moodle-container:/tmp/coverage /tmp/coverage
    ```
3. Open the report in your browser:
   ```text
   xdg-open /tmp/coverage/index.html
   ```

!!! note
    It can be required to purge your local `/tmp/covarage` directory between consecutive runs. If you find changes not
    being reflected correctly in the report, try to delete the `/tmp/coverage` directory and re-run the copy command:
    ```text
    rm -rf /tmp/coverage; docker cp my-moodle-container:/tmp/coverage /tmp/coverage
    ```