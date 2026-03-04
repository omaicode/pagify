<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_schema_migration_plans', function (Blueprint $table): void {
            $table->string('execution_hash', 64)->nullable()->after('error_message');
            $table->unsignedInteger('execution_attempts')->default(0)->after('execution_hash');
            $table->timestamp('execution_started_at')->nullable()->after('execution_attempts');
            $table->timestamp('executed_at')->nullable()->after('execution_started_at');

            $table->index(['status', 'created_at'], 'csmp_status_created_idx');
            $table->index(['execution_hash'], 'csmp_execution_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('content_schema_migration_plans', function (Blueprint $table): void {
            $table->dropIndex('csmp_status_created_idx');
            $table->dropIndex('csmp_execution_hash_idx');

            $table->dropColumn([
                'execution_hash',
                'execution_attempts',
                'execution_started_at',
                'executed_at',
            ]);
        });
    }
};
