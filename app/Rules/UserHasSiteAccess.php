<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserHasSiteAccess implements ValidationRule
{
    public function __construct(
        private readonly int $siteId
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->siteId <= 0) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $user = User::query()->find($value);

        if (!$user) {
            $fail('The selected user does not exist.');
            return;
        }

        /*
         * system.all is valid everywhere.
         */
        if ($user->hasPermission('system.all')) {
            return;
        }

        $hasSite =
            $user
            ->siteAccesses()
            ->where('site_id', $this->siteId)
            ->where('is_active', true)
            ->exists();

        if (!$hasSite) {
            $fail('The selected user does not have access to the selected Site.');
        }
    }
}
