<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiveSessionResource\Pages;
use App\Models\LiveSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LiveSessionResource extends Resource
{
    protected static ?string $model = LiveSession::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = "\u{6392}\u{671F}\u{4E0E}\u{4E1A}\u{7EE9}";
    protected static ?string $modelLabel = "\u{76F4}\u{64AD}\u{6392}\u{671F}";
    protected static ?string $pluralModelLabel = "\u{76F4}\u{64AD}\u{6392}\u{671F}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Live Plan'))->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label(__('Product'))->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('title')->label(__('Title'))->required()->maxLength(255),
                Forms\Components\DateTimePicker::make('starts_at')->label(__('Starts At'))->seconds(false)->required(),
                Forms\Components\DateTimePicker::make('ends_at')->label(__('Ends At'))->seconds(false),
                Forms\Components\Select::make('status')->label(__('Status'))->options(self::statusOptions())->default('scheduled'),
                Forms\Components\TextInput::make('slot_fee')->label(__('Slot Fee'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label(__('Commission Rate'))->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('owner_id')->label(__('Owner'))->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('pre_live_remind_at')->label(__('Pre-live Reminder'))->seconds(false),
                Forms\Components\DateTimePicker::make('review_remind_at')->label(__('Review Reminder'))->seconds(false),
                Forms\Components\Textarea::make('script_notes')->label(__('Script Notes'))->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('review_notes')->label(__('Review Notes'))->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('Title'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label(__('Product'))->searchable(),
                Tables\Columns\TextColumn::make('starts_at')->label(__('Starts At'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge(),
                Tables\Columns\TextColumn::make('slot_fee')->label(__('Slot Fee'))->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label(__('Status'))->options(self::statusOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveSessions::route('/'),
            'create' => Pages\CreateLiveSession::route('/create'),
            'edit' => Pages\EditLiveSession::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'scheduled' => __('Scheduled'),
            'preparing' => __('Preparing'),
            'live' => __('Live'),
            'finished' => __('Finished'),
            'reviewed' => __('Reviewed'),
            'cancelled' => __('Cancelled'),
        ];
    }
}
