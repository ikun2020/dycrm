<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FollowUpResource\Pages;
use App\Models\FollowUp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FollowUpResource extends Resource
{
    protected static ?string $model = FollowUp::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = '达人管理';
    protected static ?string $modelLabel = '跟进记录';
    protected static ?string $pluralModelLabel = '跟进记录';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('沟通内容')->columns(2)->schema([
                Forms\Components\Select::make('creator_id')->label('达人')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('user_id')->label('跟进人')->relationship('user', 'name')->searchable()->preload(),
                Forms\Components\Select::make('channel')->label('渠道')->options([
                    'wechat' => '微信',
                    'phone' => '电话',
                    'douyin' => '抖音私信',
                    'taobao' => '淘宝',
                    'feishu' => '飞书',
                    'other' => '其他',
                ])->default('wechat')->required(),
                Forms\Components\TextInput::make('contact_person')->label('联系人')->maxLength(255),
                Forms\Components\DateTimePicker::make('contacted_at')->label('沟通时间')->seconds(false)->required(),
                Forms\Components\Select::make('status_after')->label('沟通后状态')->options(CreatorResource::statusOptions()),
                Forms\Components\Textarea::make('content')->label('沟通记录')->rows(5)->required()->columnSpanFull(),
                Forms\Components\Textarea::make('next_action')->label('下一步动作')->rows(3)->columnSpanFull(),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label('下次跟进时间')->seconds(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.nickname')->label('达人')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('channel')->label('渠道')->badge(),
                Tables\Columns\TextColumn::make('content')->label('内容')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('contacted_at')->label('沟通时间')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label('下次跟进')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('跟进人'),
            ])
            ->defaultSort('contacted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->label('渠道')->options([
                    'wechat' => '微信',
                    'phone' => '电话',
                    'douyin' => '抖音私信',
                    'taobao' => '淘宝',
                    'feishu' => '飞书',
                    'other' => '其他',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFollowUps::route('/'),
            'create' => Pages\CreateFollowUp::route('/create'),
            'edit' => Pages\EditFollowUp::route('/{record}/edit'),
        ];
    }
}
