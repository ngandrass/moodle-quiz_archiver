/**
 * Interactive behavior for the quiz archiver report page.
 *
 * Polls the status of unfinished archive jobs and re-renders their status,
 * filesize and action cells until each job reaches a final state.
 *
 * @module     quiz_archiver/jobupdate
 * @copyright  2026 Niels Gandraß
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';

/**
 * Polling interval for status updates.
 *
 * The archive worker typically updates the status every 15 seconds. To avoid
 * aliasing it polls at double that frequency. It is delibertly a multiple of
 * two seconds, since the spinner animation takes that long for one rotation.
 */
let JOB_STATUS_POLLING_MS = 6000;


/**
 * Initializes automatic job status polling for the quiz archiver report page.
 *
 * Scans the job overview table for jobs that have not reached a final state
 * yet and, if any are found, schedules the first status update.
 *
 * @returns {void}
 */
export const init = () => {
    const jobIds = getUnfinishedJobIds();

    if (jobIds) {
        scheduleJobUpdate(jobIds);
    }
};

/**
 * Builds a CSS selector that targets a specific updatable span element on the
 * job overview page.
 *
 * Each rendered table cell carries the table column name and the job ID as
 * custom HTML attributes, which are used to address it precisely.
 *
 * @param {string} jobId The ID of the job the targeted element belongs to
 * @param {string} ref The reference of target updatable span element
 * @returns {string} A CSS selector matching the targeted span
 */
const getSpanSelector = (jobId, ref) => `span[ref="${ref}"][jobid="${jobId}"]`;

/**
 * Collects the IDs of all jobs whose status is not yet finalized.
 *
 * A job is considered unfinished as long as its status cell does not carry
 * the "has_finalized" marker, which is set server-side on initial rendering.
 *
 * @returns {string[]|null} Array of unfinished job IDs, or null if none are found
 */
const getUnfinishedJobIds = () => {
    const statusElements = document.querySelectorAll(
        'span[ref="status-column"]:not([has_finalized])'
    );

    let unfinishedJobIds = [];
    statusElements.forEach(statusElement => {
        const jobId = statusElement.getAttribute('jobid');
        unfinishedJobIds.push(jobId);
    });

    if (unfinishedJobIds.length == 0) {
        unfinishedJobIds = null;
    }

    return unfinishedJobIds;
};

/**
 * Schedules a status update for the given jobs to run after the polling interval.
 *
 * @param {string[]} jobIds The IDs of the jobs to update
 * @returns {void}
 */
const scheduleJobUpdate = (jobIds) => {
    setTimeout(updateJobs, JOB_STATUS_POLLING_MS, jobIds);
};

/**
 * Performs one polling round for all given jobs.
 *
 * For each job the current status is fetched and the status cell is re-rendered.
 * As soon as a job completed, its filesize and action cells are updated as well.
 * If any jobs are still running, another polling round is scheduled.
 *
 * @param {string[]} jobIds The IDs of the jobs to update
 * @returns {Promise<void>}
 */
const updateJobs = async (jobIds) => {

    for (const jobId of jobIds) {

        // Get latest job data.
        const jobAjax = await getJobAjax(jobId);
        if (!jobAjax){
            continue;
        }

        // Update status colum.
        await renderStatusColumn(
            jobId,
            jobAjax.job,
            jobAjax.display_args.status
        );

        if ( jobAjax.job.complete ) {

            // Update filesize column with rendered artifact size.
            document.querySelector(
                getSpanSelector(jobId, "filesize-column")
            ).innerHTML = jobAjax.display_args.filesize;

            // Update action buttons.
            await renderActionsColumn(jobId, jobAjax.display_args.actionurls);

            // Finally: Delete this job reference so that it is no longer updated.
            jobIds.pop(jobId);
        }
    }

    if (jobIds.length > 0) {
        scheduleJobUpdate(jobIds);
    }
};

/**
 * Fetches the current status of a single job from the "quiz_archiver_get_job_ajax"
 * webservice.
 *
 * @param {string} job The ID of the job to fetch the status for
 * @returns {Promise<object|null>} The webservice response containing "status", "job" and "display_args"
 * @throws {Error} If the webservice call fails or returns a non-"OK" status
 */
const getJobAjax = async (job) => {

    let fetchedData;

    await Ajax.call([{
        methodname: 'quiz_archiver_get_job_ajax',
        args: { jobid: job },
    }])[0].then( (data) => {

        if (data['status'] !== 'OK' || data['job'] === null){
            throw Error("failed to retrieve job status");
        }

        fetchedData = data;
    }).catch((error) => {
        window.console.error('Ajax call failed with error:', error);
        Notification.exception(error);
        return null;
    });

    return fetchedData;
};

/**
 * Re-renders the status cell of a job with its freshly fetched data.
 *
 * @param {string} jobId The ID of the job whose status cell should be updated
 * @param {object} job The job data as returned by the webservice
 * @param {object} statusDisplayArgs The display arguments (color, text, help) for the status badge
 * @returns {Promise<void>}
 */
const renderStatusColumn = async (jobId, job, statusDisplayArgs) => {

    await Templates.renderForPromise(
        'quiz_archiver/job_overview_table_col_status',
        {
            "badge_text": statusDisplayArgs.text,
            "badge_color": statusDisplayArgs.color,
            "tooltip_text": statusDisplayArgs.help,
            "progress": job.statusextras ?? null,
            "time_modified": job.timemodified
        }
    ).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, "status-column"),
                html,
                js,
            );
        }
    ). catch(
        (error) => {
            window.console.error("Template render failure for status colum:", error);
            Notification.exception(error);
        }
    );
};

/**
 * Re-renders the actions cell (info, show content, download, delete) of a
 * completed job.
 *
 * @param {string} jobId The ID of the job whose action cell should be updated
 * @param {object} urls The action URLs (showcontent, artifact, delete) as returned by the webservice
 * @returns {Promise<void>}
 */
const renderActionsColumn = async (jobId, urls) => {

    await Templates.renderForPromise(
        'quiz_archiver/job_overview_table_col_actions',
        {
            "job_id": jobId,
            "show_content_url": urls['showcontent'],
            "artifact_url": urls['artifact'],
            "delete_url": urls['delete'],
        }
    ).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, "actions-column"),
                html,
                js,
            );
        }
    ). catch(
        (error) => {
            window.console.error("Template render failure for action colum:", error);
            Notification.exception(error);
        }
    );
};
