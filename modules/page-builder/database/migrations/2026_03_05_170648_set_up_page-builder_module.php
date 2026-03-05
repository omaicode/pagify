<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pb_pages', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('title', 160);
			$table->string('slug', 160);
			$table->string('status', 24)->default('draft');
			$table->json('layout_json')->nullable();
			$table->json('seo_meta_json')->nullable();
			$table->longText('snapshot_html')->nullable();
			$table->timestamp('snapshot_generated_at')->nullable();
			$table->timestamp('published_at')->nullable();
			$table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(['site_id', 'slug']);
			$table->index(['status', 'published_at']);
		});

		Schema::create('pb_page_revisions', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('page_id')->constrained('pb_pages')->cascadeOnDelete();
			$table->unsignedInteger('revision_no');
			$table->string('action', 60);
			$table->json('snapshot_json');
			$table->json('diff_json')->nullable();
			$table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->json('metadata_json')->nullable();
			$table->timestamps();

			$table->unique(['page_id', 'revision_no'], 'pb_page_revision_unique');
			$table->index(['page_id', 'created_at'], 'pb_page_created_at_idx');
		});

		Schema::create('pb_section_templates', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('name', 160);
			$table->string('slug', 160);
			$table->json('schema_json');
			$table->boolean('is_active')->default(true);
			$table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(['site_id', 'slug']);
		});

		Schema::create('pb_page_templates', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('name', 160);
			$table->string('slug', 160);
			$table->string('category', 60)->nullable();
			$table->string('description', 255)->nullable();
			$table->json('schema_json');
			$table->boolean('is_active')->default(true);
			$table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(['site_id', 'slug']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pb_page_templates');
		Schema::dropIfExists('pb_section_templates');
		Schema::dropIfExists('pb_page_revisions');
		Schema::dropIfExists('pb_pages');
	}
};
