<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('creators')
                ->where('platform_uid', '')
                ->update(['platform_uid' => null]);

            $duplicates = DB::table('creators')
                ->select('platform_uid')
                ->whereNotNull('platform_uid')
                ->where('platform_uid', '!=', '')
                ->groupBy('platform_uid')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('platform_uid');

            foreach ($duplicates as $uid) {
                $ids = DB::table('creators')
                    ->where('platform_uid', $uid)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->pluck('id')
                    ->all();

                $keepId = array_shift($ids);

                if ($keepId === null || $ids === []) {
                    continue;
                }

                foreach (['follow_ups', 'live_sessions', 'samples', 'gmv_records', 'ai_reports'] as $table) {
                    DB::table($table)
                        ->whereIn('creator_id', $ids)
                        ->update(['creator_id' => $keepId]);
                }

                DB::table('creators')
                    ->whereIn('id', $ids)
                    ->delete();
            }
        });

        Schema::table('creators', function (Blueprint $table): void {
            $table->dropIndex('creators_platform_uid_index');
            $table->unique('platform_uid', 'creators_platform_uid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropUnique('creators_platform_uid_unique');
            $table->index('platform_uid', 'creators_platform_uid_index');
        });
    }
};
