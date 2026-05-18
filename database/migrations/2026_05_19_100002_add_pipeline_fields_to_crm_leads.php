<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_leads')) {
            return;
        }

        Schema::table('crm_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('crm_leads', 'pipeline_stage_id')) {
                $table->unsignedBigInteger('pipeline_stage_id')->nullable()->after('tenant_id');
                $table->index('pipeline_stage_id');
            }
            if (! Schema::hasColumn('crm_leads', 'contact_id')) {
                $table->unsignedBigInteger('contact_id')->nullable()->after('pipeline_stage_id');
            }
            if (! Schema::hasColumn('crm_leads', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->nullable()->after('contact_id');
            }
            if (! Schema::hasColumn('crm_leads', 'source')) {
                $table->string('source', 50)->nullable()->after('lead_type');
            }
            if (! Schema::hasColumn('crm_leads', 'source_detail')) {
                $table->string('source_detail', 255)->nullable()->after('source');
            }
            if (! Schema::hasColumn('crm_leads', 'utm_source')) {
                $table->string('utm_source', 100)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'utm_medium')) {
                $table->string('utm_medium', 100)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'utm_campaign')) {
                $table->string('utm_campaign', 100)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'utm_content')) {
                $table->string('utm_content', 100)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'utm_term')) {
                $table->string('utm_term', 100)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'stage_entered_at')) {
                $table->timestamp('stage_entered_at')->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'referral_code')) {
                $table->string('referral_code', 50)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'estimated_value')) {
                $table->decimal('estimated_value', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('crm_leads', 'duplicate_of_lead_id')) {
                $table->unsignedBigInteger('duplicate_of_lead_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_leads')) {
            return;
        }

        $columns = [
            'pipeline_stage_id', 'contact_id', 'organization_id', 'source', 'source_detail',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'assigned_at', 'stage_entered_at', 'referral_code', 'estimated_value', 'duplicate_of_lead_id',
        ];

        foreach ($columns as $col) {
            if (Schema::hasColumn('crm_leads', $col)) {
                Schema::table('crm_leads', fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};
