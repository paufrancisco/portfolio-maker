<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('payment_reference', 50)->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_proof_path', 'payment_submitted_at']);
        });
    }
};
