<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementStatus extends Model
	{
		protected $table = 'st_agreement_statuses';
		
		protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_terminal',
        'is_system_status',
        'is_active',
		];
		
		protected $casts = [
        'sort_order' => 'integer',
        'is_terminal' => 'boolean',
        'is_system_status' => 'boolean',
        'is_active' => 'boolean',
		];
	}
