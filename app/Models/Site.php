<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'lt_sites';

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'site_type',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postcode',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Site $site) {
            $site->code = strtoupper(trim((string) $site->code));
            $site->site_type = strtoupper(trim((string) $site->site_type));

            if ($site->name !== null) {
                $site->name = trim((string) $site->name);
            }

            if ($site->short_name !== null) {
                $site->short_name = trim((string) $site->short_name);
            }
        });
    }

    public function userAccesses()
    {
        return $this->hasMany(UserSite::class, 'site_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'dt_user_sites',
            'site_id',
            'user_id'
        )
            ->withPivot([
                'access_level',
                'is_primary',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function departments()
    {
        return $this->hasMany(
            \App\Models\Department::class,
            'site_id'
        );
    }

    public function projects()
    {
        return $this->hasMany(
            \App\Models\Project::class,
            'site_id'
        );
    }


    /*
     * Add these relationships only after the next migrations introduce
     * site_id to the related tables.
     *
     * //public function departments() { ... }
     * //public function projects() { ... }
     * public function agreements() { ... }
     */
}
