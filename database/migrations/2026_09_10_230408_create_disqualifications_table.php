<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disqualifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('targetable');
            $table->foreignId('phase_id')->constrained();
            $table->text('reason');
            $table->foreignId('admin_id')->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['targetable_type', 'targetable_id', 'phase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disqualifications');
    }
};
