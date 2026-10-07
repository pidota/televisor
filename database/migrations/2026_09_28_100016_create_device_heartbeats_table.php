<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('online');
            $table->foreignId('playlist_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('manifest_version')->default(0);
            $table->unsignedBigInteger('storage_free_bytes')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['screen_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_heartbeats');
    }
};
