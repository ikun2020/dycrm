<?php

namespace App\Jobs;

use App\Models\Creator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Waad\FilamentImportWizard\Models\ImportSession;

class ImportCreatorsChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $sessionId,
        public int $chunkIndex,
        public int $chunkSize,
    ) {}

    public function handle(): void
    {
        $session = ImportSession::query()->find($this->sessionId);

        if (! $session) {
            return;
        }

        $rows = $this->loadCsvRows($session);
        $successRows = 0;
        $failedRows = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            try {
                $record = $this->makeRecord($row, $session->column_mappings ?? []);

                if ($record === null) {
                    $failedRows++;
                    $errors[] = $this->error($rowNumber, '缺少必填字段：达人昵称或 UID');

                    continue;
                }

                Creator::query()->updateOrCreate(
                    ['platform_uid' => $record['platform_uid']],
                    $record,
                );

                $successRows++;
            } catch (Throwable $exception) {
                $failedRows++;
                $errors[] = $this->error($rowNumber, $exception->getMessage());
            }
        }

        $this->updateSession($session, count($rows), $successRows, $failedRows, $errors);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCsvRows(ImportSession $session): array
    {
        $filePath = $this->resolveFilePath((string) $session->file_path);

        if (! $filePath) {
            return [];
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return [];
        }

        $headers = $session->headers ?? [];
        $startRow = ($this->chunkIndex * $this->chunkSize) + 1;
        $endRow = $startRow + $this->chunkSize;
        $rows = [];
        $currentRow = 0;

        while (($cells = fgetcsv($handle)) !== false) {
            if ($currentRow < $startRow) {
                $currentRow++;

                continue;
            }

            if ($currentRow >= $endRow) {
                break;
            }

            $cells = array_slice($cells, 0, count($headers));
            $cells = array_pad($cells, count($headers), null);
            $row = array_combine($headers, $cells);

            if ($row !== false) {
                $rows[$currentRow + 1] = $row;
            }

            $currentRow++;
        }

        fclose($handle);

        return $rows;
    }

    private function resolveFilePath(string $filePath): ?string
    {
        $possiblePaths = [
            storage_path('app/'.$filePath),
            storage_path('app/imports/'.$filePath),
            storage_path('app/livewire-tmp/'.$filePath),
            storage_path('app/livewire/tmp/'.$filePath),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $mappings
     * @return array<string, mixed>|null
     */
    private function makeRecord(array $row, array $mappings): ?array
    {
        $record = [];

        foreach ($row as $header => $value) {
            $field = $mappings[$header] ?? null;

            if (! is_string($field) || $field === '' || str_contains($field, '|')) {
                continue;
            }

            $record[$field] = $this->blankToNull($value);
        }

        $record['nickname'] = $this->stringOrNull($record['nickname'] ?? null);
        $record['platform_uid'] = $this->stringOrNull($record['platform_uid'] ?? null);

        if (! $record['nickname'] || ! $record['platform_uid']) {
            return null;
        }

        return $record;
    }

    private function blankToNull(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return in_array(mb_strtolower($value), ['', '?', '-', '--', 'null', 'n/a', 'na', '未公布', '暂无', '无'], true)
            ? null
            : $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    private function updateSession(ImportSession $session, int $processedRows, int $successRows, int $failedRows, array $errors): void
    {
        DB::transaction(function () use ($session, $processedRows, $successRows, $failedRows, $errors): void {
            $fresh = ImportSession::query()->lockForUpdate()->find($session->id);

            if (! $fresh) {
                return;
            }

            $storedErrors = $fresh->errors ?? [];
            $storedErrors = is_array($storedErrors) ? $storedErrors : [];
            $storedErrors = array_slice(array_merge($storedErrors, $errors), 0, 50);

            $newProcessedRows = (int) $fresh->processed_rows + $processedRows;
            $newSuccessRows = (int) $fresh->success_rows + $successRows;
            $newFailedRows = (int) $fresh->failed_rows + $failedRows;
            $status = $newProcessedRows >= (int) $fresh->total_rows
                ? ($newFailedRows > 0 ? 'completed_with_errors' : 'completed')
                : 'processing';

            $fresh->update([
                'processed_rows' => $newProcessedRows,
                'success_rows' => $newSuccessRows,
                'failed_rows' => $newFailedRows,
                'errors' => $storedErrors,
                'status' => $status,
                'step' => 3,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function error(int $rowNumber, string $message): array
    {
        Log::warning('Creator import row failed.', [
            'import_session_id' => $this->sessionId,
            'row' => $rowNumber,
            'error' => $message,
        ]);

        return [
            'row' => $rowNumber,
            'error' => $message,
        ];
    }
}
