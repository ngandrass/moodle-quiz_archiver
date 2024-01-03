<?php
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

namespace quiz_archiver\local;

/**
 * Custom util functions
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class util {

    /**
     * Convert duration in seconds to a human readable format.
     *
     * @param int $duration Duration in seconds
     * @return string Human readable duration string
     */
    public static function duration_to_human_readable(int $duration): string {
        // Calculate isolated time units
        $years = floor($duration / YEARSECS);
        $months = floor(($duration % YEARSECS) / (YEARSECS / 12));
        $days = floor(($duration % (YEARSECS / 12)) / DAYSECS);
        $hours = floor(($duration % DAYSECS) / HOURSECS);
        $minutes = floor(($duration % HOURSECS) / MINSECS);
        $seconds = floor($duration % MINSECS);

        // Generate human readable string
        $humanreadable = '';
        if ($years > 0) {
            $humanreadable .= $years . 'y ';
        }
        if ($months > 0) {
            $humanreadable .= $months . 'm ';
        }
        if ($days > 0) {
            $humanreadable .= $days . 'd ';
        }
        if ($hours > 0) {
            $humanreadable .= $hours . 'h ';
        }
        if ($minutes > 0) {
            $humanreadable .= $minutes . 'm ';
        }
        if ($seconds > 0) {
            $humanreadable .= $seconds . 's ';
        }

        return trim($humanreadable);
    }

}