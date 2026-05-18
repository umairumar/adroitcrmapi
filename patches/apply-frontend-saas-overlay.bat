@echo off
REM Run from adroitsolscrmfront repo root:
REM   C:\Users\umair\adroitsolscrmfront> path\to\apply-frontend-saas-overlay.bat

set "SRC=%~dp0frontend-saas-files"
set "DST=%CD%"

if not exist "%SRC%\src\api\dashboardAPI.ts" (
  echo ERROR: frontend-saas-files folder not found beside this script.
  echo Copy patches\frontend-saas-files and this .bat from adroitcrmapi.
  exit /b 1
)

echo Copying SaaS UI files into: %DST%
xcopy "%SRC%\*" "%DST%\" /E /Y /I /Q
if errorlevel 1 (
  echo xcopy failed.
  exit /b 1
)

if exist "%DST%\.env" (
  echo Removing tracked .env ^(use .env.example / .env.local instead^)...
  del "%DST%\.env"
)

echo Done. Next:
echo   npm install
echo   npm run build
echo   git add -A
echo   git commit -m "Wire CRM UI to SaaS dashboard API and improve auth"
echo   git push -u origin cursor/wire-saas-dashboard-api-9927
