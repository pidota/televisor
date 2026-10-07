<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->unique(['playlist_id', 'sort_order']);
            $table->index(['playlist_id', 'media_asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
    }
};
