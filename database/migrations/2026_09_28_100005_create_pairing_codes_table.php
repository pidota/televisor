<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->char('code', 6)->unique();
            $table->uuid('screen_uuid');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('screen_id')->nullable()->constrained('screens')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['code', 'expires_at']);
            $table->index('screen_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairing_codes');
    }
};
