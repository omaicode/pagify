<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_entries', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->after('status');
            $table->timestamp('unpublished_at')->nullable()->after('published_at');
            $table->timestamp('scheduled_publish_at')->nullable()->after('unpublished_at');
            $table->timestamp('scheduled_unpublish_at')->nullable()->after('scheduled_publish_at');
            $table->json('schedule_metadata_json')->nullable()->after('data_json');

            $table->index(['status', 'scheduled_publish_at'], 'content_entries_status_scheduled_publish_idx');
            $table->index(['status', 'scheduled_unpublish_at'], 'content_entries_status_scheduled_unpublish_idx');
        });
    }

    public function down(): void
    {
        Schema::table('content_entries', function (Blueprint $table): void {
            $table->dropIndex('content_entries_status_scheduled_publish_idx');
            $table->dropIndex('content_entries_status_scheduled_unpublish_idx');

            $table->dropColumn([
                'published_at',
                'unpublished_at',
                'scheduled_publish_at',
                'scheduled_unpublish_at',
                'schedule_metadata_json',
            ]);
        });
    }
};
