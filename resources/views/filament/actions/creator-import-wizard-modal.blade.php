<div>
    <style>
        .fiwa-step-mapping .fiwa-merge-toggle-row,
        .fiwa-step-review .fiwa-upsert-settings,
        .fiwa-upsert-settings {
            display: none !important;
        }
    </style>

    @livewire(\App\Livewire\CreatorImportWizard::class, [
        'modelClass' => $modelClass ?? '',
        'chunkSize' => $chunkSize ?? 1000,
        'enableUpsert' => $enableUpsert ?? false,
        'upsertKeys' => $upsertKeys ?? ['id'],
        'queueConnection' => $queueConnection ?? null,
        'queueName' => $queueName ?? null,
    ])
</div>
