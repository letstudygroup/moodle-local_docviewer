# Document Viewer (local_docviewer)

A Moodle plugin that converts office documents (Word, Excel, PowerPoint, etc.) to PDF and displays them as inline previews directly within the platform. Students can view documents without downloading them, while teachers retain full control over preview and download settings per resource.

## Features

- **Inline PDF preview** — Office documents are automatically converted and displayed in an embedded viewer.
- **Automatic interception** — Resource files on course pages open in the viewer instead of downloading.
- **Per-resource controls** — Teachers can toggle preview and download visibility for each resource file.
- **Content-hash caching** — Converted PDFs are cached to avoid repeated conversions; identical files are only converted once.
- **Scheduled cache cleanup** — A cron task automatically removes old cached PDFs (configurable max age).
- **Multi-language support** — English and Greek included; fully translatable via Moodle's AMOS.
- **Privacy compliant** — No personal data is stored.

## Requirements

- **Moodle 4.5+** (uses the Hook API introduced in Moodle 4.3, requires Moodle 5 for full course page integration)
- **LibreOffice** installed on the server (headless mode is used for conversion)
- PHP 8.1+

## Installation

### Step 1: Install the plugin

**Option A — ZIP upload:**
1. Download the plugin ZIP file.
2. Go to *Site administration > Plugins > Install plugins*.
3. Upload the ZIP and follow the prompts.

**Option B — Manual installation:**
1. Extract the plugin to `local/docviewer` within your Moodle installation directory.
2. Visit the site admin notifications page to trigger the database upgrade.

### Step 2: Install LibreOffice

LibreOffice must be installed on the server for document conversion to work. The plugin does **not** install it automatically.

**Debian/Ubuntu:**
```bash
sudo apt-get install -y libreoffice-core libreoffice-writer libreoffice-calc libreoffice-impress libreoffice-draw
```

**CentOS/RHEL:**
```bash
sudo yum install -y libreoffice-core libreoffice-writer libreoffice-calc libreoffice-impress libreoffice-draw
```

**Fedora:**
```bash
sudo dnf install -y libreoffice-core libreoffice-writer libreoffice-calc libreoffice-impress libreoffice-draw
```

### Step 3: Verify installation

Go to *Site administration > Plugins > Local plugins > Document Viewer*. The settings page shows whether LibreOffice was detected and its version.

## Configuration

| Setting | Description | Default |
|---|---|---|
| Enable Document Viewer | Master on/off switch | Enabled |
| LibreOffice path | Full path to the LibreOffice binary | `/usr/bin/libreoffice` |
| Supported formats | Comma-separated list of file extensions | `doc,docx,odt,rtf,xls,xlsx,ods,ppt,pptx,odp` |
| Max file size (MB) | Files larger than this are not converted | 50 |
| Cache max age (days) | Cached PDFs older than this are cleaned up | 30 |

## Usage

### For students
When a teacher uploads a supported document as a resource, clicking on it opens the inline PDF viewer instead of downloading the file. A "Download original" button allows downloading the original file format.

### For teachers / editors
On the course page, each supported resource shows two toggle icons (visible only to users with the `moodle/course:manageactivities` capability):

- **Eye icon** — Toggle document preview on/off for that resource.
- **Download icon** — Toggle the "Download original" button visibility.

Green = enabled, grey = disabled.

## How it works

1. **Hook interception**: The plugin registers two Moodle hooks:
   - `before_standard_top_of_body_html_generation` — On `mod/resource/view.php`, redirects to the viewer.
   - `before_footer_html_generation` — Injects JavaScript that intercepts clicks on the course page.

2. **Conversion**: When a document is opened, LibreOffice converts it to PDF in headless mode. The PDF is cached using the file's content hash.

3. **Viewing**: The PDF is served via a fetch/blob approach that avoids session header conflicts with the browser's built-in PDF viewer.

## Supported file formats

| Format | Extensions |
|---|---|
| Microsoft Word | `.doc`, `.docx` |
| Microsoft Excel | `.xls`, `.xlsx` |
| Microsoft PowerPoint | `.ppt`, `.pptx` |
| OpenDocument | `.odt`, `.ods`, `.odp` |
| Rich Text | `.rtf` |

Additional formats can be added via the admin settings.

## Cache management

Converted PDFs are stored in `$CFG->dataroot/docviewer/` using the content hash as the filename. This means:
- Identical files uploaded multiple times only occupy one cached copy.
- The scheduled task `\local_docviewer\task\cleanup_cache` runs daily at 03:30 and removes files older than the configured max age.

## Capabilities

| Capability | Description | Default roles |
|---|---|---|
| `local/docviewer:view` | View documents in the inline viewer | Guest, Student, Teacher, Manager |

## Privacy

This plugin implements the Moodle Privacy API as a null provider. It does not store, process, or export any personal user data. Cached PDFs are derived from course files and contain no user-specific information.

## License

This plugin is licensed under the [GNU General Public License v3 or later](https://www.gnu.org/copyleft/gpl.html).

## Support

Please report issues at the plugin's issue tracker.
