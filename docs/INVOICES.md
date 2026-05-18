# Customer invoice PDF

## Dependencies

```bash
composer install   # includes barryvdh/laravel-dompdf
php artisan migrate
```

## Configure agency letterhead

`PUT /api/v1/white-label` with invoice fields (see [SAAS_PLATFORM.md](./SAAS_PLATFORM.md)).

## Generate from booking

```http
POST /api/v1/finance/folders/{folderId}/invoice
Authorization: Bearer {token}
```

## Download PDF

```http
GET /api/v1/finance/invoices/{id}/pdf
```

Filename pattern: `Invoice_Mr_Zieshan_Khan_INV-2026-0001.pdf`
