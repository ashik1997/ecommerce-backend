(function (window, document) {
    'use strict';

    var defaults = {
        mode: 'browse',
        selected: [],
        max: null,
        purpose: null,
        directory: null,
        width: null,
        height: null,
        onSelect: null
    };

    var state = {
        isOpen: false,
        options: Object.assign({}, defaults),
        files: [],
        selectedIds: [],
        selectedMap: {},
        selectedFiles: {},
        currentFile: null,
        selectedFolderId: null,
        selectedFolder: null,
        folderTree: [],
        folderMap: {},
        expandedFolderIds: {},
        page: 1,
        lastPage: 1,
        loading: false,
        foldersLoaded: false
    };

    var els = {};

    function client() {
        return window.AppAxios || window.axios || null;
    }

    function init() {
        els.modal = document.getElementById('mediaManagerModal');
        if (!els.modal) return;

        els.grid = els.modal.querySelector('[data-media-manager-grid]');
        els.status = els.modal.querySelector('[data-media-manager-status]');
        els.details = els.modal.querySelector('[data-media-manager-details]');
        els.search = els.modal.querySelector('[data-media-manager-search]');
        els.directory = els.modal.querySelector('[data-media-manager-directory]');
        els.folderTree = els.modal.querySelector('[data-media-manager-folder-tree]');
        els.folderRoot = els.modal.querySelector('[data-media-manager-folder-root]');
        els.folderRefresh = els.modal.querySelector('[data-media-manager-folder-refresh]');
        els.folderCreate = els.modal.querySelector('[data-media-manager-folder-create]');
        els.folderName = els.modal.querySelector('[data-media-manager-folder-name]');
        els.folderParent = els.modal.querySelector('[data-media-manager-folder-parent]');
        els.dateFrom = els.modal.querySelector('[data-media-manager-date-from]');
        els.dateTo = els.modal.querySelector('[data-media-manager-date-to]');
        els.disk = els.modal.querySelector('[data-media-manager-disk]');
        els.sizePreset = els.modal.querySelector('[data-media-manager-size-preset]');
        els.sizeHint = els.modal.querySelector('[data-media-manager-size-hint]');
        els.prev = els.modal.querySelector('[data-media-manager-prev]');
        els.next = els.modal.querySelector('[data-media-manager-next]');
        els.page = els.modal.querySelector('[data-media-manager-page]');
        els.select = els.modal.querySelector('[data-media-manager-select]');
        els.summary = els.modal.querySelector('[data-media-manager-selection-summary]');
        els.fileInput = els.modal.querySelector('[data-media-manager-file-input]');
        els.uploads = els.modal.querySelector('[data-media-manager-uploads]');

        bindEvents();
    }

    function bindEvents() {
        var floating = document.querySelector('[data-media-manager-floating]');
        if (floating) {
            floating.addEventListener('click', function () {
                open({ mode: 'browse' });
            });
        }

        els.modal.querySelectorAll('[data-media-manager-close]').forEach(function (button) {
            button.addEventListener('click', close);
        });

        els.modal.querySelectorAll('[data-media-manager-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                activateTab(button.getAttribute('data-media-manager-tab'));
            });
        });

        els.modal.querySelector('[data-media-manager-refresh]').addEventListener('click', function () {
            loadLibrary(1);
        });

        els.search.addEventListener('input', debounce(function () {
            loadLibrary(1);
        }, 350));

        [els.directory, els.dateFrom, els.dateTo].forEach(function (input) {
            input.addEventListener('change', function () {
                if (input === els.directory) syncSelectedFolderFromDirectory();
                loadLibrary(1);
            });
        });

        if (els.sizePreset) {
            els.sizePreset.addEventListener('change', updateSizeHint);
        }

        if (els.folderRoot) {
            els.folderRoot.addEventListener('click', function () {
                selectFolder(null);
            });
        }

        if (els.folderRefresh) {
            els.folderRefresh.addEventListener('click', function () {
                loadFolders(true);
            });
        }

        if (els.folderCreate) {
            els.folderCreate.addEventListener('submit', createFolder);
        }

        els.prev.addEventListener('click', function () {
            if (state.page > 1) loadLibrary(state.page - 1);
        });

        els.next.addEventListener('click', function () {
            if (state.page < state.lastPage) loadLibrary(state.page + 1);
        });

        els.select.addEventListener('click', confirmSelection);

        els.fileInput.addEventListener('change', function (event) {
            uploadFiles(Array.prototype.slice.call(event.target.files || []));
            event.target.value = '';
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && state.isOpen) close();
        });
    }

    function resetState(options) {
        state.options = Object.assign({}, defaults, options || {});
        state.selectedIds = Array.isArray(state.options.selected) ? state.options.selected.slice() : [];
        state.selectedMap = {};
        state.selectedFiles = {};
        state.selectedIds.forEach(function (id) {
            state.selectedMap[String(id)] = true;
        });
        state.currentFile = null;
        state.selectedFolderId = null;
        state.selectedFolder = null;
        state.files = [];
        state.page = 1;
        state.lastPage = 1;
    }

    function open(options) {
        if (!els.modal) init();
        if (!els.modal) return;

        resetState(options);
        state.isOpen = true;
        els.modal.classList.add('is-open');
        els.modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('media-manager-open');
        activateTab('library');
        hydrateFilters();
        hydrateUploadPreset();
        loadFolders();
        loadSelectedFiles();
        loadLibrary(1);
        renderSelectionSummary();
    }

    function close() {
        if (!els.modal) return;
        state.isOpen = false;
        state.options.onSelect = null;
        els.modal.classList.remove('is-open');
        els.modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('media-manager-open');
    }

    function activateTab(name) {
        els.modal.querySelectorAll('[data-media-manager-tab]').forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-media-manager-tab') === name);
        });
        els.modal.querySelectorAll('[data-media-manager-panel]').forEach(function (panel) {
            panel.classList.toggle('is-active', panel.getAttribute('data-media-manager-panel') === name);
        });
    }

    function hydrateFilters() {
        els.search.value = '';
        els.dateFrom.value = '';
        els.dateTo.value = '';
        if (state.options.directory) {
            els.directory.value = state.options.directory;
        } else {
            els.directory.value = '';
        }
        syncSelectedFolderFromDirectory();
        renderFolderTree();
    }

    function hydrateUploadPreset() {
        if (!els.sizePreset) return;

        var selectedKey = state.options.sizePreset || state.options.size_preset || 'square';
        if (els.sizePreset.querySelector('option[value="' + cssEscape(selectedKey) + '"]')) {
            els.sizePreset.value = selectedKey;
        } else {
            els.sizePreset.value = 'square';
        }
        updateSizeHint();
    }

    function loadFolders(force) {
        if (state.foldersLoaded && !force) return;
        var http = client();
        if (!http) return;

        if (els.folderTree && !state.foldersLoaded) {
            els.folderTree.innerHTML = '<div class="media-manager__folder-empty">Loading folders...</div>';
        }

        http.get('/media/library/folders').then(function (response) {
            var data = (((response || {}).data || {}).data || {});
            var folders = data.folders || [];
            var paths = data.paths || [];
            var seen = {};
            var selectedBefore = state.selectedFolderId;

            els.directory.innerHTML = '<option value="">All folders</option>';
            state.folderTree = data.tree || [];
            state.folderMap = {};
            folders.forEach(function (folder) {
                var value = folder.saved_name_into_storage || folder.name || '';
                if (!value || seen[value]) return;
                seen[value] = true;
                state.folderMap[String(folder.id)] = folder;
                var option = document.createElement('option');
                option.value = value;
                option.textContent = folder.name || value;
                option.setAttribute('data-media-folder-id', folder.id || '');
                els.directory.appendChild(option);
            });
            paths.forEach(function (path) {
                var value = directoryValue(path);
                if (seen[value]) return;
                seen[value] = true;
                var option = document.createElement('option');
                option.value = value;
                option.textContent = value === path ? path : value + ' (' + path + ')';
                els.directory.appendChild(option);
            });
            state.foldersLoaded = true;
            if (selectedBefore && state.folderMap[String(selectedBefore)]) {
                selectFolder(state.folderMap[String(selectedBefore)], false);
            } else if (state.options.directory) {
                els.directory.value = state.options.directory;
                syncSelectedFolderFromDirectory();
            } else {
                selectFolder(null, false);
            }
            renderFolderTree();
        }).catch(function () {});
    }

    function loadSelectedFiles() {
        var http = client();
        if (!http || !state.selectedIds.length) return;

        state.selectedIds.forEach(function (id) {
            if (state.selectedFiles[String(id)]) return;
            http.get('/media/library/' + encodeURIComponent(id)).then(function (response) {
                var file = ((response || {}).data || {}).data;
                if (!file || !file.id) return;
                state.selectedFiles[String(file.id)] = file;
            }).catch(function () {});
        });
    }

    function loadLibrary(page) {
        var http = client();
        if (!http) {
            setStatus('Axios is required to load files.');
            return;
        }

        state.loading = true;
        setStatus('Loading files...');

        http.get('/media/library', {
            params: {
                page: page || 1,
                per_page: 24,
                search: els.search.value || '',
                directory: selectedDirectoryValue() || '',
                media_folder_id: selectedFolderId() || '',
                date_from: els.dateFrom.value || '',
                date_to: els.dateTo.value || ''
            }
        }).then(function (response) {
            var payload = response.data || {};
            state.files = payload.data || [];
            state.files.forEach(function (file) {
                if (state.selectedMap[String(file.id)]) {
                    state.selectedFiles[String(file.id)] = file;
                }
            });
            state.page = (payload.meta || {}).current_page || 1;
            state.lastPage = (payload.meta || {}).last_page || 1;
            state.loading = false;
            renderGrid();
            renderPager();
            setStatus(state.files.length ? '' : 'No files found.');
        }).catch(function () {
            state.loading = false;
            renderGrid();
            setStatus('Could not load media files.');
        });
    }

    function renderGrid() {
        els.grid.innerHTML = '';
        state.files.forEach(function (file) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'media-manager__item' + (state.selectedMap[String(file.id)] ? ' is-selected' : '');
            item.setAttribute('data-media-id', file.id);
            item.innerHTML = [
                '<span class="media-manager__check"><i class="fas fa-check"></i></span>',
                '<span class="media-manager__thumb">',
                file.url ? '<img src="' + escapeAttr(file.url) + '" alt="">' : '<i class="fas fa-file-image"></i>',
                '</span>',
                '<span class="media-manager__item-name">' + escapeHtml(file.original_name || file.file_name || file.path || 'Untitled') + '</span>'
            ].join('');
            item.addEventListener('click', function () {
                toggleSelect(file);
            });
            els.grid.appendChild(item);
        });
    }

    function toggleSelect(file) {
        var id = String(file.id);
        state.currentFile = file;

        if (state.options.mode === 'browse') {
            state.selectedIds = [file.id];
            state.selectedMap = {};
            state.selectedFiles = {};
            state.selectedMap[id] = true;
            state.selectedFiles[id] = file;
        } else if (state.options.mode === 'multiple') {
            if (state.selectedMap[id]) {
                delete state.selectedMap[id];
                delete state.selectedFiles[id];
                state.selectedIds = state.selectedIds.filter(function (selectedId) {
                    return String(selectedId) !== id;
                });
            } else if (!state.options.max || state.selectedIds.length < state.options.max) {
                state.selectedMap[id] = true;
                state.selectedFiles[id] = file;
                state.selectedIds.push(file.id);
            }
        } else {
            state.selectedIds = [file.id];
            state.selectedMap = {};
            state.selectedFiles = {};
            state.selectedMap[id] = true;
            state.selectedFiles[id] = file;
        }

        renderGrid();
        renderDetails(file);
        renderSelectionSummary();
    }

    function renderDetails(file) {
        els.details.innerHTML = [
            file.url ? '<img src="' + escapeAttr(file.url) + '" alt="">' : '',
            detailRow('File name', file.original_name || file.file_name || ''),
            detailRow('Dimensions', file.width && file.height ? file.width + ' x ' + file.height : 'Unknown'),
            detailRow('Size', formatBytes(file.size)),
            detailRow('Uploaded', file.created_at || ''),
            detailRow('Folder', file.folder_path || ''),
            '<div class="media-manager__detail-row" data-media-manager-usage><strong>Usage</strong><span>Loading usage...</span></div>',
            copyRow('URL', file.url || ''),
            copyRow('Path', file.path || ''),
            '<div class="media-manager__danger-zone"><button type="button" class="media-manager__btn media-manager__btn--danger" data-media-manager-delete="' + escapeAttr(file.id) + '"><i class="fas fa-trash"></i> Delete permanently</button></div>'
        ].join('');

        els.details.querySelectorAll('[data-media-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyText(button.getAttribute('data-media-copy'));
            });
        });

        var deleteButton = els.details.querySelector('[data-media-manager-delete]');
        if (deleteButton) {
            deleteButton.addEventListener('click', function () {
                deleteSelectedFile(file);
            });
        }

        loadUsage(file);
    }

    function deleteSelectedFile(file) {
        if (!file || !file.id) return;

        var message = 'Delete this file permanently? This cannot be undone.';
        var confirmed = window.Swal
            ? null
            : window.confirm(message);

        if (window.Swal) {
            window.Swal.fire({
                title: 'Delete permanently?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Delete'
            }).then(function (result) {
                if (result.isConfirmed) {
                    performDelete(file);
                }
            });
            return;
        }

        if (confirmed) {
            performDelete(file);
        }
    }

    function performDelete(file) {
        var http = client();
        if (!http) {
            notify('Axios is required to delete files.', 'error');
            return;
        }

        http.delete('/media/library/' + encodeURIComponent(file.id)).then(function (response) {
            var payload = response.data || {};
            notify(payload.message || 'File deleted permanently.', 'success');
            delete state.selectedMap[String(file.id)];
            delete state.selectedFiles[String(file.id)];
            state.selectedIds = state.selectedIds.filter(function (id) {
                return String(id) !== String(file.id);
            });
            state.currentFile = null;
            els.details.innerHTML = '<div class="media-manager__empty-details">Select a file to view details.</div>';
            loadLibrary(state.page);
            renderSelectionSummary();
        }).catch(function (error) {
            var message = (((error || {}).response || {}).data || {}).message || 'Could not delete this file.';
            notify(message, 'error');
        });
    }

    function loadUsage(file) {
        var usageEl = els.details.querySelector('[data-media-manager-usage]');
        var http = client();
        if (!usageEl || !http || !file || !file.id) return;

        http.get('/media/' + encodeURIComponent(file.id) + '/usage').then(function (response) {
            var payload = response.data || {};
            var usage = payload.data || [];
            var count = ((payload.meta || {}).count != null) ? payload.meta.count : usage.length;
            usageEl.innerHTML = '<strong>Usage</strong>' + renderUsageSummary(count, usage);
        }).catch(function () {
            usageEl.innerHTML = '<strong>Usage</strong><span>Usage unavailable</span>';
        });
    }

    function renderUsageSummary(count, usage) {
        if (!count) {
            return '<span>Not tracked as used</span>';
        }

        var items = usage.slice(0, 4).map(function (row) {
            return '<li>' + escapeHtml(shortModelName(row.model)) + ' #' + escapeHtml(row.model_id || '-') + ' - ' + escapeHtml(row.col_name || '-') + '</li>';
        }).join('');

        var more = count > 4 ? '<small>+' + (count - 4) + ' more</small>' : '';
        return '<span>' + count + ' active use' + (count > 1 ? 's' : '') + '</span><ul class="media-manager__usage-list">' + items + '</ul>' + more;
    }

    function shortModelName(model) {
        if (!model) return 'Record';
        var parts = String(model).split('\\');
        return parts[parts.length - 1] || model;
    }

    function detailRow(label, value) {
        return '<div class="media-manager__detail-row"><strong>' + escapeHtml(label) + '</strong><span>' + escapeHtml(value || '-') + '</span></div>';
    }

    function copyRow(label, value) {
        return '<div class="media-manager__detail-row"><strong>' + escapeHtml(label) + '</strong><div class="media-manager__copy"><input readonly value="' + escapeAttr(value) + '"><button type="button" class="media-manager__btn media-manager__btn--light" data-media-copy="' + escapeAttr(value) + '"><i class="fas fa-copy"></i></button></div></div>';
    }

    function renderPager() {
        els.page.textContent = 'Page ' + state.page + ' of ' + state.lastPage;
        els.prev.disabled = state.page <= 1;
        els.next.disabled = state.page >= state.lastPage;
    }

    function renderSelectionSummary() {
        var count = state.selectedIds.length;
        var browse = state.options.mode === 'browse';
        els.summary.textContent = count ? count + ' file' + (count > 1 ? 's' : '') + ' selected' : 'No files selected';
        els.select.style.display = browse ? 'none' : '';
        els.select.disabled = !count;
    }

    function uploadFiles(files) {
        if (!files.length) return;
        activateTab('upload');
        files.forEach(uploadFile);
    }

    function uploadFile(file) {
        var row = document.createElement('div');
        row.className = 'media-manager__upload-row';
        row.innerHTML = '<span>' + escapeHtml(file.name) + '</span><div class="media-manager__upload-progress"><span></span></div><small>0%</small>';
        els.uploads.prepend(row);

        var bar = row.querySelector('.media-manager__upload-progress span');
        var label = row.querySelector('small');
        var formData = new FormData();
        formData.append('file', file);
        formData.append('disk', els.disk ? els.disk.value : 'public');
        if (selectedFolderId()) formData.append('media_folder_id', selectedFolderId());
        var uploadSize = selectedUploadSize();
        if (uploadSize.width) formData.append('width', uploadSize.width);
        if (uploadSize.height) formData.append('height', uploadSize.height);
        if (uploadSize.preset) formData.append('size_preset', uploadSize.preset);
        var directory = selectedDirectoryValue() || state.options.directory || '';
        if (directory) formData.append('directory', directory);

        var xhr = new XMLHttpRequest();
        var csrf = document.querySelector('meta[name="csrf-token"]');
        xhr.upload.addEventListener('progress', function (event) {
            if (!event.lengthComputable) return;
            var percent = Math.round((event.loaded / event.total) * 100);
            bar.style.width = percent + '%';
            label.textContent = percent + '%';
        });
        xhr.addEventListener('load', function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                label.textContent = 'Done';
                bar.style.width = '100%';
                loadLibrary(1);
                activateTab('library');
                return;
            }
            label.textContent = 'Failed';
        });
        xhr.addEventListener('error', function () {
            label.textContent = 'Failed';
        });
        xhr.open('POST', '/media/upload');
        xhr.setRequestHeader('Accept', 'application/json');
        if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf.getAttribute('content'));
        xhr.send(formData);
    }

    function selectedFolderId() {
        if (state.selectedFolderId) return state.selectedFolderId;
        if (!els.directory) return state.selectedFolderId;
        var option = els.directory.options[els.directory.selectedIndex];
        return option ? option.getAttribute('data-media-folder-id') : state.selectedFolderId;
    }

    function selectedDirectoryValue() {
        if (state.selectedFolder) {
            return state.selectedFolder.saved_name_into_storage || state.selectedFolder.name || '';
        }
        return els.directory ? (els.directory.value || '') : '';
    }

    function syncSelectedFolderFromDirectory() {
        if (!els.directory) return;
        var option = els.directory.options[els.directory.selectedIndex];
        var folderId = option ? option.getAttribute('data-media-folder-id') : null;
        if (folderId && state.folderMap[String(folderId)]) {
            state.selectedFolderId = String(folderId);
            state.selectedFolder = state.folderMap[String(folderId)];
        } else {
            state.selectedFolderId = null;
            state.selectedFolder = null;
        }
        updateFolderParentHint();
        renderFolderTree();
    }

    function selectFolder(folder, reload) {
        state.selectedFolder = folder || null;
        state.selectedFolderId = folder && folder.id ? String(folder.id) : null;
        if (folder && folder.id) {
            state.expandedFolderIds[String(folder.id)] = true;
        }
        if (els.directory) {
            els.directory.value = folder ? (folder.saved_name_into_storage || folder.name || '') : '';
        }
        updateFolderParentHint();
        renderFolderTree();
        if (reload !== false) loadLibrary(1);
    }

    function updateFolderParentHint() {
        if (!els.folderParent) return;
        els.folderParent.textContent = 'Create inside: ' + (state.selectedFolder ? state.selectedFolder.name : 'All folders');
    }

    function renderFolderTree() {
        if (!els.folderTree) return;
        if (els.folderRoot) {
            els.folderRoot.classList.toggle('is-active', !state.selectedFolderId);
        }
        if (!state.folderTree.length) {
            els.folderTree.innerHTML = '<div class="media-manager__folder-empty">No folders found.</div>';
            return;
        }

        els.folderTree.innerHTML = renderFolderNodes(state.folderTree, 0);
        els.folderTree.querySelectorAll('[data-media-folder-toggle]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                var id = button.getAttribute('data-media-folder-toggle');
                state.expandedFolderIds[id] = !state.expandedFolderIds[id];
                renderFolderTree();
            });
        });
        els.folderTree.querySelectorAll('[data-media-folder-select]').forEach(function (button) {
            button.addEventListener('click', function () {
                var id = button.getAttribute('data-media-folder-select');
                selectFolder(state.folderMap[String(id)] || null);
            });
        });
    }

    function renderFolderNodes(nodes, level) {
        return nodes.map(function (folder) {
            state.folderMap[String(folder.id)] = folder;
            var children = folder.children || [];
            var hasChildren = children.length > 0;
            var expanded = !!state.expandedFolderIds[String(folder.id)] || level === 0;
            var active = String(state.selectedFolderId || '') === String(folder.id);
            var toggle = hasChildren
                ? '<button type="button" class="media-manager__folder-toggle" data-media-folder-toggle="' + escapeAttr(folder.id) + '"><i class="fas fa-chevron-' + (expanded ? 'down' : 'right') + '"></i></button>'
                : '<span class="media-manager__folder-toggle media-manager__folder-toggle--empty"></span>';

            var row = '<div class="media-manager__folder-row' + (active ? ' is-active' : '') + '" style="--folder-depth:' + level + '">' +
                toggle +
                '<button type="button" class="media-manager__folder-name" data-media-folder-select="' + escapeAttr(folder.id) + '">' +
                '<i class="fas fa-folder"></i><span>' + escapeHtml(folder.name || folder.saved_name_into_storage || 'Untitled') + '</span>' +
                '</button>' +
                '</div>';
            var branch = hasChildren && expanded
                ? '<div class="media-manager__folder-children">' + renderFolderNodes(children, level + 1) + '</div>'
                : '';

            return row + branch;
        }).join('');
    }

    function createFolder(event) {
        event.preventDefault();
        var http = client();
        if (!http || !els.folderName) return;

        var name = (els.folderName.value || '').trim();
        if (!name) {
            notify('Folder name is required', 'error');
            return;
        }

        var button = els.folderCreate.querySelector('button[type="submit"]');
        if (button) button.disabled = true;

        http.post('/media/library/folders', {
            name: name,
            parent_id: state.selectedFolderId || null
        }).then(function (response) {
            var folder = ((response || {}).data || {}).data;
            els.folderName.value = '';
            if (folder && folder.parent_id) {
                state.expandedFolderIds[String(folder.parent_id)] = true;
            }
            if (folder && folder.id) {
                state.expandedFolderIds[String(folder.id)] = true;
            }
            state.foldersLoaded = false;
            loadFolders(true);
            if (folder && folder.id) {
                setTimeout(function () {
                    selectFolder(state.folderMap[String(folder.id)] || folder);
                }, 150);
            }
            notify('Folder created', 'success');
        }).catch(function (error) {
            var message = (((error || {}).response || {}).data || {}).message || 'Could not create folder';
            notify(message, 'error');
        }).finally(function () {
            if (button) button.disabled = false;
        });
    }

    function selectedUploadSize() {
        var option = els.sizePreset ? els.sizePreset.options[els.sizePreset.selectedIndex] : null;
        var width = option ? parseInt(option.getAttribute('data-width') || '0', 10) : 0;
        var height = option ? parseInt(option.getAttribute('data-height') || '0', 10) : 0;

        return {
            preset: option ? option.value : '',
            width: width || state.options.width || null,
            height: height || state.options.height || null
        };
    }

    function updateSizeHint() {
        if (!els.sizeHint || !els.sizePreset) return;
        var option = els.sizePreset.options[els.sizePreset.selectedIndex];
        if (!option) {
            els.sizeHint.textContent = '';
            return;
        }

        var label = option.textContent.replace(/\s+/g, ' ').trim();
        els.sizeHint.textContent = label + ' images will be resized and cropped to fit.';
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/"/g, '\\"');
    }

    function confirmSelection() {
        if (typeof state.options.onSelect !== 'function') return;
        var selected = state.selectedIds.map(function (id) {
            return state.selectedFiles[String(id)] || state.files.find(function (file) {
                return String(file.id) === String(id);
            });
        }).filter(Boolean);
        state.options.onSelect(selected);
        close();
    }

    function setStatus(message) {
        els.status.textContent = message || '';
    }

    function directoryValue(path) {
        var parts = String(path || '').split('/').filter(Boolean);
        var mediaIndex = parts.indexOf('media');
        if (mediaIndex >= 0 && parts.length > mediaIndex + 1) {
            return parts[mediaIndex + 1];
        }
        return path;
    }

    function copyText(text) {
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
            return;
        }
        var input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }

    function notify(message, type) {
        if (window.toastr && type === 'success') {
            window.toastr.success(message);
            return;
        }
        if (window.toastr) {
            window.toastr.error(message);
            return;
        }
        alert(message);
    }

    function formatBytes(bytes) {
        if (!bytes) return '-';
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + sizes[i];
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function debounce(fn, wait) {
        var timeout;
        return function () {
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                fn.apply(null, args);
            }, wait);
        };
    }

    window.MediaManager = {
        open: open,
        close: close,
        state: state
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document);
