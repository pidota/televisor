<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgent_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('layout', 30)->default('text_fullscreen');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->smallInteger('priority')->default(0);
            $table->string('status', 20)->default('draft');
            $table->boolean('applies_to_all_screens')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urgent_messages');
    }
};
