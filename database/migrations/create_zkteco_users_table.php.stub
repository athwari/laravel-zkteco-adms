<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::create($prefix.'users', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->string('pin')->unique();
            $table->string('name')->nullable();
            $table->string('card_number')->nullable();
            $table->unsignedTinyInteger('privilege')->default(0);
            $table->string('password')->nullable();
            $table->string('group')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('fingerprints')->nullable();
            $table->json('face_templates')->nullable();
            $table->foreignId('device_id')->nullable()->constrained($prefix.'devices')->onDelete('cascade');
            $table->unsignedBigInteger('app_user_id')->nullable()->index();
            $table->timestamps();

            $table->index('card_number');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::dropIfExists($prefix.'users');
    }
};
