<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(
        string $action,
        Model $subject,
        array $metadata = []
    ): AuditLog {
        $company = app()->bound('activeCompany')
            ? app('activeCompany')
            : null;
        $store = app()->bound('activeStore')
            ? app('activeStore')
            : null;
        $request = request();

        return AuditLog::create([
            'company_id' => $company?->id ?? $subject->company_id,
            'store_id' => $store?->id ?? $subject->store_id ?? null,
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
        ]);
    }
}
