<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sort_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sort_id')->constrained()->onDelete('cascade');
            $table->foreignId('waste_id')->constrained()->onDelete('cascade');
            $table->decimal('weight', 10, 2);
            $table->timestamps();

            $table->index(['sort_id']);
            $table->index(['waste_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sort_items');
    }
};
