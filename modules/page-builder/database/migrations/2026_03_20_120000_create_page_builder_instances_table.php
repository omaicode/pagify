<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pb_page_builder_instances', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('project_id', 160);
			$table->foreignId('page_id')->constrained('pb_pages')->cascadeOnDelete();
			$table->string('build_id', 120)->nullable();
			$table->unsignedBigInteger('build_version')->default(1);
			$table->json('instances_json')->nullable();
			$table->timestamps();

			$table->unique(['project_id', 'page_id']);
			$table->index(['site_id', 'build_version']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pb_page_builder_instances');
	}
};
