<?php

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

function audit(
    string $action,
    Model $model,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?string $reason = null
) {
    AuditLog::create([
        'user_id' => Auth::id(),
        'action' => $action,
        'auditable_type' => get_class($model),
        'auditable_id' => $model->id,
        'old_values' => $oldValues,
        'new_values' => $newValues,
        'reason' => $reason,
    ]);
}