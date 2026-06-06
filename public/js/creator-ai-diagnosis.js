window.creatorAiDiagnosisModal = function (config) {
    return {
        endpoint: config.endpoint,
        creatorsEndpoint: config.creatorsEndpoint || '',
        csrf: config.csrf,
        creatorId: config.creatorId,
        creatorName: config.creatorName || '',
        creatorSearch: config.creatorName || '',
        creatorListOpen: false,
        creators: config.creators || [],
        creatorPage: 1,
        creatorHasMore: (config.creators || []).length >= 5,
        creatorLoading: false,
        creatorLastQuery: config.creatorName || '',
        productId: '',
        running: false,
        status: '等待开始',
        content: '',
        score: null,
        grade: '',
        summary: '',
        riskPoints: [],
        nextSteps: [],
        generatedAt: '',
        finished: false,
        failed: false,
        progress: 0,
        activeStage: 0,
        stageTimer: null,
        progressTimer: null,
        pollAttempts: 0,
        maxPollAttempts: 180,
        stages: [
            { title: '整理资料', description: '汇总达人档案、商品资料和历史数据' },
            { title: '画像识别', description: '分析内容类目、粉丝基础和近期跟进' },
            { title: '商品匹配', description: '判断价格带、类目和卖点契合度' },
            { title: '转化评估', description: '评估 GMV、客单价和潜在转化空间' },
            { title: '风险判断', description: '识别合作成本、履约和复购风险' },
            { title: '生成建议', description: '整理评分、评级和下一步跟进建议' },
        ],

        get displayContent() {
            return this.cleanReport(this.content);
        },

        get activeStageTitle() {
            return this.stages[this.activeStage]?.title || '等待开始';
        },

        get activeStageDescription() {
            return this.stages[this.activeStage]?.description || '';
        },

        async start() {
            if (! this.creatorId || ! this.productId || this.running) {
                return;
            }

            this.resetRunState();

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        creator_id: this.creatorId,
                        product_id: this.productId,
                    }),
                });

                const payload = await this.parseJsonResponse(response);

                if (! response.ok || payload.ok === false) {
                    throw new Error(payload.message || `请求失败：${response.status}`);
                }

                if (payload.queued && payload.status_url) {
                    this.status = 'AI 评分任务已提交，正在后台生成';
                    await this.pollReport(payload.status_url);

                    return;
                }

                this.finishWithPayload(payload);
            } catch (error) {
                this.failWithMessage(error.message);
            }
        },

        resetRunState() {
            this.running = true;
            this.failed = false;
            this.status = 'AI 正在分析';
            this.content = '';
            this.score = null;
            this.grade = '';
            this.summary = '';
            this.riskPoints = [];
            this.nextSteps = [];
            this.generatedAt = '';
            this.finished = false;
            this.progress = 8;
            this.activeStage = 0;
            this.pollAttempts = 0;
            this.startThinkingAnimation();
        },

        async pollReport(statusUrl) {
            while (this.running && this.pollAttempts < this.maxPollAttempts) {
                this.pollAttempts += 1;

                await this.sleep(this.pollAttempts <= 2 ? 900 : 1500);

                const response = await fetch(statusUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });
                const payload = await this.parseJsonResponse(response);

                if (! response.ok || payload.ok === false) {
                    throw new Error(payload.message || `查询失败：${response.status}`);
                }

                if (payload.status === 'failed') {
                    throw new Error(payload.message || 'AI 评分失败，请稍后重试。');
                }

                if (payload.status === 'completed') {
                    this.finishWithPayload(payload);

                    return;
                }

                this.status = payload.status === 'processing'
                    ? 'AI 正在后台生成报告'
                    : 'AI 评分任务正在排队';
            }

            throw new Error('AI 评分仍在后台生成，请稍后到 AI 报告页面查看结果。');
        },

        finishWithPayload(payload) {
            this.stopThinkingAnimation();
            this.progress = 100;
            this.activeStage = this.stages.length - 1;
            this.generatedAt = payload.generated_at || '';
            this.status = this.generatedAt ? `评分完成：${this.generatedAt}` : '评分完成';
            this.content = payload.content || payload.summary || '';
            this.score = payload.score;
            this.grade = payload.grade || '';
            this.summary = payload.summary || this.extractSummary(this.content);
            this.riskPoints = this.toList(payload.risk_points);
            this.nextSteps = this.toList(payload.next_steps);
            this.finished = true;
            this.failed = false;
            this.running = false;
            this.scrollOutput();
        },

        failWithMessage(message) {
            this.stopThinkingAnimation();
            this.failed = true;
            this.running = false;
            this.status = '评分失败';
            this.content = this.humanizeError(message);
            this.scrollOutput();
        },

        startThinkingAnimation() {
            this.stopThinkingAnimation();

            this.stageTimer = window.setInterval(() => {
                if (! this.running) {
                    return;
                }

                this.activeStage = Math.min(this.activeStage + 1, this.stages.length - 1);
            }, 1800);

            this.progressTimer = window.setInterval(() => {
                if (! this.running) {
                    return;
                }

                const ceiling = this.activeStage >= this.stages.length - 1 ? 92 : 82;
                const nextProgress = this.progress + Math.max(1, Math.round((ceiling - this.progress) / 8));

                this.progress = Math.min(nextProgress, ceiling);
            }, 650);
        },

        stopThinkingAnimation() {
            if (this.stageTimer) {
                window.clearInterval(this.stageTimer);
                this.stageTimer = null;
            }

            if (this.progressTimer) {
                window.clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
        },

        sleep(milliseconds) {
            return new Promise((resolve) => window.setTimeout(resolve, milliseconds));
        },

        async parseJsonResponse(response) {
            const text = await response.text();

            if (! text) {
                return {};
            }

            try {
                return JSON.parse(text);
            } catch (error) {
                throw new Error(this.humanizeError(text));
            }
        },

        async openCreatorList() {
            this.creatorListOpen = true;

            if (this.creators.length === 0 && this.creatorsEndpoint) {
                await this.searchCreators();
            }
        },

        async searchCreators() {
            if (! this.creatorsEndpoint || this.running) {
                return;
            }

            this.creatorId = '';
            this.creatorName = '';
            this.creatorListOpen = true;
            this.creatorPage = 1;
            this.creatorHasMore = true;
            this.creatorLastQuery = this.creatorSearch || '';

            await this.fetchCreatorsPage(1, false);
        },

        async loadMoreCreatorsOnScroll(event) {
            const target = event.target;

            if (! this.creatorHasMore || this.creatorLoading || this.running) {
                return;
            }

            if (target.scrollTop + target.clientHeight < target.scrollHeight - 24) {
                return;
            }

            await this.loadMoreCreators();
        },

        async loadMoreCreatorsOnWheel(event) {
            if (event.deltaY <= 0 || ! this.creatorHasMore || this.creatorLoading || this.running) {
                return;
            }

            const target = event.currentTarget;

            if (target.scrollHeight > target.clientHeight && target.scrollTop + target.clientHeight < target.scrollHeight - 24) {
                return;
            }

            await this.loadMoreCreators();
        },

        async loadMoreCreators() {
            await this.fetchCreatorsPage(this.creatorPage, true);
        },

        async fetchCreatorsPage(page, append) {
            if (! this.creatorsEndpoint || this.creatorLoading) {
                return;
            }

            this.creatorLoading = true;

            try {
                const url = new URL(this.creatorsEndpoint, window.location.origin);
                url.searchParams.set('q', this.creatorLastQuery || '');
                url.searchParams.set('page', String(page));

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (! response.ok) {
                    return;
                }

                const payload = await response.json();
                const items = Array.isArray(payload) ? payload : (payload.data || []);

                this.creators = append
                    ? this.mergeCreators(this.creators, items)
                    : items;
                this.creatorHasMore = Array.isArray(payload) ? items.length >= 5 : Boolean(payload.has_more);
                this.creatorPage = Array.isArray(payload) ? page + 1 : (payload.next_page || page + 1);
            } finally {
                this.creatorLoading = false;
            }
        },

        mergeCreators(existing, incoming) {
            const seen = new Set(existing.map((creator) => String(creator.id)));
            const merged = [...existing];

            incoming.forEach((creator) => {
                if (seen.has(String(creator.id))) {
                    return;
                }

                seen.add(String(creator.id));
                merged.push(creator);
            });

            return merged;
        },

        selectCreator(creator) {
            this.creatorId = creator.id;
            this.creatorName = creator.label;
            this.creatorSearch = creator.label;
            this.creatorListOpen = false;
        },

        cleanReport(value) {
            return String(value || '')
                .replace(/RESULT_JSON\s*:\s*\{[\s\S]*$/u, '')
                .replace(/```(?:json)?/gu, '')
                .replace(/\n{3,}/gu, '\n\n')
                .trim();
        },

        extractSummary(value) {
            const text = this.cleanReport(value);

            if (! text) {
                return '';
            }

            const lines = text
                .split('\n')
                .map((line) => line.replace(/^[-*#\d.\s、，.]+/u, '').trim())
                .filter(Boolean);

            return lines.slice(0, 3).join(' ');
        },

        toList(value) {
            if (Array.isArray(value)) {
                return value.map((item) => String(item).trim()).filter(Boolean);
            }

            return String(value || '')
                .split(/\n|；|;|、/u)
                .map((item) => item.replace(/^[-*#\d.\s、，.]+/u, '').trim())
                .filter(Boolean)
                .slice(0, 6);
        },

        humanizeError(value) {
            const text = String(value || '').trim();

            if (text.includes('504 Gateway Time-out')) {
                return 'AI 生成时间过长，服务器连接超时。请稍后重试，或在 AI 设置中提高超时时间。';
            }

            if (text.includes('cURL error 28') || text.includes('SSL connection timeout')) {
                return 'AI 服务商连接超时。当前网络到服务商不稳定，或服务商接口响应过慢，请稍后再试。';
            }

            if (text.includes('system_memory_overloaded') || text.includes('memory overloaded')) {
                return 'AI 服务商当前资源过载，请稍后再试，或在 AI 设置中切换更稳定的模型/服务商。';
            }

            if (text.includes('503')) {
                return 'AI 服务商当前不可用，请稍后再试。';
            }

            if (text.includes('429') || text.includes('Too Many Requests')) {
                return '请求过于频繁，请稍后再试。';
            }

            return text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        },

        scrollOutput() {
            this.$nextTick(() => {
                if (this.$refs.output) {
                    this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                }
            });
        },
    };
};
