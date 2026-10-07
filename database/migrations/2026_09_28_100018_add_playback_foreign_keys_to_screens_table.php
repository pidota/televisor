<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->foreign('current_playlist_id')
                ->references('id')
                ->on('playlists')
                ->nullOnDelete();

            $table->foreign('current_media_asset_id')
                ->references('id')
                ->on('media_assets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->dropForeign(['current_playlist_id']);
            $table->dropForeign(['current_media_asset_id']);
        });
    }
};
