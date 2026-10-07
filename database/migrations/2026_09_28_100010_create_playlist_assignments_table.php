<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->string('assignable_type', 30);
            $table->unsignedBigInteger('assignable_id');
            $table->boolean('is_default')->default(false);
            $table->smallInteger('priority')->default(0);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id']);
            $table->index(['playlist_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_assignments');
    }
};
