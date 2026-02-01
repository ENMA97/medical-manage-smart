<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('county_id')->nullable()->after('address');
            $table->foreign('county_id')->references('id')->on('counties')->nullOnDelete();
            $table->index('county_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['county_id']);
            $table->dropIndex(['county_id']);
            $table->dropColumn('county_id');
        });
    }
};
