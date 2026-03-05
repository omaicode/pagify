<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_plugin_states', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version')->nullable();
            $table->string('package_name')->nullable();
            $table->string('source_type', 20)->default('local');
            $table->text('root_path')->nullable();
            $table->boolean('enabled')->default(false);
            $table->boolean('is_installed')->default(true);
            $table->boolean('is_compatible')->default(true);
            $table->json('compatibility_issues')->nullable();
            $table->json('manifest')->nullable();
            $table->timestamp('safe_mode_disabled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_plugin_states');
    }
};
