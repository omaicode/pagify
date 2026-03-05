<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('nickname', 100)->nullable()->after('name');
            $table->text('bio')->nullable()->after('locale');
            $table->string('avatar_path', 255)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn(['nickname', 'bio', 'avatar_path']);
        });
    }
};
