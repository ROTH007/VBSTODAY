<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    public function record(string $action, string $model, int $modelId, ?array $changes = null, ?int $userId = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
