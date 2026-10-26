// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
import {getString} from 'core/str';
import UserDate from 'core/user_date';
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
const getSpanSelector = (jobId, ref) => `span[data-ref="${ref}"][data-jobid="${jobId}"]`;

/**
 * Collects the IDs of all jobs whose status is not yet finalized.
 *
 * A job is considered unfinished as long as its status cell does not carry
 * the "data-finalized" marker, which is set server-side on initial rendering.
 *
 * @returns {string[]|null} Array of unfinished job IDs, or null if none are found
 */
const getUnfinishedJobIds = () => {

    const statusElements = document.querySelectorAll(
        'span[data-ref="status-column"]:not([data-finalized])',
    );

    let unfinishedJobIds = [];
    statusElements.forEach(statusElement => {
        const jobId = statusElement.dataset.jobid;
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
const updateJobs = async(jobIds) => {

    for (const jobId of jobIds) {

        // Get latest job data.
        const jobMetadata = await getJobMetadata(jobId);
        if (!jobMetadata) {
            continue;
        }

        // Update status colum.
        await renderStatusColumn(
            jobId,
            jobMetadata.job,
            jobMetadata.job.status_display_args,
        );

        // Update modal if available.
        const modalElement = document.querySelector(getSpanSelector(jobId, "status-modal"));
        if (modalElement) {
            await updateModal(jobId, jobMetadata);
        }

        if (jobMetadata.job.complete) {

            // Update filesize column with rendered artifact size.
            document.querySelector(
                getSpanSelector(jobId, "filesize-column")
            ).innerHTML = jobMetadata.job.artifact?.size_human ?? '';

            // Update action buttons.
            await renderActionsColumn(jobId, jobMetadata.job.actionurls);

            // Finally: Delete this job reference so that it is no longer updated.
            jobIds.pop(jobId);
        }
    }

    if (jobIds.length > 0) {
        scheduleJobUpdate(jobIds);
    }
};

/**
 * Fetches the current status of a single job from the "quiz_archiver_get_job_metadata"
 * webservice.
 *
 * @param {string} job The ID of the job to fetch the status for
 * @returns {Promise<object|null>} The webservice response containing "status" and "job"
 * @throws {Error} If the webservice call fails or returns a non-"OK" status
 */
const getJobMetadata = async(job) => {

    let fetchedData;

    await Ajax.call([{
        methodname: 'quiz_archiver_get_job_metadata',
        args: {jobid: job},
    }])[0].then((data) => {

        if (data.status !== 'OK' || !data.job) {
            throw Error("failed to retrieve job status");
        }

        fetchedData = data;
        return;
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
 * @param {object} statusDisplayArgs The display arguments (color, text, help, statusextras) for the status badge
 * @returns {Promise<void>}
 */
const renderStatusColumn = async(jobId, job, statusDisplayArgs) => {

    await Templates.renderForPromise(
        'quiz_archiver/job_overview/fragment/status',
        {
            "badge_text": statusDisplayArgs.text,
            "badge_color": statusDisplayArgs.color,
            "tooltip_text": statusDisplayArgs.help,
            "progress": statusDisplayArgs.statusextras?.progress ?? null,
            "time_modified": job.timemodified,
        }
    ).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, "status-column"),
                html,
                js,
            );
            return;
        }
    ).catch(
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
 * @param {object} urls The action URLs required for rendered action buttons
 * @returns {Promise<void>}
 */
const renderActionsColumn = async(jobId, urls) => {

    await Templates.renderForPromise(
        'quiz_archiver/job_overview/fragment/actions',
        {
            "job_id": jobId,
            "show_content_url": urls.showcontent,
            "artifact_url": urls.artifact_download,
            "delete_url": urls.job_delete,
        }
    ).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, "actions-column"),
                html,
                js,
            );
            return;
        }
    ).catch(
        (error) => {
            window.console.error("Template render failure for action colum:", error);
            Notification.exception(error);
        }
    );
};

/**
 * Re-renders the updatable rows inside the job details modal with provided job
 * data.
 *
 * @param {string} jobId The ID of the job whose modal should be updated
 * @param {object} jobMetadata The webservice response containing "job"
 * @returns {Promise<void>}
 */
const updateModal = async(jobId, jobMetadata) => {

    const job = jobMetadata.job;
    const statusDisplayArgs = job.status_display_args;

    // Render the status row.
    await Templates.renderForPromise(
        'quiz_archiver/job_overview/fragment/status',
        {
            "badge_text": statusDisplayArgs.text,
            "badge_color": statusDisplayArgs.color,
            "tooltip_text": statusDisplayArgs.help,
            "progress": statusDisplayArgs.statusextras?.progress ?? null,
        }
    ).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, "status-modal"),
                html,
                js,
            );
            return;
        }
    ).catch(
        (error) => {
            window.console.error("Template render failure for modal status row:", error);
            Notification.exception(error);
        }
    );

    // Render the archive and signature cells.
    // NOTE: Both share the same context, so one object serves them both.
    const cellContext = {
        "artifact": job.artifact,
        "signature": job.signature,
        "autodelete": job.autodelete,
        "actionurls": job.actionurls,
    };
    await Promise.all([
        renderModalCell(
            jobId,
            "archive-modal",
            'quiz_archiver/job_overview/fragment/artifact',
            cellContext,
        ),
        renderModalCell(
            jobId,
            "signature-modal",
            'quiz_archiver/job_overview/fragment/signature',
            cellContext,
        ),
    ]);

    // Update the "last modified" row.
    getString('strftimedatetimeaccurate', 'core_langconfig').then(
        (formatString) => UserDate.get(
            [{'timestamp': job.timemodified, 'format': formatString}]
        )
    ).then((formattedDates) => {
        document.querySelector(
            getSpanSelector(jobId, "timemodified-modal")
        ).innerHTML = formattedDates[0];
        return;
    }).catch((error) => {
        window.console.error("Could not format date:", error);
        Notification.exception(error);
    });
};

/**
 * Re-renders a single updatable modal cell (e.g. archive or signature) from the
 * given context.
 *
 * @param {string} jobId The ID of the job whose cell should be updated
 * @param {string} ref The reference of the target cell (e.g. "archive-modal")
 * @param {string} template The Mustache template to render the cell with
 * @param {object} context The context to render the template with
 * @returns {Promise<void>}
 */
const renderModalCell = async(jobId, ref, template, context) => {

    await Templates.renderForPromise(template, context).then(
        ({html, js}) => {
            Templates.replaceNodeContents(
                getSpanSelector(jobId, ref),
                html,
                js,
            );
            return;
        }
    ).catch(
        (error) => {
            window.console.error(`Template render failure for modal cell "${ref}":`, error);
            Notification.exception(error);
        }
    );
};
