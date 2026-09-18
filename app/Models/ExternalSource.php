<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalSource extends Model
{
	protected $table = 'lt_external_sources';

	protected $fillable = [
		'code',
		'name',
		'base_url',
		'is_active',
		'site_id',
	];

	protected $casts = [
		'is_active' => 'boolean',
		'site_id' => 'integer',
	];

	public function site()
	{
		return $this->belongsTo(
			Site::class,
			'site_id'
		);
	}

	public function permits()
	{
		return $this->hasMany(
			ExternalPermit::class,
			'external_source_id'
		);
	}

	public function syncRuns()
	{
		return $this->hasMany(
			IntegrationSyncRun::class,
			'external_source_id'
		);
	}

	public function riskIssues()
	{
		return $this->hasMany(
			ExternalRiskIssue::class,
			'external_source_id'
		);
	}
}
