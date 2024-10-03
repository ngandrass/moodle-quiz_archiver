# Quiz Archive Signing (TSP)

Quiz archives and their creation date can be digitally signed by a trusted
authority using the [Time-Stamp Protocol (TSP)](https://en.wikipedia.org/wiki/Time_stamp_protocol)
according to [RFC 3161](https://www.ietf.org/rfc/rfc3161.txt). This can be used
to cryptographically prove the integrity and creation date of the archive at a
later point in time. Quiz archives can be signed automatically at creation or
manually later on.


## Enabling Archive Signing

Prior to the first archive signing, the TSP service must be set up once within 
the plugin settings. To do so, follow these steps:

1. Navigate to _Site Administration_ > _Plugins_ (1) > _Activity modules_ >
   _Quiz_ > _Quiz Archiver_ (2)
2. Set `tsp_server_url` (3) to the URL of your desired TSP service
3. Globally enable archive signing by checking `tsp_enable` (4)
4. (Optional) Enable automatic archive signing by checking `tsp_automatic_signing` (5)
5. Save all settings (6)

![Screenshot: Configuration - TSP Settings 1](/assets/configuration/configuration_plugin_settings_1.png){ .img-thumbnail }
![Screenshot: Configuration - TSP Settings 2](/assets/configuration/configuration_tsp_settings_2.png){ .img-thumbnail }


## Signing Quiz Archives

Quiz archives can be signed either automatically during their creation or
manually at a later point in time.

### Automatic Archive Signing

If enabled, new archives will be automatically signed during creation. TSP data
can be accessed via the _Show details_ button of an archive job on the quiz
archiver overview page. Existing archives will not be signed automatically (see
[Manual archive signing](#manual-archive-signing)).

### Manual Archive Signing

To manually sign a quiz archive, navigate to the quiz archiver overview page,
click the _Show details_ button for the desired archive job, and click the
_Sign archive now_ button.


## Accessing TSP Data

Both the TSP query and the TSP response can be accessed via the job details
dialog. To do so, navigate to the quiz archiver overview page and click the
_Show details_ button for the desired archive job.

![Image of archive job details: TSP data](/assets/screenshots/quiz_archiver_job_details_modal_tsp_data.png){ .img-thumbnail }


## Validating an Archive and its Signature

To validate an archive and its signature, install `openssl` and conduct the
following steps:

1. Obtain the certificate files from your TSP authority (`.crt` and `.pem`)[^1]
2. Navigate to the quiz archiver overview page and click the _Show details_
   button for the desired archive job to check
3. Download the archive and both TSP files (`.tsq` and `.tsr`)
4. Inspect the TSP response to see the timestamp and signed hash value
    1. Execute: `openssl ts -reply -in <archive>.tsr -text`
5. Verify the quiz archive (`<archive>.tar.gz`) against the TSP response
   (`archive.tsr`). This process confirms that the archive was signed by the TSP
   authority and that the archive was not modified after signing, i.e., the hash
   values of the file matches the TSP response.
    1. Execute: `openssl ts -verify -in <archive>.tsr -data <archive>.tar.gz -CAfile <tsa>.pem -untrusted <tsa>.crt`
    2. Verify that the output is `Verification: OK`<br>
       Errors are indicated by `Verification: FAILED`
6. (Optional) Verify that TSP request and TSP response match
    1. Execute: `openssl ts -verify -in <archive>.tsr -queryfile <archive>.tsq -CAfile <tsa>.pem -untrusted <tsa>.crt`
    2. Verify that the output is `Verification: OK`<br>
       Errors are indicated by `Verification: FAILED`

[^1]: The certificate must be given by your TSP authority. You can usually find
it on the website of the service.