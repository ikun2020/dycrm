<?php

namespace App\Support;

use App\Models\Creator;
use Carbon\Carbon;
use Throwable;

class CreatorCsvImporter
{
    /**
     * @return array{imported:int, failed:int, errors:array<int, string>}
     */
    public function import(string $path): array
    {
        try {
            $rows = $this->readRows($path);
        } catch (Throwable $exception) {
            return ['imported' => 0, 'failed' => 0, 'errors' => [$exception->getMessage()]];
        }

        if (count($rows) < 2) {
            return ['imported' => 0, 'failed' => 0, 'errors' => ['文件没有可导入的数据行。']];
        }

        $headers = array_map(fn ($header): string => $this->normalizeHeader($header), array_shift($rows));
        $columns = $this->resolveColumns($headers);
        $missing = array_diff(['nickname', 'platform', 'platform_uid'], array_keys($columns));

        if ($missing !== []) {
            return ['imported' => 0, 'failed' => count($rows), 'errors' => ['缺少必填列：达人昵称、平台、UID。']];
        }

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRow($row, $columns);

            if ($this->isBlankRow($data)) {
                continue;
            }

            if (blank($data['nickname'] ?? null) || blank($data['platform'] ?? null) || blank($data['platform_uid'] ?? null)) {
                $failed++;
                $errors[] = "第 {$rowNumber} 行缺少达人昵称、平台或 UID。";

                continue;
            }

            try {
                $platform = $this->normalizePlatform((string) $data['platform']);
                $platformUid = $this->stringOrNull($data['platform_uid'] ?? null);
                $creator = $platformUid
                    ? Creator::firstOrNew(['platform' => $platform, 'platform_uid' => $platformUid])
                    : new Creator;

                $creator->fill($this->payload($data, $platform, $platformUid));
                $creator->save();
                $imported++;
            } catch (Throwable $exception) {
                $failed++;
                $errors[] = "第 {$rowNumber} 行导入失败：".$exception->getMessage();
            }
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => array_slice($errors, 0, 8)];
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readRows(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return SimpleXlsx::read($path);
        }

        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'GB18030,GBK,BIG5,UTF-8');
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, int>
     */
    private function resolveColumns(array $headers): array
    {
        $aliases = [
            'platform' => ['平台', '平台必填', 'platform'],
            'nickname' => ['达人昵称', '达人昵称必填', '昵称', 'name', 'nickname'],
            'agency_name' => ['mcn机构', '机构公司', '机构', '公司', 'agencycompany'],
            'region' => ['地区', '区域', 'region'],
            'creator_type' => ['达人类型', '达人类目', '类型', 'creatortype'],
            'platform_uid' => ['uid', '平台账号uid', '平台账号', '平台账号/uid', '账号', '抖音号'],
            'followers_count' => ['粉丝数', '粉丝', 'followers'],
            'follower_tier' => ['粉丝量级', '粉丝层级', 'followertier'],
            'primary_category' => ['主营类型', '主营类目', '主营品类', 'maincategory', 'primarycategory'],
            'reputation_score' => ['口碑分', 'reputationscore'],
            'avg_sales_amount' => ['场均销售额', '场均成交额', 'averagesalesamount'],
            'daily_sales_amount' => ['日均销售额', '日均成交额', 'dailysalesamount'],
            'avg_order_value' => ['客单价', 'averageordervalue'],
            'male_fan_ratio' => ['男粉占比', '男性粉丝占比', 'malefanratio'],
            'female_fan_ratio' => ['女粉占比', '女性粉丝占比', 'femalefanratio'],
            'gender_tendency' => ['性别倾向', 'gendertendency'],
            'province_overview' => ['省份概览', '省份分布', 'provinceoverview'],
            'city_overview' => ['城市概览', '城市分布', 'cityoverview'],
            'phone' => ['手机号', '手机', '电话', 'phone'],
            'wechat' => ['微信', '微信号', 'wechat'],
            'category' => ['类目', '品类', 'category'],
            'avg_viewers' => ['场均观看', '平均观看', 'averageviewers'],
            'quote_fee' => ['报价坑位费', '报价', '坑位费', 'quotefee'],
            'commission_rate' => ['佣金比例', '佣金', 'commissionrate'],
            'cooperation_status' => ['状态', '合作状态', 'status'],
            'tags' => ['标签', 'tags'],
        ];

        $columns = [];

        foreach ($headers as $index => $header) {
            foreach ($aliases as $field => $fieldAliases) {
                $normalizedAliases = array_map(fn ($alias): string => $this->normalizeHeader($alias), $fieldAliases);

                if (in_array($header, $normalizedAliases, true)) {
                    $columns[$field] = $index;
                }
            }
        }

        return $columns;
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $columns
     * @return array<string, string|null>
     */
    private function mapRow(array $row, array $columns): array
    {
        $data = [];

        foreach ($columns as $field => $index) {
            $data[$field] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $data;
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, string $platform, ?string $platformUid): array
    {
        $creatorType = $this->stringOrNull($data['creator_type'] ?? null);
        $primaryCategory = $this->stringOrNull($data['primary_category'] ?? null);
        $legacyCategory = $this->stringOrNull($data['category'] ?? null);

        $payload = [
            'platform' => $platform,
            'nickname' => trim((string) $data['nickname']),
            'agency_name' => $this->stringOrNull($data['agency_name'] ?? null),
            'region' => $this->stringOrNull($data['region'] ?? null),
            'creator_type' => $creatorType,
            'platform_uid' => $platformUid,
            'followers_count' => $this->integer($data['followers_count'] ?? null),
            'follower_tier' => $this->stringOrNull($data['follower_tier'] ?? null),
            'primary_category' => $primaryCategory,
            'category' => $primaryCategory ?: $creatorType ?: $legacyCategory,
            'reputation_score' => $this->decimalOrNull($data['reputation_score'] ?? null),
            'avg_sales_amount' => $this->decimalOrNull($data['avg_sales_amount'] ?? null),
            'daily_sales_amount' => $this->decimalOrNull($data['daily_sales_amount'] ?? null),
            'avg_order_value' => $this->decimalOrNull($data['avg_order_value'] ?? null) ?? 0,
            'male_fan_ratio' => $this->ratioOrNull($data['male_fan_ratio'] ?? null),
            'female_fan_ratio' => $this->ratioOrNull($data['female_fan_ratio'] ?? null),
            'gender_tendency' => $this->stringOrNull($data['gender_tendency'] ?? null),
            'province_overview' => $this->stringOrNull($data['province_overview'] ?? null),
            'city_overview' => $this->stringOrNull($data['city_overview'] ?? null),
        ];

        foreach (['phone', 'wechat'] as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $this->stringOrNull($data[$field]);
            }
        }

        if (array_key_exists('avg_viewers', $data)) {
            $payload['avg_viewers'] = $this->integer($data['avg_viewers']);
        }

        if (array_key_exists('quote_fee', $data)) {
            $payload['quote_fee'] = $this->decimalOrNull($data['quote_fee']) ?? 0;
        }

        if (array_key_exists('commission_rate', $data)) {
            $payload['commission_rate'] = $this->decimalOrNull($data['commission_rate']) ?? 0;
        }

        if (array_key_exists('cooperation_status', $data)) {
            $payload['cooperation_status'] = $this->normalizeStatus($data['cooperation_status']);
        }

        if (array_key_exists('tags', $data)) {
            $payload['tags'] = $this->tags($data['tags']);
        }

        return $payload;
    }

    private function normalizeHeader(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return str_replace([' ', '　', "\t", "\r", "\n", '/', '\\', '（', '）', '(', ')', ':', '：', '*'], '', $value);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function isBlankRow(array $data): bool
    {
        foreach ($data as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }

    private function normalizePlatform(?string $platform): string
    {
        return match (mb_strtolower(trim((string) $platform))) {
            'douyin', 'dy', '抖音' => 'douyin',
            'xiaohongshu', 'xhs', '小红书' => 'xiaohongshu',
            'shipinhao', 'sph', '视频号' => 'shipinhao',
            'kuaishou', 'ks', '快手' => 'kuaishou',
            default => 'other',
        };
    }

    private function normalizeStatus(?string $status): string
    {
        return match (mb_strtolower(trim((string) $status))) {
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

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '未公布' ? null : $value;
    }

    private function integer(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return 0;
        }

        if (str_contains($value, '亿')) {
            return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value) * 100000000));
        }

        if (str_contains($value, '万')) {
            return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value) * 10000));
        }

        return max(0, (int) round((float) preg_replace('/[^\d.]/', '', $value)));
    }

    private function decimalOrNull(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return null;
        }

        if (str_contains($value, '亿')) {
            return round((float) preg_replace('/[^\d.]/', '', $value) * 100000000, 2);
        }

        if (str_contains($value, '万')) {
            return round((float) preg_replace('/[^\d.]/', '', $value) * 10000, 2);
        }

        $value = preg_replace('/[^\d.]/', '', $value);

        return $value === '' ? null : (float) $value;
    }

    private function ratioOrNull(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '未公布') {
            return null;
        }

        $isPercent = str_contains($value, '%');
        $number = $this->decimalOrNull($value);

        if ($number === null) {
            return null;
        }

        if ($isPercent || $number > 1) {
            $number /= 100;
        }

        return min(1, max(0, $number));
    }

    /**
     * @return array<int, string>|null
     */
    private function tags(mixed $value): ?array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,，、]/u', $value) ?: [])));
    }

    private function dateTime(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
