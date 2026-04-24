<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add strategic indexes for 1M+ row performance.
     * Covers common query patterns in schedules, collects, and sort_items.
     */
    public function up(): void
    {
        // ── schedules ──────────────────────────────────────────────
        // Composite index for date range + client queries (most common)
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['date', 'client_id'], 'idx_schedules_date_client');
        });

        // ── collects ────────────────────────────────────────────────
        // Composite for status + schedule lookups (dashboard filters)
        Schema::table('collects', function (Blueprint $table) {
            $table->index(['status', 'schedule_id'], 'idx_collects_status_schedule');
            $table->index(['schedule_id', 'status'], 'idx_collects_schedule_status');
        });

        // ── sort_items ──────────────────────────────────────────────
        // Composite for waste weight aggregations (most expensive query)
        DB::statement('CREATE INDEX idx_sort_items_waste_date ON sort_items (waste_id) WHERE waste_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_sort_items_weight ON sort_items (weight) WHERE weight IS NOT NULL');

        // ── collect_items ──────────────────────────────────────────
        // For trashbag filtering on dashboard
        Schema::table('collect_items', function (Blueprint $table) {
            $table->index(['collect_id', 'trashbag_id'], 'idx_collect_items_collect_trashbag');
        });

        // ── sort_items join path ────────────────────────────────────
        // Covers: sorts -> collects -> schedules (for date filtering)
        Schema::table('sorts', function (Blueprint $table) {
            $table->index(['collect_id', 'code'], 'idx_sorts_collect_code');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', fn(Blueprint $t) => $t->dropIndex('idx_schedules_date_client'));
        Schema::table('collects', fn(Blueprint $t) => $t->dropIndex('idx_collects_status_schedule'));
        Schema::table('collects', fn(Blueprint $t) => $t->dropIndex('idx_collects_schedule_status'));
        Schema::table('collect_items', fn(Blueprint $t) => $t->dropIndex('idx_collect_items_collect_trashbag'));
        Schema::table('sorts', fn(Blueprint $t) => $t->dropIndex('idx_sorts_collect_code'));
        DB::statement('DROP INDEX IF EXISTS idx_sort_items_waste_date');
        DB::statement('DROP INDEX IF EXISTS idx_sort_items_weight');
    }
};
