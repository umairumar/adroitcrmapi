<?php

namespace App\Services\Operations;

use App\Models\BookingDeposit;
use App\Models\CommissionEntry;
use App\Models\CrmFolders;
use App\Models\CrmLead;

class BookingOperationsService
{
    public function __construct(
        private readonly DepositService $deposits,
        private readonly CommissionCalculationService $commissions,
    ) {}

    public function updateStatus(CrmFolders $folder, string $status): CrmFolders
    {
        $allowed = array_keys(config('operations.booking_statuses', []));
        if (! in_array($status, $allowed, true)) {
            abort(422, 'Invalid booking status.');
        }

        $folder->update(['booking_status' => $status]);

        if ($status === 'confirmed') {
            $this->commissions->calculateForFolder($folder->fresh());
        }

        return $folder->fresh();
    }

    public function linkLead(CrmFolders $folder, int $leadId): CrmFolders
    {
        $lead = CrmLead::findOrFail($leadId);
        $folder->update(['lead_id' => $lead->id]);

        return $folder->fresh();
    }

    public function operationsSummary(CrmFolders $folder): array
    {
        $deposits = BookingDeposit::where('folder_id', $folder->id)->orderBy('due_date')->get();
        $commissions = CommissionEntry::where('folder_id', $folder->id)->get();

        return [
            'booking' => $folder,
            'deposits' => $deposits,
            'deposit_summary' => [
                'total_scheduled' => (float) $deposits->sum('amount'),
                'total_paid' => (float) $deposits->sum('paid_amount'),
                'outstanding' => (float) $deposits->sum(fn ($d) => $d->remaining()),
            ],
            'commissions' => [
                'staff' => (float) $commissions->where('recipient_type', 'staff')->sum('amount'),
                'supplier' => (float) $commissions->where('recipient_type', 'supplier')->sum('amount'),
                'entries' => $commissions,
            ],
        ];
    }
}
