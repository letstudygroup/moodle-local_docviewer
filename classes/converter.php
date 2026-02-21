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
 * Document-to-PDF converter using LibreOffice headless mode.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_docviewer;

/**
 * Converts office documents to PDF using LibreOffice in headless mode.
 *
 * Converted files are cached by content hash to avoid repeated conversions.
 */
class converter {
    /** @var string Path to LibreOffice binary. */
    private string $lopath;

    /** @var string Path to cache directory. */
    private string $cachedir;

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;
        $this->lopath = get_config('local_docviewer', 'libreoffice_path') ?: '/usr/bin/libreoffice';
        $this->cachedir = $CFG->dataroot . '/docviewer';

        if (!is_dir($this->cachedir)) {
            mkdir($this->cachedir, 0755, true);
        }
    }

    /**
     * Check if LibreOffice is available for conversion.
     *
     * @return bool True if LibreOffice is available.
     */
    public function is_available(): bool {
        return file_exists($this->lopath) && is_executable($this->lopath);
    }

    /**
     * Get cached PDF path for a given content hash.
     *
     * @param string $contenthash The content hash of the original file.
     * @return string|null Path to the cached PDF, or null if not cached.
     */
    public function get_cached_pdf(string $contenthash): ?string {
        $pdfpath = $this->cachedir . '/' . $contenthash . '.pdf';
        if (file_exists($pdfpath) && filesize($pdfpath) > 0) {
            return $pdfpath;
        }
        return null;
    }

    /**
     * Convert a stored file to PDF.
     *
     * @param \stored_file $file The file to convert.
     * @return string|null Path to the converted PDF, or null on failure.
     */
    public function convert(\stored_file $file): ?string {
        $contenthash = $file->get_contenthash();

        // Check cache first.
        $cached = $this->get_cached_pdf($contenthash);
        if ($cached) {
            return $cached;
        }

        // Check max file size.
        $maxsize = (int) get_config('local_docviewer', 'maxfilesize') ?: 50;
        if ($file->get_filesize() > $maxsize * 1024 * 1024) {
            debugging('local_docviewer: File too large for conversion: ' . $file->get_filename(), DEBUG_NORMAL);
            return null;
        }

        // Extract file to a unique temp directory.
        $tempdir = make_temp_directory('docviewer/' . $contenthash . '_' . uniqid());
        $filename = $file->get_filename();
        $tempfile = $tempdir . '/' . $filename;
        $file->copy_content_to($tempfile);

        // Create a unique LibreOffice user profile for this conversion (avoids lock conflicts).
        $profiledir = $tempdir . '/profile';
        mkdir($profiledir, 0755, true);

        // Build the conversion command with a 120-second timeout.
        // Use cd + HOME to avoid the libreoffice wrapper script failing
        // when the web server's CWD is not accessible (e.g. /root).
        $cmd = 'cd ' . escapeshellarg($tempdir) .
               ' && HOME=' . escapeshellarg($tempdir) .
               ' timeout 120 ' .
               escapeshellcmd($this->lopath) .
               ' --headless --norestore --nofirststartwizard' .
               ' "-env:UserInstallation=file://' . $profiledir . '"' .
               ' --convert-to pdf' .
               ' --outdir ' . escapeshellarg($tempdir) .
               ' ' . escapeshellarg($tempfile) .
               ' 2>&1';

        $output = [];
        $return = 0;
        exec($cmd, $output, $return);

        // Find the generated PDF.
        $pdfname = pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
        $pdftemp = $tempdir . '/' . $pdfname;

        if (file_exists($pdftemp) && filesize($pdftemp) > 0) {
            $pdfcache = $this->cachedir . '/' . $contenthash . '.pdf';
            copy($pdftemp, $pdfcache);
            $this->cleanup_temp($tempdir);
            return $pdfcache;
        }

        // Cleanup on failure.
        $this->cleanup_temp($tempdir);
        debugging('local_docviewer: Conversion failed for ' . $filename .
                  '. Return code: ' . $return .
                  '. Output: ' . implode("\n", $output), DEBUG_NORMAL);
        return null;
    }

    /**
     * Recursively remove a temporary directory.
     *
     * @param string $dir The directory to remove.
     */
    private function cleanup_temp(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
            }
        }
        @rmdir($dir);
    }
}
