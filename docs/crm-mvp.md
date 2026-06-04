# 抖音达人 CRM MVP 模块

## 第一阶段

先做可运营的核心后台，不急着接复杂外部接口。

- 达人档案：昵称、平台账号、粉丝数、类目、地区、联系方式、报价、佣金、标签、备注
- 合作状态：待开发、已触达、沟通中、已寄样、已排期、直播中、已复盘、长期合作、暂停合作、无效达人
- 跟进记录：沟通内容、沟通渠道、负责人、下次跟进时间、附件
- 排期提醒：直播时间、商品、坑位费、佣金比例、负责人、直播前提醒、复盘提醒
- 样品管理：样品、快递单号、寄样时间、签收状态、样品成本、对应达人
- GMV 统计：直播场次 GMV、订单数、客单价、ROI、佣金成本

## 第二阶段

- AI 达人价值评分：类目匹配、历史 GMV、报价合理性、沟通响应、合作稳定性
- AI 跟踪报告：自动汇总达人进展、风险点、下一步建议
- 商品维度统计：按商品看 GMV、达人表现、复盘结论
- 日历视图：排期、寄样、跟进提醒统一展示

## 推荐数据表

- `creators`：达人主档案
- `creator_contacts`：联系方式
- `creator_tags`：达人标签
- `follow_ups`：跟进记录
- `collaborations`：合作项目
- `live_sessions`：直播场次
- `products`：商品
- `samples`：样品寄送
- `gmv_records`：GMV 数据
- `reminders`：提醒
- `creator_scores`：达人评分
- `ai_reports`：AI 报告

## Filament 后台资源

- CreatorResource：达人档案
- FollowUpResource：跟进记录
- LiveSessionResource：直播排期
- SampleResource：样品管理
- ProductResource：商品管理
- GmvRecordResource：GMV 统计
- AiReportResource：AI 报告

## 首页看板

- 今日待跟进达人
- 未来 7 天直播排期
- 样品待签收
- 本月 GMV
- 本月合作达人数量
- 高价值达人榜
- 风险达人列表
