<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class MediaUpController extends Controller
{
    public function upload($source = [], $inner_call = false)
    {
        if (count($source) == 0) {
            $source = request()->all();
            $source['file'] = request()->file('file');
        }

        $rules = [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:15360',
            'folder' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9_\/-]+$/'],
            'disk' => ['nullable', 'in:public'],
            'media_folder_id' => ['nullable', 'exists:media_folders,id'],
        ];

        $file = $source['file'] ?? null;
        $mime = $file ? $file->getMimeType() : null;
        if (str_starts_with($mime, 'image/')) {
            $rules['height'] = ['required', 'numeric', 'min:40', 'max:1080'];
            $rules['width'] = ['required', 'numeric', 'min:40', 'max:1920'];
        }
        
        $validator = Validator::make($source, $rules, [
            'file.required' => 'There is no file to upload',
            'folder.required' => 'Folder name is required',
        ]);

        if ($validator->fails()) {
            return entityResponse($validator->errors(), 422, 'error', 'Validation Error');
        }

        $path = null;
        $folder = trim((string) $source['folder'], '/');
        $maxHeight = $source['height'] ?? 1080;
        $maxWidth = $source['width'] ?? 1920;
        $disk = 'public';
        $media_folder_id = $source['media_folder_id'] ?? 1;
        
        $folder = $folder . now()->format('/Y/m/d');

        try {
            $path = $this->resizeAndSaveImage($file, $folder, $maxHeight, $maxWidth, $disk);
        } catch (\Throwable $th) {
            // dd($th->getMessage());
        }

        if (!$path) {
            $file_name  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $file_name  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $file_name);
            $file_name = substr($file_name, 0, 20);
            $file_name .= '-';
            $file_name .= Str::random(12);
            $file_name .= '.';
            $file_name .= $file->extension();

            $path =  Storage::disk($disk)->putFileAs($folder, $file, $file_name);
        }

        if (!$path) {
            return entityResponse('The file could not be stored.', 500, 'error', 'Upload Failed');
        }

        $storagePath = ltrim((string) $path, '/');
        $databasePath = 'storage/' . $storagePath;
        $fileUrl = get_public_storage_url($storagePath);

        $dirPath = trim(dirname($databasePath), '/');
        $parts = explode('/', $dirPath);
        $folders = [];
        $current = '';

        foreach ($parts as $part) {
            $current .= ($current ? '/' : '') . $part;
            $folders[] = $current;
        }

        $path = trim($databasePath, '/');
        try {
            $media = Media::create([
                'disk'      => $disk,
                'path'      => $path,
                'filename'  => basename($path),
                'extension' => pathinfo($path, PATHINFO_EXTENSION),
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize(),
                'folders'   => json_encode($folders),
                'media_folder_id'   => $media_folder_id ?? 1,
            ]);
        } catch (\Throwable $th) {
            Storage::disk('public')->delete($storagePath);

            if ($inner_call) {
                throw $th;
            }

            return entityResponse('The uploaded file could not be registered.', 500, 'error', 'Upload Failed');
        }

        if ($inner_call) {
            return $media;
        }

        return entityResponse([
            'path' => $path,
            'media' => $media,
            'url' => $fileUrl,
        ]);
    }

    public function delete($source = [])
    {
        if (count($source) == 0) {
            $source = request()->all();
        }

        $validator = Validator::make($source, [
            'path' => ['required', 'string', 'max:1000', 'not_regex:/\.\./'],
            'disk' => ['nullable', 'in:public'],
        ]);

        if ($validator->fails()) {
            return entityResponse($validator->errors(), 422, 'error', 'Validation Error');
        }

        try {
            $storagePath = MediaFile::normalizePublicPath((string) $source['path']);
            Storage::disk('public')->delete($storagePath);

            $legacyPath = public_path($storagePath);
            if ($storagePath !== '' && File::exists($legacyPath)) {
                File::delete($legacyPath);
            }
        } catch (\Throwable $th) {
            return entityResponse("failed to delete " . $source['path'] . ". " . $th->getMessage());
        }

        try {
            $storagePath = MediaFile::normalizePublicPath((string) $source['path']);
            Media::query()
                ->whereIn('path', array_unique([
                    $source['path'],
                    $storagePath,
                    'storage/' . $storagePath,
                ]))
                ->delete();
        } catch (\Throwable $th) {
            //throw $th;
        }

        return entityResponse("deleted " . $source['path']);
    }

    public function resizeAndSaveImage($file, $folder = 'images', $maxHeight = 200, $maxWidth = 200, $disk = 'public')
    {
        $mime = $file->getMimeType();

        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return false;
        }

        $img = Image::make($file);
        $canvas = Image::canvas($maxWidth, $maxHeight);
        $img->resize($maxWidth, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $canvas->insert($img, 'center');
        $imgStream = (string) $canvas->encode();

        $file_name  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $file_name  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $file_name);
        $file_name = substr($file_name, 0, 20);
        $file_name .= '-';
        $file_name .= Str::random(12);
        $file_name .= '.';
        $file_name .= $file->extension();

        $file_source_path = trim($folder, '/') . '/' . $file_name;

        $storagePath = Storage::disk($disk)->put($file_source_path, $imgStream);
        return $storagePath ? $file_source_path : false;
    }

    public function uploadImage(Request $request)
    {
        $media = $this->upload(
            source: [
                'file' => request()->file('file'),
                'folder' => 'uploads/post_image',
                'height' => 400,
                'width' => 600,
                'disk' => 'public',
                'media_folder_id' => config('media.default_folder_id'),
            ],
            inner_call: true
        );

        return response()->json(["location" => get_public_storage_url(MediaFile::normalizePublicPath($media->path))]);
    }

    public function folders()
    {
        $select = ['id', 'name', 'parent_id'];

        $data = MediaFolder::select($select)
            ->where('parent_id', 0)
            ->with([
                'children' => function ($query) use ($select) {
                    $query->select($select);
                    // $query->with([
                    //     'parent' => function ($query) {
                    //         $query->select(['id', 'name']);
                    //     }
                    // ]);
                },
                // 'parent' => function ($query) {
                //     $query->select(['id', 'name']);
                // }
            ])
            ->get();

        return entityResponse($data);
    }

    public function new_folder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return entityResponse($validator->errors(), 422, 'error', 'Validation Error');
        }

        $data = MediaFolder::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
        ]);

        return entityResponse([
            ...$data->toArray(),
            'children' => [],
            'parent' => [],
        ]);
    }

    public function files_by_folder_id($media_directory_id)
    {
        $data = Media::where('media_directory_id', $media_directory_id)
            ->orderBy('id', 'DESC')
            ->where('status', 1)
            ->paginate(20);
        return entityResponse($data);
    }
}
