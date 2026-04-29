<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('subscribable');
            $table->enum('plan_type', ['premium']);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending');
            $table->unsignedInteger('amount');
            $table->string('midtrans_order_id')->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_snap_token')->nullable();
            $table->string('midtrans_redirect_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
