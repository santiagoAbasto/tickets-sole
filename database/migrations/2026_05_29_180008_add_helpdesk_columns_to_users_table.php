<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar_path');
            $table->string('job_title')->nullable()->after('phone');
            // Quick flag for agent listings/filters (role is still source of truth).
            $table->boolean('is_agent')->default(false)->after('job_title');
            $table->boolean('is_active')->default(true)->after('is_agent');
            $table->foreignId('department_id')->nullable()->after('is_active')
                ->constrained('departments')->nullOnDelete();
            $table->timestamp('last_active_at')->nullable()->after('department_id');

            $table->index('is_agent');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn([
                'avatar_path', 'phone', 'job_title', 'is_agent', 'is_active', 'last_active_at',
            ]);
        });
    }
};
