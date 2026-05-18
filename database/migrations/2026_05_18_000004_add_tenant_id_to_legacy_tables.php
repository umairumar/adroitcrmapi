<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string> */
    private array $tables = [
        'user',
        'crm_company',
        'crm_leads',
        'crm_folders',
        'crm_payments',
        'crm_leads_remarks',
        'crm_hotels',
        'crm_transport',
        'crm_passengers',
        'crm_passengers_name',
        'crm_others',
        'crm_itinerary',
        'crm_folders_installments',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
            });
        }

        if (Schema::hasTable('user') && ! Schema::hasColumn('user', 'is_platform_admin')) {
            Schema::table('user', function (Blueprint $table) {
                $table->boolean('is_platform_admin')->default(false)->after('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }

        if (Schema::hasTable('user') && Schema::hasColumn('user', 'is_platform_admin')) {
            Schema::table('user', function (Blueprint $table) {
                $table->dropColumn('is_platform_admin');
            });
        }
    }
};
