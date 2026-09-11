<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folder_path',
        'media_folder_id',
        'file_path',
        'domain_url',
        'full_url',
        'file_name',
        'original_name',
        'size',
        'mime_type',
        'extension',
        'width',
        'height',
        'disk',
        'uploader_type',
        'uploader_id',
        'file_type',
        'is_temp',
        'temp_token',
        'metadata',
    ];

    protected $casts = [
        'is_temp' => 'boolean',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the full URL of the file
     */
    public function getUrlAttribute()
    {
        if (Storage::disk('public')->exists($this->storageRelativePath())) {
            return get_public_storage_url($this->storageRelativePath());
        }

        if (File::exists(public_path((string) $this->file_path))) {
            return url('/' . ltrim((string) $this->file_path, '/'));
        }

        return url('/' . ltrim((string) $this->file_path, '/'));
    }

    /**
     * Keep legacy database rows from exposing obsolete remote URLs.
     */
    public function getFullUrlAttribute($value): string
    {
        return $this->getUrlAttribute();
    }

    /**
     * Get the full storage path
     */
    public function getStoragePathAttribute()
    {
        if (!Storage::disk('public')->exists($this->storageRelativePath())
            && File::exists(public_path((string) $this->file_path))) {
            return public_path((string) $this->file_path);
        }

        return Storage::disk('public')->path($this->storageRelativePath());
    }

    /**
     * Check if file exists in storage or public directory
     */
    public function exists(): bool
    {
        return Storage::disk('public')->exists($this->storageRelativePath())
            || File::exists(public_path((string) $this->file_path));
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        try {
            $deleted = true;
            $storagePath = $this->storageRelativePath();
            if (Storage::disk('public')->exists($storagePath)) {
                $deleted = Storage::disk('public')->delete($storagePath) && $deleted;
            }

            $legacyPath = public_path((string) $this->file_path);
            if (File::exists($legacyPath)) {
                $deleted = File::delete($legacyPath) && $deleted;
            }

            return $deleted;
        } catch (\Exception $e) {
            // Log error but don't throw
            \Log::error('Error deleting media file: ' . $e->getMessage(), [
                'file_path' => $this->file_path,
                'disk' => $this->disk
            ]);
            return false;
        }
    }

    public function contents(): string
    {
        $storagePath = $this->storageRelativePath();
        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->get($storagePath);
        }

        return File::get(public_path((string) $this->file_path));
    }

    public function storageRelativePath(): string
    {
        return self::normalizePublicPath((string) $this->file_path);
    }

    public static function normalizePublicPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return str_starts_with($path, 'storage/') ? ltrim(substr($path, 8), '/') : $path;
    }

    /**
     * Mark file as permanent (not temp)
     */
    public function markAsPermanent(): bool
    {
        return $this->update([
            'is_temp' => false,
            'temp_token' => null,
        ]);
    }

    /**
     * Scope to get temporary files
     */
    public function scopeTemporary($query)
    {
        return $query->where('is_temp', true);
    }

    /**
     * Scope to get permanent files
     */
    public function scopePermanent($query)
    {
        return $query->where('is_temp', false);
    }

    /**
     * Scope to get files by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Clean up old temporary files (older than 24 hours)
     */
    public static function cleanupOldTempFiles()
    {
        $oldFiles = static::temporary()
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($oldFiles as $file) {
            $file->deleteFile();
            $file->forceDelete();
        }

        return $oldFiles->count();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting, also delete the physical file
        static::deleting(function ($media) {
            if ($media->isForceDeleting()) {
                $media->deleteFile();
            }
        });
    }
}
