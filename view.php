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
 * Document viewer page — displays files as inline PDF previews.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$fileurl = required_param('fileurl', PARAM_URL);
$action  = optional_param('action', 'view', PARAM_ALPHA);
$cmidparam = optional_param('cmid', 0, PARAM_INT);

require_login();

// Validate that the URL points to a local pluginfile endpoint.
$wwwroot = $CFG->wwwroot;
$path = null;
foreach (['/pluginfile.php/', '/webservice/pluginfile.php/'] as $prefix) {
    if (strpos($fileurl, $wwwroot . $prefix) === 0) {
        $path = str_replace($wwwroot . $prefix, '', strtok($fileurl, '?'));
        break;
    }
}

if ($path === null) {
    throw new \moodle_exception('invalidfileurl', 'local_docviewer');
}

// Parse the pluginfile path: contextid/component/filearea/[itemid/][filepath/]filename.
$parts = explode('/', $path);
if (count($parts) < 4) {
    throw new \moodle_exception('invalidfileurl', 'local_docviewer');
}

$contextid = (int) $parts[0];
$component = clean_param($parts[1], PARAM_COMPONENT);
$filearea  = clean_param($parts[2], PARAM_AREA);

// Get context and verify access.
$context = \context::instance_by_id($contextid, MUST_EXIST);

if ($context->contextlevel == CONTEXT_MODULE) {
    $cm = get_coursemodule_from_id(false, $context->instanceid, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    require_login($course, true, $cm);
} else if ($context->contextlevel == CONTEXT_COURSE) {
    $course = get_course($context->instanceid);
    require_login($course);
}

require_capability('local/docviewer:view', $context);

// Extract filename and itemid from the remaining path parts.
$remaining = array_slice($parts, 3);
$filename = urldecode(array_pop($remaining));

$file = null;
$fs = get_file_storage();

// Strategy 1: first remaining element is itemid.
if (!empty($remaining)) {
    $itemid = (int) $remaining[0];
    $filepathparts = array_slice($remaining, 1);
    $filepath = '/' . (!empty($filepathparts) ? implode('/', array_map('urldecode', $filepathparts)) . '/' : '');
    $file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
}

// Strategy 2: itemid = 0, remaining as filepath.
if (!$file && !empty($remaining)) {
    $filepath = '/' . implode('/', array_map('urldecode', $remaining)) . '/';
    $file = $fs->get_file($contextid, $component, $filearea, 0, $filepath, $filename);
}

// Strategy 3: itemid = 0, filepath = /.
if (!$file) {
    $file = $fs->get_file($contextid, $component, $filearea, 0, '/', $filename);
}

// Strategy 4: search all files in the area by filename.
if (!$file) {
    $allfiles = $fs->get_area_files($contextid, $component, $filearea, false, '', false);
    foreach ($allfiles as $f) {
        if ($f->get_filename() === $filename) {
            $file = $f;
            break;
        }
    }
}

if (!$file || $file->is_directory()) {
    throw new \moodle_exception('filenotfound', 'local_docviewer');
}

$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// Action: force-download the original file (works reliably in webviews / Moodle App).
if ($action === 'download') {
    \core\session\manager::write_close();
    while (ob_get_level()) {
        ob_end_clean();
    }
    send_stored_file($file, 0, 0, true);
    die;
}

// Action: serve the converted PDF directly.
if ($action === 'servepdf') {
    // If already a PDF, serve directly.
    if ($ext === 'pdf') {
        while (ob_get_level()) {
            ob_end_clean();
        }
        send_stored_file($file, 0, 0, false);
        die;
    }

    $converter = new \local_docviewer\converter();

    if (!$converter->is_available()) {
        throw new \moodle_exception('converternotavailable', 'local_docviewer');
    }

    $pdfpath = $converter->convert($file);

    if (!$pdfpath) {
        throw new \moodle_exception('conversionfailed', 'local_docviewer');
    }

    // Close session so session headers don't interfere with PDF delivery.
    \core\session\manager::write_close();

    // Clear all Moodle output buffers before sending the binary PDF.
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Remove any session/Moodle headers that break in-browser PDF rendering.
    header_remove('Expires');
    header_remove('Pragma');
    header_remove('Cache-Control');
    header_remove('X-Frame-Options');

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . rawurlencode(pathinfo($filename, PATHINFO_FILENAME)) . '.pdf"');
    header('Content-Length: ' . filesize($pdfpath));
    header('Cache-Control: public, max-age=86400');
    header('Accept-Ranges: bytes');
    readfile($pdfpath);
    die;
}

// Action: display the viewer page.
$PAGE->set_url(new \moodle_url('/local/docviewer/view.php', ['fileurl' => $fileurl]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');
$PAGE->add_body_class('local-docviewer');
$PAGE->set_title(get_string('viewer', 'local_docviewer') . ': ' . $filename);

$pdfurl = new \moodle_url('/local/docviewer/view.php', [
    'fileurl' => $fileurl,
    'action'  => 'servepdf',
]);

$PAGE->requires->js_call_amd('local_docviewer/viewer', 'init', [[
    'pdfurl' => $pdfurl->out(false),
    'errormessage' => get_string('conversionfailed', 'local_docviewer'),
]]);

// Check if download button should be hidden for this resource.
$hidedownload = false;
if ($cmidparam > 0) {
    $hidedownload = $DB->record_exists('local_docviewer_nodownload', ['cmid' => $cmidparam]);
} else if ($context->contextlevel == CONTEXT_MODULE) {
    // Try to find cmid from context.
    $hidedownload = $DB->record_exists('local_docviewer_nodownload', ['cmid' => $context->instanceid]);
}

$downloadurl = new \moodle_url('/local/docviewer/view.php', [
    'fileurl' => $fileurl,
    'action'  => 'download',
]);

$templatecontext = [
    'filename' => $filename,
    'downloadurl' => $downloadurl->out(false),
    'ext' => strtoupper($ext),
    'showdownload' => !$hidedownload,
    'str_download_original' => get_string('download_original', 'local_docviewer'),
    'str_back' => get_string('back', 'local_docviewer'),
    'str_converting' => get_string('converting', 'local_docviewer'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_docviewer/viewer', $templatecontext);
echo $OUTPUT->footer();
