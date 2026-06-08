<div class="dycrm-theme-color-menu">
    <div class="dycrm-theme-color-menu__header">
        <x-filament::icon
            icon="heroicon-o-swatch"
            class="dycrm-theme-color-menu__icon"
        />
        <span>{{ __('Theme Color') }}</span>
    </div>

    <form
        action="{{ route('admin.theme-color.update') }}"
        method="post"
        class="dycrm-theme-color-menu__options"
    >
        @csrf

        @foreach ($options as $color => $label)
            <button
                type="submit"
                name="theme_color"
                value="{{ $color }}"
                class="dycrm-theme-color-menu__button"
                @if ($currentColor === $color) aria-current="true" @endif
                title="{{ $label }}"
                aria-label="{{ __('Theme Color') }}: {{ $label }}"
            >
                <span class="dycrm-theme-color-menu__swatch" style="{{ \App\Support\ThemeColor::swatchStyle($color) }}"></span>
            </button>
        @endforeach
    </form>
</div>
