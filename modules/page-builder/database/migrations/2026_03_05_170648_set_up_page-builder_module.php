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
			$table->timestamp('published_at')->nullable();
			$table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(['site_id', 'slug']);
			$table->index(['status', 'published_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pb_pages');
	}
};
