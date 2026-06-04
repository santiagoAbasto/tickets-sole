<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 20)->default('#3b82f6');
            // Marks a terminal state (Resuelto/Cerrado/Cancelado) — excluded from "overdue".
            $table->boolean('is_final')->default(false);
            // True for the status that counts as resolved for metrics.
            $table->boolean('is_resolved')->default(false);
            // True for the default status assigned on ticket creation.
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_final', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};
