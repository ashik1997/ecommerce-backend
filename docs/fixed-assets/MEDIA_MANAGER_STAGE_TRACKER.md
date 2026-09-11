# Media Manager Stage Tracker

Last updated: 2026-06-06

## Current Status

| Stage | Scope | Status |
| --- | --- | --- |
| Stage 0 | Existing media-system audit | Complete |
| Stage 1 | Safe global media-manager foundation | Complete |
| Stage 2 | Detach-only media-picker field component | Complete |
| Stage 3 | Safe picker pilot page | Complete |
| Stage 3.5 | Product image/gallery media-manager integration | Complete |
| Stage 4 | Product media usage tracking and modal usage display | Complete |
| Stage 5 | FTP-first upload storage and folder foundation | Complete |
| Stage 6 | Guarded permanent file delete | Complete |
| Stage 7 | Modal folder-tree redesign | Pending |

## Stage 0 Baseline

Existing live media behavior preserved:

```text
routes/mediaRoutes.php
App\Http\Controllers\MediaUploaderController
App\Models\MediaFile
App\Models\MediaFolder
App\Models\MediaInUse
resources/views/backend/components/image_upload.blade.php
resources/views/backend/components/image_upload_v2.blade.php
```

Existing upload/delete routes were not changed:

```text
POST   /media/upload
DELETE /media/revert
GET    /media/load/{id}
GET    /media/fetch
GET    /media/find-by-path
POST   /media/delete-by-path
POST   /media/mark-permanent
GET    /media/{id}
DELETE /media/{id}
POST   /media/{id}/replace
```

Known live-project safety note:

```text
Current image_upload_v2 remove behavior still calls /media/delete-by-path.
This was intentionally left unchanged in Stage 1 to avoid surprise behavior changes.
Future media-picker remove behavior must be detach-only.
```

## Stage 1 Implemented Scope

Added safe, additive global media-manager foundation:

```text
Read-only media library API
Folder/path options API
Single media details API
Usage lookup API
Global modal shell included from backend.master
Bottom-right fixed Files launcher
Browse/search/filter/paginate media grid
Read-only details panel
Copy URL/path actions
Upload tab that reuses existing /media/upload
Global window.MediaManager.open(options) API
```

No product, CRM, CMS, POS, banner, blog, or existing upload component was migrated in Stage 1.

## Stage 2 Implemented Scope

Added a new reusable picker field without replacing any live field:

```text
resources/views/backend/components/media-picker-field.blade.php
public/assets/js/media-manager/media-picker-field.js
```

Component behavior:

```text
Uses window.MediaManager.open(options)
Supports single and multiple modes
Stores selected media IDs in a hidden input
Optionally stores selected paths in a second hidden input
Renders selected previews
Remove clears only the component values and preview
Remove does not call /media/delete-by-path, DELETE /media/{id}, or any destructive endpoint
Dispatches media-picker:change for page-level integrations
```

## Stage 3 Implemented Scope

Added a non-destructive authenticated pilot page:

```text
GET  /media/picker-pilot
POST /media/picker-pilot
resources/views/backend/media-manager/pilot.blade.php
```

Pilot behavior:

```text
Renders single media-picker-field
Renders multiple media-picker-field with max 6
Uses MediaManager select callback
Remove remains detach-only
POST validates selected media IDs
POST does not create, update, delete, or mark permanent any record
Submitted values are shown back through old input for visual verification
```

## Stage 3.5 Implemented Scope

Product image/gallery tabs now support the media manager without removing the old upload option:

```text
resources/views/backend/product_management/tabs/images.blade.php
public/assets/js/product_create_vue.js
public/assets/js/product_edit_vue.js
```

Implemented:

```text
Product main image: Choose from Library
Product main image: Upload New retained
Gallery images: Choose Gallery multi-select
Gallery slot: choose one image from library
Gallery slot: Upload New retained
Create and edit flows both updated
Existing backend save payload preserved: product_image_id and gallery_image_ids
```

Safety repair:

```text
Product image remove is detach-only
Gallery image remove is detach-only
Uploading a replacement no longer deletes the previous media file first
No existing media file is deleted just because it was reused or removed from a product draft
```

## Stage 4 Implemented Scope

Added product media usage tracking:

```text
app/Services/Media/MediaUsageService.php
app/Http/Controllers/ProductManagement/ProductManagementController.php
public/assets/js/media-manager/media-manager.js
public/assets/css/media-manager.css
```

Tracked product usage:

```text
Product image -> media_in_uses model App\Models\Product, col_name image
Product gallery -> media_in_uses model App\Models\Product, col_name multiple_images
Product notification image -> media_in_uses model App\Models\Product, col_name notification_image
Product variant image -> media_in_uses model App\Models\ProductVariantCombination, col_name image
```

Sync behavior:

```text
Runs inside product create/update transaction
Groups product and variant rows with slug product:{product_id}
Clears previous rows for that product slug
Rebuilds current active usage rows
Does not delete media files
Does not add migrations
```

Modal details now shows usage count and sample usage rows through:

```text
GET /media/{media}/usage
```

Compatibility fix:

```text
Product edit with empty gallery_image_ids now clears product multiple_images.
This is required because gallery remove is now detach-only.
```

## Stage 5 Implemented Scope

Fixed upload storage defaults and folder-backed media records:

```text
config/media.php
config/filesystems.php
app/Providers/AppServiceProvider.php
app/Http/Controllers/MediaUploaderController.php
app/Http/Controllers/MediaLibraryController.php
app/Models/MediaFile.php
database/migrations/2026_06_06_025616_add_media_folder_id_to_media_files_table.php
resources/views/backend/components/media-manager/modal.blade.php
public/assets/js/media-manager/media-manager.js
public/assets/css/media-manager.css
.env.example
```

Implemented:

```text
Default upload disk is ftp
Allowed upload disks currently include ftp only
S3 option is left commented for future implementation
Upload max size comes from MEDIA_UPLOAD_MAX_MB, default 5MB
Default media folder comes from MEDIA_DEFAULT_FOLDER_ID, default 2
media_files now has media_folder_id
Upload accepts media_folder_id
Upload resolves/creates media_folders from directory when folder id is missing
TenantDbMiddleware forces tenant upload disk to tenant file_upload_disk or ftp fallback
Media library API can filter by media_folder_id
Media library folder response includes a tree-friendly structure
Modal upload tab sends selected disk and media_folder_id
FTP disk file URL uses FILE_URL/app file_url + file path
MediaFile exists/delete behavior is disk-aware, so ftp uploads are not treated as public source files
```

Storage safety:

```text
New media-manager uploads no longer fall back to public/local source-code storage.
If FTP credentials are missing or invalid, upload should fail instead of silently writing into the project.
```

Modal redesign plan:

```text
Left sidebar folder tree with collapse/expand arrows
Folder name click sets selected folder state
Upload tab uploads into selected folder
New folder action creates child folder under selected folder
Right panel shows selected folder contents
Disk selector defaults to FTP
S3 selector remains hidden/commented until credentials and URL strategy are ready
```

## Stage 6 Implemented Scope

Added selected-file permanent delete from the media modal:

```text
DELETE /media/library/{media}
app/Http/Controllers/MediaLibraryController.php
public/assets/js/media-manager/media-manager.js
public/assets/css/media-manager.css
```

Delete behavior:

```text
Delete button appears in selected file details panel
Confirmation is required
Delete is blocked when active media_in_uses rows exist
Unused files are deleted from configured storage disk and force-deleted from media_files
Library grid refreshes after successful delete
```

## Stage 6 Upload Preset Update

Added upload-size presets to the global media manager:

```text
Default preset: square
Square resize: 800x800
Banner resize: 1200x400
Selected preset width/height is sent to /media/upload
Uploaded images are resized/cropped through the existing Intervention Image fit() flow
size_preset is stored in media_files metadata
```

## Stage 7 Folder Tree Update

Implemented the modal folder-tree foundation:

```text
Left-side folder tree in Media Library tab
All folders root selection
Folder name click updates selected folder state
Folder arrow toggles child expansion/collapse
Library query uses selected media_folder_id
Upload request uses selected folder and directory path
New folder form creates child folder under selected folder
Child folder storage path follows parent saved_name_into_storage path
Folder refresh action reloads the tree
Legacy directory select remains hidden for compatibility
```

## Files Added

```text
MEDIA_MANAGER_STAGE_TRACKER.md
app/Http/Controllers/MediaLibraryController.php
resources/views/backend/components/media-manager/modal.blade.php
public/assets/css/media-manager.css
public/assets/js/media-manager/media-manager.js
public/assets/js/media-manager/media-picker-field.js
resources/views/backend/components/media-picker-field.blade.php
```

## Files Modified

```text
resources/views/backend/master.blade.php
routes/mediaRoutes.php
public/assets/css/media-manager.css
public/assets/js/media-manager/media-manager.js
```

## Routes Added

```text
GET /media/library
GET /media/library/folders
GET /media/library/{media}
GET /media/{media}/usage
GET /media/picker-pilot
POST /media/picker-pilot
```

## Stage 1 Safety Rules

```text
No migration added
No existing route removed
No existing upload component changed
No existing delete behavior changed
No automatic product-field replacement
No permanent delete action added to the media-manager modal
```

## Next Recommended Stage

```text
Stage 7: redesign modal with left folder tree, child-folder creation UI, and selected-folder upload state.
```
