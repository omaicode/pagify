<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
	public function up(): void
	{
		Schema::create('media_folders', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
			$table->string('name', 180);
			$table->string('slug', 180);
			$table->unsignedInteger('sort_order')->default(0);
			$table->timestamps();

			$table->unique(['site_id', 'parent_id', 'slug'], 'media_folders_site_parent_slug_unique');
			$table->index(['site_id', 'parent_id']);
		});

		Schema::create('media_assets', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->uuid('uuid')->unique();
			$table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
			$table->string('disk', 40);
			$table->string('path', 1024);
			$table->string('path_hash', 64);
			$table->string('filename', 255);
			$table->string('original_name', 255);
			$table->string('mime_type', 160)->nullable();
			$table->string('extension', 40)->nullable();
			$table->unsignedBigInteger('size_bytes')->default(0);
			$table->string('checksum_sha256', 64)->nullable();
			$table->string('kind', 40)->default('other');
			$table->unsignedInteger('width')->nullable();
			$table->unsignedInteger('height')->nullable();
			$table->decimal('duration_seconds', 10, 3)->nullable();
			$table->json('meta')->nullable();
			$table->string('alt_text', 255)->nullable();
			$table->text('caption')->nullable();
			$table->decimal('focal_point_x', 6, 4)->nullable();
			$table->decimal('focal_point_y', 6, 4)->nullable();
			$table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamp('uploaded_at')->nullable();
			$table->timestamps();
			$table->softDeletes();

			$table->unique(['site_id', 'disk', 'path_hash'], 'media_assets_site_disk_path_hash_unique');
			$table->index(['site_id', 'kind']);
			$table->index(['site_id', 'mime_type']);
			$table->index(['site_id', 'folder_id']);
			$table->index(['site_id', 'created_at']);
		});

		Schema::create('media_tags', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->string('name', 100);
			$table->string('slug', 120);
			$table->timestamps();

			$table->unique(['site_id', 'slug']);
			$table->index(['site_id', 'name']);
		});

		Schema::create('media_asset_tag', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('asset_id')->constrained('media_assets')->cascadeOnDelete();
			$table->foreignId('tag_id')->constrained('media_tags')->cascadeOnDelete();
			$table->timestamps();

			$table->unique(['asset_id', 'tag_id']);
		});

		Schema::create('media_usages', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->foreignId('asset_id')->constrained('media_assets')->cascadeOnDelete();
			$table->string('context_type', 120);
			$table->string('context_id', 120);
			$table->string('field_key', 120)->nullable();
			$table->string('reference_path', 255)->nullable();
			$table->json('meta')->nullable();
			$table->timestamps();

			$table->index(['site_id', 'asset_id']);
			$table->index(['context_type', 'context_id']);
		});

		Schema::create('media_upload_sessions', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
			$table->uuid('uuid')->unique();
			$table->string('disk', 40);
			$table->string('temp_prefix', 255);
			$table->string('original_name', 255);
			$table->string('mime_type', 160)->nullable();
			$table->string('extension', 40)->nullable();
			$table->unsignedBigInteger('total_size_bytes')->default(0);
			$table->unsignedInteger('total_chunks')->default(1);
			$table->json('uploaded_chunks')->nullable();
			$table->string('status', 40)->default('pending');
			$table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
			$table->foreignId('uploaded_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
			$table->timestamp('expires_at')->nullable();
			$table->timestamps();

			$table->index(['site_id', 'status']);
			$table->index(['site_id', 'expires_at']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('media_upload_sessions');
		Schema::dropIfExists('media_usages');
		Schema::dropIfExists('media_asset_tag');
		Schema::dropIfExists('media_tags');
		Schema::dropIfExists('media_assets');
		Schema::dropIfExists('media_folders');
	}
};
