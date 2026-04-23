<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Collect;
use App\Models\CollectItem;
use App\Models\Schedule;
use App\Models\Sort;
use App\Models\SortItem;
use App\Models\Trashbag;
use App\Models\Waste;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed reference data and optionally bulk schedules.
     *
     * For 1 Million schedules, run with:
     *   php artisan db:seed --class=DatabaseSeeder --seed-schedules=1000000
     *
     * Default (test): ~1K schedules spread across the date range.
     */
    public function run(): void
    {
        // 1. Static reference data
        $this->seedReferenceData();

        // 2. Bulk schedules
        $targetSchedules = (int) ($_ENV['SEED_SCHEDULES'] ?? 1000);
        $this->seedBulkSchedules($targetSchedules);

        // 3. Generate collects for past schedules (in chunks, low memory)
        $this->seedCollects();
    }

    protected function seedReferenceData(): void
    {
        Trashbag::factory()->count(10)->create();
        Waste::factory()->count(100)->create();
        Client::factory()->count(50)->create();
        $this->command->info('Reference data seeded: 10 trashbags, 100 wastes, 50 clients.');
    }

    /**
     * Seed schedules in batches to handle millions of rows.
     */
    protected function seedBulkSchedules(int $totalTarget): void
    {
        $this->command->info("Seeding {$totalTarget} schedules in chunks...");

        $clientIds = Client::pluck('id')->toArray();
        $startDate = Carbon::now()->subYears(4)->startOfDay();
        $endDate = Carbon::now()->addYear()->startOfDay();
        $totalDays = $startDate->diffInDays($endDate);
        $schedulesPerDay = (int) ceil($totalTarget / max($totalDays, 1));

        $batch = [];
        $batchSize = 1000;

        for ($day = 0; $day <= $totalDays; $day++) {
            $date = $startDate->copy()->addDays($day);

            for ($i = 0; $i < $schedulesPerDay; $i++) {
                $batch[] = [
                    'client_id' => $clientIds[array_rand($clientIds)],
                    'date' => $date->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('schedules')->insert($batch);
                    $batch = [];
                }
            }
        }

        if (! empty($batch)) {
            DB::table('schedules')->insert($batch);
        }

        $this->command->info('Seeded ' . Schedule::count() . ' schedules.');
    }

    /**
     * Generate collects for past schedules using cursor + raw inserts.
     * Memory efficient - never loads full result set.
     */
    protected function seedCollects(): void
    {
        $this->command->info('Generating collects for past schedules...');

        $trashbagIds = Trashbag::pluck('id')->toArray();
        $wasteIds = Waste::pluck('id')->toArray();

        // Process by date chunks to avoid memory issues
        $startDate = Carbon::now()->subYears(4)->startOfDay();
        $endDate = Carbon::today();
        $chunkDays = 30; // Process 30 days at a time

        $currentDate = $startDate->copy();
        $collectCount = 0;
        $sortCount = 0;

        while ($currentDate <= $endDate) {
            $chunkEnd = $currentDate->copy()->addDays($chunkDays - 1);

            // Get schedule IDs for this chunk that have no collect yet
            $scheduleIds = Schedule::whereBetween('date', [$currentDate->toDateString(), $chunkEnd->toDateString()])
                ->whereDoesntHave('collect')
                ->pluck('id')
                ->toArray();

            if (empty($scheduleIds)) {
                $currentDate->addDays($chunkDays);
                continue;
            }

            $collectBatch = [];
            $collectItemBatch = [];
            $sortBatch = [];
            $sortItemBatch = [];
            $collectIdCounter = DB::table('collects')->max('id') ?? 0;
            $sortIdCounter = DB::table('sorts')->max('id') ?? 0;

            foreach ($scheduleIds as $scheduleId) {
                $collectId = ++$collectIdCounter;
                $status = (mt_rand(0, 100) < 80) ? 'DONE' : 'SKIP';

                $collectBatch[] = [
                    'schedule_id' => $scheduleId,
                    'code' => 'CLT-' . strtoupper(Str::random(8)),
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Collect items: 1-10 trashbags
                $numItems = mt_rand(1, 10);
                $used = [];
                $attempts = 0;
                while (count($collectItemBatch) < $numItems && $attempts < 20) {
                    $tid = $trashbagIds[array_rand($trashbagIds)];
                    if (isset($used[$tid])) { $attempts++; continue; }
                    $used[$tid] = true;
                    $collectItemBatch[] = [
                        'collect_id' => $collectId,
                        'trashbag_id' => $tid,
                        'quantity' => mt_rand(1, 50),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $attempts++;
                }

                // Sort only for DONE
                if ($status === 'DONE') {
                    $sortId = ++$sortIdCounter;
                    $sortBatch[] = [
                        'collect_id' => $collectId,
                        'code' => 'SRT-' . strtoupper(Str::random(8)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $numSortItems = mt_rand(10, 20);
                    $usedW = [];
                    $attemptsS = 0;
                    while (count($sortItemBatch) < count($sortBatch) * $numSortItems && $attemptsS < 50) {
                        $wid = $wasteIds[array_rand($wasteIds)];
                        if (isset($usedW[$wid])) { $attemptsS++; continue; }
                        $usedW[$wid] = true;
                        $sortItemBatch[] = [
                            'sort_id' => $sortId,
                            'waste_id' => $wid,
                            'weight' => mt_rand(1, 50000) / 100,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $attemptsS++;
                    }
                }

                // Batch insert every 500 schedules
                if (count($collectBatch) >= 500) {
                    DB::table('collects')->insert($collectBatch);
                    $collectCount += count($collectBatch);
                    $collectBatch = [];

                    DB::table('collect_items')->insert($collectItemBatch);
                    $collectItemBatch = [];

                    if (! empty($sortBatch)) {
                        DB::table('sorts')->insert($sortBatch);
                        $sortCount += count($sortBatch);
                        $sortBatch = [];
                    }

                    if (! empty($sortItemBatch)) {
                        DB::table('sort_items')->insert($sortItemBatch);
                        $sortItemBatch = [];
                    }
                }
            }

            // Insert remaining
            if (! empty($collectBatch)) {
                DB::table('collects')->insert($collectBatch);
                $collectCount += count($collectBatch);
            }
            if (! empty($collectItemBatch)) {
                DB::table('collect_items')->insert($collectItemBatch);
            }
            if (! empty($sortBatch)) {
                DB::table('sorts')->insert($sortBatch);
                $sortCount += count($sortBatch);
            }
            if (! empty($sortItemBatch)) {
                DB::table('sort_items')->insert($sortItemBatch);
            }

            $currentDate->addDays($chunkDays);
        }

        $this->command->info("Seeded {$collectCount} collects, {$sortCount} sorts.");
    }
}
