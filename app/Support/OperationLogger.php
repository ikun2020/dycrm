<?php

namespace App\Support;

use App\Models\OperationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class OperationLogger
{
    public static function record(string $action, ?Model $subject = null, array $properties = [], ?string $description = null): ?OperationLog
    {
        try {
            return OperationLog::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? self::subjectLabel($subject) : null,
                'description' => $description,
                'properties' => $properties === [] ? null : $properties,
                'ip_address' => request()?->ip(),
                'user_agent' => Str::limit((string) request()?->userAgent(), 500, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private static function subjectLabel(Model $subject): ?string
    {
        foreach (['nickname', 'name', 'sample_name', 'title', 'email', 'summary'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (filled($value)) {
                return Str::limit((string) $value, 180, '');
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
