<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collect_id')->constrained()->onDelete('cascade');
            $table->string('code');
            $table->timestamps();

            $table->unique(['collect_id']);
            $table->index(['collect_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sorts');
    }
};
