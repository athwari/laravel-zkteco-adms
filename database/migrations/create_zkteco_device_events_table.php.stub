<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::create($prefix.'device_events', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('device_id')->constrained($prefix.'devices')->cascadeOnDelete();
            $table->string('event_type', 30);
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['device_id', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::dropIfExists($prefix.'device_events');
    }
};
