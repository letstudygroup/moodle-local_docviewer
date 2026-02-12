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
 * Admin settings page for local_docviewer.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_docviewer', get_string('pluginname', 'local_docviewer'));
    $ADMIN->add('localplugins', $settings);

    // Enable/disable.
    $settings->add(new admin_setting_configcheckbox(
        'local_docviewer/enabled',
        get_string('enabled', 'local_docviewer'),
        get_string('enabled_desc', 'local_docviewer'),
        1
    ));

    // LibreOffice path.
    $settings->add(new admin_setting_configtext(
        'local_docviewer/libreoffice_path',
        get_string('libreoffice_path', 'local_docviewer'),
        get_string('libreoffice_path_desc', 'local_docviewer'),
        '/usr/bin/libreoffice'
    ));

    // Supported formats.
    $settings->add(new admin_setting_configtext(
        'local_docviewer/supportedformats',
        get_string('supportedformats', 'local_docviewer'),
        get_string('supportedformats_desc', 'local_docviewer'),
        'doc,docx,odt,rtf,xls,xlsx,ods,ppt,pptx,odp'
    ));

    // Max file size.
    $settings->add(new admin_setting_configtext(
        'local_docviewer/maxfilesize',
        get_string('maxfilesize', 'local_docviewer'),
        get_string('maxfilesize_desc', 'local_docviewer'),
        '50',
        PARAM_INT
    ));

    // Cache max age.
    $settings->add(new admin_setting_configtext(
        'local_docviewer/cache_maxage',
        get_string('cache_maxage', 'local_docviewer'),
        get_string('cache_maxage_desc', 'local_docviewer'),
        '30',
        PARAM_INT
    ));

    // LibreOffice status check.
    $lopath = get_config('local_docviewer', 'libreoffice_path') ?: '/usr/bin/libreoffice';
    $statustext = '';
    if (file_exists($lopath) && is_executable($lopath)) {
        $version = [];
        exec('HOME=/tmp DBUS_SESSION_BUS_ADDRESS= ' . escapeshellcmd($lopath) . ' --version 2>&1', $version);
        // Filter out dconf/GLib warnings from version output.
        $version = array_filter($version, function($line) {
            return stripos($line, 'dconf') === false && stripos($line, 'GLib') === false;
        });
        $versionstr = !empty($version) ? implode(' ', $version) : 'unknown version';
        $statustext = get_string('status_version', 'local_docviewer', $versionstr);
    } else {
        $statustext = get_string('status_missing', 'local_docviewer');
    }

    $settings->add(new admin_setting_heading(
        'local_docviewer/status',
        get_string('status', 'local_docviewer'),
        $statustext
    ));
}
