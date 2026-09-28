<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('number')->unique();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('draft');
            $table->text('prize_text')->nullable();
            $table->string('rules_version')->default('v1.0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
