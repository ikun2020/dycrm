<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionGroupResource\Pages;
use App\Models\PermissionGroup;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermissionGroupResource extends Resource
{
    protected static ?string $model = PermissionGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = '系统管理';

    protected static ?string $modelLabel = '权限组';

    protected static ?string $pluralModelLabel = '权限组';

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
            Section::make(__('Permission Group'))
                ->description('定义一组菜单访问、编辑和删除权限。员工绑定该权限组后会自动继承这些权限。')
                ->icon('heroicon-o-shield-check')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('Active'))
                        ->default(true),
                    Forms\Components\Textarea::make('description')
                        ->label(__('Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make(__('Permission Scope'))
                ->description('删除权限建议只给少数管理员或专门权限组。')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columnSpanFull()
                ->components([
                    Forms\Components\CheckboxList::make('menu_permissions')
                        ->label(__('Accessible Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->default(array_keys(User::menuPermissionOptions()))
                        ->required(),
                    Forms\Components\CheckboxList::make('editable_menus')
                        ->label(__('Editable Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->helperText(__('Controls create and edit actions. Employees must also have view permission for the menu.')),
                    Forms\Components\CheckboxList::make('deletable_menus')
                        ->label(__('Deletable Menus'))
                        ->options(User::menuPermissionOptions())
                        ->columns(['md' => 2, 'xl' => 4])
                        ->helperText(__('Controls delete actions. Keep unchecked for safer daily use.')),
                    Forms\Components\Toggle::make('notify_sample_shipments')
                        ->label(__('Sample Shipment Reminders'))
                        ->helperText(__('Users in this permission group will receive a notification when a sample shipment is created.'))
                        ->default(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label(__('Active'))->boolean(),
                Tables\Columns\IconColumn::make('notify_sample_shipments')->label(__('Sample Shipment Reminders'))->boolean(),
                Tables\Columns\TextColumn::make('users_count')->label(__('Users'))->counts('users')->sortable(),
                Tables\Columns\TextColumn::make('description')->label(__('Description'))->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated At'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label(__('Active')),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('users')->orderBy('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissionGroups::route('/'),
            'create' => Pages\CreatePermissionGroup::route('/create'),
            'edit' => Pages\EditPermissionGroup::route('/{record}/edit'),
        ];
    }
}
