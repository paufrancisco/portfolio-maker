<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('email')->index();
            $table->text('details');
            $table->string('attachment_path')->nullable();
            $table->string('token', 64)->unique();
            $table->string('status')->default('received')->index();
            $table->decimal('quote_amount', 10, 2)->nullable();
            $table->text('quote_note')->nullable();
            $table->string('delivery_url')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
