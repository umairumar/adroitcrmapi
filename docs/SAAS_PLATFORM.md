# Adroit Travel CRM — Travel Agency SaaS Platform

Multi-tenant Laravel API (`adroitcrmapi`) paired with the React frontend (`adroitsolscrmfront`). This document maps product capabilities to API modules.

## Invoice generation (PDF)

Customer invoices are generated from confirmed **booking folders** and match the travel-agency layout (company header, “Invoice For [Customer]”, booking details, line items, payments, bank details).

### Workflow

1. Complete a booking folder with passengers, destination, travel date, and pricing.
2. `POST /api/v1/finance/folders/{folderId}/invoice` — creates AR invoice + GL journal entry.
3. `GET /api/v1/finance/invoices/{id}/pdf` — download PDF.
4. `GET /api/v1/finance/invoices/{id}/pdf?inline=1` — view in browser.
5. `GET /api/v1/finance/invoices/{id}/preview` — JSON payload for UI preview.

### Branding & bank details (white-label)

Configure under `PUT /api/v1/white-label`:

| Field | Purpose |
|-------|---------|
| `company_address` | Letterhead address on PDF |
| `vat_number`, `company_registration` | Legal identifiers |
| `invoice_bank_name`, `invoice_sort_code`, `invoice_account_number`, `invoice_iban` | Payment block |
| `invoice_payment_instructions`, `invoice_terms` | Footer / payment notes |
| `logo_url`, `primary_color`, etc. | Visual branding |

Run `composer require barryvdh/laravel-dompdf` (already in `composer.json`) and `php artisan migrate` after pulling.

---

## Feature map

### Account management (B2B & B2C)

| Capability | API |
|------------|-----|
| Corporate accounts | `GET/POST /organizations`, contacts |
| Individual travelers | `GET/POST /contacts`, CRM leads |
| B2C self-service portal | `POST /portal/links`, `GET /portal/*` |
| Tenant onboarding | `POST /tenants/register` |

### Roles & hierarchy

| Capability | API |
|------------|-----|
| Users & agents | `GET/POST /users` |
| Permission-based access | Enforced via `AuthorizationService` (`finance.view`, `finance.manage`, etc.) |
| Branch scoping | `crm_companies` / branch filters on leads & folders |
| Lead assignment rules | `GET/POST /lead-assignment-rules` |

### Multi-channel engagement

| Channel | API |
|---------|-----|
| Unified inbox | `GET /inbox`, reply, assign |
| Email / WhatsApp / SMS / Messenger / web | `POST /engagement/webhooks/{channel}` |
| Message templates | `GET/POST /message-templates` |
| Drip campaigns | `GET/POST /campaigns`, `POST /campaigns/{id}/launch` |

### Lead management

| Capability | API |
|------------|-----|
| Capture (website, API) | `POST /leadsdirectstore`, `POST /external/leads` |
| CRM pipeline | `GET/POST /leads`, remarks, tags |
| UTM / source tracking | Lead model fields |
| Duplicate detection | `duplicate_of_lead_id` |

### Sales pipeline automation

| Capability | API |
|------------|-----|
| Pipeline stages | `GET/POST /pipeline-stages` |
| Funnel analytics | `GET /analytics/funnel` |
| Stage automation | `sales:seed-pipeline-stages`, lead stage transitions |

### Loyalty & referral

| Capability | API |
|------------|-----|
| Points earn/redeem | `POST /loyalty/contacts/{id}/earn`, `/redeem` |
| Referral codes | `GET/POST /referral-codes` |

### Customer segmentation

| Capability | API |
|------------|-----|
| Tags | `GET/POST /tags` |
| Segments (filters) | `GET/POST /segments` |
| Campaign targeting | Campaign recipients |

### Multi-location management

| Capability | API |
|------------|-----|
| Branches / franchises | `crm_companies`, `GET /analytics/branches` |
| Tenant-wide oversight | Platform admin tenant routes |

### Reporting & KPIs

| Capability | API |
|------------|-----|
| Operations dashboard | `GET /dashboard` |
| Finance reports | `GET /finance/reports/*` (trial balance, AR/AP aging, budget variance) |
| Analytics | `GET /analytics/cohorts`, `/ltv`, `/engagement` |

### Supplier & inventory

| Capability | API |
|------------|-----|
| Suppliers | `GET/POST /suppliers` |
| GDS/OTA integrations | Amadeus, Hotelbeds via `tenant-integrations` |
| Package PDF import | `POST /folders/parse-package-pdf` |

### General ledger (GL)

| Capability | API |
|------------|-----|
| Chart of accounts | `GET/POST /finance/chart-of-accounts` |
| Journal entries | `GET/POST /finance/journal-entries` |
| Auto-posting from invoices/payments | `AccountsReceivableService`, `SupplierBillController` |
| Revenue recognition | `php artisan finance:recognize-revenue` |

### Accounts payable

| Capability | API |
|------------|-----|
| Supplier bills | `GET/POST /finance/bills` |
| Bill payment | `POST /finance/bills/{id}/pay` |
| AP aging | `GET /finance/reports/ap-aging` |

### Accounts receivable

| Capability | API |
|------------|-----|
| Customer invoices | `GET/POST /finance/invoices`, folder invoice |
| Payment allocation | `POST /finance/invoices/{id}/allocate` |
| AR aging | `GET /finance/reports/ar-aging` |

### Receipt management

| Capability | API |
|------------|-----|
| Expense receipts | `GET/POST /receipts`, review workflow |

### Bank module

| Capability | API |
|------------|-----|
| Bank accounts | `GET/POST /finance/bank-accounts` |
| CSV import & reconciliation | `POST .../import`, reconcile endpoints |

### User rights

| Capability | API |
|------------|-----|
| Role-based permissions | Sanctum auth + permission checks per route |
| Audit trail | `audit_logs` via `AuditLogger` |

### Business intelligence

| Capability | API |
|------------|-----|
| Dashboard KPIs | `GET /dashboard` |
| Custom analytics | Analytics controllers |
| Webhooks for external BI | `GET/POST /webhook-endpoints` |

---

## Stack & deploy

- **API:** Laravel 12, Sanctum, multi-tenant middleware
- **Frontend:** React + Vite ([adroitsolscrmfront](https://github.com/umairumar/adroitsolscrmfront))
- **Install:** `./scripts/saas-install.sh`
- **Cron:** `engagement:process-campaigns`, `webhooks:deliver`, `integrations:sync`, `saas:sync-billing`

## Matching your sample invoice

Upload your reference PDF fields via white-label settings. The PDF title shows **“INVOICE — For [Passenger Name]”** (e.g. *Mr Zieshan Khan*) with booking ref, Umrah destination, ziaraat details, totals, and balance due.

If your sample uses a different layout, share the PDF in the repo (e.g. `docs/samples/invoice.pdf`) or list required fields — the Blade template can be adjusted to match pixel-perfect.
