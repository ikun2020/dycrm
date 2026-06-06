@php
    $aiDiagnosisConfig = [
        'endpoint' => route('creator-ai-diagnosis.run'),
        'creatorsEndpoint' => route('creator-ai-diagnosis.creators'),
        'csrf' => csrf_token(),
        'creatorId' => $creator?->id,
        'creatorName' => $creator?->nickname,
        'creators' => isset($creators)
            ? $creators->map(fn ($creatorOption) => [
                'id' => $creatorOption->id,
                'label' => $creatorOption->nickname,
            ])->values()
            : [],
    ];
@endphp

<div x-data='window.creatorAiDiagnosisModal(@json($aiDiagnosisConfig))' class="dycrm-ai-modal">
    <section class="dycrm-ai-hero">
        <div>
            <div class="dycrm-ai-title">
                <span class="dycrm-ai-icon">
                    <x-filament::icon icon="heroicon-o-sparkles" />
                </span>
                <div>
                    <div class="dycrm-ai-heading">AI 达人价值评分</div>
                    <div
                        class="dycrm-ai-subtitle"
                        x-text="creatorName ? `当前达人：${creatorName}` : '请选择达人和商品后开始评分'"
                    ></div>
                </div>
            </div>

            <p class="dycrm-ai-help">
                系统会综合达人画像、近期跟进、直播排期、GMV 和商品匹配度，生成评分、风险点和下一步跟进建议。
            </p>
        </div>

        <div class="dycrm-ai-metrics" x-show="score || grade">
            <div class="dycrm-ai-metric">
                <span>评分</span>
                <strong x-text="score || '-'"></strong>
            </div>
            <div class="dycrm-ai-metric">
                <span>评级</span>
                <strong x-text="grade || '-'"></strong>
            </div>
        </div>
    </section>

    <div class="dycrm-ai-controls @isset($creators) dycrm-ai-controls-three @else dycrm-ai-controls-two @endisset">
        @isset($creators)
            <div class="dycrm-ai-creator-picker" x-on:click.outside="creatorListOpen = false">
                <x-filament::input.wrapper>
                    <x-filament::input
                        x-model="creatorSearch"
                        x-on:focus="openCreatorList"
                        x-on:click="openCreatorList"
                        x-on:input.debounce.350ms="searchCreators"
                        x-bind:disabled="running"
                        placeholder="搜索达人昵称"
                    />
                </x-filament::input.wrapper>

                <div
                    x-show="creatorListOpen"
                    x-cloak
                    class="dycrm-ai-search-results"
                    x-on:scroll="loadMoreCreatorsOnScroll"
                    x-on:wheel="loadMoreCreatorsOnWheel"
                >
                    <template x-for="creator in creators" :key="creator.id">
                        <button type="button" x-on:click="selectCreator(creator)" x-text="creator.label"></button>
                    </template>
                    <button
                        type="button"
                        class="dycrm-ai-load-more"
                        x-show="! creatorLoading && creatorHasMore"
                        x-on:click="loadMoreCreators"
                    >
                        加载更多
                    </button>
                    <div x-show="! creatorLoading && creators.length === 0" class="dycrm-ai-search-status">暂无匹配达人</div>
                    <div x-show="creatorLoading" class="dycrm-ai-search-status">加载中...</div>
                    <div x-show="! creatorLoading && ! creatorHasMore && creators.length > 5" class="dycrm-ai-search-status">已加载全部</div>
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
            <span x-text="running ? '评分中...' : '开始 AI 评分'"></span>
        </x-filament::button>
    </div>

    <section class="dycrm-ai-report">
        <header class="dycrm-ai-report-header">
            <div>
                <div class="dycrm-ai-report-title">实时分析报告</div>
                <div class="dycrm-ai-status" x-text="status"></div>
            </div>

            <div class="dycrm-ai-time" x-show="generatedAt" x-text="generatedAt"></div>
        </header>

        <div class="dycrm-ai-output" x-ref="output">
            <template x-if="running">
                <div class="dycrm-ai-thinking">
                    <div class="dycrm-ai-thinking-main">
                        <span class="dycrm-ai-orbit" aria-hidden="true">
                            <span></span>
                        </span>

                        <div>
                            <div class="dycrm-ai-thinking-title" x-text="activeStageTitle"></div>
                            <div class="dycrm-ai-status" x-text="activeStageDescription"></div>
                        </div>
                    </div>

                    <div class="dycrm-ai-progress">
                        <div x-bind:style="`width: ${progress}%`"></div>
                    </div>

                    <div class="dycrm-ai-step-grid">
                        <template x-for="(stage, index) in stages" :key="stage.title">
                            <div
                                class="dycrm-ai-step"
                                x-bind:class="{
                                    'is-active': index === activeStage,
                                    'is-done': index < activeStage,
                                }"
                            >
                                <span class="dycrm-ai-step-dot">
                                    <x-filament::icon icon="heroicon-o-check" />
                                </span>
                                <div>
                                    <strong x-text="stage.title"></strong>
                                    <span x-text="stage.description"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="! running && ! finished">
                <div
                    class="dycrm-ai-pre"
                    x-text="displayContent || '选择达人和商品后点击开始，AI 会在这里输出分析结果。'"
                ></div>
            </template>

            <template x-if="finished">
                <div class="dycrm-ai-finished">
                    <div class="dycrm-ai-block">
                        <div class="dycrm-ai-block-title">核心摘要</div>
                        <div class="dycrm-ai-pre" x-text="summary || '暂无摘要。'"></div>
                    </div>

                    <div class="dycrm-ai-two-columns">
                        <div class="dycrm-ai-block">
                            <div class="dycrm-ai-block-title">主要风险</div>
                            <template x-if="riskPoints.length === 0">
                                <div class="dycrm-ai-status">暂无明显风险。</div>
                            </template>
                            <ul class="dycrm-ai-list">
                                <template x-for="item in riskPoints" :key="item">
                                    <li><span x-text="item"></span></li>
                                </template>
                            </ul>
                        </div>

                        <div class="dycrm-ai-block">
                            <div class="dycrm-ai-block-title">跟进建议</div>
                            <template x-if="nextSteps.length === 0">
                                <div class="dycrm-ai-status">暂无建议。</div>
                            </template>
                            <ul class="dycrm-ai-list">
                                <template x-for="item in nextSteps" :key="item">
                                    <li><span x-text="item"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <details class="dycrm-ai-block dycrm-ai-details">
                        <summary>展开详细分析</summary>
                        <div class="dycrm-ai-pre" x-text="displayContent"></div>
                    </details>
                </div>
            </template>
        </div>
    </section>
</div>
