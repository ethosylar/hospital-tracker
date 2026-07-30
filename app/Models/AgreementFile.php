<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementFile extends Model
	{
		protected $table = 'dt_agreement_files';
		
		protected $fillable = [
        'agreement_id',
        'file_id',
        'document_type_id',
        'document_version',
        'document_date',
        'is_current',
        'is_executed_copy',
        'supersedes_agreement_file_id',
        'notes',
        'linked_by_user_id',
		];
		
		protected $casts = [
        'agreement_id' => 'integer',
        'file_id' => 'integer',
        'document_type_id' => 'integer',
        'document_date' => 'date',
        'is_current' => 'boolean',
        'is_executed_copy' => 'boolean',
        'supersedes_agreement_file_id' => 'integer',
        'linked_by_user_id' => 'integer',
		];
		
		public function agreement()
		{
			return $this->belongsTo(Agreement::class,'agreement_id');
		}
		
		public function file()
		{
			return $this->belongsTo(StoredFile::class,'file_id');
		}
		
		public function documentType()
		{
			return $this->belongsTo(AgreementDocumentType::class,'document_type_id');
		}
		
		public function supersedes()
		{
			return $this->belongsTo(AgreementFile::class,'supersedes_agreement_file_id');
		}
		
		public function supersededBy()
		{
			return $this->hasMany(AgreementFile::class,'supersedes_agreement_file_id');
		}
		
		public function linkedBy()
		{
			return $this->belongsTo(User::class,'linked_by_user_id');
		}
	}	