<?php

namespace App\Services\Engagement;

use App\Models\BookingDeposit;
use App\Models\BookingDocument;
use App\Models\Contact;
use App\Models\CrmFolders;
use App\Models\CrmPayment;
use App\Models\CustomerInvoice;
use App\Models\PortalAccessToken;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PortalService
{
    public function issueMagicLink(Contact $contact): array
    {
        $ttl = (int) config('engagement.portal.token_ttl_hours', 72);

        $access = PortalAccessToken::create([
            'tenant_id' => $contact->tenant_id,
            'contact_id' => $contact->id,
            'token' => Str::random(48),
            'expires_at' => Carbon::now()->addHours($ttl),
        ]);

        return [
            'token' => $access->token,
            'expires_at' => $access->expires_at,
            'portal_url' => url('/api/v1/portal/auth/' . $access->token),
        ];
    }

    public function authenticate(string $token): ?PortalAccessToken
    {
        $access = PortalAccessToken::where('token', $token)->first();

        if (! $access || ! $access->isValid()) {
            return null;
        }

        $access->update(['last_used_at' => now()]);

        return $access;
    }

    public function dashboard(Contact $contact): array
    {
        $folderIds = collect();
        if ($contact->email && \Illuminate\Support\Facades\Schema::hasTable('crm_passengers_name')) {
            $folderIds = \Illuminate\Support\Facades\DB::table('crm_passengers_name')
                ->where('email', $contact->email)
                ->pluck('folder_id');
        }

        $folders = $folderIds->isNotEmpty()
            ? CrmFolders::whereIn('id', $folderIds)->orderByDesc('travel_date')->limit(10)->get()
            : collect();

        $tenant = Tenant::find($contact->tenant_id);

        return [
            'contact' => $contact,
            'tenant' => $tenant ? ['name' => $tenant->name, 'slug' => $tenant->slug] : null,
            'bookings' => $folders->map(fn ($f) => $this->bookingSummary($f)),
            'loyalty_points' => $contact->loyalty_points,
        ];
    }

    public function bookingDetail(CrmFolders $folder, Contact $contact): array
    {
        $invoices = CustomerInvoice::where('folder_id', $folder->id)->get();
        $deposits = BookingDeposit::where('folder_id', $folder->id)->get();
        $payments = CrmPayment::where('folder_id', $folder->id)->where('status', 'approved')->get();
        $documents = BookingDocument::where('folder_id', $folder->id)->get();

        return [
            'booking' => $this->bookingSummary($folder),
            'itinerary' => $folder->itineraries ?? [],
            'invoices' => $invoices,
            'deposits' => $deposits,
            'payments' => $payments->map(fn ($p) => [
                'amount' => $p->payment,
                'date' => $p->pdate,
                'mode' => $p->payment_mode,
            ]),
            'documents' => $documents->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'type' => $d->document_type,
                'url' => url($d->file_path),
            ]),
        ];
    }

    private function bookingSummary(CrmFolders $folder): array
    {
        return [
            'id' => $folder->id,
            'destination' => $folder->destination,
            'travel_date' => $folder->travel_date,
            'status' => $folder->booking_status ?? 'confirmed',
            'sell' => $folder->sell,
            'remaining' => $folder->remaining,
        ];
    }
}
