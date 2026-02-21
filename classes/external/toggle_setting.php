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

/**
 * External function for toggling preview and download settings per resource.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_docviewer\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Toggle preview or download setting for a course module resource.
 */
class toggle_setting extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'type' => new external_value(PARAM_ALPHA, 'Setting type: preview or download'),
        ]);
    }

    /**
     * Toggle the setting.
     *
     * @param int $cmid Course module ID.
     * @param string $type Setting type (preview or download).
     * @return array
     */
    public static function execute(int $cmid, string $type): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'type' => $type,
        ]);
        $cmid = $params['cmid'];
        $type = $params['type'];

        if (!in_array($type, ['preview', 'download'])) {
            throw new \invalid_parameter_exception('Type must be "preview" or "download".');
        }

        $cm = get_coursemodule_from_id(false, $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('moodle/course:manageactivities', $context);

        $table = ($type === 'download') ? 'local_docviewer_nodownload' : 'local_docviewer_exclude';
        $exists = $DB->record_exists($table, ['cmid' => $cmid]);

        if ($exists) {
            $DB->delete_records($table, ['cmid' => $cmid]);
            $newstate = ($type === 'download') ? 'download_visible' : 'preview_enabled';
        } else {
            $DB->insert_record($table, (object) [
                'cmid' => $cmid,
                'timecreated' => time(),
            ]);
            $newstate = ($type === 'download') ? 'download_hidden' : 'preview_disabled';
        }

        return [
            'state' => $newstate,
            'cmid' => $cmid,
            'type' => $type,
        ];
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'state' => new external_value(PARAM_TEXT, 'New state after toggle'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'type' => new external_value(PARAM_ALPHA, 'Setting type'),
        ]);
    }
}
