<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('fingerprint', 64)->unique();
            $table->string('name')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('server_url', 500)->nullable();
            $table->string('hosting_type', 20)->nullable();
            $table->string('hosting_provider')->nullable();
            $table->string('cpanel_user')->nullable();
            $table->text('cpanel_password')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_credentials');
    }
};
