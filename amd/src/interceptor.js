/**
 * Document Viewer - Link Interceptor
 *
 * Intercepts clicks on supported document file links and redirects
 * them to the inline PDF viewer. Uses event delegation to survive
 * reactive component re-renders in Moodle 5.
 *
 * @module     local_docviewer/interceptor
 * @copyright  2026 Overpass Connect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var supportedExts = [];
    var viewerUrl = '';
    var wwwroot = '';
    var resourceMap = {};

    function getExtension(url) {
        var path = url.split('?')[0].split('#')[0];
        var decoded;
        try {
            decoded = decodeURIComponent(path);
        } catch (e) {
            decoded = path;
        }
        var parts = decoded.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    }

    function isPluginFileUrl(url) {
        return url.indexOf('/pluginfile.php/') !== -1 ||
               url.indexOf('/webservice/pluginfile.php/') !== -1;
    }

    function isSupportedFile(url) {
        return isPluginFileUrl(url) && supportedExts.indexOf(getExtension(url)) !== -1;
    }

    function buildViewerUrl(fileurl) {
        return viewerUrl + '?fileurl=' + encodeURIComponent(fileurl);
    }

    function processLink(link) {
        if (link.classList.contains('docviewer-processed') ||
            link.classList.contains('docviewer-download') ||
            link.closest('.docviewer-container')) {
            return;
        }
        var href = link.getAttribute('href');
        if (!href || !isSupportedFile(href)) {
            return;
        }
        link.classList.add('docviewer-processed');
        link.setAttribute('data-original-href', href);
        link.setAttribute('href', buildViewerUrl(href));

        var icon = document.createElement('i');
        icon.className = 'fa fa-eye docviewer-icon';
        icon.setAttribute('title', 'Preview document');
        link.appendChild(document.createTextNode(' '));
        link.appendChild(icon);
    }

    function processContainer(container) {
        if (!container || !container.querySelectorAll) {
            return;
        }
        var links = container.querySelectorAll('a[href*="pluginfile.php"]');
        for (var i = 0; i < links.length; i++) {
            processLink(links[i]);
        }
    }

    return {
        init: function(params) {
            supportedExts = params.extensions || [];
            viewerUrl = params.viewerurl || '';
            wwwroot = params.wwwroot || '';

            processContainer(document.body);

            var observer = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.tagName === 'A') {
                                processLink(node);
                            } else {
                                processContainer(node);
                            }
                        }
                    }
                }
            });

            observer.observe(document.body, {childList: true, subtree: true});
        },

        initResources: function(params) {
            var resources = params.resources || [];
            var vUrl = params.viewerurl || viewerUrl;

            resources.forEach(function(res) {
                resourceMap[String(res.cmid)] = res;
            });

            document.addEventListener('click', function(e) {
                var target = e.target;
                // Don't intercept clicks on action menus, dropdowns, or toggle buttons.
                if (target.closest('.docviewer-toggle') ||
                    target.closest('.activity-actions') ||
                    target.closest('.cm_action_menu') ||
                    target.closest('.action-menu') ||
                    target.closest('.dropdown-menu') ||
                    target.closest('.dropdown-toggle')) {
                    return;
                }
                var moduleEl = target.closest('.activity.resource[data-id]');
                if (!moduleEl) {
                    return;
                }
                var cmid = moduleEl.getAttribute('data-id');
                if (!cmid || !resourceMap[cmid]) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                window.location.href = vUrl + '?fileurl=' + encodeURIComponent(resourceMap[cmid].fileurl);
            }, true);

            resources.forEach(function(res) {
                var moduleEl = document.getElementById('module-' + res.cmid);
                if (!moduleEl) {
                    return;
                }
                var nameArea = moduleEl.querySelector('.activityname') ||
                               moduleEl.querySelector('.instancename');
                if (nameArea && !nameArea.querySelector('.docviewer-icon')) {
                    var icon = document.createElement('i');
                    icon.className = 'fa fa-eye docviewer-icon';
                    icon.setAttribute('title', 'Preview document');
                    nameArea.appendChild(document.createTextNode(' '));
                    nameArea.appendChild(icon);
                }
            });
        },

        /**
         * Initialize toggle buttons for teachers to enable/disable preview per resource.
         */
        initToggles: function(params) {
            var toggles = params.toggles || [];
            var toggleUrl = params.toggleurl || '';
            var sesskey = params.sesskey || '';
            var enableLabel = params.enableLabel || 'Enable preview';
            var disableLabel = params.disableLabel || 'Disable preview';
            var showDownloadLabel = params.showDownloadLabel || 'Show download button';
            var hideDownloadLabel = params.hideDownloadLabel || 'Hide download button';

            toggles.forEach(function(item) {
                var moduleEl = document.getElementById('module-' + item.cmid);
                if (!moduleEl) {
                    return;
                }
                var actionsArea = moduleEl.querySelector('.activity-actions .actions') ||
                                  moduleEl.querySelector('.actions') ||
                                  moduleEl.querySelector('.activity-actions');
                if (!actionsArea) {
                    actionsArea = moduleEl.querySelector('.activityname') ||
                                  moduleEl.querySelector('.instancename');
                }
                if (!actionsArea || actionsArea.querySelector('.docviewer-toggle-preview')) {
                    return;
                }

                // Preview toggle button (eye icon).
                var previewBtn = document.createElement('a');
                previewBtn.className = 'docviewer-toggle docviewer-toggle-preview';
                previewBtn.href = '#';
                previewBtn.style.cssText = 'margin-left:8px;cursor:pointer;font-size:0.85em;opacity:0.7;';
                previewBtn.title = item.excluded ? enableLabel : disableLabel;

                var previewIcon = document.createElement('i');
                previewIcon.className = item.excluded ? 'fa fa-eye-slash text-muted' : 'fa fa-eye text-success';
                previewBtn.appendChild(previewIcon);

                previewBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var url = toggleUrl + '?cmid=' + item.cmid + '&type=preview&sesskey=' + sesskey;
                    fetch(url, {credentials: 'same-origin'})
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            item.excluded = (data.state === 'preview_disabled');
                            previewIcon.className = item.excluded
                                ? 'fa fa-eye-slash text-muted'
                                : 'fa fa-eye text-success';
                            previewBtn.title = item.excluded ? enableLabel : disableLabel;

                            if (item.excluded) {
                                delete resourceMap[String(item.cmid)];
                            }
                            window.location.reload();
                        });
                });

                actionsArea.appendChild(previewBtn);

                // Download toggle button (download icon).
                var downloadBtn = document.createElement('a');
                downloadBtn.className = 'docviewer-toggle docviewer-toggle-download';
                downloadBtn.href = '#';
                downloadBtn.style.cssText = 'margin-left:6px;cursor:pointer;font-size:0.85em;opacity:0.7;';
                downloadBtn.title = item.nodownload ? showDownloadLabel : hideDownloadLabel;

                var downloadIcon = document.createElement('i');
                downloadIcon.className = item.nodownload ? 'fa fa-download text-muted' : 'fa fa-download text-success';
                downloadBtn.appendChild(downloadIcon);

                downloadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var url = toggleUrl + '?cmid=' + item.cmid + '&type=download&sesskey=' + sesskey;
                    fetch(url, {credentials: 'same-origin'})
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            item.nodownload = (data.state === 'download_hidden');
                            downloadIcon.className = item.nodownload
                                ? 'fa fa-download text-muted'
                                : 'fa fa-download text-success';
                            downloadBtn.title = item.nodownload ? showDownloadLabel : hideDownloadLabel;
                        });
                });

                actionsArea.appendChild(downloadBtn);
            });
        }
    };
});
