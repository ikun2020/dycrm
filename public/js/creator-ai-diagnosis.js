window.creatorAiDiagnosisModal = function (config) {
    return {
        endpoint: config.endpoint,
        creatorsEndpoint: config.creatorsEndpoint || '',
        csrf: config.csrf,
        creatorId: config.creatorId,
        creatorSearch: '',
        creatorListOpen: false,
        creators: config.creators || [],
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
        get displayContent() {
            return this.cleanReport(this.content);
        },
        async start() {
            if (! this.creatorId || ! this.productId || this.running) {
                return;
            }

            this.running = true;
            this.status = '正在整理达人资料、商品资料和历史数据...';
            this.content = '正在整理达人资料、商品资料和历史数据...';
            this.score = null;
            this.grade = '';
            this.summary = '';
            this.riskPoints = [];
            this.nextSteps = [];
            this.generatedAt = '';
            this.finished = false;

            const progressMessages = [
                '正在调用AI服务...',
                '正在分析达人内容类目和商品匹配度...',
                '正在评估粉丝量、客单价和转化潜力...',
                '正在判断合作风险和履约风险...',
                '正在生成AI评分、评级和跟进建议...',
            ];
            let progressIndex = 0;
            let waitingMessageShown = false;
            const progressTimer = window.setInterval(() => {
                if (progressIndex < progressMessages.length) {
                    const message = progressMessages[progressIndex];
                    this.status = message;
                    this.content += '\n' + message;
                    progressIndex += 1;
                } else if (! waitingMessageShown) {
                    const message = 'AI仍在生成报告，请稍候...';
                    this.status = message;
                    this.content += '\n' + message;
                    waitingMessageShown = true;
                }

                this.$nextTick(() => {
                    this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                });
            }, 2500);

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        creator_id: this.creatorId,
                        product_id: this.productId,
                    }),
                });

                const rawResponse = await response.text();
                let data = {};

                try {
                    data = JSON.parse(rawResponse);
                } catch (error) {
                    throw new Error(rawResponse.slice(0, 300) || error.message);
                }

                if (! response.ok || ! data.ok) {
                    this.status = '评分失败';
                    this.content += '\n\n' + (data.message || ('请求失败：' + response.status));
                    this.running = false;
                    return;
                }

                this.generatedAt = data.generated_at || '';
                this.status = '评分完成：' + this.generatedAt;
                this.content = data.content || data.summary || 'AI 已完成诊断，但未返回详细报告内容。';
                this.score = data.score;
                this.grade = data.grade;
                this.summary = data.summary || this.extractSummary(this.content);
                this.riskPoints = this.toList(data.risk_points);
                this.nextSteps = this.toList(data.next_steps);
                this.finished = true;
            } catch (error) {
                this.status = '请求失败：' + error.message;
                this.content += '\n\n请求失败：' + error.message;
            } finally {
                window.clearInterval(progressTimer);
                this.running = false;
                this.$nextTick(() => {
                    this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                });
            }
        },
        async searchCreators() {
            if (! this.creatorsEndpoint || this.running) {
                return;
            }

            this.creatorId = '';
            this.creatorListOpen = true;

            const response = await fetch(this.creatorsEndpoint + '?q=' + encodeURIComponent(this.creatorSearch || ''), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (! response.ok) {
                return;
            }

            this.creators = await response.json();
        },
        selectCreator(creator) {
            this.creatorId = creator.id;
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
                .map((line) => line.replace(/^[-*#\d.、\s]+/u, '').trim())
                .filter(Boolean);

            return lines.slice(0, 3).join(' ');
        },
        toList(value) {
            if (Array.isArray(value)) {
                return value.map((item) => String(item).trim()).filter(Boolean);
            }

            return String(value || '')
                .split(/\n|；|;|。/u)
                .map((item) => item.replace(/^[-*#\d.、\s]+/u, '').trim())
                .filter(Boolean)
                .slice(0, 6);
        },
        handleEvent(eventText) {
            const lines = eventText.split('\n');
            let event = 'message';
            let dataLine = '';

            for (const line of lines) {
                if (line.startsWith('event:')) {
                    event = line.replace('event:', '').trim();
                }

                if (line.startsWith('data:')) {
                    dataLine = line;
                }
            }

            if (! dataLine) {
                return;
            }

            const data = JSON.parse(dataLine.replace('data:', '').trim());

            if (event === 'status') {
                this.status = data.message;
            }

            if (event === 'delta') {
                this.status = 'AI正在分析...';
                this.content += data.content;
                this.$nextTick(() => {
                    this.$refs.output.scrollTop = this.$refs.output.scrollHeight;
                });
            }

            if (event === 'done') {
                this.status = '评分完成：' + data.generated_at;
                this.score = data.score;
                this.grade = data.grade;
            }

            if (event === 'error') {
                this.status = '评分失败';
                this.content += '\n\n' + data.message;
            }
        },
    };
};
