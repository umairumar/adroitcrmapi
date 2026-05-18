Apply SaaS dashboard wiring in adroitsolscrmfront

=== If git apply fails (patch does not apply) ===

Your folder may be half-patched. Reset, then use the FILE OVERLAY (recommended on Windows):

  1. cd C:\Users\umair\adroitsolscrmfront
  2. git checkout main
  3. git pull origin main
  4. git reset --hard origin/main
  5. git clean -fd

  6. Copy from adroitcrmapi repo (after git pull):
       patches\frontend-saas-files\   (entire folder)
       patches\apply-frontend-saas-overlay.bat

     Or download ZIP from GitHub (main branch, patches folder).

  7. Put apply-frontend-saas-overlay.bat + frontend-saas-files\ in one folder,
     then from adroitsolscrmfront root run:
       C:\path\to\apply-frontend-saas-overlay.bat

  8. npm install && npm run build
  9. git checkout -b cursor/wire-saas-dashboard-api-9927
  10. git add -A && git commit -m "Wire CRM UI to SaaS dashboard API and improve auth"
  11. git push -u origin cursor/wire-saas-dashboard-api-9927

=== Patch method (only on clean main @ 6bcfeab) ===

  git checkout -b cursor/wire-saas-dashboard-api-9927
  curl -L -o frontend-saas-ui.patch https://raw.githubusercontent.com/umairumar/adroitcrmapi/main/patches/frontend-saas-ui.patch
  git apply --ignore-whitespace frontend-saas-ui.patch

  If you still see failures, you are not on clean main — use overlay method above.
