<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\FollowUpResource\Pages;
use App\Models\FollowUp;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FollowUpResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = FollowUp::class;

    protected static ?string $menuPermissionKey = 'follow-ups';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = "\u{8FBE}\u{4EBA}\u{7BA1}\u{7406}";

    protected static ?string $modelLabel = "\u{8DDF}\u{8FDB}";

    protected static ?string $pluralModelLabel = "\u{8DDF}\u{8FDB}\u{8BB0}\u{5F55}";

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Follow-up Content'))
                ->description('记录本次沟通内容、沟通后状态和下一步动作，帮助团队连续跟进。')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                    Forms\Components\Select::make('user_id')->label(__('User'))->relationship('user', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('channel')->label(__('Channel'))->options(self::channelOptions())->default('wechat'),
                    Forms\Components\TextInput::make('contact_person')->label(__('Contact Person'))->maxLength(255),
                    Forms\Components\DateTimePicker::make('contacted_at')->label(__('Contacted At'))->seconds(false)->required(),
                    Forms\Components\DateTimePicker::make('next_follow_up_at')->label(__('Next Follow-up At'))->seconds(false),
                    Forms\Components\Select::make('status_after')->label(__('Status After'))->options(CreatorResource::statusOptions()),
                    Forms\Components\Textarea::make('content')->label(__('Content'))->rows(5)->required()->columnSpanFull(),
                    Forms\Components\Textarea::make('next_action')->label(__('Next Action'))->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('channel')->label(__('Channel'))->badge(),
                Tables\Columns\TextColumn::make('contacted_at')->label(__('Contacted At'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('Next Follow-up'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('User')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->label(__('Channel'))->options(self::channelOptions()),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record): bool => self::canEdit($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::canDeleteAny()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFollowUps::route('/'),
            'create' => Pages\CreateFollowUp::route('/create'),
            'edit' => Pages\EditFollowUp::route('/{record}/edit'),
        ];
    }

    public static function channelOptions(): array
    {
        return [
            'wechat' => __('WeChat'),
            'phone' => __('Phone'),
            'douyin' => __('Douyin'),
            'xiaohongshu' => __('Xiaohongshu'),
            'shipinhao' => __('Shipinhao'),
            'kuaishou' => __('Kuaishou'),
            'other' => __('Other'),
        ];
    }
}
