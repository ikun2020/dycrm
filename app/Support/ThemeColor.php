<?php

namespace App\Support;

use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;

class ThemeColor
{
    public const DEFAULT = 'rose';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'rose' => __('Rose'),
            'green' => __('Green'),
            'sky' => __('Sky'),
            'pink' => __('Pink'),
            'slate' => __('Slate'),
        ];
    }

    public static function normalize(?string $color): string
    {
        return array_key_exists((string) $color, self::options()) ? (string) $color : self::DEFAULT;
    }

    /**
     * @return array<int, string>
     */
    public static function palette(?string $color): array
    {
        return match (self::normalize($color)) {
            'green' => Color::Green,
            'sky' => Color::Sky,
            'pink' => Color::Pink,
            'slate' => Color::Slate,
            default => Color::Rose,
        };
    }

    public static function styleTagForUser(?User $user): HtmlString
    {
        $palette = self::palette($user?->theme_color);
        $variables = collect($palette)
            ->map(fn (string $value, int $shade): string => "--primary-{$shade}: {$value};")
            ->implode('');

        return new HtmlString("<style id=\"dycrm-theme-color\">:root{{$variables}}</style>");
    }
}
