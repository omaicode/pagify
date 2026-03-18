<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pb_page_builder_states', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->foreignId('page_id')->constrained('pb_pages')->cascadeOnDelete();
			$table->string('build_id', 120)->nullable();
			$table->unsignedBigInteger('version')->default(1);
			$table->json('data_json')->nullable();
			$table->timestamps();

			$table->unique('page_id');
			$table->index(['site_id', 'version']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pb_page_builder_states');
	}
};
