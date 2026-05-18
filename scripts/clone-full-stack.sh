#!/usr/bin/env bash
# Clone API + frontend side-by-side for local SaaS development.
set -euo pipefail

PARENT="${1:-.}"
API_REPO="${API_REPO:-https://github.com/umairumar/adroitcrmapi.git}"
FRONT_REPO="${FRONT_REPO:-https://github.com/umairumar/adroitsolscrmfront.git}"

mkdir -p "$PARENT"
cd "$PARENT"

if [ ! -d adroitcrmapi ]; then
  git clone "$API_REPO" adroitcrmapi
else
  echo "adroitcrmapi already exists — run: cd adroitcrmapi && git pull"
fi

if [ ! -d adroitsolscrmfront ]; then
  git clone "$FRONT_REPO" adroitsolscrmfront || {
    echo ""
    echo "Could not clone frontend (private repo?). Clone manually:"
    echo "  git clone $FRONT_REPO"
    exit 1
  }
else
  echo "adroitsolscrmfront already exists — run: cd adroitsolscrmfront && git pull"
fi

echo ""
echo "Next steps:"
echo "  cd adroitcrmapi && ./scripts/saas-install.sh && php artisan serve"
echo "  cd adroitsolscrmfront && npm install && npm run dev"
echo "  Set frontend API URL to http://127.0.0.1:8000"
