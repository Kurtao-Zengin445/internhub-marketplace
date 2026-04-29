<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionService
{
    public function userApplicationLimitReached(User $user): bool
    {
        if ($user->hasActivePremium()) {
            return false;
        }

        return $user->applications()
            ->whereIn('status', ['pending', 'reviewed', 'accepted', 'rejected'])
            ->count() >= 2;
    }

    public function companyJobPostLimitReached(Company $company): bool
    {
        if ($company->hasActivePremium()) {
            return false;
        }

        return $company->programs()
            ->whereIn('status', ['draft', 'open', 'closed'])
            ->count() >= 2;
    }

    public function activatePremium(Subscription $subscription): void
    {
        $subscription->update([
            'status' => 'paid',
            'paid_at' => now(),
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $subscription->subscribable->update([
            'plan_type' => 'premium',
            'premium_until' => $subscription->ends_at,
        ]);
    }
}
