<?php

namespace App\Filament\Resources;

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
    protected static ?string $navigationGroup = '达人管理';
    protected static ?string $modelLabel = '达人';
    protected static ?string $pluralModelLabel = '达人档案';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基础档案')->columns(3)->schema([
                Forms\Components\TextInput::make('nickname')->label('达人昵称')->required()->maxLength(255),
                Forms\Components\TextInput::make('real_name')->label('真实姓名')->maxLength(255),
                Forms\Components\Select::make('platform')->label('平台')->required()->options([
                    'douyin' => '抖音',
                    'taobao' => '淘宝',
                    'kuaishou' => '快手',
                    'other' => '其他',
                ])->default('douyin'),
                Forms\Components\TextInput::make('platform_uid')->label('平台账号/UID')->maxLength(255),
                Forms\Components\TextInput::make('homepage_url')->label('主页链接')->url()->maxLength(255),
                Forms\Components\TextInput::make('region')->label('地区')->maxLength(255),
                Forms\Components\TextInput::make('phone')->label('手机号')->tel()->maxLength(255),
                Forms\Components\TextInput::make('wechat')->label('微信')->maxLength(255),
                Forms\Components\TextInput::make('agency_name')->label('机构/公司')->maxLength(255),
            ]),
            Forms\Components\Section::make('合作画像')->columns(3)->schema([
                Forms\Components\TextInput::make('category')->label('擅长类目')->maxLength(255),
                Forms\Components\TextInput::make('followers_count')->label('粉丝数')->numeric()->default(0),
                Forms\Components\TextInput::make('avg_viewers')->label('场均观看')->numeric()->default(0),
                Forms\Components\TextInput::make('avg_order_value')->label('客单价')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('quote_fee')->label('报价/坑位费')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('佣金比例')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('cooperation_status')->label('合作状态')->required()->options(self::statusOptions())->default('to_develop'),
                Forms\Components\TagsInput::make('tags')->label('标签')->placeholder('高转化、护肤、需跟进'),
                Forms\Components\Select::make('owner_id')->label('负责人')->relationship('owner', 'name')->searchable()->preload(),
            ]),
            Forms\Components\Section::make('AI 评分')->columns(3)->schema([
                Forms\Components\TextInput::make('ai_score')->label('AI 分数')->numeric()->minValue(0)->maxValue(100)->default(0),
                Forms\Components\TextInput::make('ai_grade')->label('评级')->maxLength(10),
                Forms\Components\Textarea::make('ai_summary')->label('AI 摘要')->columnSpanFull()->rows(3),
            ]),
            Forms\Components\Section::make('跟进信息')->columns(2)->schema([
                Forms\Components\DateTimePicker::make('last_contacted_at')->label('最近联系时间')->seconds(false),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label('下次跟进时间')->seconds(false),
                Forms\Components\Textarea::make('notes')->label('备注')->columnSpanFull()->rows(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label('达人')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('platform')->label('平台')->formatStateUsing(fn (string $state): string => self::platformOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('category')->label('类目')->searchable(),
                Tables\Columns\TextColumn::make('followers_count')->label('粉丝')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('cooperation_status')->label('状态')->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('ai_score')->label('AI 分')->sortable()->badge(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label('下次跟进')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('负责人'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cooperation_status')->label('合作状态')->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('platform')->label('平台')->options(self::platformOptions()),
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
            'index' => Pages\ListCreators::route('/'),
            'create' => Pages\CreateCreator::route('/create'),
            'edit' => Pages\EditCreator::route('/{record}/edit'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'to_develop' => '待开发',
            'contacted' => '已触达',
            'communicating' => '沟通中',
            'sample_sent' => '已寄样',
            'scheduled' => '已排期',
            'live' => '直播中',
            'reviewed' => '已复盘',
            'long_term' => '长期合作',
            'paused' => '暂停合作',
            'invalid' => '无效达人',
        ];
    }

    public static function platformOptions(): array
    {
        return [
            'douyin' => '抖音',
            'taobao' => '淘宝',
            'kuaishou' => '快手',
            'other' => '其他',
        ];
    }
}
