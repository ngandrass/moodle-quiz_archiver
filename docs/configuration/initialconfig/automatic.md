# Automatic Configuration

Creation of the dedicated Moodle user and role, as well as the setup of the
webservice for the archive worker, can be done automatically.

The easiest way is to use the automatic configuration feature provided via the
Moodle admin interface but a fully automated configuration via CLI is also
supported.


## Using the Moodle Admin Interface

!!! info
    This is the recommended way to configure the Quiz Archiver Moodle plugin for
    most users.

1. Navigate to _Site Administration_ > _Plugins_ (1) > _Activity modules_ >
   _Quiz_ > _Quiz Archiver_ (2)
2. Click the _Automatic configuration_ button (3)
3. Enter the URL under which the quiz archive worker can be reached (4)
4. (Optional) Change the configuration defaults (5)
5. Execute the automatic configuration (6)
6. Close the window (7)
7. (Optional) Adjust the default plugin setting on the plugin settings page

![Screenshot: Configuration - Automatic Configuration 1](/assets/configuration/configuration_plugin_settings_1.png){ .img-thumbnail }
![Screenshot: Configuration - Automatic Configuration 1](/assets/configuration/configuration_plugin_autoinstall_2.png){ .img-thumbnail }
![Screenshot: Configuration - Automatic Configuration 1](/assets/configuration/configuration_plugin_autoinstall_3.png){ .img-thumbnail }
![Screenshot: Configuration - Automatic Configuration 1](/assets/configuration/configuration_plugin_autoinstall_4.png){ .img-thumbnail }


## Using the Command Line Interface (CLI)

!!! warning
    This method is recommended for advanced users only. If you have used the
    Moodle admin interface to configure the plugin, you can skip this step.

If you want to configure this plugin in an automated fashion, you can use the
provided CLI script. The script is located at
`{$CFG->wwwroot}/mod/quiz/report/archiver/cli/autoinstall.php`.

To execute the script:

1. Open a terminal and navigate to the quiz archiver CLI directory:
   ```text
   cd /path/to/moodle/mod/quiz/report/archiver/cli
   ```
2. Execute the CLI script using PHP:
    ```text
    php autoinstall.php --help
    ```

Usage:
```text
Automatically configures Moodle for use with the quiz archiver plugin.

ATTENTION: This CLI script ...
- Enables web services and REST protocol
- Creates a quiz archiver service role and a corresponding user
- Creates a new web service with all required webservice functions
- Authorises the user to use the webservice.

Usage:
    $ php autoinstall.php
    $ php autoinstall.php --username="my-custom-archive-user"
    $ php autoinstall.php [--help|-h]

Options:
    --help, -h          Show this help message
    --force, -f         Force the autoinstall, regardless of the current state of the system
    --workerurl=<value> Sets the URL of the worker (default: http://localhost:8080)
    --wsname=<value>    Sets a custom name for the web service (default: quiz_archiver_webservice)
    --rolename=<value>  Sets a custom name for the web service role (default: quiz_archiver)
    --username=<value>  Sets a custom username for the web service user (default: quiz_archiver_serviceaccount)
```

## Next Steps

You finished the initial configuration of the quiz archiver plugin. You now can
either directly start archiving quizzes (see [Usage](/usage)) or adjust the
default plugin settings (see [Job Presets / Policies](/configuration/presets)).

[:material-account: Usage](/usage){ .md-button }
&nbsp; &nbsp; &nbsp;
[:material-file-cog: Job Presets](/configuration/presets){ .md-button }
