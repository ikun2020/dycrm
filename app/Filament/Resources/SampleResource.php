<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SampleResource\Pages;
use App\Models\Sample;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SampleResource extends Resource
{
    protected static ?string $model = Sample::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = '商品与样品';
    protected static ?string $modelLabel = '样品';
    protected static ?string $pluralModelLabel = '样品管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('样品信息')->columns(3)->schema([
                Forms\Components\TextInput::make('sample_name')->label('样品名称')->required()->maxLength(255),
                Forms\Components\Select::make('creator_id')->label('达人')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('商品')->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('quantity')->label('数量')->numeric()->default(1)->required(),
                Forms\Components\TextInput::make('sample_cost')->label('样品成本')->numeric()->prefix('¥')->default(0),
                Forms\Components\Select::make('status')->label('状态')->options([
                    'pending' => '待寄出',
                    'sent' => '已寄出',
                    'received' => '已签收',
                    'used' => '已使用',
                    'returned' => '已归还',
                    'lost' => '异常/丢失',
                ])->default('pending')->required(),
                Forms\Components\TextInput::make('shipping_company')->label('快递公司')->maxLength(255),
                Forms\Components\TextInput::make('tracking_number')->label('快递单号')->maxLength(255),
                Forms\Components\Select::make('owner_id')->label('负责人')->relationship('owner', 'name')->searchable()->preload(),
                Forms\Components\DateTimePicker::make('sent_at')->label('寄出时间')->seconds(false),
                Forms\Components\DateTimePicker::make('received_at')->label('签收时间')->seconds(false),
            ]),
            Forms\Components\Textarea::make('notes')->label('备注')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sample_name')->label('样品')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('creator.nickname')->label('达人')->searchable(),
                Tables\Columns\TextColumn::make('product.name')->label('商品')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('tracking_number')->label('快递单号')->searchable(),
                Tables\Columns\TextColumn::make('sent_at')->label('寄出')->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('received_at')->label('签收')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('状态')->options([
                    'pending' => '待寄出',
                    'sent' => '已寄出',
                    'received' => '已签收',
                    'used' => '已使用',
                    'returned' => '已归还',
                    'lost' => '异常/丢失',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSamples::route('/'),
            'create' => Pages\CreateSample::route('/create'),
            'edit' => Pages\EditSample::route('/{record}/edit'),
        ];
    }
}
