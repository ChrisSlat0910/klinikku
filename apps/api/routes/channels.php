<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Private queue channel per doctor per clinic
Broadcast::channel('queue.{clinicId}.{doctorId}', function (User $user, int $clinicId, int $doctorId): bool {
    return (int) $user->clinic_id === $clinicId;
});

// Private clinic channel (owner/admin notifications)
Broadcast::channel('clinic.{clinicId}', function (User $user, int $clinicId): bool {
    return (int) $user->clinic_id === $clinicId;
});

// Private pharmacy channel
Broadcast::channel('pharmacy.{clinicId}', function (User $user, int $clinicId): bool {
    return (int) $user->clinic_id === $clinicId
        && $user->hasAnyRole(['apoteker', 'owner', 'admin']);
});

// Presence display channel (public display screen)
Broadcast::channel('display.{clinicId}', function (?User $user, int $clinicId): bool {
    // Allow unauthenticated access for display screens
    if ($user === null) {
        return true;
    }

    return (int) $user->clinic_id === $clinicId;
});
