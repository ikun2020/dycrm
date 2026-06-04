<?php

namespace App\Filament\Resources;

use App\Filament\Exports\CreatorExporter;
use App\Filament\Imports\CreatorImporter;
use App\Filament\Resources\CreatorResource\Pages;
use App\Models\Creator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreatorResource extends Resource
{
    protected static ?string $model = Creator::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Creators';
    protected static ?string $modelLabel = 'Creator';
    protected static ?string $pluralModelLabel = 'Creator Profiles';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Profile')->columns(3)->schema([
                Forms\Components\TextInput::make('nickname')->label('Nickname')->required()->maxLength(255),
                Forms\Components\TextInput::make('real_name')->label('Real Name')->maxLength(255),
                Forms\Components\Select::make('platform')->label('Platform')->required()->options(self::platformOptions())->default('douyin'),
                Forms\Components\TextInput::make('platform_uid')->label('Platform UID')->maxLength(255),
                Forms\Components\TextInput::make('homepage_url')->label('Homepage URL')->url()->maxLength(255),
                Forms\Components\TextInput::make('region')->label('Region')->maxLength(255),
                Forms\Components\TextInput::make('phone')->label('Phone')->tel()->maxLength(255),
                Forms\Components\TextInput::make('wechat')->label('WeChat')->maxLength(255),
                Forms\Components\TextInput::make('agency_name')->label('Agency / Company')->maxLength(255),
            ]),
            Forms\Components\Section::make('Cooperation Profile')->columns(3)->schema([
                Forms\Components\TextInput::make('category')->label('Category')->maxLength(255),
                Forms\Components\TextInput::make('followers_count')->label('Followers')->numeric()->default(0),
                Forms\Components\TextInput::make('avg_viewers')->label('Average Viewers')->numeric()->default(0),
                Forms\Components\TextInput::make('avg_order_value')->label('Average Order Value')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('quote_fee')->label('Quote Fee')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('Commission Rate')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('cooperation_status')->label('Status')->required()->options(self::statusOptions())->default('to_develop'),
                Forms\Components\TagsInput::make('tags')->label('Tags')->placeholder('skincare, high-conversion, follow-up'),
                Forms\Components\Select::make('owner_id')->label('Owner')->relationship('owner', 'name')->searchable()->preload(),
            ]),
            Forms\Components\Section::make('AI Score')->columns(3)->schema([
                Forms\Components\TextInput::make('ai_score')->label('AI Score')->numeric()->minValue(0)->maxValue(100)->default(0),
                Forms\Components\TextInput::make('ai_grade')->label('Grade')->maxLength(10),
                Forms\Components\Textarea::make('ai_summary')->label('AI Summary')->columnSpanFull()->rows(3),
            ]),
            Forms\Components\Section::make('Follow-up')->columns(2)->schema([
                Forms\Components\DateTimePicker::make('last_contacted_at')->label('Last Contacted At')->seconds(false),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label('Next Follow-up At')->seconds(false),
                Forms\Components\Textarea::make('notes')->label('Notes')->columnSpanFull()->rows(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label('Creator')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('platform')->label('Platform')->formatStateUsing(fn (string $state): string => self::platformOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('category')->label('Category')->searchable(),
                Tables\Columns\TextColumn::make('followers_count')->label('Followers')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('cooperation_status')->label('Status')->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('ai_score')->label('AI Score')->sortable()->badge(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label('Next Follow-up')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cooperation_status')->label('Status')->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('platform')->label('Platform')->options(self::platformOptions()),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label('Import Creators')
                    ->importer(CreatorImporter::class),
                Tables\Actions\ExportAction::make()
                    ->label('Export Creators')
                    ->exporter(CreatorExporter::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Selected')
                        ->exporter(CreatorExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreators::route('/'),
            'create' => Pages\CreateCreator::route('/create'),
            'edit' => Pages\EditCreator::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'to_develop' => 'To Develop',
            'contacted' => 'Contacted',
            'communicating' => 'Communicating',
            'sample_sent' => 'Sample Sent',
            'scheduled' => 'Scheduled',
            'live' => 'Live',
            'reviewed' => 'Reviewed',
            'long_term' => 'Long-term',
            'paused' => 'Paused',
            'invalid' => 'Invalid',
        ];
    }

    public static function platformOptions(): array
    {
        return [
            'douyin' => 'Douyin',
            'taobao' => 'Taobao',
            'kuaishou' => 'Kuaishou',
            'other' => 'Other',
        ];
    }
}
