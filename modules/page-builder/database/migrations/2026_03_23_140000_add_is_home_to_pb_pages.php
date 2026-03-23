<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (! Schema::hasTable('pb_pages')) {
			return;
		}

		Schema::table('pb_pages', function (Blueprint $table): void {
			if (! Schema::hasColumn('pb_pages', 'is_home')) {
				$table->boolean('is_home')->default(false)->after('slug');
				$table->index(['site_id', 'is_home']);
			}
		});

		if (Schema::hasColumn('pb_pages', 'is_home')) {
			$homePageId = DB::table('pb_pages')
				->orderByRaw("CASE WHEN slug = '/' THEN 0 ELSE 1 END")
				->orderBy('id')
				->value('id');

			if ($homePageId !== null) {
				DB::table('pb_pages')->where('id', $homePageId)->update(['is_home' => true]);
			}
		}
	}

	public function down(): void
	{
		if (! Schema::hasTable('pb_pages')) {
			return;
		}

		Schema::table('pb_pages', function (Blueprint $table): void {
			if (Schema::hasColumn('pb_pages', 'is_home')) {
				$table->dropIndex(['site_id', 'is_home']);
				$table->dropColumn('is_home');
			}
		});
	}
};
