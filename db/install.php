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
 * Post-installation steps for local_docviewer.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform post-install setup: create cache directory and set defaults.
 *
 * @return bool
 */
function xmldb_local_docviewer_install() {
    global $CFG;

    // Create cache directory for converted PDFs.
    $cachedir = $CFG->dataroot . '/docviewer';
    if (!is_dir($cachedir)) {
        mkdir($cachedir, 0755, true);
    }

    // Set default configuration.
    set_config('enabled', 1, 'local_docviewer');
    set_config('libreoffice_path', '/usr/bin/libreoffice', 'local_docviewer');
    set_config('supportedformats', 'doc,docx,odt,rtf,xls,xlsx,ods,ppt,pptx,odp', 'local_docviewer');
    set_config('maxfilesize', 50, 'local_docviewer');

    return true;
}
