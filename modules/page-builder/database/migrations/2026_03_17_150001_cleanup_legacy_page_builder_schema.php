<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('pb_pages')) {
			Schema::table('pb_pages', function (Blueprint $table): void {
				if (Schema::hasColumn('pb_pages', 'snapshot_html')) {
					$table->dropColumn('snapshot_html');
				}

				if (Schema::hasColumn('pb_pages', 'snapshot_generated_at')) {
					$table->dropColumn('snapshot_generated_at');
				}
			});
		}

		Schema::dropIfExists('pb_page_templates');
		Schema::dropIfExists('pb_section_templates');
	}

	public function down(): void
	{
		if (Schema::hasTable('pb_pages')) {
			Schema::table('pb_pages', function (Blueprint $table): void {
				if (! Schema::hasColumn('pb_pages', 'snapshot_html')) {
					$table->longText('snapshot_html')->nullable()->after('seo_meta_json');
				}

				if (! Schema::hasColumn('pb_pages', 'snapshot_generated_at')) {
					$table->timestamp('snapshot_generated_at')->nullable()->after('snapshot_html');
				}
			});
		}

		if (! Schema::hasTable('pb_section_templates')) {
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
		}

		if (! Schema::hasTable('pb_page_templates')) {
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
	}
};
