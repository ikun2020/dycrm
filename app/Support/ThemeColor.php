<?php

namespace App\Support;

use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;

class ThemeColor
{
    public const DEFAULT = 'rose';

    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'rose' => 'Rose',
        'green' => 'Green',
        'sky' => 'Sky',
        'pink' => 'Pink',
        'slate' => 'Slate',
    ];

    /**
     * RGB channel values for CSS color functions like rgb(var(--token) / 0.2).
     *
     * @var array<string, array<int, string>>
     */
    private const RGB_PALETTES = [
        'rose' => [
            50 => '255 241 242',
            100 => '255 228 230',
            200 => '254 205 211',
            300 => '253 164 175',
            400 => '251 113 133',
            500 => '244 63 94',
            600 => '225 29 72',
            700 => '190 18 60',
            800 => '159 18 57',
            900 => '136 19 55',
            950 => '76 5 25',
        ],
        'green' => [
            50 => '240 253 244',
            100 => '220 252 231',
            200 => '187 247 208',
            300 => '134 239 172',
            400 => '74 222 128',
            500 => '34 197 94',
            600 => '22 163 74',
            700 => '21 128 61',
            800 => '22 101 52',
            900 => '20 83 45',
            950 => '5 46 22',
        ],
        'sky' => [
            50 => '240 249 255',
            100 => '224 242 254',
            200 => '186 230 253',
            300 => '125 211 252',
            400 => '56 189 248',
            500 => '14 165 233',
            600 => '2 132 199',
            700 => '3 105 161',
            800 => '7 89 133',
            900 => '12 74 110',
            950 => '8 47 73',
        ],
        'pink' => [
            50 => '253 242 248',
            100 => '252 231 243',
            200 => '251 207 232',
            300 => '249 168 212',
            400 => '244 114 182',
            500 => '236 72 153',
            600 => '219 39 119',
            700 => '190 24 93',
            800 => '157 23 77',
            900 => '131 24 67',
            950 => '80 7 36',
        ],
        'slate' => [
            50 => '248 250 252',
            100 => '241 245 249',
            200 => '226 232 240',
            300 => '203 213 225',
            400 => '148 163 184',
            500 => '100 116 139',
            600 => '71 85 105',
            700 => '51 65 85',
            800 => '30 41 59',
            900 => '15 23 42',
            950 => '2 6 23',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::LABELS);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::LABELS)
            ->map(fn (string $label): string => __($label))
            ->all();
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::keys());
    }

    public static function normalize(?string $color): string
    {
        return array_key_exists((string) $color, self::LABELS) ? (string) $color : self::DEFAULT;
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

    /**
     * Stable RGB tokens for custom CSS that should not depend on Filament's
     * internal color variable format.
     *
     * @return array<int, string>
     */
    public static function cssRgbPalette(?string $color): array
    {
        return self::RGB_PALETTES[self::normalize($color)];
    }

    public static function swatchStyle(string $color): string
    {
        $palette = self::cssRgbPalette($color);

        return "background: linear-gradient(135deg, rgb({$palette[400]}), rgb({$palette[600]}));";
    }

    public static function styleTagForUser(?User $user): HtmlString
    {
        $palette = self::palette($user?->theme_color);
        $variables = collect($palette)
            ->map(fn (string $value, int $shade): string => "--primary-{$shade}: {$value};")
            ->implode('');
        $customVariables = collect(self::cssRgbPalette($user?->theme_color))
            ->map(fn (string $value, int $shade): string => "--dycrm-theme-{$shade}: {$value};")
            ->implode('');

        return new HtmlString("<style id=\"dycrm-theme-color\">:root{{$variables}{$customVariables}}</style>");
    }
}
