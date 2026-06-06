<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiSettingResource\Pages;
use App\Models\AiSetting;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AiSettingResource extends Resource
{
    protected static ?string $model = AiSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = "\u{7CFB}\u{7EDF}\u{7BA1}\u{7406}";

    protected static ?string $modelLabel = "AI\u{8BBE}\u{7F6E}";

    protected static ?string $pluralModelLabel = "AI\u{8BBE}\u{7F6E}";

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('AI Provider Settings'))
                ->description('配置兼容 OpenAI Chat Completions 的模型服务。保存后 AI 评分会使用这里的启用配置。')
                ->icon('heroicon-o-cpu-chip')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\Toggle::make('is_enabled')
                        ->label(__('Enable AI'))
                        ->default(false),
                    Forms\Components\TextInput::make('provider_name')
                        ->label(__('Provider Name'))
                        ->placeholder('OpenAI / DeepSeek / Qwen / Moonshot')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('api_base_url')
                        ->label(__('API Base URL'))
                        ->helperText(__('OpenAI-compatible /v1 endpoint, for example https://api.openai.com/v1'))
                        ->required()
                        ->url()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('model')
                        ->label(__('Model'))
                        ->required()
                        ->placeholder('gpt-4o-mini / deepseek-chat / qwen-plus')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('api_key')
                        ->label(__('API Key'))
                        ->helperText(__('The key is stored server-side only and will not be displayed after saving. Use a restricted key and monitor provider spending limits.'))
                        ->password()
                        ->revealable()
                        ->afterStateHydrated(function (Forms\Components\TextInput $component): void {
                            $component->state(null);
                        })
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('timeout')
                        ->label(__('Timeout Seconds'))
                        ->numeric()
                        ->minValue(10)
                        ->maxValue(300)
                        ->default(60),
                    Forms\Components\TextInput::make('temperature')
                        ->label(__('Temperature'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(2)
                        ->step(0.1)
                        ->default(0.2),
                    Forms\Components\TextInput::make('max_tokens')
                        ->label(__('Max Tokens'))
                        ->numeric()
                        ->minValue(300)
                        ->maxValue(8000)
                        ->default(1600),
                    Forms\Components\Textarea::make('system_prompt')
                        ->label(__('System Prompt'))
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_enabled')->label(__('Enable AI'))->boolean(),
                Tables\Columns\TextColumn::make('provider_name')->label(__('Provider Name')),
                Tables\Columns\TextColumn::make('api_base_url')->label(__('API Base URL'))->limit(50),
                Tables\Columns\TextColumn::make('model')->label(__('Model'))->badge(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiSettings::route('/'),
            'create' => Pages\CreateAiSetting::route('/create'),
            'edit' => Pages\EditAiSetting::route('/{record}/edit'),
        ];
    }
}
