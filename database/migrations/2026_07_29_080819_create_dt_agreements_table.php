<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_agreements', function (Blueprint $table) {
				$table->id();
				
				$table->string('agreement_no', 80)->unique();
				$table->string('title', 255);
				
				$table->foreignId('department_id')
                ->constrained('lt_departments')
                ->restrictOnDelete();
				
				$table->foreignId('owner_user_id')
                ->constrained('users')
                ->restrictOnDelete();
				
				$table->foreignId('counterparty_id')
                ->constrained('dt_counterparties')
                ->restrictOnDelete();
				
				$table->foreignId('agreement_category_id')
                ->constrained('lt_agreement_categories')
                ->restrictOnDelete();
				
				$table->foreignId('agreement_type_id')
                ->nullable()
                ->constrained('lt_agreement_types')
                ->nullOnDelete();
				
				$table->foreignId('agreement_status_id')
                ->constrained('st_agreement_statuses')
                ->restrictOnDelete();
				
				$table->text('description')->nullable();
				$table->text('purpose')->nullable();
				$table->text('scope')->nullable();
				
				$table->date('effective_date')->nullable();
				$table->date('expiry_date')->nullable();
				$table->date('signed_date')->nullable();
				
				$table->unsignedInteger('notice_period_days')->nullable();
				$table->boolean('auto_renewal')->default(false);
				
				$table->decimal('contract_value', 18, 2)->nullable();
				$table->char('currency_code', 3)->default('MYR');
				
				/*
					* Agreement lineage:
					* ORIGINAL  = first agreement in the lineage
					* AMENDMENT = amended agreement version
					* RENEWAL   = renewed agreement period
				*/
				$table->string('lifecycle_type', 20)->default('ORIGINAL');
				
				$table->foreignId('parent_agreement_id')
                ->nullable()
                ->constrained('dt_agreements')
                ->nullOnDelete();
				
				$table->foreignId('root_agreement_id')
                ->nullable()
                ->constrained('dt_agreements')
                ->nullOnDelete();
				
				$table->unsignedInteger('revision_no')->default(0);
				$table->unsignedInteger('renewal_sequence')->default(0);
				$table->boolean('is_current_version')->default(true);
				
				$table->timestamp('submitted_at')->nullable();
				$table->foreignId('submitted_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamp('approved_at')->nullable();
				$table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->date('terminated_on')->nullable();
				$table->text('termination_reason')->nullable();
				$table->foreignId('terminated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamp('archived_at')->nullable();
				$table->foreignId('archived_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
				
				$table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamps();
				$table->softDeletes();
				
				$table->index(
                ['department_id', 'agreement_status_id'],
                'idx_agreement_department_status'
				);
				
				$table->index(
                ['owner_user_id', 'agreement_status_id'],
                'idx_agreement_owner_status'
				);
				
				$table->index(
                ['counterparty_id', 'is_current_version'],
                'idx_agreement_counterparty_current'
				);
				
				$table->index(
                ['root_agreement_id', 'renewal_sequence', 'revision_no'],
                'idx_agreement_lineage'
				);
				
				$table->index(
                ['expiry_date', 'agreement_status_id'],
                'idx_agreement_expiry_status'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_agreements');
		}
	};
