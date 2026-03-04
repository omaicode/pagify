<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_asset_transforms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('profile', 60);
            $table->string('variant', 60)->default('default');
            $table->string('status', 40)->default('pending');
            $table->string('disk', 40)->nullable();
            $table->string('path', 1024)->nullable();
            $table->string('mime_type', 160)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'profile', 'variant'], 'media_asset_transforms_asset_profile_variant_unique');
            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_asset_transforms');
    }
};
