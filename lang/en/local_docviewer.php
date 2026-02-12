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
 * English language strings for local_docviewer.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Document Viewer';
$string['enabled'] = 'Enable Document Viewer';
$string['enabled_desc'] = 'When enabled, supported document files will be displayed as PDF previews inline within the platform.';
$string['libreoffice_path'] = 'LibreOffice path';
$string['libreoffice_path_desc'] = 'Full path to the LibreOffice binary. LibreOffice is used for converting documents to PDF.';
$string['supportedformats'] = 'Supported formats';
$string['supportedformats_desc'] = 'Comma-separated list of file extensions to convert and display as PDF previews.';
$string['maxfilesize'] = 'Max file size (MB)';
$string['maxfilesize_desc'] = 'Maximum file size in megabytes that will be converted. Larger files will be served as downloads.';
$string['status'] = 'LibreOffice Status';
$string['status_ok'] = '<span style="color:green;font-weight:bold;">&#10003; LibreOffice is installed and available.</span>';
$string['status_missing'] = '<span style="color:red;font-weight:bold;">&#10007; LibreOffice not found at the configured path. Document conversion will not work.</span>';
$string['status_version'] = '<span style="color:green;font-weight:bold;">&#10003; LibreOffice is installed: {$a}</span>';
$string['viewer'] = 'Document Viewer';
$string['download_original'] = 'Download original';
$string['back'] = 'Back';
$string['invalidfileurl'] = 'Invalid file URL provided.';
$string['filenotfound'] = 'The requested file could not be found.';
$string['converternotavailable'] = 'The document converter (LibreOffice) is not available. Please contact the site administrator.';
$string['conversionfailed'] = 'The document could not be converted to PDF. Please download the original file.';
$string['privacy:metadata'] = 'The Document Viewer plugin does not store any personal data. It only converts and caches document files as PDFs.';
$string['docviewer:view'] = 'View documents in the inline document viewer';
$string['open_in_viewer'] = 'Preview document';
$string['converting'] = 'Converting document, please wait...';
$string['disable_for_resource'] = 'Disable preview';
$string['enable_for_resource'] = 'Enable preview';
$string['preview_disabled'] = 'Preview is disabled for this file.';
$string['cache_cleanup'] = 'Document Viewer cache cleanup';
$string['cache_maxage'] = 'Cache max age (days)';
$string['cache_maxage_desc'] = 'Cached PDF files older than this number of days will be automatically deleted.';
$string['show_download'] = 'Show download button';
$string['hide_download'] = 'Hide download button';
