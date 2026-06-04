<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SLA policies are scaffolded for Fase 2 (escalamiento/alertas). The
     * structure is ready so tickets can later reference a policy instead of
     * deriving SLA purely from priority.
     */
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('priority_id')->nullable()->constrained('ticket_priorities')->nullOnDelete();
            $table->unsignedSmallInteger('response_hours')->default(24);
            $table->unsignedSmallInteger('resolution_hours')->default(48);
            // Only count business hours when computing breaches (Fase 2).
            $table->boolean('business_hours_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
