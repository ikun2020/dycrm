<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-6">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('Choose Theme Color') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('This only changes your own admin button and accent color.') }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($this->getThemeColorOptions() as $color => $label)
                    @php($palette = $this->getThemeColorPalette($color))

                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition hover:border-gray-400 dark:border-gray-700 dark:hover:border-gray-500 @if ($themeColor === $color) border-primary-600 ring-2 ring-primary-500/30 dark:border-primary-400 @else border-gray-200 @endif"
                    >
                        <input
                            type="radio"
                            wire:model.live="themeColor"
                            value="{{ $color }}"
                            class="sr-only"
                        />

                        <span class="flex -space-x-1">
                            <span class="h-7 w-7 rounded-full ring-2 ring-white dark:ring-gray-900" style="background: {{ $palette[400] }}"></span>
                            <span class="h-7 w-7 rounded-full ring-2 ring-white dark:ring-gray-900" style="background: {{ $palette[600] }}"></span>
                            <span class="h-7 w-7 rounded-full ring-2 ring-white dark:ring-gray-900" style="background: {{ $palette[800] }}"></span>
                        </span>

                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            @error('themeColor')
                <p class="mt-3 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
        </section>

        <div class="flex gap-3">
            <x-filament::button type="submit">
                {{ __('Save Theme Color') }}
            </x-filament::button>

            <x-filament::button
                tag="a"
                color="gray"
                :href="\Filament\Facades\Filament::getUrl()"
            >
                {{ __('Cancel') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
