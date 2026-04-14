<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->json('answer_json')->nullable()->after('answer_date');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn('answer_json');
        });
    }
};