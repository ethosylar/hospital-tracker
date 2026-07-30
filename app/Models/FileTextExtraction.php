<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class FileTextExtraction extends Model
	{
		public const STATUS_NOT_REQUESTED = 'NOT_REQUESTED';
		public const STATUS_PENDING = 'PENDING';
		public const STATUS_PROCESSING = 'PROCESSING';
		public const STATUS_COMPLETED = 'COMPLETED';
		public const STATUS_FAILED = 'FAILED';
		
		protected $table = 'dt_file_text_extractions';
		
		protected $fillable = [
        'file_id',
        'status',
        'engine',
        'language',
        'source_checksum',
        'extracted_text',
        'page_count',
        'error_message',
        'requested_by_user_id',
        'requested_at',
        'started_at',
        'completed_at',
		];
		
		protected $casts = [
        'file_id' => 'integer',
        'page_count' => 'integer',
        'requested_by_user_id' => 'integer',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
		];
		
		public function file()
		{
			return $this->belongsTo(StoredFile::class,'file_id');
		}
		
		public function requestedBy()
		{
			return $this->belongsTo(User::class,'requested_by_user_id');
		}
	}	