<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GmvRecordResource\Pages;
use App\Models\GmvRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GmvRecordResource extends Resource
{
    protected static ?string $model = GmvRecord::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = '排期与业绩';
    protected static ?string $modelLabel = 'GMV';
    protected static ?string $pluralModelLabel = 'GMV 统计';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('业绩数据')->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label('达人')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('商品')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\Select::make('live_session_id')->label('直播场次')->relationship('liveSession', 'title')->searchable()->preload(),
                Forms\Components\DatePicker::make('recorded_on')->label('统计日期')->required(),
                Forms\Components\TextInput::make('gmv')->label('GMV')->numeric()->prefix('¥')->default(0)->required(),
                Forms\Components\TextInput::make('orders_count')->label('订单数')->numeric()->default(0),
                Forms\Components\TextInput::make('refunds_count')->label('退款单数')->numeric()->default(0),
                Forms\Components\TextInput::make('refund_amount')->label('退款金额')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('commission_amount')->label('佣金成本')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('slot_fee')->label('坑位费')->numeric()->prefix('¥')->default(0),
                Forms\Components\TextInput::make('sample_cost')->label('样品成本')->numeric()->prefix('¥')->default(0),
            ]),
            Forms\Components\Textarea::make('notes')->label('复盘备注')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('recorded_on')->label('日期')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('达人')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('商品')->searchable(),
                Tables\Columns\TextColumn::make('gmv')->label('GMV')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('orders_count')->label('订单')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')->label('佣金')->money('CNY')->sortable(),
                Tables\Columns\TextColumn::make('slot_fee')->label('坑位费')->money('CNY')->sortable(),
            ])
            ->defaultSort('recorded_on', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGmvRecords::route('/'),
            'create' => Pages\CreateGmvRecord::route('/create'),
            'edit' => Pages\EditGmvRecord::route('/{record}/edit'),
        ];
    }
}
