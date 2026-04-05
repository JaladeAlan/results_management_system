<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->unsignedTinyInteger('ca1')->default(0)->after('student_id'); // max 20
            $table->unsignedTinyInteger('ca2')->default(0)->after('ca1');        // max 20
            $table->dropColumn('ca');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->unsignedTinyInteger('ca')->default(0)->after('student_id');
            $table->dropColumn(['ca1', 'ca2']);
        });
    }
};
