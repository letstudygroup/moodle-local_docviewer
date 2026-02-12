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
 * AJAX endpoint for toggling preview and download settings per resource.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

$cmid = required_param('cmid', PARAM_INT);
$type = required_param('type', PARAM_ALPHA); // 'preview' or 'download'
$sesskey = required_param('sesskey', PARAM_RAW);

require_login();
require_sesskey();

$cm = get_coursemodule_from_id(false, $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
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

header('Content-Type: application/json');
echo json_encode(['state' => $newstate, 'cmid' => $cmid, 'type' => $type]);
