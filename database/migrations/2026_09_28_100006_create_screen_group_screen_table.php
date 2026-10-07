<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_group_screen', function (Blueprint $table) {
            $table->foreignId('screen_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->primary(['screen_group_id', 'screen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_group_screen');
    }
};
