<?php

namespace App\Models;

use Database\Factories\PlaylistFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A track list a subscriber saved, together with the phrase and settings that produced it.
 */
#[Fillable([
    'name', 'phrase', 'day_time', 'moods', 'relationship', 'genres',
    'energy', 'popularity', 'instrumentalness', 'tracks', 'custom_options',
])]
class Playlist extends Model
{
    /** @use HasFactory<PlaylistFactory> */
    use HasFactory;

    public const MAX_NAME_LENGTH = 120;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moods' => 'array',
            'genres' => 'array',
            'tracks' => 'array',
            'custom_options' => 'array',
            'energy' => 'integer',
            'popularity' => 'integer',
            'instrumentalness' => 'integer',
        ];
    }
}
