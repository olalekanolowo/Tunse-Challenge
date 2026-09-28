<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('institution_id')->constrained();
            $table->string('platform');
            $table->string('post_url');
            $table->string('community_type');
            $table->boolean('consent_confirmed');
            $table->string('title');
            $table->text('description');
            $table->text('score_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_submissions');
    }
};
