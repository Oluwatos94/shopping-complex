<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use ModulesShoppingComplex\Models\Media;
use ModulesShoppingComplex\Repositories\MediaRepository;

class MediaService
{
    private ImageManager $imageManager;

    private readonly string $disk;

    public function __construct(
        private readonly MediaRepository $mediaRepository
    ) {
        $driver = extension_loaded('imagick') ? new ImagickDriver : new GdDriver;
        $this->imageManager = new ImageManager($driver);
        $this->disk = config('media.storage_disk');
    }

    /**
     * Upload and store a single image for a model
     *
     * @param  string  $modelType  e.g., 'ModulesShoppingComplex\Catalog\Models\Product'
     * @return array{success: bool, media?: Media, error?: string}
     */
    public function uploadImage(
        UploadedFile $file,
        string $modelType,
        int $modelId,
        string $type = 'image'
    ): array {
        $validation = $this->validateImage($file);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
            ];
        }

        try {
            $filename = $this->generateFilename($file);
            $path = $this->optimizeAndSave($file, $filename);

            try {
                $media = $this->mediaRepository->create([
                    'url' => $path,
                    'type' => $type,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                ]);

                return [
                    'success' => true,
                    'media' => $media,
                ];
            } catch (\Exception $e) {
                if (Storage::disk($this->disk)->exists($path)) {
                    Storage::disk($this->disk)->delete($path);
                }

                throw $e;
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to upload image: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Delete all media records (and files) for a given model
     */
    public function deleteMediaForModel(string $modelType, int $modelId): void
    {
        $mediaItems = $this->mediaRepository->getForModel($modelType, $modelId);
        foreach ($mediaItems as $media) {
            $this->deleteMedia($media->id);
        }
    }

    /**
     * Delete all media of a specific type for a given model
     */
    public function deleteMediaByType(string $modelType, int $modelId, string $type): void
    {
        $mediaItems = $this->mediaRepository->getByTypeForModel($modelType, $modelId, $type);
        foreach ($mediaItems as $media) {
            $this->deleteMedia($media->id);
        }
    }

    /**
     * Upload a video file for a model (stored directly, no image processing)
     */
    public function uploadVideo(
        UploadedFile $file,
        string $modelType,
        int $modelId,
        string $type = 'product_video'
    ): array {
        try {
            $storagePath = config('media.storage_path', 'uploads');
            $filename = $storagePath.'/'.Str::uuid().'.'.$file->getClientOriginalExtension();

            $directory = dirname($filename);
            if (! Storage::disk($this->disk)->exists($directory)) {
                Storage::disk($this->disk)->makeDirectory($directory);
            }

            $stored = Storage::disk($this->disk)->putFileAs(
                $directory,
                $file,
                basename($filename)
            );

            if (! $stored) {
                throw new Exception('Failed to store video to disk');
            }

            try {
                $media = $this->mediaRepository->create([
                    'url' => $filename,
                    'type' => $type,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                ]);

                return ['success' => true, 'media' => $media];
            } catch (\Exception $e) {
                if (Storage::disk($this->disk)->exists($filename)) {
                    Storage::disk($this->disk)->delete($filename);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Failed to upload video: '.$e->getMessage()];
        }
    }

    /**
     * Upload multiple images for a model
     *
     * @param  array<UploadedFile>  $files
     * @return array{success: bool, media?: array<Media>, errors?: array<string>}
     */
    public function uploadMultipleImages(
        array $files,
        string $modelType,
        int $modelId,
        string $type = 'image'
    ): array {
        $uploadedMedia = [];
        $errors = [];

        foreach ($files as $index => $file) {
            $result = $this->uploadImage($file, $modelType, $modelId, $type);

            if ($result['success']) {
                $uploadedMedia[] = $result['media'];
            } else {
                $errors[] = "File {$index}: ".$result['error'];
            }
        }

        if (empty($uploadedMedia)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'media' => $uploadedMedia,
            'errors' => $errors,
        ];
    }

    /**
     * Delete a media record and its file
     */
    public function deleteMedia(int $mediaId): bool
    {
        try {
            $media = $this->mediaRepository->find($mediaId);

            if (Storage::disk($this->disk)->exists($media->url)) {
                Storage::disk($this->disk)->delete($media->url);
            }

            $this->mediaRepository->delete($mediaId);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Replace media for a model (delete old, upload new)
     *
     * @param  array<UploadedFile>  $files
     * @return array{success: bool, media?: array<Media>, errors?: array<string>}
     */
    public function replaceMediaForModel(
        array $files,
        string $modelType,
        int $modelId,
        string $type = 'image'
    ): array {
        return DB::transaction(function () use ($files, $modelType, $modelId, $type) {
            $oldMediaIds = $this->mediaRepository->getForModel($modelType, $modelId)
                ->pluck('id')
                ->toArray();

            $result = $this->uploadMultipleImages($files, $modelType, $modelId, $type);

            if ($result['success'] && ! empty($oldMediaIds)) {
                foreach ($oldMediaIds as $mediaId) {
                    $this->deleteMedia($mediaId);
                }
            }

            return $result;
        });
    }

    /**
     * Get full URL for a media file
     */
    public function getMediaUrl(Media $media): string
    {
        return Storage::disk($this->disk)->url($media->url);
    }

    /**
     * Validate image file
     *
     * @return array{valid: bool, error?: string}
     */
    private function validateImage(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return ['valid' => false, 'error' => 'Invalid file upload.'];
        }

        if ($file->getSize() > config('media.max_file_size')) {
            $maxSizeMB = config('media.max_file_size') / 1048576;

            return ['valid' => false, 'error' => "File size exceeds maximum allowed size of {$maxSizeMB}MB."];
        }

        if (! in_array($file->getMimeType(), config('media.allowed_mime_types'))) {
            return ['valid' => false, 'error' => 'File type not allowed. Only JPG, PNG, and WebP images are accepted.'];
        }

        return ['valid' => true];
    }

    /**
     * Generate unique filename for uploaded image
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $storagePath = config('media.storage_path');

        return $storagePath.'/'.Str::uuid().'.'.$extension;
    }

    /**
     * Optimize image and save to storage
     */
    private function optimizeAndSave(UploadedFile $file, string $filename): string
    {
        $image = $this->imageManager->read($file->getRealPath());
        $image->scaleDown(config('media.max_width'), config('media.max_height'));

        $quality = config('media.quality');
        $encoded = match ($file->getMimeType()) {
            'image/jpeg' => $image->toJpeg($quality),
            'image/png' => $image->toPng(),
            'image/webp' => $image->toWebp($quality),
            default => $image->toJpeg($quality),
        };

        $directory = dirname($filename);
        if (! Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        $stored = Storage::disk($this->disk)->put($filename, (string) $encoded);

        if (! $stored) {
            throw new Exception('Failed to store image to disk');
        }

        return $filename;
    }
}
