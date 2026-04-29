<?php

namespace App\Http\Helpers;

use App\Models\Internship;
use Illuminate\Support\Facades\Auth;

trait InternInternshipHelper
{
    /**
     * Get active internship for current user.
     * Returns null if no active internship exists.
     */
    protected function getActiveInternship(): ?Internship
    {
        return Internship::whereHas('application', function ($q) {
            $q->where('user_id', Auth::id());
        })->where('status', 'active')->first();
    }

    /**
     * Check if user has active internship.
     */
    protected function hasActiveInternship(): bool
    {
        return $this->getActiveInternship() !== null;
    }

    /**
     * Get active internship or redirect back with error.
     */
    protected function requireActiveInternship(): ?Internship
    {
        $internship = $this->getActiveInternship();

        if (!$internship) {
            return null;
        }

        return $internship;
    }

    /**
     * Require active internship for write operations (store, update, delete).
     * Redirects with error if no active internship.
     */
    protected function requireActiveInternshipForWrite(): ?Internship
    {
        $internship = $this->getActiveInternship();

        if (!$internship) {
            return null;
        }

        return $internship;
    }

    /**
     * Check if the given internship belongs to the current user.
     */
    protected function ownsInternship(Internship $internship): bool
    {
        return $internship->application->user_id === Auth::id();
    }
}
