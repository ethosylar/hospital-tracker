<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Support\Str;
	
	class Counterparty extends Model
	{
		protected $table = 'dt_counterparties';
		
		protected $fillable = [
        'code',
        'counterparty_type',
        'legal_name',
        'normalized_name',
        'trading_name',
        'registration_no',
        'tax_no',
        'vendor_no',
        'contact_person',
        'contact_position',
        'email',
        'phone',
        'alternate_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postcode',
        'country',
        'notes',
        'is_active',
		];
		
		protected $casts = [
        'is_active' => 'boolean',
		];
		
		protected static function booted(): void
		{
			static::creating(function (Counterparty $counterparty) {
				if (blank($counterparty->code)) {
					$counterparty->code = static::generateCode();
				}
				
				$counterparty->legal_name = trim($counterparty->legal_name);
				$counterparty->normalized_name = static::normalizeName(
                $counterparty->legal_name
				);
				$counterparty->registration_no = static::normalizeRegistrationNo(
                $counterparty->registration_no
				);
			});
			
			static::updating(function (Counterparty $counterparty) {
				if ($counterparty->isDirty('legal_name')) {
					$counterparty->legal_name = trim($counterparty->legal_name);
					$counterparty->normalized_name = static::normalizeName(
                    $counterparty->legal_name
					);
				}
				
				if ($counterparty->isDirty('registration_no')) {
					$counterparty->registration_no = static::normalizeRegistrationNo(
                    $counterparty->registration_no
					);
				}
			});
		}
		
		public static function normalizeName(?string $value): string
		{
			$value = mb_strtoupper(trim((string) $value));
			return preg_replace('/\s+/', ' ', $value) ?? $value;
		}
		
		public static function normalizeRegistrationNo(?string $value): ?string
		{
			if (blank($value)) {
				return null;
			}
			
			$value = mb_strtoupper(trim((string) $value));
			return preg_replace('/[^A-Z0-9]/', '', $value) ?: null;
		}
		
		private static function generateCode(): string
		{
			do {
				$code = 'CP-'
                . now()->format('ymd')
                . '-'
                . Str::upper(Str::random(6));
			} while (static::query()->where('code', $code)->exists());
			
			return $code;
		}
		
		// public function agreements()
		// {
		//     return $this->hasMany(Agreement::class, 'counterparty_id');
		// }
	}	