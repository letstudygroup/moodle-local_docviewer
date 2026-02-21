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
 * Scheduled task to clean up old cached PDF files.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_docviewer\task;

/**
 * Deletes cached PDFs older than the configured maximum age.
 */
class cleanup_cache extends \core\task\scheduled_task {
    /**
     * Get the name of this scheduled task.
     *
     * @return string The task name.
     */
    public function get_name(): string {
        return get_string('cache_cleanup', 'local_docviewer');
    }

    /**
     * Execute the cleanup task.
     */
    public function execute(): void {
        global $CFG;

        $cachedir = $CFG->dataroot . '/docviewer';
        if (!is_dir($cachedir)) {
            return;
        }

        $maxagedays = (int) get_config('local_docviewer', 'cache_maxage') ?: 30;
        $maxage = $maxagedays * 86400;
        $now = time();
        $deleted = 0;
        $freedbytes = 0;

        $files = glob($cachedir . '/*.pdf');
        foreach ($files as $file) {
            $age = $now - filemtime($file);
            if ($age > $maxage) {
                $size = filesize($file);
                if (@unlink($file)) {
                    $deleted++;
                    $freedbytes += $size;
                }
            }
        }

        if ($deleted > 0) {
            $freedmb = round($freedbytes / (1024 * 1024), 2);
            mtrace("local_docviewer: Cleaned up {$deleted} cached PDFs, freed {$freedmb} MB.");
        }
    }
}
