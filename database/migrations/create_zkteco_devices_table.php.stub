<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::create($prefix.'devices', function (Blueprint $table): void {
            $table->id();
            $table->string('serial_number', 64)->unique();
            $table->string('name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('push_version')->nullable();
            $table->string('device_type')->nullable();
            $table->string('language', 30)->nullable();
            $table->string('status', 20)->default('unknown');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->unsignedBigInteger('att_stamp')->default(0);
            $table->unsignedBigInteger('op_stamp')->default(0);
            $table->json('options')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('last_activity_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::dropIfExists($prefix.'devices');
    }
};
