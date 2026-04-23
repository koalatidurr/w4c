<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->enum('status', ['DONE', 'SKIP']);
            $table->timestamps();

            $table->index(['status']);
            $table->index(['schedule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collects');
    }
};
