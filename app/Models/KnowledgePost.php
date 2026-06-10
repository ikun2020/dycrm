<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgePost extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'status',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (KnowledgePost $post): void {
            $post->user_id ??= auth()->id();
            $post->slug = self::uniqueSlug($post->slug ?: $post->title);

            if ($post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }
        });

        static::updating(function (KnowledgePost $post): void {
            if ($post->isDirty('slug')) {
                $post->slug = self::uniqueSlug($post->slug ?: $post->title, $post->getKey());
            }

            if ($post->isDirty('status') && $post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value) ?: 'post';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            static::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(KnowledgeComment::class);
    }
}
