<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'published', 'archived'];

    protected $fillable = [
        'key',
        'title',
        'subtitle',
        'body',
        'image_path',
        'mobile_image_path',
        'cta_label',
        'cta_url',
        'status',
        'sort_order',
        'starts_at',
        'ends_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Slides visible to the public storefront: published and inside the
     * optional schedule window. Computed from stored timestamps so a
     * missing scheduler worker can never leak future slides.
     */
    public function scopeVisible(Builder $query): Builder
    {
        $now = now();

        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return CmsContent::resolvePublicUrl($this->image_path);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return CmsContent::resolvePublicUrl($this->mobile_image_path);
    }

    /**
     * Storage-relative paths managed by this application (never remote
     * URLs or paths outside the banner directory).
     *
     * @return list<string>
     */
    public function managedAssetPaths(): array
    {
        return collect([$this->image_path, $this->mobile_image_path])
            ->filter(fn ($path) => is_string($path) && $path !== ''
                && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')
                && ! str_contains($path, '..'))
            ->values()
            ->all();
    }
}
