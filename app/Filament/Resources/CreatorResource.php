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
    protected static ?string $navigationGroup = "\u{8FBE}\u{4EBA}\u{7BA1}\u{7406}";
    protected static ?string $modelLabel = "\u{8FBE}\u{4EBA}";
    protected static ?string $pluralModelLabel = "\u{8FBE}\u{4EBA}\u{6863}\u{6848}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Basic Profile'))->columns(3)->schema([
                Forms\Components\TextInput::make('nickname')->label(__('Nickname'))->required()->maxLength(255),
                Forms\Components\TextInput::make('real_name')->label(__('Real Name'))->maxLength(255),
                Forms\Components\Select::make('platform')->label(__('Platform'))->required()->options(self::platformOptions())->default('douyin'),
                Forms\Components\TextInput::make('platform_uid')->label(__('Platform UID'))->maxLength(255),
                Forms\Components\TextInput::make('homepage_url')->label(__('Homepage URL'))->url()->maxLength(255),
                Forms\Components\TextInput::make('region')->label(__('Region'))->maxLength(255),
                Forms\Components\TextInput::make('phone')->label(__('Phone'))->tel()->maxLength(255),
                Forms\Components\TextInput::make('wechat')->label(__('WeChat'))->maxLength(255),
                Forms\Components\TextInput::make('agency_name')->label(__('Agency / Company'))->maxLength(255),
            ]),
            Forms\Components\Section::make(__('Cooperation Profile'))->columns(3)->schema([
                Forms\Components\TextInput::make('category')->label(__('Category'))->maxLength(255),
                Forms\Components\TextInput::make('followers_count')->label(__('Followers'))->numeric()->default(0),
                Forms\Components\TextInput::make('avg_viewers')->label(__('Average Viewers'))->numeric()->default(0),
                Forms\Components\TextInput::make('avg_order_value')->label(__('Average Order Value'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('quote_fee')->label(__('Quote Fee'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label(__('Commission Rate'))->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('cooperation_status')->label(__('Status'))->required()->options(self::statusOptions())->default('to_develop'),
                Forms\Components\TagsInput::make('tags')->label(__('Tags'))->placeholder(__('skincare, high-conversion, follow-up')),
                Forms\Components\Select::make('owner_id')->label(__('Owner'))->relationship('owner', 'name')->searchable()->preload(),
            ]),
            Forms\Components\Section::make(__('AI Score'))->columns(3)->schema([
                Forms\Components\TextInput::make('ai_score')->label(__('AI Score'))->numeric()->minValue(0)->maxValue(100)->default(0),
                Forms\Components\TextInput::make('ai_grade')->label(__('Grade'))->maxLength(10),
                Forms\Components\Textarea::make('ai_summary')->label(__('AI Summary'))->columnSpanFull()->rows(3),
            ]),
            Forms\Components\Section::make(__('Follow-up'))->columns(2)->schema([
                Forms\Components\DateTimePicker::make('last_contacted_at')->label(__('Last Contacted At'))->seconds(false),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label(__('Next Follow-up At'))->seconds(false),
                Forms\Components\Textarea::make('notes')->label(__('Notes'))->columnSpanFull()->rows(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label(__('Creator'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('platform')->label(__('Platform'))->formatStateUsing(fn (string $state): string => self::platformOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('category')->label(__('Category'))->searchable(),
                Tables\Columns\TextColumn::make('followers_count')->label(__('Followers'))->numeric()->sortable(),
                Tables\Columns\TextColumn::make('cooperation_status')->label(__('Status'))->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('ai_score')->label(__('AI Score'))->sortable()->badge(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('Next Follow-up'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cooperation_status')->label(__('Status'))->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('platform')->label(__('Platform'))->options(self::platformOptions()),
            ])
            ->headerActions([
                Tables\Actions\ImportAction::make()
                    ->label(__('Import Creators'))
                    ->importer(CreatorImporter::class),
                Tables\Actions\ExportAction::make()
                    ->label(__('Export Creators'))
                    ->exporter(CreatorExporter::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label(__('Export Selected'))
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
        ];
    }

    public static function platformOptions(): array
    {
        return [
            'douyin' => __('Douyin'),
            'taobao' => __('Taobao'),
            'kuaishou' => __('Kuaishou'),
            'other' => __('Other'),
        ];
    }
}
