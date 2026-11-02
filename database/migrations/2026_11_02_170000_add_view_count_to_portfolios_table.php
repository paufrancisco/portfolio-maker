<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('portfolios', 'view_count')) {
            return;
        }

        Schema::table('portfolios', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('portfolios', 'view_count')) {
            Schema::table('portfolios', function (Blueprint $table) {
                $table->dropColumn('view_count');
            });
        }
    }
};
