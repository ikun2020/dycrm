<?php

namespace App\Filament\Resources\NitikErrorResource\Pages;

use App\Filament\Resources\NitikErrorResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewNitikError extends ViewRecord
{
    protected static string $resource = NitikErrorResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('错误信息')
                    ->schema([
                        TextEntry::make('exception_class')
                            ->label('异常类型')
                            ->badge()
                            ->color('danger'),
                        TextEntry::make('level')
                            ->label('级别')
                            ->badge()
                            ->color(fn (string $state): string => match (strtolower($state)) {
                                'error', 'critical', 'emergency' => 'danger',
                                'warning' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('message')
                            ->label('错误信息'),
                        TextEntry::make('file')
                            ->label('文件'),
                        TextEntry::make('line')
                            ->label('行号'),
                    ]),
                Section::make('出现次数')
                    ->schema([
                        TextEntry::make('count')
                            ->label('累计次数')
                            ->badge(),
                        TextEntry::make('first_seen_at')
                            ->label('首次出现')
                            ->dateTime('Y-m-d H:i:s'),
                        TextEntry::make('last_seen_at')
                            ->label('最近出现')
                            ->dateTime('Y-m-d H:i:s'),
                        IconEntry::make('is_resolved')
                            ->label('已解决')
                            ->boolean(),
                    ]),
                Section::make('堆栈追踪')
                    ->schema([
                        TextEntry::make('stack_trace')
                            ->label('')
                            ->columnSpanFull()
                            ->html()
                            ->formatStateUsing(fn (?string $state): string => '<div class="bg-gray-950 dark:bg-gray-900 p-4 rounded-lg overflow-y-auto max-h-[520px] border border-gray-800 ring-1 ring-white/5"><pre class="font-mono text-sm leading-relaxed whitespace-pre-wrap text-gray-100">'.e($state ?: '-').'</pre></div>'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
