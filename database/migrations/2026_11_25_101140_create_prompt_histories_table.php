<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lists generated for people who cannot save playlists, so an identical prompt never returns the same songs.
     */
    public function up(): void
    {
        Schema::create('prompt_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->json('prompt');
            $table->json('songs');
            $table->timestamps();

            $table->index(['fingerprint', 'guest_id']);
            $table->index(['fingerprint', 'user_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_histories');
    }
};
