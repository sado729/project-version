<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('version_histories', function (Blueprint $table) {
            $table->id();
            $table->string('version', 15);
            $table->string('commit_hash', 40)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->index(['recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('version_histories');
    }
};
