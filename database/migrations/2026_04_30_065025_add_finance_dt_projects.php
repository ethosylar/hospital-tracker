<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				// currency
				$table->char('currency_code', 3)->default('MYR')->after('sponsor');
				
				// COST side (planned vs actual vs committed)
				$table->decimal('planned_cost_total', 15, 2)->default(0)->after('currency_code');
				$table->decimal('actual_cost_total', 15, 2)->default(0)->after('planned_cost_total');
				$table->decimal('committed_cost_total', 15, 2)->default(0)->after('actual_cost_total');
				
				// FUNDING side (planned vs received)
				$table->decimal('planned_funding_total', 15, 2)->default(0)->after('committed_cost_total');
				$table->decimal('actual_funding_total', 15, 2)->default(0)->after('planned_funding_total');
				
				// optional meta
				$table->text('budget_notes')->nullable()->after('actual_funding_total');
				$table->timestamp('budget_updated_at')->nullable()->after('budget_notes');
				
				$table->index(['currency_code']);
			});
		}
		
		public function down(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				$table->dropIndex(['currency_code']);
				$table->dropColumn([
                'currency_code',
                'planned_cost_total',
                'actual_cost_total',
                'committed_cost_total',
                'planned_funding_total',
                'actual_funding_total',
                'budget_notes',
                'budget_updated_at',
				]);
			});
		}
	};	