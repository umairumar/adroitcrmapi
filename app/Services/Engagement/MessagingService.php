<?php

namespace App\Services\Engagement;

use App\Mail\MEmail;
use App\Models\Contact;
use App\Models\ConversationThread;
use App\Models\CrmLead;
use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MessagingService
{
    public function findOrCreateThread(
        string $channel,
        ?int $contactId = null,
        ?int $leadId = null,
        ?int $tenantId = null,
    ): ConversationThread {
        $query = ConversationThread::where('channel', $channel);

        if ($contactId) {
            $query->where('contact_id', $contactId);
        }
        if ($leadId) {
            $query->where('lead_id', $leadId);
        }

        $thread = $query->where('status', 'open')->first();

        if ($thread) {
            return $thread;
        }

        return ConversationThread::create([
            'tenant_id' => $tenantId,
            'contact_id' => $contactId,
            'lead_id' => $leadId,
            'channel' => $channel,
            'status' => 'open',
        ]);
    }

    public function send(
        ConversationThread $thread,
        string $body,
        ?string $subject = null,
        ?int $sentBy = null,
        ?array $variables = [],
    ): Message {
        $body = $this->renderTemplate($body, $variables);

        $message = Message::create([
            'tenant_id' => $thread->tenant_id,
            'thread_id' => $thread->id,
            'direction' => 'outbound',
            'channel' => $thread->channel,
            'body' => $body,
            'status' => 'queued',
            'sent_by' => $sentBy,
        ]);

        try {
            $this->dispatchToChannel($thread, $body, $subject);
            $message->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $message->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);
        }

        $thread->update(['last_message_at' => now()]);

        return $message->fresh();
    }

    public function sendFromTemplate(
        MessageTemplate $template,
        Contact|CrmLead $recipient,
        ?int $sentBy = null,
    ): Message {
        $contactId = $recipient instanceof Contact ? $recipient->id : $recipient->contact_id;
        $leadId = $recipient instanceof CrmLead ? $recipient->id : null;

        $thread = $this->findOrCreateThread(
            $template->channel,
            $contactId,
            $leadId,
            $recipient->tenant_id ?? null,
        );

        $vars = $this->variablesForRecipient($recipient);

        return $this->send($thread, $template->body, $template->subject, $sentBy, $vars);
    }

    public function recordInbound(
        ConversationThread $thread,
        string $body,
        ?string $externalId = null,
    ): Message {
        $message = Message::create([
            'tenant_id' => $thread->tenant_id,
            'thread_id' => $thread->id,
            'direction' => 'inbound',
            'channel' => $thread->channel,
            'body' => $body,
            'status' => 'delivered',
            'external_id' => $externalId,
            'sent_at' => now(),
        ]);

        $thread->update(['last_message_at' => now()]);

        return $message;
    }

    private function dispatchToChannel(ConversationThread $thread, string $body, ?string $subject): void
    {
        $email = $this->resolveEmail($thread);

        $phone = $this->resolvePhone($thread);

        match ($thread->channel) {
            'email' => $this->sendEmail($email, $subject ?? 'Message', $body),
            'whatsapp', 'sms', 'messenger', 'web_chat' => $this->sendExternalStub(
                $thread->channel,
                $thread->channel === 'email' ? $email : $phone,
                $body
            ),
            default => throw new \InvalidArgumentException('Unsupported channel'),
        };
    }

    private function sendEmail(?string $email, string $subject, string $body): void
    {
        if (! $email) {
            throw new \RuntimeException('No email address for recipient');
        }

        Mail::to($email)->send(new MEmail($body, $subject));
    }

    private function sendExternalStub(string $channel, ?string $destination, string $body): void
    {
        // Ready for Twilio / WhatsApp Cloud API / Meta — logs until credentials configured
        \Log::info("engagement.{$channel}.send", [
            'to' => $destination,
            'body' => Str::limit($body, 200),
            'api_url' => config("engagement.providers.{$channel}"),
        ]);
    }

    private function resolveEmail(ConversationThread $thread): ?string
    {
        if ($thread->contact_id) {
            return Contact::find($thread->contact_id)?->email;
        }
        if ($thread->lead_id) {
            return CrmLead::find($thread->lead_id)?->email;
        }

        return null;
    }

    private function resolvePhone(ConversationThread $thread): ?string
    {
        if ($thread->contact_id) {
            return Contact::find($thread->contact_id)?->phone;
        }
        if ($thread->lead_id) {
            return CrmLead::find($thread->lead_id)?->phone;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderTemplate(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return $body;
    }

    /**
     * @return array<string, string>
     */
    private function variablesForRecipient(Contact|CrmLead $recipient): array
    {
        return [
            'name' => $recipient->name ?? '',
            'email' => $recipient->email ?? '',
            'phone' => $recipient->phone ?? '',
        ];
    }
}
