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
    protected static ?string $navigationGroup = 'Schedule & GMV';
    protected static ?string $modelLabel = 'Live Session';
    protected static ?string $pluralModelLabel = 'Live Sessions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Live Plan')->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label('Creator')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Product')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('title')->label('Title')->required()->maxLength(255),
                Forms\Components\DateTimePicker::make('starts_at')->label('Starts At')->seconds(false)->required(),
                Forms\Components\DateTimePicker::make('ends_at')->label('Ends At')->seconds(false),
                Forms\Components\Select::make('status')->label('Status')->options(self::statusOptions())->default('scheduled'),
                Forms\Components\TextInput::make('slot_fee')->label('Slot Fee')->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('Commission Rate')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('owner_id')->label('Owner')->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('pre_live_remind_at')->label('Pre-live Reminder')->seconds(false),
                Forms\Components\DateTimePicker::make('review_remind_at')->label('Review Reminder')->seconds(false),
                Forms\Components\Textarea::make('script_notes')->label('Script Notes')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('review_notes')->label('Review Notes')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('Creator')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('starts_at')->label('Starts At')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('slot_fee')->label('Slot Fee')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
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
            'scheduled' => 'Scheduled',
            'preparing' => 'Preparing',
            'live' => 'Live',
            'finished' => 'Finished',
            'reviewed' => 'Reviewed',
            'cancelled' => 'Cancelled',
        ];
    }
}
