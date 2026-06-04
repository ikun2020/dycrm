<?php

namespace App\Filament\Widgets;

class CreatorResourceStatus
{
    public static function label(?string $status): string
    {
        return match ($status) {
            'to_develop' => __('To Develop'),
            'contacted' => __('Contacted'),
            'communicating' => __('Communicating'),
            'sample_sent' => __('Sample Sent'),
            'scheduled' => __('Scheduled'),
            'live' => __('Live'),
            'reviewed' => __('Reviewed'),
            'long_term' => __('Long-term'),
            'paused' => __('Paused'),
            'invalid' => __('Invalid'),
            default => (string) $status,
        };
    }

    public static function platform(?string $platform): string
    {
        return match ($platform) {
            'douyin' => __('Douyin'),
            'taobao' => __('Taobao'),
            'kuaishou' => __('Kuaishou'),
            'other' => __('Other'),
            default => (string) $platform,
        };
    }
}
