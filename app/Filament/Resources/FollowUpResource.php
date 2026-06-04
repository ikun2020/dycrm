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
    protected static ?string $navigationGroup = "\u{8FBE}\u{4EBA}\u{7BA1}\u{7406}";
    protected static ?string $modelLabel = "\u{8DDF}\u{8FDB}";
    protected static ?string $pluralModelLabel = "\u{8DDF}\u{8FDB}\u{8BB0}\u{5F55}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Follow-up Content'))->columns(2)->schema([
                Forms\Components\Select::make('creator_id')->label(__('Creator'))->relationship('creator', 'nickname')->searchable()->preload()->required(),
                Forms\Components\Select::make('user_id')->label(__('User'))->relationship('user', 'name')->searchable()->preload(),
                Forms\Components\Select::make('channel')->label(__('Channel'))->options([
                    'wechat' => __('WeChat'),
                    'phone' => __('Phone'),
                    'douyin' => __('Douyin'),
                    'taobao' => __('Taobao'),
                    'other' => __('Other'),
                ])->default('wechat'),
                Forms\Components\TextInput::make('contact_person')->label(__('Contact Person'))->maxLength(255),
                Forms\Components\DateTimePicker::make('contacted_at')->label(__('Contacted At'))->seconds(false)->required(),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label(__('Next Follow-up At'))->seconds(false),
                Forms\Components\Select::make('status_after')->label(__('Status After'))->options(CreatorResource::statusOptions()),
                Forms\Components\Textarea::make('content')->label(__('Content'))->rows(5)->required()->columnSpanFull(),
                Forms\Components\Textarea::make('next_action')->label(__('Next Action'))->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.nickname')->label(__('Creator'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('channel')->label(__('Channel'))->badge(),
                Tables\Columns\TextColumn::make('contacted_at')->label(__('Contacted At'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('Next Follow-up'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('User')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->label(__('Channel'))->options([
                    'wechat' => __('WeChat'),
                    'phone' => __('Phone'),
                    'douyin' => __('Douyin'),
                    'taobao' => __('Taobao'),
                    'other' => __('Other'),
                ]),
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
            'index' => Pages\ListFollowUps::route('/'),
            'create' => Pages\CreateFollowUp::route('/create'),
            'edit' => Pages\EditFollowUp::route('/{record}/edit'),
        ];
    }
}
