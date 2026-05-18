<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_folders')) {
            Schema::table('crm_folders', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_folders', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('tenant_id');
                }
                if (! Schema::hasColumn('crm_folders', 'booking_status')) {
                    $table->string('booking_status', 30)->default('quote')->after('invoice_status');
                }
                if (! Schema::hasColumn('crm_folders', 'deposit_required')) {
                    $table->decimal('deposit_required', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('crm_folders', 'deposit_paid')) {
                    $table->decimal('deposit_paid', 12, 2)->default(0);
                }
            });
        }

        if (Schema::hasTable('crm_payments')) {
            Schema::table('crm_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_payments', 'payment_type')) {
                    $table->string('payment_type', 30)->default('payment')->after('folder_id');
                }
                if (! Schema::hasColumn('crm_payments', 'booking_deposit_id')) {
                    $table->unsignedBigInteger('booking_deposit_id')->nullable()->after('payment_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_folders')) {
            foreach (['lead_id', 'booking_status', 'deposit_required', 'deposit_paid'] as $col) {
                if (Schema::hasColumn('crm_folders', $col)) {
                    Schema::table('crm_folders', fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }

        if (Schema::hasTable('crm_payments')) {
            foreach (['payment_type', 'booking_deposit_id'] as $col) {
                if (Schema::hasColumn('crm_payments', $col)) {
                    Schema::table('crm_payments', fn (Blueprint $t) => $t->dropColumn($col));
                }
            }
        }
    }
};
