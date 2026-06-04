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
    protected static ?string $navigationGroup = '排期与业绩';
    protected static ?string $modelLabel = '直播排期';
    protected static ?string $pluralModelLabel = '直播排期';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('排期信息')->columns(3)->schema([
                Forms\Components\TextInput::make('title')->label('直播标题')->required()->maxLength(255),
                Forms\Components\Select::make('creator_id')->label('达人')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('商品')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('starts_at')->label('开始时间')->seconds(false)->required(),
                Forms\Components\DateTimePicker::make('ends_at')->label('结束时间')->seconds(false),
                Forms\Components\Select::make('status')->label('状态')->options([
                    'scheduled' => '已排期',
                    'preparing' => '准备中',
                    'live' => '直播中',
                    'finished' => '已结束',
                    'reviewed' => '已复盘',
                    'cancelled' => '已取消',
                ])->default('scheduled')->required(),
                Forms\Components\TextInput::make('slot_fee')->label('坑位费')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label('佣金比例')->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('owner_id')->label('负责人')->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('pre_live_remind_at')->label('直播前提醒')->seconds(false),
                Forms\Components\DateTimePicker::make('review_remind_at')->label('复盘提醒')->seconds(false),
            ]),
            Forms\Components\Textarea::make('script_notes')->label('脚本/货盘备注')->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('review_notes')->label('复盘备注')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('直播')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('达人')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('商品')->searchable(),
                Tables\Columns\TextColumn::make('starts_at')->label('开播时间')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('slot_fee')->label('坑位费')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('负责人'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状态')->options([
                    'scheduled' => '已排期',
                    'preparing' => '准备中',
                    'live' => '直播中',
                    'finished' => '已结束',
                    'reviewed' => '已复盘',
                    'cancelled' => '已取消',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveSessions::route('/'),
            'create' => Pages\CreateLiveSession::route('/create'),
            'edit' => Pages\EditLiveSession::route('/{record}/edit'),
        ];
    }
}
