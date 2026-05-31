<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_files', function (Blueprint $table) {
            $table->json('validation_warnings')->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('model_files', function (Blueprint $table) {
            $table->dropColumn('validation_warnings');
        });
    }
};
