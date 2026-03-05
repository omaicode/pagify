<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	public function up(): void
	{
		Schema::create('updater_executions', function (Blueprint $table): void {
			$table->bigIncrements('id');
			$table->unsignedBigInteger('requested_by_admin_id')->nullable();
			$table->string('target_type', 32);
			$table->string('target_value')->nullable();
			$table->string('status', 32)->default('queued');
			$table->json('meta')->nullable();
			$table->json('result')->nullable();
			$table->string('snapshot_path')->nullable();
			$table->timestamp('started_at')->nullable();
			$table->timestamp('finished_at')->nullable();
			$table->timestamps();

			$table->index(['target_type', 'target_value']);
			$table->index('status');
			$table->index('requested_by_admin_id');
		});

		Schema::create('updater_execution_items', function (Blueprint $table): void {
			$table->bigIncrements('id');
			$table->foreignId('execution_id')->constrained('updater_executions')->cascadeOnDelete();
			$table->string('module_slug');
			$table->string('package_name');
			$table->string('status', 32)->default('pending');
			$table->string('from_version')->nullable();
			$table->string('to_version')->nullable();
			$table->text('error_message')->nullable();
			$table->timestamps();

			$table->index(['execution_id', 'module_slug']);
			$table->index('status');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('updater_execution_items');
		Schema::dropIfExists('updater_executions');
	}
};
