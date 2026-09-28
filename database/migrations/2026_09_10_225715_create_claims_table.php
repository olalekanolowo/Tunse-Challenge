<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('institution_id')->constrained();
            $table->foreignId('phase_id')->constrained();
            $table->foreignId('claim_type_id')->constrained();
            $table->string('recruit_name');
            $table->string('recruit_phone');
            $table->string('state');
            $table->string('lga');
            $table->string('category')->nullable();
            $table->date('date_recruited');
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('declaration_at');
            $table->string('status')->default('submitted');
            $table->unsignedInteger('provisional_points')->default(0);
            $table->unsignedInteger('audited_points')->nullable();
            $table->string('tworker_phone')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->decimal('approx_value', 12, 2)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index('recruit_phone');
            $table->index(['user_id', 'phase_id']);
            $table->index(['institution_id', 'phase_id']);
            $table->index('status');
            $table->unique(['user_id', 'claim_type_id', 'recruit_phone', 'phase_id'], 'claims_no_exact_duplicate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
