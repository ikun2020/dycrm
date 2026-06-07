<?php

namespace App\Livewire;

use App\Jobs\ImportCreatorsChunk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use RuntimeException;
use Throwable;
use Waad\FilamentImportWizard\Livewire\ImportWizard;
use Waad\FilamentImportWizard\Models\ImportSession;

class CreatorImportWizard extends ImportWizard
{
    private const MAX_UPLOAD_KILOBYTES = 8192;

    private const MAX_IMPORT_ROWS = 50000;

    private const MAX_IMPORT_COLUMNS = 50;

    private const MAX_CELL_LENGTH = 1000;

    private const PREVIEW_ROWS = 20;

    private const PARSE_TIMEOUT_SECONDS = 45;

    private const INTEGER_FIELDS = [
        'followers_count',
        'avg_viewers',
    ];

    private const DECIMAL_FIELDS = [
        'reputation_score',
        'avg_sales_amount',
        'daily_sales_amount',
        'avg_order_value',
        'quote_fee',
        'commission_rate',
    ];

    private const RATIO_FIELDS = [
        'male_fan_ratio',
        'female_fan_ratio',
    ];

    private const IMPORTABLE_FIELDS = [
        'nickname',
        'platform',
        'platform_uid',
        'phone',
        'wechat',
        'region',
        'agency_name',
        'creator_type',
        'category',
        'followers_count',
        'follower_tier',
        'primary_category',
        'reputation_score',
        'avg_sales_amount',
        'daily_sales_amount',
        'avg_viewers',
        'avg_order_value',
        'male_fan_ratio',
        'female_fan_ratio',
        'gender_tendency',
        'province_overview',
        'city_overview',
        'quote_fee',
        'commission_rate',
        'cooperation_status',
        'tags',
        'notes',
    ];

    public function getModelColumns(): array
    {
        return self::IMPORTABLE_FIELDS;
    }

    public function getGroupedModelColumns(): array
    {
        return [
            'fields' => self::IMPORTABLE_FIELDS,
            'relationships' => [],
            'translatables' => [],
        ];
    }

    public function getUniqueRelations(): array
    {
        return [];
    }

    protected function rules(): array
    {
        return [
            'uploadedFile' => ($this->step === 1 ? 'required|' : '').'file|mimes:csv,xlsx,xls|max:'.self::MAX_UPLOAD_KILOBYTES,
        ];
    }

    protected function processUploadedFile(UploadedFile $file): void
    {
        $this->errorMessage = null;

        $clientOriginalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($clientOriginalName, PATHINFO_EXTENSION));

        if (! in_array($extension, ['csv', 'xlsx', 'xls'], true)) {
            $this->errorMessage = '只支持 CSV、XLSX、XLS 导入文件。';

            return;
        }

        $realPath = $file->getRealPath();

        if (! $realPath || ! file_exists($realPath)) {
            $this->errorMessage = '上传文件不存在，请重新选择文件。';

            return;
        }

        try {
            $this->assertSafeFileSize($realPath);
        } catch (Throwable $exception) {
            $this->errorMessage = $exception->getMessage();

            return;
        }

        $importsDir = storage_path('app/imports');

        if (! is_dir($importsDir)) {
            mkdir($importsDir, 0755, true);
        }

        $newFileName = uniqid('', true).'.'.$extension;
        $filePath = 'imports/'.$newFileName;
        $fullPath = storage_path('app/'.$filePath);

        if (! copy($realPath, $fullPath)) {
            $this->errorMessage = '上传文件保存失败，请重新上传。';

            return;
        }

        try {
            $data = $this->runWithImportTimeout(
                fn (): array => $this->parseFile($fullPath, $extension),
            );
        } catch (Throwable $exception) {
            @unlink($fullPath);
            $this->errorMessage = $exception->getMessage();

            Log::warning('Creator import file rejected.', [
                'file_name' => $clientOriginalName,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $this->headers = $data['headers'];
        $this->previewData = $data['rows'];
        $this->totalRows = (int) ($data['totalRows'] ?? count($data['rows']));

        $this->autoMapColumns();
        $this->initializeModelFieldMappings();

        if (! $this->hasRequiredImportMappings()) {
            @unlink($fullPath);
            $this->errorMessage = '导入文件不符合达人导入模板：必须包含“达人昵称”和“UID”。请先下载导入模板后再导入。';

            return;
        }

        $fileSize = filesize($fullPath) ?: 0;
        $fileMime = mime_content_type($fullPath) ?: 'text/csv';

        $this->session = ImportSession::create([
            'user_id' => auth()->id() ?? null,
            'tenant_id' => $this->getTenantId(),
            'model_class' => $this->modelClass,
            'file_path' => $filePath,
            'file_name' => $clientOriginalName,
            'file_size' => $fileSize,
            'file_type' => $fileMime,
            'headers' => $this->headers,
            'column_mappings' => $this->columnMappings,
            'total_rows' => $this->totalRows,
            'step' => 1,
            'status' => 'pending',
            'enable_upsert' => true,
            'upsert_keys' => ['platform_uid'],
            'config' => ['locale_merge_columns' => $this->localeMergeColumns],
        ]);

        $this->enableUpsert = true;
        $this->upsertKeys = ['platform_uid'];
        $this->step = 2;
    }

    public function startImport()
    {
        if (! $this->session) {
            return;
        }

        $this->enableUpsert = true;
        $this->upsertKeys = ['platform_uid'];

        if ($this->totalRows > 0 && $this->totalRows <= 300) {
            $this->queueConnection = 'sync';
        }

        try {
            $this->runWithImportTimeout(fn () => $this->prepareExcelImportFile());
        } catch (Throwable $exception) {
            $this->status = 'failed';
            $this->errorMessage = $exception->getMessage();
            $this->session->update([
                'status' => 'failed',
                'step' => 3,
                'errors' => [[
                    'row' => 0,
                    'error' => $exception->getMessage(),
                ]],
            ]);

            $this->dispatch('importStarted', [
                'message' => $exception->getMessage(),
                'sessionId' => $this->session->id,
            ]);

            return;
        }


        $this->status = 'processing';
        $this->session->update([
            'status' => 'processing',
            'step' => 3,
            'processed_rows' => 0,
            'success_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
            'enable_upsert' => true,
            'upsert_keys' => ['platform_uid'],
        ]);
        $this->session->refresh();

        $chunkSize = $this->chunkSize ?: config('filament-import-wizard.chunk_size', 300);
        $totalChunks = (int) ceil($this->totalRows / $chunkSize);
        $this->totalChunks = $totalChunks;
        $queueConnection = $this->queueConnection ?? config('filament-import-wizard.queue_connection') ?? config('queue.default', 'sync');
        $queueName = $this->queueName ?? config('filament-import-wizard.queue_name');

        for ($i = 0; $i < $totalChunks; $i++) {
            $dispatch = ImportCreatorsChunk::dispatch($this->session->id, $i, $chunkSize)
                ->onConnection($queueConnection);

            if ($queueName) {
                $dispatch->onQueue($queueName);
            }
        }

        if ($queueConnection === 'sync') {
            $this->session->refresh();
            $this->status = $this->session->status;
            $this->processedRows = $this->session->processed_rows ?? 0;
            $this->successRows = $this->session->success_rows ?? 0;
            $this->failedRows = $this->session->failed_rows ?? 0;
            $message = '导入已完成。';
        } else {
            $message = "{$totalChunks} 个导入任务已加入队列。";
        }

        $this->dispatch('importStarted', [
            'message' => $message,
            'sessionId' => $this->session->id,
        ]);
    }

    private function prepareExcelImportFile(): void
    {
        if (! $this->session) {
            return;
        }

        $extension = strtolower(pathinfo((string) $this->session->file_name, PATHINFO_EXTENSION));

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return;
        }

        $sourcePath = $this->resolveImportFilePath((string) $this->session->file_path);

        if (! $sourcePath) {
            return;
        }

        $targetRelativePath = preg_replace('/\.[^.]+$/', '.csv', (string) $this->session->file_path)
            ?: ((string) $this->session->file_path.'.csv');
        $targetPath = storage_path('app/'.$targetRelativePath);

        if (! is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0775, true);
        }

        try {
            [$reader, $highestRow, $highestColumnIndex] = $this->runWithImportTimeout(function () use ($sourcePath): array {
                $this->assertSafeFileSize($sourcePath);

                return $this->safeExcelReader($sourcePath);
            });

            $spreadsheet = $this->runWithImportTimeout(
                fn () => $reader->load($sourcePath),
            );
            $sheet = $spreadsheet->getActiveSheet();
            $headers = $this->session->headers ?: $this->headers;
            $mappings = $this->session->column_mappings ?: $this->columnMappings;
            $handle = fopen($targetPath, 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $cells = $this->excelRowValues($sheet, $rowIndex, min($highestColumnIndex, count($headers)));

                $cells = array_slice($cells, 0, count($headers));
                $cells = array_pad($cells, count($headers), null);

                if (empty(array_filter($cells, fn (mixed $value): bool => filled($value)))) {
                    continue;
                }

                foreach ($cells as $index => $value) {
                    $header = $headers[$index] ?? null;
                    $field = $header ? ($mappings[$header] ?? null) : null;

                    if (is_string($field) && ! str_contains($field, '|')) {
                        $cells[$index] = $this->normalizeImportCellValue($value, $field);
                    }
                }

                fputcsv($handle, $cells);
            }

            fclose($handle);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $this->session->update([
                'file_path' => $targetRelativePath,
                'file_name' => pathinfo((string) $this->session->file_name, PATHINFO_FILENAME).'.csv',
                'file_type' => 'csv',
            ]);
            $this->session->refresh();
        } catch (Throwable $exception) {
            Log::warning('Failed to prepare queued creator import CSV.', [
                'import_session_id' => $this->session->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolveImportFilePath(string $filePath): ?string
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

    private function normalizeImportCellValue(mixed $value, string $field): mixed
    {
        if ($this->isBlankImportValue($value)) {
            return null;
        }

        if (in_array($field, self::INTEGER_FIELDS, true)) {
            return $this->integerImportValue($value);
        }

        if (in_array($field, self::DECIMAL_FIELDS, true)) {
            return $this->decimalImportValue($value);
        }

        if (in_array($field, self::RATIO_FIELDS, true)) {
            return $this->ratioImportValue($value);
        }

        return match ($field) {
            'platform' => $this->normalizeImportPlatform($value),
            'cooperation_status' => $this->normalizeImportStatus($value),
            default => is_string($value) ? trim($value) : $value,
        };
    }

    private function isBlankImportValue(mixed $value): bool
    {
        $value = mb_strtolower(trim((string) $value));

        return in_array($value, ['', '?', '-', '--', 'null', 'n/a', 'na', '未公布', '暂无', '无'], true);
    }

    private function integerImportValue(mixed $value): ?int
    {
        $number = $this->decimalImportValue($value);

        return $number === null ? null : max(0, (int) round($number));
    }

    private function decimalImportValue(mixed $value): ?float
    {
        if ($this->isBlankImportValue($value)) {
            return null;
        }

        $value = trim((string) $value);
        $multiplier = 1;

        if (str_contains($value, '亿')) {
            $multiplier = 100000000;
        } elseif (str_contains($value, '万')) {
            $multiplier = 10000;
        }

        $number = preg_replace('/[^\d.]/u', '', $value);

        return $number === '' ? null : round((float) $number * $multiplier, 4);
    }

    private function ratioImportValue(mixed $value): ?float
    {
        if ($this->isBlankImportValue($value)) {
            return null;
        }

        $value = trim((string) $value);
        $number = $this->decimalImportValue($value);

        if ($number === null) {
            return null;
        }

        if (str_contains($value, '%') || $number > 1) {
            $number /= 100;
        }

        return min(1, max(0, $number));
    }

    private function normalizeImportPlatform(mixed $value): string
    {
        return match (mb_strtolower(trim((string) $value))) {
            'douyin', 'dy', '抖音' => 'douyin',
            'xiaohongshu', 'xhs', '小红书' => 'xiaohongshu',
            'shipinhao', 'sph', '视频号' => 'shipinhao',
            'kuaishou', 'ks', '快手' => 'kuaishou',
            default => 'other',
        };
    }

    private function normalizeImportStatus(mixed $value): string
    {
        return match (mb_strtolower(trim((string) $value))) {
            'contacted', '已触达', '已联系' => 'contacted',
            'communicating', '沟通中' => 'communicating',
            'sample_sent', '已寄样' => 'sample_sent',
            'scheduled', '已排期' => 'scheduled',
            'live', '直播中' => 'live',
            'reviewed', '已复盘' => 'reviewed',
            'long_term', '长期合作' => 'long_term',
            'paused', '暂停合作' => 'paused',
            'invalid', '无效达人' => 'invalid',
            default => 'to_develop',
        };
    }

    protected function autoMapColumns(): void
    {
        $aliases = [
            '达人昵称' => 'nickname',
            '昵称' => 'nickname',
            '达人名' => 'nickname',
            '名称' => 'nickname',
            '平台' => 'platform',
            'uid' => 'platform_uid',
            '平台账号' => 'platform_uid',
            '平台账号uid' => 'platform_uid',
            '平台账号/uid' => 'platform_uid',
            '抖音号' => 'platform_uid',
            '手机号' => 'phone',
            '手机' => 'phone',
            '微信' => 'wechat',
            '地区' => 'region',
            'mcn机构' => 'agency_name',
            '机构' => 'agency_name',
            '机构/公司' => 'agency_name',
            '达人类型' => 'creator_type',
            '类目' => 'category',
            '粉丝数' => 'followers_count',
            '粉丝量级' => 'follower_tier',
            '主营类型' => 'primary_category',
            '主营类目' => 'primary_category',
            '口碑分' => 'reputation_score',
            '场均销售额' => 'avg_sales_amount',
            '日均销售额' => 'daily_sales_amount',
            '场均观看' => 'avg_viewers',
            '客单价' => 'avg_order_value',
            '男粉占比' => 'male_fan_ratio',
            '女粉占比' => 'female_fan_ratio',
            '性别倾向' => 'gender_tendency',
            '省份概览' => 'province_overview',
            '城市概览' => 'city_overview',
            '报价' => 'quote_fee',
            '坑位费' => 'quote_fee',
            '报价/坑位费' => 'quote_fee',
            '佣金比例' => 'commission_rate',
            '状态' => 'cooperation_status',
            '标签' => 'tags',
            '备注' => 'notes',
        ];

        foreach ($this->headers as $header) {
            $normalized = Str::of((string) $header)
                ->trim()
                ->lower()
                ->replace([' ', '　', "\t", "\r", "\n"], '')
                ->toString();

            if (isset($aliases[$normalized])) {
                $this->columnMappings[$header] = $aliases[$normalized];
            }
        }
    }

    protected function parseExcel(string $path): array
    {
        $this->assertSafeFileSize($path);

        [$reader, $highestRow, $highestColumnIndex] = $this->safeExcelReader($path);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $rows = [];
        $totalRows = 0;

        for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
            $cells = $this->excelRowValues($sheet, $rowIndex, $highestColumnIndex);

            if ($rowIndex === 1) {
                foreach ($cells as $i => $h) {
                    $headerName = filled($h) ? Str::of((string) $h)->trim()->studly()->toString() : '';
                    $headers[] = $headerName ?: 'Column'.($i + 1);
                }

                continue;
            }

            if ($headers === [] || empty(array_filter($cells, fn (mixed $value): bool => filled($value)))) {
                continue;
            }

            $this->assertSafeCells($cells);
            $totalRows++;

            if ($totalRows > self::MAX_IMPORT_ROWS) {
                throw new RuntimeException('导入文件数据过多，最多支持 '.self::MAX_IMPORT_ROWS.' 行。请拆分文件后再导入。');
            }

            if (count($rows) >= self::PREVIEW_ROWS) {
                continue;
            }

            $cells = array_slice($cells, 0, count($headers));
            $cells = array_pad($cells, count($headers), null);

            $rows[] = array_combine($headers, $cells);
        }

        $headers = $this->trimEmptyTrailingHeaders($headers, $rows);
        $rows = array_map(
            fn (array $row): array => array_intersect_key($row, array_flip($headers)),
            $rows,
        );
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return ['headers' => $headers, 'rows' => $rows, 'totalRows' => $totalRows];
    }

    protected function parseCsv(string $path): array
    {
        $this->assertSafeFileSize($path);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('无法读取导入文件，请重新上传。');
        }

        $headers = [];
        $rows = [];
        $totalRows = 0;
        $rowIndex = 0;

        try {
            while (($cells = fgetcsv($handle)) !== false) {
                $rowIndex++;

                if ($rowIndex === 1) {
                    $headers = $this->normalizeImportHeaders($cells);
                    $this->assertSafeColumnCount(count($headers));

                    continue;
                }

                if ($headers === [] || empty(array_filter($cells, fn (mixed $value): bool => filled($value)))) {
                    continue;
                }

                $this->assertSafeCells($cells);
                $totalRows++;

                if ($totalRows > self::MAX_IMPORT_ROWS) {
                    throw new RuntimeException('导入文件数据过多，最多支持 '.self::MAX_IMPORT_ROWS.' 行。请拆分文件后再导入。');
                }

                if (count($rows) >= self::PREVIEW_ROWS) {
                    continue;
                }

                $cells = array_slice($cells, 0, count($headers));
                $cells = array_pad($cells, count($headers), null);
                $row = array_combine($headers, $cells);

                if ($row !== false) {
                    $rows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        return ['headers' => $headers, 'rows' => $rows, 'totalRows' => $totalRows];
    }

    private function assertSafeFileSize(string $path): void
    {
        $size = filesize($path);
        $maxBytes = self::MAX_UPLOAD_KILOBYTES * 1024;

        if ($size === false || $size <= 0) {
            throw new RuntimeException('导入文件为空，请重新选择文件。');
        }

        if ($size > $maxBytes) {
            throw new RuntimeException('导入文件过大，当前最多支持 '.self::MAX_UPLOAD_KILOBYTES / 1024 .'MB。请拆分文件后再导入。');
        }
    }

    private function assertSafeColumnCount(int $columnCount): void
    {
        if ($columnCount <= 0) {
            throw new RuntimeException('导入文件没有可识别的表头。');
        }

        if ($columnCount > self::MAX_IMPORT_COLUMNS) {
            throw new RuntimeException('导入文件列数过多，最多支持 '.self::MAX_IMPORT_COLUMNS.' 列。请使用系统导入模板。');
        }
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function assertSafeCells(array $cells): void
    {
        foreach ($cells as $value) {
            if (is_string($value) && mb_strlen($value) > self::MAX_CELL_LENGTH) {
                throw new RuntimeException('导入文件存在过长单元格内容，请检查后重新导入。');
            }
        }
    }

    /**
     * @return array{0: mixed, 1: int, 2: int}
     */
    private function safeExcelReader(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $worksheets = $reader->listWorksheetInfo($path);
        $firstSheet = $worksheets[0] ?? null;

        if (! $firstSheet) {
            throw new RuntimeException('导入文件没有可读取的工作表。');
        }

        $highestRow = (int) ($firstSheet['totalRows'] ?? 0);
        $highestColumnIndex = (int) ($firstSheet['totalColumns'] ?? 0);

        if ($highestRow <= 1) {
            throw new RuntimeException('导入文件没有可导入的数据。');
        }

        if (($highestRow - 1) > self::MAX_IMPORT_ROWS) {
            throw new RuntimeException('导入文件数据过多，最多支持 '.self::MAX_IMPORT_ROWS.' 行。请拆分文件后再导入。');
        }

        $this->assertSafeColumnCount($highestColumnIndex);

        if (! empty($firstSheet['worksheetName']) && method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly([(string) $firstSheet['worksheetName']]);
        }

        $reader->setReadFilter(new class($highestRow, $highestColumnIndex) implements IReadFilter
        {
            public function __construct(
                private readonly int $maxRow,
                private readonly int $maxColumnIndex,
            ) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= $this->maxRow
                    && Coordinate::columnIndexFromString((string) $columnAddress) <= $this->maxColumnIndex;
            }
        });

        return [$reader, $highestRow, $highestColumnIndex];
    }

    /**
     * @return array<int, mixed>
     */
    private function excelRowValues($sheet, int $rowIndex, int $highestColumnIndex): array
    {
        $cells = [];

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $cells[] = $sheet
                ->getCell(Coordinate::stringFromColumnIndex($columnIndex).$rowIndex)
                ->getValue();
        }

        return $cells;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array<int, string>
     */
    private function normalizeImportHeaders(array $cells): array
    {
        $headers = [];

        foreach ($cells as $i => $h) {
            $headerValue = is_string($h) ? preg_replace('/^\xEF\xBB\xBF/', '', $h) : $h;
            $headerName = filled($headerValue) ? Str::of((string) $headerValue)->trim()->studly()->toString() : '';
            $headers[] = $headerName ?: 'Column'.($i + 1);
        }

        return $headers;
    }

    private function hasRequiredImportMappings(): bool
    {
        $mappedFields = array_values(array_filter(
            $this->columnMappings,
            fn (mixed $field): bool => is_string($field) && ! str_contains($field, '|'),
        ));

        return in_array('nickname', $mappedFields, true)
            && in_array('platform_uid', $mappedFields, true);
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function runWithImportTimeout(callable $callback): mixed
    {
        if (! function_exists('pcntl_alarm') || ! function_exists('pcntl_signal')) {
            return $callback();
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, function (): void {
            throw new RuntimeException('导入文件处理超时，请确认文件来自系统导入模板，或拆分后重新导入。');
        });
        pcntl_alarm(self::PARSE_TIMEOUT_SECONDS);

        try {
            return $callback();
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, SIG_DFL);
        }
    }
}
