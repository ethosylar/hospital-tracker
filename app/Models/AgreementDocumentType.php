<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementDocumentType extends Model
	{
		protected $table = 'lt_agreement_document_types';
		
		protected $fillable = [
        'code',
        'name',
        'description',
        'ocr_eligible',
        'sort_order',
        'is_system_type',
        'is_active',
		];
		
		protected $casts = [
        'ocr_eligible' => 'boolean',
        'sort_order' => 'integer',
        'is_system_type' => 'boolean',
        'is_active' => 'boolean',
		];
		
		public function agreementFiles()
		{
			return $this->hasMany(
            AgreementFile::class,
            'document_type_id'
			);
		}
	}	