<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\ChecksMenuPermission;
use App\Filament\Resources\CreatorResource\Pages;
use App\Filament\Resources\CreatorResource\RelationManagers\FollowUpsRelationManager;
use App\Models\Creator;
use App\Models\FollowUp;
use App\Models\Product;
use App\Support\SimpleXlsx;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Waad\FilamentImportWizard\Actions\ImportWizardAction;

class CreatorResource extends Resource
{
    use ChecksMenuPermission;

    protected static ?string $model = Creator::class;

    protected static ?string $menuPermissionKey = 'creators';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = '达人管理';

    protected static ?string $modelLabel = '达人';

    protected static ?string $pluralModelLabel = '达人档案';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('身份信息')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
                    Forms\Components\Select::make('platform')
                        ->label('平台')
                        ->required()
                        ->options(self::platformOptions())
                        ->default('douyin'),
                    Forms\Components\TextInput::make('nickname')
                        ->label('达人昵称')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('agency_name')
                        ->label('MCN机构')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('region')
                        ->label('地区')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('creator_type')
                        ->label('达人类型')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('platform_uid')
                        ->label('UID')
                        ->required()
                        ->unique(Creator::class, 'platform_uid', ignoreRecord: true)
                        ->maxLength(255),
                ]),
            Section::make('数据画像')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 4])
                ->components([
                    Forms\Components\TextInput::make('followers_count')
                        ->label('粉丝数')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('follower_tier')
                        ->label('粉丝量级')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('primary_category')
                        ->label('主营类型')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('reputation_score')
                        ->label('口碑分')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('avg_sales_amount')
                        ->label('场均销售额')
                        ->numeric()
                        ->prefix('CNY')
                        ->step(0.01),
                    Forms\Components\TextInput::make('daily_sales_amount')
                        ->label('日均销售额')
                        ->numeric()
                        ->prefix('CNY')
                        ->step(0.01),
                    Forms\Components\TextInput::make('avg_order_value')
                        ->label('客单价')
                        ->numeric()
                        ->prefix('CNY')
                        ->default(0)
                        ->step(0.01),
                    Forms\Components\TextInput::make('gender_tendency')
                        ->label('性别倾向')
                        ->maxLength(255),
                ]),
            Section::make('人群地域')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\TextInput::make('male_fan_ratio')
                        ->label('男粉占比')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01)
                        ->placeholder('0.10'),
                    Forms\Components\TextInput::make('female_fan_ratio')
                        ->label('女粉占比')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01)
                        ->placeholder('0.90'),
                    Forms\Components\Textarea::make('province_overview')
                        ->label('省份概览')
                        ->rows(3),
                    Forms\Components\Textarea::make('city_overview')
                        ->label('城市概览')
                        ->rows(3),
                ]),
            Section::make('内部管理')
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
                    Forms\Components\Select::make('cooperation_status')
                        ->label('状态')
                        ->required()
                        ->options(self::statusOptions())
                        ->default('to_develop'),
                    Forms\Components\TagsInput::make('tags')
                        ->label('标签')
                        ->placeholder('护肤、高转化、需跟进'),
                    Forms\Components\Select::make('owner_id')
                        ->label('负责人')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload(),
                ]),
            Section::make(__('Follow-up'))
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->components([
                    Forms\Components\DateTimePicker::make('last_contacted_at')->label(__('Last Contacted At'))->seconds(false),
                    Forms\Components\DateTimePicker::make('next_follow_up_at')->label(__('Next Follow-up At'))->seconds(false),
                    Forms\Components\Textarea::make('notes')->label(__('Notes'))->columnSpanFull()->rows(5),
                ]),
            Section::make(__('AI Score'))
                ->columnSpanFull()
                ->columns(['md' => 2, 'xl' => 3])
                ->components([
                    Forms\Components\TextInput::make('ai_score')->label(__('AI Score'))->numeric()->minValue(0)->maxValue(10)->default(0),
                    Forms\Components\TextInput::make('ai_grade')->label(__('Grade'))->maxLength(10),
                    Forms\Components\DateTimePicker::make('ai_scored_at')->label(__('AI Scored At'))->seconds(false),
                    Forms\Components\Textarea::make('ai_summary')->label(__('AI Summary'))->columnSpanFull()->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')
                    ->label('达人昵称')
                    ->searchable(['nickname', 'platform_uid', 'agency_name'])
                    ->limit(16)
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform')
                    ->label('平台')
                    ->formatStateUsing(fn (?string $state): string => self::platformOptions()[$state] ?? (string) $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('creator_type')
                    ->label('达人类型')
                    ->searchable()
                    ->limit(10),
                Tables\Columns\TextColumn::make('followers_count')
                    ->label('粉丝数')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform_uid')
                    ->label('UID')
                    ->searchable()
                    ->limit(16)
                    ->copyable(),
                Tables\Columns\TextColumn::make('cooperation_status')
                    ->label('状态')
                    ->formatStateUsing(fn (?string $state): string => self::statusOptions()[$state] ?? (string) $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('ai_score')
                    ->label(__('AI Score'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('ai_grade')
                    ->label(__('Grade'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('Owner')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cooperation_status')->label('状态')->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('platform')->label('平台')->options(self::platformOptions()),
                Tables\Filters\SelectFilter::make('creator_type')->label('达人类型')->options(fn (): array => self::creatorTypeOptions()),
                Tables\Filters\SelectFilter::make('owner_id')->label(__('Owner'))->relationship('owner', 'name')->searchable()->preload(),
            ])
            ->filtersRemoveAllAction(fn (Action $action): Action => $action->color('primary'))
            ->filtersTriggerAction(fn (Action $action): Action => $action->extraModalFooterActions([
                $table->getFiltersApplyAction()->close(),
                Action::make('resetFilters')
                    ->label(__('filament-tables::table.filters.actions.reset.label'))
                    ->color('primary')
                    ->action('resetTableFiltersForm')
                    ->button(),
            ]))
            ->headerActions([
                Action::make('downloadCreatorImportTemplate')
                    ->label('下载导入模板')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => self::downloadCreatorTemplate()),
                ImportWizardAction::make('importCreators')
                    ->label('导入达人')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('导入达人')
                    ->forModel(Creator::class)
                    ->chunkSize(300)
                    ->queueConnection('redis')
                    ->queueName('default')
                    ->enableUpsert(true)
                    ->upsertKeys(['platform_uid'])
                    ->modalContent(fn () => view('filament.actions.creator-import-wizard-modal', [
                        'modelClass' => Creator::class,
                        'chunkSize' => 300,
                        'enableUpsert' => true,
                        'upsertKeys' => ['platform_uid'],
                        'queueConnection' => 'redis',
                        'queueName' => 'default',
                    ])),
                Action::make('exportCreatorsCsv')
                    ->label('导出达人')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => self::downloadCreatorsSpreadsheet()),
            ])
            ->actions([
                Action::make('generateAiScore')
                    ->label(__('AI Rating'))
                    ->icon('heroicon-o-sparkles')
                    ->visible(fn (): bool => auth()->user()?->canEditMenu('creators') ?? false)
                    ->modalHeading(__('AI Rating'))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->modalContent(fn (Creator $record) => view('filament.actions.creator-ai-diagnosis-modal', [
                        'creator' => $record,
                        'products' => Product::query()
                            ->where('status', 'active')
                            ->orderByDesc('id')
                            ->get(['id', 'name', 'brand', 'category']),
                    ])),
                Action::make('quickFollowUp')
                    ->label('跟进')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->visible(fn (Creator $record): bool => self::canEdit($record))
                    ->modalHeading(fn (Creator $record): string => __('Follow up :creator', ['creator' => $record->nickname]))
                    ->form(fn (): array => self::quickFollowUpForm())
                    ->action(function (Creator $record, array $data): void {
                        self::recordFollowUp($record, $data);

                        Notification::make()
                            ->title(__('Follow-up saved'))
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (Creator $record): bool => self::canEdit($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('exportSelectedCreatorsCsv')
                        ->label(__('Export Selected'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::downloadCreatorsSpreadsheet($records->modelKeys()))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => self::canDeleteAny()),
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

    public static function getRelations(): array
    {
        return [
            FollowUpsRelationManager::class,
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
            'xiaohongshu' => __('Xiaohongshu'),
            'shipinhao' => __('Shipinhao'),
            'kuaishou' => __('Kuaishou'),
            'other' => __('Other'),
        ];
    }

    public static function creatorTypeOptions(): array
    {
        return self::distinctOptions('creator_type');
    }

    public static function followerTierOptions(): array
    {
        return self::distinctOptions('follower_tier');
    }

    public static function primaryCategoryOptions(): array
    {
        return self::distinctOptions('primary_category');
    }

    public static function quickFollowUpForm(): array
    {
        return [
            Forms\Components\Select::make('channel')
                ->label(__('Channel'))
                ->options(FollowUpResource::channelOptions())
                ->default('wechat')
                ->required(),
            Forms\Components\DateTimePicker::make('contacted_at')
                ->label(__('Contacted At'))
                ->default(now())
                ->seconds(false)
                ->required(),
            Forms\Components\DateTimePicker::make('next_follow_up_at')
                ->label(__('Next Follow-up At'))
                ->seconds(false),
            Forms\Components\Select::make('status_after')
                ->label(__('Status After'))
                ->options(self::statusOptions()),
            Forms\Components\TextInput::make('contact_person')
                ->label(__('Contact Person'))
                ->maxLength(255),
            Forms\Components\Textarea::make('content')
                ->label(__('Content'))
                ->rows(4)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('next_action')
                ->label(__('Next Action'))
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    public static function recordFollowUp(Creator $creator, array $data): FollowUp
    {
        $followUp = $creator->followUps()->create([
            'user_id' => auth()->id(),
            'channel' => $data['channel'] ?? 'wechat',
            'contact_person' => $data['contact_person'] ?? null,
            'status_after' => $data['status_after'] ?? null,
            'content' => $data['content'],
            'next_action' => $data['next_action'] ?? null,
            'contacted_at' => $data['contacted_at'] ?? now(),
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
        ]);

        $creator->forceFill([
            'last_contacted_at' => $followUp->contacted_at,
            'next_follow_up_at' => $followUp->next_follow_up_at,
            'cooperation_status' => $followUp->status_after ?: $creator->cooperation_status,
            'owner_id' => $creator->owner_id ?: auth()->id(),
        ])->save();

        return $followUp;
    }

    /**
     * @return array<int, string>
     */
    private static function spreadsheetHeaders(): array
    {
        return [
            '平台',
            '达人昵称',
            'MCN机构',
            '地区',
            '达人类型',
            'UID',
            '粉丝数',
            '粉丝量级',
            '主营类型',
            '口碑分',
            '场均销售额',
            '日均销售额',
            '客单价',
            '男粉占比',
            '女粉占比',
            '性别倾向',
            '省份概览',
            '城市概览',
        ];
    }

    private static function downloadCreatorTemplate()
    {
        $path = tempnam(sys_get_temp_dir(), 'creator-template-').'.xlsx';
        SimpleXlsx::write([
            self::spreadsheetHeaders(),
            [
                '抖音',
                '示例达人',
                '示例MCN',
                '华东',
                '美妆',
                '123456789',
                '100000',
                '腰部达人',
                '护肤',
                '4.80',
                '50000',
                '8000',
                '199',
                '0.20',
                '0.80',
                '女性',
                '广东、江苏、河南省',
                '上海、北京、广州市',
            ],
        ], $path);

        return response()->download(
            $path,
            'creator-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private static function downloadCreatorsSpreadsheet(?array $ids = null)
    {
        $query = Creator::query()->with('owner')->orderBy('id');

        if ($ids !== null) {
            $query->whereKey($ids);
        }

        $rows = [self::spreadsheetHeaders()];

        $query->chunk(500, function ($creators) use (&$rows): void {
            foreach ($creators as $creator) {
                $rows[] = [
                    self::platformOptions()[$creator->platform] ?? $creator->platform,
                    $creator->nickname,
                    $creator->agency_name,
                    $creator->region,
                    $creator->creator_type,
                    $creator->platform_uid,
                    $creator->followers_count,
                    $creator->follower_tier,
                    $creator->primary_category,
                    $creator->reputation_score,
                    $creator->avg_sales_amount,
                    $creator->daily_sales_amount,
                    $creator->avg_order_value,
                    $creator->male_fan_ratio,
                    $creator->female_fan_ratio,
                    $creator->gender_tendency,
                    $creator->province_overview,
                    $creator->city_overview,
                ];
            }
        });

        $path = tempnam(sys_get_temp_dir(), 'creators-').'.xlsx';
        SimpleXlsx::write($rows, $path);

        return response()->download(
            $path,
            'creators-'.now()->format('YmdHis').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return Creator::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }
}
