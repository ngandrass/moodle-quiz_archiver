# Creating Quiz Archives

Once the Moodle plugin and the archive worker service are [installed](/installation)
and [set up](/configuration), quizzes can be archived by performing the following steps:

1. Navigate to a Moodle quiz
2. Select the _Results_ tab (1), open the dropdown menu (2), and select _Quiz Archiver_        
3. Set your desired archiving options (3) and initiate a new archive job by
   clicking the _Archive quiz_ button (4)
4. Confirm that your archive job was created (5) and wait for it to finish. You
   can check its current status using the refresh button (6)
5. Once the job is completed, you can download the archive by clicking the
   _Download archive_ button (7)

![Screenshot: Creating a new quiz archive 1](/assets/configuration/configuration_quiz_archive_creation_1.png){ .img-thumbnail }
![Screenshot: Creating a new quiz archive 2](/assets/configuration/configuration_quiz_archive_creation_2.png){ .img-thumbnail }
![Screenshot: Creating a new quiz archive 3](/assets/configuration/configuration_quiz_archive_creation_3.png){ .img-thumbnail }
![Screenshot: Creating a new quiz archive 4](/assets/configuration/configuration_quiz_archive_creation_4.png){ .img-thumbnail }


## Inspecting Quiz Archive Details

To inspect the details of a quiz archive job, click the _Show details_ button (1).
This will open a modal dialog showing all relevant information.

![Screenshot: Inspecting a quiz archive 1](/assets/configuration/configuration_quiz_archive_inspection_1.png){ .img-thumbnail }<br>
![Screenshot: Inspecting a quiz archive 2](/assets/configuration/configuration_quiz_archive_inspection_2.png){ .img-thumbnail }


## Downloading Quiz Archives

Once a quiz archive job is finished, the archive can be downloaded by clicking
the _Download archive_ button (1) located inside the job overview table or within
each archive jobs details dialog.

![Screenshot: Downloading a quiz archive](/assets/configuration/configuration_quiz_archive_download_1.png){ .img-thumbnail }


## Deleting Quiz Archives

!!! danger
    This action is irreversible and will permanently delete the archive and all
    associated data. Make sure to download the archive before deleting it.

Created archives can be deleted by clicking the _Delete archive_ (1) button
within the job overview table.

![Screenshot: Deleting a quiz archive](/assets/configuration/configuration_quiz_archive_delete_1.png){ .img-thumbnail }

