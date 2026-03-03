<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('content_type_id')->constrained('content_types')->cascadeOnDelete();
            $table->string('slug', 160);
            $table->string('status', 24)->default('draft');
            $table->json('data_json');
            $table->timestamps();

            $table->unique(['site_id', 'content_type_id', 'slug']);
            $table->index(['content_type_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};
