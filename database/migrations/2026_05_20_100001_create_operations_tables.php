<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('type', 50)->nullable(); // hotel, transport, visa, etc.
            $table->decimal('default_commission_rate', 8, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });

        Schema::create('staff_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('applies_to', 30)->default('booked_by'); // booked_by, agent, custom
            $table->string('calculation_type', 20)->default('percent'); // percent, fixed
            $table->string('calculation_base', 30)->default('folder_commission');
            $table->decimal('rate', 12, 4);
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('supplier_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->string('supplier_name_match')->nullable();
            $table->string('component', 30)->default('any'); // hotel, transport, other, any
            $table->string('calculation_type', 20)->default('percent');
            $table->string('calculation_base', 30)->default('line_commission');
            $table->decimal('rate', 12, 4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('folder_id');
            $table->string('recipient_type', 20); // staff, supplier, affiliate
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('rule_type', 50)->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('rate', 12, 4)->default(0);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending'); // pending, approved, paid, void
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'folder_id']);
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        Schema::create('commission_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('recipient_type', 20)->default('staff');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, approved, paid
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('folder_id');
            $table->string('label', 100)->default('Deposit');
            $table->string('deposit_type', 30)->default('deposit'); // deposit, installment, balance
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending, partial, paid, overdue
            $table->unsignedBigInteger('legacy_installment_id')->nullable();
            $table->timestamps();

            $table->index(['folder_id', 'status']);
        });

        Schema::create('booking_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('folder_id');
            $table->string('title');
            $table->string('document_type', 50)->default('other');
            $table->string('file_path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['folder_id']);
        });

        Schema::create('expense_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('category', 50)->default('other');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('GBP');
            $table->date('receipt_date');
            $table->string('file_path')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('work_date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->string('status', 20)->default('present'); // present, absent, leave, half_day
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('leave_type', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('expense_receipts');
        Schema::dropIfExists('booking_documents');
        Schema::dropIfExists('booking_deposits');
        Schema::dropIfExists('commission_payouts');
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('supplier_commission_rules');
        Schema::dropIfExists('staff_commission_rules');
        Schema::dropIfExists('suppliers');
    }
};
