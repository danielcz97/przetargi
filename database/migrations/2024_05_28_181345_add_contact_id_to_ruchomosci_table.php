<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ruchomosci', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->after('id');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.s
     */
    public function down(): void
    {
        Schema::table('ruchomosci', function (Blueprint $table) {
            //
        });
    }
};
