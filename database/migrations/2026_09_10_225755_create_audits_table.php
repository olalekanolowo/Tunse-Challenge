<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained();
            $table->foreignId('auditor_id')->constrained('users');
            $table->string('outcome');
            $table->string('backend_lookup_key')->nullable();
            $table->text('audit_notes')->nullable();
            $table->timestamp('audited_at');
            $table->timestamps();

            $table->index('claim_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
