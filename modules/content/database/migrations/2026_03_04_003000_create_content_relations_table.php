<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('source_entry_id')->constrained('content_entries')->cascadeOnDelete();
            $table->foreignId('target_entry_id')->constrained('content_entries')->cascadeOnDelete();
            $table->string('field_key', 120);
            $table->string('relation_type', 24);
            $table->unsignedInteger('position')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['source_entry_id', 'target_entry_id', 'field_key', 'relation_type'], 'cr_unique_link');
            $table->index(['source_entry_id', 'field_key'], 'cr_source_field_idx');
            $table->index(['target_entry_id', 'field_key'], 'cr_target_field_idx');
            $table->index(['site_id', 'relation_type'], 'cr_site_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_relations');
    }
};
