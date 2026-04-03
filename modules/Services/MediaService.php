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

    public function __construct(
        private readonly MediaRepository $mediaRepository
    ) {
        $driver = extension_loaded('imagick') ? new ImagickDriver : new GdDriver;
        $this->imageManager = new ImageManager($driver);
    }

    /**
     * Upload and store a single image for a model
     *
     * @param  string  $modelType  e.g., 'ModulesShoppingComplex\Models\Product'
     * @return array{success: bool, media?: Media, error?: string}
     */
    public function uploadImage(
        UploadedFile $file,
        string $modelType,
        int $modelId,
        string $type = 'image'
    ): array {
        // Validate file
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

            // Create media record - cleanup file if this fails
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
                // Cleanup: Delete the uploaded file since DB insert failed
                if (Storage::disk(config('media.storage_disk'))->exists($path)) {
                    Storage::disk(config('media.storage_disk'))->delete($path);
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
            'errors' => $errors, // Partial errors if any
        ];
    }

    /**
     * Delete a media record and its file
     */
    public function deleteMedia(int $mediaId): bool
    {
        try {
            $media = $this->mediaRepository->find($mediaId);

            // Delete file from storage
            if (Storage::disk(config('media.storage_disk'))->exists($media->url)) {
                Storage::disk(config('media.storage_disk'))->delete($media->url);
            }

            // Delete database record
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
     * Delete all media for a model
     */
    public function deleteAllMediaForModel(string $modelType, int $modelId): bool
    {
        try {
            $mediaItems = $this->mediaRepository->getForModel($modelType, $modelId);

            foreach ($mediaItems as $media) {
                // Delete file from storage
                if (Storage::disk(config('media.storage_disk'))->exists($media->url)) {
                    Storage::disk(config('media.storage_disk'))->delete($media->url);
                }
            }

            $this->mediaRepository->deleteForModel($modelType, $modelId);

            return true;
        } catch (Exception $e) {
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
            // Get IDs of old media before uploading new ones
            $oldMediaIds = $this->mediaRepository->getForModel($modelType, $modelId)
                ->pluck('id')
                ->toArray();

            // Upload new images
            $result = $this->uploadMultipleImages($files, $modelType, $modelId, $type);

            // Only delete old images if upload succeeded
            if ($result['success'] && ! empty($oldMediaIds)) {
                foreach ($oldMediaIds as $mediaId) {
                    $this->deleteMedia($mediaId);
                }
            }

            return $result;
        });
    }

    /**
     * Validate image file
     *
     * @return array{valid: bool, error?: string}
     */
    private function validateImage(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            return [
                'valid' => false,
                'error' => 'Invalid file upload.',
            ];
        }

        // Check file size
        if ($file->getSize() > config('media.max_file_size')) {
            $maxSizeMB = config('media.max_file_size') / 1048576;

            return [
                'valid' => false,
                'error' => "File size exceeds maximum allowed size of {$maxSizeMB}MB.",
            ];
        }

        // Check MIME type
        if (! in_array($file->getMimeType(), config('media.allowed_mime_types'))) {
            return [
                'valid' => false,
                'error' => 'File type not allowed. Only JPG, PNG, and WebP images are accepted.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * Generate unique filename for uploaded image
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $storagePath = config('media.storage_path');

        return $storagePath.'/'.$filename;
    }

    /**
     * Optimize image and save to storage
     */
    private function optimizeAndSave(UploadedFile $file, string $filename): string
    {
        $image = $this->imageManager->read($file->getRealPath());

        // Resize if image is larger than max dimensions
        $maxWidth = config('media.max_width');
        $maxHeight = config('media.max_height');
        $image->scaleDown($maxWidth, $maxHeight);

        // Encode with quality setting - using toJpeg/toPng/toWebp based on file type
        $quality = config('media.quality');
        $mimeType = $file->getMimeType();
        $encoded = match ($mimeType) {
            'image/jpeg' => $image->toJpeg($quality),
            'image/png' => $image->toPng(),
            'image/webp' => $image->toWebp($quality),
            default => $image->toJpeg($quality),
        };

        // Ensure directory exists
        $directory = dirname($filename);
        if (! Storage::disk(config('media.storage_disk'))->exists($directory)) {
            Storage::disk(config('media.storage_disk'))->makeDirectory($directory);
        }

        $stored = Storage::disk(config('media.storage_disk'))->put($filename, (string) $encoded);

        if (! $stored) {
            throw new Exception('Failed to store image to disk');
        }

        return $filename;
    }

    /**
     * Get full URL for a media file
     */
    public function getMediaUrl(Media $media): string
    {
        return asset('storage/'.$media->url);
    }
}
