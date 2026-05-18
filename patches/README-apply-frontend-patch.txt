Apply SaaS dashboard wiring in adroitsolscrmfront (when branch was never pushed)

Why "src refspec cursor/wire-saas-dashboard-api-9927 does not match any":
  That branch exists only in the cloud agent workspace, not on your PC or GitHub.
  You must create the branch locally and apply this patch, then push.

Steps (Windows — PowerShell or cmd):

  1. cd C:\Users\umair\adroitsolscrmfront
  2. git checkout main
  3. git pull origin main
  4. git checkout -b cursor/wire-saas-dashboard-api-9927

  5. Download the patch (pick one):
     - From API repo (after git pull adroitcrmapi main):
       copy patches\frontend-saas-ui.patch to this folder
     - Raw URL:
       https://raw.githubusercontent.com/umairumar/adroitcrmapi/main/patches/frontend-saas-ui.patch

  6. From repo root (adroitsolscrmfront):
     git apply --ignore-whitespace frontend-saas-ui.patch
     (If paths fail, try: git apply --ignore-whitespace -p0 frontend-saas-ui.patch)

  7. npm install
  8. npm run build

  9. git add -A
  10. git commit -m "Wire CRM UI to SaaS dashboard API and improve auth"
  11. git push -u origin cursor/wire-saas-dashboard-api-9927

  12. Open a PR on GitHub: cursor/wire-saas-dashboard-api-9927 -> main

Local run after merge:
  - API: php artisan serve (port 8000), run saas-install / migrations
  - Frontend: copy .env.example to .env.local, npm run dev (Vite proxies /api to localhost)
