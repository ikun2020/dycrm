<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = "\u{7CFB}\u{7EDF}\u{7BA1}\u{7406}";
    protected static ?string $modelLabel = "\u{5458}\u{5DE5}\u{8D26}\u{53F7}";
    protected static ?string $pluralModelLabel = "\u{5458}\u{5DE5}\u{8D26}\u{53F7}";

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Account'))->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label(__('Role'))
                    ->required()
                    ->options(self::roleOptions())
                    ->default('staff'),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('Can Login'))
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label(__('Email'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label(__('Role'))
                    ->formatStateUsing(fn (string $state): string => self::roleOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')->label(__('Can Login'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label(__('Role'))->options(self::roleOptions()),
                Tables\Filters\TernaryFilter::make('is_active')->label(__('Can Login')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function roleOptions(): array
    {
        return [
            'super_admin' => __('Super Admin'),
            'staff' => __('Staff'),
        ];
    }
}
