<div id="mediaManagerModal" class="media-manager" aria-hidden="true">
    <div class="media-manager__backdrop" data-media-manager-close></div>
    <div class="media-manager__dialog" role="dialog" aria-modal="true" aria-labelledby="mediaManagerTitle">
        <div class="media-manager__header">
            <div>
                <h5 id="mediaManagerTitle" class="media-manager__title">Media Library</h5>
                <p class="media-manager__subtitle">Browse existing files or upload new images.</p>
            </div>
            <a href="{{ route('media.picker-pilot') }}" class="media-manager__pilot-link" target="_blank">
                <i class="fas fa-vial"></i>
                Test file picker
            </a>
            <button type="button" class="media-manager__icon-btn" data-media-manager-close aria-label="Close media manager">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="media-manager__tabs" role="tablist">
            <button type="button" class="media-manager__tab is-active" data-media-manager-tab="library">Media Library</button>
            <button type="button" class="media-manager__tab" data-media-manager-tab="upload">Upload Files</button>
        </div>

        <div class="media-manager__body">
            <section class="media-manager__panel is-active" data-media-manager-panel="library">
                <div class="media-manager__toolbar">
                    <div class="media-manager__search">
                        <i class="fas fa-search"></i>
                        <input type="search" data-media-manager-search placeholder="Search files">
                    </div>
                    <select data-media-manager-directory class="media-manager__legacy-directory">
                        <option value="">All folders</option>
                    </select>
                    <input type="date" data-media-manager-date-from title="From date">
                    <input type="date" data-media-manager-date-to title="To date">
                    <button type="button" class="media-manager__btn media-manager__btn--light" data-media-manager-refresh>
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <div class="media-manager__content">
                    <aside class="media-manager__folders">
                        <div class="media-manager__folders-header">
                            <strong>Folders</strong>
                            <button type="button" class="media-manager__icon-btn" data-media-manager-folder-refresh title="Refresh folders">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <button type="button" class="media-manager__folder-root is-active" data-media-manager-folder-root>
                            <i class="fas fa-folder"></i>
                            <span>All folders</span>
                        </button>
                        <div class="media-manager__folder-tree" data-media-manager-folder-tree>
                            <div class="media-manager__folder-empty">Loading folders...</div>
                        </div>
                        <form class="media-manager__folder-create" data-media-manager-folder-create>
                            <label>
                                New folder
                                <input type="text" data-media-manager-folder-name placeholder="Folder name" maxlength="150">
                            </label>
                            <button type="submit" class="media-manager__btn media-manager__btn--primary">
                                <i class="fas fa-plus"></i>
                                Create
                            </button>
                            <small data-media-manager-folder-parent>Create inside: All folders</small>
                        </form>
                    </aside>
                    <div class="media-manager__library">
                        <div class="media-manager__status" data-media-manager-status>Loading files...</div>
                        <div class="media-manager__grid" data-media-manager-grid></div>
                        <div class="media-manager__pager">
                            <button type="button" class="media-manager__btn media-manager__btn--light" data-media-manager-prev disabled>Previous</button>
                            <span data-media-manager-page>Page 1 of 1</span>
                            <button type="button" class="media-manager__btn media-manager__btn--light" data-media-manager-next disabled>Next</button>
                        </div>
                    </div>

                    <aside class="media-manager__details" data-media-manager-details>
                        <div class="media-manager__empty-details">Select a file to view details.</div>
                    </aside>
                </div>
            </section>

            <section class="media-manager__panel" data-media-manager-panel="upload">
                <div class="media-manager__upload-options">
                    <label>
                        Size
                        <select data-media-manager-size-preset>
                            @foreach (config('media.size_presets', []) as $presetKey => $preset)
                                <option value="{{ $presetKey }}"
                                    data-width="{{ (int) ($preset['width'] ?? 0) }}"
                                    data-height="{{ (int) ($preset['height'] ?? 0) }}"
                                    @selected($presetKey === 'square')>
                                    {{ $preset['label'] ?? ucfirst($presetKey) }} ({{ (int) ($preset['width'] ?? 0) }}x{{ (int) ($preset['height'] ?? 0) }})
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Storage
                        <select data-media-manager-disk>
                            <option value="public" selected>Local public storage</option>
                        </select>
                    </label>
                    <span data-media-manager-upload-hint>Max upload: {{ (int) config('media.max_upload_mb', 5) }}MB</span>
                </div>
                <div class="media-manager__upload" data-media-manager-dropzone>
                    <i class="fas fa-cloud-upload-alt"></i>
                    <strong>Drop images here or click to upload</strong>
                    <span data-media-manager-size-hint>Square images will be resized to 800x800.</span>
                    <span>JPG, PNG, GIF, and WEBP up to {{ (int) config('media.max_upload_mb', 5) }}MB.</span>
                    <input type="file" data-media-manager-file-input accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" multiple>
                </div>
                <div class="media-manager__uploads" data-media-manager-uploads></div>
            </section>
        </div>

        <div class="media-manager__footer">
            <span data-media-manager-selection-summary>No files selected</span>
            <div>
                <button type="button" class="media-manager__btn media-manager__btn--light" data-media-manager-close>Close</button>
                <button type="button" class="media-manager__btn media-manager__btn--primary" data-media-manager-select disabled>Select</button>
            </div>
        </div>
    </div>
</div>

<button type="button" class="media-manager-floating d-none" data-media-manager-floating aria-label="Open files">
    <i class="fas fa-folder-open"></i>
    <span>Files</span>
</button>
