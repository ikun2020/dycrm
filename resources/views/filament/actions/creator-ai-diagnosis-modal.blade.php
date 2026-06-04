@php
    $aiDiagnosisConfig = [
        'endpoint' => route('creator-ai-diagnosis.run'),
        'creatorsEndpoint' => route('creator-ai-diagnosis.creators'),
        'csrf' => csrf_token(),
        'creatorId' => $creator?->id,
        'creators' => isset($creators)
            ? $creators->map(fn ($creatorOption) => [
                'id' => $creatorOption->id,
                'label' => $creatorOption->nickname,
            ])->values()
            : [],
    ];
@endphp

<div x-data='window.creatorAiDiagnosisModal(@json($aiDiagnosisConfig))' class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
        <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $creator?->nickname ?? 'AI评分' }}</div>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">选择达人和商品后开始AI评分，结果会写入达人评分和 AI 报告。</div>
    </div>

    <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
        @isset($creators)
            <div class="relative" x-on:click.outside="creatorListOpen = false">
                <x-filament::input.wrapper>
                    <x-filament::input
                        x-model="creatorSearch"
                        x-on:focus="creatorListOpen = true"
                        x-on:input.debounce.350ms="searchCreators"
                        x-bind:disabled="running"
                        placeholder="选择/搜索达人"
                    />
                </x-filament::input.wrapper>

                <div
                    x-show="creatorListOpen && creators.length > 0"
                    x-cloak
                    class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                >
                    <template x-for="creator in creators" :key="creator.id">
                        <button
                            type="button"
                            x-on:click="selectCreator(creator)"
                            class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
                            x-text="creator.label"
                        ></button>
                    </template>
                </div>
            </div>
        @endisset

        <x-filament::input.wrapper>
            <x-filament::input.select x-model="productId" x-bind:disabled="running">
                <option value="">选择评分商品</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name }}
                        @if ($product->brand)
                            / {{ $product->brand }}
                        @endif
                        @if ($product->category)
                            / {{ $product->category }}
                        @endif
                    </option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <x-filament::button
            icon="heroicon-o-sparkles"
            x-on:click="start"
            x-bind:disabled="running || ! creatorId || ! productId"
        >
            开始AI评分
        </x-filament::button>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-gray-950 dark:text-white">实时分析报告</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="status"></div>
                </div>

                <div class="flex items-center gap-2" x-show="score || grade">
                    <span class="rounded-md bg-rose-50 px-2 py-1 text-sm font-medium text-rose-700 dark:bg-rose-500/10 dark:text-rose-300" x-text="score ? `分数 ${score}/10` : ''"></span>
                    <span class="rounded-md bg-gray-100 px-2 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200" x-text="grade"></span>
                </div>
            </div>
        </div>

        <div class="max-h-[560px] min-h-[320px] overflow-y-auto px-4 py-4" x-ref="output">
            <template x-if="! finished">
                <div class="whitespace-pre-wrap text-sm leading-7 text-gray-700 dark:text-gray-200" x-text="displayContent || '选择达人和商品后点击开始AI评分，AI 会在这里输出分析过程。'"></div>
            </template>

            <template x-if="finished">
                <div class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg bg-rose-50 p-4 dark:bg-rose-500/10">
                            <div class="text-xs font-medium text-rose-700 dark:text-rose-300">匹配分数</div>
                            <div class="mt-2 flex items-end gap-1">
                                <span class="text-4xl font-semibold text-rose-700 dark:text-rose-300" x-text="score"></span>
                                <span class="pb-1 text-sm text-rose-600 dark:text-rose-300">/10</span>
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">适配评级</div>
                            <div class="mt-3 text-2xl font-semibold text-gray-950 dark:text-white" x-text="grade || '-'"></div>
                        </div>

                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400">评分时间</div>
                            <div class="mt-3 text-base font-medium text-gray-950 dark:text-white" x-text="generatedAt || '-'"></div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white">核心摘要</div>
                        <div class="mt-2 text-sm leading-7 text-gray-700 dark:text-gray-200" x-text="summary || '暂无摘要。'"></div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">主要风险</div>
                            <template x-if="riskPoints.length === 0">
                                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">暂无明显风险。</div>
                            </template>
                            <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                                <template x-for="item in riskPoints" :key="item">
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500"></span>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="text-sm font-semibold text-gray-950 dark:text-white">跟进建议</div>
                            <template x-if="nextSteps.length === 0">
                                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">暂无建议。</div>
                            </template>
                            <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                                <template x-for="item in nextSteps" :key="item">
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                        <span x-text="item"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <details class="rounded-lg border border-gray-200 dark:border-gray-700">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">详细分析</summary>
                        <div class="border-t border-gray-200 px-4 py-4 text-sm leading-7 text-gray-700 dark:border-gray-700 dark:text-gray-200">
                            <div class="whitespace-pre-wrap" x-text="displayContent"></div>
                        </div>
                    </details>
                </div>
            </template>
        </div>
    </div>
</div>
