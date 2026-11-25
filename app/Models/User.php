<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Paddle\Billable;

#[Fillable(['name', 'email', 'password', 'google_id', 'apple_id', 'email_verified_at'])]
#[Hidden(['password', 'remember_token', 'google_id', 'apple_id'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Playlist, $this>
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * Saving playlists is what the subscription pays for.
     */
    public function canSavePlaylists(): bool
    {
        return $this->subscribed();
    }
}
