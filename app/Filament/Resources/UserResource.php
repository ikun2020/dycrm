<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $modelLabel = '员工账号';

    protected static ?string $pluralModelLabel = '员工账号';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Account'))
                ->description('维护员工登录信息。具体访问、创建、编辑和删除权限由角色决定。')
                ->icon('heroicon-o-user-circle')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label(__('Email'))
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, ?User $record): void {
                            if ($record !== null) {
                                $component->state($record->email);
                            }
                        })
                        ->disabled(fn (): bool => ! (auth()->user()?->isSuperAdmin() ?? false))
                        ->dehydrated(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                        ->extraInputAttributes(['autocomplete' => 'off'])
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->label(__('Password'))
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->extraInputAttributes(['autocomplete' => 'new-password'])
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Can Login'))
                        ->default(true),
                ]),
            Section::make('角色权限')
                ->description('员工绑定角色后，会自动继承该角色配置的访问、创建、编辑、删除和提醒权限。')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->components([
                    Select::make('role_ids')
                        ->label('角色')
                        ->multiple()
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('guard_name', 'web')
                                ->orderBy('name'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Role $record): string => self::displayRoleName($record->name))
                        ->afterStateHydrated(fn (Select $component, ?User $record): Select => $component->state(
                            $record?->roles()
                                ->pluck('roles.id')
                                ->map(fn (int $id): string => (string) $id)
                                ->all() ?? [],
                        ))
                        ->helperText('普通员工按岗位选择一个或多个角色；选择“超级管理员”会拥有全部后台权限。'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label(__('Email'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->formatStateUsing(fn (string $state): string => self::displayRoleName($state))
                    ->placeholder('-')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')->label(__('Can Login'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('角色')
                    ->relationship('roles', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label(__('Can Login')),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('roles')
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function displayRoleName(string $name): string
    {
        return $name === config('filament-shield.super_admin.name', 'super_admin')
            ? '超级管理员'
            : $name;
    }
}
