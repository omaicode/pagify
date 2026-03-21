<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('pb_page_folders', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('folder_id', 160);
			$table->string('name', 160);
			$table->string('slug', 160);
			$table->string('parent_folder_id', 160)->nullable();
			$table->unsignedInteger('sort_order')->default(0);
			$table->timestamps();

			$table->unique(['site_id', 'folder_id']);
			$table->index(['site_id', 'parent_folder_id', 'sort_order']);
		});

		Schema::table('pb_pages', function (Blueprint $table): void {
			$table->string('folder_id', 160)->nullable()->after('slug');
			$table->unsignedInteger('folder_order')->default(0)->after('folder_id');
			$table->index(['site_id', 'folder_id', 'folder_order']);
		});
	}

	public function down(): void
	{
		Schema::table('pb_pages', function (Blueprint $table): void {
			$table->dropIndex(['site_id', 'folder_id', 'folder_order']);
			$table->dropColumn(['folder_id', 'folder_order']);
		});

		Schema::dropIfExists('pb_page_folders');
	}
};
