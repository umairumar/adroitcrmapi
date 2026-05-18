# Adroit Travel CRM — Frontend

React + Vite UI for [adroitcrmapi](https://github.com/umairumar/adroitcrmapi).

## Local development (full stack)

**Terminal 1 — API**

```bash
cd adroitcrmapi
./scripts/saas-install.sh
php artisan serve
```

**Terminal 2 — Frontend**

```bash
cd adroitsolscrmfront
cp .env.example .env.local
npm install
npm run dev
```

Open the URL Vite prints (usually http://localhost:5173). The dev server proxies `/api` and `/sanctum` to `http://127.0.0.1:8000`.

## Production

Build with your live API URL:

```env
VITE_API_BASE_URL=https://api.haqtravels.co.uk/api/v1
VITE_BASE_UPLOAD_URL=https://api.haqtravels.co.uk/uploads
```

```bash
npm run build
```

Deploy the `dist/` folder to static hosting (Netlify, Vercel, cPanel, etc.).

Ensure the Laravel API `.env` includes your frontend domain in `SANCTUM_STATEFUL_DOMAINS`.

## SaaS dashboard

After login, dashboard pages call `GET /api/v1/dashboard` for live leads, folders, payments, trends, and agent stats.
