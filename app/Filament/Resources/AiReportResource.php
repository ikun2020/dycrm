<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiReportResource\Pages;
use App\Models\AiReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiReportResource extends Resource
{
    protected static ?string $model = AiReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'AI 分析';
    protected static ?string $modelLabel = 'AI 报告';
    protected static ?string $pluralModelLabel = 'AI 报告';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('报告信息')->columns(3)->schema([
                Forms\Components\Select::make('creator_id')->label('达人')->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('report_type')->label('报告类型')->options([
                    'tracking' => '跟踪报告',
                    'score' => '价值评分',
                    'review' => '直播复盘',
                ])->default('tracking')->required(),
                Forms\Components\DateTimePicker::make('generated_at')->label('生成时间')->seconds(false),
                Forms\Components\TextInput::make('score')->label('评分')->numeric()->minValue(0)->maxValue(100)->default(0),
                Forms\Components\TextInput::make('grade')->label('评级')->maxLength(10),
                Forms\Components\Select::make('generated_by')->label('生成/录入人')->relationship('generatedBy', 'name')->searchable()->preload(),
                Forms\Components\Textarea::make('summary')->label('摘要')->rows(5)->required()->columnSpanFull(),
                Forms\Components\Textarea::make('risk_points')->label('风险点')->rows(4)->columnSpanFull(),
                Forms\Components\Textarea::make('next_steps')->label('下一步建议')->rows(4)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.nickname')->label('达人')->searchable(),
                Tables\Columns\TextColumn::make('report_type')->label('类型')->badge(),
                Tables\Columns\TextColumn::make('score')->label('评分')->sortable()->badge(),
                Tables\Columns\TextColumn::make('grade')->label('评级')->badge(),
                Tables\Columns\TextColumn::make('summary')->label('摘要')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('generated_at')->label('生成时间')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiReports::route('/'),
            'create' => Pages\CreateAiReport::route('/create'),
            'edit' => Pages\EditAiReport::route('/{record}/edit'),
        ];
    }
}
