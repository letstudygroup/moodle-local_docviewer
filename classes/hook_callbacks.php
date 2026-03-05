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
 * Hook callbacks for local_docviewer.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_docviewer;

use core\hook\output\before_footer_html_generation;
use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Hook callbacks that intercept resource pages and inject the viewer JS.
 */
class hook_callbacks {
    /**
     * Get the list of supported file extensions.
     *
     * @return array List of supported extensions.
     */
    private static function get_supported_extensions(): array {
        $formats = get_config('local_docviewer', 'supportedformats') ?:
                   'doc,docx,odt,rtf,xls,xlsx,ods,ppt,pptx,odp';
        return array_map('trim', explode(',', $formats));
    }

    /**
     * Check if preview is excluded for a given course module.
     *
     * @param int $cmid The course module ID.
     * @return bool True if excluded.
     */
    private static function is_excluded(int $cmid): bool {
        global $DB;
        return $DB->record_exists('local_docviewer_exclude', ['cmid' => $cmid]);
    }

    /**
     * On mod_resource view pages, redirect to our viewer immediately.
     *
     * @param before_standard_top_of_body_html_generation $hook The hook instance.
     */
    public static function before_standard_top_of_body(before_standard_top_of_body_html_generation $hook): void {
        global $PAGE, $CFG;

        if (!get_config('local_docviewer', 'enabled')) {
            return;
        }

        if (during_initial_install() || !isloggedin() || isguestuser()) {
            return;
        }

        // Only act on mod/resource/view.php, nowhere else.
        $scriptname = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        if (strpos($scriptname, 'mod/resource/view.php') === false) {
            return;
        }
        try {
            $cm = $PAGE->cm;
            if (!$cm || $cm->modname !== 'resource') {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        // Check per-file exclusion.
        if (self::is_excluded($PAGE->cm->id)) {
            return;
        }

        $supported = self::get_supported_extensions();
        $ctx = \context_module::instance($PAGE->cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $ctx->id,
            'mod_resource',
            'content',
            0,
            'sortorder DESC, id ASC',
            false
        );
        if (!$files) {
            return;
        }

        $file = reset($files);
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

        if (!in_array($ext, $supported)) {
            return;
        }

        $fileurl = \moodle_url::make_pluginfile_url(
            $ctx->id,
            'mod_resource',
            'content',
            0,
            $file->get_filepath(),
            $file->get_filename()
        );
        $rawfileurl = $fileurl->out(false);
        $url = (new \moodle_url('/local/docviewer/view.php'))->out(false)
             . '?fileurl=' . rawurlencode($rawfileurl);
        $hook->add_html('<script>window.location.replace(' . json_encode($url) . ');</script>');
    }

    /**
     * Inject the Document Viewer JS interceptor before the page footer.
     *
     * @param before_footer_html_generation $hook The hook instance.
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $CFG, $DB;

        if (!get_config('local_docviewer', 'enabled')) {
            return;
        }

        if (during_initial_install() || !isloggedin() || isguestuser()) {
            return;
        }

        // Do not inject interceptor JS when accessed from the Moodle App (or via Teams/webview).
        if (\core_useragent::is_moodle_app()) {
            return;
        }

        $supported = self::get_supported_extensions();
        $viewerurl = new \moodle_url('/local/docviewer/view.php');

        // Always inject the base interceptor for pluginfile.php links.
        $PAGE->requires->js_call_amd('local_docviewer/interceptor', 'init', [[
            'extensions' => $supported,
            'viewerurl'  => $viewerurl->out(false),
        ]]);

        // On course pages, also provide resource data for event delegation.
        if (
            strpos($PAGE->pagetype, 'course-view-') === 0
            && !empty($PAGE->course->id)
            && $PAGE->course->id != SITEID
        ) {
            try {
                $modinfo = get_fast_modinfo($PAGE->course);
                $resourcedata = [];
                $excludedcmids = [];

                // Load all exclusions for this course's resources in one query.
                $allcmids = [];
                foreach ($modinfo->get_instances_of('resource') as $cminfo) {
                    $allcmids[] = (int) $cminfo->id;
                }
                if (!empty($allcmids)) {
                    [$insql, $inparams] = $DB->get_in_or_equal($allcmids);
                    $excludedrecords = $DB->get_records_select(
                        'local_docviewer_exclude',
                        "cmid $insql",
                        $inparams
                    );
                    foreach ($excludedrecords as $rec) {
                        $excludedcmids[$rec->cmid] = true;
                    }
                }

                // Check if user can manage activities (for toggle buttons).
                $coursecontext = \context_course::instance($PAGE->course->id);
                $canmanage = has_capability('moodle/course:manageactivities', $coursecontext);

                foreach ($modinfo->get_instances_of('resource') as $cminfo) {
                    if (!$cminfo->uservisible) {
                        continue;
                    }

                    // Skip excluded resources.
                    if (isset($excludedcmids[$cminfo->id])) {
                        continue;
                    }

                    $ctx = \context_module::instance($cminfo->id);
                    $fs = get_file_storage();
                    $files = $fs->get_area_files(
                        $ctx->id,
                        'mod_resource',
                        'content',
                        0,
                        'sortorder DESC, id ASC',
                        false
                    );
                    if (!$files) {
                        continue;
                    }

                    $mainfile = reset($files);
                    $ext = strtolower(pathinfo($mainfile->get_filename(), PATHINFO_EXTENSION));

                    if (in_array($ext, $supported)) {
                        $fileurl = \moodle_url::make_pluginfile_url(
                            $ctx->id,
                            'mod_resource',
                            'content',
                            0,
                            $mainfile->get_filepath(),
                            $mainfile->get_filename()
                        );
                        $resourcedata[] = [
                            'cmid'     => (int) $cminfo->id,
                            'fileurl'  => $fileurl->out(),
                            'ext'      => $ext,
                            'filename' => $mainfile->get_filename(),
                        ];
                    }
                }

                if (!empty($resourcedata)) {
                    $PAGE->requires->js_call_amd('local_docviewer/interceptor', 'initResources', [[
                        'resources' => $resourcedata,
                        'viewerurl' => $viewerurl->out(false),
                    ]]);
                }

                // For teachers: inject toggle buttons for all supported resources.
                if ($canmanage) {
                    // Also load nodownload settings.
                    $nodownloadcmids = [];
                    if (!empty($allcmids)) {
                        $ndrecords = $DB->get_records_select(
                            'local_docviewer_nodownload',
                            "cmid $insql",
                            $inparams
                        );
                        foreach ($ndrecords as $rec) {
                            $nodownloadcmids[$rec->cmid] = true;
                        }
                    }

                    $toggledata = [];
                    foreach ($modinfo->get_instances_of('resource') as $cminfo) {
                        if (!$cminfo->uservisible) {
                            continue;
                        }
                        $ctx = \context_module::instance($cminfo->id);
                        $fs = get_file_storage();
                        $files = $fs->get_area_files(
                            $ctx->id,
                            'mod_resource',
                            'content',
                            0,
                            'sortorder DESC, id ASC',
                            false
                        );
                        if (!$files) {
                            continue;
                        }
                        $mainfile = reset($files);
                        $ext = strtolower(pathinfo($mainfile->get_filename(), PATHINFO_EXTENSION));
                        if (in_array($ext, $supported)) {
                            $toggledata[] = [
                                'cmid' => (int) $cminfo->id,
                                'excluded' => isset($excludedcmids[$cminfo->id]),
                                'nodownload' => isset($nodownloadcmids[$cminfo->id]),
                            ];
                        }
                    }
                    if (!empty($toggledata)) {
                        $PAGE->requires->js_call_amd('local_docviewer/interceptor', 'initToggles', [[
                            'toggles' => $toggledata,
                            'enableLabel' => get_string('enable_for_resource', 'local_docviewer'),
                            'disableLabel' => get_string('disable_for_resource', 'local_docviewer'),
                            'showDownloadLabel' => get_string('show_download', 'local_docviewer'),
                            'hideDownloadLabel' => get_string('hide_download', 'local_docviewer'),
                        ]]);
                    }
                }
            } catch (\Exception $e) {
                debugging(
                    'local_docviewer: Error processing course resources: ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }
    }
}
