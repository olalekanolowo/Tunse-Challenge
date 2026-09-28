<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_adjustments', function (Blueprint $table) {
            $table->id();
            $table->morphs('targetable');
            $table->foreignId('phase_id')->constrained();
            $table->integer('points');
            $table->text('reason');
            $table->foreignId('admin_id')->constrained('users');
            $table->timestamp('created_at')->nullable();

            $table->index(['targetable_type', 'targetable_id', 'phase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_adjustments');
    }
};
