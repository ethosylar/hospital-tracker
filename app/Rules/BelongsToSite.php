<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class BelongsToSite implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly int $siteId,
        private readonly bool $requireActive = false,
        private readonly string $keyColumn = 'id',
        private readonly string $siteColumn = 'site_id'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /*
         * The main site_id validation will produce the correct
         * validation message if site_id itself is invalid.
         */
        if ($this->siteId <= 0) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $query =
            DB::table($this->table)
            ->where($this->keyColumn, $value)
            ->where($this->siteColumn, $this->siteId);

        if ($this->requireActive) {
            $query->where('is_active', true);
        }

        if (!$query->exists()) {
            $name = str_replace('_', ' ', $attribute);

            $fail("The selected {$name} must belong to the selected Site.");
        }
    }
}
