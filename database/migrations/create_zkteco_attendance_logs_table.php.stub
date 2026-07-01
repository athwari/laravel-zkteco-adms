<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::create($prefix.'attendance_logs', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('device_id')->constrained($prefix.'devices')->cascadeOnDelete();
            $table->string('pin');
            $table->timestamp('recorded_at');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedTinyInteger('verify_mode')->default(0);
            $table->string('work_code', 32)->default('');
            $table->string('reserved_1')->nullable();
            $table->string('reserved_2')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'pin', 'recorded_at']);
            $table->index('recorded_at');
            $table->index('pin');
        });
    }

    public function down(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::dropIfExists($prefix.'attendance_logs');
    }
};
