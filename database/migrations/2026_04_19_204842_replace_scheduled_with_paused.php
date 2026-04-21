<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quizzes')
            ->where('status', 'scheduled')
            ->update(['status' => 'paused']);
    }

    public function down(): void
    {
        DB::table('quizzes')
            ->where('status', 'paused')
            ->update(['status' => 'scheduled']);
    }
};