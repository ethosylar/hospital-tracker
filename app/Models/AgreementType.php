<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementType extends Model
	{
		protected $table = 'lt_agreement_types';
		
		protected $fillable = [
        'agreement_category_id',
        'code',
        'name',
        'description',
        'sort_order',
        'is_system_type',
        'is_active',
		];
		
		protected $casts = [
        'agreement_category_id' => 'integer',
        'sort_order' => 'integer',
        'is_system_type' => 'boolean',
        'is_active' => 'boolean',
		];
		
		public function category()
		{
			return $this->belongsTo(
            AgreementCategory::class,
            'agreement_category_id'
			);
		}
	}	