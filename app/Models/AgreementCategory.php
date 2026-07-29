<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementCategory extends Model
	{
		protected $table = 'lt_agreement_categories';
		
		protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_system_category',
        'is_active',
		];
		
		protected $casts = [
        'sort_order' => 'integer',
        'is_system_category' => 'boolean',
        'is_active' => 'boolean',
		];
		
		public function types()
		{
			return $this->hasMany(
            AgreementType::class,
            'agreement_category_id'
			);
		}
	}	