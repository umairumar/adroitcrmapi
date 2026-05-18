<?php

namespace App\Services\Integrations;

use App\Models\TenantWebhookEndpoint;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDispatcher
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(int $tenantId, string $event, array $payload): int
    {
        $endpoints = TenantWebhookEndpoint::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (TenantWebhookEndpoint $e) => $e->subscribesTo($event));

        $count = 0;
        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::create([
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'payload' => $this->wrapPayload($event, $payload),
                'status' => 'pending',
            ]);

            if ($this->attemptDelivery($delivery, $endpoint)) {
                $count++;
            }
        }

        return $count;
    }

    public function attemptDelivery(WebhookDelivery $delivery, ?TenantWebhookEndpoint $endpoint = null): bool
    {
        $endpoint ??= $delivery->endpoint;
        if (! $endpoint || ! $endpoint->is_active) {
            return false;
        }

        $body = json_encode($delivery->payload);
        $timestamp = time();
        $secret = $this->decryptSecret($endpoint);
        $signature = $secret
            ? hash_hmac('sha256', $timestamp . '.' . $body, $secret)
            : null;

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Delivery-Id' => (string) $delivery->id,
            'X-Webhook-Timestamp' => (string) $timestamp,
        ];

        if ($signature) {
            $headers['X-Webhook-Signature'] = $signature;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(15)
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $delivery->increment('attempts');
            $delivery->update([
                'response_code' => $response->status(),
                'response_body' => Str::limit($response->body(), 2000),
            ]);

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'success',
                    'delivered_at' => now(),
                    'error_message' => null,
                ]);

                return true;
            }

            $this->markFailed($delivery, 'HTTP ' . $response->status());
        } catch (\Throwable $e) {
            $delivery->increment('attempts');
            $this->markFailed($delivery, $e->getMessage());
        }

        return false;
    }

    public function processRetries(): int
    {
        $deliveries = WebhookDelivery::query()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->where('attempts', '<', (int) config('integrations.webhooks.max_attempts', 5))
            ->limit(50)
            ->get();

        $processed = 0;
        foreach ($deliveries as $delivery) {
            if ($this->attemptDelivery($delivery)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function wrapPayload(string $event, array $data): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'event' => $event,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    private function markFailed(WebhookDelivery $delivery, string $message): void
    {
        $maxAttempts = (int) config('integrations.webhooks.max_attempts', 5);
        $retryMinutes = config('integrations.webhooks.retry_minutes', [1, 5, 15, 60, 240]);
        $attemptIndex = min($delivery->attempts - 1, count($retryMinutes) - 1);
        $delay = $retryMinutes[$attemptIndex] ?? 60;

        $delivery->update([
            'status' => $delivery->attempts >= $maxAttempts ? 'failed' : 'pending',
            'error_message' => $message,
            'next_retry_at' => $delivery->attempts >= $maxAttempts ? null : now()->addMinutes($delay),
        ]);
    }

    private function decryptSecret(TenantWebhookEndpoint $endpoint): ?string
    {
        if (! $endpoint->secret) {
            return null;
        }

        try {
            return Crypt::decryptString($endpoint->secret);
        } catch (\Throwable) {
            return $endpoint->secret;
        }
    }
}
