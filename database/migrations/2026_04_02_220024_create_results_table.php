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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->cascadeOnDelete();
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('ca')->default(0);    // max 30
            $table->unsignedTinyInteger('exam')->default(0);  // max 70
            $table->unsignedTinyInteger('total')->default(0); // ca + exam
            $table->enum('status', ['pending', 'approved', 'flagged'])->default('pending');
            $table->text('flag_description')->nullable();
            $table->timestamps();
 
            $table->unique(['course_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
