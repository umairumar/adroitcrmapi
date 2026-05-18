<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_branding', function (Blueprint $table) {
            $table->text('company_address')->nullable()->after('support_phone');
            $table->string('company_registration', 80)->nullable()->after('company_address');
            $table->string('vat_number', 40)->nullable()->after('company_registration');
            $table->string('invoice_bank_name')->nullable()->after('vat_number');
            $table->string('invoice_sort_code', 20)->nullable()->after('invoice_bank_name');
            $table->string('invoice_account_number', 40)->nullable()->after('invoice_sort_code');
            $table->string('invoice_iban', 50)->nullable()->after('invoice_account_number');
            $table->text('invoice_payment_instructions')->nullable()->after('invoice_iban');
            $table->text('invoice_terms')->nullable()->after('invoice_payment_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_branding', function (Blueprint $table) {
            $table->dropColumn([
                'company_address',
                'company_registration',
                'vat_number',
                'invoice_bank_name',
                'invoice_sort_code',
                'invoice_account_number',
                'invoice_iban',
                'invoice_payment_instructions',
                'invoice_terms',
            ]);
        });
    }
};
