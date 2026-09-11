<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MediaUploaderController extends Controller
{
    /**
     * Serve a public-disk file when the web server cannot follow/create the
     * conventional public/storage symbolic link (common on shared hosting).
     */
    public function servePublic(string $path)
    {
        $path = MediaFile::normalizePublicPath($path);

        if ($path === ''
            || str_contains($path, "\0")
            || collect(explode('/', $path))->contains(fn ($segment) => $segment === '..')
            || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Handle lazy file upload (FilePond process)
     */
    public function upload(Request $request)
    {
        $maxUploadMb = max((int) config('media.max_upload_mb', 5), 1);
        $maxUploadKb = $maxUploadMb * 1024;

        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:' . $maxUploadKb,
            'disk' => 'nullable|in:public',
            'directory' => 'nullable|string|max:150',
            'media_folder_id' => 'nullable|integer|exists:media_folders,id',
            'size_preset' => 'nullable|string|max:50',
            'width' => 'nullable|integer|min:1|max:5000',
            'height' => 'nullable|integer|min:1|max:5000',
        ]);

        $storagePath = null;

        try {
            $file = $request->file('file');
            
            // Get width and height from request if provided
            $targetWidth = $request->input('width', null);
            $targetHeight = $request->input('height', null);
            $sizePreset = $request->input('size_preset', null);
            
            $mediaFolder = $this->resolveMediaFolder($request);
            $directory = $this->uploadDirectory($mediaFolder, $request);
            
            // Generate unique filename
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $this->extensionForMime((string) $file->getMimeType());
            
            // Clean and limit filename to 20 chars + unique ID
            $cleanName = Str::slug(Str::limit($originalName, 20, ''));
            $uniqueId = Str::random(8);
            $fileName = $cleanName . '_' . $uniqueId . '.' . $extension;
            
            // Create folder structure: media/{directory}/{year}/{month} or media/{year}/{month}
            $year = now()->format('Y');
            $month = now()->format('m');
            if (!empty($directory)) {
                $folderPath = "uploads/media/{$directory}/{$year}/{$month}";
            } else {
                $folderPath = "uploads/media/{$year}/{$month}";
            }
            
            // Process image with Intervention Image
            $image = Image::make($file);
            
            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();
            
            // Resize if dimensions are provided
            if ($targetWidth && $targetHeight) {
                $image->fit($targetWidth, $targetHeight, function ($constraint) {
                    $constraint->upsize();
                });
            } elseif ($targetWidth || $targetHeight) {
                $image->resize($targetWidth, $targetHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Encode image
            $encodedImage = $image->encode($extension, 85);
            
            $disk = 'public';
            $storagePath = $folderPath . '/' . $fileName;
            Storage::disk($disk)->put($storagePath, $encodedImage);
            
            // Get file size
            $fileSize = Storage::disk($disk)->size($storagePath);
            
            $fileUrl = get_public_storage_url($storagePath);
            $databasePath = 'storage/' . ltrim($storagePath, '/');
            
            // Create media file record
            $mediaFile = MediaFile::create([
                'media_folder_id' => $mediaFolder?->id,
                'folder_path' => 'storage/' . ltrim($folderPath, '/'),
                'file_path' => $databasePath,
                'domain_url' => url('/'),
                'full_url' => $fileUrl,
                'file_name' => $fileName,
                'original_name' => $file->getClientOriginalName(),
                'size' => $fileSize,
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'width' => $image->width(),
                'height' => $image->height(),
                'disk' => $disk,
                'uploader_type' => auth()->check() ? get_class(auth()->user()) : null,
                'uploader_id' => auth()->id(),
                'file_type' => 'image',
                'is_temp' => true,
                'metadata' => [
                    'original_width' => $originalWidth,
                    'original_height' => $originalHeight,
                    'resized' => ($targetWidth || $targetHeight) ? true : false,
                    'size_preset' => $sizePreset,
                    'max_upload_mb' => $maxUploadMb,
                ],
            ]);
            
            // Return simple response
            return response()->json([
                'success' => true,
                'id' => $mediaFile->id,
                'path' => $databasePath,
                'url' => $fileUrl,
                'width' => $image->width(), // Image width
                'height' => $image->height(), // Image height
            ]);
            
        } catch (\Exception $e) {
            if ($storagePath && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }

            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Handle file revert (FilePond revert - delete temporary file)
     */
    public function revert(Request $request)
    {
        try {
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            $mediaId = $data['id'] ?? null;
            
            if (!$mediaId) {
                return response()->json(['error' => 'No file ID provided'], 400);
            }
            
            $mediaFile = MediaFile::find($mediaId);
            
            if ($mediaFile && $mediaFile->is_temp) {
                // Delete physical file
                $mediaFile->deleteFile();
                
                // Delete database record
                $mediaFile->forceDelete();
                
                return response()->json(['success' => true], 200);
            }
            
            return response()->json(['error' => 'File not found or not temporary'], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Load existing file for FilePond
     */
    public function load($id)
    {
        try {
            $mediaFile = MediaFile::where('id', $id)->first();
            
            if (!$mediaFile || !$mediaFile->exists()) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $fileContent = $mediaFile->contents();
            
            return response($fileContent, 200)
                ->header('Content-Type', $mediaFile->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $mediaFile->file_name . '"');
                
        } catch (\Exception $e) {
            return response()->json(['error' => 'File not found: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Fetch file metadata
     */
    public function fetch(Request $request)
    {
        try {
            $url = $request->input('url');
            
            if (!$url) {
                return response()->json(['error' => 'URL required'], 400);
            }
            
            // Extract file ID from URL if it's a stored media file
            // This is for fetching already uploaded files
            $mediaFile = MediaFile::where('full_url', $url)->first();
            
            if (!$mediaFile) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            if (!$mediaFile->exists()) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $fileContent = $mediaFile->contents();
            
            return response($fileContent, 200)
                ->header('Content-Type', $mediaFile->mime_type)
                ->header('Content-Disposition', 'inline; filename="' . $mediaFile->file_name . '"');
                
        } catch (\Exception $e) {
            return response()->json(['error' => 'Fetch failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark uploaded files as permanent (called when form is saved)
     */
    public function markAsPermanent(Request $request)
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:media_files,id',
        ]);
        
        try {
            $fileIds = $request->input('file_ids');
            
            MediaFile::whereIn('id', $fileIds)
                ->update([
                    'is_temp' => false,
                    'temp_token' => null,
                ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Files marked as permanent',
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a media file permanently
     */
    public function destroy($id)
    {
        try {
            $mediaFile = MediaFile::findOrFail($id);
            
            // Delete physical file
            $mediaFile->deleteFile();
            
            // Delete database record
            $mediaFile->forceDelete();
            
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete media file by path or ID
     */
    public function deleteByPath(Request $request)
    {
        $request->validate([
            'media_id' => ['nullable', 'integer'],
            'file_path' => ['nullable', 'string', 'max:1000', 'not_regex:/\.\./'],
        ]);

        try {
            $mediaId = $request->input('media_id');
            $filePath = $request->input('file_path');
            
            $mediaFile = null;
            
            // Try to find by media ID first
            if ($mediaId) {
                $mediaFile = MediaFile::find($mediaId);
            }
            
            // If not found by ID, try to find by file path
            if (!$mediaFile && $filePath) {
                $normalizedPath = MediaFile::normalizePublicPath((string) $filePath);
                $mediaFile = MediaFile::query()
                    ->whereIn('file_path', array_unique([
                        $filePath,
                        $normalizedPath,
                        'storage/' . $normalizedPath,
                    ]))
                    ->first();
            }
            
            if ($mediaFile) {
                // Delete physical file
                $mediaFile->deleteFile();
                
                // Delete database record
                $mediaFile->forceDelete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully',
                ]);
            }
            
            // If media file not found in database, try to delete physical file directly
            if ($filePath) {
                $deleted = false;

                $normalizedPath = MediaFile::normalizePublicPath((string) $filePath);
                if ($normalizedPath !== '' && Storage::disk('public')->exists($normalizedPath)) {
                    $deleted = Storage::disk('public')->delete($normalizedPath);
                }

                $legacyPath = public_path($normalizedPath);
                if ($normalizedPath !== '' && File::exists($legacyPath)) {
                    $deleted = File::delete($legacyPath) || $deleted;
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $deleted ? 'File deleted successfully' : 'File not found or already deleted',
                ]);
            }
            
            return response()->json([
                'error' => 'No file path or media ID provided'
            ], 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find media file by path
     */
    public function findByPath(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string', 'max:1000', 'not_regex:/\.\./'],
        ]);

        try {
            $filePath = $request->input('path');
            
            if (!$filePath) {
                return response()->json([
                    'error' => 'File path required'
                ], 400);
            }
            
            $normalizedPath = MediaFile::normalizePublicPath((string) $filePath);
            $mediaFile = MediaFile::query()
                ->whereIn('file_path', array_unique([
                    $filePath,
                    $normalizedPath,
                    'storage/' . $normalizedPath,
                ]))
                ->first();
            
            if ($mediaFile) {
                return response()->json([
                    'success' => true,
                    'id' => $mediaFile->id,
                    'data' => $mediaFile,
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Media file not found'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get media file info
     */
    public function show($id)
    {
        try {
            $mediaFile = MediaFile::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $mediaFile,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'File not found'], 404);
        }
    }

    /**
     * Replace existing file (delete old, upload new)
     */
    public function replace(Request $request, $id)
    {
        $maxUploadKb = max((int) config('media.max_upload_mb', 5), 1) * 1024;
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:' . $maxUploadKb,
            'directory' => 'nullable|string|max:150',
            'media_folder_id' => 'nullable|integer|exists:media_folders,id',
            'size_preset' => 'nullable|string|max:50',
            'width' => 'nullable|integer|min:1|max:5000',
            'height' => 'nullable|integer|min:1|max:5000',
        ]);
        
        try {
            $oldMediaFile = MediaFile::find($id);
            $response = $this->upload($request);

            if ($response->getStatusCode() < 300 && $oldMediaFile) {
                $oldMediaFile->forceDelete();
            }

            return $response;
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function resolveMediaFolder(Request $request): ?MediaFolder
    {
        if ($request->filled('media_folder_id')) {
            return MediaFolder::query()
                ->where('id', $request->input('media_folder_id'))
                ->where('status', 1)
                ->first();
        }

        $directory = trim((string) $request->input('directory', ''), '/');
        if ($directory === '') {
            return $this->defaultMediaFolder();
        }

        return MediaFolder::query()
            ->firstOrCreate(
                ['saved_name_into_storage' => Str::slug($directory), 'status' => 1],
                [
                    'name' => Str::headline(str_replace(['-', '_'], ' ', $directory)),
                    'parent_id' => $this->defaultMediaFolderId(),
                    'is_default' => 0,
                    'creator' => auth()->id(),
                    'slug' => Str::slug($directory),
                ]
            );
    }

    private function defaultMediaFolderId(): ?int
    {
        return $this->defaultMediaFolder()?->id;
    }

    private function defaultMediaFolder(): ?MediaFolder
    {
        $configuredFolderId = (int) config('media.default_folder_id', 2);

        if ($configuredFolderId > 0) {
            $folder = MediaFolder::query()
                ->where('id', $configuredFolderId)
                ->where('status', 1)
                ->first();

            if ($folder) {
                return $folder;
            }
        }

        return MediaFolder::query()
            ->where('is_default', 1)
            ->where('status', 1)
            ->orderBy('id')
            ->first();
    }

    private function uploadDirectory(?MediaFolder $mediaFolder, Request $request): string
    {
        if ($mediaFolder && (int) $mediaFolder->is_default !== 1) {
            return $this->sanitizeDirectory((string) $mediaFolder->saved_name_into_storage);
        }

        return $this->sanitizeDirectory((string) $request->input('directory', ''));
    }

    private function sanitizeDirectory(string $directory): string
    {
        return collect(preg_split('#[\\\\/]+#', trim($directory, '/\\')) ?: [])
            ->map(fn (string $segment): string => Str::slug($segment))
            ->filter()
            ->implode('/');
    }

    private function extensionForMime(string $mime): string
    {
        return [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ][$mime] ?? 'jpg';
    }
}
