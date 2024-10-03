# Archive Job Presets (Global Defaults)

Default values for all archive job settings can be configured globally on the
plugin settings page.

!!! tip
    By default, users are allowed to customize these settings during archive
    creation. However, each setting can be locked individually to prevent users from
    modifying it during archive creation. This allows the enforcement of
    organization wide policies for archived quizzes.

To customize these options:

1. Navigate to _Site Administration_ > _Plugins_ (1) > _Activity modules_ >
   _Quiz_ > _Quiz Archiver_ (2)
2. Scroll down to the _Archive presets_ section (3)
3. Set the desired default values for each option (4)
    - Options can depend on another, as indicated by (6). This causes the
      dependent option to be disabled, if the parent option is not set (e.g.,
      question feedback is not exported if question exporting is fully disabled)
    - More options than shown in the screenshots are available. Scroll down to
      see all (7)
4. (Optional) Lock individual options by checking the _Lock_ checkbox (5)

Locked options will be grayed out during archive creation (8).

![Screenshot: Configuration - Archive job presets 1](/assets/configuration/configuration_plugin_settings_1.png){ .img-thumbnail }
![Screenshot: Configuration - Archive job presets 2](/assets/configuration/configuration_archive_job_presets_2.png){ .img-thumbnail }
![Screenshot: Configuration - Archive job presets 3](/assets/configuration/configuration_archive_job_presets_3.png){ .img-thumbnail }
