<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;

class EventAuthorizer
{
    public function allows(User $user, Event $event, array $roles, ?int $barId = null): bool
    {
        $companyUser = $event->company->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->first();

        if (! $companyUser) {
            return false;
        }

        if (in_array($companyUser->pivot->role, ['owner', 'admin', 'manager'], true)) {
            return true;
        }

        return $event->assignments()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereIn('role', $roles)
            ->when($barId, fn ($query) => $query->where(function ($scope) use ($barId) {
                $scope->whereNull('event_bar_id')->orWhere('event_bar_id', $barId);
            }))
            ->exists();
    }

    public function authorize(User $user, Event $event, array $roles, ?int $barId = null): void
    {
        abort_unless($this->allows($user, $event, $roles, $barId), 403, 'Sem permissão para esta operação do evento.');
    }
}
