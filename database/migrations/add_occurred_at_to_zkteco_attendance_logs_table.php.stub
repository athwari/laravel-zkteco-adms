<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $table = config('zkteco-adms.table_prefix', 'zkteco_').'attendance_logs';

        Schema::table($table, function (Blueprint $table): void {
            $table->timestamp('occurred_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        $table = config('zkteco-adms.table_prefix', 'zkteco_').'attendance_logs';

        Schema::table($table, function (Blueprint $table): void {
            $table->dropIndex(['occurred_at']);
            $table->dropColumn('occurred_at');
        });
    }
};
