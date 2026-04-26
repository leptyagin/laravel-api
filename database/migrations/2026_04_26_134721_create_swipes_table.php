<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swipes', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id_1')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id_2')->index()->constrained('users')->cascadeOnDelete();

            $table->boolean('user_like_1')->nullable();
            $table->boolean('user_like_2')->nullable();

            $table->timestamp('matched_at')->nullable();

            $table->unique(['user_id_1', 'user_id_2']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swipes');
    }
};
