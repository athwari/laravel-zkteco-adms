<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::create($prefix.'device_commands', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('device_id')->constrained($prefix.'devices')->cascadeOnDelete();
            $table->unsignedBigInteger('command_id')->nullable();
            $table->string('command_type', 20);
            $table->text('command_content');
            $table->string('status', 20)->default('pending');
            $table->integer('return_code')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('response')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index('status');
            $table->index('command_id');
        });
    }

    public function down(): void
    {
        $prefix = config('zkteco-adms.table_prefix', 'zkteco_');

        Schema::dropIfExists($prefix.'device_commands');
    }
};
