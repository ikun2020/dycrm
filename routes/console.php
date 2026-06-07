<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Schedule::command('queue:prune-batches --hours=48')->daily();

Artisan::command('imports:prune {--days= : Number of days of import history to keep} {--optimize : Optimize import tables after pruning}', function (): int {
    $days = (int) ($this->option('days') ?: config('dycrm.import_history_days', 7));

    if ($days < 1) {
        $this->error('The retention period must be at least 1 day.');

        return Command::FAILURE;
    }

    $cutoff = now()->subDays($days);
    $oldImportIds = DB::table('imports')
        ->where('created_at', '<', $cutoff)
        ->select('id');

    $failedRows = DB::table('failed_import_rows')
        ->whereIn('import_id', $oldImportIds)
        ->count();

    $imports = DB::table('imports')
        ->where('created_at', '<', $cutoff)
        ->delete();

    $this->info("Pruned {$imports} import record(s) and {$failedRows} failed import row(s) older than {$days} day(s).");

    if ($this->option('optimize')) {
        DB::statement('OPTIMIZE TABLE failed_import_rows');
        DB::statement('OPTIMIZE TABLE imports');

        $this->info('Optimized import tables.');
    }

    return Command::SUCCESS;
})->purpose('Prune old Filament import records and their failed import rows.');

Schedule::command('imports:prune')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('nitik:prune --days=30')
    ->dailyAt('03:45')
    ->withoutOverlapping();
