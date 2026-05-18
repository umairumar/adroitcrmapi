<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('app_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('accent_color', 20)->nullable();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->string('email_from_name')->nullable();
            $table->text('email_footer_html')->nullable();
            $table->text('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('config_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('integration_providers')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->string('status', 20)->default('inactive');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'provider_id']);
        });

        Schema::create('integration_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_integration_id')->constrained('tenant_integrations')->cascadeOnDelete();
            $table->string('sync_type', 30);
            $table->string('status', 20);
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedInteger('records_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('marketplace_apps', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->default('integration');
            $table->json('scopes')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->default(60);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenant_api_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('marketplace_app_id')->constrained('marketplace_apps')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'marketplace_app_id']);
        });

        Schema::create('tenant_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_api_app_id')->constrained('tenant_api_apps')->cascadeOnDelete();
            $table->string('name');
            $table->string('key_prefix', 12);
            $table->string('key_hash', 64);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['key_prefix', 'revoked_at']);
        });

        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_api_key_id')->constrained('tenant_api_keys')->cascadeOnDelete();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_api_key_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
        Schema::dropIfExists('tenant_api_keys');
        Schema::dropIfExists('tenant_api_apps');
        Schema::dropIfExists('marketplace_apps');
        Schema::dropIfExists('integration_sync_logs');
        Schema::dropIfExists('tenant_integrations');
        Schema::dropIfExists('integration_providers');
        Schema::dropIfExists('tenant_branding');
    }
};
