<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_types') || ! Schema::hasTable('sites')) {
            return;
        }

        try {
            Schema::table('content_types', function (Blueprint $table): void {
                $table->foreign('site_id', 'content_types_site_id_foreign')
                    ->references('id')
                    ->on('sites')
                    ->nullOnDelete();
            });
        } catch (Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('content_types')) {
            return;
        }

        try {
            Schema::table('content_types', function (Blueprint $table): void {
                $table->dropForeign('content_types_site_id_foreign');
            });
        } catch (Throwable) {
        }
    }
};
