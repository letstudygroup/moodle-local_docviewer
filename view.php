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
$cmid_param = optional_param('cmid', 0, PARAM_INT);

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
$PAGE->set_title(get_string('viewer', 'local_docviewer') . ': ' . $filename);

$pdfurl = new \moodle_url('/local/docviewer/view.php', [
    'fileurl' => $fileurl,
    'action'  => 'servepdf',
]);

// Check if download button should be hidden for this resource.
$hidedownload = false;
if ($cmid_param > 0) {
    $hidedownload = $DB->record_exists('local_docviewer_nodownload', ['cmid' => $cmid_param]);
} else if ($context->contextlevel == CONTEXT_MODULE) {
    // Try to find cmid from context.
    $hidedownload = $DB->record_exists('local_docviewer_nodownload', ['cmid' => $context->instanceid]);
}

echo $OUTPUT->header();
?>
<div class="docviewer-container">
    <div class="docviewer-toolbar">
        <span class="docviewer-filename" title="<?php echo s($filename); ?>">
            <i class="fa fa-file-text"></i>
            <?php echo s($filename); ?>
        </span>
        <div class="docviewer-actions">
            <?php if (!$hidedownload): ?>
            <a href="<?php echo s($fileurl); ?>" class="btn btn-primary btn-sm docviewer-download" download>
                <i class="fa fa-download"></i>
                <?php echo get_string('download_original', 'local_docviewer'); ?>
                (<?php echo strtoupper($ext); ?>)
            </a>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i>
                <?php echo get_string('back', 'local_docviewer'); ?>
            </a>
        </div>
    </div>
    <div class="docviewer-frame" id="docviewer-frame">
        <div class="docviewer-loading" id="docviewer-loading">
            <div class="spinner-border text-primary" role="status"></div>
            <span><?php echo get_string('converting', 'local_docviewer'); ?></span>
        </div>
        <iframe id="docviewer-iframe"
                style="display:none;"
                allowfullscreen></iframe>
    </div>
</div>
<script>
(function() {
    var pdfUrl = <?php echo json_encode($pdfurl->out(false)); ?>;
    var iframe = document.getElementById('docviewer-iframe');
    var loading = document.getElementById('docviewer-loading');

    fetch(pdfUrl, {credentials: 'same-origin'})
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.blob();
        })
        .then(function(blob) {
            // #toolbar=0 hides Chrome/Edge PDF toolbar (download, print).
            // For Firefox PDF.js, we inject CSS after load.
            var blobUrl = URL.createObjectURL(blob) + '#toolbar=0&navpanes=0';
            iframe.src = blobUrl;
            iframe.addEventListener('load', function() {
                loading.style.display = 'none';
                iframe.style.display = 'block';
            });
        })
        .catch(function(err) {
            loading.innerHTML = '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-triangle"></i> ' +
                '<?php echo addslashes_js(get_string('conversionfailed', 'local_docviewer')); ?>' +
                '</div>';
        });
})();
</script>
<?php
echo $OUTPUT->footer();
