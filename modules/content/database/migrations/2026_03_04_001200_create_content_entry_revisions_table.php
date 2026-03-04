<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entry_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_entry_id')->constrained('content_entries')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->string('action', 60);
            $table->json('snapshot_json');
            $table->json('diff_json')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['content_entry_id', 'revision_no'], 'cer_entry_revision_unique');
            $table->index(['content_entry_id', 'created_at'], 'cer_entry_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entry_revisions');
    }
};
