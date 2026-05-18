<?php

namespace Tests\Unit;

use App\Models\CrmFolders;
use App\Models\CrmLead;
use App\Models\CrmPassengersName;
use App\Models\CustomerInvoice;
use App\Services\Finance\CustomerInvoicePdfService;
use App\Services\Integrations\WhiteLabelService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class CustomerInvoicePdfServiceTest extends TestCase
{
    public function test_build_view_data_uses_primary_passenger_as_bill_to(): void
    {
        $whiteLabel = $this->createMock(WhiteLabelService::class);
        $whiteLabel->method('forTenant')->willReturn((object) [
            'app_name' => 'Haq Travels',
            'logo_url' => null,
            'company_address' => null,
            'support_phone' => null,
            'support_email' => null,
            'vat_number' => null,
            'company_registration' => null,
            'primary_color' => '#0f766e',
            'secondary_color' => '#134e4a',
            'accent_color' => '#14b8a6',
            'invoice_terms' => null,
            'invoice_payment_instructions' => null,
            'invoice_bank_name' => null,
            'invoice_sort_code' => null,
            'invoice_account_number' => null,
            'invoice_iban' => null,
        ]);

        $service = new CustomerInvoicePdfService($whiteLabel);

        $passenger = new CrmPassengersName([
            'title' => 'Mr',
            'fname' => 'Zieshan',
            'lname' => 'Khan',
        ]);

        $folder = new CrmFolders([
            'id' => 42,
            'tenant_id' => 1,
            'destination' => 'Umrah',
            'travel_date' => '2026-06-01',
            'sell' => 2500,
            'deposit_paid' => 500,
        ]);
        $folder->setRelation('passengersNames', new Collection([$passenger]));
        $folder->setRelation('hotels', new Collection);
        $folder->setRelation('lead', null);
        $folder->setRelation('payments', new Collection);

        $invoice = new CustomerInvoice([
            'tenant_id' => 1,
            'folder_id' => 42,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'currency' => 'GBP',
            'subtotal' => 2500,
            'tax_amount' => 0,
            'total' => 2500,
            'amount_paid' => 500,
        ]);
        $invoice->setRelation('lines', new Collection);
        $invoice->setRelation('folder', $folder);

        $data = $service->buildViewData($invoice);

        $this->assertSame('Mr Zieshan Khan', $data['billTo']);
        $this->assertSame('42', $data['bookingRef']);
        $this->assertSame(2000.0, $data['balanceDue']);
    }
}
