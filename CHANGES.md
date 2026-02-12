# Changelog

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
