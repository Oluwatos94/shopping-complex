<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Media;

class MediaRepository
{
    /**
     * Find a media by ID
     *
     * @throws ModelNotFoundException
     */
    public function find(int $id): Media
    {
        $media = Media::query()->find($id);

        if (! $media) {
            throw new ModelNotFoundException("Media with ID {$id} not found.");
        }

        return $media;
    }

    /**
     * Get all media for a specific model (e.g., product)
     *
     * @return Collection<int, Media>
     */
    public function getForModel(string $modelType, int $modelId): Collection
    {
        return Media::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get media by type for a specific model
     *
     * @return Collection<int, Media>
     */
    public function getByTypeForModel(string $modelType, int $modelId, string $type): Collection
    {
        return Media::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('type', $type)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Create a new media record
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Media
    {
        return DB::transaction(function () use ($data) {
            return Media::create($data);
        });
    }

    /**
     * Create multiple media records
     *
     * @param  array<array<string, mixed>>  $dataArray
     * @return Collection<int, Media>
     */
    public function createMultiple(array $dataArray): Collection
    {
        return DB::transaction(function () use ($dataArray) {
            if (empty($dataArray)) {
                return collect([]);
            }

            // Add timestamps to all records
            $now = now();
            $dataArray = array_map(fn ($data) => array_merge($data, [
                'created_at' => $now,
                'updated_at' => $now,
            ]), $dataArray);

            // Bulk insert all records at once
            Media::insert($dataArray);

            // Fetch and return the created records
            // We can identify them by created_at timestamp and model_id
            $modelIds = array_unique(array_column($dataArray, 'model_id'));
            $modelTypes = array_unique(array_column($dataArray, 'model_type'));

            return Media::query()
                ->where('created_at', $now)
                ->whereIn('model_id', $modelIds)
                ->whereIn('model_type', $modelTypes)
                ->get();
        });
    }

    /**
     * Delete a media record
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $media = $this->find($id);

            return $media->delete();
        });
    }

    /**
     * Delete all media for a specific model
     */
    public function deleteForModel(string $modelType, int $modelId): int
    {
        return DB::transaction(function () use ($modelType, $modelId) {
            return Media::query()
                ->where('model_type', $modelType)
                ->where('model_id', $modelId)
                ->delete();
        });
    }

    /**
     * Delete media by type for a specific model
     */
    public function deleteByTypeForModel(string $modelType, int $modelId, string $type): int
    {
        return DB::transaction(function () use ($modelType, $modelId, $type) {
            return Media::query()
                ->where('model_type', $modelType)
                ->where('model_id', $modelId)
                ->where('type', $type)
                ->delete();
        });
    }

    /**
     * Count media for a specific model
     */
    public function countForModel(string $modelType, int $modelId): int
    {
        return Media::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->count();
    }

    /**
     * Update media URL
     *
     * @throws ModelNotFoundException
     */
    public function updateUrl(int $id, string $url): Media
    {
        return DB::transaction(function () use ($id, $url) {
            $media = $this->find($id);
            $media->update(['url' => $url]);

            return $media;
        });
    }
}
