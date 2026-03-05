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
 * Document Viewer - PDF viewer loader.
 *
 * Fetches the converted PDF and renders it inside an iframe.
 *
 * @module     local_docviewer/viewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        /**
         * Load and display the PDF in the viewer iframe.
         *
         * @param {object} params The viewer parameters.
         * @param {string} params.pdfurl The URL to fetch the PDF from.
         * @param {string} params.errormessage The error message to display on failure.
         */
        init: function(params) {
            const pdfUrl = params.pdfurl;
            const errorMessage = params.errormessage;
            const iframe = document.getElementById('docviewer-iframe');
            const loading = document.getElementById('docviewer-loading');

            // In Moodle App / webview environments, blob URLs may not work.
            // Fall back to loading the PDF URL directly in the iframe.
            const useBlobUrl = !/MoodleMobile/i.test(navigator.userAgent);

            if (useBlobUrl) {
                fetch(pdfUrl, {credentials: 'same-origin'})
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.blob();
                    })
                    .then(function(blob) {
                        // #toolbar=0 hides Chrome/Edge PDF toolbar (download, print).
                        var blobUrl = URL.createObjectURL(blob) + '#toolbar=0&navpanes=0';
                        iframe.src = blobUrl;
                        iframe.addEventListener('load', function() {
                            loading.style.display = 'none';
                            iframe.style.display = 'block';
                        });
                    })
                    .catch(function() {
                        loading.innerHTML = '<div class="alert alert-danger">' +
                            '<i class="fa fa-exclamation-triangle"></i> ' +
                            errorMessage +
                            '</div>';
                    });
            } else {
                // Direct iframe load for Moodle App compatibility.
                iframe.src = pdfUrl;
                iframe.addEventListener('load', function() {
                    loading.style.display = 'none';
                    iframe.style.display = 'block';
                });
            }
        }
    };
});
