<?php

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A visitor who is not signed in, recognized by the token in their cookie.
 */
#[Fillable(['token'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory, Prunable;

    /**
     * Guests whose history has expired are forgotten too.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::whereDoesntHave('promptHistories')
            ->where('created_at', '<', now()->subDays(PromptHistory::RETENTION_DAYS));
    }

    /**
     * @return HasMany<PromptHistory, $this>
     */
    public function promptHistories(): HasMany
    {
        return $this->hasMany(PromptHistory::class);
    }
}
