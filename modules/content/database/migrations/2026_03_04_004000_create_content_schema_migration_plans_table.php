<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_schema_migration_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('content_type_id');
            $table->unsignedBigInteger('requested_by_admin_id')->nullable();
            $table->string('status', 24)->default('queued');
            $table->json('schema_before_json');
            $table->json('schema_after_json');
            $table->json('plan_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('planned_at')->nullable();
            $table->timestamps();

            $table->foreign('site_id', 'csmp_site_fk')->references('id')->on('sites')->nullOnDelete();
            $table->foreign('content_type_id', 'csmp_type_fk')->references('id')->on('content_types')->cascadeOnDelete();
            $table->foreign('requested_by_admin_id', 'csmp_req_admin_fk')->references('id')->on('admins')->nullOnDelete();

            $table->index(['content_type_id', 'status'], 'csmp_type_status_idx');
            $table->index(['site_id', 'created_at'], 'csmp_site_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_schema_migration_plans');
    }
};
