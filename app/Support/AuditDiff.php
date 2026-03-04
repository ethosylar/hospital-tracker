<?php

namespace App\Support;

class AuditDiff
{
    /**
     * Build a { field: {from, to} } diff using only keys present in $newData.
     * - Ignores keys you don't want to audit (e.g., updated_at)
     */
    public static function diff(array $oldRow, array $newData, array $ignoreKeys = ['updated_at', 'created_at']): array
    {
        $changes = [];

        foreach ($newData as $key => $newVal) {
            if (in_array($key, $ignoreKeys, true)) continue;

            $oldVal = $oldRow[$key] ?? null;

            // normalize dates/strings a bit (optional)
            if (is_string($oldVal)) $oldVal = trim($oldVal);
            if (is_string($newVal)) $newVal = trim($newVal);

            // compare strictly but tolerant for null/empty
            if ($oldVal !== $newVal) {
                $changes[$key] = [
                    'from' => $oldVal,
                    'to'   => $newVal,
                ];
            }
        }

        return $changes;
    }
}