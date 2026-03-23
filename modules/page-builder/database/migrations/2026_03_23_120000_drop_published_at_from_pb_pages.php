<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('pb_pages')) {
			return;
		}

		Schema::table('pb_pages', function (Blueprint $table): void {
			if (Schema::hasColumn('pb_pages', 'published_at')) {
				$table->dropIndex(['status', 'published_at']);
				$table->dropColumn('published_at');
			}
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('pb_pages')) {
			return;
		}

		Schema::table('pb_pages', function (Blueprint $table): void {
			if (! Schema::hasColumn('pb_pages', 'published_at')) {
				$table->timestamp('published_at')->nullable()->after('seo_meta_json');
				$table->index(['status', 'published_at']);
			}
		});
	}
};
