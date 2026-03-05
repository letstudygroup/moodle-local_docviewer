# Changelog

## v1.1.1 (2026-03-05)

### Bug fixes
- Fixed compatibility with Moodle App and Microsoft Teams webview (#8).
  - Document preview now works in the Moodle App: the viewer page loads with
    direct iframe rendering (no blob URLs) for webview compatibility.
  - The download button now uses a server-side forced download (`Content-Disposition:
    attachment`) instead of the HTML `download` attribute, which is unreliable in
    webviews and caused the "index.php" filename issue.
  - The JS link interceptor on course pages is skipped in the Moodle App, since
    the app renders course content natively.

## v1.0.0 (2026-02-12)

Initial release.

### Features
- Inline PDF preview for office documents (Word, Excel, PowerPoint, OpenDocument, RTF).
- Automatic interception of resource file clicks on course pages.
- LibreOffice headless conversion with per-conversion user profiles.
- Content-hash based PDF caching to avoid duplicate conversions.
- Per-resource toggle to enable/disable document preview.
- Per-resource toggle to show/hide the "Download original" button.
- Embedded viewer with full-screen layout and hidden browser PDF toolbar.
- Scheduled task for automatic cache cleanup (configurable max age).
- Admin settings page with LibreOffice status check.
- English and Greek language packs.
- Privacy API implementation (null provider — no personal data stored).
- Moodle 5 Hook API integration (no legacy event observers).
