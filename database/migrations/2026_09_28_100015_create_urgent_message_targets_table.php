<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgent_message_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('urgent_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['urgent_message_id', 'screen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urgent_message_targets');
    }
};
