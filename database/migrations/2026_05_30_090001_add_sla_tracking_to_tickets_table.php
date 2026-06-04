<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Guards so the SLA scheduler alerts/escalates each ticket once.
            $table->timestamp('due_soon_notified_at')->nullable()->after('last_activity_at');
            $table->timestamp('overdue_notified_at')->nullable()->after('due_soon_notified_at');
            $table->timestamp('escalated_at')->nullable()->after('overdue_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['due_soon_notified_at', 'overdue_notified_at', 'escalated_at']);
        });
    }
};
