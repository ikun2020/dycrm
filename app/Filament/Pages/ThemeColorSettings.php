<?php

namespace App\Filament\Pages;

use App\Support\ThemeColor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ThemeColorSettings extends Page
{
    protected static ?string $slug = 'theme-color';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.theme-color-settings';

    public string $themeColor = ThemeColor::DEFAULT;

    public function mount(): void
    {
        $this->themeColor = ThemeColor::normalize(auth()->user()?->theme_color);
    }

    public function getTitle(): string|Htmlable
    {
        return __('Theme Color');
    }

    /**
     * @return array<string, string>
     */
    public function getThemeColorOptions(): array
    {
        return ThemeColor::options();
    }

    /**
     * @return array<int, string>
     */
    public function getThemeColorPalette(string $color): array
    {
        return ThemeColor::palette($color);
    }

    public function save(): void
    {
        $this->validate([
            'themeColor' => ['required', 'string', 'in:'.implode(',', array_keys(ThemeColor::options()))],
        ]);

        auth()->user()?->forceFill([
            'theme_color' => ThemeColor::normalize($this->themeColor),
        ])->save();

        Notification::make()
            ->success()
            ->title(__('Theme color saved'))
            ->send();
    }
}
