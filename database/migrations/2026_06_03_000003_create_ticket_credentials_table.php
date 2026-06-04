<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_credentials', function (Blueprint $table) {
            // Internal-only hosting/cPanel access for a ticket. Never exposed to customers.
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cpanel_user')->nullable();
            $table->text('cpanel_password')->nullable(); // stored encrypted (cast)
            $table->string('server_url', 500)->nullable();
            $table->string('hosting_type', 20)->nullable(); // osole | external
            $table->string('hosting_provider')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_credentials');
    }
};
