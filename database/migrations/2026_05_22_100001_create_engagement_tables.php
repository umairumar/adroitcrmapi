<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('channel', 30);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'channel']);
        });

        Schema::create('conversation_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('channel', 30);
            $table->string('external_id')->nullable();
            $table->string('status', 20)->default('open');
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'channel', 'status']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('thread_id')->constrained('conversation_threads')->cascadeOnDelete();
            $table->string('direction', 10); // inbound, outbound
            $table->string('channel', 30);
            $table->text('body')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('external_id')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('channel', 30)->default('email');
            $table->foreignId('segment_id')->nullable()->constrained('segments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order')->default(1);
            $table->unsignedInteger('delay_hours')->default(0);
            $table->foreignId('template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('current_step')->default(0);
            $table->timestamp('next_send_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status', 'next_send_at']);
        });

        Schema::create('client_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('portal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('contact_id');
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_access_tokens');
        Schema::dropIfExists('client_feedback');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaign_steps');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_threads');
        Schema::dropIfExists('message_templates');
    }
};
