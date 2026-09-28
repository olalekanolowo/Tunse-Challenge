<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained();
            $table->string('challenge_id')->nullable()->unique();
            $table->string('phone');
            $table->string('state');
            $table->string('student_id_number');
            $table->string('student_id_file_path')->nullable();
            $table->string('department');
            $table->unsignedSmallInteger('graduation_year');
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status']);
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
