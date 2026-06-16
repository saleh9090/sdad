<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_country_code', 8)->default('+968')->after('phone');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_country_code', 8)->default('+968')->after('phone');
            $table->string('guardian_phone_country_code', 8)->nullable()->after('guardian_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone_country_code', 'guardian_phone_country_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_country_code');
        });
    }
};
