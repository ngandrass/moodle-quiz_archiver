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

namespace quiz_archiver\local\admin\setting;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Custom read-only admin setting checkbox that is always checked
 *
 * @codeCoverageIgnore
 *
 * @package   quiz_archiver
 * @copyright 2024 Niels Gandraß <niels@gandrass.de>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configcheckbox_alwaystrue extends \admin_setting_configcheckbox {

    /**
     * Retrieves the current setting using the objects name
     *
     * @return int Always 1, because this setting is always true
     */
    public function get_setting() {
        return 1;
    }

    /**
     * Is this option forced in config.php?
     *
     * @return bool Always true, because this setting is always read only
     */
    public function is_readonly(): bool {
        return true;
    }

}
