<?php

namespace App\Support;

use App\Models\Creator;
use Carbon\Carbon;

class CreatorCsvImporter
{
    /**
     * @return array{imported:int, failed:int, errors:array<int, string>}
     */
    public function import(string $path): array
    {
        $rows = $this->readRows($path);

        if (count($rows) < 2) {
            return ['imported' => 0, 'failed' => 0, 'errors' => ['CSV 没有可导入的数据行']];
        }

        $headers = array_map(fn ($header): string => $this->normalizeHeader($header), array_shift($rows));
        $columns = $this->resolveColumns($headers);

        $missing = array_diff(['nickname', 'platform'], array_keys($columns));

        if ($missing !== []) {
            return ['imported' => 0, 'failed' => count($rows), 'errors' => ['缺少必填列：达人昵称、平台']];
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

            if (blank($data['nickname'] ?? null) || blank($data['platform'] ?? null)) {
                $failed++;
                $errors[] = "第 {$rowNumber} 行缺少达人昵称或平台";
                continue;
            }

            $platform = $this->normalizePlatform((string) $data['platform']);
            $platformUid = $this->stringOrNull($data['platform_uid'] ?? null);

            $creator = $platformUid
                ? Creator::firstOrNew(['platform' => $platform, 'platform_uid' => $platformUid])
                : new Creator();

            $creator->fill([
                'nickname' => trim((string) $data['nickname']),
                'platform' => $platform,
                'platform_uid' => $platformUid,
                'phone' => $this->stringOrNull($data['phone'] ?? null),
                'wechat' => $this->stringOrNull($data['wechat'] ?? null),
                'agency_name' => $this->stringOrNull($data['agency_name'] ?? null),
                'category' => $this->stringOrNull($data['category'] ?? null),
                'followers_count' => $this->integer($data['followers_count'] ?? null),
                'avg_viewers' => $this->integer($data['avg_viewers'] ?? null),
                'avg_order_value' => $this->decimal($data['avg_order_value'] ?? null),
                'quote_fee' => $this->decimal($data['quote_fee'] ?? null),
                'commission_rate' => $this->decimal($data['commission_rate'] ?? null),
                'cooperation_status' => $this->normalizeStatus($data['cooperation_status'] ?? null),
                'tags' => $this->tags($data['tags'] ?? null),
                'ai_score' => $this->boundedInteger($data['ai_score'] ?? null, 0, 100),
                'ai_grade' => $this->stringOrNull($data['ai_grade'] ?? null),
                'ai_summary' => $this->stringOrNull($data['ai_summary'] ?? null),
                'notes' => $this->stringOrNull($data['notes'] ?? null),
                'last_contacted_at' => $this->dateTime($data['last_contacted_at'] ?? null),
                'next_follow_up_at' => $this->dateTime($data['next_follow_up_at'] ?? null),
            ]);

            $creator->save();
            $imported++;
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => array_slice($errors, 0, 5)];
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readRows(string $path): array
    {
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
     * @param array<int, string> $headers
     * @return array<string, int>
     */
    private function resolveColumns(array $headers): array
    {
        $aliases = [
            'nickname' => ['达人昵称', '达人昵称必填', '昵称', 'nickname'],
            'platform' => ['平台', '平台必填', '平台必填抖音小红书视频号快手其他', 'platform'],
            'platform_uid' => ['平台账号', '平台账号uid', '平台账号手机号', 'uid', '账号'],
            'phone' => ['手机号', '手机', '电话', 'phone'],
            'wechat' => ['微信', '微信号', 'wechat'],
            'agency_name' => ['机构公司', '机构', '公司', 'agencycompany'],
            'category' => ['类目', '品类', 'category'],
            'followers_count' => ['粉丝数', '粉丝', 'followers'],
            'avg_viewers' => ['场均观看', '平均观看', 'averageviewers'],
            'avg_order_value' => ['客单价', 'averageordervalue'],
            'quote_fee' => ['报价坑位费', '报价', '坑位费', 'quotefee'],
            'commission_rate' => ['佣金比例', '佣金', 'commissionrate'],
            'cooperation_status' => ['状态', '合作状态', 'status'],
            'tags' => ['标签', 'tags'],
            'ai_score' => ['ai分数', 'aiscore'],
            'ai_grade' => ['评级', 'grade'],
            'ai_summary' => ['ai摘要', 'aisummary'],
            'notes' => ['备注', 'notes'],
            'last_contacted_at' => ['最近联系时间', 'lastcontactedat'],
            'next_follow_up_at' => ['下次跟进时间', 'nextfollowupat'],
        ];

        $columns = [];

        foreach ($headers as $index => $header) {
            foreach ($aliases as $field => $fieldAliases) {
                if (in_array($header, array_map(fn ($alias): string => $this->normalizeHeader($alias), $fieldAliases), true)) {
                    $columns[$field] = $index;
                }
            }
        }

        return $columns;
    }

    /**
     * @param array<int, string|null> $row
     * @param array<string, int> $columns
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

    private function normalizeHeader(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return str_replace([' ', '　', "\t", "\r", "\n", '/', '\\', '（', '）', '(', ')', ':', '：'], '', $value);
    }

    /**
     * @param array<string, string|null> $data
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

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value): int
    {
        return max(0, (int) preg_replace('/[^\d]/', '', (string) $value));
    }

    private function boundedInteger(mixed $value, int $min, int $max): int
    {
        return min($max, max($min, $this->integer($value)));
    }

    private function decimal(mixed $value): float
    {
        $value = preg_replace('/[^\d.]/', '', (string) $value);

        return $value === '' ? 0.0 : (float) $value;
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
        } catch (\Throwable) {
            return null;
        }
    }
}
