# Manual Configuration

This plugin requires the creation of a dedicated Moodle user and role, as well
as the setup of the Moodle webservices for the archive worker.

!!! warning
    This is the manual configuration process which can be quite involved. Please
    only use the manual configuration if you consider yourself an advanced user
    / Moodle administrator.

    [:material-cog-play: Automatic Configuration](/configuration/initialconfig/automatic){ .md-button }
    &nbsp;&nbsp;&nbsp;
    Most users want to use the
    [automated configuration](/configuration/initialconfig/automatic) instead.

## Create Moodle User and Role

At first, a new Moodle user and a global role need to be created for the Quiz
Archiver. It will be used by the archive worker service to access quiz data.

### Create a designated Moodle user for the quiz archiver webservice
    
1. Navigate to _Site Administration_ > _Users_ (1) > _Accounts_ > _Add a new user_ (2)
2. Set a username (e.g. `quiz_archiver`) (3), a password (4), first and
       lastname (5), and a hidden email address (6)
3. Create the user (7)

![Screenshot: Configuration - Create Moodle User 1](/assets/configuration/configuration_create_moodle_user_1.png){ .img-thumbnail }
![Screenshot: Configuration - Create Moodle User 2](/assets/configuration/configuration_create_moodle_user_2.png){ .img-thumbnail }

### Create a global role to handle permissions for the `quiz_archiver` Moodle user

1. Navigate to _Site Administration_ > _Users_ (1) > _Permissions_ > _Define roles_ (2)
2. Select _Add a new role_ (3)
3. Set _Use role or archetype_ (4) to `No role`
4. Upload the role definitions file from `res/moodle_role_quiz_archiver.xml` (5).
   This will automatically assign all required capabilities[^1].
5. Click on _Continue_ (6) to import the role definitions for review
6. Optionally change the role name or description and create the role (7)

![Screenshot: Configuration - Create Role 1](/assets/configuration/configuration_create_role_1.png){ .img-thumbnail }
![Screenshot: Configuration - Create Role 2](/assets/configuration/configuration_create_role_2.png){ .img-thumbnail }
![Screenshot: Configuration - Create Role 3](/assets/configuration/configuration_create_role_3.png){ .img-thumbnail }
![Screenshot: Configuration - Create Role 4](/assets/configuration/configuration_create_role_4.png){ .img-thumbnail }

[^1]: You can check all capabilities prior to role creation in the next step or
by manually inspecting the role definition XML file
(`res/moodle_role_quiz_archiver.xml`).

### Assign the `quiz_archiver` Moodle user to the created role

1. Navigate to _Site Administration_ > _Users_ (1) > _Permissions_ > _Assign system roles_ (2)
2. Select the `Quiz Archiver Service Account` role (3)
3. Search the created `quiz_archiver` Moodle user (4), select it in the list
   of potential users (5), and add it to the role (6)

![Screenshot: Configuration - Assign Role 1](/assets/configuration/configuration_assign_role_1.png){ .img-thumbnail }
![Screenshot: Configuration - Assign Role 2](/assets/configuration/configuration_assign_role_2.png){ .img-thumbnail }
![Screenshot: Configuration - Assign Role 3](/assets/configuration/configuration_assign_role_3.png){ .img-thumbnail }


## Setup Webservice

The quiz archive worker service interacts with the Moodle platform using the
Moodle webservice API. Therefore, it must be enabled and a corresponding
external service must be created.

### Enable webservices globally

1. Navigate to _Site Administration_ > _Server_ (1) > _Web services_ > _Overview_ (2)
2. Click on _Enable web services_ (3), check the checkbox (4), and save the
   changes (5)
3. Navigate back to the _Overview_ (2) page
4. Click on _Enable protocols_ (6), enable the _REST protocol_ (7), and save the
   changes (8)

![Screenshot: Configuration - Enable Webservices 1](/assets/configuration/configuration_enable_webservices_1.png){ .img-thumbnail }
![Screenshot: Configuration - Enable Webservices 2](/assets/configuration/configuration_enable_webservices_2.png){ .img-thumbnail }
![Screenshot: Configuration - Enable Webservices 3](/assets/configuration/configuration_enable_webservices_3.png){ .img-thumbnail }
![Screenshot: Configuration - Enable Webservices 4](/assets/configuration/configuration_enable_webservices_4.png){ .img-thumbnail }

## Create an external webservice for the quiz archive worker to use

1. Navigate to _Site Administration_ > _Server_ (1) > _Web services_ > _External services_ (2)
2. Under the _Custom services_ section, select _Add_ (3)
3. Enter a name (e.g. `quiz_archiver`) (4) and enable it (5)
4. Expand the additional settings (6), enable file up- and download (7)
5. Create the new webservice by clicking _Add service_ (8)

![Screenshot: Configuration - Create Webservice 1](/assets/configuration/configuration_create_webservice_1.png){ .img-thumbnail }
![Screenshot: Configuration - Create Webservice 2](/assets/configuration/configuration_create_webservice_2.png){ .img-thumbnail }
![Screenshot: Configuration - Create Webservice 3](/assets/configuration/configuration_create_webservice_3.png){ .img-thumbnail }

### Add all `quiz_archiver_*` webservice functions to the `quiz_archiver` external service

1. Navigate to _Site Administration_ > _Server_ (1) > _Web services_ > _External services_ (2)
2. Open the _Functions_ page for the `quiz_archiver` webservice (3)
3. Click the _Add functions_ link (4)
4. Search for `quiz_archiver` (5) and add all `quiz_archiver_*` functions
5. Save the changes by clicking _Add functions_ (6)

![Screenshot: Configuration - Assign Webservice Functions 1](/assets/configuration/configuration_assign_webservice_functions_1.png){ .img-thumbnail }
![Screenshot: Configuration - Assign Webservice Functions 2](/assets/configuration/configuration_assign_webservice_functions_2.png){ .img-thumbnail }
![Screenshot: Configuration - Assign Webservice Functions 3](/assets/configuration/configuration_assign_webservice_functions_3.png){ .img-thumbnail }
![Screenshot: Configuration - Assign Webservice Functions 4](/assets/configuration/configuration_assign_webservice_functions_4.png){ .img-thumbnail }


## Configure Plugin Settings

Once the user, role, and webservice are created, the last step is to configure
the quiz archiver plugin to use the created webservice and user.

1. Navigate to _Site Administration_ > _Plugins_ (1) > _Activity modules_ >
   _Quiz_ > _Quiz Archiver_ (2)
2. Set `worker_url` (3) to the URL under which the quiz archive worker can be
   reached (e.g., `http://quiz-archive-worker:5000` or `http://127.0.0.1:5000`)
3. Select the previously created `quiz_archiver` webservice for `webservice_id` (4)
   from the drop-down menu
4. Enter the user ID of the previously created Moodle user for `webservice_userid` (5).
   It can easily be found by navigating to the users profile page and inspecting
   the page URL. It contains the user ID as the `id` query parameter.
5. (Optional) Specify a custom job timeout in minutes
6. (Optional) Specify a custom Moodle base URL[^2].
7. Save all settings and create your first quiz archive (see [Usage](#usage)).
8. (Optional) Adjust the default [capability](#capabilities) assignments.

![Screenshot: Configuration - Plugin Settings 1](/assets/configuration/configuration_plugin_settings_1.png){ .img-thumbnail }
![Screenshot: Configuration - Plugin Settings 2](/assets/configuration/configuration_plugin_settings_2.png){ .img-thumbnail }

[^2]: This is only required if you run the quiz archive worker in an internal / 
private network, e.g., when using Docker. If this setting is present, the public
Moodle `$CFG->wwwroot` will be replaced by the `internal_wwwroot` setting.
Example: `https://your.public.moodle/` will be replaced by `http://moodle.local/`.


## Next Steps

You finished the initial configuration of the quiz archiver plugin. You now can
either directly start archiving quizzes (see [Usage](/usage)) or adjust the
default plugin settings (see [Job Presets / Policies](/configuration/presets)).

[:material-account: Usage](/usage){ .md-button }
&nbsp; &nbsp; &nbsp;
[:material-file-cog: Job Presets](/configuration/presets){ .md-button }
