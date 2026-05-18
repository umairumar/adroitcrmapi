<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, cancelled
            $table->string('plan', 50)->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('billing_status', 30)->default('trial'); // trial, active, grace_period, suspended
            $table->string('billing_email')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['status', 'plan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
