<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_notes', function (Blueprint $table) {
            // Origin of the note: null = written by hand, 'whatsapp' = copy of a sent WhatsApp message.
            $table->string('channel')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_notes', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
