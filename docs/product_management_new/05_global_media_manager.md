# 05 Global Modal Media Manager

Last updated: 2026-06-07

## Goal

Build a WordPress-like modal media manager that is imported once in the backend master layout and can be used from any page.

It must support multiple image pickers on the same page without conflicts.

## Existing Foundation

Current global master already loads:

```text
assets/css/media-manager.css
```

Existing media routes:

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

Existing model:

```text
App\Models\MediaFile
```

Existing usage table:

```text
media_in_uses
```

## Required Global API

Expose:

```js
window.MediaManager.open(options)
```

Single picker example:

```js
window.MediaManager.open({
  mode: 'single',
  selected: [currentMediaId],
  purpose: 'product_main',
  directory: 'products',
  width: 800,
  height: 800,
  onSelect: function (files) {
    // files is an array, even in single mode
  }
});
```

Multiple picker example:

```js
window.MediaManager.open({
  mode: 'multiple',
  selected: [11, 12],
  max: 6,
  purpose: 'product_gallery',
  directory: 'products',
  onSelect: function (files) {
    // selected library files
  }
});
```

## Conflict-Free Rule

The modal is global but each open call gets its own isolated runtime context.

Required state shape:

```js
{
  isOpen: false,
  mode: 'single',
  callerId: null,
  selectedIds: [],
  max: null,
  purpose: null,
  directory: null,
  width: null,
  height: null,
  filters: {},
  onSelect: null
}
```

On every open:

```text
reset modal state
apply options
load media library
show modal
```

On close:

```text
clear callback
clear callerId
clear temporary selection not confirmed
```

## UI Requirements

Modal tabs:

```text
Upload Files
Media Library
```

Upload Files:

```text
drag/drop
click to choose
upload progress
multi upload
optional width/height/directory from caller options
new upload is selected automatically
```

Media Library:

```text
grid of existing media
search by file name/original name/path
filter by date
filter by folder/directory
filter by website when applicable
pagination or load more
selected state
details sidebar
```

Details sidebar:

```text
preview image
file name
original name
path
URL
dimensions
file size
uploaded date
copy URL
copy path
usage count when available
delete action only if authorized and safe
```

## Value Contract

Every selected file returned to callers should be normalized:

```json
{
  "id": 10,
  "url": "https://example.com/uploads/...",
  "path": "uploads/media/products/2026/06/image.jpg",
  "file_name": "image_abcd1234.jpg",
  "original_name": "image.jpg",
  "width": 800,
  "height": 800,
  "size": 120000,
  "mime_type": "image/jpeg",
  "is_temp": false
}
```

## Delete vs Detach

This is mandatory.

```text
Clearing an image picker field means detach from that field.
Clearing an image picker field must not delete the media file.
Deleting a media file is a separate explicit library action.
```

Product v3 field remove behavior:

```text
remove from product image field
remove from gallery selection
save tab to persist detach
do not call /media/revert or /media/delete-by-path for existing reusable media
```

## Backend Library Endpoints

Add new endpoints under existing media prefix or a new media manager controller.

Recommended:

```text
GET /media/library
GET /media/library/{media}
GET /media/library/{media}/usage
```

Optional:

```text
POST /media/library/bulk-mark-permanent
DELETE /media/library/{media}
```

Library query parameters:

```text
q
file_type=image
mime_type
directory
product_website_id
date_from
date_to
page
per_page
sort=-created_at
```

## Media Usage Tracking

Use `media_in_uses` to track where media is attached.

Suggested usage rows:

```text
media_id = 10
model = App\Models\Product
model_id = 123
col_name = image
status = 1
```

Gallery:

```text
model = App\Models\Product
model_id = 123
col_name = multiple_images
```

Variant:

```text
model = App\Models\ProductVariantCombination
model_id = 55
col_name = image
```

Notification:

```text
model = App\Models\Product
model_id = 123
col_name = notification_image
```

## Master Blade Import

Recommended future include in:

```text
resources/views/backend/master.blade.php
```

Add:

```blade
@include('backend.components.media-manager.modal')
<script src="{{ versioned_asset('assets/js/media-manager/bootstrap.js') }}" defer></script>
<script src="{{ versioned_asset('assets/js/media-manager/app.js') }}" defer></script>
```

Load this globally only after the modal is stable. During first implementation, it can be loaded only in Product v3 pages.

## Frontend File Tree

```text
resources/views/backend/components/media-manager/modal.blade.php

public/assets/js/media-manager/
  bootstrap.js
  app.js
  stores/mediaManagerStore.js
  utils/mediaManagerApi.js
  utils/formatFileSize.js
  components/MediaManagerModal.js
  components/MediaUploadPanel.js
  components/MediaLibraryGrid.js
  components/MediaLibraryItem.js
  components/MediaDetailsPanel.js
  components/MediaSearchToolbar.js

public/assets/css/media-manager.css
```

## Product v3 Integration

Product v3 should not implement its own media modal.

It should use:

```text
MediaPickerField.js
```

which internally calls:

```js
window.MediaManager.open(...)
```

