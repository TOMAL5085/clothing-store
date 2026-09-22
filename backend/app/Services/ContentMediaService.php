<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\CmsContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentMediaService
{
    public const VERSION_KEY = 'cms:version';

    /**
     * Current public-content cache version. Bumped on every admin CMS or
     * banner mutation so public responses can never serve stale rows.
     */
    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bumpVersion(): void
    {
        if (Cache::has(self::VERSION_KEY)) {
            Cache::increment(self::VERSION_KEY);
        } else {
            Cache::put(self::VERSION_KEY, 2);
        }
    }

    /**
     * Replace managed image assets for a CMS record or banner. Only files
     * under the entity directory are ever touched; remote URLs and foreign
     * paths are left alone and never deleted.
     *
     * @param  array{image?: UploadedFile, mobile_image?: UploadedFile}  $files
     */
    public function replaceManagedAssets(CmsContent|Banner $record, array $files, string $directory): void
    {
        $disk = Storage::disk('public');
        $updates = [];

        foreach (['image' => 'image_path', 'mobile_image' => 'mobile_image_path'] as $input => $column) {
            $file = $files[$input] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $old = $record->{$column};
            $path = $file->storeAs(
                $directory.'/'.$record->getKey(),
                Str::uuid().'.'.$file->extension(),
                'public'
            );

            $updates[$column] = $path;

            if (is_string($old) && $old !== '' && ! str_starts_with($old, 'http://') && ! str_starts_with($old, 'https://') && ! str_contains($old, '..')) {
                $disk->delete($old);
            }
        }

        if ($updates !== []) {
            $record->update($updates);
        }
    }

    /**
     * Remove managed storage assets for a record being deleted. Remote
     * URLs are never touched.
     */
    public function deleteManagedAssets(CmsContent|Banner $record): void
    {
        $disk = Storage::disk('public');

        foreach ($record->managedAssetPaths() as $path) {
            $disk->delete($path);
        }
    }
}
