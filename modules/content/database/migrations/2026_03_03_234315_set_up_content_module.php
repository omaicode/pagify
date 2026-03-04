<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	public function up(): void
	{
		if (! Schema::hasTable('content_types')) {
			Schema::create('content_types', function (Blueprint $table): void {
				$table->id();
				$table->unsignedBigInteger('site_id')->nullable();
				$table->string('name', 120);
				$table->string('slug', 120);
				$table->string('description', 1000)->nullable();
				$table->boolean('is_active')->default(true);
				$table->json('schema_json')->nullable();
				$table->timestamps();

				$table->unique(['site_id', 'slug']);
				$table->index(['site_id', 'is_active']);
			});
		}

		if (! Schema::hasTable('content_type_fields')) {
			Schema::create('content_type_fields', function (Blueprint $table): void {
				$table->id();
				$table->foreignId('content_type_id')->constrained('content_types')->cascadeOnDelete();
				$table->string('key', 120);
				$table->string('label', 120);
				$table->string('field_type', 40);
				$table->json('config_json')->nullable();
				$table->json('validation_json')->nullable();
				$table->json('conditional_json')->nullable();
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_required')->default(false);
				$table->boolean('is_localized')->default(false);
				$table->timestamps();

				$table->unique(['content_type_id', 'key']);
				$table->index(['content_type_id', 'field_type']);
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('content_type_fields');
		Schema::dropIfExists('content_types');
	}
};
