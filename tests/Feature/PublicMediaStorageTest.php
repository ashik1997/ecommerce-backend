<?php

namespace Tests\Feature;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaStorageTest extends TestCase
{
    public function test_public_disk_is_the_only_media_upload_disk(): void
    {
        $this->assertSame('public', config('filesystems.default'));
        $this->assertSame(['public'], config('media.allowed_disks'));
        $this->assertSame(['local', 'public', 's3'], array_keys(config('filesystems.disks')));
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
    }

    public function test_media_paths_are_compatible_with_public_storage_urls_and_cleanup(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/media/example.txt', 'example');

        $media = new MediaFile([
            'disk' => 'public',
            'file_path' => 'storage/uploads/media/example.txt',
        ]);

        $this->assertSame('uploads/media/example.txt', $media->storageRelativePath());
        $this->assertTrue($media->exists());
        $this->assertSame('example', $media->contents());
        $this->assertStringEndsWith('/storage/uploads/media/example.txt', $media->url);
        $this->assertTrue($media->deleteFile());
        Storage::disk('public')->assertMissing('uploads/media/example.txt');
    }

    public function test_file_urls_use_the_current_request_host_and_port(): void
    {
        app('url')->setRequest(Request::create('http://localhost:8080/products'));

        $this->assertSame('http://localhost:8080', get_file_url());
        $this->assertSame(
            'http://localhost:8080/storage/uploads/media/example.jpg',
            get_public_storage_url('uploads/media/example.jpg')
        );
    }

    public function test_public_media_can_be_served_without_a_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/media/example.txt', 'example');

        $response = $this->get('/storage/uploads/media/example.txt');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('example', $response->streamedContent());
    }

    public function test_public_media_fallback_returns_not_found_for_missing_files(): void
    {
        Storage::fake('public');

        $this->get('/storage/uploads/media/missing.jpg')->assertNotFound();
    }
}
