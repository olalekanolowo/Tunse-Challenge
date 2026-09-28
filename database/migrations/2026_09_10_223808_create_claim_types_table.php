<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained();
            $table->string('code');
            $table->string('label');
            $table->unsignedInteger('base_points')->nullable();
            $table->boolean('active')->default(true);
            $table->text('validation_rule_text')->nullable();
            $table->timestamps();

            $table->unique(['phase_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_types');
    }
};
