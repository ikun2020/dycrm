<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreatorResource\Pages;
use App\Models\Creator;
use App\Support\CreatorCsvImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CreatorResource extends Resource
{
    protected static ?string $model = Creator::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = "\u{8FBE}\u{4EBA}\u{7BA1}\u{7406}";
    protected static ?string $modelLabel = "\u{8FBE}\u{4EBA}";
    protected static ?string $pluralModelLabel = "\u{8FBE}\u{4EBA}\u{6863}\u{6848}";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Basic Profile'))->columns(3)->schema([
                Forms\Components\TextInput::make('nickname')->label(__('Nickname'))->required()->maxLength(255),
                Forms\Components\Select::make('platform')->label(__('Platform'))->required()->options(self::platformOptions())->default('douyin'),
                Forms\Components\TextInput::make('platform_uid')->label(__('Platform UID'))->maxLength(255),
                Forms\Components\TextInput::make('phone')->label(__('Phone'))->tel()->maxLength(255),
                Forms\Components\TextInput::make('wechat')->label(__('WeChat'))->maxLength(255),
                Forms\Components\TextInput::make('agency_name')->label(__('Agency / Company'))->maxLength(255),
            ]),
            Forms\Components\Section::make(__('Cooperation Profile'))->columns(3)->schema([
                Forms\Components\TextInput::make('category')->label(__('Category'))->maxLength(255),
                Forms\Components\TextInput::make('followers_count')->label(__('Followers'))->numeric()->default(0),
                Forms\Components\TextInput::make('avg_viewers')->label(__('Average Viewers'))->numeric()->default(0),
                Forms\Components\TextInput::make('avg_order_value')->label(__('Average Order Value'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('quote_fee')->label(__('Quote Fee'))->numeric()->prefix('CNY')->default(0),
                Forms\Components\TextInput::make('commission_rate')->label(__('Commission Rate'))->numeric()->suffix('%')->default(0),
                Forms\Components\Select::make('cooperation_status')->label(__('Status'))->required()->options(self::statusOptions())->default('to_develop'),
                Forms\Components\TagsInput::make('tags')->label(__('Tags'))->placeholder(__('skincare, high-conversion, follow-up')),
                Forms\Components\Select::make('owner_id')->label(__('Owner'))->relationship('owner', 'name')->searchable()->preload(),
            ]),
            Forms\Components\Section::make(__('AI Score'))->columns(3)->schema([
                Forms\Components\TextInput::make('ai_score')->label(__('AI Score'))->numeric()->minValue(0)->maxValue(100)->default(0),
                Forms\Components\TextInput::make('ai_grade')->label(__('Grade'))->maxLength(10),
                Forms\Components\Textarea::make('ai_summary')->label(__('AI Summary'))->columnSpanFull()->rows(3),
            ]),
            Forms\Components\Section::make(__('Follow-up'))->columns(2)->schema([
                Forms\Components\DateTimePicker::make('last_contacted_at')->label(__('Last Contacted At'))->seconds(false),
                Forms\Components\DateTimePicker::make('next_follow_up_at')->label(__('Next Follow-up At'))->seconds(false),
                Forms\Components\Textarea::make('notes')->label(__('Notes'))->columnSpanFull()->rows(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->label(__('Creator'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('platform')->label(__('Platform'))->formatStateUsing(fn (string $state): string => self::platformOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('category')->label(__('Category'))->searchable(),
                Tables\Columns\TextColumn::make('followers_count')->label(__('Followers'))->numeric()->sortable(),
                Tables\Columns\TextColumn::make('cooperation_status')->label(__('Status'))->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('ai_score')->label(__('AI Score'))->sortable()->badge(),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('Next Follow-up'))->dateTime('Y-m-d H:i')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cooperation_status')->label(__('Status'))->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('platform')->label(__('Platform'))->options(self::platformOptions()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('downloadCreatorImportTemplate')
                    ->label(__('Download Import Template'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'w');

                        echo "\xEF\xBB\xBF";

                        fputcsv($handle, [
                            __('Nickname Required'),
                            __('Platform Required'),
                            __('Platform UID'),
                            __('Phone'),
                            __('WeChat'),
                            __('Agency / Company'),
                            __('Category'),
                            __('Followers'),
                            __('Average Viewers'),
                            __('Average Order Value'),
                            __('Quote Fee'),
                            __('Commission Rate'),
                            __('Status'),
                            __('Tags'),
                            __('AI Score'),
                            __('Grade'),
                            __('AI Summary'),
                            __('Notes'),
                            __('Last Contacted At'),
                            __('Next Follow-up At'),
                        ]);

                        fputcsv($handle, [
                            'example_creator',
                            __('Douyin'),
                            '123456789',
                            '13800000000',
                            'wechat_id',
                            'example_agency',
                            'beauty',
                            '100000',
                            '5000',
                            '99',
                            '3000',
                            '20',
                            __('To Develop'),
                            'skincare,high-conversion',
                            '80',
                            'A',
                            'high conversion potential',
                            'sample notes',
                            now()->subDay()->format('Y-m-d H:i:s'),
                            now()->addDays(3)->format('Y-m-d H:i:s'),
                        ]);

                        fclose($handle);
                    }, 'creator-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),
                Tables\Actions\Action::make('importCreatorsCsv')
                    ->label(__('Import Creators'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label(__('Import File'))
                            ->disk('local')
                            ->directory('imports')
                            ->maxSize(50 * 1024)
                            ->helperText(__('Creator Import Help'))
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $file = is_array($data['file'] ?? null) ? reset($data['file']) : ($data['file'] ?? null);

                        if (! $file) {
                            Notification::make()
                                ->title(__('Import file is required.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = app(CreatorCsvImporter::class)->import(Storage::disk('local')->path($file));
                        Storage::disk('local')->delete($file);

                        $body = __('Creator import completed. :count row(s) imported.', [
                            'count' => $result['imported'],
                        ]);

                        if ($result['failed'] > 0) {
                            $body .= ' '.__(':count row(s) failed. Download the failure file for details.', [
                                'count' => $result['failed'],
                            ]);

                            if ($result['errors'] !== []) {
                                $body .= "\n".implode("\n", $result['errors']);
                            }
                        }

                        $notification = Notification::make()
                            ->title(__('Import Creators'))
                            ->body($body);

                        $result['imported'] > 0
                            ? $notification->success()
                            : $notification->danger();

                        $notification->send();
                    }),
                Tables\Actions\Action::make('exportCreatorsCsv')
                    ->label(__('Export Creators'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => self::downloadCreatorsCsv()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportSelectedCreatorsCsv')
                        ->label(__('Export Selected'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => self::downloadCreatorsCsv($records->modelKeys()))
                        ->deselectRecordsAfterCompletion(),
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

    private static function downloadCreatorsCsv(?array $ids = null)
    {
        $query = Creator::query()->with('owner')->orderBy('id');

        if ($ids !== null) {
            $query->whereKey($ids);
        }

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            echo "\xEF\xBB\xBF";

            fputcsv($handle, [
                __('Nickname'),
                __('Platform'),
                __('Platform UID'),
                __('Phone'),
                __('WeChat'),
                __('Agency / Company'),
                __('Category'),
                __('Followers'),
                __('Average Viewers'),
                __('Average Order Value'),
                __('Quote Fee'),
                __('Commission Rate'),
                __('Status'),
                __('Tags'),
                __('AI Score'),
                __('Grade'),
                __('AI Summary'),
                __('Notes'),
                __('Last Contacted At'),
                __('Next Follow-up At'),
                __('Owner'),
            ]);

            $query->chunk(500, function ($creators) use ($handle): void {
                foreach ($creators as $creator) {
                    fputcsv($handle, [
                        $creator->nickname,
                        self::platformOptions()[$creator->platform] ?? $creator->platform,
                        $creator->platform_uid,
                        $creator->phone,
                        $creator->wechat,
                        $creator->agency_name,
                        $creator->category,
                        $creator->followers_count,
                        $creator->avg_viewers,
                        $creator->avg_order_value,
                        $creator->quote_fee,
                        $creator->commission_rate,
                        self::statusOptions()[$creator->cooperation_status] ?? $creator->cooperation_status,
                        implode(',', $creator->tags ?? []),
                        $creator->ai_score,
                        $creator->ai_grade,
                        $creator->ai_summary,
                        $creator->notes,
                        $creator->last_contacted_at?->format('Y-m-d H:i:s'),
                        $creator->next_follow_up_at?->format('Y-m-d H:i:s'),
                        $creator->owner?->name,
                    ]);
                }
            });

            fclose($handle);
        }, 'creators-'.now()->format('YmdHis').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
