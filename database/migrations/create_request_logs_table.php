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
        Schema::create('gl_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('method', 10);
            $table->string('path');
            $table->unsignedSmallInteger('status_code');
            $table->string('ip', 45)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->json('response_body')->nullable();
            $table->float('duration_ms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_request_logs');
    }
};
