# Moodle Plugin

You can install this plugin like any other Moodle plugin, as described below.
However, keep in mind that you additionally need to deploy the external [quiz
archive worker service](/installation/archiveworker) for this plugin to work.


### Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.


### Installing manually

The plugin can be also installed by putting the contents of this directory to

```text
{your/moodle/dirroot}/mod/quiz/report/archiver
```

Afterward, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

```text
php admin/cli/upgrade.php
```

to complete the installation from the command line.


## Next Steps

After installing the Moodle plugin, you need to install the additional [quiz
archive worker service](/installation/archiveworker) to make the plugin work.

[:simple-docker: Installation: Archive Worker Service](/installation/archiveworker){ .md-button }
