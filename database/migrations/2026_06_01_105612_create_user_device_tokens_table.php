<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_token')->unique();
            $table->string('platform')->default('android');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
