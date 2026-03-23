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
			if (Schema::hasColumn('pb_pages', 'status')) {
				$table->dropColumn('status');
			}
		});
	}

	public function down(): void
	{
		if (! Schema::hasTable('pb_pages')) {
			return;
		}

		Schema::table('pb_pages', function (Blueprint $table): void {
			if (! Schema::hasColumn('pb_pages', 'status')) {
				$table->string('status', 24)->default('draft')->after('slug');
			}
		});
	}
};
