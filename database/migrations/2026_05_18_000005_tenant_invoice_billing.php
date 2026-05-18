<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (Schema::hasColumn('tenants', 'stripe_customer_id')) {
                    $table->dropColumn('stripe_customer_id');
                }
                if (Schema::hasColumn('tenants', 'stripe_subscription_id')) {
                    $table->dropColumn('stripe_subscription_id');
                }
                if (! Schema::hasColumn('tenants', 'billing_status')) {
                    $table->string('billing_status', 30)->default('trial')->after('trial_ends_at');
                }
                if (! Schema::hasColumn('tenants', 'billing_email')) {
                    $table->string('billing_email')->nullable()->after('billing_status');
                }
                if (! Schema::hasColumn('tenants', 'payment_terms_days')) {
                    $table->unsignedSmallInteger('payment_terms_days')->default(30)->after('billing_email');
                }
            });
        }

        Schema::create('tenant_billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->string('plan', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('status', 20)->default('draft'); // draft, sent, paid, overdue, void
            $table->date('issue_date');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_invoices');

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                foreach (['billing_status', 'billing_email', 'payment_terms_days'] as $col) {
                    if (Schema::hasColumn('tenants', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
