<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('device_token_hash')->nullable();
            $table->timestamp('token_last_rotated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->string('device_model')->nullable();
            $table->string('android_version', 50)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('resolution', 20)->nullable();
            $table->unsignedBigInteger('storage_total_bytes')->nullable();
            $table->unsignedBigInteger('storage_free_bytes')->nullable();
            $table->unsignedInteger('manifest_version')->default(0);
            $table->unsignedBigInteger('current_playlist_id')->nullable();
            $table->unsignedBigInteger('current_media_asset_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
