<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 20)->default('#64748b');
            // SLA targets in hours, used to compute due_at and overdue state.
            $table->unsignedSmallInteger('response_hours')->default(24);
            $table->unsignedSmallInteger('resolution_hours')->default(48);
            $table->unsignedSmallInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_priorities');
    }
};
