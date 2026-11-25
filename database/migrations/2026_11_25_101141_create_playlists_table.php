<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track lists subscribers saved, with the prompt that produced them so they can keep tuning it.
     */
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phrase', 280);
            $table->string('day_time', 40);
            $table->json('moods');
            $table->string('relationship', 40);
            $table->json('genres');
            $table->unsignedTinyInteger('energy');
            $table->unsignedTinyInteger('popularity');
            $table->unsignedTinyInteger('instrumentalness');
            $table->json('tracks');
            $table->json('custom_options')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
