<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One list generated for someone who cannot save playlists: the prompt and the songs it returned.
 * Kept for a limited time only.
 */
#[Fillable(['guest_id', 'user_id', 'fingerprint', 'prompt', 'songs'])]
class PromptHistory extends Model
{
    use Prunable;

    public const RETENTION_DAYS = 90;

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt' => 'array',
            'songs' => 'array',
        ];
    }
}
