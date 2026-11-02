<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->json('published_data')->nullable()->after('data');
            $table->timestamp('published_at')->nullable()->after('status');
        });

        // Backfill: already-published portfolios keep their current content
        DB::table('portfolios')
            ->where('status', 'published')
            ->update([
                'published_data' => DB::raw('data'),
                'published_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['published_data', 'published_at']);
        });
    }
};
