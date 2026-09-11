<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Models\MediaInUse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    public function pilot()
    {
        $single = $this->mediaFromIds((string) old('pilot_single_media_id', ''));
        $multiple = $this->mediaFromIds((string) old('pilot_gallery_media_ids', ''));

        return view('backend.media-manager.pilot', [
            'singleSelectedFiles' => $single,
            'multipleSelectedFiles' => $multiple,
        ]);
    }

    public function pilotSubmit(Request $request)
    {
        $request->validate([
            'pilot_single_media_id' => ['nullable', 'integer', 'exists:media_files,id'],
            'pilot_single_media_path' => ['nullable', 'string', 'max:1000'],
            'pilot_gallery_media_ids' => ['nullable', 'string', 'max:1000'],
            'pilot_gallery_media_paths' => ['nullable', 'string', 'max:5000'],
        ]);

        $galleryIds = collect(explode(',', (string) $request->input('pilot_gallery_media_ids')))
            ->map(fn ($id) => trim($id))
            ->filter()
            ->values();

        if ($galleryIds->isNotEmpty()) {
            $existingCount = MediaFile::query()
                ->whereIn('id', $galleryIds)
                ->count();

            if ($existingCount !== $galleryIds->count()) {
                return back()
                    ->withErrors(['pilot_gallery_media_ids' => 'One or more selected gallery files no longer exist.'])
                    ->withInput();
            }
        }

        return back()
            ->with('media_picker_pilot_success', 'Media picker pilot submitted successfully. No database record was changed.')
            ->withInput();
    }

    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 24), 1), 60);
        $query = MediaFile::query()
            ->where('file_type', $request->input('file_type', 'image'))
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', '%' . $search . '%')
                    ->orWhere('file_name', 'like', '%' . $search . '%')
                    ->orWhere('file_path', 'like', '%' . $search . '%')
                    ->orWhere('folder_path', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('directory')) {
            $directory = trim((string) $request->input('directory'), '/');
            $query->where('folder_path', 'like', '%/' . $directory . '/%');
        }

        if ($request->filled('media_folder_id')) {
            $query->where('media_folder_id', $request->input('media_folder_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('product_website_id')) {
            $query->where('product_website_id', $request->input('product_website_id'));
        }

        $media = $query->paginate($perPage);

        return response()->json([
            'data' => $media->getCollection()->map(function (MediaFile $file) {
                return $this->serializeMedia($file);
            })->values(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function show(MediaFile $media)
    {
        return response()->json([
            'success' => true,
            'data' => $this->serializeMedia($media, true),
        ]);
    }

    public function folders()
    {
        $folders = MediaFolder::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'saved_name_into_storage', 'parent_id', 'is_default']);

        $paths = MediaFile::query()
            ->select('folder_path')
            ->whereNotNull('folder_path')
            ->distinct()
            ->orderBy('folder_path')
            ->limit(250)
            ->pluck('folder_path')
            ->filter()
            ->values();

        return response()->json([
            'data' => [
                'folders' => $folders,
                'tree' => $this->folderTree($folders),
                'paths' => $paths,
            ],
        ]);
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ]);

        $name = trim($data['name']);
        $parentId = (int) ($data['parent_id'] ?? 0);
        $slug = Str::slug($name);
        $parent = $parentId > 0
            ? MediaFolder::query()->where('status', 1)->find($parentId)
            : null;
        $savedName = $parent
            ? trim((string) $parent->saved_name_into_storage, '/') . '/' . $slug
            : $slug;

        $folder = MediaFolder::query()->firstOrCreate(
            [
                'saved_name_into_storage' => $savedName,
                'parent_id' => $parentId,
            ],
            [
                'name' => $name,
                'is_default' => 0,
                'creator' => auth()->id(),
                'slug' => $slug,
                'status' => 1,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $folder,
        ], $folder->wasRecentlyCreated ? 201 : 200);
    }

    public function usage(MediaFile $media)
    {
        if (! Schema::hasTable('media_in_uses')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'count' => 0,
                    'tracking_available' => false,
                ],
            ]);
        }

        $usage = MediaInUse::query()
            ->where('media_id', $media->id)
            ->where('status', 1)
            ->latest('id')
            ->get(['id', 'model', 'model_id', 'col_name', 'created_at']);

        return response()->json([
            'data' => $usage,
            'meta' => [
                'count' => $usage->count(),
            ],
        ]);
    }

    public function destroy(MediaFile $media)
    {
        $usageCount = Schema::hasTable('media_in_uses')
            ? MediaInUse::query()
                ->where('media_id', $media->id)
                ->where('status', 1)
                ->count()
            : 0;

        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This file is used in ' . $usageCount . ' place' . ($usageCount > 1 ? 's' : '') . '. Remove those usages before deleting it permanently.',
                'usage_count' => $usageCount,
            ], 409);
        }

        try {
            $media->deleteFile();
            $media->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted permanently.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function serializeMedia(MediaFile $file, bool $withDetails = false): array
    {
        $data = [
            'id' => $file->id,
            'media_folder_id' => $file->media_folder_id,
            'url' => $file->url,
            'path' => $file->file_path,
            'folder_path' => $file->folder_path,
            'original_name' => $file->original_name,
            'file_name' => $file->file_name,
            'width' => $file->width,
            'height' => $file->height,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'file_type' => $file->file_type,
            'is_temp' => (bool) $file->is_temp,
            'product_website_id' => $file->product_website_id,
            'created_at' => optional($file->created_at)->format('Y-m-d H:i:s'),
        ];

        if ($withDetails) {
            $data['disk'] = $file->disk;
            $data['domain_url'] = $file->domain_url;
            $data['full_url'] = $file->url;
            $data['updated_at'] = optional($file->updated_at)->format('Y-m-d H:i:s');
        }

        return $data;
    }

    private function folderTree($folders, int $parentId = 0): array
    {
        return $folders
            ->filter(fn ($folder) => (int) ($folder->parent_id ?? 0) === $parentId)
            ->map(function ($folder) use ($folders) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'saved_name_into_storage' => $folder->saved_name_into_storage,
                    'parent_id' => $folder->parent_id,
                    'is_default' => $folder->is_default,
                    'children' => $this->folderTree($folders, (int) $folder->id),
                ];
            })
            ->values()
            ->all();
    }

    private function mediaFromIds(string $ids)
    {
        $mediaIds = collect(explode(',', $ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        if ($mediaIds->isEmpty()) {
            return collect();
        }

        $files = MediaFile::query()
            ->whereIn('id', $mediaIds)
            ->get()
            ->keyBy('id');

        return $mediaIds
            ->map(fn ($id) => $files->get($id))
            ->filter()
            ->map(fn (MediaFile $file) => $this->serializeMedia($file))
            ->values();
    }
}
