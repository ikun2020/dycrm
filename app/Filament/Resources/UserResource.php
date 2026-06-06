<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\PermissionGroup;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                ->description('维护员工登录信息。停用账号后，该员工将无法进入后台。')
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
                        ->default('staff')
                        ->live(),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Can Login'))
                        ->default(true),
                ]),
            Section::make(__('Permission Group'))
                ->description('普通员工默认继承权限组。只有特殊账号才需要开启个人覆盖权限。')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\Select::make('permission_group_id')
                        ->label(__('Permission Group'))
                        ->options(fn (): array => PermissionGroup::query()
                            ->where('is_active', true)
                            ->orderBy('id')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => PermissionGroup::query()->where('name', '商务 BD')->value('id'))
                        ->helperText('超级管理员不受权限组限制；普通员工建议直接选择商务 BD、运营或只读。'),
                    Forms\Components\Toggle::make('use_custom_permissions')
                        ->label(__('Use Custom Permissions'))
                        ->default(false)
                        ->live()
                        ->helperText('开启后，该员工不再继承权限组，而是使用下面的个人权限配置。'),
                ]),
            Section::make(__('Custom Permissions'))
                ->description('仅在需要给某个员工单独放权或收权时使用。删除权限建议保持关闭。')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => (bool) $get('use_custom_permissions'))
                ->components([
                    Forms\Components\CheckboxList::make('menu_permissions')
                        ->label(__('Accessible Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->default(array_keys(User::menuPermissionOptions()))
                        ->helperText(__('Leave all unchecked to block the employee from business menus.')),
                    Forms\Components\CheckboxList::make('editable_menus')
                        ->label(__('Editable Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->default(array_keys(User::menuPermissionOptions()))
                        ->helperText(__('Controls create and edit actions. Employees must also have view permission for the menu.')),
                    Forms\Components\CheckboxList::make('deletable_menus')
                        ->label(__('Deletable Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->helperText(__('Controls delete actions. Keep unchecked for safer daily use.')),
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
                Tables\Columns\TextColumn::make('permissionGroup.name')
                    ->label(__('Permission Group'))
                    ->placeholder('-')
                    ->badge(),
                Tables\Columns\IconColumn::make('use_custom_permissions')
                    ->label(__('Custom Permissions'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label(__('Can Login'))->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label(__('Role'))->options(self::roleOptions()),
                Tables\Filters\SelectFilter::make('permission_group_id')
                    ->label(__('Permission Group'))
                    ->relationship('permissionGroup', 'name'),
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
            ->with('permissionGroup')
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

    public static function roleOptions(): array
    {
        return [
            'super_admin' => __('Super Admin'),
            'staff' => __('Staff'),
        ];
    }
}
