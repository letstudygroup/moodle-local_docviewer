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
 * Greek language strings for local_docviewer.
 *
 * @package    local_docviewer
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Document Viewer';
$string['enabled'] = 'Ενεργοποίηση Document Viewer';
$string['enabled_desc'] = 'Όταν είναι ενεργοποιημένο, τα υποστηριζόμενα αρχεία εγγράφων θα εμφανίζονται ως προεπισκόπηση PDF μέσα στην πλατφόρμα.';
$string['libreoffice_path'] = 'Διαδρομή LibreOffice';
$string['libreoffice_path_desc'] = 'Πλήρης διαδρομή προς το εκτελέσιμο αρχείο του LibreOffice.';
$string['supportedformats'] = 'Υποστηριζόμενες μορφές';
$string['supportedformats_desc'] = 'Λίστα επεκτάσεων αρχείων διαχωρισμένων με κόμμα που θα μετατρέπονται και θα εμφανίζονται ως PDF.';
$string['maxfilesize'] = 'Μέγιστο μέγεθος αρχείου (MB)';
$string['maxfilesize_desc'] = 'Μέγιστο μέγεθος αρχείου σε megabytes που θα μετατρέπεται. Τα μεγαλύτερα αρχεία θα κατεβαίνουν κανονικά.';
$string['status'] = 'Κατάσταση LibreOffice';
$string['status_ok'] = '<span style="color:green;font-weight:bold;">&#10003; Το LibreOffice είναι εγκατεστημένο και διαθέσιμο.</span>';
$string['status_missing'] = '<span style="color:red;font-weight:bold;">&#10007; Το LibreOffice δεν βρέθηκε στη διαδρομή. Η μετατροπή εγγράφων δεν θα λειτουργήσει.</span>';
$string['status_version'] = '<span style="color:green;font-weight:bold;">&#10003; Το LibreOffice είναι εγκατεστημένο: {$a}</span>';
$string['viewer'] = 'Προβολή Εγγράφου';
$string['download_original'] = 'Λήψη αρχικού';
$string['back'] = 'Πίσω';
$string['invalidfileurl'] = 'Μη έγκυρο URL αρχείου.';
$string['filenotfound'] = 'Το αρχείο δεν βρέθηκε.';
$string['converternotavailable'] = 'Ο μετατροπέας εγγράφων (LibreOffice) δεν είναι διαθέσιμος. Επικοινωνήστε με τον διαχειριστή.';
$string['conversionfailed'] = 'Η μετατροπή του εγγράφου σε PDF απέτυχε. Παρακαλώ κατεβάστε το αρχικό αρχείο.';
$string['privacy:metadata'] = 'Το Document Viewer δεν αποθηκεύει προσωπικά δεδομένα. Μετατρέπει και αποθηκεύει προσωρινά αρχεία εγγράφων ως PDF.';
$string['docviewer:view'] = 'Προβολή εγγράφων στο Document Viewer';
$string['open_in_viewer'] = 'Προεπισκόπηση εγγράφου';
$string['converting'] = 'Μετατροπή εγγράφου, παρακαλώ περιμένετε...';
$string['disable_for_resource'] = 'Απενεργοποίηση προεπισκόπησης';
$string['enable_for_resource'] = 'Ενεργοποίηση προεπισκόπησης';
$string['preview_disabled'] = 'Η προεπισκόπηση είναι απενεργοποιημένη για αυτό το αρχείο.';
$string['cache_cleanup'] = 'Καθαρισμός cache Document Viewer';
$string['cache_maxage'] = 'Μέγιστη ηλικία cache (ημέρες)';
$string['cache_maxage_desc'] = 'Τα αρχεία PDF στο cache που είναι παλαιότερα από αυτές τις ημέρες θα διαγράφονται αυτόματα.';
$string['show_download'] = 'Εμφάνιση κουμπιού λήψης';
$string['hide_download'] = 'Απόκρυψη κουμπιού λήψης';
